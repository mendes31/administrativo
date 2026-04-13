<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Services\DbConnection;
use Exception;
use Generator;
use PDO;

/**
 * Repositório responsável pelas operações relacionadas às páginas associadas aos níveis de acesso.
 *
 * **Regra de permission inicial** (novo nível ou inclusão em massa de linhas em `adms_access_levels_pages`):
 * para cada página, `permission = 1` quando ocorre **qualquer** destes casos (em **todos** os níveis, inclusive id 1):
 * - `adms_pages.public_page = 1` (pública no cadastro; lembrar que o roteador também dispensa login), ou
 * - `adms_pages.default_page = 1` (página padrão na matriz), ou
 * - `controller` está na propriedade `$basicControllers` (mínimos: dashboard, perfil, informativos, etc.).
 * Páginas privadas sem esses critérios: `permission = 0` (liberação manual na matriz). O acesso em rota para
 * utilizadores com nível 1 segue a regra em `PagesRoutesRepository` / `UserAccessHelper::hasFullSystemAccess()`.
 *
 * @package App\adms\Models\Repository
 */
class AccessLevelsPagesRepository extends DbConnection
{
    /** Última mensagem de erro (para retorno ao controller em caso de falha) */
    private static ?string $lastErrorMessage = null;

    /**
     * Lista de controllers que representam permissões básicas
     * que devem ser concedidas a TODOS os níveis de acesso
     * logo na criação (além das páginas públicas).
     *
     * ATENÇÃO: os IDs das páginas podem variar entre ambientes,
     * por isso usamos o campo `controller` de `adms_pages`.
     *
     * Dashboard            -> Dashboard
     * Troca Obrigatória    -> ForcePasswordChange
     * Perfil do Usuário    -> Profile
     * Editar Senha Perfil  -> UpdatePassword
     * Informativos (lista, ver, ciência, leitura)
     *                      -> ListInformativos, ViewInformativo,
     *                         AcknowledgeInformativo, ReadInformativo
     */
    private array $basicControllers = [
        'Dashboard',
        'ForcePasswordChange',
        'Profile',
        'UpdatePassword',
        'ListInformativos',
        'ViewInformativo',
        'AcknowledgeInformativo',
        'ReadInformativo',
    ];

    public static function getLastErrorMessage(): ?string
    {
        return self::$lastErrorMessage;
    }

    /**
     * Recupera as páginas associadas a um nível de acesso.
     *
     * Este método realiza uma consulta no banco de dados para obter todas as páginas associadas a um 
     * nível de acesso específico, retornando um array com os IDs das páginas.
     *
     * @param int $accessLevel ID do nível de acesso.
     * @return array<int>|array<int, true> Lista de IDs (sem permission) ou mapa page_id => true (com permission).
     */
    public function getPagesAccessLevelsArray(int $accessLevel, bool $permission = false): array
    {
        // DISTINCT / GROUP BY: a tabela pode acumular linhas duplicadas (mesmo nível + mesma página)
        // por sincronizações e INSERTs repetidos; sem isso fetchAll estoura memória (ex.: sync de níveis).
        if ($permission) {
            $sql = 'SELECT adms_page_id
                    FROM adms_access_levels_pages
                    WHERE adms_access_level_id = :adms_access_level_id
                      AND permission = 1
                    GROUP BY adms_page_id';
        } else {
            $sql = 'SELECT DISTINCT adms_page_id
                    FROM adms_access_levels_pages
                    WHERE adms_access_level_id = :adms_access_level_id';
        }

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':adms_access_level_id', $accessLevel, PDO::PARAM_INT);
        $stmt->execute();

        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        if (!is_array($ids)) {
            return [];
        }
        $ids = array_values(array_filter(
            array_map(static fn($v): int => (int)$v, $ids),
            static fn(int $pid): bool => $pid > 0
        ));

        if ($permission) {
            $indexed = [];
            foreach ($ids as $pid) {
                if ($pid > 0) {
                    $indexed[$pid] = true;
                }
            }

            return $indexed;
        }

        return $ids;
    }

    /**
     * Insere em massa as páginas associadas a um nível de acesso.
     *
     * Este método insere em lote as permissões de acesso às páginas para diferentes níveis de acesso no banco de dados.
     * Ele utiliza uma transação SQL para garantir a integridade das operações e gera logs para monitoramento.
     *
     * @param array $data Dados contendo as páginas a serem associadas a cada nível de acesso.
     * @return bool Retorna `true` se a operação foi bem-sucedida, ou `false` em caso de erro.
     */
    public function createPagesAccessLevel(array $data): bool
    {
        try {
            // Marca o ponto inicial de uma transação SQL
            $conn = $this->getConnection();
            $conn->beginTransaction();

            // Array para armazenar ID do nível de acesso para salvar no log
            $accessLevelArrayId = [];

            // Buscar metadados das páginas envolvidas (public_page, default_page, controller)
            $allPageIds = [];
            foreach ($data as $accessLevelId => $accessLevelPages) {
                foreach ($accessLevelPages as $pageId) {
                    $allPageIds[(int)$pageId] = true;
                }
            }

            $pagesMeta = [];
            if (!empty($allPageIds)) {
                $ids = implode(',', array_map('intval', array_keys($allPageIds)));
                $sqlPages = "SELECT id, controller, public_page, default_page 
                             FROM adms_pages 
                             WHERE id IN ({$ids})";
                $stmtPages = $conn->prepare($sqlPages);
                $stmtPages->execute();
                foreach ($stmtPages->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $pagesMeta[(int)$row['id']] = $row;
                }
            }

            // Percorrer o array com nível de acesso e páginas
            foreach ($data as $accessLevelId => $accessLevelPages) {

                // Array para acumular os valores
                $values = [];
                $placeholders = [];

                // Percorrer o array de páginas que o nível de acesso não tem permissão de acessar
                foreach ($accessLevelPages as $pageId) {
                    $pageId = (int)$pageId;
                    if ($pageId <= 0) {
                        continue;
                    }

                    $meta = $pagesMeta[$pageId] ?? null;
                    $controller  = $meta['controller'] ?? '';
                    $publicPage  = (int)($meta['public_page'] ?? 0);
                    $defaultPage = (int)($meta['default_page'] ?? 0);

                    $isBasic   = in_array($controller, $this->basicControllers, true);
                    $isPublic  = $publicPage === 1;
                    $isDefault = $defaultPage === 1;

                    $permission = ($isPublic || $isDefault || $isBasic) ? 1 : 0;

                    $now = date('Y-m-d H:i:s');
                    $values[] = $permission;
                    $values[] = $accessLevelId;
                    $values[] = $pageId;
                    $values[] = $now;
                    $values[] = $now;
                    $placeholders[] = '(?, ?, ?, ?, ?)';
                }

                // Criar QUERY somente se o nível de acesso não tem página cadastrada
                if ($accessLevelPages ?? false) {

                    // UPSERT: UNIQUE (adms_access_level_id, adms_page_id) evita duplicados; alinha permission se já existir
                    $sql = 'INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at) VALUES '
                        . implode(', ', $placeholders)
                        . ' ON DUPLICATE KEY UPDATE permission = VALUES(permission), updated_at = VALUES(updated_at)';

                    // Preparar a QUERY
                    $stmt = $conn->prepare($sql);

                    // Executar a QUERY
                    $stmt->execute($values);

                    // Criar o array com ID do nível de acesso para salvar no log
                    $accessLevelArrayId[] = $accessLevelId;
                }
            }

            // Gerar log de sucesso
            GenerateLog::generateLog("info", "Páginas cadastradas para o nível de acesso.", ['adms_access_level_id' => $accessLevelArrayId]);

            // Acessa somente o commit se cadastrou alguma página para o nível de acesso
            if ($accessLevelArrayId ?? false) {
                // Operação SQL concluída com êxito
                $conn->commit();
            }

            return true;
        } catch (Exception $e) {

            // Operação SQL não é concluída com êxito
            $this->getConnection()->rollBack();

            // Gerar log de erro
            GenerateLog::generateLog("error", "Páginas não cadastradas para o nível de acesso.", ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Inicializa automaticamente as permissões de um NOVO nível de acesso.
     *
     * - Garante que TODAS as páginas ativas existam em `adms_access_levels_pages`
     *   para o nível informado.
     * - Define permission = 1 para:
     *     * páginas públicas (`public_page = 1`),
     *     * páginas padrão (`default_page = 1`) — normalmente privadas, mas liberadas na matriz para todo nível novo,
     *     * páginas cujos controllers estão em $basicControllers
     * - Define permission = 0 para as demais (inclui nível super administrador id 1 em páginas estritamente privadas).
     *
     * @param int $accessLevelId ID do nível de acesso recém-criado
     * @return bool
     */
    public function initializeForNewAccessLevel(int $accessLevelId): bool
    {
        if ($accessLevelId <= 0) {
            return false;
        }

        try {
            $conn = $this->getConnection();
            $conn->beginTransaction();

            // Buscar todas as páginas ativas com seus metadados
            $sqlPages = 'SELECT id, controller, public_page, default_page
                         FROM adms_pages
                         WHERE page_status = 1';
            $stmtPages = $conn->prepare($sqlPages);
            $stmtPages->execute();
            $pages = $stmtPages->fetchAll(PDO::FETCH_ASSOC) ?: [];

            if (empty($pages)) {
                $conn->commit();
                return true;
            }

            $sqlInsert = 'INSERT INTO adms_access_levels_pages
                            (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
                          VALUES (:permission, :level_id, :page_id, :created_at, :updated_at)
                          ON DUPLICATE KEY UPDATE permission = VALUES(permission), updated_at = VALUES(updated_at)';
            $stmtInsert = $conn->prepare($sqlInsert);

            $now = date('Y-m-d H:i:s');

            foreach ($pages as $page) {
                $pageId      = (int)($page['id'] ?? 0);
                $controller  = $page['controller'] ?? '';
                $publicPage  = (int)($page['public_page'] ?? 0);
                $defaultPage = (int)($page['default_page'] ?? 0);

                if ($pageId <= 0) {
                    continue;
                }

                $isBasic    = in_array($controller, $this->basicControllers, true);
                $isPublic   = $publicPage === 1;
                $isDefault  = $defaultPage === 1;
                $permission = ($isPublic || $isDefault || $isBasic) ? 1 : 0;

                $stmtInsert->bindValue(':permission', $permission, PDO::PARAM_INT);
                $stmtInsert->bindValue(':level_id', $accessLevelId, PDO::PARAM_INT);
                $stmtInsert->bindValue(':page_id', $pageId, PDO::PARAM_INT);
                $stmtInsert->bindValue(':created_at', $now);
                $stmtInsert->bindValue(':updated_at', $now);
                $stmtInsert->execute();
            }

            $conn->commit();

            GenerateLog::generateLog('info', 'Permissões padrão inicializadas para novo nível de acesso.', [
                'adms_access_level_id' => $accessLevelId,
            ]);

            return true;
        } catch (Exception $e) {
            try {
                $conn = $this->getConnection();
                if (method_exists($conn, 'inTransaction') && $conn->inTransaction()) {
                    $conn->rollBack();
                }
            } catch (\Throwable $rollbackEx) {
                // silêncio em rollback
            }

            GenerateLog::generateLog('error', 'Falha ao inicializar permissões padrão para novo nível de acesso.', [
                'adms_access_level_id' => $accessLevelId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Copia TODAS as permissões (0 e 1) de um nível de acesso origem
     * para um nível de acesso destino, substituindo completamente
     * as permissões anteriores do destino.
     *
     * @param int $sourceLevelId ID do nível de acesso origem
     * @param int $targetLevelId ID do nível de acesso destino
     * @return bool
     */
    public function copyAccessLevelPermissions(int $sourceLevelId, int $targetLevelId): bool
    {
        self::$lastErrorMessage = null;

        if ($sourceLevelId <= 0 || $targetLevelId <= 0) {
            self::$lastErrorMessage = 'Níveis de acesso origem/destino inválidos.';
            return false;
        }

        // Nunca permitir copiar para ou a partir do Super Admin (ID 1)
        if ($sourceLevelId === UserAccessHelper::SUPER_ADMIN_LEVEL_ID
            || $targetLevelId === UserAccessHelper::SUPER_ADMIN_LEVEL_ID) {
            self::$lastErrorMessage = 'Não é permitido copiar permissões envolvendo o Super Administrador.';
            return false;
        }

        try {
            $conn = $this->getConnection();
            $conn->beginTransaction();

            // Apagar todas as permissões atuais do nível destino
            $sqlDelete = 'DELETE FROM adms_access_levels_pages
                          WHERE adms_access_level_id = :target_id';
            $stmtDelete = $conn->prepare($sqlDelete);
            $stmtDelete->bindValue(':target_id', $targetLevelId, PDO::PARAM_INT);
            $stmtDelete->execute();

            // Copiar permissões da origem para o destino
            $now     = date('Y-m-d H:i:s');
            $sqlCopy = 'INSERT INTO adms_access_levels_pages
                            (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
                        SELECT 
                            permission,
                            :target_id AS adms_access_level_id,
                            adms_page_id,
                            :created_at AS created_at,
                            :updated_at AS updated_at
                        FROM adms_access_levels_pages
                        WHERE adms_access_level_id = :source_id';

            $stmtCopy = $conn->prepare($sqlCopy);
            $stmtCopy->bindValue(':target_id', $targetLevelId, PDO::PARAM_INT);
            $stmtCopy->bindValue(':source_id', $sourceLevelId, PDO::PARAM_INT);
            $stmtCopy->bindValue(':created_at', $now);
            $stmtCopy->bindValue(':updated_at', $now);
            $stmtCopy->execute();

            $conn->commit();

            GenerateLog::generateLog('info', 'Permissões de nível de acesso copiadas.', [
                'source_level' => $sourceLevelId,
                'target_level' => $targetLevelId,
            ]);

            return true;
        } catch (Exception $e) {
            try {
                $conn = $this->getConnection();
                if (method_exists($conn, 'inTransaction') && $conn->inTransaction()) {
                    $conn->rollBack();
                }
            } catch (\Throwable $rollbackEx) {
                // silêncio
            }

            self::$lastErrorMessage = $e->getMessage();
            GenerateLog::generateLog('error', 'Erro ao copiar permissões entre níveis de acesso.', [
                'source_level' => $sourceLevelId,
                'target_level' => $targetLevelId,
                'error'        => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Atualiza as permissões de páginas associadas a um nível de acesso.
     *
     * COMPORTAMENTO CORRIGIDO:
     * - Processa TODAS as permissões enviadas com seus valores (0 ou 1)
     * - ADICIONA novas permissões se não existirem
     * - ATUALIZA permissões existentes com novos valores
     * - Cada checkbox desmarcado envia valor 0, cada checkbox marcado envia valor 1
     *
     * @param array $data Dados contendo as permissões de páginas a serem atualizadas.
     * @return bool Retorna `true` se a operação foi bem-sucedida, ou `false` em caso de erro.
     */
    public function updateAccessLevelPages(array $data): bool
    {
        self::$lastErrorMessage = null;
        // Log de debug detalhado
        error_log('=== UPDATEACCESSLEVELPAGES INICIADO ===');
        error_log('Timestamp: ' . date('Y-m-d H:i:s'));
        error_log('updateAccessLevelPages chamado com dados: ' . json_encode($data));
        
        // Log específico para desktop vs mobile
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'não definido';
        error_log('User-Agent no repositório: ' . $userAgent);
        
        if (strpos($userAgent, 'Mobile') !== false || strpos($userAgent, 'Android') !== false || strpos($userAgent, 'iPhone') !== false) {
            error_log('🔍 REPOSITÓRIO: REQUISIÇÃO IDENTIFICADA COMO MOBILE');
        } else {
            error_log('🔍 REPOSITÓRIO: REQUISIÇÃO IDENTIFICADA COMO DESKTOP');
        }

        $accessLevelId = (int) ($data['adms_access_level_id'] ?? 0);
        if ($accessLevelId <= 0) {
            self::$lastErrorMessage = 'Nível de acesso inválido (ID não informado).';
            return false;
        }
        if ($accessLevelId === UserAccessHelper::SUPER_ADMIN_LEVEL_ID) {
            self::$lastErrorMessage = 'Permissão para o Super Administrador não pode ser editada.';
            GenerateLog::generateLog("error", "Permissão para o Super Administrador não pode ser editada.", ['id' => $accessLevelId]);
            $_SESSION['error'] = "Permissão para o Super Administrador não pode ser editada!";
            return false;
        }

        try {
            // Marca o ponto inicial de uma transação SQL
            $this->getConnection()->beginTransaction();

            // Criar o elemento permissions no array quando não vem do formulário
            $data['permissions'] = $data['permissions'] ?? [];
            
            // Garantir que permissions seja sempre um array
            if (!is_array($data['permissions'])) {
                error_log('permissions não é um array, convertendo...');
                if (is_string($data['permissions'])) {
                    // Se for string, tentar decodificar JSON
                    $decoded = json_decode($data['permissions'], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $data['permissions'] = $decoded;
                    } else {
                        // Se não for JSON válido, criar array vazio
                        $data['permissions'] = [];
                        error_log('String não é JSON válido, criando array vazio');
                    }
                } else {
                    // Se for outro tipo, criar array vazio
                    $data['permissions'] = [];
                }
            }
            
            // Verificar se ainda não é um array válido
            if (!is_array($data['permissions'])) {
                error_log('permissions ainda não é um array válido após conversão');
                $data['permissions'] = [];
            }
            
            // Log de debug
            error_log('permissions após processamento: ' . json_encode($data['permissions']));
            error_log('Tipo final de permissions: ' . gettype($data['permissions']));
            error_log('permissions é array? ' . (is_array($data['permissions']) ? 'Sim' : 'Não'));
            error_log('Tamanho permissions: ' . count($data['permissions']));
            error_log('Primeiros 5 elementos permissions: ' . json_encode(array_slice($data['permissions'], 0, 5, true)));

            // Recuperar todas as páginas cadastradas para o nível de acesso
            $resultAccessLevelsPages = $this->getPagesAccessLevelsArray($accessLevelId);
            $resultAccessLevelsPages = $resultAccessLevelsPages ? $resultAccessLevelsPages : [];
            
            // Log de debug
            error_log('Páginas existentes no BD: ' . json_encode($resultAccessLevelsPages));

            // Recuperar as páginas que nível de acesso tem permissão de acessar
            $resultAccessLevelsPagesPermissions = $this->getPagesAccessLevelsArray($accessLevelId, true);
            $resultAccessLevelsPagesPermissions = $resultAccessLevelsPagesPermissions ? $resultAccessLevelsPagesPermissions : [];
            
            // Log de debug
            error_log('Páginas com permissão no BD: ' . json_encode($resultAccessLevelsPagesPermissions));

            // Processar todas as permissões com seus valores (0 ou 1)
            foreach ($data['permissions'] as $pageId => $permissionValue) {
                // Validar se o ID da página é válido (evita INSERT com page_id=0 que gera Duplicate key)
                if ($pageId === '' || $pageId === null || !is_numeric($pageId)) {
                    error_log('ID de página inválido ignorado: ' . var_export($pageId, true));
                    continue;
                }
                $pageId = (int) $pageId;
                if ($pageId <= 0) {
                    error_log('ID de página inválido (<=0) ignorado: ' . $pageId);
                    continue;
                }
                $permissionValue = (int) $permissionValue; // 0 ou 1
                
                error_log('Processando página ID: ' . $pageId . ' com permissão: ' . $permissionValue);

                $now = date('Y-m-d H:i:s');
                $sql = 'INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
                        VALUES (:permission, :adms_access_level_id, :adms_page_id, :created_at, :updated_at)
                        ON DUPLICATE KEY UPDATE permission = VALUES(permission), updated_at = VALUES(updated_at)';
                $stmt = $this->getConnection()->prepare($sql);
                $stmt->bindValue(':permission', $permissionValue, PDO::PARAM_INT);
                $stmt->bindValue(':adms_access_level_id', $accessLevelId, PDO::PARAM_INT);
                $stmt->bindValue(':adms_page_id', $pageId, PDO::PARAM_INT);
                $stmt->bindValue(':created_at', $now);
                $stmt->bindValue(':updated_at', $now);
                $stmt->execute();
                error_log('Página gravada (upsert): ' . $pageId . ' com permissão: ' . $permissionValue);

                if (is_array($resultAccessLevelsPagesPermissions) && array_key_exists($pageId, $resultAccessLevelsPagesPermissions)) {
                    unset($resultAccessLevelsPagesPermissions[$pageId]);
                }
            }

            // IMPORTANTE: Todas as permissões foram processadas
            // O sistema agora processa cada permissão individualmente com seu valor (0 ou 1)
            
            if ($resultAccessLevelsPagesPermissions) {
                error_log('ATENÇÃO: ' . count($resultAccessLevelsPagesPermissions) . ' permissões existentes não foram processadas');
                error_log('Páginas não processadas: ' . json_encode($resultAccessLevelsPagesPermissions));
                
                // Log das permissões que não foram processadas
                foreach ($resultAccessLevelsPagesPermissions as $pageId) {
                    error_log('Permissão NÃO PROCESSADA para página: ' . $pageId);
                }
            }

            // Operação SQL concluída com êxito
            $this->getConnection()->commit();
            
            // Log de debug
            error_log('updateAccessLevelPages concluído com sucesso');
            error_log('Total de páginas processadas: ' . count($data['permissions']));
            error_log('Permissões salvas para o nível de acesso: ' . $accessLevelId);
            
            // Log detalhado da verificação
            error_log('=== VERIFICAÇÃO APÓS SALVAMENTO ===');
            
            // Verificar se as permissões foram realmente salvas
            $verificacao = $this->getPagesAccessLevelsArray($accessLevelId, true);
            error_log('Verificação após salvamento - Páginas com permissão: ' . json_encode($verificacao));
            error_log('Total de páginas com permissão após salvamento: ' . count($verificacao));
            
            // Verificação adicional: consultar diretamente o banco
            $sqlVerificacao = "SELECT adms_page_id, permission FROM adms_access_levels_pages WHERE adms_access_level_id = :adms_access_level_id ORDER BY adms_page_id LIMIT 10";
            $stmtVerificacao = $this->getConnection()->prepare($sqlVerificacao);
            $stmtVerificacao->bindValue(':adms_access_level_id', $accessLevelId, PDO::PARAM_INT);
            $stmtVerificacao->execute();
            $verificacaoDireta = $stmtVerificacao->fetchAll(PDO::FETCH_ASSOC);
            error_log('Verificação direta no banco (primeiras 10): ' . json_encode($verificacaoDireta));
            
            // Verificar se há permissões com valor 0
            $sqlVerificacao0 = "SELECT COUNT(*) as total FROM adms_access_levels_pages WHERE adms_access_level_id = :adms_access_level_id AND permission = 0";
            $stmtVerificacao0 = $this->getConnection()->prepare($sqlVerificacao0);
            $stmtVerificacao0->bindValue(':adms_access_level_id', $accessLevelId, PDO::PARAM_INT);
            $stmtVerificacao0->execute();
            $total0 = $stmtVerificacao0->fetch(PDO::FETCH_ASSOC);
            error_log('Total de permissões com valor 0: ' . $total0['total']);
            
            // Verificar se há permissões com valor 1
            $sqlVerificacao1 = "SELECT COUNT(*) as total FROM adms_access_levels_pages WHERE adms_access_level_id = :adms_access_level_id AND permission = 1";
            $stmtVerificacao1 = $this->getConnection()->prepare($sqlVerificacao1);
            $stmtVerificacao1->bindValue(':adms_access_level_id', $accessLevelId, PDO::PARAM_INT);
            $stmtVerificacao1->execute();
            $total1 = $stmtVerificacao1->fetch(PDO::FETCH_ASSOC);
            error_log('Total de permissões com valor 1: ' . $total1['total']);

            return true;
        } catch (\Throwable $e) {
            self::$lastErrorMessage = $e->getMessage();
            try {
                $conn = $this->getConnection();
                if (method_exists($conn, 'inTransaction') && $conn->inTransaction()) {
                    $conn->rollBack();
                }
            } catch (\Throwable $rollbackEx) {
                // ignora falha no rollback
            }
            GenerateLog::generateLog("error", "Permissão de acesso à página pelo nível de acesso não editada.", [
                'id' => $accessLevelId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            error_log('Erro em updateAccessLevelPages: ' . $e->getMessage());
            error_log('Trace: ' . $e->getTraceAsString());
            return false;
        }
    }
}
