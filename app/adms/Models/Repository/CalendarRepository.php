<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

class CalendarRepository extends DbConnection
{
    public function getSettings(): array
    {
        $sql = "SELECT id, week_start_day, weekend_start_day, weekend_end_day, valid_for_one_year
                FROM calendar_settings
                ORDER BY id ASC
                LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            // Padrão: semana começa na segunda, fim de semana sábado-domingo
            return [
                'id' => 0,
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

        $oldFullStmt = $conn->prepare('SELECT * FROM calendar_settings ORDER BY id ASC LIMIT 1');
        $oldFullStmt->execute();
        $oldFullRow = $oldFullStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        $hasRow = $oldFullRow !== null;

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
            if ($oldFullRow
                && (int) ($oldFullRow['week_start_day'] ?? 0) === $weekStart
                && (int) ($oldFullRow['weekend_start_day'] ?? 0) === $weekendStart
                && (int) ($oldFullRow['weekend_end_day'] ?? 0) === $weekendEnd
                && (int) ($oldFullRow['valid_for_one_year'] ?? 0) === $validOneYear) {
                return true;
            }
            $stmt->bindValue(':updated_at', date('Y-m-d H:i:s'));
        } else {
            $stmt->bindValue(':created_at', date('Y-m-d H:i:s'));
        }

        $ok = $stmt->execute();
        if ($ok) {
            $usuarioId = $_SESSION['user_id'] ?? 1;
            $newFullStmt = $conn->prepare('SELECT * FROM calendar_settings ORDER BY id ASC LIMIT 1');
            $newFullStmt->execute();
            $newFullRow = $newFullStmt->fetch(PDO::FETCH_ASSOC) ?: [];
            if ($hasRow && $oldFullRow) {
                if ($this->calendarSettingsBusinessChanged($oldFullRow, $newFullRow)) {
                    LogAlteracaoService::registrarAlteracao(
                        'calendar_settings',
                        (int) $oldFullRow['id'],
                        $usuarioId,
                        'UPDATE',
                        $oldFullRow,
                        $newFullRow
                    );
                }
            } elseif (!$hasRow && !empty($newFullRow['id'])) {
                LogAlteracaoService::registrarAlteracao(
                    'calendar_settings',
                    (int) $newFullRow['id'],
                    $usuarioId,
                    'INSERT',
                    [],
                    $newFullRow
                );
            }
        }

        return $ok;
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
        $conn = $this->getConnection();
        $id = isset($data['id']) ? (int)$data['id'] : 0;
        $name = trim((string)($data['name'] ?? ''));
        $start = $data['start_date'] ?? null;
        $end = $data['end_date'] ?? null;
        $obs = $data['observations'] ?? null;
        $year = (int)($data['year'] ?? (substr((string)$start, 0, 4) ?: date('Y')));
        $usuarioId = $_SESSION['user_id'] ?? 1;

        if ($id > 0) {
            $oldStmt = $conn->prepare('SELECT * FROM calendar_holidays WHERE id = :id LIMIT 1');
            $oldStmt->bindValue(':id', $id, PDO::PARAM_INT);
            $oldStmt->execute();
            $oldData = $oldStmt->fetch(PDO::FETCH_ASSOC) ?: null;

            $sql = "UPDATE calendar_holidays
                       SET name = :name,
                           start_date = :start_date,
                           end_date = :end_date,
                           observations = :observations,
                           year = :year,
                           updated_at = :updated_at
                     WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':start_date', $start);
            $stmt->bindValue(':end_date', $end ?: null);
            $stmt->bindValue(':observations', $obs);
            $stmt->bindValue(':year', $year, PDO::PARAM_INT);
            $stmt->bindValue(':updated_at', date('Y-m-d H:i:s'));
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            if ($oldData && $this->calendarHolidayInputMatchesRow($oldData, $name, $start, $end, $obs, $year)) {
                return true;
            }
            if (!$stmt->execute()) {
                return false;
            }
            $newStmt = $conn->prepare('SELECT * FROM calendar_holidays WHERE id = :id LIMIT 1');
            $newStmt->bindValue(':id', $id, PDO::PARAM_INT);
            $newStmt->execute();
            $newData = $newStmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if ($oldData && $newData && $this->calendarHolidayBusinessChanged($oldData, $newData)) {
                LogAlteracaoService::registrarAlteracao(
                    'calendar_holidays',
                    $id,
                    $usuarioId,
                    'UPDATE',
                    $oldData,
                    $newData
                );
            }

            return true;
        }

        $sql = "INSERT INTO calendar_holidays
                    (name, start_date, end_date, observations, year, created_at)
                VALUES
                    (:name, :start_date, :end_date, :observations, :year, :created_at)";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':start_date', $start);
        $stmt->bindValue(':end_date', $end ?: null);
        $stmt->bindValue(':observations', $obs);
        $stmt->bindValue(':year', $year, PDO::PARAM_INT);
        $stmt->bindValue(':created_at', date('Y-m-d H:i:s'));
        if (!$stmt->execute()) {
            return false;
        }
        $newId = (int) $conn->lastInsertId();
        $newStmt = $conn->prepare('SELECT * FROM calendar_holidays WHERE id = :id LIMIT 1');
        $newStmt->bindValue(':id', $newId, PDO::PARAM_INT);
        $newStmt->execute();
        $newData = $newStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if ($newData) {
            LogAlteracaoService::registrarAlteracao(
                'calendar_holidays',
                $newId,
                $usuarioId,
                'INSERT',
                [],
                $newData
            );
        }

        return $newId;
    }

    public function deleteHoliday(int $id): bool
    {
        $conn = $this->getConnection();
        $selStmt = $conn->prepare('SELECT * FROM calendar_holidays WHERE id = :id LIMIT 1');
        $selStmt->bindValue(':id', $id, PDO::PARAM_INT);
        $selStmt->execute();
        $oldData = $selStmt->fetch(PDO::FETCH_ASSOC) ?: null;

        $sql = "DELETE FROM calendar_holidays WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $ok = $stmt->execute();
        if ($ok && $oldData && $stmt->rowCount() > 0) {
            $usuarioId = $_SESSION['user_id'] ?? 1;
            LogAlteracaoService::registrarAlteracao(
                'calendar_holidays',
                $id,
                $usuarioId,
                'DELETE',
                $oldData,
                []
            );
        }

        return $ok;
    }

    /**
     * Compara apenas campos de negócio (ignora timestamps), para evitar log em UPDATE que só mexe em updated_at.
     *
     * @param array<string, mixed> $old
     * @param array<string, mixed> $new
     */
    private function calendarSettingsBusinessChanged(array $old, array $new): bool
    {
        foreach (['week_start_day', 'weekend_start_day', 'weekend_end_day', 'valid_for_one_year'] as $key) {
            if ((string) ($old[$key] ?? '') !== (string) ($new[$key] ?? '')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function calendarHolidayInputMatchesRow(
        array $row,
        string $name,
        mixed $start,
        mixed $end,
        mixed $obs,
        int $year
    ): bool {
        if (trim((string) ($row['name'] ?? '')) !== $name) {
            return false;
        }
        if ((string) ($row['start_date'] ?? '') !== (string) ($start ?? '')) {
            return false;
        }
        if ($this->normalizeHolidayScalar($row['end_date'] ?? null) !== $this->normalizeHolidayScalar($end)) {
            return false;
        }
        if (trim((string) ($row['observations'] ?? '')) !== trim((string) ($obs ?? ''))) {
            return false;
        }
        if ((int) ($row['year'] ?? 0) !== $year) {
            return false;
        }

        return true;
    }

    /**
     * @param array<string, mixed> $old
     * @param array<string, mixed> $new
     */
    private function calendarHolidayBusinessChanged(array $old, array $new): bool
    {
        foreach (['name', 'start_date', 'end_date', 'observations', 'year'] as $key) {
            if ($key === 'year') {
                if ((int) ($old[$key] ?? 0) !== (int) ($new[$key] ?? 0)) {
                    return true;
                }
                continue;
            }
            if ($key === 'end_date' || $key === 'observations') {
                if ($this->normalizeHolidayScalar($old[$key] ?? null) !== $this->normalizeHolidayScalar($new[$key] ?? null)) {
                    return true;
                }
                continue;
            }
            if ((string) ($old[$key] ?? '') !== (string) ($new[$key] ?? '')) {
                return true;
            }
        }

        return false;
    }

    private function normalizeHolidayScalar(mixed $v): string
    {
        if ($v === null || $v === '') {
            return '';
        }

        return trim((string) $v);
    }
}

