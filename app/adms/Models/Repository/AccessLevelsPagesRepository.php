<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use Exception;
use Generator;
use PDO;

/**
 * Repositório responsável pelas operações relacionadas às páginas associadas aos níveis de acesso.
 *
 * Esta classe gerencia a recuperação e inserção de páginas associadas a níveis de acesso no banco de dados.
 * Ela oferece métodos para obter as páginas vinculadas a um determinado nível de acesso e para realizar 
 * a inserção em massa de novas associações entre níveis de acesso e páginas.
 *
 * @package App\adms\Models\Repository
 */
class AccessLevelsPagesRepository extends DbConnection
{
    /** Última mensagem de erro (para retorno ao controller em caso de falha) */
    private static ?string $lastErrorMessage = null;

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
     * @return array|bool Retorna um array com os IDs das páginas ou `false` se não houver resultados.
     */
    public function getPagesAccessLevelsArray(int $accessLevel, bool $permission = false): array|bool
    {
        // QUERY para recuperar os registros do banco de dados
        $sql = 'SELECT adms_page_id
                FROM adms_access_levels_pages
                WHERE adms_access_level_id = :adms_access_level_id';

        // Acessa o if quando retornar somente as paginas que tiverem permissão 1
        if ($permission) {
            $sql .= " AND permission = 1";
        }

        // Preparar a QUERY
        $stmt = $this->getConnection()->prepare($sql);

        // Substituir os parâmetros da QUERY pelos valores
        $stmt->bindValue(':adms_access_level_id', $accessLevel, PDO::PARAM_INT);

        // Executar a QUERY
        $stmt->execute();

        // Ler os registros
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Retornar array indexado para facilitar verificação de permissões
        if ($result) {
            if ($permission) {
                // Para verificação de permissões, retornar array indexado
                $indexedArray = [];
                foreach ($result as $row) {
                    $indexedArray[$row['adms_page_id']] = true;
                }
                return $indexedArray;
            } else {
                // Para operações de atualização, retornar array simples
                return array_column($result, 'adms_page_id');
            }
        }
        
        return [];
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
            $this->getConnection()->beginTransaction();

            // Array para armazenar ID do nível de acesso para salvar no log
            $accessLevelArrayId = [];

            // Percorrer o array com nível de acesso e páginas
            foreach ($data as $accessLevelId => $accessLevelPages) {

                // Array para acumular os valores
                $values = [];
                $placeholders = [];

                // Percorrer o array de páginas que o nível de acesso não tem permissão de acessar
                foreach ($accessLevelPages as $pageId) {
                    $values[] = $accessLevelId == 1 ? 1 : 0;
                    $values[] = $accessLevelId;
                    $values[] = $pageId;
                    $values[] = date("Y-m-d H:i:s");
                    $placeholders[] = "(?, ?, ?, ?)";
                }

                // Criar QUERY somente se o nível de acesso não tem página cadastrada
                if ($accessLevelPages ?? false) {

                    // QUERY para cadastrar em massa as páginas para o nível de acesso
                    $sql = "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at) VALUES " . implode(", ", $placeholders);

                    // Preparar a QUERY
                    $stmt = $this->getConnection()->prepare($sql);

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
                $this->getConnection()->commit();
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
        if ($accessLevelId === 1) {
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

                // Verificar se a página não está cadastrada para o nível de acesso
                if (!in_array($pageId, $resultAccessLevelsPages)) {
                    // QUERY para cadastrar página para o nível de acesso
                    $sql = 'INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at) VALUES (:permission, :adms_access_level_id, :adms_page_id, :created_at)';
                    $stmt = $this->getConnection()->prepare($sql);
                    $stmt->bindValue(':permission', $permissionValue, PDO::PARAM_INT);
                    $stmt->bindValue(':adms_access_level_id', $accessLevelId, PDO::PARAM_INT);
                    $stmt->bindValue(':adms_page_id', $pageId, PDO::PARAM_INT);
                    $stmt->bindValue(':created_at', date("Y-m-d H:i:s"));
                    $stmt->execute();
                    error_log('Página inserida: ' . $pageId . ' com permissão: ' . $permissionValue);
                } else {
                    // QUERY para atualizar página para o nível de acesso
                    $sql = 'UPDATE adms_access_levels_pages SET permission = :permission, updated_at = :updated_at WHERE adms_access_level_id = :adms_access_level_id AND adms_page_id = :adms_page_id';
                    $stmt = $this->getConnection()->prepare($sql);
                    $stmt->bindValue(':permission', $permissionValue, PDO::PARAM_INT);
                    $stmt->bindValue(':updated_at', date("Y-m-d H:i:s"));
                    $stmt->bindValue(':adms_access_level_id', $accessLevelId, PDO::PARAM_INT);
                    $stmt->bindValue(':adms_page_id', $pageId, PDO::PARAM_INT);
                    
                    // Log detalhado da query
                    error_log('🔍 EXECUTANDO UPDATE:');
                    error_log('   SQL: ' . $sql);
                    error_log('   Parâmetros: permission=' . $permissionValue . ', updated_at=' . date("Y-m-d H:i:s") . ', adms_access_level_id=' . $accessLevelId . ', pageId=' . $pageId);
                    
                    $stmt->execute();
                    
                    // Verificar se o UPDATE realmente alterou alguma linha
                    $rowCount = $stmt->rowCount();
                    error_log('   Linhas afetadas pelo UPDATE: ' . $rowCount);
                    
                    if ($rowCount === 0) {
                        error_log('   ⚠️ ATENÇÃO: UPDATE não alterou nenhuma linha!');
                        // Verificar o valor atual no banco
                        $sqlCheck = "SELECT permission FROM adms_access_levels_pages WHERE adms_access_level_id = :adms_access_level_id AND adms_page_id = :pageId LIMIT 1";
                        $stmtCheck = $this->getConnection()->prepare($sqlCheck);
                        $stmtCheck->bindValue(':adms_access_level_id', $accessLevelId, PDO::PARAM_INT);
                        $stmtCheck->bindValue(':pageId', $pageId, PDO::PARAM_INT);
                        $stmtCheck->execute();
                        $currentValue = $stmtCheck->fetch(PDO::FETCH_ASSOC);
                        error_log('   Valor atual no banco: ' . ($currentValue ? $currentValue['permission'] : 'não encontrado'));
                    }
                    
                    error_log('Página atualizada: ' . $pageId . ' com permissão: ' . $permissionValue);
                    
                    // Remover da lista de permissões ativas se foi processada (é array [page_id => true])
                    if (is_array($resultAccessLevelsPagesPermissions) && array_key_exists($pageId, $resultAccessLevelsPagesPermissions)) {
                        unset($resultAccessLevelsPagesPermissions[$pageId]);
                    }
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
