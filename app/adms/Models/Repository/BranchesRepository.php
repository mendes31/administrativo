<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use Exception;
use PDO;

class BranchesRepository extends DbConnection
{
    private const SELECT_COLS = 'id, name, code, cnpj, establishment_type, razao_social, nome_fantasia,
        data_abertura, porte, cnae_principal, natureza_juridica,
        logradouro, numero, complemento, cep, bairro, municipio, uf, situacao_cadastral,
        address, phone, email, active';

    /**
     * @param array<string, mixed> $filtros
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildFilterClause(array $filtros = []): array
    {
        $where = [];
        $params = [];

        if (($filtros['name'] ?? '') !== '') {
            $where[] = '(name LIKE :name OR nome_fantasia LIKE :name OR razao_social LIKE :name OR municipio LIKE :name)';
            $params[':name'] = '%' . $filtros['name'] . '%';
        }
        if (($filtros['code'] ?? '') !== '') {
            $where[] = 'code LIKE :code';
            $params[':code'] = '%' . $filtros['code'] . '%';
        }
        if (($filtros['email'] ?? '') !== '') {
            $where[] = 'email LIKE :email';
            $params[':email'] = '%' . $filtros['email'] . '%';
        }
        if (($filtros['cnpj'] ?? '') !== '') {
            $digits = preg_replace('/\D+/', '', (string) $filtros['cnpj']) ?? '';
            if ($digits !== '') {
                $where[] = 'cnpj LIKE :cnpj';
                $params[':cnpj'] = '%' . $digits . '%';
            }
        }
        if (($filtros['establishment_type'] ?? '') !== '') {
            $where[] = 'establishment_type = :establishment_type';
            $params[':establishment_type'] = $filtros['establishment_type'];
        }
        if (($filtros['active'] ?? '') !== '') {
            $where[] = 'active = :active';
            $params[':active'] = (int) $filtros['active'];
        }

        $sql = $where === [] ? '' : (' WHERE ' . implode(' AND ', $where));

        return [$sql, $params];
    }

    /**
     * @param array<string, mixed> $filtros
     */
    public function getAllBranches(int $page = 1, int $limitResult = 10, array $filtros = []): array
    {
        $offset = max(0, ($page - 1) * $limitResult);
        [$where, $params] = $this->buildFilterClause($filtros);
        $sql = 'SELECT ' . self::SELECT_COLS . ' FROM adms_branches' . $where
            . ' ORDER BY establishment_type ASC, name ASC LIMIT :limit OFFSET :offset';
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $type);
        }
        $stmt->bindValue(':limit', $limitResult, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param array<string, mixed> $filtros
     */
    public function getAmountBranches(array $filtros = []): int
    {
        [$where, $params] = $this->buildFilterClause($filtros);
        $sql = 'SELECT COUNT(id) as amount_records FROM adms_branches' . $where;
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $type);
        }
        $stmt->execute();

        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['amount_records'] ?? 0);
    }

    public function getBranch(int $id): array|bool
    {
        $sql = 'SELECT ' . self::SELECT_COLS . ', created_at, updated_at FROM adms_branches WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function cnpjExists(string $cnpjDigits, ?int $excludeId = null): bool
    {
        $sql = 'SELECT id FROM adms_branches WHERE cnpj = :cnpj';
        if ($excludeId !== null) {
            $sql .= ' AND id <> :id';
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':cnpj', $cnpjDigits, PDO::PARAM_STR);
        if ($excludeId !== null) {
            $stmt->bindValue(':id', $excludeId, PDO::PARAM_INT);
        }
        $stmt->execute();

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createBranch(array $data): bool|int
    {
        try {
            $sql = 'INSERT INTO adms_branches (
                        name, code, cnpj, establishment_type, razao_social, nome_fantasia,
                        data_abertura, porte, cnae_principal, natureza_juridica,
                        logradouro, numero, complemento, cep, bairro, municipio, uf, situacao_cadastral,
                        address, phone, email, active, created_at
                    ) VALUES (
                        :name, :code, :cnpj, :establishment_type, :razao_social, :nome_fantasia,
                        :data_abertura, :porte, :cnae_principal, :natureza_juridica,
                        :logradouro, :numero, :complemento, :cep, :bairro, :municipio, :uf, :situacao_cadastral,
                        :address, :phone, :email, :active, :created_at
                    )';
            $stmt = $this->getConnection()->prepare($sql);
            $this->bindBranchFields($stmt, $data);
            $stmt->bindValue(':created_at', date('Y-m-d H:i:s'));
            $stmt->execute();

            $branchId = $this->getConnection()->lastInsertId();

            if ($branchId) {
                $usuarioId = $_SESSION['user_id'] ?? 1;
                LogAlteracaoService::registrarAlteracao(
                    'adms_branches',
                    $branchId,
                    $usuarioId,
                    'INSERT',
                    [],
                    $data
                );
            }

            return $branchId;
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Filial não cadastrada.', ['name' => $data['name'] ?? '', 'error' => $e->getMessage()]);

            return false;
        }
    }

    public function updateBranch(array $data): bool
    {
        try {
            $oldData = $this->getBranch((int) $data['id']);
            $sql = 'UPDATE adms_branches SET
                        name = :name,
                        code = :code,
                        cnpj = :cnpj,
                        establishment_type = :establishment_type,
                        razao_social = :razao_social,
                        nome_fantasia = :nome_fantasia,
                        data_abertura = :data_abertura,
                        porte = :porte,
                        cnae_principal = :cnae_principal,
                        natureza_juridica = :natureza_juridica,
                        logradouro = :logradouro,
                        numero = :numero,
                        complemento = :complemento,
                        cep = :cep,
                        bairro = :bairro,
                        municipio = :municipio,
                        uf = :uf,
                        situacao_cadastral = :situacao_cadastral,
                        address = :address,
                        phone = :phone,
                        email = :email,
                        active = :active,
                        updated_at = :updated_at
                    WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $this->bindBranchFields($stmt, $data);
            $stmt->bindValue(':updated_at', date('Y-m-d H:i:s'));
            $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);
            $result = $stmt->execute();

            if ($result && $oldData) {
                $usuarioId = $_SESSION['user_id'] ?? 1;
                LogAlteracaoService::registrarAlteracao(
                    'adms_branches',
                    $data['id'],
                    $usuarioId,
                    'UPDATE',
                    $oldData,
                    $data
                );
            }

            return $result;
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Filial não editada.', ['id' => $data['id'] ?? null, 'error' => $e->getMessage()]);

            return false;
        }
    }

    /** @param array<string, mixed> $data */
    private function bindBranchFields(\PDOStatement $stmt, array $data): void
    {
        $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
        $stmt->bindValue(':code', $data['code'], PDO::PARAM_STR);
        $this->bindNullableString($stmt, ':cnpj', $data['cnpj'] ?? null);
        $stmt->bindValue(':establishment_type', $data['establishment_type'] ?? 'filial', PDO::PARAM_STR);
        $this->bindNullableString($stmt, ':razao_social', $data['razao_social'] ?? null);
        $this->bindNullableString($stmt, ':nome_fantasia', $data['nome_fantasia'] ?? null);
        $this->bindNullableString($stmt, ':data_abertura', $data['data_abertura'] ?? null);
        $this->bindNullableString($stmt, ':porte', $data['porte'] ?? null);
        $this->bindNullableString($stmt, ':cnae_principal', $data['cnae_principal'] ?? null);
        $this->bindNullableString($stmt, ':natureza_juridica', $data['natureza_juridica'] ?? null);
        $this->bindNullableString($stmt, ':logradouro', $data['logradouro'] ?? null);
        $this->bindNullableString($stmt, ':numero', $data['numero'] ?? null);
        $this->bindNullableString($stmt, ':complemento', $data['complemento'] ?? null);
        $this->bindNullableString($stmt, ':cep', $data['cep'] ?? null);
        $this->bindNullableString($stmt, ':bairro', $data['bairro'] ?? null);
        $this->bindNullableString($stmt, ':municipio', $data['municipio'] ?? null);
        $this->bindNullableString($stmt, ':uf', $data['uf'] ?? null);
        $this->bindNullableString($stmt, ':situacao_cadastral', $data['situacao_cadastral'] ?? null);
        $stmt->bindValue(':address', $data['address'] ?? '', PDO::PARAM_STR);
        $stmt->bindValue(':phone', $data['phone'] ?? '', PDO::PARAM_STR);
        $stmt->bindValue(':email', $data['email'] ?? '', PDO::PARAM_STR);
        $stmt->bindValue(':active', (int) ($data['active'] ?? 1), PDO::PARAM_INT);
    }

    private function bindNullableString(\PDOStatement $stmt, string $param, mixed $value): void
    {
        if ($value === null || $value === '') {
            $stmt->bindValue($param, null, PDO::PARAM_NULL);
            return;
        }
        $stmt->bindValue($param, (string) $value, PDO::PARAM_STR);
    }

    public function deleteBranch(int $id): bool
    {
        try {
            $oldData = $this->getBranch($id);
            $sql = 'DELETE FROM adms_branches WHERE id = :id LIMIT 1';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $affectedRows = $stmt->rowCount();

            if ($affectedRows > 0) {
                if ($oldData) {
                    $usuarioId = $_SESSION['user_id'] ?? 1;
                    LogAlteracaoService::registrarAlteracao(
                        'adms_branches',
                        $id,
                        $usuarioId,
                        'DELETE',
                        $oldData,
                        []
                    );
                }

                return true;
            }

            GenerateLog::generateLog('error', 'Filial não apagada.', ['id' => $id]);

            return false;
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Filial não apagada.', ['id' => $id, 'error' => $e->getMessage()]);

            return false;
        }
    }

    public function getAllBranchesSelect(): array
    {
        $sql = 'SELECT id, name, code, establishment_type, nome_fantasia, cnpj, razao_social
                FROM adms_branches WHERE active = 1 ORDER BY establishment_type ASC, name ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Opções de empresa contratante a partir das filiais cujo code = slug conhecido.
     *
     * @return array<string, string> slug => nome fantasia (ou name)
     */
    public function getEmpresaContratanteOptionsFromBranches(): array
    {
        $sql = "SELECT code, nome_fantasia, name
                FROM adms_branches
                WHERE active = 1
                  AND code IN ('tiaraju_farma', 'lab_tiaraju_matriz', 'lab_tiaraju_filial')
                ORDER BY FIELD(code, 'tiaraju_farma', 'lab_tiaraju_matriz', 'lab_tiaraju_filial')";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $slug = (string) ($row['code'] ?? '');
            if ($slug === '') {
                continue;
            }
            $label = trim((string) ($row['nome_fantasia'] ?: $row['name'] ?: $slug));
            $out[$slug] = $label !== '' ? $label : $slug;
        }

        return $out;
    }

    public function getBranchIdByCode(string $code): ?int
    {
        $code = trim($code);
        if ($code === '') {
            return null;
        }
        $sql = 'SELECT id FROM adms_branches WHERE code = :code AND active = 1 LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':code', $code, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? (int) $row['id'] : null;
    }
}
