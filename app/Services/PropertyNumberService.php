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

        [$year, $ppe, $serial, $office] = $parts;

        if (! preg_match('/^\d{4}$/', $year)) {
            throw new InvalidArgumentException('Year segment must be four digits.');
        }

        if (! preg_match('/^\d{2}$/', $ppe)) {
            throw new InvalidArgumentException('PPE code segment must be two digits.');
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

        return [
            'year' => $year,
            'ppe' => $ppe,
            'serial' => $serial,
            'serial_int' => $serialInt,
            // store the office exactly as provided (no padding)
            'office' => $officeRaw,
            'property_number' => $this->assemble([
                'year' => $year,
                'ppe' => $ppe,
                'serial' => $serial,
                'office' => $officeRaw,
            ]),
        ];
    }

    public function assemble(array $components): string
    {
        $year = $components['year'] ?? $components['year_procured'] ?? null;
        $ppe = $components['ppe'] ?? $components['ppe_code'] ?? null;
        $serial = $components['serial'] ?? null;
        $office = $components['office'] ?? $components['office_code'] ?? null;

        if (! is_string($year) || ! preg_match('/^\d{4}$/', $year)) {
            throw new InvalidArgumentException('Year segment must be four digits.');
        }

        if (! is_string($ppe) || ! preg_match('/^\d{2}$/', $ppe)) {
            throw new InvalidArgumentException('PPE code segment must be two digits.');
        }

        if ($serial === null && isset($components['serial_int'])) {
            $width = $components['serial_width'] ?? 4;
            $serial = $this->padSerial((int) $components['serial_int'], (int) $width);
        }

        if (! is_string($serial) || $serial === '') {
            throw new InvalidArgumentException('Serial segment cannot be empty.');
        }

        // Accept 1-4 alphanumeric office as-is (no automatic stripping/padding).
        $officeRaw = (string) $office;
        if ($officeRaw === '') {
            throw new InvalidArgumentException('Office code segment cannot be empty.');
        }
        if (! preg_match('/^[A-Za-z0-9]{1,4}$/', $officeRaw)) {
            throw new InvalidArgumentException('Office code segment must be 1 to 4 alphanumeric characters.');
        }

        // preserve office exactly as entered
        return sprintf('%s-%s-%s-%s', $year, $ppe, $serial, $officeRaw);
    }


    public function padSerial(int $n, int $width = 4): string
    {
        $width = max(1, $width);
        $value = (string) max(0, $n);
        return str_pad($value, $width, '0', STR_PAD_LEFT);
    }
}
