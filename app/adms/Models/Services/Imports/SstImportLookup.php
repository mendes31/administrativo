<?php

declare(strict_types=1);

namespace App\adms\Models\Services\Imports;

use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Models\Services\DbConnection;
use PDO;

final class SstImportLookup extends DbConnection
{
    /** @var list<string> */
    private const TABLES = [
        'adms_sst_riscos',
        'adms_sst_epis',
        'adms_sst_exames',
        'adms_sst_treinamentos',
        'adms_sst_cids',
        'adms_sst_medicos',
        'adms_sst_ghe',
        'adms_sst_ghe_colaboradores',
        'adms_sst_ghe_treinamentos',
        'adms_sst_equipamento_tipos',
        'adms_sst_equipamentos',
        'adms_sst_equipamento_checklist_itens',
        'adms_sst_riscos_cargo',
        'adms_sst_risco_epi',
        'adms_sst_risco_exame',
        'adms_sst_risco_treinamento',
        'adms_sst_epi_necessidade',
        'adms_sst_exame_necessidade',
        'adms_sst_treinamento_necessidade',
    ];

    public function byId(string $table, int $id): ?array
    {
        $table = $this->safeTable($table);
        if ($table === null || $id <= 0) {
            return null;
        }
        $stmt = $this->getConnection()->prepare("SELECT * FROM {$table} WHERE id = :id LIMIT 1");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function byCodigo(string $table, string $codigo): ?array
    {
        $table = $this->safeTable($table);
        $codigo = strtoupper(trim($codigo));
        if ($table === null || $codigo === '') {
            return null;
        }
        $stmt = $this->getConnection()->prepare("SELECT * FROM {$table} WHERE UPPER(codigo) = :c LIMIT 1");
        $stmt->bindValue(':c', $codigo);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function byCodigoAll(string $table, string $codigo): array
    {
        $table = $this->safeTable($table);
        $codigo = strtoupper(trim($codigo));
        if ($table === null || $codigo === '') {
            return [];
        }
        $stmt = $this->getConnection()->prepare("SELECT * FROM {$table} WHERE UPPER(codigo) = :c");
        $stmt->bindValue(':c', $codigo);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function byNome(string $table, string $nome, string $column = 'nome'): ?array
    {
        $table = $this->safeTable($table);
        $nome = trim(preg_replace('/\s+/u', ' ', $nome) ?? $nome);
        if ($table === null || $nome === '' || !in_array($column, ['nome', 'descricao'], true)) {
            return null;
        }
        $stmt = $this->getConnection()->prepare(
            "SELECT * FROM {$table} WHERE LOWER(TRIM({$column})) = LOWER(:n) LIMIT 1"
        );
        $stmt->bindValue(':n', $nome);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function catalog(string $table, string $raw, bool $hasCodigo = true, string $nameCol = 'nome'): ?array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        if (ctype_digit($raw)) {
            return $this->byId($table, (int) $raw);
        }
        if ($hasCodigo) {
            $byCode = $this->byCodigo($table, $raw);
            if ($byCode !== null) {
                return $byCode;
            }
        }

        return $this->byNome($table, $raw, $nameCol);
    }

    public function medico(string $raw): ?array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        if (ctype_digit($raw)) {
            return $this->byId('adms_sst_medicos', (int) $raw);
        }
        $stmt = $this->getConnection()->prepare(
            'SELECT * FROM adms_sst_medicos WHERE crm = :c OR LOWER(TRIM(nome)) = LOWER(:n) LIMIT 1'
        );
        $stmt->bindValue(':c', $raw);
        $stmt->bindValue(':n', $raw);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function user(string $raw): ?int
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        $pdo = $this->getConnection();
        if (ctype_digit($raw)) {
            $stmt = $pdo->prepare('SELECT id FROM adms_users WHERE id = :id LIMIT 1');
            $stmt->bindValue(':id', (int) $raw, PDO::PARAM_INT);
            $stmt->execute();
            $id = $stmt->fetchColumn();

            return $id ? (int) $id : null;
        }
        $cpf = preg_replace('/\D+/', '', $raw) ?? '';
        if (strlen($cpf) === 11) {
            $stmt = $pdo->prepare(
                'SELECT id FROM adms_users
                 WHERE REPLACE(REPLACE(REPLACE(REPLACE(cpf, ".", ""), "-", ""), "/", ""), " ", "") = :cpf
                 LIMIT 1'
            );
            $stmt->bindValue(':cpf', $cpf);
            $stmt->execute();
            $id = $stmt->fetchColumn();
            if ($id) {
                return (int) $id;
            }
        }
        $stmt = $pdo->prepare(
            'SELECT id FROM adms_users
             WHERE username = :u OR LOWER(email) = LOWER(:e) OR LOWER(TRIM(name)) = LOWER(:n)
             LIMIT 1'
        );
        $stmt->bindValue(':u', $raw);
        $stmt->bindValue(':e', $raw);
        $stmt->bindValue(':n', $raw);
        $stmt->execute();
        $id = $stmt->fetchColumn();

        return $id ? (int) $id : null;
    }

    public function department(string $raw): ?int
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        $repo = new DepartmentsRepository();
        if (ctype_digit($raw)) {
            $row = $repo->getDepartment((int) $raw);

            return $row ? (int) $row['id'] : null;
        }
        $row = $repo->getByName($raw);

        return $row ? (int) $row['id'] : null;
    }

    public function position(string $raw): ?int
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        $repo = new PositionsRepository();
        if (ctype_digit($raw)) {
            $row = $repo->getPosition((int) $raw);

            return $row ? (int) $row['id'] : null;
        }
        $row = $repo->getByName($raw);

        return $row ? (int) $row['id'] : null;
    }

    /**
     * @param array<string, int|string|null> $where
     */
    public function findLink(string $table, array $where): ?array
    {
        $table = $this->safeTable($table);
        if ($table === null || $where === []) {
            return null;
        }
        $parts = [];
        $params = [];
        $i = 0;
        foreach ($where as $col => $val) {
            if (!preg_match('/^[a-z_]+$/', $col)) {
                continue;
            }
            $ph = ':p' . $i++;
            if ($val === null || $val === '') {
                $parts[] = "{$col} IS NULL";
            } else {
                $parts[] = "{$col} = {$ph}";
                $params[$ph] = $val;
            }
        }
        if ($parts === []) {
            return null;
        }
        $sql = "SELECT * FROM {$table} WHERE " . implode(' AND ', $parts) . ' LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    private function safeTable(string $table): ?string
    {
        return in_array($table, self::TABLES, true) ? $table : null;
    }
}
