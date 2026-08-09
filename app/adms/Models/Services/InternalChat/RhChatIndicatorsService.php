<?php

namespace App\adms\Models\Services\InternalChat;

use App\adms\Models\Services\DbConnection;
use PDO;

/**
 * Indicadores de RH lidos só do banco do Portal (sem SAP).
 * Usado pelo piloto do chat interno.
 */
class RhChatIndicatorsService extends DbConnection
{
    /**
     * @return array{total:int, by_department: list<array{departamento:string, total:int}>, match_mode?: string}
     */
    public function countActiveEmployees(?string $departmentName = null): array
    {
        return $this->countEmployeesByStatus(true, $departmentName);
    }

    /**
     * Inativos: status Inativo ou com data de desligamento.
     *
     * @return array{total:int, by_department: list<array{departamento:string, total:int}>, match_mode?: string}
     */
    public function countInactiveEmployees(?string $departmentName = null): array
    {
        return $this->countEmployeesByStatus(false, $departmentName);
    }

    /**
     * Desligados no mês/ano (pela data_desligamento).
     *
     * @return array{total:int, month:int, year:int, by_department: list<array{departamento:string, total:int}>}
     */
    public function countTerminatedInMonth(int $month, int $year): array
    {
        $month = max(1, min(12, $month));
        $year = max(2000, min(2100, $year));

        $sqlTotal = "SELECT COUNT(*) AS total
            FROM adms_users usr
            WHERE usr.data_desligamento IS NOT NULL
              AND MONTH(usr.data_desligamento) = :month
              AND YEAR(usr.data_desligamento) = :year";
        $stmt = $this->getConnection()->prepare($sqlTotal);
        $stmt->bindValue(':month', $month, PDO::PARAM_INT);
        $stmt->bindValue(':year', $year, PDO::PARAM_INT);
        $stmt->execute();
        $total = (int) ($stmt->fetchColumn() ?: 0);

        $sqlByDept = "SELECT dep.name AS departamento, COUNT(*) AS total
            FROM adms_users usr
            LEFT JOIN adms_departments dep ON dep.id = usr.user_department_id
            WHERE usr.data_desligamento IS NOT NULL
              AND MONTH(usr.data_desligamento) = :month
              AND YEAR(usr.data_desligamento) = :year
            GROUP BY dep.id, dep.name
            ORDER BY total DESC, dep.name ASC
            LIMIT 30";
        $stmt2 = $this->getConnection()->prepare($sqlByDept);
        $stmt2->bindValue(':month', $month, PDO::PARAM_INT);
        $stmt2->bindValue(':year', $year, PDO::PARAM_INT);
        $stmt2->execute();
        $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $byDepartment = [];
        foreach ($rows as $row) {
            $byDepartment[] = [
                'departamento' => (string) (($row['departamento'] ?? '') !== '' ? $row['departamento'] : '(sem departamento)'),
                'total' => (int) ($row['total'] ?? 0),
            ];
        }

        return [
            'total' => $total,
            'month' => $month,
            'year' => $year,
            'by_department' => $byDepartment,
        ];
    }

    /**
     * @return array{total:int, by_department: list<array{departamento:string, total:int}>, match_mode?: string}
     */
    private function countEmployeesByStatus(bool $active, ?string $departmentName): array
    {
        $statusSql = $active
            ? "usr.status = 'Ativo' AND usr.data_desligamento IS NULL"
            : "(usr.status = 'Inativo' OR usr.data_desligamento IS NOT NULL)";

        $dept = $departmentName !== null ? trim($departmentName) : '';
        $modes = [];
        if ($dept === '') {
            $modes[] = 'none';
        } else {
            $modes[] = 'exact';
            // Nomes curtos (TI): não usar LIKE — evita Marketing, Analítico, etc.
            if (mb_strlen($dept) > 3) {
                $modes[] = 'like';
            }
        }

        $total = 0;
        $byDepartment = [];
        $usedMode = 'none';

        foreach ($modes as $mode) {
            $filter = $this->buildDepartmentFilter($dept, $mode);
            $result = $this->runCountQuery($statusSql, $filter['sql'], $filter['params']);
            $total = $result['total'];
            $byDepartment = $result['by_department'];
            $usedMode = $mode;
            if ($dept === '' || $total > 0 || $mode === 'like' || count($modes) === 1) {
                break;
            }
        }

        return [
            'total' => $total,
            'by_department' => $byDepartment,
            'match_mode' => $usedMode,
        ];
    }

    /**
     * @param array<string, string> $params
     * @return array{total:int, by_department: list<array{departamento:string, total:int}>}
     */
    private function runCountQuery(string $statusSql, string $whereDept, array $params): array
    {
        $sqlTotal = "SELECT COUNT(*) AS total
            FROM adms_users usr
            INNER JOIN adms_departments dep ON dep.id = usr.user_department_id
            WHERE {$statusSql}
              {$whereDept}";
        $stmt = $this->getConnection()->prepare($sqlTotal);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, PDO::PARAM_STR);
        }
        $stmt->execute();
        $total = (int) ($stmt->fetchColumn() ?: 0);

        $sqlByDept = "SELECT dep.name AS departamento, COUNT(*) AS total
            FROM adms_users usr
            INNER JOIN adms_departments dep ON dep.id = usr.user_department_id
            WHERE {$statusSql}
              {$whereDept}
            GROUP BY dep.id, dep.name
            ORDER BY total DESC, dep.name ASC
            LIMIT 30";
        $stmt2 = $this->getConnection()->prepare($sqlByDept);
        foreach ($params as $k => $v) {
            $stmt2->bindValue($k, $v, PDO::PARAM_STR);
        }
        $stmt2->execute();
        $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $byDepartment = [];
        foreach ($rows as $row) {
            $byDepartment[] = [
                'departamento' => (string) ($row['departamento'] ?? ''),
                'total' => (int) ($row['total'] ?? 0),
            ];
        }

        return [
            'total' => $total,
            'by_department' => $byDepartment,
        ];
    }

    /**
     * @return array{sql: string, params: array<string, string>}
     */
    private function buildDepartmentFilter(string $dept, string $mode): array
    {
        if ($mode === 'none' || $dept === '') {
            return ['sql' => '', 'params' => []];
        }

        if ($mode === 'like') {
            return [
                'sql' => ' AND dep.name LIKE :dept ',
                'params' => [':dept' => '%' . $dept . '%'],
            ];
        }

        return [
            'sql' => ' AND LOWER(dep.name) = LOWER(:dept) ',
            'params' => [':dept' => $dept],
        ];
    }

    /**
     * @return list<string>
     */
    public function listDepartmentNames(): array
    {
        $sql = 'SELECT name FROM adms_departments ORDER BY name ASC';
        $rows = $this->getConnection()->query($sql)->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $names = [];
        foreach ($rows as $name) {
            $name = trim((string) $name);
            if ($name !== '') {
                $names[] = $name;
            }
        }

        return $names;
    }

    /**
     * @return array{total:int, with_attempts:int}
     */
    public function countBlockedUsers(): array
    {
        $sql = "SELECT
            SUM(CASE WHEN bloqueado = 'Sim' OR bloqueado = '1' OR bloqueado = 1 THEN 1 ELSE 0 END) AS total,
            SUM(CASE WHEN (bloqueado = 'Sim' OR bloqueado = '1' OR bloqueado = 1)
                      AND COALESCE(tentativas_login, 0) > 0 THEN 1 ELSE 0 END) AS with_attempts
            FROM adms_users";
        $row = $this->getConnection()->query($sql)->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total' => (int) ($row['total'] ?? 0),
            'with_attempts' => (int) ($row['with_attempts'] ?? 0),
        ];
    }
}
