<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use App\adms\Models\Services\TiRustdeskSecretService;
use Exception;
use PDO;
use PDOException;

class TiRustdeskRepository extends DbConnection
{
    public const STATUS_ATIVO = 'ativo';
    public const STATUS_INATIVO = 'inativo';

    /** @var list<string> */
    private const CARD_COLORS = [
        '#c27b7b', '#b89a6a', '#b8889a', '#9b7bb8', '#d4a07a',
        '#8fa8d4', '#8a8ab8', '#6a9aaa', '#7aa8d4', '#c49a7a',
        '#c07aa8', '#a888c4', '#6a8a9a', '#c48aa0',
    ];

    public static function cardColor(int $id): string
    {
        $i = $id > 0 ? ($id - 1) % count(self::CARD_COLORS) : 0;

        return self::CARD_COLORS[$i];
    }

    public static function normalizeId(string $raw): string
    {
        return preg_replace('/\D+/', '', $raw) ?? '';
    }

    public static function formatId(string $raw): string
    {
        $digits = self::normalizeId($raw);
        if ($digits === '') {
            return '';
        }

        return trim(chunk_split($digits, 3, ' '));
    }

    /**
     * @param array<string, mixed> $form
     * @return array{0: list<string>, 1: array<string, mixed>}
     */
    public function validateAndNormalize(array $form, ?int $excludeId = null): array
    {
        $errors = [];
        $alias = trim((string) ($form['alias'] ?? ''));
        if ($alias === '') {
            $errors[] = 'Informe o apelido (nome amigável da máquina).';
        }

        $rustdeskId = self::normalizeId((string) ($form['rustdesk_id'] ?? ''));
        if ($rustdeskId === '') {
            $errors[] = 'Informe o ID do RustDesk.';
        } elseif (strlen($rustdeskId) < 6 || strlen($rustdeskId) > 16) {
            $errors[] = 'O ID do RustDesk deve ter entre 6 e 16 dígitos.';
        } elseif ($this->rustdeskIdExists($rustdeskId, $excludeId)) {
            $errors[] = 'Já existe um cadastro com esse ID do RustDesk.';
        }

        $status = (string) ($form['status'] ?? self::STATUS_ATIVO);
        if (!in_array($status, [self::STATUS_ATIVO, self::STATUS_INATIVO], true)) {
            $errors[] = 'Status inválido.';
        }

        $userId = (int) ($form['adms_user_id'] ?? 0);
        if ($userId < 0) {
            $userId = 0;
        }

        $senha = (string) ($form['senha'] ?? '');
        $limparSenha = !empty($form['limpar_senha']);
        if ($senha !== '' && !TiRustdeskSecretService::isConfigured()) {
            $errors[] = 'Não é possível gravar a senha: defina TI_RUSTDESK_ENCRYPTION_KEY no .env (mín. 32 caracteres).';
        }

        $data = [
            'alias' => $alias,
            'rustdesk_id' => $rustdeskId,
            'adms_user_id' => $userId > 0 ? $userId : null,
            'observacoes' => trim((string) ($form['observacoes'] ?? '')) ?: null,
            'status' => $status,
            'senha' => $senha,
            'limpar_senha' => $limparSenha,
        ];

        return [$errors, $data];
    }

    public function rustdeskIdExists(string $rustdeskId, ?int $excludeId = null): bool
    {
        $rustdeskId = self::normalizeId($rustdeskId);
        if ($rustdeskId === '') {
            return false;
        }
        try {
            $sql = 'SELECT id FROM ti_rustdesk WHERE rustdesk_id = :rid';
            if ($excludeId !== null && $excludeId > 0) {
                $sql .= ' AND id <> :id';
            }
            $sql .= ' LIMIT 1';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':rid', $rustdeskId, PDO::PARAM_STR);
            if ($excludeId !== null && $excludeId > 0) {
                $stmt->bindValue(':id', $excludeId, PDO::PARAM_INT);
            }
            $stmt->execute();

            return (bool) $stmt->fetchColumn();
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'TiRustdeskRepository::rustdeskIdExists', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getByRustdeskId(string $rustdeskId): ?array
    {
        $rustdeskId = self::normalizeId($rustdeskId);
        if ($rustdeskId === '') {
            return null;
        }
        try {
            $stmt = $this->getConnection()->prepare(
                'SELECT id FROM ti_rustdesk WHERE rustdesk_id = :rid LIMIT 1'
            );
            $stmt->bindValue(':rid', $rustdeskId, PDO::PARAM_STR);
            $stmt->execute();
            $id = (int) $stmt->fetchColumn();

            return $id > 0 ? $this->getById($id) : null;
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'TiRustdeskRepository::getByRustdeskId', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getAll(int $page = 1, int $limit = 24, string $filter = '', string $filterStatus = ''): array
    {
        $offset = max(0, ($page - 1) * $limit);
        [$whereSql, $params] = $this->buildWhere($filter, $filterStatus);

        $sql = 'SELECT r.id, r.alias, r.rustdesk_id, r.status, r.adms_user_id, r.observacoes,
                       r.created_at, r.updated_at,
                       (r.senha_encriptada IS NOT NULL AND r.senha_encriptada <> \'\') AS has_senha,
                       u.name AS colaborador_nome, u.email AS colaborador_email
                FROM ti_rustdesk r
                LEFT JOIN adms_users u ON u.id = r.adms_user_id
                WHERE ' . $whereSql . '
                ORDER BY r.alias ASC
                LIMIT :limit OFFSET :offset';

        try {
            $stmt = $this->getConnection()->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v, PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($rows as &$row) {
                $row['has_senha'] = (int) ($row['has_senha'] ?? 0) === 1;
                $row['rustdesk_id_fmt'] = self::formatId((string) ($row['rustdesk_id'] ?? ''));
                $row['card_color'] = self::cardColor((int) ($row['id'] ?? 0));
            }
            unset($row);

            return $rows;
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'TiRustdeskRepository::getAll', ['error' => $e->getMessage()]);

            return [];
        }
    }

    public function countAll(string $filter = '', string $filterStatus = ''): int
    {
        [$whereSql, $params] = $this->buildWhere($filter, $filterStatus);
        try {
            $stmt = $this->getConnection()->prepare(
                'SELECT COUNT(*) FROM ti_rustdesk r
                 LEFT JOIN adms_users u ON u.id = r.adms_user_id
                 WHERE ' . $whereSql
            );
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v, PDO::PARAM_STR);
            }
            $stmt->execute();

            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'TiRustdeskRepository::countAll', ['error' => $e->getMessage()]);

            return 0;
        }
    }

    /**
     * Ficha sem o ciphertext (nunca enviar senha_encriptada à view).
     *
     * @return array<string, mixed>|null
     */
    public function getById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        try {
            $stmt = $this->getConnection()->prepare(
                'SELECT r.id, r.alias, r.rustdesk_id, r.status, r.adms_user_id, r.observacoes,
                        r.created_by_user_id, r.updated_by_user_id, r.created_at, r.updated_at,
                        (r.senha_encriptada IS NOT NULL AND r.senha_encriptada <> \'\') AS has_senha,
                        u.name AS colaborador_nome, u.email AS colaborador_email
                 FROM ti_rustdesk r
                 LEFT JOIN adms_users u ON u.id = r.adms_user_id
                 WHERE r.id = :id LIMIT 1'
            );
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return null;
            }
            $row['has_senha'] = (int) ($row['has_senha'] ?? 0) === 1;
            $row['rustdesk_id_fmt'] = self::formatId((string) ($row['rustdesk_id'] ?? ''));
            $row['card_color'] = self::cardColor((int) $row['id']);

            return $row;
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'TiRustdeskRepository::getById', ['error' => $e->getMessage()]);

            return null;
        }
    }

    public function getEncryptedPassword(int $id): ?string
    {
        if ($id <= 0) {
            return null;
        }
        try {
            $stmt = $this->getConnection()->prepare(
                'SELECT senha_encriptada FROM ti_rustdesk WHERE id = :id LIMIT 1'
            );
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $val = $stmt->fetchColumn();
            if ($val === false || $val === null || $val === '') {
                return null;
            }

            return (string) $val;
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'TiRustdeskRepository::getEncryptedPassword', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data, int $actorId): int|false
    {
        try {
            $enc = null;
            if (($data['senha'] ?? '') !== '') {
                $enc = TiRustdeskSecretService::encrypt((string) $data['senha']);
            }

            $sql = 'INSERT INTO ti_rustdesk
                        (alias, rustdesk_id, senha_encriptada, adms_user_id, observacoes, status,
                         created_by_user_id, updated_by_user_id, created_at)
                    VALUES
                        (:alias, :rustdesk_id, :senha_encriptada, :adms_user_id, :observacoes, :status,
                         :created_by, :updated_by, NOW())';
            $stmt = $this->getConnection()->prepare($sql);
            $this->bindFields($stmt, $data, $enc);
            $stmt->bindValue(':created_by', $actorId > 0 ? $actorId : null, $actorId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':updated_by', $actorId > 0 ? $actorId : null, $actorId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->execute();
            $id = (int) $this->getConnection()->lastInsertId();
            if ($id > 0) {
                LogAlteracaoService::registrarAlteracao(
                    'ti_rustdesk',
                    $id,
                    $actorId > 0 ? $actorId : 1,
                    'INSERT',
                    [],
                    $this->auditSafe($data, $enc !== null)
                );
            }

            return $id > 0 ? $id : false;
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'TiRustdeskRepository::create', ['error' => $e->getMessage()]);

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
            $sets = 'alias = :alias, rustdesk_id = :rustdesk_id, adms_user_id = :adms_user_id,
                     observacoes = :observacoes, status = :status, updated_by_user_id = :updated_by';
            $enc = null;
            $senhaMudou = false;
            if (!empty($data['limpar_senha'])) {
                $sets .= ', senha_encriptada = NULL';
                $senhaMudou = true;
            } elseif (($data['senha'] ?? '') !== '') {
                $enc = TiRustdeskSecretService::encrypt((string) $data['senha']);
                $sets .= ', senha_encriptada = :senha_encriptada';
                $senhaMudou = true;
            }

            $sql = "UPDATE ti_rustdesk SET {$sets} WHERE id = :id";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':alias', (string) $data['alias'], PDO::PARAM_STR);
            $stmt->bindValue(':rustdesk_id', (string) $data['rustdesk_id'], PDO::PARAM_STR);
            $userId = (int) ($data['adms_user_id'] ?? 0);
            $stmt->bindValue(':adms_user_id', $userId > 0 ? $userId : null, $userId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $obs = $data['observacoes'] ?? null;
            $stmt->bindValue(':observacoes', $obs, $obs === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':status', (string) $data['status'], PDO::PARAM_STR);
            $stmt->bindValue(':updated_by', $actorId > 0 ? $actorId : null, $actorId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
            if ($enc !== null) {
                $stmt->bindValue(':senha_encriptada', $enc, PDO::PARAM_STR);
            }
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $ok = $stmt->execute();
            if ($ok) {
                $depois = $this->auditSafe($data, $senhaMudou ? ($enc !== null) : (bool) ($antes['has_senha'] ?? false));
                LogAlteracaoService::registrarAlteracao(
                    'ti_rustdesk',
                    $id,
                    $actorId > 0 ? $actorId : 1,
                    'UPDATE',
                    $this->auditSafe($antes, (bool) ($antes['has_senha'] ?? false)),
                    $depois
                );
            }

            return $ok;
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'TiRustdeskRepository::update', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * @return list<array{id:int,name:string,email:string}>
     */
    public function getUsersSelect(): array
    {
        try {
            $stmt = $this->getConnection()->query(
                "SELECT id, name, email FROM adms_users WHERE status = 'Ativo' ORDER BY name ASC"
            );

            return $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'TiRustdeskRepository::getUsersSelect', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * @return array{0: string, 1: array<string, string>}
     */
    private function buildWhere(string $filter, string $filterStatus): array
    {
        $where = ['1=1'];
        $params = [];
        if ($filter !== '') {
            $where[] = '(r.alias LIKE :q OR r.rustdesk_id LIKE :q OR u.name LIKE :q OR u.email LIKE :q)';
            $params[':q'] = '%' . $filter . '%';
        }
        if ($filterStatus !== '' && in_array($filterStatus, [self::STATUS_ATIVO, self::STATUS_INATIVO], true)) {
            $where[] = 'r.status = :status';
            $params[':status'] = $filterStatus;
        }

        return [implode(' AND ', $where), $params];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function bindFields(\PDOStatement $stmt, array $data, ?string $enc): void
    {
        $stmt->bindValue(':alias', (string) $data['alias'], PDO::PARAM_STR);
        $stmt->bindValue(':rustdesk_id', (string) $data['rustdesk_id'], PDO::PARAM_STR);
        $stmt->bindValue(':senha_encriptada', $enc, $enc === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $userId = (int) ($data['adms_user_id'] ?? 0);
        $stmt->bindValue(':adms_user_id', $userId > 0 ? $userId : null, $userId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $obs = $data['observacoes'] ?? null;
        $stmt->bindValue(':observacoes', $obs, $obs === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':status', (string) $data['status'], PDO::PARAM_STR);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function auditSafe(array $data, bool $hasSenha): array
    {
        $out = $data;
        unset($out['senha'], $out['senha_encriptada'], $out['limpar_senha']);
        $out['senha'] = $hasSenha ? '[definida]' : '[vazia]';

        return $out;
    }
}
