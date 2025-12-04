<?php

namespace App\Services;

use App\Models\BorrowRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;
use setasign\Fpdi\Fpdi;

class RoutingSlipPdf
{
    private const FONT_FAMILY = 'Times';
    private const FONT_SIZE = 10.0;
    private const BASELINE_OFFSET = 2.0;

    /**
     * Rectangles for each AcroForm field; some fields appear multiple times on the template.
     *
     * @var array<string, array<int, array{llx: float, lly: float, urx: float, ury: float}>>
     */
    private const FIELD_LAYOUT = [
        'request_slip' => [
            ['llx' => 145.733, 'lly' => 200.075, 'urx' => 266.469, 'ury' => 222.075],
            ['llx' => 110.617, 'lly' => 529.502, 'urx' => 231.353, 'ury' => 551.502],
        ],
        'bname_slip' => [
            ['llx' => 137.337, 'lly' => 638.337, 'urx' => 287.337, 'ury' => 660.337],
            ['llx' => 66.147, 'lly' => 487.212, 'urx' => 203.671, 'ury' => 509.212],
            ['llx' => 52.9107, 'lly' => 395.285, 'urx' => 202.317, 'ury' => 417.285],
            ['llx' => 348.692, 'lly' => 279.809, 'urx' => 471.342, 'ury' => 301.809],
        ],
        'baddress_slip' => [
            ['llx' => 147.382, 'lly' => 616.008, 'urx' => 553.667, 'ury' => 638.008],
        ],
        'bphone_slip' => [
            ['llx' => 404.727, 'lly' => 640.232, 'urx' => 554.727, 'ury' => 662.232],
        ],
        'bdescription1_slip' => [
            ['llx' => 133.124, 'lly' => 594.301, 'urx' => 501.484, 'ury' => 616.301],
            ['llx' => 168.838, 'lly' => 244.885, 'urx' => 505.343, 'ury' => 266.885],
        ],
        'bdescription2_slip' => [
            ['llx' => 53.2635, 'lly' => 572.98, 'urx' => 501.914, 'ury' => 594.98],
            ['llx' => 89.0575, 'lly' => 223.068, 'urx' => 505.417, 'ury' => 245.068],
        ],
        'date_slip' => [
            ['llx' => 437.28, 'lly' => 121.8, 'urx' => 514.2, 'ury' => 145.8],
        ],
    ];

    private string $templatePath;

    public function __construct(?string $templatePath = null, ?string $preparedTemplatePath = null)
    {
        $defaultPrepared = storage_path('app/templates/routing_slip.prepared.pdf');
        $defaultTemplate = public_path('pdf/routing_slip.pdf');

        $prepared = $preparedTemplatePath ?: $defaultPrepared;
        $resolved = $templatePath ?: $defaultTemplate;

        if ($prepared && is_file($prepared)) {
            $this->templatePath = $prepared;
        } else {
            $this->templatePath = $resolved;
        }
    }

    /**
     * @return array{filename: string, content: string}
     */
    public function render(BorrowRequest $borrowRequest, ?string $filename = null): array
    {
        if (! class_exists(Fpdi::class)) {
            throw new RuntimeException('setasign/fpdi is required to render the routing slip.');
        }

        if (! is_file($this->templatePath)) {
            throw new RuntimeException('Routing slip template not found at ' . $this->templatePath . '.');
        }

        $borrowRequest->loadMissing(['user', 'items.item', 'items.manpowerRole']);

        $pdf = new Fpdi('P', 'pt');
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0);

        $pageCount = $pdf->setSourceFile($this->templatePath);
        if ($pageCount < 1) {
            throw new RuntimeException('The routing slip template does not contain any pages.');
        }

        $templateId = $pdf->importPage(1);
        $size = $pdf->getTemplateSize($templateId);

        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $pdf->useTemplate($templateId, 0, 0, $size['width'], $size['height']);

        $requestCode = $this->formatRequestCode($borrowRequest);
        $borrowerName = $this->resolveBorrowerName($borrowRequest);
        $address = $this->resolveAddress($borrowRequest);
        $phone = trim((string) ($borrowRequest->user?->phone ?? ''));
        [$descLine1, $descLine2] = $this->buildDescriptionLines($borrowRequest);
        $dateValue = $this->formatDate($borrowRequest->delivered_at ?? $borrowRequest->updated_at ?? Carbon::now());

        $this->writeField($pdf, $size, 'request_slip', $requestCode);
        $this->writeField($pdf, $size, 'bname_slip', $borrowerName);
        $this->writeField($pdf, $size, 'baddress_slip', $address);
        $this->writeField($pdf, $size, 'bphone_slip', $phone);
        $this->writeField($pdf, $size, 'bdescription1_slip', $descLine1);
        $this->writeField($pdf, $size, 'bdescription2_slip', $descLine2);
        $this->writeField($pdf, $size, 'date_slip', $dateValue, ['align' => 'C']);

        $binary = $pdf->Output('S');

        $outputName = $filename ?: sprintf('routing-slip-%s.pdf', Str::slug($requestCode ?: (string) $borrowRequest->id, '-'));

        return [
            'filename' => $this->sanitizeFilename($outputName),
            'content' => $binary,
        ];
    }

    private function writeField(Fpdi $pdf, array $pageSize, string $name, ?string $value, array $options = []): void
    {
        $text = trim((string) ($value ?? ''));
        if ($text === '') {
            return;
        }

        foreach (self::FIELD_LAYOUT[$name] ?? [] as $rect) {
            $this->writeText($pdf, $pageSize, $rect, $text, $options);
        }
    }

    /**
     * @param array{llx: float, lly: float, urx: float, ury: float} $rect
     */
    private function writeText(Fpdi $pdf, array $pageSize, array $rect, string $value, array $options = []): void
    {
        $paddingX = $options['padding_x'] ?? 4.0;
        $paddingY = $options['padding_y'] ?? 3.0;

        $fieldWidth = max($rect['urx'] - $rect['llx'], 4);
        $fieldHeight = max($rect['ury'] - $rect['lly'], 10);

        $width = max($fieldWidth - ($paddingX * 2), 4);
        $height = max($fieldHeight - ($paddingY * 2), 10);

        $x = $rect['llx'] + $paddingX;
        $top = $pageSize['height'] - $rect['ury'];

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont(self::FONT_FAMILY, '', self::FONT_SIZE);

        $lineHeight = $options['line_height'] ?? 12.0;
        $baselineShift = $options['baseline_offset'] ?? self::BASELINE_OFFSET;
        $y = $top + $fieldHeight - $paddingY - $lineHeight + $baselineShift;

        $pdf->SetXY($x, $y);
        $pdf->MultiCell($width, $lineHeight, $this->encode($value), 0, $options['align'] ?? 'L');
    }

    private function encode(string $value): string
    {
        $encoded = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $value);
        if ($encoded === false) {
            return preg_replace('/[^\x20-\x7E]/', '', $value) ?? $value;
        }

        return $encoded;
    }

    private function formatRequestCode(BorrowRequest $borrowRequest): string
    {
        $formatted = trim((string) ($borrowRequest->formatted_request_id ?? ''));
        if ($formatted !== '') {
            return $formatted;
        }

        $id = (int) $borrowRequest->id;
        return sprintf('BR-%04d', $id > 0 ? $id : 0);
    }

    private function resolveBorrowerName(BorrowRequest $borrowRequest): string
    {
        $user = $borrowRequest->user;
        if (! $user) {
            return '';
        }

        $name = trim((string) ($user->full_name ?? ''));
        if ($name !== '') {
            return $name;
        }

        $parts = array_filter([
            $user->first_name ?? null,
            $user->last_name ?? null,
        ]);

        if (! empty($parts)) {
            return trim(implode(' ', $parts));
        }

        return trim((string) ($user->name ?? ''));
    }

    private function resolveAddress(BorrowRequest $borrowRequest): string
    {
        $candidates = [
            $borrowRequest->location,
            $borrowRequest->user?->address,
        ];

        foreach ($candidates as $candidate) {
            $value = trim((string) ($candidate ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function buildDescriptionLines(BorrowRequest $borrowRequest): array
    {
        $items = $borrowRequest->items ?? collect();

        $labels = $items
            ->filter(fn ($item) => ! (bool) ($item->is_manpower ?? false))
            ->map(function ($item) {
                $name = trim((string) ($item->item->name ?? $item->name ?? 'Item'));
                $qty = max(0, (int) ($item->quantity ?? 0));
                return sprintf('%s (x%d)', $name === '' ? 'Item' : $name, $qty);
            })
            ->values();

        if ($labels->isEmpty()) {
            $labels = collect(['No physical items recorded']);
        }

        $raw = $labels->implode('; ');
        $wrapped = wordwrap($raw, 80, "\n", false);
        $lines = explode("\n", $wrapped);

        $line1 = $lines[0] ?? '';
        $line2 = $lines[1] ?? '';

        if (count($lines) > 2) {
            $line2 = rtrim($line2);
            if ($line2 !== '') {
                $line2 .= '…';
            } else {
                $line2 = '…';
            }
        }

        return [trim($line1), trim($line2)];
    }

    private function formatDate($value): string
    {
        if ($value instanceof Carbon) {
            return $value->format('m/d/Y');
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->format('m/d/Y');
        }

        if (is_string($value) && $value !== '') {
            try {
                return Carbon::parse($value)->format('m/d/Y');
            } catch (\Throwable) {
                return $value;
            }
        }

        return Carbon::now()->format('m/d/Y');
    }

    private function sanitizeFilename(string $value): string
    {
        $clean = trim($value);
        if ($clean === '') {
            return 'routing-slip.pdf';
        }

        $name = Str::slug(pathinfo($clean, PATHINFO_FILENAME));
        $extension = strtolower(pathinfo($clean, PATHINFO_EXTENSION) ?: 'pdf');

        if ($name === '') {
            $name = 'routing-slip';
        }

        return $name . '.' . $extension;
    }
}
