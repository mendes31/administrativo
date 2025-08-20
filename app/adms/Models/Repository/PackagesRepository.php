<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use Exception;
use PDO;

/**
 * Repository responsável por buscar e manipular pacotes no banco de dados.
 *
 * Esta classe fornece métodos para recuperar, criar, atualizar e deletar pacotes no banco de dados.
 * Ela estende a classe `DbConnection` para gerenciar conexões com o banco de dados e utiliza o `GenerateLog`
 * para registrar erros que ocorrem durante as operações.
 *
 * @package App\adms\Models\Repository
 * @author Cesar <cesar@celke.com.br>
 */
class PackagesRepository extends DbConnection
{

    /**
     * Recuperar todos os pacotes com paginação.
     *
     * Este método retorna uma lista de pacotes da tabela `adms_packages_pages`, com suporte à paginação.
     *
     * @param int $page Número da página para recuperação de pacotes (começa do 1).
     * @param int $limitResult Número máximo de resultados por página.
     * @return array Lista de pacotes recuperados do banco de dados.
     */
    public function getAllPackages(int $page = 1, int $limitResult = 10): array
    {
        // Calcular o registro inicial, função max para garantir valor mínimo 0
        $offset = max(0, ($page - 1) * $limitResult);

        // QUERY para recuperar os registros do banco de dados
        $sql = 'SELECT id, name 
                FROM adms_packages_pages                
                ORDER BY name ASC
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
     * Recuperar a quantidade total de pacotes para paginação.
     *
     * Este método retorna a quantidade total de pacotes na tabela `adms_packages_pages`, útil para a paginação.
     *
     * @return int Quantidade total de pacotes encontrados no banco de dados.
     */
    public function getAmountPackages(): int
    {
        // QUERY para recuperar a quantidade de registros
        $sql = 'SELECT COUNT(id) as amount_records
                FROM adms_packages_pages';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();

        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['amount_records'] ?? 0);
    }

    /**
     * Recuperar um pacote específico pelo ID.
     *
     * Este método retorna os detalhes de um pacote específico identificado pelo ID.
     *
     * @param int $id ID do pacote a ser recuperado.
     * @return array|bool Detalhes do pacote recuperado ou `false` se não encontrado.
     */
    public function getPackage(int $id): array|bool
    {
        // QUERY para recuperar o registro do banco de dados
        $sql = 'SELECT id, name, obs, created_at, updated_at
                FROM adms_packages_pages
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
     * Cadastrar um novo pacote.
     *
     * Este método insere um novo pacote na tabela `adms_packages_pages`. Em caso de erro, um log é gerado.
     *
     * @param array $data Dados do pacote a ser cadastrado, incluindo `name`.
     * @return bool|int `true` se o pacote foi criado com sucesso ou `false` em caso de erro.
     */
    public function createPackage(array $data): bool|int
    {
        try {            

            // QUERY para cadastrar pacote
            $sql = 'INSERT INTO adms_packages_pages (name, obs, created_at) VALUES (:name, :obs, :created_at)';

            // Preparar a QUERY
            $stmt = $this->getConnection()->prepare($sql);

            // Substituir os parâmetros da QUERY pelos valores
            $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
            $stmt->bindValue(':obs', $data['obs'], PDO::PARAM_STR);
            $stmt->bindValue(':created_at', date("Y-m-d H:i:s"));

            // Executar a QUERY
            $stmt->execute();

            // Retornar o ID do pacote recém-cadastrado
            $packageId = $this->getConnection()->lastInsertId();

            // Registrar log de alteração
            if ($packageId) {
                $usuarioId = $_SESSION['user_id'] ?? 1; // ID do usuário logado ou 1 como padrão
                $logData = [
                    'name' => $data['name'],
                    'obs' => $data['obs'],
                    'created_at' => date("Y-m-d H:i:s")
                ];
                
                LogAlteracaoService::registrarAlteracao(
                    'adms_packages_pages',
                    $packageId,
                    $usuarioId,
                    'INSERT',
                    [],
                    $logData
                );
            }

            return $packageId;
        } catch (Exception $e) {
            // Gerar log de erro
            GenerateLog::generateLog("error", "Pacote não cadastrado.", ['name' => $data['name'], 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Atualizar os dados de um pacote existente.
     *
     * Este método atualiza as informações de um pacote existente. Em caso de erro, um log é gerado.
     *
     * @param array $data Dados atualizados do pacote, incluindo `id`, `name`.
     * @return bool `true` se a atualização foi bem-sucedida ou `false` em caso de erro.
     */
    public function updatePackage(array $data): bool
    {
        try {
            // Recuperar dados antigos antes da atualização
            $oldData = $this->getPackage($data['id']);
            
            // QUERY para atualizar pacote
            $sql = 'UPDATE adms_packages_pages SET name = :name, obs = :obs, updated_at = :updated_at';

            // Condição para indicar qual registro editar
            $sql .= ' WHERE id = :id';

            // Preparar a QUERY
            $stmt = $this->getConnection()->prepare($sql);

            // Substituir os parâmetros da QUERY pelos valores 
            $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
            $stmt->bindValue(':obs', $data['obs'], PDO::PARAM_STR);
            $stmt->bindValue(':updated_at', date("Y-m-d H:i:s"));
            $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);

            // Executar a QUERY
            $result = $stmt->execute();

            // Registrar log de alteração se a atualização foi bem-sucedida
            if ($result && $oldData) {
                $usuarioId = $_SESSION['user_id'] ?? 1; // ID do usuário logado ou 1 como padrão
                $newData = array_merge($oldData, [
                    'name' => $data['name'],
                    'obs' => $data['obs'],
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
                
                LogAlteracaoService::registrarAlteracao(
                    'adms_packages_pages',
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
            GenerateLog::generateLog("error", "Pacote não editado.", ['id' => $data['id'], 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Deletar um pacote pelo ID.
     *
     * Este método remove um pacote específico da tabela `adms_packages_pages`. Em caso de erro, um log é gerado.
     *
     * @param int $id ID do pacote a ser deletado.
     * @return bool `true` se o pacote foi deletado com sucesso ou `false` em caso de erro.
     */
    public function deletePackage(int $id): bool
    {
        try {
            // Recuperar dados antes da exclusão
            $oldData = $this->getPackage($id);
            
            // QUERY para deletar pacote
            $sql = 'DELETE FROM adms_packages_pages WHERE id = :id LIMIT 1';

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
                        'adms_packages_pages',
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
                GenerateLog::generateLog("error", "Pacote não apagado.", ['id' => $id]);

                return false;
            }
        } catch (Exception $e) {
            // Gerar log de erro
            GenerateLog::generateLog("error", "Pacote não apagado.", ['id' => $id, 'error' => $e->getMessage()]);

            return false;
        }
    }

    public function getAllPackagesSelect(): array
    {
        // QUERY para recuperar os registros do banco de dados
        $sql = 'SELECT id, name 
                FROM adms_packages_pages                
                ORDER BY name ASC';

        // Preparar a QUERY
        $stmt = $this->getConnection()->prepare($sql);


        // Executar a QUERY
        $stmt->execute();

        // Ler os registros e retornar
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
