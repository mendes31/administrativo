<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;
use Exception;

class LgpdTermosRepository extends DbConnection
{
    /**
     * Buscar termo ativo mais recente por tipo (ex.: 'login').
     */
    public function getTermoAtivoPorTipo(string $tipo): ?array
    {
        try {
            $sql = "SELECT *
                    FROM lgpd_termos
                    WHERE tipo = :tipo
                      AND status = 'Ativo'
                      AND data_inicio_vigencia <= NOW()
                      AND (data_fim_vigencia IS NULL OR data_fim_vigencia >= NOW())
                    ORDER BY data_inicio_vigencia DESC, id DESC
                    LIMIT 1";

            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':tipo', $tipo, PDO::PARAM_STR);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (Exception $e) {
            error_log('Erro ao buscar termo LGPD por tipo: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Listar termos com paginação simples (para tela administrativa).
     */
    public function getAll(int $page = 1, int $perPage = 10, array $filters = []): array
    {
        $offset = max(0, ($page - 1) * $perPage);

        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = '(titulo LIKE :search OR versao LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['tipo'])) {
            $where[] = 'tipo = :tipo';
            $params[':tipo'] = $filters['tipo'];
        }

        if (!empty($filters['status'])) {
            $where[] = 'status = :status';
            $params[':status'] = $filters['status'];
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT *
                FROM lgpd_termos
                {$whereSql}
                ORDER BY created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->getConnection()->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }

        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAmount(array $filters = []): int
    {
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = '(titulo LIKE :search OR versao LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['tipo'])) {
            $where[] = 'tipo = :tipo';
            $params[':tipo'] = $filters['tipo'];
        }

        if (!empty($filters['status'])) {
            $where[] = 'status = :status';
            $params[':status'] = $filters['status'];
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT COUNT(*) AS total FROM lgpd_termos {$whereSql}";
        $stmt = $this->getConnection()->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }

        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT * FROM lgpd_termos WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Buscar o último termo ativo (independente do tipo).
     * Usado como fallback para o consentimento de login.
     */
    public function getLastActiveTerm(): ?array
    {
        try {
            $sql = "SELECT *
                    FROM lgpd_termos
                    WHERE status = 'Ativo'
                    ORDER BY data_inicio_vigencia DESC, id DESC
                    LIMIT 1";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (Exception $e) {
            error_log('Erro ao buscar último termo LGPD ativo: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Buscar todos os termos ativos (para select em formulários).
     */
    public function getAllActiveTerms(): array
    {
        try {
            $sql = "SELECT id, versao, titulo, tipo
                    FROM lgpd_termos
                    WHERE status = 'Ativo'
                      AND data_inicio_vigencia <= NOW()
                      AND (data_fim_vigencia IS NULL OR data_fim_vigencia >= NOW())
                    ORDER BY titulo ASC";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Erro ao buscar todos os termos LGPD ativos: ' . $e->getMessage());
            return [];
        }
    }

    public function create(array $data): bool|int
    {
        // Gerar identificador lógico do documento se não vier do formulário
        $documentoCodigo = $data['documento_codigo'] ?? null;
        if (empty($documentoCodigo)) {
            $documentoCodigo = $this->generateDocumentoCodigo($data['tipo'] ?? 'geral', $data['titulo'] ?? '');
        }

        $sql = "INSERT INTO lgpd_termos 
                    (versao, titulo, tipo, documento_codigo, conteudo, data_inicio_vigencia, data_fim_vigencia, status, created_at) 
                VALUES 
                    (:versao, :titulo, :tipo, :documento_codigo, :conteudo, :data_inicio_vigencia, :data_fim_vigencia, :status, NOW())";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':versao', $data['versao'], PDO::PARAM_STR);
        $stmt->bindValue(':titulo', $data['titulo'], PDO::PARAM_STR);
        $stmt->bindValue(':tipo', $data['tipo'], PDO::PARAM_STR);
        $stmt->bindValue(':documento_codigo', $documentoCodigo, PDO::PARAM_STR);
        $stmt->bindValue(':conteudo', $data['conteudo'], PDO::PARAM_STR);
        $stmt->bindValue(':data_inicio_vigencia', $data['data_inicio_vigencia'], PDO::PARAM_STR);
        $stmt->bindValue(':data_fim_vigencia', $data['data_fim_vigencia'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':status', $data['status'] ?? 'Ativo', PDO::PARAM_STR);

        $ok = $stmt->execute();

        if (!$ok) {
            return false;
        }

        // Retornar ID e registrar log de alterações, se possível
        try {
            $id = (int)$this->getConnection()->lastInsertId();

            // Se tivermos um usuário na sessão, registrar no Log de Modificações
            if (!empty($_SESSION['user_id'])) {
                $dadosDepois = $data;
                $dadosDepois['id'] = $id;
                $dadosDepois['documento_codigo'] = $documentoCodigo;

                LogAlteracaoService::registrarAlteracao(
                    'lgpd_termos',
                    $id,
                    (int)$_SESSION['user_id'],
                    'INSERT',
                    [],
                    $dadosDepois
                );
            }

            return $id;
        } catch (\Throwable $e) {
            // Em último caso, apenas retorna true para manter compatibilidade
            return true;
        }
    }

    /**
     * Cria uma nova versão de um termo existente.
     * - Mantém tipo e documento_codigo
     * - Atualiza data_fim_vigencia e status da versão anterior
     * - Retorna o ID da nova versão ou null em caso de erro
     */
    public function createNewVersion(int $idAnterior, array $dataNova): ?int
    {
        try {
            $conn = $this->getConnection();
            $conn->beginTransaction();

            // Buscar termo anterior
            $sqlAnterior = "SELECT * FROM lgpd_termos WHERE id = :id";
            $stmtAnt = $conn->prepare($sqlAnterior);
            $stmtAnt->bindValue(':id', $idAnterior, PDO::PARAM_INT);
            $stmtAnt->execute();
            $termoAntigo = $stmtAnt->fetch(PDO::FETCH_ASSOC);

            if (!$termoAntigo) {
                $conn->rollBack();
                return null;
            }

            // Garantir que exista um documento_codigo para o termo
            $documentoCodigo = $termoAntigo['documento_codigo'] ?? null;
            if (empty($documentoCodigo)) {
                $documentoCodigo = $this->generateDocumentoCodigo($termoAntigo['tipo'], $termoAntigo['titulo']);

                $stmtUpdCodigo = $conn->prepare(
                    "UPDATE lgpd_termos SET documento_codigo = :codigo WHERE id = :id"
                );
                $stmtUpdCodigo->bindValue(':codigo', $documentoCodigo, PDO::PARAM_STR);
                $stmtUpdCodigo->bindValue(':id', $idAnterior, PDO::PARAM_INT);
                $stmtUpdCodigo->execute();
            }

            // Fechar vigência da versão anterior
            $dataFim = $dataNova['data_inicio_vigencia'] ?? date('Y-m-d H:i:s');
            $stmtClose = $conn->prepare(
                "UPDATE lgpd_termos 
                 SET data_fim_vigencia = :data_fim, status = 'Inativo', updated_at = NOW()
                 WHERE id = :id"
            );
            $stmtClose->bindValue(':data_fim', $dataFim, PDO::PARAM_STR);
            $stmtClose->bindValue(':id', $idAnterior, PDO::PARAM_INT);
            $stmtClose->execute();

            // Inserir nova versão
            $sqlInsert = "INSERT INTO lgpd_termos 
                            (versao, titulo, tipo, documento_codigo, conteudo, data_inicio_vigencia, data_fim_vigencia, status, created_at)
                          VALUES
                            (:versao, :titulo, :tipo, :documento_codigo, :conteudo, :data_inicio_vigencia, :data_fim_vigencia, :status, NOW())";

            $stmtNew = $conn->prepare($sqlInsert);
            $stmtNew->bindValue(':versao', $dataNova['versao'], PDO::PARAM_STR);
            $stmtNew->bindValue(':titulo', $dataNova['titulo'], PDO::PARAM_STR);
            $stmtNew->bindValue(':tipo', $termoAntigo['tipo'], PDO::PARAM_STR);
            $stmtNew->bindValue(':documento_codigo', $documentoCodigo, PDO::PARAM_STR);
            $stmtNew->bindValue(':conteudo', $dataNova['conteudo'], PDO::PARAM_STR);
            $stmtNew->bindValue(':data_inicio_vigencia', $dataNova['data_inicio_vigencia'], PDO::PARAM_STR);
            $stmtNew->bindValue(':data_fim_vigencia', $dataNova['data_fim_vigencia'] ?? null, PDO::PARAM_STR);
            $stmtNew->bindValue(':status', $dataNova['status'] ?? 'Ativo', PDO::PARAM_STR);

            if (!$stmtNew->execute()) {
                $conn->rollBack();
                return null;
            }

            $newId = (int)$conn->lastInsertId();
            $conn->commit();

            return $newId > 0 ? $newId : null;
        } catch (Exception $e) {
            error_log('Erro ao criar nova versão de termo LGPD: ' . $e->getMessage());
            try {
                $this->getConnection()->rollBack();
            } catch (Exception $ignored) {
            }
            return null;
        }
    }

    /**
     * Exclui definitivamente um termo LGPD, registrando log de alterações se possível.
     */
    public function delete(int $id): bool
    {
        $conn = $this->getConnection();

        // Buscar registro antes da exclusão para log
        $dadosAntes = $this->getById($id);

        $stmt = $conn->prepare("DELETE FROM lgpd_termos WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        $ok = $stmt->execute();

        if ($ok && $stmt->rowCount() > 0 && $dadosAntes && !empty($_SESSION['user_id'])) {
            LogAlteracaoService::registrarAlteracao(
                'lgpd_termos',
                $id,
                (int)$_SESSION['user_id'],
                'DELETE',
                $dadosAntes,
                []
            );
        }

        return $ok;
    }

    /**
     * Gera um identificador lógico de documento baseado em tipo e título.
     * Ex.: tipo=login, título="Termo de Uso" => LOGIN_TERMO_DE_USO_abc123
     */
    private function generateDocumentoCodigo(string $tipo, string $titulo): string
    {
        $base = strtoupper($tipo . '_' . preg_replace('/[^a-z0-9]+/i', '_', $titulo));
        $base = trim($base, '_');
        // Sufixo para garantir unicidade mesmo com títulos repetidos
        $sufixo = substr(sha1($base . microtime(true)), 0, 6);
        return $base . '_' . $sufixo;
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE lgpd_termos
                SET versao = :versao,
                    titulo = :titulo,
                    tipo = :tipo,
                    conteudo = :conteudo,
                    data_inicio_vigencia = :data_inicio_vigencia,
                    data_fim_vigencia = :data_fim_vigencia,
                    status = :status,
                    updated_at = NOW()
                WHERE id = :id";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':versao', $data['versao'], PDO::PARAM_STR);
        $stmt->bindValue(':titulo', $data['titulo'], PDO::PARAM_STR);
        $stmt->bindValue(':tipo', $data['tipo'], PDO::PARAM_STR);
        $stmt->bindValue(':conteudo', $data['conteudo'], PDO::PARAM_STR);
        $stmt->bindValue(':data_inicio_vigencia', $data['data_inicio_vigencia'], PDO::PARAM_STR);
        $stmt->bindValue(':data_fim_vigencia', $data['data_fim_vigencia'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':status', $data['status'] ?? 'Ativo', PDO::PARAM_STR);

        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM lgpd_termos WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}


