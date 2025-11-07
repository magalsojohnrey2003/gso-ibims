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

        // Support both 4-part and 5-part formats:
        //  - 4-part: year-category-serial-office
        //  - 5-part: year-category-gla-serial-office
        if (! in_array(count($parts), [4, 5], true) || in_array('', $parts, true)) {
            throw new InvalidArgumentException('Property numbers must contain either 4 or 5 non-empty segments (year-category-serial-office or year-category-gla-serial-office).');
        }

        if (count($parts) === 4) {
            [$year, $categoryCode, $serial, $office] = $parts;
            $gla = null;
        } else {
            [$year, $categoryCode, $gla, $serial, $office] = $parts;
        }

        if (! preg_match('/^\d{4}$/', $year)) {
            throw new InvalidArgumentException('Year segment must be four digits.');
        }

        // Category: allow 1-4 digits (legacy codes may be shorter like '05')
        if (! preg_match('/^\d{1,4}$/', $categoryCode)) {
            throw new InvalidArgumentException('Category segment must be 1 to 4 digits.');
        }

        // GLA: if present must be 1-4 digits
        if ($gla !== null && $gla !== '') {
            if (! preg_match('/^\d{1,4}$/', $gla)) {
                throw new InvalidArgumentException('GLA segment must be 1 to 4 digits when present.');
            }
        }

        $serial = strtoupper($serial);
        if ($serial === '') {
            throw new InvalidArgumentException('Serial segment cannot be empty.');
        }

        $serialInt = null;
        if (preg_match('/^\d+$/', $serial)) {
            $serialInt = (int) $serial;
        }

        // Office: must be exactly 4 digits.
        $officeRaw = (string) $office;
        if ($officeRaw === '') {
            throw new InvalidArgumentException('Office code segment cannot be empty.');
        }
        if (! preg_match('/^\d{4}$/', $officeRaw)) {
            throw new InvalidArgumentException('Office code segment must be exactly 4 digits.');
        }

    $categoryDigits = preg_replace('/\D/', '', (string) $categoryCode);
        $officeDigits = substr($officeRaw, 0, 4);

        $assembled = $this->assemble([
            'year' => $year,
            'category' => $categoryDigits,
            'gla' => $gla,
            'serial' => $serial,
            'office' => $officeDigits,
        ]);

        return [
            'year' => $year,
            'category' => $categoryDigits,
            'category_code' => $categoryDigits,
            'gla' => $gla,
            'serial' => $serial,
            'serial_int' => $serialInt,
            'office' => $officeDigits,
            'property_number' => $assembled,
        ];
    }

    public function assemble(array $components): string
    {
        $year = $components['year'] ?? $components['year_procured'] ?? null;
        $category = $components['category'] ?? $components['category_code'] ?? null;
    $gla = $components['gla'] ?? null;
        $serial = $components['serial'] ?? null;
        $office = $components['office'] ?? $components['office_code'] ?? null;

        if (! is_string($year) || ! preg_match('/^\d{4}$/', $year)) {
            throw new InvalidArgumentException('Year segment must be four digits.');
        }

        $categoryDigits = preg_replace('/\D/', '', (string) $category);
        if ($categoryDigits === '' || strlen($categoryDigits) > 4) {
            throw new InvalidArgumentException('Category segment must be 1 to 4 digits.');
        }

        // GLA is optional (supports 4-part and 5-part formats). If present it must be digits 1-4.
        if ($gla !== null && $gla !== '') {
            if (! preg_match('/^\d{1,4}$/', (string) $gla)) {
                throw new InvalidArgumentException('GLA segment must be 1 to 4 digits.');
            }
        }

        if ($serial === null && isset($components['serial_int'])) {
            $width = $components['serial_width'] ?? 4;
            $serial = $this->padSerial((int) $components['serial_int'], (int) $width);
        }

        if (! is_string($serial) || $serial === '') {
            throw new InvalidArgumentException('Serial segment cannot be empty.');
        }

        $officeDigits = preg_replace('/\D/', '', (string) $office);
        if ($officeDigits === '') {
            throw new InvalidArgumentException('Office code segment cannot be empty.');
        }
        if (strlen($officeDigits) !== 4) {
            throw new InvalidArgumentException('Office code segment must be exactly 4 digits.');
        }

        if ($gla === null || $gla === '') {
            return sprintf('%s-%s-%s-%s', $year, $categoryDigits, $serial, $officeDigits);
        }
        return sprintf('%s-%s-%s-%s-%s', $year, $categoryDigits, $gla, $serial, $officeDigits);
    }

    public function padSerial(int $n, int $width = 4): string
    {
        $width = max(1, $width);
        $value = (string) max(0, $n);
        return str_pad($value, $width, '0', STR_PAD_LEFT);
    }
}
