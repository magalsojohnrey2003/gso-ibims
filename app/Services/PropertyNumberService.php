<?php

namespace App\Services;

use InvalidArgumentException;

class PropertyNumberService
{
    public function parse(string $pn): array
    {
        $value = trim($pn);
        if ($value === '') {
            throw new InvalidArgumentException('The property number cannot be empty.');
        }

        $normalized = preg_replace('/\s+/', '', $value);
        $parts = explode('-', $normalized);

        if (count($parts) !== 4 || in_array('', $parts, true)) {
            throw new InvalidArgumentException('Property numbers must contain four non-empty segments.');
        }

        [$year, $categoryCode, $serial, $office] = $parts;

        if (! preg_match('/^\d{4}$/', $year)) {
            throw new InvalidArgumentException('Year segment must be four digits.');
        }

        // Accept 1-4 uppercase alphanumeric characters for the category-derived segment.
        if (! preg_match('/^[A-Z0-9]{1,4}$/', $categoryCode)) {
            throw new InvalidArgumentException('Category-derived segment must be 1 to 4 uppercase alphanumeric characters.');
        }

        $serial = strtoupper($serial);
        if ($serial === '') {
            throw new InvalidArgumentException('Serial segment cannot be empty.');
        }

        $serialInt = null;
        if (preg_match('/^\d+$/', $serial)) {
            $serialInt = (int) $serial;
        }

        // Accept alphanumeric office (1-4 chars). Preserve exactly as entered.
        $officeRaw = (string) $office;
        if ($officeRaw === '') {
            throw new InvalidArgumentException('Office code segment cannot be empty.');
        }
        if (! preg_match('/^[A-Za-z0-9]{1,4}$/', $officeRaw)) {
            throw new InvalidArgumentException('Office code segment must be 1 to 4 alphanumeric characters.');
        }

        $assembled = $this->assemble([
            'year' => $year,
            'category' => $categoryCode,
            'serial' => $serial,
            'office' => $officeRaw,
        ]);

        return [
            'year' => $year,
            'category' => $categoryCode,
            'category_code' => $categoryCode,
            'serial' => $serial,
            'serial_int' => $serialInt,
            'office' => $officeRaw,
            'property_number' => $assembled,
        ];
    }

    public function assemble(array $components): string
    {
        $year = $components['year'] ?? $components['year_procured'] ?? null;
        // accept multiple aliases for category-derived component, but prefer category/category_code
        $category = $components['category'] ?? $components['category_code'] ?? null;
        $serial = $components['serial'] ?? null;
        $office = $components['office'] ?? $components['office_code'] ?? null;

        if (! is_string($year) || ! preg_match('/^\d{4}$/', $year)) {
            throw new InvalidArgumentException('Year segment must be four digits.');
        }

        if (! is_string($category) || ! preg_match('/^[A-Z0-9]{1,4}$/', $category)) {
            throw new InvalidArgumentException('Category-derived segment must be 1 to 4 uppercase alphanumeric characters.');
        }

        if ($serial === null && isset($components['serial_int'])) {
            $width = $components['serial_width'] ?? 4;
            $serial = $this->padSerial((int) $components['serial_int'], (int) $width);
        }

        if (! is_string($serial) || $serial === '') {
            throw new InvalidArgumentException('Serial segment cannot be empty.');
        }

        $officeRaw = (string) $office;
        if ($officeRaw === '') {
            throw new InvalidArgumentException('Office code segment cannot be empty.');
        }
        if (! preg_match('/^[A-Za-z0-9]{1,4}$/', $officeRaw)) {
            throw new InvalidArgumentException('Office code segment must be 1 to 4 alphanumeric characters.');
        }

        return sprintf('%s-%s-%s-%s', $year, $category, $serial, $officeRaw);
    }

    public function padSerial(int $n, int $width = 4): string
    {
        $width = max(1, $width);
        $value = (string) max(0, $n);
        return str_pad($value, $width, '0', STR_PAD_LEFT);
    }
}