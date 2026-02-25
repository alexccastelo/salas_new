<?php

namespace Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use Clinica\Helpers\DateHelper;

class DateHelperTest extends TestCase
{
    public function testCalcularHorasDecimaisNoMesmoDia()
    {
        // 14:00 to 16:30 is 2.5 hours
        $horas = DateHelper::calcularHorasDecimais('2024-05-10', '14:00', '16:30');
        $this->assertEquals(2.5, $horas);
    }

    public function testCalcularHorasDecimaisVirandoONoite()
    {
        // 23:00 to 01:00 is 2 hours
        $horas = DateHelper::calcularHorasDecimais('2024-05-10', '23:00', '01:00');
        $this->assertEquals(2.0, $horas);
    }

    public function testFormatBr()
    {
        // Should format standard SQL date to BR format
        $dataBr = DateHelper::formatBr('2024-12-31');
        $this->assertEquals('31/12/2024', $dataBr);

        // Should return same input if format is invalid
        $invalid = DateHelper::formatBr('texto-invalido');
        $this->assertEquals('texto-invalido', $invalid);
    }
}
