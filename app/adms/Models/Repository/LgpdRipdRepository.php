<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use PDO;
use Exception;

/**
 * Repositório para operações com Relatórios de Impacto à Proteção de Dados (RIPD).
 *
 * @package App\adms\Models\Repository
 */
class LgpdRipdRepository extends DbConnection
{
    /**
     * Obtém todos os relatórios RIPD
     *
     * @return array
     */
    public function getAllRipd(): array
    {
        try {
            $query = "SELECT 
                        r.id,
                        r.codigo,
                        r.titulo,
                        r.versao,
                        r.data_elaboracao,
                        r.status,
                        r.data_aprovacao,
                        r.created_at,
                        a.titulo as aipd_titulo,
                        a.codigo as aipd_codigo,
                        a.descricao as aipd_descricao,
                        a.nivel_risco as aipd_nivel_risco,
                        a.status as aipd_status,
                        d.name as departamento_nome,
                        e.name as elaborador_nome,
                        rev.name as revisor_nome,
                        apr.name as aprovador_nome,
                        resp.name as responsavel_implementacao_nome
                      FROM lgpd_ripd r
                      JOIN lgpd_aipd a ON r.aipd_id = a.id
                      JOIN adms_departments d ON a.departamento_id = d.id
                      JOIN adms_users e ON r.elaborador_id = e.id
                      LEFT JOIN adms_users rev ON r.revisor_id = rev.id
                      LEFT JOIN adms_users apr ON r.aprovador_id = apr.id
                      LEFT JOIN adms_users resp ON r.responsavel_implementacao = resp.id
                      ORDER BY r.created_at DESC";
            
            $stmt = $this->getConnection()->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erro ao buscar relatórios RIPD: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtém a quantidade total de relatórios RIPD
     *
     * @return int
     */
    public function getAmountRipd(): int
    {
        try {
            $query = "SELECT COUNT(*) as total FROM lgpd_ripd";
            $stmt = $this->getConnection()->prepare($query);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) ($result['total'] ?? 0);
        } catch (Exception $e) {
            error_log("Erro ao contar relatórios RIPD: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtém um relatório RIPD por ID
     *
     * @param int $id
     * @return array|null
     */
    public function getRipdById(int $id): ?array
    {
        try {
            $query = "SELECT 
                        r.*,
                        a.titulo as aipd_titulo,
                        a.codigo as aipd_codigo,
                        a.descricao as aipd_descricao,
                        a.observacoes as aipd_observacoes,
                        a.nivel_risco as aipd_nivel_risco,
                        a.status as aipd_status,
                        d.name as departamento_nome,
                        e.name as elaborador_nome,
                        rev.name as revisor_nome,
                        apr.name as aprovador_nome,
                        resp.name as responsavel_implementacao_nome
                      FROM lgpd_ripd r
                      JOIN lgpd_aipd a ON r.aipd_id = a.id
                      JOIN adms_departments d ON a.departamento_id = d.id
                      JOIN adms_users e ON r.elaborador_id = e.id
                      LEFT JOIN adms_users rev ON r.revisor_id = rev.id
                      LEFT JOIN adms_users apr ON r.aprovador_id = apr.id
                      LEFT JOIN adms_users resp ON r.responsavel_implementacao = resp.id
                      WHERE r.id = :id";
            
            $stmt = $this->getConnection()->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (Exception $e) {
            error_log("Erro ao buscar RIPD por ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtém RIPD por AIPD
     *
     * @param int $aipdId
     * @return array|null
     */
    public function getRipdByAipdId(int $aipdId): ?array
    {
        try {
            $query = "SELECT * FROM lgpd_ripd WHERE aipd_id = :aipd_id ORDER BY created_at DESC LIMIT 1";
            $stmt = $this->getConnection()->prepare($query);
            $stmt->bindParam(':aipd_id', $aipdId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (Exception $e) {
            error_log("Erro ao buscar RIPD por AIPD: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Cria um novo relatório RIPD
     *
     * @param array $data
     * @return bool
     */
    public function create(array $data): bool
    {
        error_log("Repository - create iniciado com dados: " . json_encode($data));
        
        try {
            $this->getConnection()->beginTransaction();
            
            // Gerar código único se não fornecido
            if (empty($data['codigo'])) {
                $data['codigo'] = $this->generateUniqueCode();
                error_log("Repository - Código único gerado: " . $data['codigo']);
            }
            
            $query = "INSERT INTO lgpd_ripd 
                      (codigo, aipd_id, titulo, versao, data_elaboracao, elaborador_id, 
                       revisor_id, aprovador_id, status, data_aprovacao, observacoes_revisao,
                       observacoes_aprovacao, conclusao_geral, recomendacoes_finais,
                       proximos_passos, prazo_implementacao, responsavel_implementacao) 
                      VALUES (:codigo, :aipd_id, :titulo, :versao, :data_elaboracao, :elaborador_id,
                              :revisor_id, :aprovador_id, :status, :data_aprovacao, :observacoes_revisao,
                              :observacoes_aprovacao, :conclusao_geral, :recomendacoes_finais,
                              :proximos_passos, :prazo_implementacao, :responsavel_implementacao)";
            
            $stmt = $this->getConnection()->prepare($query);
            
            $stmt->bindParam(':codigo', $data['codigo'], PDO::PARAM_STR);
            $stmt->bindParam(':aipd_id', $data['aipd_id'], PDO::PARAM_INT);
            $stmt->bindParam(':titulo', $data['titulo'], PDO::PARAM_STR);
            $stmt->bindParam(':versao', $data['versao'], PDO::PARAM_STR);
            $stmt->bindParam(':data_elaboracao', $data['data_elaboracao'], PDO::PARAM_STR);
            $stmt->bindParam(':elaborador_id', $data['elaborador_id'], PDO::PARAM_INT);
            $stmt->bindParam(':revisor_id', $data['revisor_id'], PDO::PARAM_INT);
            $stmt->bindParam(':aprovador_id', $data['aprovador_id'], PDO::PARAM_INT);
            $stmt->bindParam(':status', $data['status'], PDO::PARAM_STR);
            $stmt->bindParam(':data_aprovacao', $data['data_aprovacao'], PDO::PARAM_STR);
            $stmt->bindParam(':observacoes_revisao', $data['observacoes_revisao'], PDO::PARAM_STR);
            $stmt->bindParam(':observacoes_aprovacao', $data['observacoes_aprovacao'], PDO::PARAM_STR);
            $stmt->bindParam(':conclusao_geral', $data['conclusao_geral'], PDO::PARAM_STR);
            $stmt->bindParam(':recomendacoes_finais', $data['recomendacoes_finais'], PDO::PARAM_STR);
            $stmt->bindParam(':proximos_passos', $data['proximos_passos'], PDO::PARAM_STR);
            $stmt->bindParam(':prazo_implementacao', $data['prazo_implementacao'], PDO::PARAM_STR);
            $stmt->bindParam(':responsavel_implementacao', $data['responsavel_implementacao'], PDO::PARAM_INT);
            
            error_log("Repository - Executando query INSERT");
            $result = $stmt->execute();
            
            if ($result) {
                $this->getConnection()->commit();
                error_log("Repository - RIPD criado com sucesso, commit realizado");
                
                // Gerar log
                GenerateLog::generateLog('INFO', 'RIPD criado com sucesso: ' . $data['codigo'], []);
                
                return true;
            }
            
            error_log("Repository - Falha na execução da query");
            return false;
        } catch (Exception $e) {
            $this->getConnection()->rollBack();
            error_log("Repository - Erro ao criar RIPD: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Atualiza um relatório RIPD existente
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        try {
            $this->getConnection()->beginTransaction();
            
            $query = "UPDATE lgpd_ripd SET 
                        titulo = :titulo,
                        versao = :versao,
                        data_elaboracao = :data_elaboracao,
                        revisor_id = :revisor_id,
                        aprovador_id = :aprovador_id,
                        status = :status,
                        data_aprovacao = :data_aprovacao,
                        observacoes_revisao = :observacoes_revisao,
                        observacoes_aprovacao = :observacoes_aprovacao,
                        conclusao_geral = :conclusao_geral,
                        recomendacoes_finais = :recomendacoes_finais,
                        proximos_passos = :proximos_passos,
                        prazo_implementacao = :prazo_implementacao,
                        responsavel_implementacao = :responsavel_implementacao,
                        updated_at = CURRENT_TIMESTAMP
                      WHERE id = :id";
            
            $stmt = $this->getConnection()->prepare($query);
            
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':titulo', $data['titulo'], PDO::PARAM_STR);
            $stmt->bindParam(':versao', $data['versao'], PDO::PARAM_STR);
            $stmt->bindParam(':data_elaboracao', $data['data_elaboracao'], PDO::PARAM_STR);
            $stmt->bindParam(':revisor_id', $data['revisor_id'], PDO::PARAM_INT);
            $stmt->bindParam(':aprovador_id', $data['aprovador_id'], PDO::PARAM_INT);
            $stmt->bindParam(':status', $data['status'], PDO::PARAM_STR);
            $stmt->bindParam(':data_aprovacao', $data['data_aprovacao'], PDO::PARAM_STR);
            $stmt->bindParam(':observacoes_revisao', $data['observacoes_revisao'], PDO::PARAM_STR);
            $stmt->bindParam(':observacoes_aprovacao', $data['observacoes_aprovacao'], PDO::PARAM_STR);
            $stmt->bindParam(':conclusao_geral', $data['conclusao_geral'], PDO::PARAM_STR);
            $stmt->bindParam(':recomendacoes_finais', $data['recomendacoes_finais'], PDO::PARAM_STR);
            $stmt->bindParam(':proximos_passos', $data['proximos_passos'], PDO::PARAM_STR);
            $stmt->bindParam(':prazo_implementacao', $data['prazo_implementacao'], PDO::PARAM_STR);
            $stmt->bindParam(':responsavel_implementacao', $data['responsavel_implementacao'], PDO::PARAM_INT);
            
            $result = $stmt->execute();
            
            if ($result) {
                $this->getConnection()->commit();
                
                // Gerar log
                GenerateLog::generateLog('INFO', 'RIPD atualizado com sucesso: ID ' . $id, []);
                
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            $this->getConnection()->rollBack();
            error_log("Erro ao atualizar RIPD: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Exclui um relatório RIPD
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        try {
            $this->getConnection()->beginTransaction();
            
            $query = "DELETE FROM lgpd_ripd WHERE id = :id";
            $stmt = $this->getConnection()->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $result = $stmt->execute();
            
            $this->getConnection()->commit();
            
            if ($result) {
                // Gerar log
                GenerateLog::generateLog('INFO', 'RIPD excluído com sucesso: ID ' . $id, []);
            }
            
            return $result;
        } catch (Exception $e) {
            $this->getConnection()->rollBack();
            error_log("Erro ao excluir RIPD: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Gera um código único para o relatório RIPD
     *
     * @return string
     */
    private function generateUniqueCode(): string
    {
        $prefix = 'RIPD';
        $counter = 1;
        
        do {
            $code = $prefix . '-' . str_pad($counter, 3, '0', STR_PAD_LEFT);
            $query = "SELECT COUNT(*) FROM lgpd_ripd WHERE codigo = :codigo";
            $stmt = $this->getConnection()->prepare($query);
            $stmt->bindParam(':codigo', $code, PDO::PARAM_STR);
            $stmt->execute();
            
            $exists = $stmt->fetchColumn() > 0;
            $counter++;
        } while ($exists);
        
        return $code;
    }

    /**
     * Gera RIPD automaticamente baseado em uma AIPD
     *
     * @param int $aipdId
     * @param int $elaboradorId
     * @return array|false
     */
    public function generateRipdFromAipd(int $aipdId, int $elaboradorId)
    {
        error_log("Repository - generateRipdFromAipd iniciado para AIPD ID: $aipdId");
        
        try {
            // Buscar dados da AIPD
            $query = "SELECT 
                        a.*,
                        d.name as departamento_nome,
                        r.atividade as ropa_atividade,
                        u.name as responsavel_nome
                      FROM lgpd_aipd a
                      JOIN adms_departments d ON a.departamento_id = d.id
                      LEFT JOIN lgpd_ropa r ON a.ropa_id = r.id
                      LEFT JOIN adms_users u ON a.responsavel_id = u.id
                      WHERE a.id = :aipd_id";
            
            $stmt = $this->getConnection()->prepare($query);
            $stmt->bindParam(':aipd_id', $aipdId, PDO::PARAM_INT);
            $stmt->execute();
            
            $aipd = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$aipd) {
                error_log("Repository - AIPD não encontrada para ID: $aipdId");
                return false;
            }
            
            error_log("Repository - AIPD encontrada: " . json_encode($aipd));
            
            // Gerar dados do RIPD baseado na AIPD
            $ripdData = [
                'aipd_id' => $aipdId,
                'titulo' => 'RIPD - ' . $aipd['titulo'],
                'versao' => '1.0',
                'data_elaboracao' => date('Y-m-d'),
                'elaborador_id' => $elaboradorId,
                'status' => 'Rascunho',
                'conclusao_geral' => $this->generateConclusaoGeral($aipd),
                'recomendacoes_finais' => $this->generateRecomendacoesFinais($aipd),
                'proximos_passos' => $this->generateProximosPassos($aipd),
                'prazo_implementacao' => date('Y-m-d', strtotime('+30 days')),
                'responsavel_implementacao' => $aipd['responsavel_id']
            ];
            
            error_log("Repository - Dados do RIPD gerados: " . json_encode($ripdData));
            
            return $ripdData;
        } catch (Exception $e) {
            error_log("Repository - Erro ao gerar RIPD da AIPD: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Gera conclusão geral baseada na AIPD
     *
     * @param array $aipd
     * @return string
     */
    private function generateConclusaoGeral(array $aipd): string
    {
        $conclusao = "Com base na Avaliação de Impacto à Proteção de Dados (AIPD) realizada, ";
        $conclusao .= "conclui-se que o tratamento de dados pessoais identificado como '{$aipd['titulo']}' ";
        $conclusao .= "apresenta {$aipd['nivel_risco']} risco à proteção de dados.\n\n";
        
        $conclusao .= "Principais pontos identificados:\n";
        $conclusao .= "- Título: {$aipd['titulo']}\n";
        $conclusao .= "- Departamento: {$aipd['departamento_nome']}\n";
        $conclusao .= "- Status: {$aipd['status']}\n";
        $conclusao .= "- Nível de Risco: {$aipd['nivel_risco']}\n";
        if (!empty($aipd['descricao'])) {
            $conclusao .= "- Descrição: {$aipd['descricao']}\n";
        }
        if (!empty($aipd['observacoes'])) {
            $conclusao .= "- Observações: {$aipd['observacoes']}\n";
        }
        $conclusao .= "\n";
        
        $conclusao .= "O tratamento está em conformidade com os princípios da LGPD, ";
        $conclusao .= "sendo necessária a implementação das medidas de mitigação identificadas.";
        
        return $conclusao;
    }

    /**
     * Gera recomendações finais baseadas na AIPD
     *
     * @param array $aipd
     * @return string
     */
    private function generateRecomendacoesFinais(array $aipd): string
    {
        $recomendacoes = "1. Implementar todas as medidas de mitigação identificadas na AIPD\n";
        $recomendacoes .= "2. Estabelecer monitoramento contínuo dos riscos identificados\n";
        $recomendacoes .= "3. Realizar revisão periódica da AIPD conforme evolução do tratamento\n";
        $recomendacoes .= "4. Treinar equipe sobre as medidas de proteção implementadas\n";
        $recomendacoes .= "5. Documentar todas as ações de implementação das medidas\n";
        $recomendacoes .= "6. Estabelecer processo de revisão e atualização do relatório\n";
        $recomendacoes .= "7. Monitorar indicadores de conformidade e eficácia das medidas";
        
        return $recomendacoes;
    }

    /**
     * Gera próximos passos baseados na AIPD
     *
     * @param array $aipd
     * @return string
     */
    private function generateProximosPassos(array $aipd): string
    {
        $passos = "1. Aprovação do RIPD pela alta administração\n";
        $passos .= "2. Implementação das medidas de mitigação identificadas\n";
        $passos .= "3. Treinamento da equipe envolvida\n";
        $passos .= "4. Monitoramento da eficácia das medidas\n";
        $passos .= "5. Revisão periódica conforme cronograma estabelecido\n";
        $passos .= "6. Atualização da documentação conforme necessário";
        
        return $passos;
    }

    /**
     * Obtém estatísticas dos relatórios RIPD
     *
     * @return array
     */
    public function getEstatisticas(): array
    {
        try {
            $estatisticas = [];
            
            // Total por status
            $query = "SELECT status, COUNT(*) as total FROM lgpd_ripd GROUP BY status";
            $stmt = $this->getConnection()->prepare($query);
            $stmt->execute();
            $estatisticas['por_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Total geral
            $query = "SELECT COUNT(*) as total FROM lgpd_ripd";
            $stmt = $this->getConnection()->prepare($query);
            $stmt->execute();
            $estatisticas['total'] = $stmt->fetchColumn();
            
            // RIPDs aprovados
            $query = "SELECT COUNT(*) as total FROM lgpd_ripd WHERE status = 'Aprovado'";
            $stmt = $this->getConnection()->prepare($query);
            $stmt->execute();
            $estatisticas['aprovados'] = $stmt->fetchColumn();
            
            // RIPDs em revisão
            $query = "SELECT COUNT(*) as total FROM lgpd_ripd WHERE status = 'Em Revisão'";
            $stmt = $this->getConnection()->prepare($query);
            $stmt->execute();
            $estatisticas['em_revisao'] = $stmt->fetchColumn();
            
            // RIPDs por mês
            $query = "SELECT 
                        DATE_FORMAT(created_at, '%Y-%m') as mes,
                        COUNT(*) as total
                      FROM lgpd_ripd 
                      WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                      GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                      ORDER BY mes DESC";
            $stmt = $this->getConnection()->prepare($query);
            $stmt->execute();
            $estatisticas['por_mes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $estatisticas;
        } catch (Exception $e) {
            error_log("Erro ao buscar estatísticas dos RIPDs: " . $e->getMessage());
            return [];
        }
    }
}
