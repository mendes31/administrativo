<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;
use Exception;

class LgpdConsentimentosRepository extends DbConnection
{
    /**
     * Obtém todos os consentimentos
     *
     * @return array
     */
    public function getAllConsentimentos(): array
    {
        try {
            $query = "SELECT 
                        id,
                        lgpd_termo_id,
                        adms_user_id,
                        titular_nome,
                        titular_email,
                        finalidade,
                        canal,
                        data_consentimento,
                        status,
                        created_at,
                        updated_at
                      FROM lgpd_consentimentos 
                      ORDER BY created_at DESC";
            
            $stmt = $this->getConnection()->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Erro ao buscar consentimentos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtém consentimento por ID
     *
     * @param int $id
     * @return array|null
     */
    public function getConsentimentoById(int $id): ?array
    {
        try {
            $query = "SELECT 
                        id,
                        lgpd_termo_id,
                        adms_user_id,
                        titular_nome,
                        titular_email,
                        finalidade,
                        canal,
                        data_consentimento,
                        status,
                        versao_termo,
                        ip_address,
                        user_agent,
                        consent_hash,
                        timestamp_milliseconds,
                        created_by_user_id,
                        revoked_by_user_id,
                        revoked_at,
                        revocation_reason,
                        updated_by_user_id,
                        collection_method,
                        referrer_url,
                        origin_url,
                        created_at,
                        updated_at
                      FROM lgpd_consentimentos 
                      WHERE id = :id";
            
            $stmt = $this->getConnection()->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\Exception $e) {
            error_log("Erro ao buscar consentimento por ID: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return null;
        }
    }

    /**
     * Cria um novo consentimento
     *
     * @param array $data
     * @return int|false Retorna o ID do consentimento criado ou false em caso de erro
     */
    public function create(array $data): int|false
    {
        try {
            // Campos básicos (sempre presentes)
            $campos = ['titular_nome', 'titular_email', 'finalidade', 'canal', 'data_consentimento', 'status', 'versao_termo'];
            $valores = [':titular_nome', ':titular_email', ':finalidade', ':canal', ':data_consentimento', ':status', ':versao_termo'];
            
            // Campos de auditoria (opcionais, se existirem no $data)
            $camposAuditoria = [
                'ip_address', 'user_agent', 'consent_hash', 'timestamp_milliseconds',
                'created_by_user_id', 'collection_method', 'referrer_url', 'origin_url',
                'adms_user_id'
            ];
            
            // Campos opcionais principais (não exatamente "auditoria", mas auxiliares)
            if (isset($data['lgpd_termo_id'])) {
                $campos[] = 'lgpd_termo_id';
                $valores[] = ':lgpd_termo_id';
            }

            foreach ($camposAuditoria as $campo) {
                if (isset($data[$campo])) {
                    $campos[] = $campo;
                    $valores[] = ':' . $campo;
                }
            }
            
            $query = "INSERT INTO lgpd_consentimentos 
                      (" . implode(', ', $campos) . ") 
                      VALUES (" . implode(', ', $valores) . ")";
            
            $stmt = $this->getConnection()->prepare($query);
            
            // Bind dos campos básicos
            $stmt->bindParam(':titular_nome', $data['titular_nome'], PDO::PARAM_STR);
            $stmt->bindParam(':titular_email', $data['titular_email'], PDO::PARAM_STR);
            $stmt->bindParam(':finalidade', $data['finalidade'], PDO::PARAM_STR);
            $stmt->bindParam(':canal', $data['canal'], PDO::PARAM_STR);
            $stmt->bindParam(':data_consentimento', $data['data_consentimento'], PDO::PARAM_STR);
            $stmt->bindParam(':status', $data['status'], PDO::PARAM_STR);
            $versao = $data['versao_termo'] ?? null;
            $stmt->bindParam(':versao_termo', $versao, PDO::PARAM_STR);
            
            // Bind dos campos de auditoria (se existirem)
            foreach ($camposAuditoria as $campo) {
                if (isset($data[$campo])) {
                    $tipo = in_array($campo, ['created_by_user_id', 'timestamp_milliseconds', 'adms_user_id']) ? PDO::PARAM_INT : PDO::PARAM_STR;
                    $stmt->bindParam(':' . $campo, $data[$campo], $tipo);
                }
            }

            // Bind do termo, se informado
            if (isset($data['lgpd_termo_id'])) {
                $termoId = (int)$data['lgpd_termo_id'];
                $stmt->bindParam(':lgpd_termo_id', $termoId, PDO::PARAM_INT);
            }
            
            $success = $stmt->execute();
            
            if ($success) {
                $consentId = (int)$this->getConnection()->lastInsertId();

                // Registrar no histórico interno LGPD
                if ($this->hasHistoryTable()) {
                    // Criação não tem motivo de alteração
                    $this->addHistory($consentId, 'criado', $data['created_by_user_id'] ?? null, null, $data, null);
                }

                // Registrar no Log de Modificações (Administração)
                if (!empty($data['created_by_user_id'])) {
                    $dadosDepois = $data;
                    $dadosDepois['id'] = $consentId;
                    LogAlteracaoService::registrarAlteracao(
                        'lgpd_consentimentos',
                        $consentId,
                        (int)$data['created_by_user_id'],
                        'INSERT',
                        [],
                        $dadosDepois
                    );
                }
                
                return $consentId;
            }
            
            return false;
        } catch (\Exception $e) {
            error_log("Erro ao criar consentimento: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica se a tabela de histórico existe
     * 
     * @return bool
     */
    private function hasHistoryTable(): bool
    {
        try {
            $query = "SHOW TABLES LIKE 'lgpd_consentimentos_historico'";
            $stmt = $this->getConnection()->prepare($query);
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Retorna o último ID inserido na tabela de consentimentos.
     */
    public function getLastInsertId(): int
    {
        try {
            return (int)$this->getConnection()->lastInsertId();
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * Adiciona registro no histórico de alterações
     * 
     * @param int $consentId
     * @param string $acao
     * @param int|null $userId
     * @param array|null $dadosAnteriores
     * @param array|null $dadosNovos
     * @param string|null $motivo
     * @return bool
     */
    private function addHistory(int $consentId, string $acao, ?int $userId, ?array $dadosAnteriores, ?array $dadosNovos, ?string $motivo = null): bool
    {
        try {
            $query = "INSERT INTO lgpd_consentimentos_historico 
                      (consentimento_id, acao, usuario_id, dados_anteriores, dados_novos, motivo, ip_address, user_agent)
                      VALUES (:consentimento_id, :acao, :usuario_id, :dados_anteriores, :dados_novos, :motivo, :ip_address, :user_agent)";
            
            $stmt = $this->getConnection()->prepare($query);
            $stmt->bindValue(':consentimento_id', $consentId, PDO::PARAM_INT);
            $stmt->bindValue(':acao', $acao);
            $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':dados_anteriores', $dadosAnteriores ? json_encode($dadosAnteriores, JSON_UNESCAPED_UNICODE) : null);
            $stmt->bindValue(':dados_novos', $dadosNovos ? json_encode($dadosNovos, JSON_UNESCAPED_UNICODE) : null);
            $stmt->bindValue(':motivo', $motivo);
            $stmt->bindValue(':ip_address', $_SERVER['REMOTE_ADDR'] ?? null);
            $stmt->bindValue(':user_agent', $_SERVER['HTTP_USER_AGENT'] ?? null);
            
            return $stmt->execute();
        } catch (\Exception $e) {
            error_log("Erro ao adicionar histórico: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Atualiza um consentimento
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        error_log("LGPD Repository Update: Iniciando atualização do consentimento ID: {$id}");
        error_log("LGPD Repository Update: Dados recebidos: " . json_encode($data));
        
        try {
            // Campos básicos sempre presentes
            $campos = [
                'titular_nome = :titular_nome',
                'titular_email = :titular_email',
                'finalidade = :finalidade',
                'canal = :canal',
                'data_consentimento = :data_consentimento',
                'status = :status',
                'versao_termo = :versao_termo',
                'updated_at = NOW()'
            ];

            // Adicionar lgpd_termo_id se fornecido
            if (isset($data['lgpd_termo_id']) && !empty($data['lgpd_termo_id'])) {
                $campos[] = 'lgpd_termo_id = :lgpd_termo_id';
            } else {
                // Se não fornecido, definir como NULL para permitir desvincular
                $campos[] = 'lgpd_termo_id = NULL';
            }

            $query = "UPDATE lgpd_consentimentos 
                      SET " . implode(', ', $campos) . "
                      WHERE id = :id";
            
            error_log("LGPD Repository Update: Query: " . $query);
            
            $stmt = $this->getConnection()->prepare($query);
            
            if (!$stmt) {
                $errorInfo = $this->getConnection()->errorInfo();
                error_log("LGPD Repository Update: Erro ao preparar query: " . json_encode($errorInfo));
                return false;
            }
            
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':titular_nome', $data['titular_nome'] ?? '', PDO::PARAM_STR);
            $stmt->bindValue(':titular_email', $data['titular_email'] ?? null, $data['titular_email'] ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':finalidade', $data['finalidade'] ?? '', PDO::PARAM_STR);
            $stmt->bindValue(':canal', $data['canal'] ?? null, $data['canal'] ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':data_consentimento', $data['data_consentimento'] ?? date('Y-m-d'), PDO::PARAM_STR);
            $stmt->bindValue(':status', $data['status'] ?? 'Ativo', PDO::PARAM_STR);
            $versao = !empty($data['versao_termo']) ? $data['versao_termo'] : null;
            $stmt->bindValue(':versao_termo', $versao, $versao ? PDO::PARAM_STR : PDO::PARAM_NULL);
            
            if (isset($data['lgpd_termo_id']) && !empty($data['lgpd_termo_id'])) {
                $termoId = (int)$data['lgpd_termo_id'];
                $stmt->bindValue(':lgpd_termo_id', $termoId, PDO::PARAM_INT);
            }
            
            error_log("LGPD Repository Update: Executando query...");
            $result = $stmt->execute();
            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                error_log("LGPD Repository Update: Erro ao executar query: " . json_encode($errorInfo));
            } else {
                error_log("LGPD Repository Update: Query executada com sucesso. Linhas afetadas: " . $stmt->rowCount());
            }
            
            return $result;
        } catch (\Exception $e) {
            error_log("LGPD Repository Update: Exceção capturada - " . $e->getMessage());
            error_log("LGPD Repository Update: Stack trace: " . $e->getTraceAsString());
            error_log("LGPD Repository Update: Arquivo: " . $e->getFile() . " - Linha: " . $e->getLine());
            return false;
        } catch (\Throwable $e) {
            error_log("LGPD Repository Update: Erro fatal - " . $e->getMessage());
            error_log("LGPD Repository Update: Stack trace: " . $e->getTraceAsString());
            error_log("LGPD Repository Update: Arquivo: " . $e->getFile() . " - Linha: " . $e->getLine());
            return false;
        }
    }

    /**
     * Exclui um consentimento
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id, ?int $userId = null, ?string $motivo = null): bool
    {
        try {
            // Buscar dados anteriores para histórico e log
            $dadosAnteriores = $this->getConsentimentoById($id);

            $query = "DELETE FROM lgpd_consentimentos WHERE id = :id";
            
            $stmt = $this->getConnection()->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            $ok = $stmt->execute();

            if ($ok && $stmt->rowCount() > 0 && $dadosAnteriores) {
                // Registrar no histórico LGPD
                if ($this->hasHistoryTable()) {
                    $this->addHistory($id, 'deletado', $userId, $dadosAnteriores, null, $motivo);
                }

                // Registrar no Log de Modificações
                if (!empty($userId)) {
                    LogAlteracaoService::registrarAlteracao(
                        'lgpd_consentimentos',
                        $id,
                        $userId,
                        'DELETE',
                        $dadosAnteriores,
                        []
                    );
                }
            }

            return $ok;
        } catch (\Exception $e) {
            error_log("Erro ao excluir consentimento: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Revoga um consentimento
     *
     * @param int $id
     * @param int|null $userId ID do usuário que está revogando
     * @param string|null $motivo Motivo da revogação
     * @return bool
     */
    public function revogarConsentimento(int $id, ?int $userId = null, ?string $motivo = null): bool
    {
        try {
            // Buscar dados anteriores para histórico
            $dadosAnteriores = $this->getConsentimentoById($id);
            
            $query = "UPDATE lgpd_consentimentos 
                      SET status = 'Revogado', 
                          revoked_by_user_id = :revoked_by_user_id,
                          revoked_at = NOW(),
                          revocation_reason = :revocation_reason,
                          updated_at = NOW()
                      WHERE id = :id";

            $conn = $this->getConnection();
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
            $stmt->bindValue(':revoked_by_user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':revocation_reason', $motivo);

            $ok = $stmt->execute();

            if (!$ok || $stmt->rowCount() === 0) {
                error_log("LGPD: Nenhuma linha atualizada ao revogar consentimento. ID={$id}");
            } else {
                error_log("LGPD: Consentimento ID={$id} revogado com sucesso. Linhas afetadas=" . $stmt->rowCount());
                
                // Registrar no histórico
                if ($this->hasHistoryTable() && $dadosAnteriores) {
                    $dadosNovos = $dadosAnteriores;
                    $dadosNovos['status'] = 'Revogado';
                    $dadosNovos['revoked_by_user_id'] = $userId;
                    $dadosNovos['revoked_at'] = date('Y-m-d H:i:s');
                    $dadosNovos['revocation_reason'] = $motivo;
                    
                    $this->addHistory($id, 'revogado', $userId, $dadosAnteriores, $dadosNovos, $motivo);
                }

                // Registrar no Log de Modificações
                if (!empty($userId)) {
                    LogAlteracaoService::registrarAlteracao(
                        'lgpd_consentimentos',
                        $id,
                        $userId,
                        'UPDATE',
                        $dadosAnteriores ?? [],
                        $dadosNovos ?? []
                    );
                }
            }

            return $ok && $stmt->rowCount() > 0;
        } catch (\Exception $e) {
            error_log("Erro ao revogar consentimento: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtém o último consentimento ATIVO de um titular pelo e-mail,
     * opcionalmente filtrando por canal.
     *
     * Esta consulta é usada no login para confirmar se já existe um
     * consentimento válido para a versão atual do termo.
     *
     * @param string $email
     * @param string|null $canal
     * @return array|null
     */
    public function getUltimoConsentimentoAtivoPorEmail(string $email, ?string $canal = null): ?array
    {
        if (empty($email)) {
            return null;
        }

        try {
            $query = "SELECT 
                          id,
                          titular_nome,
                          titular_email,
                          finalidade,
                          canal,
                          data_consentimento,
                          status,
                          versao_termo,
                          created_at,
                          updated_at
                      FROM lgpd_consentimentos
                      WHERE titular_email = :email
                        AND status = 'Ativo'";

            if (!empty($canal)) {
                $query .= " AND canal = :canal";
            }

            $query .= " ORDER BY data_consentimento DESC, id DESC
                        LIMIT 1";

            $stmt = $this->getConnection()->prepare($query);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            if (!empty($canal)) {
                $stmt->bindParam(':canal', $canal, PDO::PARAM_STR);
            }

            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result !== false ? $result : null;
        } catch (\Exception $e) {
            error_log("Erro ao buscar último consentimento ativo por e-mail: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtém o último consentimento ATIVO de um usuário (adms_users.id),
     * opcionalmente filtrando por canal.
     *
     * Este método é o preferencial para o fluxo de login,
     * pois não depende de e-mail ou CPF.
     *
     * @param int $userId
     * @param string|null $canal
     * @return array|null
     */
    public function getUltimoConsentimentoAtivoPorUsuario(int $userId, ?string $canal = null): ?array
    {
        if ($userId <= 0) {
            return null;
        }

        try {
            $query = "SELECT 
                          id,
                          adms_user_id,
                          titular_nome,
                          titular_email,
                          finalidade,
                          canal,
                          data_consentimento,
                          status,
                          versao_termo,
                          created_at,
                          updated_at
                      FROM lgpd_consentimentos
                      WHERE adms_user_id = :user_id
                        AND status = 'Ativo'";

            if (!empty($canal)) {
                $query .= " AND canal = :canal";
            }

            $query .= " ORDER BY data_consentimento DESC, id DESC
                        LIMIT 1";

            $stmt = $this->getConnection()->prepare($query);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            if (!empty($canal)) {
                $stmt->bindValue(':canal', $canal, PDO::PARAM_STR);
            }

            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result !== false ? $result : null;
        } catch (\Exception $e) {
            error_log("Erro ao buscar último consentimento ativo por usuário: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtém estatísticas dos consentimentos
     *
     * @return array
     */
    public function getEstatisticas(): array
    {
        try {
            $query = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'Ativo' THEN 1 ELSE 0 END) as ativos,
                        SUM(CASE WHEN status = 'Revogado' THEN 1 ELSE 0 END) as revogados,
                        SUM(CASE WHEN status = 'Expirado' THEN 1 ELSE 0 END) as expirados,
                        SUM(CASE WHEN data_consentimento >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as ultimos_30_dias
                      FROM lgpd_consentimentos";
            
            $stmt = $this->getConnection()->prepare($query);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Erro ao buscar estatísticas: " . $e->getMessage());
            return [
                'total' => 0,
                'ativos' => 0,
                'revogados' => 0,
                'expirados' => 0,
                'ultimos_30_dias' => 0
            ];
        }
    }

    /**
     * Obtém consentimentos por status
     *
     * @param string $status
     * @return array
     */
    public function getConsentimentosPorStatus(string $status): array
    {
        try {
            $query = "SELECT 
                        id,
                        titular_nome,
                        titular_email,
                        finalidade,
                        canal,
                        data_consentimento,
                        status,
                        created_at
                      FROM lgpd_consentimentos 
                      WHERE status = :status
                      ORDER BY created_at DESC";
            
            $stmt = $this->getConnection()->prepare($query);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Erro ao buscar consentimentos por status: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtém consentimentos expirados
     *
     * @return array
     */
    public function getConsentimentosExpirados(): array
    {
        try {
            $query = "SELECT 
                        id,
                        titular_nome,
                        titular_email,
                        finalidade,
                        canal,
                        data_consentimento,
                        status,
                        created_at
                      FROM lgpd_consentimentos 
                      WHERE data_consentimento < DATE_SUB(NOW(), INTERVAL 1 YEAR)
                      AND status = 'Ativo'
                      ORDER BY data_consentimento ASC";
            
            $stmt = $this->getConnection()->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Erro ao buscar consentimentos expirados: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Busca consentimentos por termo
     *
     * @param string $termo
     * @return array
     */
    public function buscarPorTermo(string $termo): array
    {
        try {
            $query = "SELECT 
                        id,
                        titular_nome,
                        titular_email,
                        finalidade,
                        canal,
                        data_consentimento,
                        status,
                        created_at
                      FROM lgpd_consentimentos 
                      WHERE titular_nome LIKE :termo 
                         OR titular_email LIKE :termo 
                         OR finalidade LIKE :termo
                      ORDER BY created_at DESC";
            
            $stmt = $this->getConnection()->prepare($query);
            $termo = "%{$termo}%";
            $stmt->bindParam(':termo', $termo, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Erro ao buscar consentimentos por termo: " . $e->getMessage());
            return [];
        }
    }
}
