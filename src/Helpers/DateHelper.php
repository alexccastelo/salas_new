<?php

namespace Clinica\Helpers;

use DateTime;

class DateHelper
{
    // Calcula diferença entre dois horários em horas decimais
    public static function calcularHorasDecimais($data, $horaInicio, $horaFim)
    {
        $inicio = new DateTime($data . ' ' . $horaInicio);
        $fim = new DateTime($data . ' ' . $horaFim);

        // Se o fim for menor que o início, assume que virou o dia
        if ($fim < $inicio) {
            $fim->modify('+1 day');
        }

        $intervalo = $inicio->diff($fim);
        $minutosTotais = ($intervalo->days * 24 * 60) + ($intervalo->h * 60) + $intervalo->i;

        return $minutosTotais / 60;
    }

    public static function formatBr($date)
    {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d ? $d->format('d/m/Y') : $date;
    }
}
