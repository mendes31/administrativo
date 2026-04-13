<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use Exception;
use PDO;

class WorkShiftsRepository extends DbConnection
{
    public function getAllWorkShifts(int $page = 1, int $limitResult = 10, string $filterDescription = ''): array
    {
        $offset = max(0, ($page - 1) * $limitResult);
        $where = '';
        if ($filterDescription !== '') {
            $where = 'WHERE description LIKE :desc';
        }
        $sql = "SELECT id, description, entry_1, exit_1, entry_2, exit_2, entry_3, exit_3,
                       overtime_tolerance_minutes, absence_tolerance_minutes, total_minutes,
                       created_at, updated_at
                FROM adms_work_shifts {$where}
                ORDER BY id ASC LIMIT :limit OFFSET :offset";
        $stmt = $this->getConnection()->prepare($sql);
        if ($filterDescription !== '') {
            $stmt->bindValue(':desc', '%' . $filterDescription . '%', PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limitResult, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getAmountWorkShifts(string $filterDescription = ''): int
    {
        $where = '';
        if ($filterDescription !== '') {
            $where = 'WHERE description LIKE :desc';
        }
        $sql = 'SELECT COUNT(id) AS amount_records FROM adms_work_shifts ' . $where;
        $stmt = $this->getConnection()->prepare($sql);
        if ($filterDescription !== '') {
            $stmt->bindValue(':desc', '%' . $filterDescription . '%', PDO::PARAM_STR);
        }
        $stmt->execute();

        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['amount_records'] ?? 0);
    }

    /**
     * Lista id + descrição para selects (mesmo formato que departamentos/cargos).
     *
     * @return array<int, array{id: int, name: string}>
     */
    public function getAllWorkShiftsSelect(): array
    {
        $sql = 'SELECT id, description AS name FROM adms_work_shifts ORDER BY description ASC';
        $stmt = $this->getConnection()->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getWorkShift(int $id): array|bool
    {
        $sql = 'SELECT id, description, entry_1, exit_1, entry_2, exit_2, entry_3, exit_3,
                       overtime_tolerance_minutes, absence_tolerance_minutes, total_minutes,
                       created_at, updated_at
                FROM adms_work_shifts WHERE id = :id LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createWorkShift(array $data): bool|int
    {
        try {
            $row = $this->normalizeRow($data);
            $sql = 'INSERT INTO adms_work_shifts (
                        description, entry_1, exit_1, entry_2, exit_2, entry_3, exit_3,
                        overtime_tolerance_minutes, absence_tolerance_minutes, total_minutes, created_at, updated_at
                    ) VALUES (
                        :description, :entry_1, :exit_1, :entry_2, :exit_2, :entry_3, :exit_3,
                        :ot, :ab, :total, :created_at, :updated_at
                    )';
            $stmt = $this->getConnection()->prepare($sql);
            $this->bindWorkShift($stmt, $row);
            $now = date('Y-m-d H:i:s');
            $stmt->bindValue(':created_at', $now);
            $stmt->bindValue(':updated_at', $now);
            $stmt->execute();
            $newId = (int) $this->getConnection()->lastInsertId();
            if ($newId > 0) {
                $this->logChange($newId, $_SESSION['user_id'] ?? 1, 'INSERT', [], $row + ['id' => $newId]);
            }

            return $newId;
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Turno não cadastrado.', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function updateWorkShift(array $data): bool
    {
        try {
            $old = $this->getWorkShift((int) $data['id']);
            if (!$old) {
                return false;
            }
            $row = $this->normalizeRow($data);
            $sql = 'UPDATE adms_work_shifts SET
                    description = :description,
                    entry_1 = :entry_1, exit_1 = :exit_1,
                    entry_2 = :entry_2, exit_2 = :exit_2,
                    entry_3 = :entry_3, exit_3 = :exit_3,
                    overtime_tolerance_minutes = :ot,
                    absence_tolerance_minutes = :ab,
                    total_minutes = :total,
                    updated_at = :updated_at
                    WHERE id = :id LIMIT 1';
            $stmt = $this->getConnection()->prepare($sql);
            $this->bindWorkShift($stmt, $row);
            $stmt->bindValue(':id', (int) $data['id'], PDO::PARAM_INT);
            $stmt->bindValue(':updated_at', date('Y-m-d H:i:s'));
            $ok = $stmt->execute();
            if ($ok) {
                $this->logChange((int) $data['id'], $_SESSION['user_id'] ?? 1, 'UPDATE', $old, $row + ['id' => (int) $data['id']]);
            }

            return $ok;
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Turno não atualizado.', ['id' => $data['id'] ?? null, 'error' => $e->getMessage()]);

            return false;
        }
    }

    public function deleteWorkShift(int $id): bool
    {
        try {
            $old = $this->getWorkShift($id);
            if (!$old) {
                return false;
            }
            $sql = 'DELETE FROM adms_work_shifts WHERE id = :id LIMIT 1';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $ok = $stmt->execute() && $stmt->rowCount() > 0;
            if ($ok) {
                $this->logChange($id, $_SESSION['user_id'] ?? 1, 'DELETE', $old, []);
            }

            return $ok;
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Turno não apagado.', ['id' => $id, 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Soma os intervalos (entrada/saída) em minutos.
     * Cada intervalo pode atravessar meia-noite: se a saída for ≤ entrada no relógio, considera-se saída no dia seguinte.
     */
    public static function computeTotalMinutes(array $data): int
    {
        $total = 0;
        foreach ([1, 2, 3] as $i) {
            $e = self::normalizeTimeString($data['entry_' . $i] ?? null);
            $x = self::normalizeTimeString($data['exit_' . $i] ?? null);
            if ($e !== null && $x !== null) {
                $total += self::diffMinutes($e, $x);
            }
        }

        return max(0, $total);
    }

    public static function formatMinutesLabel(int $minutes): string
    {
        if ($minutes <= 0) {
            return '—';
        }
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        return sprintf('%02d:%02d', $h, $m);
    }

    public static function timeInputValue(?string $dbTime): string
    {
        if ($dbTime === null || $dbTime === '') {
            return '';
        }
        if (preg_match('/^(\d{2}):(\d{2})/', $dbTime, $m)) {
            return $m[1] . ':' . $m[2];
        }

        return '';
    }

    private function normalizeRow(array $data): array
    {
        $total = self::computeTotalMinutes($data);

        return [
            'description' => trim((string) ($data['description'] ?? '')),
            'entry_1' => self::normalizeTimeString($data['entry_1'] ?? null),
            'exit_1' => self::normalizeTimeString($data['exit_1'] ?? null),
            'entry_2' => self::normalizeTimeString($data['entry_2'] ?? null),
            'exit_2' => self::normalizeTimeString($data['exit_2'] ?? null),
            'entry_3' => self::normalizeTimeString($data['entry_3'] ?? null),
            'exit_3' => self::normalizeTimeString($data['exit_3'] ?? null),
            'overtime_tolerance_minutes' => max(0, min(999, (int) ($data['overtime_tolerance_minutes'] ?? 0))),
            'absence_tolerance_minutes' => max(0, min(999, (int) ($data['absence_tolerance_minutes'] ?? 0))),
            'total_minutes' => $total,
        ];
    }

    private function bindWorkShift(\PDOStatement $stmt, array $row): void
    {
        $stmt->bindValue(':description', $row['description']);
        foreach (['entry_1', 'exit_1', 'entry_2', 'exit_2', 'entry_3', 'exit_3'] as $f) {
            $v = $row[$f];
            if ($v === null) {
                $stmt->bindValue(':' . $f, null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':' . $f, $v);
            }
        }
        $stmt->bindValue(':ot', $row['overtime_tolerance_minutes'], PDO::PARAM_INT);
        $stmt->bindValue(':ab', $row['absence_tolerance_minutes'], PDO::PARAM_INT);
        $stmt->bindValue(':total', $row['total_minutes'], PDO::PARAM_INT);
    }

    private static function normalizeTimeString(null|string $v): ?string
    {
        if ($v === null) {
            return null;
        }
        $v = trim($v);
        if ($v === '') {
            return null;
        }
        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $v, $m)) {
            return sprintf('%02d:%02d:%02d', (int) $m[1], (int) $m[2], isset($m[3]) ? (int) $m[3] : 0);
        }

        return null;
    }

    /**
     * Duração em minutos entre dois horários.
     * Se a saída for estritamente anterior à entrada no relógio, assume-se saída no dia seguinte (turno noturno).
     */
    private static function diffMinutes(string $startHms, string $endHms): int
    {
        $start = self::timeHmsToSecondsSinceMidnight($startHms);
        $end = self::timeHmsToSecondsSinceMidnight($endHms);
        if ($start === null || $end === null) {
            return 0;
        }
        $day = 86400;
        if ($end > $start) {
            return (int) round(($end - $start) / 60);
        }
        if ($end < $start) {
            return (int) round(($day - $start + $end) / 60);
        }

        // Horários iguais: duração zero (turno de 24h exigiria outro cadastro).
        return 0;
    }

    /** @return int|null segundos desde 00:00:00 do mesmo dia */
    private static function timeHmsToSecondsSinceMidnight(string $hms): ?int
    {
        if (!preg_match('/^(\d{2}):(\d{2}):(\d{2})$/', $hms, $m)) {
            return null;
        }
        $h = (int) $m[1];
        $min = (int) $m[2];
        $s = (int) $m[3];
        if ($h > 23 || $min > 59 || $s > 59) {
            return null;
        }

        return $h * 3600 + $min * 60 + $s;
    }

    private function logChange(int $id, int $userId, string $op, array $old, array $new): void
    {
        try {
            LogAlteracaoService::registrarAlteracao('adms_work_shifts', $id, $userId, $op, $old, $new);
        } catch (Exception) {
            // não bloquear CRUD
        }
    }
}
