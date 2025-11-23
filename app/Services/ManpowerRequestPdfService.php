<?php

namespace App\Services;

use App\Models\Item;
use App\Models\ManpowerRequest;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use RuntimeException;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\PdfParser;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\PdfParser\Type\PdfArray;
use setasign\Fpdi\PdfParser\Type\PdfDictionary;
use setasign\Fpdi\PdfParser\Type\PdfName;
use setasign\Fpdi\PdfParser\Type\PdfNumeric;
use setasign\Fpdi\PdfParser\Type\PdfString;
use setasign\Fpdi\PdfParser\Type\PdfType;
use Throwable;

class ManpowerRequestPdfService
{
    /**
     * Cached AcroForm field layout keyed by identifier.
     *
     * @var array<string, array{llx: float, lly: float, urx: float, ury: float}>|null
     */
    private ?array $fieldLayout = null;

    private string $templatePath;

    private string $originalTemplatePath;

    private ?string $preparedTemplatePath = null;

    private bool $repairAttempted = false;

    public function __construct(?string $templatePath = null)
    {
        $storageTemplate = storage_path('app/templates/borrow_request_form_v2.pdf');
        $publicTemplate = public_path('pdf/borrow_request_form_v2.pdf');

        $resolvedTemplate = $templatePath ?? (is_file($storageTemplate) ? $storageTemplate : $publicTemplate);
        $this->originalTemplatePath = $resolvedTemplate;

        $preparedCandidate = storage_path('app/templates/borrow_request_form_v2.prepared.pdf');
        if (is_file($preparedCandidate)) {
            $this->preparedTemplatePath = $preparedCandidate;
        }

        if ($this->preparedTemplatePath && is_file($this->preparedTemplatePath)) {
            $this->templatePath = $this->preparedTemplatePath;
        } else {
            $this->templatePath = $resolvedTemplate;
        }
    }

    /**
     * @return array{success: bool, content?: string, filename?: string, mime?: string, message?: string}
     */
    public function render(ManpowerRequest $manpowerRequest): array
    {
        try {
            if (! class_exists(Fpdi::class)) {
                throw new RuntimeException('PDF generation library (setasign/fpdi) is missing.');
            }

            if (! is_file($this->templatePath)) {
                if ($this->templatePath !== $this->originalTemplatePath && is_file($this->originalTemplatePath)) {
                    $this->templatePath = $this->originalTemplatePath;
                } else {
                    throw new RuntimeException('Manpower request template not found.');
                }
            }

            $layout = $this->getFieldLayout();
            if ($layout === []) {
                throw new RuntimeException('No AcroForm fields were detected in the manpower request template.');
            }

            $manpowerRequest->loadMissing('user');

            $pdf = new Fpdi('P', 'pt');
            $pdf->SetAutoPageBreak(false);
            $pdf->SetMargins(0, 0, 0);

            $pageCount = $pdf->setSourceFile($this->templatePath);
            if ($pageCount < 1) {
                throw new RuntimeException('The manpower request template does not contain any pages.');
            }

            $templateId = $pdf->importPage(1);
            $size = $pdf->getTemplateSize($templateId);

            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId, 0, 0, $size['width'], $size['height']);

            $fieldData = $this->getFillableData($manpowerRequest);

            foreach ($fieldData as $field => $value) {
                if (str_starts_with($field, 'check_')) {
                    $this->renderCheckbox($pdf, $size, Arr::get($layout, $field), $this->normalizeCheckboxValue($value));
                    continue;
                }

                $this->writeText($pdf, $size, Arr::get($layout, $field), $value);
            }

            $qrRect = Arr::get($layout, 'form_qr_code') ?? Arr::get($layout, 'form_qr_code_af_image');
            $this->renderQrCode($pdf, $size, $qrRect, $manpowerRequest);

            $binary = $pdf->Output('S');

            return [
                'success' => true,
                'content' => $binary,
                'filename' => 'manpower-request-' . $manpowerRequest->id . '.pdf',
                'mime' => 'application/pdf',
            ];
        } catch (Throwable $e) {
            Log::error('Failed to render manpower request PDF', [
                'manpower_request_id' => $manpowerRequest->id ?? null,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Build the value map that will be merged into the PDF.
     *
     * @return array<string, string>
     */
    private function getFillableData(ManpowerRequest $manpowerRequest): array
    {
        $user = $manpowerRequest->user;
        $timezone = config('app.timezone');
        $start = $manpowerRequest->start_at?->copy()->timezone($timezone);
        $end = $manpowerRequest->end_at?->copy()->timezone($timezone);

        $officeName = '';
        if ($user && method_exists($user, 'office')) {
            $officeName = (string) ($user->office?->name ?? '');
        }
        if ($officeName === '') {
            $officeName = (string) ($manpowerRequest->office_agency ?? '');
        }

        $addressParts = array_filter([
            $manpowerRequest->location,
            $manpowerRequest->barangay,
            $manpowerRequest->municipality,
        ], static fn ($value) => is_string($value) && trim($value) !== '');

        $address = implode(', ', $addressParts);
        if ($address === '' && is_string($user?->address) && trim($user->address) !== '') {
            $address = $user->address;
        }

        $name = '';
        if ($user) {
            $name = trim((string) ($user->full_name ?? ''));
            if ($name === '') {
                $segments = array_filter([
                    $user->first_name ?? null,
                    $user->middle_name ?? null,
                    $user->last_name ?? null,
                ], static fn ($value) => is_string($value) && trim($value) !== '');

                if ($segments !== []) {
                    $name = implode(' ', $segments);
                } elseif (is_string($user->name) && trim($user->name) !== '') {
                    $name = $user->name;
                }
            }
        }

        $fields = [
            'form_roa' => $officeName,
            'form_cn' => $user?->phone ?? '',
            'form_address' => $address,
            'form_purpose' => $manpowerRequest->purpose ?? '',
            'form_db' => $this->formatDate($start),
            'form_dtr' => $this->formatDate($end),
            'form_tou' => $this->formatTimeRange($start, $end),
            'form_name' => $name,
        ];

        $roleLabel = trim((string) ($manpowerRequest->role ?? 'Manpower'));
        $baseLabel = $roleLabel !== '' ? $roleLabel : 'Manpower';
        $approvedQuantity = (int) ($manpowerRequest->approved_quantity ?? 0);
        $quantity = $approvedQuantity > 0 ? $approvedQuantity : max((int) $manpowerRequest->quantity, 0);
        if ($quantity <= 0) {
            $quantity = max((int) $manpowerRequest->quantity, 0);
        }
        $fields['item_1'] = sprintf('Manpower-%s(x%d)', $baseLabel, $quantity);
        $fields['check_1'] = 'Yes';

        $randomItems = Item::query()
            ->inRandomOrder()
            ->limit(11)
            ->get(['name']);

        $slot = 2;
        foreach ($randomItems as $item) {
            if ($slot > 12) {
                break;
            }

            $fields['item_' . $slot] = $item->name ?? '';
            $fields['check_' . $slot] = 'Off';
            $slot++;
        }

        for (; $slot <= 12; $slot++) {
            $fields['item_' . $slot] = '';
            $fields['check_' . $slot] = 'Off';
        }

        return $fields;
    }

    private function normalizeCheckboxValue(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['yes', 'true', '1', 'on'], true);
    }

    private function formatDate(?Carbon $date): string
    {
        if (! $date) {
            return '';
        }

        return $date->format('F j, Y');
    }

    private function formatTimeRange(?Carbon $start, ?Carbon $end): string
    {
        $startValue = $this->formatTime($start);
        $endValue = $this->formatTime($end);

        if ($startValue && $endValue) {
            return $startValue . ' - ' . $endValue;
        }

        return $startValue ?: $endValue;
    }

    private function formatTime(?Carbon $date): string
    {
        if (! $date) {
            return '';
        }

        if ($date->format('H:i:s') === '00:00:00') {
            return '';
        }

        return $date->format('g:i A');
    }

    private function writeText(Fpdi $pdf, array $pageSize, ?array $rect, ?string $value): void
    {
        $text = trim((string) ($value ?? ''));
        if ($text === '' || $rect === null) {
            return;
        }

        $paddingX = 4.0;
        $paddingY = 3.0;
        $width = max($rect['urx'] - $rect['llx'] - ($paddingX * 2), 4);
        $height = max($rect['ury'] - $rect['lly'] - ($paddingY * 2), 10);

        $fontSize = $this->resolveFontSize($width, $height, $text, 9.0, 12.5);

        $pdf->SetFont('Helvetica', '', $fontSize);
        $textHeight = $pdf->GetStringWidth($this->encode($text)) > $width
            ? $fontSize * 2
            : $fontSize;

        $yOffset = ($height - $textHeight) / 2;
        $x = $rect['llx'] + $paddingX;
        $y = ($pageSize['height'] - $rect['ury']) + $paddingY + max($yOffset, 0);

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY($x, $y);
        $lineHeight = min(max($height / 2.2, 12), 18);
        $pdf->MultiCell($width, $lineHeight, $this->encode($text), 0, 'L');
    }

    private function renderCheckbox(Fpdi $pdf, array $pageSize, ?array $rect, bool $checked): void
    {
        if (! $checked || $rect === null) {
            return;
        }

        $width = max($rect['urx'] - $rect['llx'], 8);
        $height = max($rect['ury'] - $rect['lly'], 8);
        $box = min($width, $height);
        $x = $rect['llx'] + (($width - $box) / 2);
        $y = ($pageSize['height'] - $rect['ury']) + (($height - $box) / 2);

        $pdf->SetTextColor(55, 65, 81);
        $pdf->SetFont('ZapfDingbats', '', $box);
        $pdf->Text($x, $y + $box, '4');
    }

    private function renderQrCode(Fpdi $pdf, array $pageSize, ?array $rect, ManpowerRequest $manpowerRequest): void
    {
        if ($rect === null) {
            return;
        }

        $payload = trim($this->buildQrPayload($manpowerRequest));
        if ($payload === '') {
            return;
        }

        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_Q,
            'scale' => 6,
            'imageBase64' => false,
            'addQuietzone' => true,
            'dataModeOverride' => 'byte',
        ]);

        $binary = (new QRCode($options))->render($payload);
        if (! is_string($binary) || $binary === '') {
            return;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'qr');
        if ($tmp === false) {
            return;
        }

        try {
            file_put_contents($tmp, $binary);

            $width = max($rect['urx'] - $rect['llx'], 24);
            $height = max($rect['ury'] - $rect['lly'], 24);
            $x = $rect['llx'];
            $y = $pageSize['height'] - $rect['ury'];

            $pdf->Image($tmp, $x, $y, $width, $height, 'PNG');
        } finally {
            @unlink($tmp);
        }
    }

    private function buildQrPayload(ManpowerRequest $manpowerRequest): string
    {
        if ($manpowerRequest->exists && Route::has('admin.manpower.requests.scan')) {
            try {
                return URL::temporarySignedRoute(
                    'admin.manpower.requests.scan',
                    now()->addDays(30),
                    ['manpowerRequest' => $manpowerRequest->id]
                );
            } catch (Throwable) {
                // fall through to fallback payloads
            }
        }

        if (method_exists($manpowerRequest, 'getPublicStatusUrlAttribute')) {
            $url = $manpowerRequest->public_status_url;
            if (is_string($url) && $url !== '') {
                return $url;
            }
        }

        if (is_string($manpowerRequest->public_token) && $manpowerRequest->public_token !== '') {
            try {
                if (Route::has('manpower.requests.public.show')) {
                    return route('manpower.requests.public.show', $manpowerRequest->public_token);
                }
            } catch (Throwable) {
                // ignore and fall back
            }
        }

        return (string) $manpowerRequest->id;
    }

    private function resolveFontSize(float $width, float $height, string $value, float $min, float $max): float
    {
        $length = max(mb_strlen($value, 'UTF-8'), 1);
        $estimated = $width / ($length * 0.55);

        return max(min($estimated, $max), $min, min($height, $max));
    }

    private function encode(string $value): string
    {
        $encoded = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $value);
        if ($encoded === false) {
            return preg_replace('/[^\x20-\x7E]/', '', $value) ?? $value;
        }

        return $encoded;
    }

    /**
     * @return array<string, array{llx: float, lly: float, urx: float, ury: float}>
     */
    private function getFieldLayout(): array
    {
        if ($this->fieldLayout !== null) {
            return $this->fieldLayout;
        }

        $layout = $this->parseFieldLayout($this->templatePath);

        if ($layout === [] && ! $this->repairAttempted) {
            $this->repairAttempted = true;
            if ($this->attemptAutoRepairTemplate()) {
                $layout = $this->parseFieldLayout($this->templatePath);
            }
        }

        $this->fieldLayout = $layout;

        return $this->fieldLayout;
    }

    /**
     * @return array<string, array{llx: float, lly: float, urx: float, ury: float}>
     */
    private function parseFieldLayout(string $path): array
    {
        $layout = [];

        try {
            $parser = new PdfParser(StreamReader::createByFile($path));
            $catalog = PdfDictionary::ensure($parser->getCatalog());

            if (! isset($catalog->value['AcroForm'])) {
                return $layout;
            }

            $acroForm = PdfDictionary::ensure(
                PdfType::resolve($catalog->value['AcroForm'], $parser)
            );

            if (! isset($acroForm->value['Fields'])) {
                return $layout;
            }

            $fields = PdfArray::ensure(
                PdfType::resolve($acroForm->value['Fields'], $parser)
            )->value;

            foreach ($fields as $fieldRef) {
                $this->collectFieldLayout($parser, $fieldRef);
            }
        } catch (Throwable $e) {
            Log::warning('ManpowerRequestPdfService: unable to parse template layout', [
                'template' => $path,
                'message' => $e->getMessage(),
            ]);
        }

        return $this->fieldLayout ?? [];
    }

    private function collectFieldLayout(PdfParser $parser, mixed $fieldObject, string $parent = ''): void
    {
        try {
            $field = PdfDictionary::ensure(PdfType::resolve($fieldObject, $parser));
        } catch (Throwable) {
            return;
        }

        $values = $field->value;
        $name = $parent;

        if (isset($values['T'])) {
            $resolvedName = PdfType::resolve($values['T'], $parser);
            if ($resolvedName instanceof PdfString || $resolvedName instanceof PdfName) {
                $raw = (string) $resolvedName->value;
                $name = $parent !== '' ? $parent . '.' . $raw : $raw;
            }
        }

        if (isset($values['Kids'])) {
            $kids = PdfArray::ensure(PdfType::resolve($values['Kids'], $parser))->value;
            foreach ($kids as $kid) {
                $this->collectFieldLayout($parser, $kid, $name);
            }
            return;
        }

        if ($name === '' || ! isset($values['Rect'])) {
            return;
        }

        try {
            $rect = PdfArray::ensure(PdfType::resolve($values['Rect'], $parser))->value;
        } catch (Throwable) {
            return;
        }

        $resolved = $this->resolveRect($parser, $rect);
        if ($resolved === null) {
            return;
        }

        $this->fieldLayout[$name] = $resolved;
    }

    /**
     * @param array<int, mixed> $items
     * @return array{llx: float, lly: float, urx: float, ury: float}|null
     */
    private function resolveRect(PdfParser $parser, array $items): ?array
    {
        if (count($items) !== 4) {
            return null;
        }

        $resolved = [];
        foreach ($items as $item) {
            $value = PdfType::resolve($item, $parser);
            if ($value instanceof PdfNumeric) {
                $resolved[] = (float) $value->value;
                continue;
            }

            if ($value instanceof PdfString) {
                $resolved[] = (float) $value->value;
                continue;
            }

            return null;
        }

        if (count($resolved) !== 4) {
            return null;
        }

        return [
            'llx' => (float) $resolved[0],
            'lly' => (float) $resolved[1],
            'urx' => (float) $resolved[2],
            'ury' => (float) $resolved[3],
        ];
    }

    private function attemptAutoRepairTemplate(): bool
    {
        $qpdf = $this->findQpdfBinary();
        if (! $qpdf) {
            return false;
        }

        if (! is_file($this->originalTemplatePath)) {
            return false;
        }

        $target = $this->preparedTemplatePath ?? ($this->originalTemplatePath . '.prepared.pdf');

        $directory = dirname($target);
        if (! is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        $result = $this->executeCommand([
            $qpdf,
            '--qdf',
            '--object-streams=disable',
            $this->originalTemplatePath,
            $target,
        ]);

        if ($result['exit_code'] !== 0) {
            Log::warning('ManpowerRequestPdfService: qpdf failed to prepare template', [
                'exit_code' => $result['exit_code'],
                'stderr' => $result['stderr'] ?? '',
            ]);
            if (is_file($target) && $target !== $this->originalTemplatePath) {
                @unlink($target);
            }
            return false;
        }

        $this->templatePath = $target;
        $this->fieldLayout = null;

        Log::info('ManpowerRequestPdfService: prepared form template using qpdf.', [
            'template' => $this->templatePath,
            'qpdf' => $qpdf,
        ]);

        return true;
    }

    private function findQpdfBinary(): ?string
    {
        $candidates = PHP_OS_FAMILY === 'Windows'
            ? [['where', 'qpdf.exe'], ['where', 'qpdf']]
            : [['which', 'qpdf']];

        foreach ($candidates as $command) {
            $result = $this->executeCommand($command);
            if ($result['exit_code'] === 0 && ! empty($result['stdout'])) {
                $path = trim(strtok($result['stdout'], "\r\n"));
                if ($path !== '' && is_file($path)) {
                    return $path;
                }
            }
        }

        return null;
    }

    /**
     * @param array<int, string>|string $command
     * @return array{exit_code:int, stdout:string, stderr:string}
     */
    private function executeCommand(array|string $command): array
    {
        if (is_array($command)) {
            $command = implode(' ', array_map('escapeshellarg', $command));
        }

        $spec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $proc = @proc_open($command, $spec, $pipes, base_path());

        if (! is_resource($proc)) {
            return ['exit_code' => 1, 'stdout' => '', 'stderr' => 'Unable to spawn process.'];
        }

        if (isset($pipes[0])) {
            fclose($pipes[0]);
        }

        $stdout = isset($pipes[1]) ? (stream_get_contents($pipes[1]) ?: '') : '';
        $stderr = isset($pipes[2]) ? (stream_get_contents($pipes[2]) ?: '') : '';

        if (isset($pipes[1])) {
            fclose($pipes[1]);
        }
        if (isset($pipes[2])) {
            fclose($pipes[2]);
        }

        $exit = proc_close($proc);

        return [
            'exit_code' => $exit,
            'stdout' => trim($stdout),
            'stderr' => trim($stderr),
        ];
    }
}
