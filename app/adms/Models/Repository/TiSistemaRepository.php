<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use Exception;
use PDO;
use PDOException;

class TiSistemaRepository extends DbConnection
{
    public const TIPOS = ['embarcado', 'local', 'rede', 'saas', 'outro'];
    public const STATUS_ATIVO = 'ativo';
    public const STATUS_INATIVO = 'inativo';

    /**
     * Rótulo amigável para listagens e selects (nome · tag · local · filial).
     *
     * @param array<string, mixed> $row
     */
    public static function formatLabel(array $row): string
    {
        $parts = [trim((string) ($row['nome'] ?? ''))];
        $tag = trim((string) ($row['equipamento_tag'] ?? ''));
        if ($tag !== '') {
            $parts[] = $tag;
        }
        $loc = trim((string) ($row['localizacao'] ?? ''));
        if ($loc !== '') {
            $parts[] = $loc;
        }
        $filial = trim((string) ($row['filial_nome'] ?? ''));
        if ($filial !== '') {
            $parts[] = $filial;
        }

        return implode(' · ', array_filter($parts, static fn (string $p): bool => $p !== ''));
    }

    /**
     * @param array<string, mixed> $form
     * @return array{0: list<string>, 1: array<string, mixed>}
     */
    public function validateAndNormalize(array $form, ?int $excludeId = null): array
    {
        $errors = [];
        $nome = trim((string) ($form['nome'] ?? ''));
        if ($nome === '') {
            $errors[] = 'Informe o nome do sistema.';
        }

        $tipo = (string) ($form['tipo'] ?? 'outro');
        if (!in_array($tipo, self::TIPOS, true)) {
            $errors[] = 'Tipo inválido.';
        }

        $status = (string) ($form['status'] ?? self::STATUS_ATIVO);
        if (!in_array($status, [self::STATUS_ATIVO, self::STATUS_INATIVO], true)) {
            $errors[] = 'Status inválido.';
        }

        $tag = trim((string) ($form['equipamento_tag'] ?? ''));
        if ($tipo === 'embarcado' && $tag === '') {
            $errors[] = 'Para sistemas embarcados, informe a tag do equipamento (identifica a máquina física).';
        }

        $branchId = (int) ($form['adms_branch_id'] ?? 0);
        if ($branchId < 0) {
            $branchId = 0;
        }

        if ($tag !== '' && $this->equipamentoTagExists($tag, $excludeId)) {
            $errors[] = 'Já existe um sistema cadastrado com essa tag de equipamento.';
        }

        $codigo = trim((string) ($form['codigo'] ?? ''));
        // Código interno é gerado pelo sistema na criação e imutável na edição.
        // Se excludeId informado, preservamos o código já gravado (ignoramos POST).
        if ($excludeId !== null && $excludeId > 0) {
            $existing = $this->getById($excludeId);
            $codigo = trim((string) ($existing['codigo'] ?? ''));
        } else {
            $codigo = '';
        }

        $data = [
            'codigo' => $codigo !== '' ? $codigo : null,
            'nome' => $nome,
            'descricao' => trim((string) ($form['descricao'] ?? '')) ?: null,
            'tipo' => $tipo,
            'localizacao' => trim((string) ($form['localizacao'] ?? '')) ?: null,
            'equipamento_tag' => $tag !== '' ? $tag : null,
            'fabricante' => trim((string) ($form['fabricante'] ?? '')) ?: null,
            'modelo' => trim((string) ($form['modelo'] ?? '')) ?: null,
            'numero_serie' => trim((string) ($form['numero_serie'] ?? '')) ?: null,
            'adms_branch_id' => $branchId > 0 ? $branchId : null,
            'observacoes' => trim((string) ($form['observacoes'] ?? '')) ?: null,
            'status' => $status,
        ];

        return [$errors, $data];
    }

    public function equipamentoTagExists(string $tag, ?int $excludeId = null): bool
    {
        $tag = trim($tag);
        if ($tag === '') {
            return false;
        }
        try {
            $sql = 'SELECT id FROM ti_sistemas WHERE equipamento_tag = :tag';
            if ($excludeId !== null && $excludeId > 0) {
                $sql .= ' AND id <> :id';
            }
            $sql .= ' LIMIT 1';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':tag', $tag, PDO::PARAM_STR);
            if ($excludeId !== null && $excludeId > 0) {
                $stmt->bindValue(':id', $excludeId, PDO::PARAM_INT);
            }
            $stmt->execute();
            return (bool) $stmt->fetchColumn();
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'TiSistemaRepository::equipamentoTagExists', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function codigoExists(string $codigo, ?int $excludeId = null): bool
    {
        $codigo = trim($codigo);
        if ($codigo === '') {
            return false;
        }
        try {
            $sql = 'SELECT id FROM ti_sistemas WHERE codigo = :codigo';
            if ($excludeId !== null && $excludeId > 0) {
                $sql .= ' AND id <> :id';
            }
            $sql .= ' LIMIT 1';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':codigo', $codigo, PDO::PARAM_STR);
            if ($excludeId !== null && $excludeId > 0) {
                $stmt->bindValue(':id', $excludeId, PDO::PARAM_INT);
            }
            $stmt->execute();
            return (bool) $stmt->fetchColumn();
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'TiSistemaRepository::codigoExists', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Próximo código interno incremental (somente dígitos, 5 casas: 00001, 00002…).
     */
    public function allocateNextCodigo(): string
    {
        try {
            $stmt = $this->getConnection()->query(
                "SELECT MAX(CAST(codigo AS UNSIGNED)) FROM ti_sistemas
                 WHERE codigo IS NOT NULL AND codigo REGEXP '^[0-9]+$'"
            );
            $max = (int) ($stmt ? $stmt->fetchColumn() : 0);
            $next = $max + 1;
            // Evita colisão se existir código fora do padrão numérico com mesmo valor formatado.
            for ($i = 0; $i < 20; $i++) {
                $candidate = str_pad((string) ($next + $i), 5, '0', STR_PAD_LEFT);
                if (!$this->codigoExists($candidate)) {
                    return $candidate;
                }
            }
            return str_pad((string) (time() % 100000), 5, '0', STR_PAD_LEFT);
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'TiSistemaRepository::allocateNextCodigo', ['error' => $e->getMessage()]);
            return str_pad('1', 5, '0', STR_PAD_LEFT);
        }
    }

/**
     * Preview do próximo código (mesmo cálculo de allocate; não reserva no banco).
     */
    public function peekNextCodigo(): string
    {
        try {
            $stmt = $this->getConnection()->query(
                "SELECT MAX(CAST(codigo AS UNSIGNED)) FROM ti_sistemas
                 WHERE codigo IS NOT NULL AND codigo REGEXP '^[0-9]+$'"
            );
            $max = (int) ($stmt ? $stmt->fetchColumn() : 0);
            return str_pad((string) ($max + 1), 5, '0', STR_PAD_LEFT);
        } catch (PDOException $e) {
            return '00001';
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getAll(int $page = 1, int $limit = 20, string $filterNome = '', string $filterStatus = ''): array
    {
        $offset = max(0, ($page - 1) * $limit);
        $where = ['1=1'];
        $params = [];

        if ($filterNome !== '') {
            $where[] = '(s.nome LIKE :nome OR s.codigo LIKE :nome OR s.localizacao LIKE :nome'
                . ' OR s.equipamento_tag LIKE :nome OR s.fabricante LIKE :nome OR s.modelo LIKE :nome'
                . ' OR b.name LIKE :nome OR b.nome_fantasia LIKE :nome)';
            $params[':nome'] = '%' . $filterNome . '%';
        }
        if ($filterStatus !== '' && in_array($filterStatus, [self::STATUS_ATIVO, self::STATUS_INATIVO], true)) {
            $where[] = 's.status = :status';
            $params[':status'] = $filterStatus;
        }

        $sql = 'SELECT s.*,
                       COALESCE(NULLIF(b.nome_fantasia, \'\'), b.name) AS filial_nome,
                       (SELECT COUNT(*) FROM ti_acessos a
                         WHERE a.ti_sistema_id = s.id AND a.status = \'ativo\') AS acessos_ativos
                FROM ti_sistemas s
                LEFT JOIN adms_branches b ON b.id = s.adms_branch_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY s.nome ASC, s.equipamento_tag ASC
                LIMIT :limit OFFSET :offset';

        try {
            $stmt = $this->getConnection()->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v, PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'TiSistemaRepository::getAll', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function countAll(string $filterNome = '', string $filterStatus = ''): int
    {
        $where = ['1=1'];
        $params = [];
        if ($filterNome !== '') {
            $where[] = '(s.nome LIKE :nome OR s.codigo LIKE :nome OR s.localizacao LIKE :nome'
                . ' OR s.equipamento_tag LIKE :nome OR s.fabricante LIKE :nome OR s.modelo LIKE :nome'
                . ' OR b.name LIKE :nome OR b.nome_fantasia LIKE :nome)';
            $params[':nome'] = '%' . $filterNome . '%';
        }
        if ($filterStatus !== '' && in_array($filterStatus, [self::STATUS_ATIVO, self::STATUS_INATIVO], true)) {
            $where[] = 's.status = :status';
            $params[':status'] = $filterStatus;
        }
        try {
            $stmt = $this->getConnection()->prepare(
                'SELECT COUNT(*) FROM ti_sistemas s
                 LEFT JOIN adms_branches b ON b.id = s.adms_branch_id
                 WHERE ' . implode(' AND ', $where)
            );
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v, PDO::PARAM_STR);
            }
            $stmt->execute();
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'TiSistemaRepository::countAll', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        try {
            $stmt = $this->getConnection()->prepare(
                'SELECT s.*,
                        COALESCE(NULLIF(b.nome_fantasia, \'\'), b.name) AS filial_nome,
                        b.code AS filial_code
                 FROM ti_sistemas s
                 LEFT JOIN adms_branches b ON b.id = s.adms_branch_id
                 WHERE s.id = :id LIMIT 1'
            );
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'TiSistemaRepository::getById', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getAtivosSelect(): array
    {
        try {
            $stmt = $this->getConnection()->query(
                "SELECT s.id, s.nome, s.equipamento_tag, s.localizacao, s.tipo,
                        COALESCE(NULLIF(b.nome_fantasia, ''), b.name) AS filial_nome
                 FROM ti_sistemas s
                 LEFT JOIN adms_branches b ON b.id = s.adms_branch_id
                 WHERE s.status = 'ativo'
                 ORDER BY s.nome ASC, s.equipamento_tag ASC"
            );
            return $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'TiSistemaRepository::getAtivosSelect', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data, int $actorId): int|false
    {
        try {
            if (trim((string) ($data['codigo'] ?? '')) === '') {
                $data['codigo'] = $this->allocateNextCodigo();
            }
            $sql = 'INSERT INTO ti_sistemas
                        (codigo, nome, descricao, tipo, localizacao, equipamento_tag, fabricante, modelo,
                         numero_serie, adms_branch_id, observacoes, status, created_by_user_id, created_at)
                    VALUES
                        (:codigo, :nome, :descricao, :tipo, :localizacao, :equipamento_tag, :fabricante, :modelo,
                         :numero_serie, :adms_branch_id, :observacoes, :status, :created_by, NOW())';
            $stmt = $this->getConnection()->prepare($sql);
            $this->bindSistemaFields($stmt, $data);
            $stmt->bindValue(':created_by', $actorId > 0 ? $actorId : null, $actorId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->execute();
            $id = (int) $this->getConnection()->lastInsertId();
            if ($id > 0) {
                LogAlteracaoService::registrarAlteracao('ti_sistemas', $id, $actorId > 0 ? $actorId : 1, 'INSERT', [], $data);
            }
            return $id > 0 ? $id : false;
        } catch (Exception $e) {
            // Colisão rara de código único: tenta mais uma vez com novo número.
            if (str_contains($e->getMessage(), 'uq_ti_sistemas_codigo') || str_contains($e->getMessage(), 'Duplicate')) {
                try {
                    $data['codigo'] = $this->allocateNextCodigo();
                    $sql = 'INSERT INTO ti_sistemas
                                (codigo, nome, descricao, tipo, localizacao, equipamento_tag, fabricante, modelo,
                                 numero_serie, adms_branch_id, observacoes, status, created_by_user_id, created_at)
                            VALUES
                                (:codigo, :nome, :descricao, :tipo, :localizacao, :equipamento_tag, :fabricante, :modelo,
                                 :numero_serie, :adms_branch_id, :observacoes, :status, :created_by, NOW())';
                    $stmt = $this->getConnection()->prepare($sql);
                    $this->bindSistemaFields($stmt, $data);
                    $stmt->bindValue(':created_by', $actorId > 0 ? $actorId : null, $actorId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
                    $stmt->execute();
                    $id = (int) $this->getConnection()->lastInsertId();
                    if ($id > 0) {
                        LogAlteracaoService::registrarAlteracao('ti_sistemas', $id, $actorId > 0 ? $actorId : 1, 'INSERT', [], $data);
                        return $id;
                    }
                } catch (Exception $e2) {
                    GenerateLog::generateLog('error', 'TiSistemaRepository::create.retry', ['error' => $e2->getMessage()]);
                }
            }
            GenerateLog::generateLog('error', 'TiSistemaRepository::create', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data, int $actorId): bool
    {
        $antes = $this->getById($id);
        if ($antes === null) {
            return false;
        }
        try {
            $sql = 'UPDATE ti_sistemas SET
                        codigo = :codigo,
                        nome = :nome,
                        descricao = :descricao,
                        tipo = :tipo,
                        localizacao = :localizacao,
                        equipamento_tag = :equipamento_tag,
                        fabricante = :fabricante,
                        modelo = :modelo,
                        numero_serie = :numero_serie,
                        adms_branch_id = :adms_branch_id,
                        observacoes = :observacoes,
                        status = :status
                    WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $this->bindSistemaFields($stmt, $data);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $ok = $stmt->execute();
            if ($ok) {
                LogAlteracaoService::registrarAlteracao(
                    'ti_sistemas',
                    $id,
                    $actorId > 0 ? $actorId : 1,
                    'UPDATE',
                    $antes,
                    $data
                );
            }
            return $ok;
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'TiSistemaRepository::update', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function bindSistemaFields(\PDOStatement $stmt, array $data): void
    {
        $codigo = trim((string) ($data['codigo'] ?? ''));
        $stmt->bindValue(':codigo', $codigo !== '' ? $codigo : null, $codigo !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':nome', trim((string) ($data['nome'] ?? '')), PDO::PARAM_STR);
        $stmt->bindValue(':descricao', trim((string) ($data['descricao'] ?? '')) ?: null);
        $stmt->bindValue(':tipo', (string) ($data['tipo'] ?? 'outro'), PDO::PARAM_STR);
        $stmt->bindValue(':localizacao', trim((string) ($data['localizacao'] ?? '')) ?: null);

        $tag = trim((string) ($data['equipamento_tag'] ?? ''));
        $stmt->bindValue(':equipamento_tag', $tag !== '' ? $tag : null, $tag !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':fabricante', trim((string) ($data['fabricante'] ?? '')) ?: null);
        $stmt->bindValue(':modelo', trim((string) ($data['modelo'] ?? '')) ?: null);
        $stmt->bindValue(':numero_serie', trim((string) ($data['numero_serie'] ?? '')) ?: null);

        $branchId = (int) ($data['adms_branch_id'] ?? 0);
        $stmt->bindValue(
            ':adms_branch_id',
            $branchId > 0 ? $branchId : null,
            $branchId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL
        );

        $stmt->bindValue(':observacoes', trim((string) ($data['observacoes'] ?? '')) ?: null);
        $stmt->bindValue(':status', (string) ($data['status'] ?? self::STATUS_ATIVO), PDO::PARAM_STR);
    }
}
