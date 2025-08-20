<?php

namespace App\adms\Models\Repository;

use App\adms\Controllers\Services\Validation\ValidationEmptyField;
use App\adms\Helpers\GenerateLog;
use App\adms\Helpers\SlugImg;
use App\adms\Helpers\Upload;
use App\adms\Helpers\ValExtImg;
use App\adms\Models\Services\DbConnection;
use Exception;
use PDO;

/**
 * Repository responsável em buscar e manipular usuários no banco de dados.
 *
 * Esta classe fornece métodos para recuperar, criar, atualizar e deletar usuários no banco de dados.
 * Ela estende a classe `DbConnection` para gerenciar conexões com o banco de dados e utiliza o `GenerateLog`
 * para registrar erros que ocorrem durante as operações.
 *
 * @package App\adms\Models\Repository
 * @return Rafael Mendes
 */
class UsersRepository extends DbConnection
{
    /** @var array|string|null $data Recebe os dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /** @var array|string|null $data Recebe o nome da imagem*/
    private array|string|null $nameImg = null;

    /** @var array|string|null $data Recebe o nome do diretório  */
    private array|string|null $directory = null;

    /** @var string $delImg Recebe o endereço da imagem que deve ser excluida */
    private string $delImg;

    /** @var array|string|null $data Recebe os dados que devem ser enviados para a VIEW */
    private array|string|null $dataImage = null;

    /**
     * Recuperar todos os usuários com paginação.
     *
     * Este método retorna uma lista de usuários da tabela `adms_users`, com suporte à paginação.
     *
     * @param int $page Número da página para recuperação de usuários (começa do 1).
     * @param int $limitResult Número máximo de resultados por página.
     * @param array $filtros Filtros para aplicar nas consultas.
     * @return array Lista de usuários recuperados do banco de dados.
     */
    public function getAllUsers(int $page = 1, int $limitResult = 10, array $filtros = [])
    {
        $offset = max(0, ($page - 1) * $limitResult);
        $where = [];
        $params = [];
        if (!empty($filtros['nome'])) {
            $where[] = 'usr.name LIKE :nome';
            $params[':nome'] = '%' . $filtros['nome'] . '%';
        }
        if (!empty($filtros['email'])) {
            $where[] = 'usr.email LIKE :email';
            $params[':email'] = '%' . $filtros['email'] . '%';
        }
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = 'SELECT usr.id, usr.name, usr.email, usr.username, usr.user_department_id, usr.user_position_id, usr.status, usr.bloqueado, usr.tentativas_login, usr.senha_nunca_expira, usr.modificar_senha_proximo_logon, dep.name name_dep, pos.name name_pos
                FROM adms_users usr
                INNER JOIN adms_departments dep ON usr.user_department_id = dep.id
                INNER JOIN adms_positions pos ON usr.user_position_id = pos.id 
                ' . $whereSql . '
                ORDER BY usr.name ASC
                LIMIT :limit OFFSET :offset';
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limitResult, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar usuário por email ou username
     */
    public function getUserByEmailOrUsername(string $email, string $username): array|false
    {
        $sql = 'SELECT * FROM adms_users WHERE email = :email OR username = :username LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        return $u ?: false;
    }

    /**
     * Recuperar a quantidade total de usuários para paginação.
     *
     * Este método retorna a quantidade total de usuários na tabela `adms_users`, útil para a paginação.
     *
     * @param array $filtros Filtros para aplicar nas consultas.
     * @return int|bool Quantidade total de usuários encontrados no banco de dados ou `false` em caso de erro.
     */
    public function getAmountUsers(array $filtros = []): int|bool
    {
        $where = [];
        $params = [];
        if (!empty($filtros['nome'])) {
            $where[] = 'name LIKE :nome';
            $params[':nome'] = '%' . $filtros['nome'] . '%';
        }
        if (!empty($filtros['email'])) {
            $where[] = 'email LIKE :email';
            $params[':email'] = '%' . $filtros['email'] . '%';
        }
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = 'SELECT COUNT(id) as amount_records FROM adms_users ' . $whereSql;
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();
        return ($stmt->fetch(PDO::FETCH_ASSOC)['amount_records']) ?? 0;
    }

    /**
     * Recuperar um usuário específico pelo ID.
     *
     * Este método retorna os detalhes de um usuário específico identificado pelo ID.
     *
     * @param int $id ID do usuário a ser recuperado.
     * @return array|bool Detalhes do usuário recuperado ou `false` se não encontrado.
     */
    public function getUser(int $id): array|bool
    {
        // QUERY para recuperar o registro selecionado do banco de dados
        $sql = 'SELECT 
                    t0.id, 
                    t0.name, 
                    t0.email, 
                    t0.username, 
                    t0.image, 
                    t0.data_nascimento, 
                    t0.user_department_id, 
                    t0.user_position_id, 
                    t0.created_at, 
                    t0.updated_at, 
                    t0.status,
                    t0.bloqueado,
                    t0.tentativas_login,
                    t0.senha_nunca_expira,
                    t0.modificar_senha_proximo_logon,
                    t1.name dep_name, 
                    t2.name pos_name
                FROM adms_users t0
                INNER JOIN adms_departments t1 ON t0.user_department_id = t1.id
                INNER JOIN adms_positions t2 ON t0.user_position_id = t2.id
                WHERE t0.id = :id
                ORDER BY t0.id DESC';

        // Preparar a QUERY
        $stmt = $this->getConnection()->prepare($sql);

        // Substituir o link da QUERY pelo valor / Evita SQL INJECTION
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        // Executar a QUERY
        $stmt->execute();

        // Ler o registro e retornar
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Cadastrar um novo usuário.
     *
     * Este método insere um novo usuário na tabela `adms_users`. Em caso de erro, um log é gerado.
     *
     * @param array $data Dados do usuário a ser cadastrado, incluindo `name`, `email`, `username`, `password`.
     * @return bool|int `true` se o usuário foi criado com sucesso ou `false` em caso de erro.
     */
    public function createUser(array $data): bool|int
    {
        try {
            // Garantir imagem default quando não vier
            if (empty($data['image'])) {
                $data['image'] = 'icon_user.png';
            }
            $sql = 'INSERT INTO adms_users (
                name, email, username, user_department_id, user_position_id, password, status, bloqueado, tentativas_login, senha_nunca_expira, modificar_senha_proximo_logon, created_at, image, data_nascimento
            ) VALUES (
                :name, :email, :username, :user_department_id, :user_position_id, :password, :status, :bloqueado, :tentativas_login, :senha_nunca_expira, :modificar_senha_proximo_logon, :created_at, :image, :data_nascimento
            )';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
            $stmt->bindValue(':email', $data['email'], PDO::PARAM_STR);
            $stmt->bindValue(':username', $data['username'], PDO::PARAM_STR);
            $stmt->bindValue(':user_department_id', $data['user_department_id'], PDO::PARAM_INT);
            $stmt->bindValue(':user_position_id', $data['user_position_id'], PDO::PARAM_INT);
            $stmt->bindValue(':password', password_hash($data['password'], PASSWORD_DEFAULT));
            $stmt->bindValue(':status', $data['status'] ?? 'Ativo', PDO::PARAM_STR);
            $stmt->bindValue(':bloqueado', $data['bloqueado'] ?? 'Não', PDO::PARAM_STR);
            $stmt->bindValue(':tentativas_login', $data['tentativas_login'] ?? 0, PDO::PARAM_INT);
            $stmt->bindValue(':senha_nunca_expira', $data['senha_nunca_expira'] ?? 'Não', PDO::PARAM_STR);
            $stmt->bindValue(':modificar_senha_proximo_logon', $data['modificar_senha_proximo_logon'] ?? 'Não', PDO::PARAM_STR);
            $stmt->bindValue(':created_at', date("Y-m-d H:i:s"));
            $stmt->bindValue(':image', $data['image'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':data_nascimento', $data['data_nascimento'] ?? null, PDO::PARAM_STR);
            $stmt->execute();
            $novoId = $this->getConnection()->lastInsertId();
            // Log de inserção
            if ($novoId) {
                $dadosDepois = [
                    'id' => $novoId,
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'username' => $data['username'],
                    'user_department_id' => $data['user_department_id'],
                    'user_position_id' => $data['user_position_id'],
                    'status' => $data['status'] ?? 'Ativo',
                    'bloqueado' => $data['bloqueado'] ?? 'Não',
                    'tentativas_login' => $data['tentativas_login'] ?? 0,
                    'senha_nunca_expira' => $data['senha_nunca_expira'] ?? 'Não',
                    'modificar_senha_proximo_logon' => $data['modificar_senha_proximo_logon'] ?? 'Não',
                ];
                \App\adms\Models\Services\LogAlteracaoService::registrarAlteracao(
                    'adms_users',
                    $novoId,
                    $_SESSION['user_id'] ?? 0,
                    'insert',
                    [],
                    $dadosDepois
                );
            }
            return $novoId;
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Usuário não cadastrado.", ['username' => $data['username'], 'email' => $data['email'], 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Atualizar os dados de um usuário existente.
     *
     * Este método atualiza as informações de um usuário existente. Se a senha for fornecida, ela também será atualizada.
     * Em caso de erro, um log é gerado.
     *
     * @param array $data Dados atualizados do usuário, incluindo `id`, `name`, `email`, `username`, e opcionalmente `password`.
     * @return bool `true` se a atualização foi bem-sucedida ou `false` em caso de erro.
     */
    public function updateUser(array $data): bool
    {
        try {
            // Debug: verificar dados recebidos
            error_log("DEBUG updateUser - Dados recebidos: " . print_r($data, true));
            // Preencher campos ausentes mínimos p/ updates parciais vindos de import
            $defaults = ['status'=>null,'bloqueado'=>null,'senha_nunca_expira'=>null,'modificar_senha_proximo_logon'=>null,'tentativas_login'=>null];
            $data = array_merge($defaults, $data);
            
            // Captura os dados antigos antes da alteração
            $dadosAntes = $this->getUser($data['id']);

            // QUERY para atualizar o usuário
            $sql = 'UPDATE adms_users SET name = :name, email = :email, username = :username, user_department_id = :user_department_id, user_position_id = :user_position_id, updated_at = :updated_at';
            if (isset($data['status'])) {
                $sql .= ', status = :status';
            }
            if (isset($data['bloqueado'])) {
                $sql .= ', bloqueado = :bloqueado';
            }
            if (isset($data['senha_nunca_expira'])) {
                $sql .= ', senha_nunca_expira = :senha_nunca_expira';
            }
            if (isset($data['modificar_senha_proximo_logon'])) {
                $sql .= ', modificar_senha_proximo_logon = :modificar_senha_proximo_logon';
            }
            if (!empty($data['image']) && is_array($data['image'])) {
                error_log("DEBUG updateUser - Processando imagem: " . print_r($data['image'], true));
                
                // Processar upload da nova imagem
                if ($this->upload($data, $data['image'])) {
                    error_log("DEBUG updateUser - Upload realizado com sucesso");
                    
                    // Deletar imagem antiga se existir
                    $this->deleteImage($data);
                    error_log("DEBUG updateUser - Imagem antiga deletada");
                    
                    // Obter nome da nova imagem
                    $slugImg = new \App\adms\Helpers\SlugImg();
                    $nameImgFormatad = $slugImg->slug($data['image']['name']);
                    error_log("DEBUG updateUser - Nome da nova imagem: " . $nameImgFormatad);
                    
                    $sql .= ', image = :image';
                    $data['image'] = $nameImgFormatad;
                } else {
                    error_log("DEBUG updateUser - Falha no upload da imagem");
                    return false;
                }
            } else {
                error_log("DEBUG updateUser - Sem imagem para processar ou não é array");
            }
            if (!empty($data['data_nascimento'])) {
                $sql .= ', data_nascimento = :data_nascimento';
            }
            if (isset($data['bloqueado']) && $data['bloqueado'] === 'Não' && isset($dadosAntes['bloqueado']) && $dadosAntes['bloqueado'] === 'Sim') {
                $sql .= ', tentativas_login = 0, data_bloqueio_temporario = NULL';
            }
            $sql .= ' WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
            $stmt->bindValue(':email', $data['email'], PDO::PARAM_STR);
            $stmt->bindValue(':username', $data['username'], PDO::PARAM_STR);
            $stmt->bindValue(':user_department_id', (int)$data['user_department_id'], PDO::PARAM_INT);
            $stmt->bindValue(':user_position_id', (int)$data['user_position_id'], PDO::PARAM_INT);
            $stmt->bindValue(':updated_at', date("Y-m-d H:i:s"));
            if (isset($data['status'])) {
                $stmt->bindValue(':status', $data['status'], PDO::PARAM_STR);
            }
            if (isset($data['bloqueado'])) {
                $stmt->bindValue(':bloqueado', $data['bloqueado'], PDO::PARAM_STR);
            }
            if (isset($data['senha_nunca_expira'])) {
                $stmt->bindValue(':senha_nunca_expira', $data['senha_nunca_expira'], PDO::PARAM_STR);
            }
            if (isset($data['modificar_senha_proximo_logon'])) {
                $stmt->bindValue(':modificar_senha_proximo_logon', $data['modificar_senha_proximo_logon'], PDO::PARAM_STR);
            }
            if (!empty($data['image']) && !is_array($data['image'])) {
                $stmt->bindValue(':image', $data['image'], PDO::PARAM_STR);
            }
            if (!empty($data['data_nascimento'])) {
                $stmt->bindValue(':data_nascimento', $data['data_nascimento'], PDO::PARAM_STR);
            }
            $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);
            if (!empty($data['password'])) {
                $stmt->bindValue(':password', password_hash($data['password'], PASSWORD_DEFAULT));
            }
            $result = $stmt->execute();
            if (!$result) {
                $err = $stmt->errorInfo();
                GenerateLog::generateLog("error", "DEBUG updateUser - Execução falhou.", [
                    'id' => $data['id'] ?? null,
                    'errorInfo' => $err,
                ]);
            }
            // Se atualização bem-sucedida, registra o log de alteração
            if ($result) {
                // Monta os dados depois da alteração (agora com todos os campos relevantes)
                $dadosDepois = [
                    'id' => $data['id'],
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'username' => $data['username'],
                    'user_department_id' => $data['user_department_id'],
                    'user_position_id' => $data['user_position_id'],
                    'status' => $data['status'] ?? $dadosAntes['status'] ?? null,
                    'bloqueado' => $data['bloqueado'] ?? $dadosAntes['bloqueado'] ?? null,
                    'tentativas_login' => isset($data['tentativas_login']) ? $data['tentativas_login'] : ($dadosAntes['tentativas_login'] ?? null),
                    'senha_nunca_expira' => $data['senha_nunca_expira'] ?? $dadosAntes['senha_nunca_expira'] ?? null,
                    'modificar_senha_proximo_logon' => $data['modificar_senha_proximo_logon'] ?? $dadosAntes['modificar_senha_proximo_logon'] ?? null,
                ];
                \App\adms\Models\Services\LogAlteracaoService::registrarAlteracao(
                    'adms_users',
                    $data['id'],
                    $_SESSION['user_id'] ?? 0,
                    'update',
                    $dadosAntes,
                    $dadosDepois
                );
            }
            return $result;
        } catch (Exception $e) {
            \App\adms\Helpers\GenerateLog::generateLog("error", "Usuário não editado, nenhum valor foi alterado.", ['id' => $data['id'], 'email' => $data['email'], 'username' => $data['username'], 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Atualizar imagem do usuário pelo botão "Editar Imagem" (UpdateUserImage).
     *
     * Este método é usado especificamente para o botão "Editar Imagem" na view do usuário.
     * Ele processa o upload da nova imagem e remove a antiga.
     *
     * @param array $data Dados contendo o ID do usuário e a nova imagem.
     * @return bool `true` se a imagem foi atualizada com sucesso ou `false` em caso de erro.
     */
    public function updateUserImage(array $data): bool
    {
        try {
            // Debug: verificar dados recebidos
            error_log("DEBUG updateUserImage - Dados recebidos: " . print_r($data, true));
            
            // Captura os dados antigos antes da alteração
            $dadosAntes = $this->getUser($data['id']);
            error_log("DEBUG updateUserImage - Dados antigos: " . print_r($dadosAntes, true));
            
            // Verificar se há imagem para processar
            if (!isset($data['image']) || empty($data['image']['name'])) {
                error_log("DEBUG updateUserImage - Imagem não encontrada ou vazia");
                $this->data['errors'][] = "Erro: Necessário selecionar uma imagem válida!";
                return false;
            }
            
            // Validar tipo de imagem (usar MIME real quando disponível)
            $detectedType = $data['image']['type'] ?? '';
            if (function_exists('finfo_open') && is_uploaded_file($data['image']['tmp_name'] ?? '')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $realMime = finfo_file($finfo, $data['image']['tmp_name']);
                finfo_close($finfo);
                if (!empty($realMime)) {
                    $detectedType = $realMime;
                }
            }

            $valExtImg = new ValExtImg();
            $valExtImg->validateExtImg($detectedType);
            error_log("DEBUG updateUserImage - Validação da imagem: " . ($valExtImg->getResult() ? 'SUCESSO' : 'FALHOU'));
            
            if (!$valExtImg->getResult()) {
                error_log("DEBUG updateUserImage - Formato de imagem não suportado: " . ($detectedType ?: 'desconhecido') . ' size=' . ($data['image']['size'] ?? 'null'));
                $this->data['errors'][] = "Erro: Formato de imagem não suportado! Use JPG, PNG ou GIF.";
                return false;
            }
            
            // Processar upload da nova imagem
            error_log("DEBUG updateUserImage - Iniciando upload...");
            if ($this->upload($data, $data['image'])) {
                error_log("DEBUG updateUserImage - Upload realizado com sucesso");
                // Atualizar banco com nova imagem
                $slugImg = new SlugImg();
                $nameImgFormatad = $slugImg->slug($data['image']['name']);
                
                $sql = 'UPDATE adms_users SET image = :image, updated_at = :updated_at WHERE id = :id';
                $stmt = $this->getConnection()->prepare($sql);
                $stmt->bindValue(':image', $nameImgFormatad, PDO::PARAM_STR);
                $stmt->bindValue(':updated_at', date("Y-m-d H:i:s"));
                $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);
                
                $result = $stmt->execute();
                
                if ($result) {
                    // SÓ deletar a imagem antiga DEPOIS de salvar no banco com sucesso
                    $this->deleteImage($data);
                    
                    // Log de alteração
                    $dadosDepois = [
                        'id' => $data['id'],
                        'image' => $nameImgFormatad,
                        'updated_at' => date("Y-m-d H:i:s")
                    ];
                    
                    \App\adms\Models\Services\LogAlteracaoService::registrarAlteracao(
                        'adms_users',
                        $data['id'],
                        $_SESSION['user_id'] ?? 0,
                        'update_user_image',
                        $dadosAntes ?: [],
                        $dadosDepois
                    );
                    
                    return true;
                }
            }
            
            return false;
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Imagem do usuário não foi atualizada.", ['id' => $data['id'], 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Atualizar apenas a imagem do perfil do usuário logado.
     *
     * Este método é específico para o perfil do usuário, atualizando apenas o caminho da imagem
     * no banco de dados. É mais simples que updateUserImage() usado por administradores.
     *
     * @param array $data Dados contendo o ID do usuário e o caminho da nova imagem.
     * @return bool `true` se a imagem foi atualizada com sucesso ou `false` em caso de erro.
     */
    public function updateUserProfileImage(array $data): bool
    {
        try {
            // Captura os dados antigos antes da alteração
            $dadosAntes = $this->getUser($data['id']);

            // SEMPRE deletar a imagem antiga antes de atualizar
            $this->deleteImage($data);

            // QUERY para atualizar apenas a imagem do usuário
            $sql = 'UPDATE adms_users SET image = :image, updated_at = :updated_at WHERE id = :id';
            
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':image', $data['image'], PDO::PARAM_STR);
            $stmt->bindValue(':updated_at', date("Y-m-d H:i:s"));
            $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);
            
            $result = $stmt->execute();
            
            // Se atualização bem-sucedida, registra o log de alteração
            if ($result) {
                $dadosDepois = [
                    'id' => $data['id'],
                    'image' => $data['image'],
                    'updated_at' => date("Y-m-d H:i:s")
                ];
                
                \App\adms\Models\Services\LogAlteracaoService::registrarAlteracao(
                    'adms_users',
                    $data['id'],
                    $_SESSION['user_id'] ?? 0,
                    'update_profile_image',
                    $dadosAntes ?: [],
                    $dadosDepois
                );
            }
            
            return $result;
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Imagem do perfil não foi atualizada.", ['id' => $data['id'], 'error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Atualizar imagem do usuário na edição geral (UpdateUser).
     * 
     * Este método é usado quando o administrador edita um usuário e altera a imagem.
     * Ele processa o upload da nova imagem e remove a antiga.
     *
     * @param array $data Dados contendo o ID do usuário e a nova imagem.
     * @return bool `true` se a imagem foi atualizada com sucesso ou `false` em caso de erro.
     */
    public function updateUserGeneralImage(array $data): bool
    {
        try {
            // Captura os dados antigos antes da alteração
            $dadosAntes = $this->getUser($data['id']);
            
            // Verificar se há imagem para processar
            if (!isset($data['image']) || empty($data['image']['name'])) {
                // Se não há imagem, definir como icon_user.png
                $data['image'] = 'users/icon_user.png';
                
                // Deletar imagem antiga se existir
                $this->deleteImage($data);
                
                // Atualizar banco para icon_user.png
                $sql = 'UPDATE adms_users SET image = :image, updated_at = :updated_at WHERE id = :id';
                $stmt = $this->getConnection()->prepare($sql);
                $stmt->bindValue(':image', $data['image'], PDO::PARAM_STR);
                $stmt->bindValue(':updated_at', date("Y-m-d H:i:s"));
                $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);
                
                $result = $stmt->execute();
            } else {
                // Processar upload da nova imagem
                if ($this->upload($data, $data['image'])) {
                    // Deletar imagem antiga
                    $this->deleteImage($data);
                    
                    // Atualizar banco com nova imagem
                    $slugImg = new SlugImg();
                    $nameImgFormatad = $slugImg->slug($data['image']['name']);
                    
                    $sql = 'UPDATE adms_users SET image = :image, updated_at = :updated_at WHERE id = :id';
                    $stmt = $this->getConnection()->prepare($sql);
                    $stmt->bindValue(':image', $nameImgFormatad, PDO::PARAM_STR);
                    $stmt->bindValue(':updated_at', date("Y-m-d H:i:s"));
                    $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);
                    
                    $result = $stmt->execute();
                } else {
                    return false;
                }
            }
            
            // Se atualização bem-sucedida, registra o log de alteração
            if ($result) {
                $dadosDepois = [
                    'id' => $data['id'],
                    'image' => $data['image'],
                    'updated_at' => date("Y-m-d H:i:s")
                ];
                
                \App\adms\Models\Services\LogAlteracaoService::registrarAlteracao(
                    'adms_users',
                    $data['id'],
                    $_SESSION['user_id'] ?? 0,
                    'update_user_general_image',
                    $dadosAntes ?: [],
                    $dadosDepois
                );
            }
            
            return $result;
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Imagem do usuário não foi atualizada na edição geral.", ['id' => $data['id'], 'error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Remover imagem do usuário (definir como icon_user.png).
     * 
     * Este método é usado para remover a imagem do usuário, definindo-a como icon_user.png
     * e removendo o arquivo físico da pasta.
     *
     * @param array $data Dados contendo o ID do usuário.
     * @return bool `true` se a imagem foi removida com sucesso ou `false` em caso de erro.
     */
    public function removeUserImage(array $data): bool
    {
        try {
            // Captura os dados antigos antes da alteração
            $dadosAntes = $this->getUser($data['id']);
            
            // Deletar imagem antiga se existir
            $this->deleteImage($data);
            
            // Atualizar banco para icon_user.png
            $sql = 'UPDATE adms_users SET image = :image, updated_at = :updated_at WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':image', 'users/icon_user.png', PDO::PARAM_STR);
            $stmt->bindValue(':updated_at', date("Y-m-d H:i:s"));
            $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);
            
            $result = $stmt->execute();
            
            // Se atualização bem-sucedida, registra o log de alteração
            if ($result) {
                $dadosDepois = [
                    'id' => $data['id'],
                    'image' => 'users/icon_user.png',
                    'updated_at' => date("Y-m-d H:i:s")
                ];
                
                \App\adms\Models\Services\LogAlteracaoService::registrarAlteracao(
                    'adms_users',
                    $data['id'],
                    $_SESSION['user_id'] ?? 0,
                    'remove_user_image',
                    $dadosAntes ?: [],
                    $dadosDepois
                );
            }
            
            return $result;
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Imagem do usuário não foi removida.", ['id' => $data['id'], 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Metodo gera o slug da imagem com o helper SlugImg
     * Faz o upload da imagem usando o helper AdmsUploadImgRes
     * Chama o metodo edit para atualizar as informações no banco de dados
     * @return void
     */
    private function upload(array $data, array $dataImage): bool
    {
        // Validação de segurança
        if (!is_array($dataImage) || !isset($dataImage['name']) || !isset($dataImage['tmp_name'])) {
            return false;
        }
        
        $slugImg = new SlugImg();
        $this->nameImg = $slugImg->slug($dataImage['name']);

        $directory = "public/adms/uploads/users/" . $data['id'] . "/";

        $uploadImgRes = new Upload();
        $result = $uploadImgRes->upload($directory, $dataImage['tmp_name'], $this->nameImg, 300, 300);

        if ($result && $uploadImgRes->getResult()) {
            return true;
        }
        return false;
    }

    /**
     * Método para apagar a imagem antiga do usuário
     * @param array $data
     * @return bool
     */
    private function deleteImage(array $data): bool
    {
        // Garante que o ID do usuário foi passado corretamente
        if (!isset($data['id']) || empty($data['id'])) {
            return false;
        }

        // Obtém os dados do usuário pelo método getUser()
        $user = $this->getUser($data['id']);

        // Verifique se o usuário existe
        if (!$user) {
            return false;
        }

        // SEMPRE tentar deletar a imagem antiga se existir
        if (!empty($user['image'])) {
            $oldImagePath = "public/adms/uploads/users/" . $data['id'] . "/" . $user['image'];
            
            // Verifica se o arquivo realmente existe antes de tentar excluir
            if (file_exists($oldImagePath)) {
                // Tentar excluir a imagem antiga
                if (unlink($oldImagePath)) {
                    // Log de sucesso (opcional)
                    return true;
                }
            }
        }
        
        // Limpar diretório de imagens antigas não referenciadas
        $this->cleanUserImageDirectory($data['id']);
        
        // Retorna true mesmo se não houver imagem para deletar
        return true;
    }
    
    /**
     * Limpa o diretório de imagens do usuário, removendo arquivos não referenciados no banco
     * @param int $userId ID do usuário
     * @return void
     */
    private function cleanUserImageDirectory(int $userId): void
    {
        $userDir = "public/adms/uploads/users/" . $userId . "/";
        
        if (!is_dir($userDir)) {
            return;
        }
        
        // Obter dados do usuário para saber qual imagem está ativa
        $user = $this->getUser($userId);
        $activeImage = $user['image'] ?? null;
        
        // Listar todos os arquivos no diretório
        $files = glob($userDir . "*");
        
        foreach ($files as $file) {
            $fileName = basename($file);
            
            // Não deletar o diretório ou arquivos especiais
            if (is_dir($file) || $fileName === '.' || $fileName === '..') {
                continue;
            }
            
            // Não deletar a imagem ativa no banco
            if ($activeImage && $fileName === $activeImage) {
                continue;
            }
            
            // Deletar arquivos antigos não referenciados
            if (unlink($file)) {
                // Log opcional de limpeza
                GenerateLog::generateLog("info", "Imagem antiga removida durante limpeza.", [
                    'user_id' => $userId,
                    'file' => $fileName
                ]);
            }
        }
    }




    /**
     * Atualizar a senha de um usuário.
     *
     * Este método atualiza a senha de um usuário específico. Em caso de erro, um log é gerado.
     *
     * @param array $data Dados atualizados do usuário, incluindo `id` e `password`.
     * @return bool `true` se a atualização foi bem-sucedida ou `false` em caso de erro.
     */
    public function updatePasswordUser(array $data): bool
    {
        try {
            // Atualizar senha e modificar_senha_proximo_logon
            $sql = 'UPDATE adms_users SET password = :password, modificar_senha_proximo_logon = "Não", updated_at = :updated_at WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':password', password_hash($data['password'], PASSWORD_DEFAULT));
            $stmt->bindValue(':updated_at', date("Y-m-d H:i:s"));
            $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);
            return $stmt->execute();
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Senha não editada.", ['id' => $data['id'], 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Deletar um usuário pelo ID.
     *
     * Este método remove um usuário específico da tabela `adms_users`. Em caso de erro, um log é gerado.
     *
     * @param int $id ID do usuário a ser deletado.
     * @return bool `true` se o usuário foi deletado com sucesso ou `false` em caso de erro.
     */
    public function deleteUser(int $id): bool
    {
        try {
            // Captura os dados antigos antes da exclusão
            $dadosAntes = $this->getUser($id);
            $sql = 'DELETE FROM adms_users WHERE id = :id LIMIT 1';
            $stms = $this->getConnection()->prepare($sql);
            $stms->bindValue(':id', $id, PDO::PARAM_INT);
            $stms->execute();
            $affectedRows = $stms->rowCount();
            if ($affectedRows > 0) {
                // Log de exclusão
                \App\adms\Models\Services\LogAlteracaoService::registrarAlteracao(
                    'adms_users',
                    $id,
                    $_SESSION['user_id'] ?? 0,
                    'delete',
                    $dadosAntes ?: [],
                    []
                );
                return true;
            } else {
                GenerateLog::generateLog("error", "Usuário não apagado.", ['id' => $id]);
                return false;
            }
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Usuário não apagado.", ['id' => $id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function getUserDepartments(int $id): array|bool
    {
        // QUERY para recuperar o registro do banco de dados
        $sql = 'SELECT t1.name
                FROM adms_users t0
                INNER JOIN adms_departments t1 ON t0.user_department_id = t1.id
                WHERE t1.id = :id
                ORDER BY t1.id DESC';

        // Preparar a quey
        $stmt = $this->getConnection()->prepare($sql);

        // Substituir o link pelo valor
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        // Executar a query
        $stmt->execute();

        // Ler os registros e retornar
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllUsersSelect(): array
    {
        // QUERY para recuperar os registros do banco de dados
        $sql = 'SELECT id, name, email FROM adms_users ORDER BY name ASC';

        // Preparar a QUERY
        $stmt = $this->getConnection()->prepare($sql);

        // Executar a QUERY
        $stmt->execute();

        // Ler os registros e retornar
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna usuários por cargo específico
     */
    public function getUsersByPosition(int $positionId): array
    {
        $sql = 'SELECT id, name, email, user_department_id, user_position_id 
                FROM adms_users 
                WHERE user_position_id = :position_id 
                ORDER BY name ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':position_id', $positionId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Retorna o total de usuários
     */
    public function getTotalUsers(): int
    {
        $sql = 'SELECT COUNT(*) as total FROM adms_users';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        return (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    }

    /**
     * Recupera todos os usuários para uso em formulários (select).
     *
     * @return array Lista de usuários para select
     */
    public function getAllUsersForSelect(): array
    {
        $sql = 'SELECT id, name, email FROM adms_users ORDER BY name ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Atualizar o perfil do usuário (dados pessoais).
     *
     * Este método atualiza as informações pessoais do usuário, incluindo nome, email, data de nascimento e imagem.
     * Não permite alterar senha, status ou outras configurações administrativas.
     *
     * @param array $data Dados do usuário a ser atualizado.
     * @return bool `true` se o usuário foi atualizado com sucesso ou `false` em caso de erro.
     */
    public function updateUserProfile(array $data): bool
    {
        try {
            // Captura os dados antigos antes da alteração
            $dadosAntes = $this->getUser($data['id']);

            // QUERY para atualizar o perfil do usuário
            $sql = 'UPDATE adms_users SET name = :name, email = :email, data_nascimento = :data_nascimento, updated_at = :updated_at';
            
            if (!empty($data['image'])) {
                $sql .= ', image = :image';
            }
            
            $sql .= ' WHERE id = :id';
            
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
            $stmt->bindValue(':email', $data['email'], PDO::PARAM_STR);
            $stmt->bindValue(':data_nascimento', $data['data_nascimento'], PDO::PARAM_STR);
            $stmt->bindValue(':updated_at', date("Y-m-d H:i:s"));
            $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);
            
            if (!empty($data['image'])) {
                $stmt->bindValue(':image', $data['image'], PDO::PARAM_STR);
            }
            
            $result = $stmt->execute();
            
            // Se atualização bem-sucedida, registra o log de alteração
            if ($result) {
                $dadosDepois = [
                    'id' => $data['id'],
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'data_nascimento' => $data['data_nascimento'],
                    'image' => $data['image'] ?? $dadosAntes['image'],
                    'updated_at' => date("Y-m-d H:i:s")
                ];
                
                \App\adms\Models\Services\LogAlteracaoService::registrarAlteracao(
                    'adms_users',
                    $data['id'],
                    $_SESSION['user_id'] ?? 0,
                    'update',
                    $dadosAntes ?: [],
                    $dadosDepois
                );
            }
            
            return $result;
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Perfil do usuário não editado.", ['id' => $data['id'], 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Atualizar apenas a imagem do usuário
     *
     * @param array $data Dados contendo ID e nova imagem
     * @return bool true se sucesso, false se falha
     */
    public function updateUserImageOnly(array $data): bool
    {
        try {
            $sql = 'UPDATE adms_users SET image = :image, updated_at = :updated_at WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':image', $data['image'], PDO::PARAM_STR);
            $stmt->bindValue(':updated_at', date("Y-m-d H:i:s"));
            $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);
            
            $result = $stmt->execute();
            
            if ($result) {
                error_log("UsersRepository: Imagem atualizada com sucesso para usuário ID: " . $data['id']);
                return true;
            }
            
            error_log("UsersRepository: Falha ao atualizar imagem para usuário ID: " . $data['id']);
            return false;
            
        } catch (Exception $e) {
            error_log("UsersRepository: Erro ao atualizar imagem: " . $e->getMessage());
            return false;
        }
    }


}
