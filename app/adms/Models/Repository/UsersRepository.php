<?php

namespace App\adms\Models\Repository;

use App\adms\Controllers\Services\Validation\ValidationEmptyField;
use App\adms\Helpers\GenerateLog;
use App\adms\Helpers\SlugImg;
use App\adms\Helpers\Upload;
use App\adms\Helpers\ValExtImg;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Repository\AdmsPasswordPolicyRepository;
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
    /**
     * Login ignorado no organograma e nas contagens (ex.: usuário técnico "manager" do seed).
     */
    private const ORGCHART_EXCLUDED_USERNAME = 'manager';

    /** @var array<string, int>|null slug (minúsculo) => id do departamento */
    private static ?array $timelineDeptSlugToIdCache = null;

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
        if (!empty($filtros['usuario'])) {
            $where[] = 'usr.username LIKE :usuario';
            $params[':usuario'] = '%' . $filtros['usuario'] . '%';
        }
        if (!empty($filtros['departamento_id']) && is_numeric($filtros['departamento_id'])) {
            $where[] = 'usr.user_department_id = :departamento_id';
            $params[':departamento_id'] = (int)$filtros['departamento_id'];
        }
        if (!empty($filtros['cargo_id']) && is_numeric($filtros['cargo_id'])) {
            $where[] = 'usr.user_position_id = :cargo_id';
            $params[':cargo_id'] = (int)$filtros['cargo_id'];
        }
        if (!empty($filtros['status']) && in_array($filtros['status'], ['Ativo', 'Inativo'])) {
            $where[] = 'usr.status = :status';
            $params[':status'] = $filtros['status'];
        }
        if (isset($filtros['bloqueado']) && $filtros['bloqueado'] !== '' && $filtros['bloqueado'] !== null) {
            $where[] = 'usr.bloqueado = :bloqueado';
            $params[':bloqueado'] = ($filtros['bloqueado'] == '1' || $filtros['bloqueado'] === 1) ? 1 : 0;
        }
        
        // Filtro de desligado (baseado em data_desligamento)
        if (isset($filtros['desligado']) && $filtros['desligado'] !== '' && $filtros['desligado'] !== null) {
            if ($filtros['desligado'] == '1' || $filtros['desligado'] === 1) {
                // Filtrar apenas desligados (com data_desligamento)
                $where[] = 'usr.data_desligamento IS NOT NULL';
            } else {
                // Filtrar apenas não desligados (sem data_desligamento)
                $where[] = 'usr.data_desligamento IS NULL';
            }
        }

        // Filtro de período (de/até) por tipo selecionado
        $periodoTipo = $filtros['periodo_tipo'] ?? '';
        $dataDe = $filtros['data_de'] ?? '';
        $dataAte = $filtros['data_ate'] ?? '';
        if ($periodoTipo === 'atualizacao_cargos') {
            $sqlExists = "EXISTS (
                SELECT 1
                FROM adms_log_alteracoes log
                INNER JOIN adms_log_alteracoes_detalhes det ON det.log_alteracao_id = log.id
                WHERE log.tabela = 'adms_users'
                  AND log.objeto_id = usr.id
                  AND log.tipo_operacao = 'UPDATE'
                  AND det.campo = 'user_position_id'";
            if (!empty($dataDe)) {
                $sqlExists .= " AND DATE(log.data_alteracao) >= :periodo_data_de";
                $params[':periodo_data_de'] = $dataDe;
            }
            if (!empty($dataAte)) {
                $sqlExists .= " AND DATE(log.data_alteracao) <= :periodo_data_ate";
                $params[':periodo_data_ate'] = $dataAte;
            }
            $sqlExists .= ")";
            $where[] = $sqlExists;
        } else {
            $periodoMap = [
                'admissao' => 'usr.data_admissao',
                'desligamento' => 'usr.data_desligamento',
            ];
            if (isset($periodoMap[$periodoTipo])) {
                $periodoField = $periodoMap[$periodoTipo];
                if (!empty($dataDe)) {
                    $where[] = "DATE({$periodoField}) >= :periodo_data_de";
                    $params[':periodo_data_de'] = $dataDe;
                }
                if (!empty($dataAte)) {
                    $where[] = "DATE({$periodoField}) <= :periodo_data_ate";
                    $params[':periodo_data_ate'] = $dataAte;
                }
            }
        }
        
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = 'SELECT usr.id, usr.name, usr.email, usr.username, usr.cpf, usr.celular, usr.user_department_id, usr.user_position_id, usr.status, usr.bloqueado, usr.tentativas_login, usr.senha_nunca_expira, usr.modificar_senha_proximo_logon, usr.data_admissao, usr.data_desligamento, usr.motivo_desligamento, dep.name name_dep, pos.name name_pos
                FROM adms_users usr
                LEFT JOIN adms_departments dep ON usr.user_department_id = dep.id
                LEFT JOIN adms_positions pos ON usr.user_position_id = pos.id 
                ' . $whereSql . '
                ORDER BY usr.name ASC
                LIMIT :limit OFFSET :offset';
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $paramType);
        }
        $stmt->bindValue(':limit', $limitResult, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Recuperar todos os usuários para exportação (sem paginação), respeitando os mesmos filtros da listagem.
     *
     * @param array $filtros
     * @return array<int, array<string,mixed>>
     */
    public function getAllUsersForExport(array $filtros = []): array
    {
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
        if (!empty($filtros['usuario'])) {
            $where[] = 'usr.username LIKE :usuario';
            $params[':usuario'] = '%' . $filtros['usuario'] . '%';
        }
        if (!empty($filtros['departamento_id']) && is_numeric($filtros['departamento_id'])) {
            $where[] = 'usr.user_department_id = :departamento_id';
            $params[':departamento_id'] = (int)$filtros['departamento_id'];
        }
        if (!empty($filtros['cargo_id']) && is_numeric($filtros['cargo_id'])) {
            $where[] = 'usr.user_position_id = :cargo_id';
            $params[':cargo_id'] = (int)$filtros['cargo_id'];
        }
        if (!empty($filtros['status']) && in_array($filtros['status'], ['Ativo', 'Inativo'])) {
            $where[] = 'usr.status = :status';
            $params[':status'] = $filtros['status'];
        }
        if (isset($filtros['bloqueado']) && $filtros['bloqueado'] !== '' && $filtros['bloqueado'] !== null) {
            $where[] = 'usr.bloqueado = :bloqueado';
            $params[':bloqueado'] = ($filtros['bloqueado'] == '1' || $filtros['bloqueado'] === 1) ? 1 : 0;
        }

        if (isset($filtros['desligado']) && $filtros['desligado'] !== '' && $filtros['desligado'] !== null) {
            if ($filtros['desligado'] == '1' || $filtros['desligado'] === 1) {
                $where[] = 'usr.data_desligamento IS NOT NULL';
            } else {
                $where[] = 'usr.data_desligamento IS NULL';
            }
        }

        // Filtro de período (de/até) por tipo selecionado
        $periodoTipo = $filtros['periodo_tipo'] ?? '';
        $dataDe = $filtros['data_de'] ?? '';
        $dataAte = $filtros['data_ate'] ?? '';
        if ($periodoTipo === 'atualizacao_cargos') {
            $sqlExists = "EXISTS (
                SELECT 1
                FROM adms_log_alteracoes log
                INNER JOIN adms_log_alteracoes_detalhes det ON det.log_alteracao_id = log.id
                WHERE log.tabela = 'adms_users'
                  AND log.objeto_id = usr.id
                  AND log.tipo_operacao = 'UPDATE'
                  AND det.campo = 'user_position_id'";
            if (!empty($dataDe)) {
                $sqlExists .= " AND DATE(log.data_alteracao) >= :periodo_data_de";
                $params[':periodo_data_de'] = $dataDe;
            }
            if (!empty($dataAte)) {
                $sqlExists .= " AND DATE(log.data_alteracao) <= :periodo_data_ate";
                $params[':periodo_data_ate'] = $dataAte;
            }
            $sqlExists .= ")";
            $where[] = $sqlExists;
        } else {
            $periodoMap = [
                'admissao' => 'usr.data_admissao',
                'desligamento' => 'usr.data_desligamento',
            ];
            if (isset($periodoMap[$periodoTipo])) {
                $periodoField = $periodoMap[$periodoTipo];
                if (!empty($dataDe)) {
                    $where[] = "DATE({$periodoField}) >= :periodo_data_de";
                    $params[':periodo_data_de'] = $dataDe;
                }
                if (!empty($dataAte)) {
                    $where[] = "DATE({$periodoField}) <= :periodo_data_ate";
                    $params[':periodo_data_ate'] = $dataAte;
                }
            }
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = 'SELECT usr.id, usr.name, usr.email, usr.username, usr.cpf, usr.celular, usr.user_department_id, usr.user_position_id, usr.status, usr.bloqueado, usr.tentativas_login, usr.senha_nunca_expira, usr.modificar_senha_proximo_logon, usr.data_admissao, usr.data_desligamento, usr.motivo_desligamento, dep.name name_dep, pos.name name_pos
                FROM adms_users usr
                LEFT JOIN adms_departments dep ON usr.user_department_id = dep.id
                LEFT JOIN adms_positions pos ON usr.user_position_id = pos.id 
                ' . $whereSql . '
                ORDER BY usr.name ASC';

        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $paramType);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Buscar usuário por username (chave única de importação)
     */
    public function getUserByUsername(string $username): array|false
    {
        $sql = 'SELECT 
                    t0.id, 
                    t0.name, 
                    t0.email, 
                    t0.username, 
                    t0.cpf,
                    t0.celular,
                    t0.image, 
                    t0.data_nascimento, 
                    t0.user_department_id, 
                    t0.user_position_id, 
                    t0.immediate_supervisor_id,
                    t0.created_at, 
                    t0.updated_at, 
                    t0.status,
                    t0.bloqueado,
                    t0.tentativas_login,
                    t0.senha_nunca_expira,
                    t0.modificar_senha_proximo_logon,
                    t0.data_admissao,
                    t0.data_desligamento,
                    t0.motivo_desligamento
                FROM adms_users t0
                WHERE t0.username = :username
                LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        return $u ?: false;
    }

    // getUserByEmailUsernameOrCpf mantido apenas para compatibilidade com código antigo.

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
            $where[] = 'usr.name LIKE :nome';
            $params[':nome'] = '%' . $filtros['nome'] . '%';
        }
        if (!empty($filtros['email'])) {
            $where[] = 'usr.email LIKE :email';
            $params[':email'] = '%' . $filtros['email'] . '%';
        }
        if (!empty($filtros['usuario'])) {
            $where[] = 'usr.username LIKE :usuario';
            $params[':usuario'] = '%' . $filtros['usuario'] . '%';
        }
        if (!empty($filtros['departamento_id']) && is_numeric($filtros['departamento_id'])) {
            $where[] = 'usr.user_department_id = :departamento_id';
            $params[':departamento_id'] = (int)$filtros['departamento_id'];
        }
        if (!empty($filtros['cargo_id']) && is_numeric($filtros['cargo_id'])) {
            $where[] = 'usr.user_position_id = :cargo_id';
            $params[':cargo_id'] = (int)$filtros['cargo_id'];
        }
        if (!empty($filtros['status']) && in_array($filtros['status'], ['Ativo', 'Inativo'])) {
            $where[] = 'usr.status = :status';
            $params[':status'] = $filtros['status'];
        }
        if (isset($filtros['bloqueado']) && $filtros['bloqueado'] !== '' && $filtros['bloqueado'] !== null) {
            $where[] = 'usr.bloqueado = :bloqueado';
            $params[':bloqueado'] = ($filtros['bloqueado'] == '1' || $filtros['bloqueado'] === 1) ? 1 : 0;
        }
        
        // Filtro de desligado (baseado em data_desligamento)
        if (isset($filtros['desligado']) && $filtros['desligado'] !== '' && $filtros['desligado'] !== null) {
            if ($filtros['desligado'] == '1' || $filtros['desligado'] === 1) {
                // Filtrar apenas desligados (com data_desligamento)
                $where[] = 'usr.data_desligamento IS NOT NULL';
            } else {
                // Filtrar apenas não desligados (sem data_desligamento)
                $where[] = 'usr.data_desligamento IS NULL';
            }
        }

        // Filtro de período (de/até) por tipo selecionado
        $periodoTipo = $filtros['periodo_tipo'] ?? '';
        $dataDe = $filtros['data_de'] ?? '';
        $dataAte = $filtros['data_ate'] ?? '';
        if ($periodoTipo === 'atualizacao_cargos') {
            $sqlExists = "EXISTS (
                SELECT 1
                FROM adms_log_alteracoes log
                INNER JOIN adms_log_alteracoes_detalhes det ON det.log_alteracao_id = log.id
                WHERE log.tabela = 'adms_users'
                  AND log.objeto_id = usr.id
                  AND log.tipo_operacao = 'UPDATE'
                  AND det.campo = 'user_position_id'";
            if (!empty($dataDe)) {
                $sqlExists .= " AND DATE(log.data_alteracao) >= :periodo_data_de";
                $params[':periodo_data_de'] = $dataDe;
            }
            if (!empty($dataAte)) {
                $sqlExists .= " AND DATE(log.data_alteracao) <= :periodo_data_ate";
                $params[':periodo_data_ate'] = $dataAte;
            }
            $sqlExists .= ")";
            $where[] = $sqlExists;
        } else {
            $periodoMap = [
                'admissao' => 'usr.data_admissao',
                'desligamento' => 'usr.data_desligamento',
            ];
            if (isset($periodoMap[$periodoTipo])) {
                $periodoField = $periodoMap[$periodoTipo];
                if (!empty($dataDe)) {
                    $where[] = "DATE({$periodoField}) >= :periodo_data_de";
                    $params[':periodo_data_de'] = $dataDe;
                }
                if (!empty($dataAte)) {
                    $where[] = "DATE({$periodoField}) <= :periodo_data_ate";
                    $params[':periodo_data_ate'] = $dataAte;
                }
            }
        }
        
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = 'SELECT COUNT(usr.id) as amount_records 
                FROM adms_users usr
                LEFT JOIN adms_departments dep ON usr.user_department_id = dep.id
                LEFT JOIN adms_positions pos ON usr.user_position_id = pos.id 
                ' . $whereSql;
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $paramType);
        }
        $stmt->execute();
        return ($stmt->fetch(PDO::FETCH_ASSOC)['amount_records']) ?? 0;
    }
    
    /**
     * Obter departamentos para filtro
     */
    public function getDepartmentsForFilter(): array
    {
        $sql = 'SELECT id, name FROM adms_departments ORDER BY name ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obter cargos para filtro
     */
    public function getPositionsForFilter(): array
    {
        $sql = 'SELECT id, name FROM adms_positions ORDER BY name ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                    t0.cpf,
                    t0.celular,
                    t0.image,
                    t0.timeline_bio,
                    t0.data_nascimento,
                    t0.data_admissao,
                    t0.data_desligamento,
                    t0.motivo_desligamento,
                    t0.user_department_id, 
                    t0.user_position_id,
                    t0.immediate_supervisor_id,
                    t0.created_at, 
                    t0.updated_at, 
                    t0.status,
                    t0.bloqueado,
                    t0.tentativas_login,
                    t0.senha_nunca_expira,
                    t0.modificar_senha_proximo_logon,
                    t0.super_usuario,
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
     * Perfil público para a timeline (departamento/cargo opcionais).
     *
     * @return array<string, mixed>|null
     */
    public function getUserForTimelineProfile(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $sql = 'SELECT  t0.id,
                        t0.name,
                        t0.username,
                        t0.image,
                        t0.timeline_bio,
                        t0.status,
                        t1.name AS dep_name,
                        t2.name AS pos_name
                FROM adms_users t0
                LEFT JOIN adms_departments t1 ON t0.user_department_id = t1.id
                LEFT JOIN adms_positions t2 ON t0.user_position_id = t2.id
                WHERE t0.id = :id
                LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Atualiza texto curto exibido no perfil da timeline (apenas o próprio usuário deve chamar).
     */
    public function updateTimelineBio(int $userId, string $bio): bool
    {
        if ($userId <= 0) {
            return false;
        }
        $bio = trim(mb_substr(strip_tags($bio), 0, 500));
        $sql = 'UPDATE adms_users SET timeline_bio = :bio, updated_at = NOW() WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        if ($bio === '') {
            $stmt->bindValue(':bio', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':bio', $bio, PDO::PARAM_STR);
        }
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Diretório de colaboradores na timeline: usuários ativos com cargo/depto/bio.
     */
    public function countActiveUsersForTimelineDirectory(?string $search = null): int
    {
        $bind = [];
        $whereSearch = $this->buildTimelineDirectorySearchClause($search, $bind);
        $sql = "SELECT COUNT(*) AS c
                FROM adms_users t0
                LEFT JOIN adms_departments t1 ON t0.user_department_id = t1.id
                LEFT JOIN adms_positions t2 ON t0.user_position_id = t2.id
                WHERE t0.status = 'Ativo'
                {$whereSearch}";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($bind as $k => $v) {
            $stmt->bindValue($k, $v, PDO::PARAM_STR);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)($row['c'] ?? 0);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listActiveUsersForTimelineDirectory(int $limit, int $offset, ?string $search = null): array
    {
        $limit = max(1, min(100, $limit));
        $offset = max(0, $offset);
        $bind = [];
        $whereSearch = $this->buildTimelineDirectorySearchClause($search, $bind);
        $sql = "SELECT t0.id,
                       t0.name,
                       t0.username,
                       t0.image,
                       t0.timeline_bio,
                       t1.name AS dep_name,
                       t2.name AS pos_name
                FROM adms_users t0
                LEFT JOIN adms_departments t1 ON t0.user_department_id = t1.id
                LEFT JOIN adms_positions t2 ON t0.user_position_id = t2.id
                WHERE t0.status = 'Ativo'
                {$whereSearch}
                ORDER BY t0.name ASC
                LIMIT {$limit} OFFSET {$offset}";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($bind as $k => $v) {
            $stmt->bindValue($k, $v, PDO::PARAM_STR);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    }

    /**
     * @param array<string, string> $bind
     */
    private function buildTimelineDirectorySearchClause(?string $search, array &$bind): string
    {
        $q = trim((string)$search);
        if ($q !== '' && mb_strlen($q) > 200) {
            $q = mb_substr($q, 0, 200);
        }
        if ($q === '') {
            return '';
        }
        $term = '%' . $q . '%';
        $bind[':td_s1'] = $term;
        $bind[':td_s2'] = $term;
        $bind[':td_s3'] = $term;
        $bind[':td_s4'] = $term;
        $bind[':td_s5'] = $term;

        return ' AND (
            t0.name LIKE :td_s1 OR t0.username LIKE :td_s2
            OR t1.name LIKE :td_s3 OR t2.name LIKE :td_s4
            OR t0.timeline_bio LIKE :td_s5
        )';
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
                name, email, username, cpf, celular, user_department_id, user_position_id, immediate_supervisor_id, password, status, bloqueado, tentativas_login, senha_nunca_expira, modificar_senha_proximo_logon, enviar_boas_vindas_email, enviar_boas_vindas_whatsapp, created_at, image, data_nascimento, data_admissao, super_usuario
            ) VALUES (
                :name, :email, :username, :cpf, :celular, :user_department_id, :user_position_id, :immediate_supervisor_id, :password, :status, :bloqueado, :tentativas_login, :senha_nunca_expira, :modificar_senha_proximo_logon, :enviar_boas_vindas_email, :enviar_boas_vindas_whatsapp, :created_at, :image, :data_nascimento, :data_admissao, :super_usuario
            )';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
            $stmt->bindValue(':email', $data['email'], PDO::PARAM_STR);
            $stmt->bindValue(':username', $data['username'], PDO::PARAM_STR);
            $stmt->bindValue(':cpf', $data['cpf'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':celular', $data['celular'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':user_department_id', $data['user_department_id'], PDO::PARAM_INT);
            $stmt->bindValue(':user_position_id', $data['user_position_id'], PDO::PARAM_INT);
            $stmt->bindValue(':immediate_supervisor_id', (!empty($data['immediate_supervisor_id']) && is_numeric($data['immediate_supervisor_id'])) ? (int)$data['immediate_supervisor_id'] : null, PDO::PARAM_INT);
            $stmt->bindValue(':password', password_hash($data['password'], PASSWORD_DEFAULT));
            $stmt->bindValue(':status', $data['status'] ?? 'Ativo', PDO::PARAM_STR);
            $stmt->bindValue(':bloqueado', $data['bloqueado'] ?? 'Não', PDO::PARAM_STR);
            $stmt->bindValue(':tentativas_login', $data['tentativas_login'] ?? 0, PDO::PARAM_INT);
            $stmt->bindValue(':senha_nunca_expira', $data['senha_nunca_expira'] ?? 'Não', PDO::PARAM_STR);
            $stmt->bindValue(':modificar_senha_proximo_logon', $data['modificar_senha_proximo_logon'] ?? 'Não', PDO::PARAM_STR);
            $stmt->bindValue(':enviar_boas_vindas_email', !empty($data['enviar_boas_vindas_email']) ? 1 : 0, PDO::PARAM_INT);
            $stmt->bindValue(':enviar_boas_vindas_whatsapp', !empty($data['enviar_boas_vindas_whatsapp']) ? 1 : 0, PDO::PARAM_INT);
            $stmt->bindValue(':created_at', date("Y-m-d H:i:s"));
            $stmt->bindValue(':image', $data['image'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':data_nascimento', $data['data_nascimento'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':data_admissao', $data['data_admissao'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':super_usuario', !empty($data['super_usuario']) ? 1 : 0, PDO::PARAM_INT);
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
                    'super_usuario' => !empty($data['super_usuario']) ? 1 : 0,
                ];
                \App\adms\Models\Services\LogAlteracaoService::registrarAlteracao(
                    'adms_users',
                    $novoId,
                    $_SESSION['user_id'] ?? 0,
                    'insert',
                    [],
                    $dadosDepois
                );
                
                // Invalidar cache de getAllUsersSelect
                $cacheService = new \App\adms\Models\Services\QueryCacheService();
                $cacheService->forget('users_select_all');
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
            
            // Gerenciar histórico de admissões/desligamentos
            $historyRepo = new \App\adms\Models\Repository\EmploymentHistoryRepository();
            
            // CASO 1: DESLIGAMENTO - Se data_desligamento foi preenchida e não havia antes
            if (!empty($data['data_desligamento']) && empty($dadosAntes['data_desligamento'])) {
                // Verificar se existe período ativo no histórico
                $periodoAtual = $historyRepo->getCurrentPeriod($data['id']);
                
                if ($periodoAtual) {
                    // Atualizar período existente com data de desligamento
                    $historyRepo->updateTermination(
                        $data['id'],
                        $data['data_desligamento'],
                        $data['motivo_desligamento'] ?? null
                    );
                } else {
                    // Criar novo registro histórico (caso não exista)
                    $historyRepo->create([
                        'adms_user_id' => $data['id'],
                        'data_admissao' => $dadosAntes['data_admissao'] ?? $data['data_admissao'] ?? date('Y-m-d'),
                        'data_desligamento' => $data['data_desligamento'],
                        'motivo_desligamento' => $data['motivo_desligamento'] ?? null,
                        'tipo_periodo' => 'Admissão',
                        'observacoes' => 'Desligamento registrado'
                    ]);
                }
                error_log("DESLIGAMENTO registrado no histórico para usuário {$data['id']}");
            }
            
            // CASO 2: RECONTRATAÇÃO - Se data_desligamento foi removida e havia data antes
            if (empty($data['data_desligamento']) && !empty($dadosAntes['data_desligamento'])) {
                // É uma recontratação - criar novo período no histórico
                // Manter os campos atuais (data_admissao nova, sem desligamento)
                $data['motivo_desligamento'] = null; // Limpar motivo apenas do registro atual
                
                // Criar novo registro histórico para a recontratação
                $historyRepo->create([
                    'adms_user_id' => $data['id'],
                    'data_admissao' => $data['data_admissao'] ?? date('Y-m-d'),
                    'data_desligamento' => null, // Ainda ativo
                    'motivo_desligamento' => null,
                    'tipo_periodo' => 'Recontratação',
                    'observacoes' => 'Colaborador recontratado'
                ]);
                
                // Se status não foi definido, ativar automaticamente
                if (!isset($data['status'])) {
                    $data['status'] = 'Ativo';
                }
                error_log("RECONTRATAÇÃO registrada no histórico para usuário {$data['id']}");
            }
            
            // CASO 3: NOVA ADMISSÃO - Se data_admissao foi alterada e é posterior à data de desligamento anterior
            if (!empty($data['data_admissao']) && !empty($dadosAntes['data_desligamento'])) {
                $novaAdmissao = new \DateTime($data['data_admissao']);
                $desligamentoAnterior = new \DateTime($dadosAntes['data_desligamento']);
                if ($novaAdmissao > $desligamentoAnterior) {
                    // Nova admissão é posterior ao desligamento - é recontratação
                    // Criar novo período no histórico
                    $historyRepo->create([
                        'adms_user_id' => $data['id'],
                        'data_admissao' => $data['data_admissao'],
                        'data_desligamento' => null, // Ainda ativo
                        'motivo_desligamento' => null,
                        'tipo_periodo' => 'Recontratação',
                        'observacoes' => 'Colaborador recontratado - nova admissão posterior ao desligamento'
                    ]);
                    
                    // Limpar desligamento do registro atual
                    $data['data_desligamento'] = null;
                    $data['motivo_desligamento'] = null;
                    if (!isset($data['status'])) {
                        $data['status'] = 'Ativo';
                    }
                    error_log("RECONTRATAÇÃO detectada para usuário {$data['id']} - nova admissão posterior ao desligamento");
                }
            }
            
            // CASO 4: PRIMEIRA ADMISSÃO - Se não há histórico e data_admissao foi preenchida
            if (!empty($data['data_admissao']) && empty($dadosAntes['data_admissao'])) {
                $historico = $historyRepo->getByUserId($data['id']);
                if (empty($historico)) {
                    // Primeira admissão - criar registro histórico
                    $historyRepo->create([
                        'adms_user_id' => $data['id'],
                        'data_admissao' => $data['data_admissao'],
                        'data_desligamento' => null,
                        'motivo_desligamento' => null,
                        'tipo_periodo' => 'Admissão',
                        'observacoes' => 'Primeira admissão do colaborador'
                    ]);
                    error_log("PRIMEIRA ADMISSÃO registrada no histórico para usuário {$data['id']}");
                }
            }
            
            // HIERARQUIA: Se usuário está sendo inativado E tem subordinados, promovê-los automaticamente
            if (isset($data['status']) && $data['status'] == 0 && $dadosAntes['status'] == 1) {
                // Usuário está sendo inativado
                error_log("=== HIERARQUIA: Usuário {$data['id']} está sendo inativado ===");
                
                // Verificar se tem subordinados
                $hierarchyService = new \App\adms\Models\Services\HierarchyManagementService();
                $checkResult = $hierarchyService::checkSubordinates($data['id']);
                
                if ($checkResult['has_subordinates']) {
                    error_log("HIERARQUIA: Usuário tem {$checkResult['count']} subordinados - promovendo automaticamente...");
                    
                    // Promover subordinados para o nível superior
                    $promoteResult = $hierarchyService::promoteSubordinates($data['id']);
                    
                    if ($promoteResult['success']) {
                        error_log("HIERARQUIA: ✅ {$promoteResult['promoted_count']} subordinados promovidos com sucesso");
                        
                        // Adicionar mensagem de sucesso na sessão
                        if (!isset($_SESSION['hierarchy_message'])) {
                            $_SESSION['hierarchy_message'] = $promoteResult['message'];
                        }
                    } else {
                        error_log("HIERARQUIA: ❌ Erro ao promover subordinados: {$promoteResult['message']}");
                    }
                }
            }

            // QUERY para atualizar o usuário
            $sql = 'UPDATE adms_users SET name = :name, email = :email, username = :username, cpf = :cpf, celular = :celular, user_department_id = :user_department_id, user_position_id = :user_position_id, immediate_supervisor_id = :immediate_supervisor_id, updated_at = :updated_at';
            if (array_key_exists('super_usuario', $data)) {
                $sql .= ', super_usuario = :super_usuario';
            }
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
            // Sempre incluir campos de admissão/desligamento (podem ser null para limpar)
            if (isset($data['data_admissao'])) {
                $sql .= ', data_admissao = :data_admissao';
            }
            // Sempre incluir data_desligamento e motivo_desligamento para permitir limpar valores
            $sql .= ', data_desligamento = :data_desligamento';
            $sql .= ', motivo_desligamento = :motivo_desligamento';
            if (isset($data['bloqueado']) && $data['bloqueado'] === 'Não' && isset($dadosAntes['bloqueado']) && $dadosAntes['bloqueado'] === 'Sim') {
                $sql .= ', tentativas_login = 0, data_bloqueio_temporario = NULL';
            }
            $sql .= ' WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
            $stmt->bindValue(':email', $data['email'], PDO::PARAM_STR);
            $stmt->bindValue(':username', $data['username'], PDO::PARAM_STR);
            $stmt->bindValue(':cpf', $data['cpf'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':celular', $data['celular'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':user_department_id', (int)$data['user_department_id'], PDO::PARAM_INT);
            $stmt->bindValue(':user_position_id', (int)$data['user_position_id'], PDO::PARAM_INT);
            $stmt->bindValue(':immediate_supervisor_id', (!empty($data['immediate_supervisor_id']) && is_numeric($data['immediate_supervisor_id'])) ? (int)$data['immediate_supervisor_id'] : null, PDO::PARAM_INT);
            $stmt->bindValue(':updated_at', date("Y-m-d H:i:s"));
            if (array_key_exists('super_usuario', $data)) {
                $stmt->bindValue(':super_usuario', !empty($data['super_usuario']) ? 1 : 0, PDO::PARAM_INT);
            }
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
            if (isset($data['data_admissao'])) {
                $stmt->bindValue(':data_admissao', !empty($data['data_admissao']) ? $data['data_admissao'] : null, PDO::PARAM_STR);
            }
            // Sempre bindar data_desligamento e motivo_desligamento (podem ser null)
            $stmt->bindValue(':data_desligamento', !empty($data['data_desligamento']) ? $data['data_desligamento'] : null, PDO::PARAM_STR);
            $stmt->bindValue(':motivo_desligamento', !empty($data['motivo_desligamento']) ? $data['motivo_desligamento'] : null, PDO::PARAM_STR);
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
                    'super_usuario' => array_key_exists('super_usuario', $data)
                        ? (!empty($data['super_usuario']) ? 1 : 0)
                        : ($dadosAntes['super_usuario'] ?? null),
                ];
                \App\adms\Models\Services\LogAlteracaoService::registrarAlteracao(
                    'adms_users',
                    $data['id'],
                    $_SESSION['user_id'] ?? 0,
                    'update',
                    $dadosAntes,
                    $dadosDepois
                );
                
                // Invalidar cache de getAllUsersSelect
                $cacheService = new \App\adms\Models\Services\QueryCacheService();
                $cacheService->forget('users_select_all');
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
            // Atualizar senha e flag de "modificar_senha_proximo_logon"
            // Se não for informado no array, assume "Não" (comportamento padrão antigo).
            $modificarProximoLogon = isset($data['modificar_senha_proximo_logon'])
                ? $data['modificar_senha_proximo_logon']
                : 'Não';

            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

            $conn = $this->getConnection();

            $sql = 'UPDATE adms_users 
                       SET password = :password,
                           modificar_senha_proximo_logon = :modificar_senha_proximo_logon,
                           updated_at = :updated_at 
                     WHERE id = :id';
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':password', $hashedPassword);
            $stmt->bindValue(':modificar_senha_proximo_logon', $modificarProximoLogon, PDO::PARAM_STR);
            $stmt->bindValue(':updated_at', date("Y-m-d H:i:s"));
            $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);
            $result = $stmt->execute();

            if ($result) {
                // Registrar histórico de senha apenas quando explicitamente solicitado
                $salvarHistorico = $data['salvar_historico'] ?? true;
                if ($salvarHistorico) {
                    try {
                        $sqlHist = 'INSERT INTO adms_password_history (user_id, password, created_at)
                                    VALUES (:user_id, :password, :created_at)';
                        $stmtHist = $conn->prepare($sqlHist);
                        $stmtHist->bindValue(':user_id', $data['id'], PDO::PARAM_INT);
                        $stmtHist->bindValue(':password', $hashedPassword, PDO::PARAM_STR);
                        $stmtHist->bindValue(':created_at', date("Y-m-d H:i:s"));
                        $stmtHist->execute();

                        // Limitar quantidade de registros de histórico por usuário
                        // com base em historico_senhas definido na política.
                        try {
                            $policyRepo = new AdmsPasswordPolicyRepository();
                            $policy = $policyRepo->getPolicy();
                            $limite = $policy ? (int)$policy->historico_senhas : 0;

                            if ($limite > 0) {
                                // Deletar registros mais antigos, mantendo apenas os N mais recentes.
                                $sqlCleanup = '
                                    DELETE FROM adms_password_history
                                    WHERE user_id = :user_id
                                      AND id NOT IN (
                                          SELECT id FROM (
                                              SELECT id
                                              FROM adms_password_history
                                              WHERE user_id = :user_id_inner
                                              ORDER BY created_at DESC
                                              LIMIT :limite
                                          ) AS t
                                      )';
                                $stmtCleanup = $conn->prepare($sqlCleanup);
                                $stmtCleanup->bindValue(':user_id', $data['id'], PDO::PARAM_INT);
                                $stmtCleanup->bindValue(':user_id_inner', $data['id'], PDO::PARAM_INT);
                                $stmtCleanup->bindValue(':limite', $limite, PDO::PARAM_INT);
                                $stmtCleanup->execute();
                            }
                        } catch (Exception $e) {
                            GenerateLog::generateLog("error", "Falha ao limpar histórico de senhas excedente.", [
                                'user_id' => $data['id'] ?? null,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    } catch (Exception $e) {
                        // Não falhar a troca de senha por erro no histórico, apenas logar.
                        GenerateLog::generateLog("error", "Falha ao registrar histórico de senha.", [
                            'user_id' => $data['id'] ?? null,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            return $result;
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
            
            // HIERARQUIA: Se usuário tem subordinados, promovê-los ANTES de deletar
            error_log("=== HIERARQUIA: Verificando subordinados antes de deletar usuário {$id} ===");
            
            $hierarchyService = new \App\adms\Models\Services\HierarchyManagementService();
            $checkResult = $hierarchyService::checkSubordinates($id);
            
            if ($checkResult['has_subordinates']) {
                error_log("HIERARQUIA: Usuário tem {$checkResult['count']} subordinados - promovendo automaticamente...");
                
                // Promover subordinados para o nível superior
                $promoteResult = $hierarchyService::promoteSubordinates($id);
                
                if ($promoteResult['success']) {
                    error_log("HIERARQUIA: ✅ {$promoteResult['promoted_count']} subordinados promovidos com sucesso");
                    
                    // Adicionar mensagem de sucesso na sessão
                    if (!isset($_SESSION['hierarchy_message'])) {
                        $_SESSION['hierarchy_message'] = $promoteResult['message'];
                    }
                } else {
                    error_log("HIERARQUIA: ❌ Erro ao promover subordinados: {$promoteResult['message']}");
                    // Continuar com a exclusão mesmo se promoção falhar (foreign key vai lidar)
                }
            }
            
            $sql = 'DELETE FROM adms_users WHERE id = :id LIMIT 1';
            $stms = $this->getConnection()->prepare($sql);
            $stms->bindValue(':id', $id, PDO::PARAM_INT);
            $stms->execute();
            $affectedRows = $stms->rowCount();
            if ($affectedRows > 0) {
                // Remover histórico de senhas associado a este usuário
                try {
                    $sqlHist = 'DELETE FROM adms_password_history WHERE user_id = :user_id';
                    $stmtHist = $this->getConnection()->prepare($sqlHist);
                    $stmtHist->bindValue(':user_id', $id, PDO::PARAM_INT);
                    $stmtHist->execute();
                } catch (Exception $e) {
                    GenerateLog::generateLog("error", "Falha ao remover histórico de senhas ao deletar usuário.", [
                        'user_id' => $id,
                        'error' => $e->getMessage(),
                    ]);
                }
                // Log de exclusão
                \App\adms\Models\Services\LogAlteracaoService::registrarAlteracao(
                    'adms_users',
                    $id,
                    $_SESSION['user_id'] ?? 0,
                    'delete',
                    $dadosAntes ?: [],
                    []
                );
                
                // Invalidar cache de getAllUsersSelect
                $cacheService = new \App\adms\Models\Services\QueryCacheService();
                $cacheService->forget('users_select_all');
                
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

    /**
     * Obter todos os usuários para organograma
     *
     * Considera apenas colaboradores ativos e não desligados.
     * Inclui contagem de subordinados diretos via subquery.
     */
    public function getAllUsersForChart(): array
    {
        try {
            $sql = 'SELECT 
                        u.id,
                        u.name,
                        u.email,
                        u.image,
                        u.user_department_id,
                        u.user_position_id,
                        u.immediate_supervisor_id,
                        u.status,
                        u.data_desligamento,
                        d.name AS department_name,
                        p.name AS position_name,
                        -- Contagem de subordinados diretos (apenas ativos e não desligados)
                        (
                            SELECT COUNT(*) 
                            FROM adms_users u2 
                            WHERE u2.immediate_supervisor_id = u.id 
                              AND u2.status = :statusAtivo
                              AND (u2.data_desligamento IS NULL 
                                   OR u2.data_desligamento = "0000-00-00")
                              AND LOWER(TRIM(u2.username)) <> LOWER(:exclOrgchartUser)
                        ) AS direct_subordinates_count
                    FROM adms_users u
                    INNER JOIN adms_departments d ON u.user_department_id = d.id
                    INNER JOIN adms_positions p ON u.user_position_id = p.id
                    WHERE u.status = :statusAtivo
                      AND (u.data_desligamento IS NULL 
                           OR u.data_desligamento = "0000-00-00")
                      AND LOWER(TRIM(u.username)) <> LOWER(:exclOrgchartUser)
                    ORDER BY u.name ASC';
            
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':statusAtivo', 'Ativo', PDO::PARAM_STR);
            $stmt->bindValue(':exclOrgchartUser', self::ORGCHART_EXCLUDED_USERNAME, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Erro ao buscar usuários para organograma.', [
                'exception' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Obter estatísticas de hierarquia para o organograma.
     *
     * - total_users: total de colaboradores ativos (contrato não desligado)
     * - total_managers: total de Gerentes/Supervisores (por cargo OU por ter subordinados)
     * - total_coordinators: total de Coordenadores (por cargo)
     *
     * @return array Estatísticas agregadas
     */
    public function getHierarchyStats(): array
    {
        try {
            $sql = 'SELECT 
                        COUNT(*) AS total_users,
                        SUM(
                            CASE 
                                WHEN 
                                    EXISTS (
                                        SELECT 1 
                                        FROM adms_users s
                                        WHERE s.immediate_supervisor_id = u.id
                                          AND s.status = :statusAtivo
                                          AND (s.data_desligamento IS NULL 
                                               OR s.data_desligamento = "0000-00-00")
                                          AND LOWER(TRIM(s.username)) <> LOWER(:exclOrgchartUser)
                                    )
                                    OR LOWER(p.name) LIKE :cargoGerente
                                    OR LOWER(p.name) LIKE :cargoSupervisor
                                THEN 1
                                ELSE 0
                            END
                        ) AS total_managers,
                        SUM(
                            CASE 
                                WHEN LOWER(p.name) LIKE :cargoCoordenador
                                THEN 1
                                ELSE 0
                            END
                        ) AS total_coordinators
                    FROM adms_users u
                    INNER JOIN adms_positions p ON u.user_position_id = p.id
                    WHERE u.status = :statusAtivo
                      AND (u.data_desligamento IS NULL 
                           OR u.data_desligamento = "0000-00-00")
                      AND LOWER(TRIM(u.username)) <> LOWER(:exclOrgchartUser)';
            
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':statusAtivo', 'Ativo', PDO::PARAM_STR);
            $stmt->bindValue(':exclOrgchartUser', self::ORGCHART_EXCLUDED_USERNAME, PDO::PARAM_STR);
            $stmt->bindValue(':cargoGerente', '%gerente%', PDO::PARAM_STR);
            $stmt->bindValue(':cargoSupervisor', '%supervisor%', PDO::PARAM_STR);
            $stmt->bindValue(':cargoCoordenador', '%coordenador%', PDO::PARAM_STR);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            
            return [
                // Total de colaboradores ativos (não desligados)
                'total_users' => (int)($result['total_users'] ?? 0),
                // Indicador de Gerentes/Supervisores
                'total_managers' => (int)($result['total_managers'] ?? 0),
                // Indicador de Coordenadores
                'total_coordinators' => (int)($result['total_coordinators'] ?? 0),
            ];
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Erro ao calcular estatísticas de hierarquia para organograma.', [
                'exception' => $e->getMessage(),
            ]);
            
            return [
                'total_users' => 0,
                'total_managers' => 0,
                'total_coordinators' => 0,
            ];
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
        // Cache para queries frequentes (TTL: 5 minutos)
        $cacheService = new \App\adms\Models\Services\QueryCacheService(null, 300);
        // Stamp para renovar cache automaticamente quando houver mudanças na tabela
        $stampSql = 'SELECT COUNT(*) AS total_rows, COALESCE(MAX(id), 0) AS max_id FROM adms_users';
        $stampStmt = $this->getConnection()->prepare($stampSql);
        $stampStmt->execute();
        $stamp = $stampStmt->fetch(PDO::FETCH_ASSOC) ?: ['total_rows' => 0, 'max_id' => 0];
        $cacheKey = 'users_select_all_' . (int)$stamp['total_rows'] . '_' . (int)$stamp['max_id'];
        
        // Tentar obter do cache
        $cached = $cacheService->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        // Se não estiver em cache, buscar do banco
        $sql = 'SELECT id, name, email FROM adms_users ORDER BY name ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Armazenar no cache
        $cacheService->put($cacheKey, $result);
        
        return $result;
    }

    /**
     * Retorna usuários por cargo específico
     */
    public function getUsersByPosition(int $positionId): array
    {
        $sql = 'SELECT id, name, email, user_department_id, user_position_id, status 
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
     * Buscar todos os subordinados de um gestor
     */
    public function getSubordinates(int $supervisorId): array
    {
        $sql = "SELECT id, name, email, status 
                FROM adms_users 
                WHERE immediate_supervisor_id = :supervisor_id 
                AND status = 'Ativo'
                ORDER BY name ASC";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':supervisor_id', $supervisorId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Recupera todos os usuários para uso em formulários (select).
     *
     * @return array Lista de usuários para select
     */
    public function getAllUsersForSelect(): array
    {
        $sql = 'SELECT id, name, email, status 
                FROM adms_users 
                WHERE status = :status 
                ORDER BY name ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':status', 'Ativo', PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Atualiza rapidamente os campos de consentimento LGPD do usuário.
     *
     * Esta operação é usada principalmente no fluxo de login, quando
     * já existe um consentimento válido na tabela lgpd_consentimentos
     * e queremos apenas sincronizar os flags em adms_users.
     *
     * @param int $userId
     * @param string $versaoTermo
     * @return bool
     */
    public function atualizarConsentimentoLgpd(int $userId, string $versaoTermo): bool
    {
        try {
            $sql = 'UPDATE adms_users 
                       SET lgpd_consent_given   = 1,
                           lgpd_consent_date    = NOW(),
                           lgpd_consent_version = :versao,
                           receber_notificacoes_whatsapp = 1
                     WHERE id = :id';

            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':versao', $versaoTermo, PDO::PARAM_STR);
            $stmt->bindValue(':id', $userId, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Erro ao atualizar consentimento LGPD do usuário.', [
                'user_id' => $userId,
                'versao_termo' => $versaoTermo,
                'exception' => $e->getMessage(),
            ]);
            return false;
        }
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

    /**
     * Mapa id => nome para usuários ativos (timeline / menções).
     *
     * @param array<int> $ids
     * @return array<int, string>
     */
    public function getIdNameMapForIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn ($v) => $v > 0)));
        if ($ids === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT id, name FROM adms_users WHERE id IN ($placeholders) AND status = 'Ativo'";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($ids);
        $map = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $map[(int)$row['id']] = (string)$row['name'];
        }

        return $map;
    }

    /**
     * IDs de colaboradores ativos para menção @todos (exclui o autor e o login técnico do organograma).
     *
     * @return array<int>
     */
    public function getAllActiveUserIdsForTimelineMentions(int $excludeUserId): array
    {
        try {
            $sql = 'SELECT u.id FROM adms_users u
                    WHERE u.status = "Ativo"
                      AND (u.data_desligamento IS NULL OR u.data_desligamento = "0000-00-00")
                      AND LOWER(TRIM(u.username)) <> LOWER(:excl)';
            $params = [':excl' => self::ORGCHART_EXCLUDED_USERNAME];
            if ($excludeUserId > 0) {
                $sql .= ' AND u.id <> :uid';
                $params[':uid'] = $excludeUserId;
            }
            $sql .= ' ORDER BY u.id ASC';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            $ids = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $ids[] = (int)$row['id'];
            }

            return $ids;
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Erro ao listar IDs para menção @todos na timeline.', [
                'exception' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * IDs de colaboradores ativos de um departamento (menção @depto-id), com as mesmas regras de @todos.
     *
     * @return array<int>
     */
    public function getActiveUserIdsByDepartmentForTimelineMentions(int $departmentId, int $excludeUserId): array
    {
        if ($departmentId <= 0) {
            return [];
        }
        try {
            $sql = 'SELECT u.id FROM adms_users u
                    WHERE u.status = "Ativo"
                      AND (u.data_desligamento IS NULL OR u.data_desligamento = "0000-00-00")
                      AND u.user_department_id = :dep
                      AND LOWER(TRIM(u.username)) <> LOWER(:excl)';
            $params = [
                ':dep' => $departmentId,
                ':excl' => self::ORGCHART_EXCLUDED_USERNAME,
            ];
            if ($excludeUserId > 0) {
                $sql .= ' AND u.id <> :uid';
                $params[':uid'] = $excludeUserId;
            }
            $sql .= ' ORDER BY u.id ASC';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            $ids = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $ids[] = (int) $row['id'];
            }

            return $ids;
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Erro ao listar IDs por departamento na timeline.', [
                'exception' => $e->getMessage(),
                'department_id' => $departmentId,
            ]);

            return [];
        }
    }

    /**
     * Interpreta token @depto-1, @dep-1 ou @departamento-1 (case-insensitive).
     */
    public function parseTimelineDepartmentMentionToken(string $token): ?int
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }
        if (preg_match('/^(?:depto|dep|departamento)-(\d+)$/i', $token, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    /**
     * Slug estável a partir do nome do departamento (ex.: "Financeiro" → financeiro, "RH Comercial" → rh-comercial).
     */
    public function timelineDepartmentSlugFromName(string $name): string
    {
        $slugImg = new SlugImg();
        $slug = $slugImg->slug(trim($name));
        if ($slug === null || $slug === '') {
            return '';
        }

        return trim($slug, '-');
    }

    /**
     * Mapa slug (minúsculo) → id; em colisão de slug, prevalece o menor id.
     *
     * @return array<string, int>
     */
    private function loadTimelineDepartmentSlugToIdMap(): array
    {
        if (self::$timelineDeptSlugToIdCache !== null) {
            return self::$timelineDeptSlugToIdCache;
        }
        try {
            $sql = 'SELECT id, name FROM adms_departments ORDER BY id ASC';
            $rows = $this->getConnection()->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Erro ao montar mapa de slugs de departamentos (timeline).', [
                'exception' => $e->getMessage(),
            ]);
            self::$timelineDeptSlugToIdCache = [];

            return self::$timelineDeptSlugToIdCache;
        }
        $map = [];
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $slug = mb_strtolower($this->timelineDepartmentSlugFromName((string) ($row['name'] ?? '')), 'UTF-8');
            if ($slug === '') {
                continue;
            }
            if (!isset($map[$slug])) {
                $map[$slug] = $id;
            }
        }
        self::$timelineDeptSlugToIdCache = $map;

        return self::$timelineDeptSlugToIdCache;
    }

    /**
     * Resolve menção de departamento: formato gravado @depto-id ou slug pelo nome (ex.: financeiro).
     */
    public function resolveTimelineDepartmentMention(string $token): ?int
    {
        $id = $this->parseTimelineDepartmentMentionToken($token);
        if ($id !== null && $id > 0) {
            return $id;
        }
        $slug = mb_strtolower(trim($token), 'UTF-8');
        if ($slug === '') {
            return null;
        }
        $map = $this->loadTimelineDepartmentSlugToIdMap();

        return $map[$slug] ?? null;
    }

    public function getDepartmentNameById(int $id): ?string
    {
        if ($id <= 0) {
            return null;
        }
        $stmt = $this->getConnection()->prepare('SELECT name FROM adms_departments WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? (string) $row['name'] : null;
    }

    /**
     * @param array<int> $ids
     * @return array<int, string> id => nome
     */
    public function getDepartmentNamesByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn ($v) => $v > 0)));
        if ($ids === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT id, name FROM adms_departments WHERE id IN ($placeholders)";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($ids);
        $map = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $map[(int) $row['id']] = (string) $row['name'];
        }

        return $map;
    }

    /**
     * Departamentos para autocomplete de menção (@depto-id).
     *
     * @return array<int, array<string, mixed>>
     */
    public function searchDepartmentsForTimelineMention(string $q, int $limit = 8): array
    {
        $limit = max(0, min(20, $limit));
        if ($limit === 0) {
            return [];
        }
        $q = trim($q);
        try {
            if ($q === '') {
                $sql = 'SELECT id, name FROM adms_departments ORDER BY name ASC LIMIT ' . (int) $limit;
                $rows = $this->getConnection()->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } else {
                $like = '%' . $q . '%';
                $sql = 'SELECT id, name FROM adms_departments WHERE name LIKE :q ORDER BY name ASC LIMIT ' . (int) $limit;
                $stmt = $this->getConnection()->prepare($sql);
                $stmt->execute([':q' => $like]);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            }
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Erro ao buscar departamentos para menção na timeline.', [
                'exception' => $e->getMessage(),
            ]);

            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            $did = (int) ($row['id'] ?? 0);
            if ($did <= 0) {
                continue;
            }
            $name = (string) ($row['name'] ?? '');
            $slug = $this->timelineDepartmentSlugFromName($name);
            if ($slug === '') {
                $slug = 'depto-' . $did;
            }
            $out[] = [
                'id' => 0,
                'name' => $name,
                'email' => '',
                'username' => $slug,
                'image' => null,
                'mention_department' => true,
                'department_id' => $did,
            ];
        }

        return $out;
    }

    /**
     * Busca rápida de colaboradores para autocomplete de menções na timeline (por username).
     *
     * @return array<int, array<string, mixed>>
     */
    public function searchUsersForTimeline(string $q, int $limit = 12): array
    {
        $q = trim($q);
        $limit = max(1, min(30, $limit));
        $lower = mb_strtolower($q, 'UTF-8');

        $mentionAllRows = [];
        if ($q === '') {
            $mentionAllRows[] = [
                'id' => 0,
                'name' => 'Todos os colaboradores',
                'email' => '',
                'username' => 'todos',
                'image' => null,
                'mention_all' => true,
            ];
            if ($limit >= 2) {
                $mentionAllRows[] = [
                    'id' => 0,
                    'name' => 'Todos (everyone)',
                    'email' => '',
                    'username' => 'everyone',
                    'image' => null,
                    'mention_all' => true,
                ];
            }
        } else {
            if (str_starts_with('todos', $lower)) {
                $mentionAllRows[] = [
                    'id' => 0,
                    'name' => 'Todos os colaboradores',
                    'email' => '',
                    'username' => 'todos',
                    'image' => null,
                    'mention_all' => true,
                ];
            }
            if (str_starts_with('everyone', $lower)) {
                $mentionAllRows[] = [
                    'id' => 0,
                    'name' => 'Todos os colaboradores',
                    'email' => '',
                    'username' => 'everyone',
                    'image' => null,
                    'mention_all' => true,
                ];
            }
        }

        $afterPrefix = $limit - count($mentionAllRows);
        if ($afterPrefix < 1) {
            return $mentionAllRows;
        }

        $deptLimit = min(8, max(0, (int) ceil($afterPrefix / 2)));
        $deptRows = $this->searchDepartmentsForTimelineMention($q, $deptLimit);

        $slot = $limit - count($mentionAllRows) - count($deptRows);
        if ($slot < 1) {
            return array_merge($mentionAllRows, $deptRows);
        }

        if ($q === '') {
            $sql = 'SELECT id, name, email, username, image FROM adms_users
                    WHERE status = "Ativo" AND username IS NOT NULL AND username != ""
                    ORDER BY username ASC
                    LIMIT ' . (int) $slot;
            $rows = $this->getConnection()->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } else {
            $like = '%' . $q . '%';
            $sql = 'SELECT id, name, email, username, image FROM adms_users
                    WHERE status = "Ativo" AND username IS NOT NULL AND username != ""
                      AND username LIKE :q
                    ORDER BY username ASC
                    LIMIT ' . (int) $slot;
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute([':q' => $like]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        return array_merge($mentionAllRows, $deptRows, $rows);
    }

    /**
     * Resolve menção @username (correspondência exata, usuário ativo).
     */
    public function findIdByUsernameExact(string $username): ?int
    {
        $username = trim($username);
        if ($username === '') {
            return null;
        }
        $stmt = $this->getConnection()->prepare(
            'SELECT id FROM adms_users WHERE status = "Ativo" AND username = :u LIMIT 1'
        );
        $stmt->execute([':u' => $username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? (int) $row['id'] : null;
    }

    /**
     * @param array<int, string> $usernames
     * @return array<string, array{id:int, name:string, username:string}>
     */
    public function getActiveUsersByUsernames(array $usernames): array
    {
        $usernames = array_values(array_unique(array_filter(array_map('trim', $usernames), static fn ($u) => $u !== '')));
        if ($usernames === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($usernames), '?' ));
        $sql = "SELECT id, name, username FROM adms_users
                WHERE status = 'Ativo' AND username IN ($placeholders)";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($usernames);
        $out = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $out[(string) $row['username']] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'username' => (string) $row['username'],
            ];
        }

        return $out;
    }

    public function existsActiveUser(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }
        $stmt = $this->getConnection()->prepare(
            'SELECT 1 FROM adms_users WHERE id = :id AND status = :st LIMIT 1'
        );
        $stmt->execute([':id' => $id, ':st' => 'Ativo']);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * @return array{id: int, name: string, email: string}|null
     */
    public function getActiveUserBasics(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $stmt = $this->getConnection()->prepare(
            'SELECT id, name, email FROM adms_users WHERE id = :id AND status = "Ativo" LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        return [
            'id' => (int)($row['id'] ?? 0),
            'name' => (string)($row['name'] ?? ''),
            'email' => (string)($row['email'] ?? ''),
        ];
    }

    /**
     * Autocomplete para gestores registrarem RSVP de terceiros (sem entradas especiais da timeline).
     *
     * @return array<int, array{id: int, name: string, email: string}>
     */
    public function searchActiveUsersForAutocomplete(string $q, int $limit = 15): array
    {
        $q = trim($q);
        $limit = max(1, min(30, $limit));
        if (mb_strlen($q) < 2) {
            return [];
        }
        $like = '%' . $q . '%';
        $sql = 'SELECT id, name, email FROM adms_users
                WHERE status = "Ativo"
                  AND (name LIKE :q OR email LIKE :q OR username LIKE :q)
                ORDER BY name ASC
                LIMIT ' . $limit;
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([':q' => $like]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return $rows;
    }

}
