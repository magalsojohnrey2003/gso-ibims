<?php

namespace App\Services;

use App\Models\WalkInRequest;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
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

class WalkInRequestPdfService
{
    /** @var array<string, array{llx: float, lly: float, urx: float, ury: float}>|null */
    private ?array $fieldLayout = null;

    private string $templatePath;

    private string $originalTemplatePath;

    private ?string $preparedTemplatePath = null;

    private bool $repairAttempted = false;

    public function __construct(?string $templatePath = null)
    {
        $storageTemplate = storage_path('app/templates/borrow_request_form_v2.pdf');
        $publicTemplate = public_path('pdf/borrow_request_form_v2.pdf');

        // Use provided path or default to public template
        $configured = $templatePath ?? (is_file($storageTemplate) ? $storageTemplate : $publicTemplate);
        $this->originalTemplatePath = $configured;

        // We keep a prepared (qpdf-uncompressed) copy here
        $this->preparedTemplatePath = storage_path('app/templates/borrow_request_form_v2.prepared.pdf');

        // Prefer the prepared copy if already present
        if ($this->preparedTemplatePath && is_file($this->preparedTemplatePath)) {
            $this->templatePath = $this->preparedTemplatePath;
        } else {
            $this->templatePath = $configured;
        }
    }

    /**
     * @return array{success: bool, content?: string, filename?: string, mime?: string, message?: string}
     */
    public function render(WalkInRequest $walkInRequest): array
    {
        try {
            if (! class_exists(Fpdi::class)) {
                throw new RuntimeException('PDF generation library (setasign/fpdi) is not available.');
            }

            if (! is_file($this->templatePath)) {
                throw new RuntimeException('Walk-in request PDF template not found.');
            }

            $layout = $this->getFieldLayout();
            if ($layout === []) {
                throw new RuntimeException('No AcroForm fields found in walk-in request template.');
            }

            $walkInRequest->loadMissing(['items.item']);

            $pdf = new Fpdi('P', 'pt');
            $pdf->SetAutoPageBreak(false);
            $pdf->SetMargins(0, 0, 0);

            $pageCount = $pdf->setSourceFile($this->templatePath);
            if ($pageCount < 1) {
                throw new RuntimeException('Walk-in template has no pages.');
            }

            $templateId = $pdf->importPage(1);
            $size = $pdf->getTemplateSize($templateId);

            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId, 0, 0, $size['width'], $size['height']);

            $timezone = config('app.timezone');
            $borrowedAt = $walkInRequest->borrowed_at?->timezone($timezone);
            $returnedAt = $walkInRequest->returned_at?->timezone($timezone);

            $fields = [
                'form_roa' => $walkInRequest->office_agency ?? '',
                'form_name' => $walkInRequest->borrower_name ?? '',
                'form_cn' => $walkInRequest->contact_number ?? '',
                'form_address' => $walkInRequest->address ?? '',
                'form_purpose' => $walkInRequest->purpose ?? '',
                'form_db' => $this->formatDate($borrowedAt),
                'form_dtr' => $this->formatDate($returnedAt),
                'form_tou' => $this->formatTimeOfUsage($borrowedAt, $returnedAt),
            ];

            foreach ($fields as $field => $value) {
                $this->writeText($pdf, $size, Arr::get($layout, $field), $value);
            }

            // Generate and render QR code for approval
            $qrUrl = $this->generateQrApprovalUrl($walkInRequest);
            if ($qrUrl) {
                $qrRect = Arr::get($layout, 'form_qr_code') ?? Arr::get($layout, 'form_qr_code_af_image');
                $this->renderQrCode($pdf, $size, $qrRect, $qrUrl);
            }

            $this->renderItems($pdf, $size, $layout, $walkInRequest);

            $binary = $pdf->Output('S');

            return [
                'success' => true,
                'content' => $binary,
                'filename' => 'walk-in-request-' . $walkInRequest->id . '.pdf',
                'mime' => 'application/pdf',
            ];
        } catch (Throwable $e) {
            Log::error('Failed to render walk-in request PDF', [
                'walk_in_request_id' => $walkInRequest->id ?? null,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    private function renderItems(Fpdi $pdf, array $pageSize, array $layout, WalkInRequest $walkInRequest): void
    {
        $rows = $walkInRequest->items->map(function ($item) {
            $name = $item->item?->name ?? ('Item #' . $item->item_id);
            $quantity = (int) $item->quantity;
            return [
                'label' => $name . ' (x' . $quantity . ')',
                'checked' => false,
            ];
        })->values();

        // Include manpower as a dedicated row when present
        $manpowerRole = trim((string) ($walkInRequest->manpower_role ?? ''));
        $manpowerQty = (int) ($walkInRequest->manpower_quantity ?? 0);
        if ($manpowerRole !== '' || $manpowerQty > 0) {
            $roleLabel = $manpowerRole !== '' ? $manpowerRole : 'Manpower';
            $qtyLabel = $manpowerQty > 0 ? $manpowerQty : 1;
            $rows->prepend([
                'label' => 'Manpower - ' . $roleLabel . ' (x' . $qtyLabel . ')',
                'checked' => false,
            ]);
        }

        // Only render actual rows; pad the rest with blanks (no random placeholders)
        $rows = $rows->take(12)->values();
        while ($rows->count() < 12) {
            $rows->push([
                'label' => '',
                'checked' => false,
            ]);
        }

        for ($i = 0; $i < 12; $i++) {
            $slot = $rows[$i];
            $index = $i + 1;
            $this->writeText($pdf, $pageSize, Arr::get($layout, 'item_' . $index), $slot['label']);
            $this->renderCheckbox($pdf, $pageSize, Arr::get($layout, 'check_' . $index), (bool) $slot['checked']);
        }
    }

    private function formatDate(?Carbon $date): string
    {
        if (! $date) {
            return '';
        }

        return $date->format('F j, Y');
    }

    private function formatTimeOfUsage(?Carbon $borrowedAt, ?Carbon $returnedAt): string
    {
        $start = $this->formatTime($borrowedAt);
        $end = $this->formatTime($returnedAt);

        if ($start && $end) {
            return $start . ' - ' . $end;
        }

        return '';
    }

    private function formatTime(?Carbon $date): string
    {
        if (! $date) {
            return '';
        }

        return $date->format('H:i:s') === '00:00:00'
            ? ''
            : $date->format('g:i A');
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
            ? $fontSize * 2  // Multi-line text
            : $fontSize;     // Single line

        // Center vertically
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
        $pdf->Text($x, $y + $box, '4'); // ZapfDingbats glyph "4" is a checkmark
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

        // Parse current template
        $layout = $this->parseFieldLayout($this->templatePath);

        // If parsing failed (e.g., object streams/compression), try to auto-prepare with qpdf once
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
            Log::warning('WalkInRequestPdfService: unable to parse template layout', [
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
            Log::warning('WalkInRequestPdfService: qpdf failed to prepare template', [
                'exit_code' => $result['exit_code'],
                'stderr' => $result['stderr'] ?? '',
            ]);
            if (is_file($target) && $target !== $this->originalTemplatePath) {
                @unlink($target);
            }
            return false;
        }

        // Switch to prepared copy
        $this->templatePath = $target;
        $this->fieldLayout = null;

        Log::info('WalkInRequestPdfService: prepared walk-in form template using qpdf.', [
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

        if (isset($pipes[0])) { fclose($pipes[0]); }
        $stdout = isset($pipes[1]) ? (stream_get_contents($pipes[1]) ?: '') : '';
        $stderr = isset($pipes[2]) ? (stream_get_contents($pipes[2]) ?: '') : '';
        if (isset($pipes[1])) { fclose($pipes[1]); }
        if (isset($pipes[2])) { fclose($pipes[2]); }

        $exit = proc_close($proc);

        return [
            'exit_code' => $exit,
            'stdout' => trim($stdout),
            'stderr' => trim($stderr),
        ];
    }

    /**
     * Render QR code image in the PDF
     */
    private function renderQrCode(Fpdi $pdf, array $pageSize, ?array $rect, string $url): void
    {
        if ($rect === null || $url === '') {
            return;
        }

        try {
            $options = new QROptions([
                'outputType' => QRCode::OUTPUT_IMAGE_PNG,
                'eccLevel' => QRCode::ECC_H,
                'scale' => 8,
                'imageBase64' => false,
                'addQuietzone' => true,
            ]);

            $qrCode = new QRCode($options);
            $binary = $qrCode->render($url);

            if (!is_string($binary) || $binary === '') {
                Log::warning('Failed to generate QR code binary');
                return;
            }

            // Create temporary file
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
        } catch (Throwable $e) {
            Log::warning('Failed to render QR code in walk-in PDF', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Generate a temporary signed URL for QR code approval
     */
    private function generateQrApprovalUrl(WalkInRequest $walkInRequest): ?string
    {
        try {
            return URL::temporarySignedRoute(
                'admin.walkin.approve.qr',
                now()->addDays(30),
                ['id' => $walkInRequest->id]
            );
        } catch (Throwable $e) {
            Log::warning('Failed to generate QR approval URL', [
                'walk_in_request_id' => $walkInRequest->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
