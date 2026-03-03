<?php

namespace App\adms\Models\Services;

use App\adms\Models\Repository\CalendarRepository;
use DateTimeImmutable;

/**
 * Serviço para operações com dias úteis considerando
 * fim de semana e feriados configurados no módulo Calendário.
 */
class WorkdayCalendarService
{
    private CalendarRepository $calendarRepo;

    /** @var array<int, array<string, bool>> */
    private array $holidayCache = [];

    private int $weekendStart;
    private int $weekendEnd;

    public function __construct()
    {
        $this->calendarRepo = new CalendarRepository();
        $settings = $this->calendarRepo->getSettings();
        $this->weekendStart = (int)($settings['weekend_start_day'] ?? 6);
        $this->weekendEnd = (int)($settings['weekend_end_day'] ?? 7);
    }

    public function isWorkday(DateTimeImmutable $date): bool
    {
        $dow = (int)$date->format('N'); // 1=Seg ... 7=Dom
        if ($this->isWeekend($dow)) {
            return false;
        }

        $year = (int)$date->format('Y');
        $this->ensureHolidayYearLoaded($year);

        $key = $date->format('Y-m-d');
        if (!empty($this->holidayCache[$year][$key])) {
            return false;
        }

        return true;
    }

    public function addWorkdays(DateTimeImmutable $date, int $days): DateTimeImmutable
    {
        if ($days <= 0) {
            return $date;
        }

        $current = $date;
        $added = 0;
        while ($added < $days) {
            $current = $current->modify('+1 day');
            if ($this->isWorkday($current)) {
                $added++;
            }
        }

        return $current;
    }

    public function nextWorkday(DateTimeImmutable $date): DateTimeImmutable
    {
        return $this->addWorkdays($date, 1);
    }

    private function isWeekend(int $dayOfWeek): bool
    {
        if ($this->weekendStart <= $this->weekendEnd) {
            return $dayOfWeek >= $this->weekendStart && $dayOfWeek <= $this->weekendEnd;
        }

        // Caso o intervalo cruze o final da semana (ex.: 6 até 2)
        return $dayOfWeek >= $this->weekendStart || $dayOfWeek <= $this->weekendEnd;
    }

    private function ensureHolidayYearLoaded(int $year): void
    {
        if (isset($this->holidayCache[$year])) {
            return;
        }

        $rows = $this->calendarRepo->listHolidays($year);
        $map = [];
        foreach ($rows as $row) {
            $start = $row['start_date'] ?? null;
            if (!$start) {
                continue;
            }
            $end = $row['end_date'] ?? null;
            $startDate = DateTimeImmutable::createFromFormat('Y-m-d', $start);
            if (!$startDate) {
                continue;
            }

            if ($end) {
                $endDate = DateTimeImmutable::createFromFormat('Y-m-d', $end) ?: $startDate;
            } else {
                $endDate = $startDate;
            }

            $current = $startDate;
            while ($current <= $endDate) {
                $map[$current->format('Y-m-d')] = true;
                $current = $current->modify('+1 day');
            }
        }

        $this->holidayCache[$year] = $map;
    }
}

