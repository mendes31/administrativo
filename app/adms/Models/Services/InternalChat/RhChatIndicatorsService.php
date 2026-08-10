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
     * Desligados agrupados por mês no ano (pela data_desligamento).
     *
     * @return array{total:int, year:int, by_month: list<array{mes:int, rotulo:string, total:int}>}
     */
    public function countTerminatedByMonth(int $year): array
    {
        $year = max(2000, min(2100, $year));
        $monthNames = [
            1 => 'jan', 2 => 'fev', 3 => 'mar', 4 => 'abr',
            5 => 'mai', 6 => 'jun', 7 => 'jul', 8 => 'ago',
            9 => 'set', 10 => 'out', 11 => 'nov', 12 => 'dez',
        ];

        $sql = "SELECT MONTH(usr.data_desligamento) AS mes, COUNT(*) AS total
            FROM adms_users usr
            WHERE usr.data_desligamento IS NOT NULL
              AND YEAR(usr.data_desligamento) = :year
            GROUP BY MONTH(usr.data_desligamento)
            ORDER BY mes ASC";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':year', $year, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $totalsByMonth = [];
        foreach ($rows as $row) {
            $m = (int) ($row['mes'] ?? 0);
            if ($m >= 1 && $m <= 12) {
                $totalsByMonth[$m] = (int) ($row['total'] ?? 0);
            }
        }

        $byMonth = [];
        $total = 0;
        ksort($totalsByMonth, SORT_NUMERIC);
        foreach ($totalsByMonth as $m => $n) {
            if ($n < 1) {
                continue;
            }
            $total += $n;
            $byMonth[] = [
                'mes' => (int) $m,
                'rotulo' => ($monthNames[$m] ?? (string) $m) . '/' . $year,
                'total' => $n,
            ];
        }

        return [
            'total' => $total,
            'year' => $year,
            'by_month' => $byMonth,
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
            SUM(CASE WHEN {$this->blockedSql()} THEN 1 ELSE 0 END) AS total,
            SUM(CASE WHEN {$this->blockedSql()}
                      AND COALESCE(tentativas_login, 0) > 0 THEN 1 ELSE 0 END) AS with_attempts
            FROM adms_users";
        $row = $this->getConnection()->query($sql)->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total' => (int) ($row['total'] ?? 0),
            'with_attempts' => (int) ($row['with_attempts'] ?? 0),
        ];
    }

    /**
     * Bloqueados no Portal sem data de desligamento (ainda «na empresa» no cadastro).
     *
     * @return array{total:int, by_department: list<array{departamento:string, total:int}>}
     */
    public function countBlockedNotTerminated(): array
    {
        $blocked = $this->blockedSql('usr');
        $sqlTotal = "SELECT COUNT(*) AS total
            FROM adms_users usr
            WHERE {$blocked}
              AND usr.data_desligamento IS NULL";
        $total = (int) ($this->getConnection()->query($sqlTotal)->fetchColumn() ?: 0);

        $sqlByDept = "SELECT dep.name AS departamento, COUNT(*) AS total
            FROM adms_users usr
            LEFT JOIN adms_departments dep ON dep.id = usr.user_department_id
            WHERE {$blocked}
              AND usr.data_desligamento IS NULL
            GROUP BY dep.id, dep.name
            ORDER BY total DESC, dep.name ASC
            LIMIT 30";
        $rows = $this->getConnection()->query($sqlByDept)->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return [
            'total' => $total,
            'by_department' => $this->mapDepartmentRows($rows),
        ];
    }

    /**
     * Desligados (com data_desligamento), opcionalmente por ano e/ou mês.
     *
     * @return array{
     *   total:int,
     *   year:?int,
     *   month:?int,
     *   by_department: list<array{departamento:string, total:int}>
     * }
     */
    public function countTerminated(?int $month = null, ?int $year = null): array
    {
        if ($month !== null) {
            $month = max(1, min(12, $month));
        }
        if ($year !== null) {
            $year = max(2000, min(2100, $year));
        }

        $where = ['usr.data_desligamento IS NOT NULL'];
        $params = [];
        if ($month !== null) {
            $where[] = 'MONTH(usr.data_desligamento) = :month';
            $params[':month'] = $month;
        }
        if ($year !== null) {
            $where[] = 'YEAR(usr.data_desligamento) = :year';
            $params[':year'] = $year;
        }
        $whereSql = implode(' AND ', $where);

        $sqlTotal = "SELECT COUNT(*) AS total FROM adms_users usr WHERE {$whereSql}";
        $stmt = $this->getConnection()->prepare($sqlTotal);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, PDO::PARAM_INT);
        }
        $stmt->execute();
        $total = (int) ($stmt->fetchColumn() ?: 0);

        $sqlByDept = "SELECT dep.name AS departamento, COUNT(*) AS total
            FROM adms_users usr
            LEFT JOIN adms_departments dep ON dep.id = usr.user_department_id
            WHERE {$whereSql}
            GROUP BY dep.id, dep.name
            ORDER BY total DESC, dep.name ASC
            LIMIT 30";
        $stmt2 = $this->getConnection()->prepare($sqlByDept);
        foreach ($params as $k => $v) {
            $stmt2->bindValue($k, $v, PDO::PARAM_INT);
        }
        $stmt2->execute();
        $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return [
            'total' => $total,
            'year' => $year,
            'month' => $month,
            'by_department' => $this->mapDepartmentRows($rows),
        ];
    }

    /**
     * Lista nominativa de desligados (com data_desligamento).
     *
     * @return array{
     *   total:int,
     *   year:?int,
     *   month:?int,
     *   department:?string,
     *   truncated:bool,
     *   rows: list<array<string, mixed>>
     * }
     */
    public function listTerminated(
        ?int $month = null,
        ?int $year = null,
        ?string $department = null,
        int $limit = 150
    ): array {
        if ($month !== null) {
            $month = max(1, min(12, $month));
        }
        if ($year !== null) {
            $year = max(2000, min(2100, $year));
        }
        $limit = max(1, min(500, $limit));
        $department = $department !== null ? trim($department) : '';

        $where = ['usr.data_desligamento IS NOT NULL'];
        $params = [];
        $types = [];
        if ($month !== null) {
            $where[] = 'MONTH(usr.data_desligamento) = :month';
            $params[':month'] = $month;
            $types[':month'] = PDO::PARAM_INT;
        }
        if ($year !== null) {
            $where[] = 'YEAR(usr.data_desligamento) = :year';
            $params[':year'] = $year;
            $types[':year'] = PDO::PARAM_INT;
        }
        if ($department !== '') {
            $where[] = 'LOWER(dep.name) = LOWER(:dept)';
            $params[':dept'] = $department;
            $types[':dept'] = PDO::PARAM_STR;
        }
        $whereSql = implode(' AND ', $where);

        $sqlCount = "SELECT COUNT(*) AS total
            FROM adms_users usr
            LEFT JOIN adms_departments dep ON dep.id = usr.user_department_id
            WHERE {$whereSql}";
        $stmtCount = $this->getConnection()->prepare($sqlCount);
        foreach ($params as $k => $v) {
            $stmtCount->bindValue($k, $v, $types[$k] ?? PDO::PARAM_STR);
        }
        $stmtCount->execute();
        $total = (int) ($stmtCount->fetchColumn() ?: 0);

        $sql = "SELECT usr.name AS nome,
                       usr.username,
                       dep.name AS departamento,
                       pos.name AS cargo,
                       usr.data_admissao AS admissao,
                       usr.data_desligamento AS desligamento,
                       usr.motivo_desligamento AS motivo,
                       usr.status
                FROM adms_users usr
                LEFT JOIN adms_departments dep ON dep.id = usr.user_department_id
                LEFT JOIN adms_positions pos ON pos.id = usr.user_position_id
                WHERE {$whereSql}
                ORDER BY usr.data_desligamento DESC, usr.name ASC
                LIMIT {$limit}";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, $types[$k] ?? PDO::PARAM_STR);
        }
        $stmt->execute();
        $raw = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $rows = [];
        foreach ($raw as $row) {
            $adm = $this->normalizeDate($row['admissao'] ?? null);
            $term = $this->normalizeDate($row['desligamento'] ?? null);
            $rows[] = [
                'nome' => (string) ($row['nome'] ?? ''),
                'username' => (string) ($row['username'] ?? ''),
                'departamento' => (string) (($row['departamento'] ?? '') !== '' ? $row['departamento'] : '(sem departamento)'),
                'cargo' => (string) (($row['cargo'] ?? '') !== '' ? $row['cargo'] : '—'),
                'admissao' => $adm ?? '',
                'desligamento' => $term ?? '',
                'motivo' => (string) (($row['motivo'] ?? '') !== '' ? $row['motivo'] : '—'),
                'status' => (string) ($row['status'] ?? ''),
            ];
        }

        return [
            'total' => $total,
            'year' => $year,
            'month' => $month,
            'department' => $department !== '' ? $department : null,
            'truncated' => $total > count($rows),
            'rows' => $rows,
            'name' => 'Desligados',
        ];
    }

    /**
     * Contratações/admissões (pela data_admissao), opcionalmente por ano e/ou mês.
     *
     * @return array{
     *   total:int,
     *   year:?int,
     *   month:?int,
     *   by_department: list<array{departamento:string, total:int}>
     * }
     */
    public function countHired(?int $month = null, ?int $year = null): array
    {
        if ($month !== null) {
            $month = max(1, min(12, $month));
        }
        if ($year !== null) {
            $year = max(2000, min(2100, $year));
        }

        $where = ['usr.data_admissao IS NOT NULL'];
        $params = [];
        if ($month !== null) {
            $where[] = 'MONTH(usr.data_admissao) = :month';
            $params[':month'] = $month;
        }
        if ($year !== null) {
            $where[] = 'YEAR(usr.data_admissao) = :year';
            $params[':year'] = $year;
        }
        $whereSql = implode(' AND ', $where);

        $sqlTotal = "SELECT COUNT(*) AS total FROM adms_users usr WHERE {$whereSql}";
        $stmt = $this->getConnection()->prepare($sqlTotal);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, PDO::PARAM_INT);
        }
        $stmt->execute();
        $total = (int) ($stmt->fetchColumn() ?: 0);

        $sqlByDept = "SELECT dep.name AS departamento, COUNT(*) AS total
            FROM adms_users usr
            LEFT JOIN adms_departments dep ON dep.id = usr.user_department_id
            WHERE {$whereSql}
            GROUP BY dep.id, dep.name
            ORDER BY total DESC, dep.name ASC
            LIMIT 30";
        $stmt2 = $this->getConnection()->prepare($sqlByDept);
        foreach ($params as $k => $v) {
            $stmt2->bindValue($k, $v, PDO::PARAM_INT);
        }
        $stmt2->execute();
        $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return [
            'total' => $total,
            'year' => $year,
            'month' => $month,
            'by_department' => $this->mapDepartmentRows($rows),
        ];
    }

    /**
     * Lista nominativa de contratações (pela data_admissao).
     *
     * @return array{
     *   total:int,
     *   year:?int,
     *   month:?int,
     *   department:?string,
     *   truncated:bool,
     *   rows: list<array<string, mixed>>,
     *   name: string
     * }
     */
    public function listHired(
        ?int $month = null,
        ?int $year = null,
        ?string $department = null,
        int $limit = 150
    ): array {
        if ($month !== null) {
            $month = max(1, min(12, $month));
        }
        if ($year !== null) {
            $year = max(2000, min(2100, $year));
        }
        $limit = max(1, min(500, $limit));
        $department = $department !== null ? trim($department) : '';

        $where = ['usr.data_admissao IS NOT NULL'];
        $params = [];
        $types = [];
        if ($month !== null) {
            $where[] = 'MONTH(usr.data_admissao) = :month';
            $params[':month'] = $month;
            $types[':month'] = PDO::PARAM_INT;
        }
        if ($year !== null) {
            $where[] = 'YEAR(usr.data_admissao) = :year';
            $params[':year'] = $year;
            $types[':year'] = PDO::PARAM_INT;
        }
        if ($department !== '') {
            $where[] = 'LOWER(dep.name) = LOWER(:dept)';
            $params[':dept'] = $department;
            $types[':dept'] = PDO::PARAM_STR;
        }
        $whereSql = implode(' AND ', $where);

        $sqlCount = "SELECT COUNT(*) AS total
            FROM adms_users usr
            LEFT JOIN adms_departments dep ON dep.id = usr.user_department_id
            WHERE {$whereSql}";
        $stmtCount = $this->getConnection()->prepare($sqlCount);
        foreach ($params as $k => $v) {
            $stmtCount->bindValue($k, $v, $types[$k] ?? PDO::PARAM_STR);
        }
        $stmtCount->execute();
        $total = (int) ($stmtCount->fetchColumn() ?: 0);

        $sql = "SELECT usr.name AS nome,
                       usr.username,
                       dep.name AS departamento,
                       pos.name AS cargo,
                       usr.data_admissao AS admissao,
                       usr.data_desligamento AS desligamento,
                       usr.status
                FROM adms_users usr
                LEFT JOIN adms_departments dep ON dep.id = usr.user_department_id
                LEFT JOIN adms_positions pos ON pos.id = usr.user_position_id
                WHERE {$whereSql}
                ORDER BY usr.data_admissao DESC, usr.name ASC
                LIMIT {$limit}";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, $types[$k] ?? PDO::PARAM_STR);
        }
        $stmt->execute();
        $raw = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $rows = [];
        foreach ($raw as $row) {
            $adm = $this->normalizeDate($row['admissao'] ?? null);
            $term = $this->normalizeDate($row['desligamento'] ?? null);
            $rows[] = [
                'nome' => (string) ($row['nome'] ?? ''),
                'username' => (string) ($row['username'] ?? ''),
                'departamento' => (string) (($row['departamento'] ?? '') !== '' ? $row['departamento'] : '(sem departamento)'),
                'cargo' => (string) (($row['cargo'] ?? '') !== '' ? $row['cargo'] : '—'),
                'admissao' => $adm ?? '',
                'desligamento' => $term ?? '',
                'status' => (string) ($row['status'] ?? ''),
            ];
        }

        return [
            'total' => $total,
            'year' => $year,
            'month' => $month,
            'department' => $department !== '' ? $department : null,
            'truncated' => $total > count($rows),
            'rows' => $rows,
            'name' => 'Contratações',
        ];
    }

    /**
     * Busca colaborador por nome, username ou e-mail (sem CPF/celular).
     *
     * @return array{
     *   query: string,
     *   matches: list<array<string, mixed>>,
     *   match_count: int
     * }
     */
    public function lookupPerson(string $query): array
    {
        $query = trim(preg_replace('/\s+/u', ' ', $query) ?? $query);
        if ($query === '' || mb_strlen($query) < 2) {
            return ['query' => $query, 'matches' => [], 'match_count' => 0];
        }

        $like = '%' . $query . '%';
        $sql = "SELECT usr.id, usr.name, usr.username, usr.email, usr.status, usr.bloqueado,
                       usr.data_admissao, usr.data_desligamento,
                       dep.name AS departamento, pos.name AS cargo
                FROM adms_users usr
                LEFT JOIN adms_departments dep ON dep.id = usr.user_department_id
                LEFT JOIN adms_positions pos ON pos.id = usr.user_position_id
                WHERE usr.name LIKE :q
                   OR usr.username LIKE :q
                   OR usr.email LIKE :q
                   OR usr.matricula LIKE :q
                   OR CAST(usr.id AS CHAR) = :q_exact_id
                ORDER BY
                    CASE
                        WHEN usr.matricula = :q_exact_mat THEN 0
                        WHEN LOWER(usr.name) = LOWER(:q_exact) THEN 1
                        WHEN LOWER(usr.username) = LOWER(:q_exact2) THEN 2
                        WHEN LOWER(usr.name) LIKE LOWER(:q_prefix) THEN 3
                        ELSE 4
                    END,
                    usr.name ASC
                LIMIT 8";
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':q', $like, PDO::PARAM_STR);
            $stmt->bindValue(':q_exact_id', $query, PDO::PARAM_STR);
            $stmt->bindValue(':q_exact_mat', $query, PDO::PARAM_STR);
            $stmt->bindValue(':q_exact', $query, PDO::PARAM_STR);
            $stmt->bindValue(':q_exact2', $query, PDO::PARAM_STR);
            $stmt->bindValue(':q_prefix', $query . '%', PDO::PARAM_STR);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            // Homolog sem coluna matricula: fallback legado.
            $sql = "SELECT usr.id, usr.name, usr.username, usr.email, usr.status, usr.bloqueado,
                           usr.data_admissao, usr.data_desligamento,
                           dep.name AS departamento, pos.name AS cargo
                    FROM adms_users usr
                    LEFT JOIN adms_departments dep ON dep.id = usr.user_department_id
                    LEFT JOIN adms_positions pos ON pos.id = usr.user_position_id
                    WHERE usr.name LIKE :q
                       OR usr.username LIKE :q
                       OR usr.email LIKE :q
                       OR CAST(usr.id AS CHAR) = :q_exact_id
                    ORDER BY
                        CASE
                            WHEN LOWER(usr.name) = LOWER(:q_exact) THEN 0
                            WHEN LOWER(usr.username) = LOWER(:q_exact2) THEN 1
                            WHEN LOWER(usr.name) LIKE LOWER(:q_prefix) THEN 2
                            ELSE 3
                        END,
                        usr.name ASC
                    LIMIT 8";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':q', $like, PDO::PARAM_STR);
            $stmt->bindValue(':q_exact_id', $query, PDO::PARAM_STR);
            $stmt->bindValue(':q_exact', $query, PDO::PARAM_STR);
            $stmt->bindValue(':q_exact2', $query, PDO::PARAM_STR);
            $stmt->bindValue(':q_prefix', $query . '%', PDO::PARAM_STR);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        $matches = [];
        foreach ($rows as $row) {
            $admissao = $this->normalizeDate($row['data_admissao'] ?? null);
            $desligamento = $this->normalizeDate($row['data_desligamento'] ?? null);
            $tenure = $this->computeTenure($admissao, $desligamento);
            $blocked = $this->isBlockedValue($row['bloqueado'] ?? null);

            $matches[] = [
                'id' => (int) ($row['id'] ?? 0),
                'name' => (string) ($row['name'] ?? ''),
                'username' => (string) ($row['username'] ?? ''),
                'email' => (string) ($row['email'] ?? ''),
                'status' => (string) ($row['status'] ?? ''),
                'blocked' => $blocked,
                'department' => (string) (($row['departamento'] ?? '') !== '' ? $row['departamento'] : '(sem departamento)'),
                'position' => (string) (($row['cargo'] ?? '') !== '' ? $row['cargo'] : '(sem cargo)'),
                'admission_date' => $admissao,
                'termination_date' => $desligamento,
                'years_at_company' => $tenure['years_decimal'],
                'tenure_label' => $tenure['label'],
            ];
        }

        return [
            'query' => $query,
            'matches' => $matches,
            'match_count' => count($matches),
        ];
    }

    private function blockedSql(string $alias = ''): string
    {
        $col = $alias !== '' ? "{$alias}.bloqueado" : 'bloqueado';

        return "({$col} = 'Sim' OR {$col} = '1' OR {$col} = 1)";
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array{departamento:string, total:int}>
     */
    private function mapDepartmentRows(array $rows): array
    {
        $byDepartment = [];
        foreach ($rows as $row) {
            $byDepartment[] = [
                'departamento' => (string) (($row['departamento'] ?? '') !== '' ? $row['departamento'] : '(sem departamento)'),
                'total' => (int) ($row['total'] ?? 0),
            ];
        }

        return $byDepartment;
    }

    private function isBlockedValue(mixed $value): bool
    {
        if ($value === true || $value === 1 || $value === '1') {
            return true;
        }
        $s = mb_strtolower(trim((string) $value));

        return $s === 'sim' || $s === 's' || $s === 'true';
    }

    private function normalizeDate(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === '0000-00-00') {
            return null;
        }
        $s = substr((string) $value, 0, 10);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) {
            return null;
        }

        return $s;
    }

    /**
     * @return array{years_decimal: float|null, label: string}
     */
    private function computeTenure(?string $admission, ?string $termination): array
    {
        if ($admission === null) {
            return ['years_decimal' => null, 'label' => 'sem data de admissão'];
        }

        try {
            $start = new \DateTimeImmutable($admission);
            $end = $termination !== null
                ? new \DateTimeImmutable($termination)
                : new \DateTimeImmutable('today');
            if ($end < $start) {
                return ['years_decimal' => null, 'label' => 'datas inconsistentes'];
            }
            $diff = $start->diff($end);
            $years = $diff->y;
            $months = $diff->m;
            $decimal = round($years + ($months / 12), 1);
            if ($years <= 0 && $months <= 0) {
                $label = $diff->days . ' dia(s)';
            } elseif ($years <= 0) {
                $label = $months . ' mês(es)';
            } elseif ($months <= 0) {
                $label = $years . ' ano(s)';
            } else {
                $label = $years . ' ano(s) e ' . $months . ' mês(es)';
            }
            if ($termination !== null) {
                $label .= ' (até o desligamento)';
            }

            return ['years_decimal' => $decimal, 'label' => $label];
        } catch (\Throwable) {
            return ['years_decimal' => null, 'label' => 'não calculável'];
        }
    }
}
