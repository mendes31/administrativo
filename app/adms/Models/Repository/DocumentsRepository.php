<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use Exception;
use PDO;

/**
 * Repository responsável por buscar e manipular documentos no banco de dados.
 *
 * Esta classe fornece métodos para recuperar, criar, atualizar e deletar documentos no banco de dados.
 * Ela estende a classe `DbConnection` para gerenciar conexões com o banco de dados e utiliza o `GenerateLog`
 * para registrar erros que ocorrem durante as operações.
 *
 * @package App\adms\Models\Repository
 * @author Cesar <cesar@celke.com.br>
 */
class DocumentsRepository extends DbConnection
{   

    /**
     * Recuperar todos os documentos com paginação e filtros.
     *
     * @param int $page Número da página para recuperação de documentos (começa do 1).
     * @param int $limitResult Número máximo de resultados por página.
     * @param string $filterCodDoc Filtro de busca pelo código (parcial ou inteiro).
     * @param string $filterName Filtro de busca pelo nome (parcial ou inteiro).
     * @param string $filterVersion Filtro de busca pela versão (parcial ou inteiro).
     * @param string $filterStatus Filtro de busca pelo status ('1', '0' ou '').
     * @return array Lista de documentos recuperados do banco de dados.
     */
    public function getAllDocuments(int $page = 1, int $limitResult = 10, string $filterCodDoc = '', string $filterName = '', string $filterVersion = '', string $filterStatus = ''): array
    {
        $offset = max(0, ($page - 1) * $limitResult);
        $sql = 'SELECT id, cod_doc, name_doc, version, active 
                FROM adms_documents';
        $where = [];
        $params = [];
        if (!empty($filterCodDoc)) {
            $where[] = 'cod_doc LIKE :cod_doc';
            $params[':cod_doc'] = '%' . $filterCodDoc . '%';
        }
        if (!empty($filterName)) {
            $where[] = 'name_doc LIKE :name_doc';
            $params[':name_doc'] = '%' . $filterName . '%';
        }
        if (!empty($filterVersion)) {
            $where[] = 'version LIKE :version';
            $params[':version'] = '%' . $filterVersion . '%';
        }
        if ($filterStatus !== '' && ($filterStatus === '0' || $filterStatus === '1')) {
            $where[] = 'active = :active';
            $params[':active'] = (int)$filterStatus;
        }
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY name_doc ASC
                LIMIT :limit OFFSET :offset';
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limitResult, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Recuperar a quantidade total de documentos para paginação e filtros.
     *
     * @param string $filterCodDoc Filtro de busca pelo código (parcial ou inteiro).
     * @param string $filterName Filtro de busca pelo nome (parcial ou inteiro).
     * @param string $filterVersion Filtro de busca pela versão (parcial ou inteiro).
     * @param string $filterStatus Filtro de busca pelo status ('1', '0' ou '').
     * @return int Quantidade total de documentos encontrados no banco de dados.
     */
    public function getAmountDocuments(string $filterCodDoc = '', string $filterName = '', string $filterVersion = '', string $filterStatus = ''): int
    {
        $sql = 'SELECT COUNT(id) as amount_records
                FROM adms_documents';
        $where = [];
        $params = [];
        if (!empty($filterCodDoc)) {
            $where[] = 'cod_doc LIKE :cod_doc';
            $params[':cod_doc'] = '%' . $filterCodDoc . '%';
        }
        if (!empty($filterName)) {
            $where[] = 'name_doc LIKE :name_doc';
            $params[':name_doc'] = '%' . $filterName . '%';
        }
        if (!empty($filterVersion)) {
            $where[] = 'version LIKE :version';
            $params[':version'] = '%' . $filterVersion . '%';
        }
        if ($filterStatus !== '' && ($filterStatus === '0' || $filterStatus === '1')) {
            $where[] = 'active = :active';
            $params[':active'] = (int)$filterStatus;
        }
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['amount_records'] ?? 0);
    }

    /**
     * Recuperar um documento específico pelo ID.
     *
     * @param int $id ID do documento a ser recuperado.
     * @return array|bool Detalhes do documento recuperado ou `false` se não encontrado.
     */
    public function getDocument(int $id): array|bool
    {
        // QUERY para recuperar o registro do banco de dados
        $sql = 'SELECT id, cod_doc, name_doc, version, active, created_at, updated_at
                FROM adms_documents
                WHERE id = :id
                ORDER BY name_doc ASC';

        // Preparar a QUERY
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        // Executar a QUERY
        $stmt->execute();

        // Ler o registro e retornar
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Cadastrar um novo documento.
     *
     * Este método insere um novo documento na tabela `adms_documents`. Em caso de erro, um log é gerado.
     *
     * @param array $data Dados do documento a ser cadastrado, incluindo `cod_doc`, `name_doc`, `version`, `active`.
     * @return bool|int `true` se o documento foi criado com sucesso ou `false` em caso de erro.
     */
    public function createDocument(array $data): bool|int
    {
        try {            

            // QUERY para cadastrar documento
            $sql = 'INSERT INTO adms_documents (cod_doc, name_doc, version, active, created_at) VALUES (:cod_doc, :name_doc, :version, :active, :created_at)';

            // Preparar a QUERY
            $stmt = $this->getConnection()->prepare($sql);

            // Substituir os parâmetros da QUERY pelos valores
            $stmt->bindValue(':cod_doc', $data['cod_doc'], PDO::PARAM_STR);
            $stmt->bindValue(':name_doc', $data['name_doc'], PDO::PARAM_STR);   
            $stmt->bindValue(':version', $data['version'], PDO::PARAM_STR);
            $stmt->bindValue(':active', $data['active'], PDO::PARAM_BOOL);
            $stmt->bindValue(':created_at', date("Y-m-d H:i:s"));

            // Executar a QUERY
            $stmt->execute();

            // Retornar o ID do documento recém-cadastrado
            $documentId = $this->getConnection()->lastInsertId();

            // Registrar log de alteração
            if ($documentId) {
                $usuarioId = $_SESSION['user_id'] ?? 1; // ID do usuário logado ou 1 como padrão
                $logData = [
                    'cod_doc' => $data['cod_doc'],
                    'name_doc' => $data['name_doc'],
                    'version' => $data['version'],
                    'active' => $data['active'],
                    'created_at' => date("Y-m-d H:i:s")
                ];
                
                LogAlteracaoService::registrarAlteracao(
                    'adms_documents',
                    $documentId,
                    $usuarioId,
                    'INSERT',
                    [],
                    $logData
                );
            }

            return $documentId;
        } catch (Exception $e) {
            // Gerar log de erro
            GenerateLog::generateLog("error", "Documento não cadastrado.", ['cod_doc' => $data['cod_doc'], 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Atualizar os dados de um documento existente.
     *
     * Este método atualiza as informações de um documento existente. Em caso de erro, um log é gerado.
     *
     * @param array $data Dados atualizados do documento, incluindo `id`, `cod_doc`, `name_doc`, `version`, `active`.
     * @return bool `true` se a atualização foi bem-sucedida ou `false` em caso de erro.
     */
    public function updateDocument(array $data): bool
    {
        try {
            // Recuperar dados antigos antes da atualização
            $oldData = $this->getDocument($data['id']);
            
            // QUERY para atualizar documento
            $sql = 'UPDATE adms_documents SET 
                    cod_doc = :cod_doc, 
                    name_doc = :name_doc, 
                    version = :version, 
                    active = :active, 
                    updated_at = :updated_at';

            // Condição para indicar qual registro editar
            $sql .= ' WHERE id = :id';

            // Preparar a QUERY
            $stmt = $this->getConnection()->prepare($sql);

            // Substituir os parâmetros da QUERY pelos valores 
            $stmt->bindValue(':cod_doc', $data['cod_doc'], PDO::PARAM_STR);
            $stmt->bindValue(':name_doc', $data['name_doc'], PDO::PARAM_STR);
            $stmt->bindValue(':version', $data['version'], PDO::PARAM_STR);
            $stmt->bindValue(':active', $data['active'], PDO::PARAM_BOOL);
            $stmt->bindValue(':updated_at', date("Y-m-d H:i:s"));
            $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);

            // Executar a QUERY
            $result = $stmt->execute();

            // Registrar log de alteração se a atualização foi bem-sucedida
            if ($result && $oldData) {
                $usuarioId = $_SESSION['user_id'] ?? 1; // ID do usuário logado ou 1 como padrão
                $newData = array_merge($oldData, [
                    'cod_doc' => $data['cod_doc'],
                    'name_doc' => $data['name_doc'],
                    'version' => $data['version'],
                    'active' => $data['active'],
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
                
                LogAlteracaoService::registrarAlteracao(
                    'adms_documents',
                    $data['id'],
                    $usuarioId,
                    'UPDATE',
                    $oldData,
                    $newData
                );
            }

            return $result;
        } catch (Exception $e) {
            // Gerar log de erro
            GenerateLog::generateLog("error", "Documento não editado.", ['id' => $data['id'], 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Deletar um documento pelo ID.
     *
     * Este método remove um documento específico da tabela `adms_documents`. Em caso de erro, um log é gerado.
     *
     * @param int $id ID do documento a ser deletado.
     * @return bool `true` se o documento foi deletado com sucesso ou `false` em caso de erro.
     */
        public function deleteDocument(int $id): bool
    {
        try {
            // Recuperar dados antes da exclusão
            $oldData = $this->getDocument($id);
           
            $sql = 'DELETE FROM adms_documents WHERE id = :id LIMIT 1';

            // Preparar a QUERY
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);

            // Executar a QUERY
            $stmt->execute();

            // Verificar o número de linhas afetadas
            $affectedRows = $stmt->rowCount();

            if ($affectedRows > 0) {
                // Registrar log de alteração se a exclusão foi bem-sucedida
                if ($oldData) {
                    $usuarioId = $_SESSION['user_id'] ?? 1; // ID do usuário logado ou 1 como padrão
                    LogAlteracaoService::registrarAlteracao(
                        'adms_documents',
                        $id,
                        $usuarioId,
                        'DELETE',
                        $oldData,
                        []
                    );
                }
                
                return true;
            } else {
                // Gerar log de erro
                GenerateLog::generateLog("error", "Documento não apagado.", ['id' => $id]);

                return false;
            }
        } catch (Exception $e) {
            // Gerar log de erro
            GenerateLog::generateLog("error", "Documento não apagado.", ['id' => $id, 'error' => $e->getMessage()]);

            return false;
        }
    }

    public function getDocumentsArray(): array|bool
    {

        // QUERY para recuperar os registros do banco de dados
        $sql = 'SELECT id
                FROM adms_documents';

        // Preparar a QUERY
        $stmt = $this->getConnection()->prepare($sql);

        // Executar a QUERY
        $stmt->execute();

        // Ler os registros
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Retornar apenas os valores de 'id' como array simples
        return $result ? array_column($result, 'id') : false;
    }

    public function getAllDocumentsFull(): array
    {

        // QUERY para recuperar os registros do banco de dados
        $sql = 'SELECT id, cod_doc, name_doc, version, active
                FROM adms_documents
                ORDER BY name_doc ASC';

        // Preparar a QUERY
        $stmt = $this->getConnection()->prepare($sql);

        // Executar a QUERY
        $stmt->execute();

        // Ler os registros e retornar
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
