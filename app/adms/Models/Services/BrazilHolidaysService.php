<?php

namespace App\adms\Models\Services;

/**
 * Gera a lista de feriados nacionais brasileiros para um ano específico.
 *
 * Esta classe não persiste nada no banco; apenas retorna um array
 * que pode ser usado pelo repositório de calendário.
 */
class BrazilHolidaysService
{
    /**
     * Retorna os feriados nacionais brasileiros para o ano informado.
     *
     * @param int $year
     * @return array<int, array<string, string|null>>
     */
    public function getNationalHolidays(int $year): array
    {
        $easter = $this->calculateEasterDate($year);

        // Carnaval (terça-feira) = 47 dias antes da Páscoa
        $carnavalTuesday = (clone $easter)->modify('-47 days');
        // Paixão de Cristo (sexta-feira santa) = 2 dias antes da Páscoa
        $goodFriday = (clone $easter)->modify('-2 days');
        // Corpus Christi = 60 dias após a Páscoa
        $corpusChristi = (clone $easter)->modify('+60 days');

        $holidays = [
            [
                'name' => 'Confraternização Universal',
                'start_date' => sprintf('%04d-01-01', $year),
                'end_date' => null,
                'observations' => 'Feriado nacional',
            ],
            [
                'name' => 'Carnaval',
                'start_date' => $carnavalTuesday->format('Y-m-d'),
                'end_date' => null,
                'observations' => 'Ponto facultativo / feriado nacional em muitas regiões',
            ],
            [
                'name' => 'Paixão de Cristo',
                'start_date' => $goodFriday->format('Y-m-d'),
                'end_date' => null,
                'observations' => 'Sexta-feira Santa',
            ],
            [
                'name' => 'Tiradentes',
                'start_date' => sprintf('%04d-04-21', $year),
                'end_date' => null,
                'observations' => 'Feriado nacional',
            ],
            [
                'name' => 'Dia do Trabalho',
                'start_date' => sprintf('%04d-05-01', $year),
                'end_date' => null,
                'observations' => 'Feriado nacional',
            ],
            [
                'name' => 'Corpus Christi',
                'start_date' => $corpusChristi->format('Y-m-d'),
                'end_date' => null,
                'observations' => 'Feriado nacional (ponto facultativo em alguns locais)',
            ],
            [
                'name' => 'Independência do Brasil',
                'start_date' => sprintf('%04d-09-07', $year),
                'end_date' => null,
                'observations' => 'Feriado nacional',
            ],
            [
                'name' => 'Nossa Senhora Aparecida',
                'start_date' => sprintf('%04d-10-12', $year),
                'end_date' => null,
                'observations' => 'Padroeira do Brasil',
            ],
            [
                'name' => 'Finados',
                'start_date' => sprintf('%04d-11-02', $year),
                'end_date' => null,
                'observations' => 'Feriado nacional',
            ],
            [
                'name' => 'Proclamação da República',
                'start_date' => sprintf('%04d-11-15', $year),
                'end_date' => null,
                'observations' => 'Feriado nacional',
            ],
            [
                'name' => 'Natal',
                'start_date' => sprintf('%04d-12-25', $year),
                'end_date' => null,
                'observations' => 'Feriado nacional',
            ],
        ];

        return $holidays;
    }

    /**
     * Calcula a data da Páscoa pelo algoritmo de Meeus/Jones/Butcher.
     *
     * @param int $year
     * @return \DateTimeImmutable
     */
    private function calculateEasterDate(int $year): \DateTimeImmutable
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31); // 3=março, 4=abril
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return new \DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $day));
    }
}

