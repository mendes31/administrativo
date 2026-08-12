<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;
use PDOException;

/**
 * Configuração do portal público LGPD (DPO, empresa, documentos e comitê).
 */
class LgpdPortalConfigRepository extends DbConnection
{
    private static ?array $cachedConfig = null;

    /** @var list<array<string, mixed>>|null */
    private static ?array $cachedComite = null;

    public static function clearCache(): void
    {
        self::$cachedConfig = null;
        self::$cachedComite = null;
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        if (self::$cachedConfig !== null) {
            return self::$cachedConfig;
        }

        try {
            if (!$this->tableExists('lgpd_portal_config')) {
                self::$cachedConfig = [];

                return [];
            }

            $stmt = $this->getConnection()->query(
                'SELECT * FROM lgpd_portal_config ORDER BY id ASC LIMIT 1'
            );
            $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
            self::$cachedConfig = is_array($row) ? $row : [];
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao ler lgpd_portal_config.', [
                'error' => $e->getMessage(),
            ]);
            self::$cachedConfig = [];
        }

        return self::$cachedConfig;
    }

    /**
     * Valor efetivo: banco tem prioridade; .env é fallback legado.
     */
    public function getEffective(string $field, string $envKey = '', string $default = ''): string
    {
        $row = $this->getConfig();
        $fromDb = trim((string) ($row[$field] ?? ''));
        if ($fromDb !== '') {
            return $fromDb;
        }

        if ($envKey !== '') {
            $fromEnv = trim((string) ($_ENV[$envKey] ?? ''));
            if ($fromEnv !== '') {
                return $fromEnv;
            }
        }

        return $default;
    }

    public function empresaNome(): string
    {
        $name = $this->getEffective('empresa_nome', 'LGPD_EMPRESA_NOME');
        if ($name !== '') {
            return $name;
        }

        return trim((string) ($_ENV['APP_NAME'] ?? 'Tiaraju')) ?: 'Tiaraju';
    }

    public function dpoNome(): string
    {
        return $this->getEffective('dpo_nome', 'LGPD_DPO_NOME');
    }

    public function dpoEmail(): string
    {
        return $this->getEffective('dpo_email', 'LGPD_DPO_EMAIL');
    }

    public function dpoTelefone(): string
    {
        return $this->getEffective('dpo_telefone', 'LGPD_DPO_TELEFONE');
    }

    public function cartilhaPath(): string
    {
        return $this->getEffective(
            'cartilha_path',
            'LGPD_CARTILHA_PATH',
            'storage/lgpd/publico/cartilha.pdf'
        );
    }

    public function cartaCompromissoPath(): string
    {
        return $this->getEffective(
            'carta_compromisso_path',
            'LGPD_CARTA_COMPROMISSO_PATH',
            'storage/lgpd/publico/carta-compromisso.pdf'
        );
    }

    public function comiteTitulo(): string
    {
        $t = trim((string) ($this->getConfig()['comite_titulo'] ?? ''));

        return $t !== '' ? $t : 'Comitê de Privacidade e Proteção de Dados';
    }

    public function comiteDescricao(): string
    {
        return trim((string) ($this->getConfig()['comite_descricao'] ?? ''));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getComiteMembros(bool $onlyActive = false): array
    {
        if (!$onlyActive && self::$cachedComite !== null) {
            return self::$cachedComite;
        }

        try {
            if (!$this->tableExists('lgpd_comite_membros')) {
                if (!$onlyActive) {
                    self::$cachedComite = [];
                }

                return [];
            }

            $sql = 'SELECT * FROM lgpd_comite_membros';
            if ($onlyActive) {
                $sql .= ' WHERE ativo = 1';
            }
            $sql .= ' ORDER BY ordem ASC, nome ASC';

            $stmt = $this->getConnection()->query($sql);
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

            if (!$onlyActive) {
                self::$cachedComite = is_array($rows) ? $rows : [];
            }

            return is_array($rows) ? $rows : [];
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao ler lgpd_comite_membros.', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function saveConfig(array $data): bool
    {
        if (!$this->tableExists('lgpd_portal_config')) {
            return false;
        }

        $payload = [
            'empresa_nome' => $this->nullIfEmpty(trim((string) ($data['empresa_nome'] ?? ''))),
            'dpo_nome' => $this->nullIfEmpty(trim((string) ($data['dpo_nome'] ?? ''))),
            'dpo_email' => $this->nullIfEmpty(trim((string) ($data['dpo_email'] ?? ''))),
            'dpo_telefone' => $this->nullIfEmpty(trim((string) ($data['dpo_telefone'] ?? ''))),
            'cartilha_path' => $this->nullIfEmpty(trim((string) ($data['cartilha_path'] ?? ''))),
            'carta_compromisso_path' => $this->nullIfEmpty(trim((string) ($data['carta_compromisso_path'] ?? ''))),
            'comite_titulo' => $this->nullIfEmpty(trim((string) ($data['comite_titulo'] ?? ''))),
            'comite_descricao' => $this->nullIfEmpty(trim((string) ($data['comite_descricao'] ?? ''))),
        ];

        if ($payload['dpo_email'] !== null && !filter_var((string) $payload['dpo_email'], FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        try {
            $pdo = $this->getConnection();
            $existing = $this->getConfig();
            $before = $existing;

            if ($existing === []) {
                $stmt = $pdo->prepare(
                    'INSERT INTO lgpd_portal_config
                        (empresa_nome, dpo_nome, dpo_email, dpo_telefone, cartilha_path,
                         carta_compromisso_path, comite_titulo, comite_descricao, created_at, updated_at)
                     VALUES
                        (:empresa_nome, :dpo_nome, :dpo_email, :dpo_telefone, :cartilha_path,
                         :carta_compromisso_path, :comite_titulo, :comite_descricao, NOW(), NOW())'
                );
            } else {
                $stmt = $pdo->prepare(
                    'UPDATE lgpd_portal_config SET
                        empresa_nome = :empresa_nome,
                        dpo_nome = :dpo_nome,
                        dpo_email = :dpo_email,
                        dpo_telefone = :dpo_telefone,
                        cartilha_path = :cartilha_path,
                        carta_compromisso_path = :carta_compromisso_path,
                        comite_titulo = :comite_titulo,
                        comite_descricao = :comite_descricao,
                        updated_at = NOW()
                     WHERE id = :id'
                );
                $stmt->bindValue(':id', (int) $existing['id'], PDO::PARAM_INT);
            }

            foreach ($payload as $key => $value) {
                if ($value === null) {
                    $stmt->bindValue(':' . $key, null, PDO::PARAM_NULL);
                } else {
                    $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
                }
            }

            $ok = $stmt->execute();
            if ($ok) {
                self::clearCache();
                $after = $this->getConfig();
                $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'lgpd_portal_config',
                    (int) ($after['id'] ?? $existing['id'] ?? 1),
                    $usuarioId,
                    $existing === [] ? 'INSERT' : 'UPDATE',
                    $before,
                    $after
                );
            }

            return $ok;
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao salvar lgpd_portal_config.', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function saveComiteMembro(array $data): bool
    {
        if (!$this->tableExists('lgpd_comite_membros')) {
            return false;
        }

        $nome = trim((string) ($data['nome'] ?? ''));
        if ($nome === '') {
            return false;
        }

        $email = trim((string) ($data['email'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $id = (int) ($data['id'] ?? 0);
        $cargo = $this->nullIfEmpty(trim((string) ($data['cargo'] ?? '')));
        $telefone = $this->nullIfEmpty(trim((string) ($data['telefone'] ?? '')));
        $ordem = max(0, (int) ($data['ordem'] ?? 0));
        $ativo = !empty($data['ativo']) ? 1 : 0;

        try {
            $pdo = $this->getConnection();
            $before = $id > 0 ? $this->getComiteMembroById($id) : [];

            if ($id > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE lgpd_comite_membros SET
                        nome = :nome,
                        cargo = :cargo,
                        email = :email,
                        telefone = :telefone,
                        ordem = :ordem,
                        ativo = :ativo,
                        updated_at = NOW()
                     WHERE id = :id'
                );
                $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO lgpd_comite_membros
                        (nome, cargo, email, telefone, ordem, ativo, created_at, updated_at)
                     VALUES
                        (:nome, :cargo, :email, :telefone, :ordem, :ativo, NOW(), NOW())'
                );
            }

            $stmt->bindValue(':nome', $nome, PDO::PARAM_STR);
            $this->bindNullable($stmt, ':cargo', $cargo);
            $this->bindNullable($stmt, ':email', $email !== '' ? $email : null);
            $this->bindNullable($stmt, ':telefone', $telefone);
            $stmt->bindValue(':ordem', $ordem, PDO::PARAM_INT);
            $stmt->bindValue(':ativo', $ativo, PDO::PARAM_INT);

            $ok = $stmt->execute();
            if ($ok) {
                self::clearCache();
                $newId = $id > 0 ? $id : (int) $pdo->lastInsertId();
                $after = $this->getComiteMembroById($newId);
                $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'lgpd_comite_membros',
                    $newId,
                    $usuarioId,
                    $id > 0 ? 'UPDATE' : 'INSERT',
                    $before,
                    $after
                );
            }

            return $ok;
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao salvar lgpd_comite_membros.', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function deleteComiteMembro(int $id): bool
    {
        if ($id <= 0 || !$this->tableExists('lgpd_comite_membros')) {
            return false;
        }

        try {
            $before = $this->getComiteMembroById($id);
            if ($before === []) {
                return false;
            }

            $stmt = $this->getConnection()->prepare('DELETE FROM lgpd_comite_membros WHERE id = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $ok = $stmt->execute();

            if ($ok) {
                self::clearCache();
                $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'lgpd_comite_membros',
                    $id,
                    $usuarioId,
                    'DELETE',
                    $before,
                    []
                );
            }

            return $ok;
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao excluir lgpd_comite_membros.', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getComiteMembroById(int $id): array
    {
        if ($id <= 0 || !$this->tableExists('lgpd_comite_membros')) {
            return [];
        }

        try {
            $stmt = $this->getConnection()->prepare('SELECT * FROM lgpd_comite_membros WHERE id = :id LIMIT 1');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return is_array($row) ? $row : [];
        } catch (PDOException) {
            return [];
        }
    }

    private function nullIfEmpty(string $value): ?string
    {
        return $value === '' ? null : $value;
    }

    private function bindNullable(\PDOStatement $stmt, string $param, ?string $value): void
    {
        if ($value === null) {
            $stmt->bindValue($param, null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue($param, $value, PDO::PARAM_STR);
        }
    }

    private function tableExists(string $table): bool
    {
        try {
            $stmt = $this->getConnection()->query('SHOW TABLES LIKE ' . $this->getConnection()->quote($table));

            return (bool) $stmt->fetch(PDO::FETCH_NUM);
        } catch (PDOException) {
            return false;
        }
    }
}
