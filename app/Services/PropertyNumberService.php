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

        // Allow 1-4 digits for office, normalize to digits and pad when returning
        $officeDigits = preg_replace('/\D/', '', $office);
        if ($officeDigits === '') {
            throw new InvalidArgumentException('Office code segment cannot be empty.');
        }
        if (! preg_match('/^\d{1,4}$/', $officeDigits)) {
            throw new InvalidArgumentException('Office code segment must be 1 to 4 digits.');
        }

        // Return the office as padded 4-digit string to remain consistent with stored PN format
        $officePadded = str_pad($officeDigits, 4, '0', STR_PAD_LEFT);

        return [
            'year' => $year,
            'ppe' => $ppe,
            'serial' => $serial,
            'serial_int' => $serialInt,
            'office' => $officePadded,
            'property_number' => $this->assemble([
                'year' => $year,
                'ppe' => $ppe,
                'serial' => $serial,
                'office' => $officeDigits, // assemble will pad
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

        // Normalize and accept 1-4 digits for office, then pad to 4
        $officeDigits = (string) preg_replace('/\D/', '', (string) $office);
        if ($officeDigits === '') {
            throw new InvalidArgumentException('Office code segment cannot be empty.');
        }
        if (! preg_match('/^\d{1,4}$/', $officeDigits)) {
            throw new InvalidArgumentException('Office code segment must be 1 to 4 digits.');
        }
        $officePadded = str_pad($officeDigits, 4, '0', STR_PAD_LEFT);

        return sprintf('%s-%s-%s-%s', $year, $ppe, $serial, $officePadded);
    }


    public function padSerial(int $n, int $width = 4): string
    {
        $width = max(1, $width);
        $value = (string) max(0, $n);
        return str_pad($value, $width, '0', STR_PAD_LEFT);
    }
}
