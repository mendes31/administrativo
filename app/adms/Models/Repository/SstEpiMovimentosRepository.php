<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

class SstEpiMovimentosRepository extends DbConnection
{
    /** @var list<string> */
    public const TIPOS_ENTRADA = ['Entrada', 'Devolução'];

    /** @var list<string> */
    public const TIPOS_SAIDA = ['Saída', 'Entrega'];

    public function hasTable(): bool
    {
        try {
            $this->getConnection()->query('SELECT 1 FROM adms_sst_epi_movimentos LIMIT 1');

            return true;
        } catch (\PDOException) {
            return false;
        }
    }

    public function getAll(int $page, int $perPage, array $filters = []): array
    {
        if (!$this->hasTable()) {
            return [];
        }
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        [$whereClause, $params] = $this->buildWhere($filters);
        $sql = "SELECT m.*, ep.nome AS epi_nome, u.name AS responsavel_nome
                FROM adms_sst_epi_movimentos m
                INNER JOIN adms_sst_epis ep ON ep.id = m.adms_sst_epi_id
                LEFT JOIN adms_users u ON u.id = m.created_by
                {$whereClause}
                ORDER BY m.data_movimento DESC, m.id DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getTotal(array $filters = []): int
    {
        if (!$this->hasTable()) {
            return 0;
        }
        [$whereClause, $params] = $this->buildWhere($filters);
        $sql = "SELECT COUNT(*) AS total FROM adms_sst_epi_movimentos m
                INNER JOIN adms_sst_epis ep ON ep.id = m.adms_sst_epi_id
                {$whereClause}";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();

        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    /** @return list<array<string, mixed>> */
    public function getByEpiId(int $epiId, int $limit = 50): array
    {
        if (!$this->hasTable() || $epiId <= 0) {
            return [];
        }
        $sql = "SELECT m.*, u.name AS responsavel_nome
                FROM adms_sst_epi_movimentos m
                LEFT JOIN adms_users u ON u.id = m.created_by
                WHERE m.adms_sst_epi_id = :eid
                ORDER BY m.data_movimento DESC, m.id DESC
                LIMIT :lim";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':eid', $epiId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getSaldoCalculado(int $epiId): int
    {
        if (!$this->hasTable() || $epiId <= 0) {
            return 0;
        }
        $sql = "SELECT tipo_movimento, quantidade FROM adms_sst_epi_movimentos WHERE adms_sst_epi_id = :eid";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':eid', $epiId, PDO::PARAM_INT);
        $stmt->execute();
        $saldo = 0;
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $saldo += self::impactoSaldo((string) ($row['tipo_movimento'] ?? ''), (int) ($row['quantidade'] ?? 0));
        }

        return max(0, $saldo);
    }

    public static function impactoSaldo(string $tipo, int $quantidade): int
    {
        if ($tipo === 'Ajuste') {
            return $quantidade;
        }
        $qty = max(0, abs($quantidade));
        if (in_array($tipo, self::TIPOS_ENTRADA, true)) {
            return $qty;
        }
        if (in_array($tipo, self::TIPOS_SAIDA, true)) {
            return -$qty;
        }

        return 0;
    }

    public function existsReferencia(string $tipo, int $refId): bool
    {
        if (!$this->hasTable() || $refId <= 0 || trim($tipo) === '') {
            return false;
        }
        $sql = 'SELECT 1 FROM adms_sst_epi_movimentos WHERE referencia_tipo = :t AND referencia_id = :rid LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':t', $tipo, PDO::PARAM_STR);
        $stmt->bindValue(':rid', $refId, PDO::PARAM_INT);
        $stmt->execute();

        return (bool) $stmt->fetchColumn();
    }

    /** @return list<array{ca_numero: string, saldo: int, ca_validade: string|null}> */
    public function getSaldoPorCaPorEpi(int $epiId, bool $somenteComSaldo = true): array
    {
        if (!$this->hasTable() || $epiId <= 0 || !$this->hasColumn('ca_numero')) {
            return [];
        }
        $sql = 'SELECT ca_numero, tipo_movimento, quantidade, ca_validade
                FROM adms_sst_epi_movimentos
                WHERE adms_sst_epi_id = :eid
                  AND ca_numero IS NOT NULL AND TRIM(ca_numero) <> \'\'
                ORDER BY id ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':eid', $epiId, PDO::PARAM_INT);
        $stmt->execute();

        /** @var array<string, array{ca_numero: string, saldo: int, ca_validade: string|null}> $porCa */
        $porCa = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $ca = strtoupper(trim((string) ($row['ca_numero'] ?? '')));
            if ($ca === '') {
                continue;
            }
            if (!isset($porCa[$ca])) {
                $porCa[$ca] = ['ca_numero' => $ca, 'saldo' => 0, 'ca_validade' => null];
            }
            $porCa[$ca]['saldo'] += self::impactoSaldo(
                (string) ($row['tipo_movimento'] ?? ''),
                (int) ($row['quantidade'] ?? 0)
            );
            $val = trim((string) ($row['ca_validade'] ?? ''));
            if ($val !== '' && in_array((string) ($row['tipo_movimento'] ?? ''), self::TIPOS_ENTRADA, true)) {
                $porCa[$ca]['ca_validade'] = $val;
            }
        }

        $out = array_values($porCa);
        if ($somenteComSaldo) {
            $out = array_values(array_filter($out, static fn (array $c): bool => (int) ($c['saldo'] ?? 0) > 0));
        }

        usort($out, static fn (array $a, array $b): int => strcmp((string) $a['ca_numero'], (string) $b['ca_numero']));

        return $out;
    }

    public function getSaldoCa(int $epiId, string $caNumero): int
    {
        $ca = strtoupper(trim($caNumero));
        if ($ca === '') {
            return 0;
        }
        foreach ($this->getSaldoPorCaPorEpi($epiId, false) as $row) {
            if (($row['ca_numero'] ?? '') === $ca) {
                return max(0, (int) ($row['saldo'] ?? 0));
            }
        }

        return 0;
    }

    public function getValidadeCaLote(int $epiId, string $caNumero): ?string
    {
        if (!$this->hasTable() || $epiId <= 0 || !$this->hasColumn('ca_validade')) {
            return null;
        }
        $ca = strtoupper(trim($caNumero));
        if ($ca === '') {
            return null;
        }
        $sql = "SELECT ca_validade FROM adms_sst_epi_movimentos
                WHERE adms_sst_epi_id = :eid
                  AND ca_numero = :ca
                  AND ca_validade IS NOT NULL
                ORDER BY id DESC
                LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':eid', $epiId, PDO::PARAM_INT);
        $stmt->bindValue(':ca', $ca, PDO::PARAM_STR);
        $stmt->execute();
        $val = $stmt->fetchColumn();

        return $val !== false && $val !== null && $val !== '' ? (string) $val : null;
    }

    /** @return list<string> */
    public function getCaNumerosPorEpi(int $epiId, int $limit = 15): array
    {
        $rows = $this->getSaldoPorCaPorEpi($epiId, true);
        $out = [];
        foreach ($rows as $row) {
            $ca = (string) ($row['ca_numero'] ?? '');
            if ($ca !== '') {
                $out[] = $ca;
            }
            if (count($out) >= $limit) {
                break;
            }
        }

        return $out;
    }

    public function create(array $data): int|false
    {
        if (!$this->hasTable()) {
            return false;
        }
        $sql = 'INSERT INTO adms_sst_epi_movimentos
            (adms_sst_epi_id, tipo_movimento, quantidade, ca_numero, ca_validade, data_movimento, documento_ref,
             referencia_tipo, referencia_id, saldo_apos, observacoes, created_by, created_at)
            VALUES
            (:epi_id, :tipo, :qty, :ca_numero, :ca_validade, :data_mov, :doc_ref, :ref_tipo, :ref_id, :saldo_apos, :obs, :created_by, NOW())';
        if (!$this->hasColumn('ca_numero')) {
            $sql = 'INSERT INTO adms_sst_epi_movimentos
            (adms_sst_epi_id, tipo_movimento, quantidade, data_movimento, documento_ref,
             referencia_tipo, referencia_id, saldo_apos, observacoes, created_by, created_at)
            VALUES
            (:epi_id, :tipo, :qty, :data_mov, :doc_ref, :ref_tipo, :ref_id, :saldo_apos, :obs, :created_by, NOW())';
        }
        $stmt = $this->getConnection()->prepare($sql);
        $epiId = (int) ($data['adms_sst_epi_id'] ?? 0);
        $tipo = (string) ($data['tipo_movimento'] ?? 'Entrada');
        $qty = (int) ($data['quantidade'] ?? 1);
        if ($tipo !== 'Ajuste') {
            $qty = max(1, abs($qty));
        }
        $uid = (int) ($_SESSION['user_id'] ?? 0);
        $stmt->bindValue(':epi_id', $epiId, PDO::PARAM_INT);
        $stmt->bindValue(':tipo', (string) ($data['tipo_movimento'] ?? 'Entrada'), PDO::PARAM_STR);
        $stmt->bindValue(':qty', $qty, PDO::PARAM_INT);
        if ($this->hasColumn('ca_numero')) {
            $ca = trim((string) ($data['ca_numero'] ?? ''));
            $stmt->bindValue(':ca_numero', $ca !== '' ? $ca : null, $ca !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $val = trim((string) ($data['ca_validade'] ?? ''));
            $stmt->bindValue(':ca_validade', $val !== '' ? $val : null, $val !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        }
        $stmt->bindValue(':data_mov', (string) ($data['data_movimento'] ?? date('Y-m-d')), PDO::PARAM_STR);
        $doc = trim((string) ($data['documento_ref'] ?? ''));
        $stmt->bindValue(':doc_ref', $doc !== '' ? $doc : null, $doc !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $refTipo = trim((string) ($data['referencia_tipo'] ?? ''));
        $stmt->bindValue(':ref_tipo', $refTipo !== '' ? $refTipo : null, $refTipo !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $refId = (int) ($data['referencia_id'] ?? 0);
        $stmt->bindValue(':ref_id', $refId > 0 ? $refId : null, $refId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $saldoApos = isset($data['saldo_apos']) ? (int) $data['saldo_apos'] : null;
        $stmt->bindValue(':saldo_apos', $saldoApos, $saldoApos === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $obs = trim((string) ($data['observacoes'] ?? ''));
        $stmt->bindValue(':obs', $obs !== '' ? $obs : null, $obs !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':created_by', $uid > 0 ? $uid : null, $uid > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
        if (!$stmt->execute()) {
            return false;
        }
        $newId = (int) $this->getConnection()->lastInsertId();
        if ($newId > 0) {
            LogAlteracaoService::registrarAlteracao('adms_sst_epi_movimentos', $newId, $uid > 0 ? $uid : 1, 'INSERT', [], array_merge($data, ['id' => $newId]));
        }

        return $newId;
    }

    private function buildWhere(array $filters): array
    {
        $where = [];
        $params = [];
        if (!empty($filters['adms_sst_epi_id'])) {
            $where[] = 'm.adms_sst_epi_id = :epi_id';
            $params[':epi_id'] = (int) $filters['adms_sst_epi_id'];
        }
        if (!empty($filters['tipo_movimento'])) {
            $where[] = 'm.tipo_movimento = :tipo';
            $params[':tipo'] = $filters['tipo_movimento'];
        }
        if (!empty($filters['search'])) {
            $search = '(ep.nome LIKE :search OR m.documento_ref LIKE :search';
            if ($this->hasColumn('ca_numero')) {
                $search .= ' OR m.ca_numero LIKE :search';
            }
            $where[] = $search . ')';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['estoque_baixo'])) {
            $where[] = 'ep.estoque_minimo > 0 AND ep.estoque_atual <= ep.estoque_minimo';
        }
        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        return [$whereClause, $params];
    }

    private function hasColumn(string $column): bool
    {
        static $cache = [];
        if (array_key_exists($column, $cache)) {
            return $cache[$column];
        }
        if (!$this->hasTable()) {
            $cache[$column] = false;

            return false;
        }
        try {
            $stmt = $this->getConnection()->query(
                'SHOW COLUMNS FROM adms_sst_epi_movimentos LIKE ' . $this->getConnection()->quote($column)
            );
            $cache[$column] = (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException) {
            $cache[$column] = false;
        }

        return $cache[$column];
    }
}
