<?php

namespace Tests\Unit;

use App\Services\PropertyNumberService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PropertyNumberServiceTest extends TestCase
{
    public function test_parse_returns_expected_components(): void
    {
        $service = new PropertyNumberService();

        $result = $service->parse(' 2020-05-0660-8831 ');

        $this->assertSame('2020', $result['year']);
    $this->assertSame('05', $result['category']);
        $this->assertSame('0660', $result['serial']);
        $this->assertSame(660, $result['serial_int']);
        $this->assertSame('8831', $result['office']);
        $this->assertSame('2020-05-0660-8831', $result['property_number']);
    }

    public function test_assemble_produces_canonical_property_number(): void
    {
        $service = new PropertyNumberService();

        $number = $service->assemble([
            'year' => '2021',
            'category' => '07',
            'serial' => '0123',
            'office' => '1100',
        ]);

        $this->assertSame('2021-07-0123-1100', $number);
    }

    public function test_pad_serial_adds_leading_zeroes(): void
    {
        $service = new PropertyNumberService();

        $this->assertSame('0060', $service->padSerial(60, 4));
        $this->assertSame('0001', $service->padSerial(1, 4));
    }

    public function test_parse_rejects_invalid_format(): void
    {
        $service = new PropertyNumberService();

        $this->expectException(InvalidArgumentException::class);
        $service->parse('invalid-format');
    }
}
