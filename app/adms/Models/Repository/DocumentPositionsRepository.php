<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use Exception;
use PDO;

/**
 * Repository responsável em buscar e manipular treinamentos de documentos no banco de dados.
 *
 * Esta classe fornece métodos para recuperar, criar, atualizar e deletar treinamentos de documentos no banco de dados.
 * Ela estende a classe `DbConnection` para gerenciar conexões com o banco de dados e utiliza o `GenerateLog`
 * para registrar erros que ocorrem durante as operações.
 *
 * @package App\adms\Models\Repository
 * @author Cesar <cesar@celke.com.br>
 */
class DocumentPositionsRepository extends DbConnection
{

    /**
     * Recuperar todos os treinamentos de documentos com paginação.
     *
     * Este método retorna uma lista de treinamentos de documentos da tabela `adms_document_positions`, com suporte à paginação.
     *
     * @param int $page Número da página para recuperação de treinamentos de documentos (começa do 1).
     * @param int $limitResult Número máximo de resultados por página.
     * @return array Lista de treinamentos de documentos recuperados do banco de dados.
     */
    public function getAllDocumentPositions(int $page = 1, int $limitResult = 10): array
    {
        // Calcular o registro inicial, função max para garantir valor mínimo 0
        $offset = max(0, ($page - 1) * $limitResult);

        // QUERY para recuperar os registros do banco de dados
        $sql = 'SELECT id, mandatory, adms_document_id, adms_position_id, created_at, updated_at
                FROM adms_document_positions               
                ORDER BY id ASC
                LIMIT :limit OFFSET :offset';

        // Preparar a QUERY
        $stmt = $this->getConnection()->prepare($sql);

        // Substituir os parâmetros da QUERY pelos valores
        $stmt->bindValue(':limit', $limitResult, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        // Executar a QUERY
        $stmt->execute();

        // Ler os registros e retornar
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Recuperar a quantidade total de documentos de acesso para paginação.
     *
     * Este método retorna a quantidade total de documentos de acesso na tabela `adms_document_positions`, útil para a paginação.
     *
     * @return int Quantidade total de documentos de acesso encontrados no banco de dados.
     */
    public function getAmountDocumentPositions(): int
    {
        // QUERY para recuperar a quantidade de registros
        $sql = 'SELECT COUNT(id) as amount_records
                FROM adms_document_positions';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();

        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['amount_records'] ?? 0);
    }

    /**
     * Recuperar um documento de acesso específico pelo ID.
     *
     * Este método retorna os detalhes de um documento de acesso específico identificado pelo ID.
     *
     * @param int $id ID do documento de acesso a ser recuperado.
     * @return array|bool Detalhes do documento de acesso recuperado ou `false` se não encontrado.
     */
    public function getDocumentPosition(int $id): array|bool
    {
        // QUERY para recuperar o registro do banco de dados
        $sql = 'SELECT id, mandatory, adms_document_id, adms_position_id, created_at, updated_at
                FROM adms_document_positions
                WHERE id = :id
                ORDER BY id DESC';

        // Preparar a QUERY
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        // Executar a QUERY
        $stmt->execute();

        // Ler o registro e retornar
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Cadastrar um novo documento 
     *
     * @param array $data Dados do documento a ser cadastrado, incluindo `name`.
     * @return bool|int `true` se o documento foi criado com sucesso ou `false` em caso de erro.
     */
    public function createDocumentPosition(array $data): bool|int
    {
        try {

            // QUERY para cadastrar documento
            $sql = 'INSERT INTO adms_document_positions (mandatory, adms_document_id, adms_position_id, created_at) VALUES (:mandatory, :adms_document_id, :adms_position_id, :created_at)';

            // Preparar a QUERY
            $stmt = $this->getConnection()->prepare($sql);

            // Substituir os parâmetros da QUERY pelos valores
            $stmt->bindValue(':mandatory', $data['mandatory'], PDO::PARAM_STR);
            $stmt->bindValue(':adms_document_id', $data['adms_document_id'], PDO::PARAM_INT);
            $stmt->bindValue(':adms_position_id', $data['adms_position_id'], PDO::PARAM_INT);
            $stmt->bindValue(':created_at', date("Y-m-d H:i:s"));

            // Executar a QUERY
            $stmt->execute();

            // Retornar o ID do documento recém cadastrado
            return $this->getConnection()->lastInsertId();

            
        } catch (Exception $e) {
            // Gerar log de erro
            GenerateLog::generateLog("error", "Documento não cadastrado.", ['mandatory' => $data['mandatory'], 'adms_document_id' => $data['adms_document_id'], 'adms_position_id' => $data['adms_position_id'], 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Atualizar os dados de um documento existente.
     *
     * Este método atualiza as informações de um documento existente. Se a senha for fornecida, ela também será atualizada.
     * Em caso de erro, um log é gerado.
     *
     * @param array $data Dados atualizados do nível de acesso, incluindo `id`, `name`.
     * @return bool `true` se a atualização foi bem-sucedida ou `false` em caso de erro.
     */
    public function updateDocumentPosition(array $data): bool
    {
        try {
            // QUERY para atualizar documento
            $sql = 'UPDATE adms_document_positions SET mandatory = :mandatory, adms_document_id = :adms_document_id, adms_position_id = :adms_position_id, updated_at = :updated_at';

            // Condição para indicar qual registro editar
            $sql .= ' WHERE id = :id';

            // Preparar a QUERY
            $stmt = $this->getConnection()->prepare($sql);

            // Substituir os parâmetros da QUERY pelos valores
            $stmt->bindValue(':mandatory', $data['mandatory'], PDO::PARAM_STR);
            $stmt->bindValue(':adms_document_id', $data['adms_document_id'], PDO::PARAM_INT);
            $stmt->bindValue(':adms_position_id', $data['adms_position_id'], PDO::PARAM_INT);
            $stmt->bindValue(':updated_at', date("Y-m-d H:i:s"));
            $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);

            // Executar a QUERY
            return $stmt->execute();
        } catch (Exception $e) {
            // Gerar log de erro
            GenerateLog::generateLog("error", "Documento não editado.", ['id' => $data['id'], 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Deletar um documento pelo ID.
     *
     * Este método remove um documento específico da tabela `adms_document_positions`. Em caso de erro, um log é gerado.
     *
     * @param int $id ID do documento a ser deletado.
     * @return bool `true` se o documento foi deletado com sucesso ou `false` em caso de erro.
     */
    public function deleteDocumentPosition(int $id): bool
    {
        try {
            // QUERY para deletar documento
            $sql = 'DELETE FROM adms_document_positions WHERE id = :id LIMIT 1';

            // Preparar a QUERY
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);

            // Executar a QUERY
            $stmt->execute();

            // Verificar o número de linhas afetadas
            $affectedRows = $stmt->rowCount();

            if ($affectedRows > 0) {
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

    public function getAllDocumentPositionsSelect(): array
    {

        // QUERY para recuperar os registros do banco de dados
        $sql = 'SELECT id, mandatory, adms_document_id, adms_position_id, created_at, updated_at
                FROM adms_document_positions                
                ORDER BY id ASC';

        // Preparar a QUERY
        $stmt = $this->getConnection()->prepare($sql);

        // Executar a QUERY
        $stmt->execute();

        // Ler os registros e retornar
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
