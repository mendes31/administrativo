<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

class CalendarRepository extends DbConnection
{
    public function getSettings(): array
    {
        $sql = "SELECT week_start_day, weekend_start_day, weekend_end_day, valid_for_one_year
                FROM calendar_settings
                ORDER BY id ASC
                LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            // Padrão: semana começa na segunda, fim de semana sábado-domingo
            return [
                'week_start_day' => 1,
                'weekend_start_day' => 6,
                'weekend_end_day' => 7,
                'valid_for_one_year' => 1,
            ];
        }

        return $row;
    }

    public function saveSettings(array $data): bool
    {
        $conn = $this->getConnection();
        $existing = $this->getSettings();

        $weekStart = (int)($data['week_start_day'] ?? $existing['week_start_day'] ?? 1);
        $weekendStart = (int)($data['weekend_start_day'] ?? $existing['weekend_start_day'] ?? 6);
        $weekendEnd = (int)($data['weekend_end_day'] ?? $existing['weekend_end_day'] ?? 7);
        $validOneYear = !empty($data['valid_for_one_year']) ? 1 : 0;

        $hasRowStmt = $conn->prepare("SELECT COUNT(*) AS total FROM calendar_settings");
        $hasRowStmt->execute();
        $hasRow = (int)($hasRowStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0) > 0;

        if ($hasRow) {
            $sql = "UPDATE calendar_settings
                       SET week_start_day = :week_start_day,
                           weekend_start_day = :weekend_start_day,
                           weekend_end_day = :weekend_end_day,
                           valid_for_one_year = :valid_for_one_year,
                           updated_at = :updated_at";
        } else {
            $sql = "INSERT INTO calendar_settings
                        (week_start_day, weekend_start_day, weekend_end_day, valid_for_one_year, created_at)
                    VALUES
                        (:week_start_day, :weekend_start_day, :weekend_end_day, :valid_for_one_year, :created_at)";
        }

        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':week_start_day', $weekStart, PDO::PARAM_INT);
        $stmt->bindValue(':weekend_start_day', $weekendStart, PDO::PARAM_INT);
        $stmt->bindValue(':weekend_end_day', $weekendEnd, PDO::PARAM_INT);
        $stmt->bindValue(':valid_for_one_year', $validOneYear, PDO::PARAM_INT);

        if ($hasRow) {
            $stmt->bindValue(':updated_at', date('Y-m-d H:i:s'));
        } else {
            $stmt->bindValue(':created_at', date('Y-m-d H:i:s'));
        }

        return $stmt->execute();
    }

    public function listHolidays(int $year): array
    {
        $sql = "SELECT id, name, start_date, end_date, observations, year
                FROM calendar_holidays
                WHERE year = :year
                ORDER BY start_date ASC, name ASC";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':year', $year, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function saveHoliday(array $data): bool|int
    {
        $id = isset($data['id']) ? (int)$data['id'] : 0;
        $name = trim((string)($data['name'] ?? ''));
        $start = $data['start_date'] ?? null;
        $end = $data['end_date'] ?? null;
        $obs = $data['observations'] ?? null;
        $year = (int)($data['year'] ?? (substr((string)$start, 0, 4) ?: date('Y')));

        if ($id > 0) {
            $sql = "UPDATE calendar_holidays
                       SET name = :name,
                           start_date = :start_date,
                           end_date = :end_date,
                           observations = :observations,
                           year = :year,
                           updated_at = :updated_at
                     WHERE id = :id";
        } else {
            $sql = "INSERT INTO calendar_holidays
                        (name, start_date, end_date, observations, year, created_at)
                    VALUES
                        (:name, :start_date, :end_date, :observations, :year, :created_at)";
        }

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':start_date', $start);
        $stmt->bindValue(':end_date', $end ?: null);
        $stmt->bindValue(':observations', $obs);
        $stmt->bindValue(':year', $year, PDO::PARAM_INT);

        if ($id > 0) {
            $stmt->bindValue(':updated_at', date('Y-m-d H:i:s'));
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        }

        $stmt->bindValue(':created_at', date('Y-m-d H:i:s'));
        if ($stmt->execute()) {
            return (int)$this->getConnection()->lastInsertId();
        }

        return false;
    }

    public function deleteHoliday(int $id): bool
    {
        $sql = "DELETE FROM calendar_holidays WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}

