<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use Exception;
use PDO;

/**
 * Repository responsável por buscar e manipular páginas no banco de dados.
 *
 * Esta classe fornece métodos para recuperar, criar, atualizar e deletar páginas no banco de dados.
 * Ela estende a classe `DbConnection` para gerenciar conexões com o banco de dados e utiliza o `GenerateLog`
 * para registrar erros que ocorrem durante as operações.
 *
 * @package App\adms\Models\Repository
 * @author Cesar <cesar@celke.com.br>
 */
class PagesRepository extends DbConnection
{

    /**
     * Recuperar todas as páginas com paginação.
     *
     * Este método retorna uma lista de páginas da tabela `adms_pages`, com suporte à paginação.
     *
     * @param int $page Número da página para recuperação de páginas (começa do 1).
     * @param int $limitResult Número máximo de resultados por página.
     * @return array Lista de páginas recuperadas do banco de dados.
     */
    public function getAllPages(int $page = 1, int $limitResult = 10, array $filters = []): array
    {
        $offset = max(0, ($page - 1) * $limitResult);
        $where = [];
        $params = [];
        if (!empty($filters['nome'])) {
            $where[] = 'name LIKE :nome';
            $params[':nome'] = '%' . $filters['nome'] . '%';
        }
        if (!empty($filters['controller'])) {
            $where[] = 'controller_url LIKE :controller';
            $params[':controller'] = '%' . $filters['controller'] . '%';
        }
        if ($filters['status'] !== '' && $filters['status'] !== null) {
            $where[] = 'page_status = :status';
            $params[':status'] = (int)$filters['status'];
        }
        if ($filters['publica'] !== '' && $filters['publica'] !== null) {
            $where[] = 'public_page = :publica';
            $params[':publica'] = (int)$filters['publica'];
        }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = 'SELECT id, name, controller_url, page_status, public_page FROM adms_pages '
            . $whereSql . ' ORDER BY name ASC LIMIT :limit OFFSET :offset';
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
     * Recuperar a quantidade total de páginas para paginação.
     *
     * Este método retorna a quantidade total de páginas na tabela `adms_pages`, útil para a paginação.
     *
     * @return int Quantidade total de páginas encontradas no banco de dados.
     */
    public function getAmountPages(array $filters = []): int
    {
        $where = [];
        $params = [];
        if (!empty($filters['nome'])) {
            $where[] = 'name LIKE :nome';
            $params[':nome'] = '%' . $filters['nome'] . '%';
        }
        if (!empty($filters['controller'])) {
            $where[] = 'controller_url LIKE :controller';
            $params[':controller'] = '%' . $filters['controller'] . '%';
        }
        if ($filters['status'] !== '' && $filters['status'] !== null) {
            $where[] = 'page_status = :status';
            $params[':status'] = (int)$filters['status'];
        }
        if ($filters['publica'] !== '' && $filters['publica'] !== null) {
            $where[] = 'public_page = :publica';
            $params[':publica'] = (int)$filters['publica'];
        }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = 'SELECT COUNT(id) as amount_records FROM adms_pages ' . $whereSql;
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['amount_records'] ?? 0);
    }

    /**
     * Recuperar uma página específica pelo ID.
     *
     * Este método retorna os detalhes de uma página específica identificada pelo ID.
     *
     * @param int $id ID da página a ser recuperada.
     * @return array|bool Detalhes da página recuperada ou `false` se não encontrada.
     */
    public function getPage(int $id): array|bool
    {
        // QUERY para recuperar o registro do banco de dados
        $sql = 'SELECT ap.id, ap.name, ap.controller, ap.controller_url, ap.directory, ap.obs, ap.page_status, ap.public_page, ap.adms_packages_page_id, ap.adms_groups_page_id, ap.created_at, ap.updated_at,
                app.name app_name,
                agp.name agp_name
                FROM adms_pages AS ap
                INNER JOIN adms_packages_pages AS app ON app.id=ap.adms_packages_page_id
                INNER JOIN adms_groups_pages AS agp ON agp.id=ap.adms_groups_page_id
                WHERE ap.id = :id
                ORDER BY ap.id DESC';

        // Preparar a QUERY
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        // Executar a QUERY
        $stmt->execute();

        // Ler o registro e retornar
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Cadastrar uma nova página.
     *
     * Este método insere uma nova página na tabela `adms_pages`. Em caso de erro, um log é gerado.
     *
     * @param array $data Dados da página a ser cadastrada, incluindo `name`, `controller`, `controller_url`, `directory`, `obs`, `page_status`, `public_page`, `adms_packages_page_id`, e `adms_groups_page_id`.
     * @return bool|int `true` se a página foi criada com sucesso ou `false` em caso de erro.
     */
    public function createPage(array $data): bool|int
    {
        try {            

            // QUERY para cadastrar página
            $sql = 'INSERT INTO adms_pages (name, controller, controller_url, directory, obs, page_status, public_page, adms_packages_page_id, adms_groups_page_id, created_at) VALUES (:name, :controller, :controller_url, :directory, :obs, :page_status, :public_page, :adms_packages_page_id, :adms_groups_page_id, :created_at)';

            // Preparar a QUERY
            $stmt = $this->getConnection()->prepare($sql);

            // Substituir os parâmetros da QUERY pelos valores
            $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
            $stmt->bindValue(':controller', $data['controller'], PDO::PARAM_STR);
            $stmt->bindValue(':controller_url', $data['controller_url'], PDO::PARAM_STR);
            $stmt->bindValue(':directory', $data['directory'], PDO::PARAM_STR);
            $stmt->bindValue(':obs', $data['obs'], PDO::PARAM_STR);
            $stmt->bindValue(':page_status', $data['page_status'], PDO::PARAM_BOOL);
            $stmt->bindValue(':public_page', $data['public_page'], PDO::PARAM_BOOL);
            $stmt->bindValue(':adms_packages_page_id', $data['adms_packages_page_id'], PDO::PARAM_INT);
            $stmt->bindValue(':adms_groups_page_id', $data['adms_groups_page_id'], PDO::PARAM_INT);
            $stmt->bindValue(':created_at', date("Y-m-d H:i:s"));

            // Executar a QUERY
            $stmt->execute();

            // Retornar o ID da página recém-cadastrada
            $pageId = $this->getConnection()->lastInsertId();

            // Registrar log de alteração
            if ($pageId) {
                $usuarioId = $_SESSION['user_id'] ?? 1; // ID do usuário logado ou 1 como padrão
                $logData = [
                    'name' => $data['name'],
                    'controller' => $data['controller'],
                    'controller_url' => $data['controller_url'],
                    'directory' => $data['directory'],
                    'obs' => $data['obs'],
                    'page_status' => $data['page_status'],
                    'public_page' => $data['public_page'],
                    'adms_packages_page_id' => $data['adms_packages_page_id'],
                    'adms_groups_page_id' => $data['adms_groups_page_id'],
                    'created_at' => date("Y-m-d H:i:s")
                ];
                
                LogAlteracaoService::registrarAlteracao(
                    'adms_pages',
                    $pageId,
                    $usuarioId,
                    'INSERT',
                    [],
                    $logData
                );
            }

            return $pageId;
        } catch (Exception $e) {
            // Gerar log de erro
            GenerateLog::generateLog("error", "Página não cadastrada.", ['name' => $data['name'], 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Atualizar os dados de uma página existente.
     *
     * Este método atualiza as informações de uma página existente. Em caso de erro, um log é gerado.
     *
     * @param array $data Dados atualizados da página, incluindo `id`, `name`, `controller`, `controller_url`, `directory`, `obs`, `page_status`, `public_page`, `adms_packages_page_id`, e `adms_groups_page_id`.
     * @return bool `true` se a atualização foi bem-sucedida ou `false` em caso de erro.
     */
    public function updatePage(array $data): bool
    {
        try {
            // Recuperar dados antigos antes da atualização
            $oldData = $this->getPage($data['id']);
            
            // QUERY para atualizar página
            $sql = 'UPDATE adms_pages SET 
                    name = :name, 
                    controller = :controller, 
                    controller_url = :controller_url, 
                    directory = :directory, 
                    obs = :obs,
                    page_status = :page_status, 
                    public_page = :public_page, 
                    adms_packages_page_id = :adms_packages_page_id, 
                    adms_groups_page_id = :adms_groups_page_id,  
                    updated_at = :updated_at';

            // Condição para indicar qual registro editar
            $sql .= ' WHERE id = :id';

            // Preparar a QUERY
            $stmt = $this->getConnection()->prepare($sql);

            // Substituir os parâmetros da QUERY pelos valores 
            $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
            $stmt->bindValue(':controller', $data['controller'], PDO::PARAM_STR);
            $stmt->bindValue(':controller_url', $data['controller_url'], PDO::PARAM_STR);
            $stmt->bindValue(':directory', $data['directory'], PDO::PARAM_STR);
            $stmt->bindValue(':obs', $data['obs'], PDO::PARAM_STR);
            $stmt->bindValue(':page_status', $data['page_status'], PDO::PARAM_BOOL);
            $stmt->bindValue(':public_page', $data['public_page'], PDO::PARAM_BOOL);
            $stmt->bindValue(':adms_packages_page_id', $data['adms_packages_page_id'], PDO::PARAM_INT);
            $stmt->bindValue(':adms_groups_page_id', $data['adms_groups_page_id'], PDO::PARAM_INT);
            $stmt->bindValue(':updated_at', date("Y-m-d H:i:s"));
            $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);

            // Executar a QUERY
            $result = $stmt->execute();

            // Registrar log de alteração se a atualização foi bem-sucedida
            if ($result && $oldData) {
                $usuarioId = $_SESSION['user_id'] ?? 1; // ID do usuário logado ou 1 como padrão
                $newData = array_merge($oldData, [
                    'name' => $data['name'],
                    'controller' => $data['controller'],
                    'controller_url' => $data['controller_url'],
                    'directory' => $data['directory'],
                    'obs' => $data['obs'],
                    'page_status' => $data['page_status'],
                    'public_page' => $data['public_page'],
                    'adms_packages_page_id' => $data['adms_packages_page_id'],
                    'adms_groups_page_id' => $data['adms_groups_page_id'],
                    'updated_at' => date("Y-m-d H:i:s")
                ]);
                
                LogAlteracaoService::registrarAlteracao(
                    'adms_pages',
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
            GenerateLog::generateLog("error", "Página não editada.", ['id' => $data['id'], 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Deletar uma página pelo ID.
     *
     * Este método remove uma página específica da tabela `adms_pages`. Em caso de erro, um log é gerado.
     *
     * @param int $id ID da página a ser deletada.
     * @return bool `true` se a página foi deletada com sucesso ou `false` em caso de erro.
     */
    public function deletePage(int $id): bool
    {
        try {
            // Recuperar dados antes da exclusão
            $oldData = $this->getPage($id);
            
            // QUERY para deletar página
            $sql = 'DELETE FROM adms_pages WHERE id = :id LIMIT 1';

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
                        'adms_pages',
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
                GenerateLog::generateLog("error", "Página não apagada.", ['id' => $id]);

                return false;
            }
        } catch (Exception $e) {
            // Gerar log de erro
            GenerateLog::generateLog("error", "Página não apagada.", ['id' => $id, 'error' => $e->getMessage()]);

            return false;
        }
    }

    public function getPagesArray(): array|bool
    {

        // QUERY para recuperar os registros do banco de dados
        $sql = 'SELECT id
                FROM adms_pages';

        // Preparar a QUERY
        $stmt = $this->getConnection()->prepare($sql);

        // Executar a QUERY
        $stmt->execute();

        // Ler os registros
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Retornar apenas os valores de 'id' como array simples
        return $result ? array_column($result, 'id') : false;
    }

    public function getAllPagesFull(): array
    {

        // QUERY para recuperar os registros do banco de dados
        $sql = 'SELECT ap.id, ap.name, ap.obs, ap.page_status, ap.public_page,
                app.name app_name, agp.name AS agp_name
                FROM adms_pages AS ap 
                INNER JOIN adms_packages_pages AS app ON app.id=ap.adms_packages_page_id
                INNER JOIN adms_groups_pages as agp on ap.adms_groups_page_id = agp.id
                WHERE ap.page_status = :page_status
                ORDER BY ap.id ASC';

        // Preparar a QUERY
        $stmt = $this->getConnection()->prepare($sql);

        // Substituir os parâmetros da QUERY pelos valores
        $stmt->bindValue(':page_status', 1, PDO::PARAM_INT);

        // Executar a QUERY
        $stmt->execute();

        // Ler os registros e retornar
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
