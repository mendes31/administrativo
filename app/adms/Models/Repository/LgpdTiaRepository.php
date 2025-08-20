<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use PDO;
use Exception;

/**
 * Repositório para operações com Testes de Impacto às Atividades (TIA).
 *
 * @package App\adms\Models\Repository
 */
class LgpdTiaRepository extends DbConnection
{
    /**
     * Obtém todos os testes TIA
     *
     * @return array
     */
    public function getAllTia(): array
    {
        try {
            $query = "SELECT 
                        t.id,
                        t.codigo,
                        t.titulo,
                        t.descricao,
                        t.ropa_id,
                        t.departamento_id,
                        t.responsavel_id,
                        t.data_teste,
                        t.resultado,
                        t.justificativa,
                        t.recomendacoes,
                        t.status,
                        t.created_at,
                        t.updated_at,
                        r.atividade as ropa_atividade,
                        d.name as departamento_nome,
                        u.name as responsavel_nome
                      FROM lgpd_tia t
                      LEFT JOIN lgpd_ropa r ON t.ropa_id = r.id
                      LEFT JOIN adms_departments d ON t.departamento_id = d.id
                      LEFT JOIN adms_users u ON t.responsavel_id = u.id
                      ORDER BY t.created_at DESC";
            
            $stmt = $this->getConnection()->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erro ao buscar testes TIA: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtém a quantidade total de testes TIA
     *
     * @return int
     */
    public function getAmountTia(): int
    {
        try {
            $query = "SELECT COUNT(*) as total FROM lgpd_tia";
            $stmt = $this->getConnection()->prepare($query);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) ($result['total'] ?? 0);
        } catch (Exception $e) {
            error_log("Erro ao contar testes TIA: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtém um teste TIA por ID
     *
     * @param int $id
     * @return array|null
     */
    public function getTiaById(int $id): ?array
    {
        try {
            $query = "SELECT 
                        t.*,
                        r.atividade as ropa_atividade,
                        d.name as departamento_nome,
                        u.name as responsavel_nome
                      FROM lgpd_tia t
                      LEFT JOIN lgpd_ropa r ON t.ropa_id = r.id
                      LEFT JOIN adms_departments d ON t.departamento_id = d.id
                      LEFT JOIN adms_users u ON t.responsavel_id = u.id
                      WHERE t.id = :id";
            
            $stmt = $this->getConnection()->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (Exception $e) {
            error_log("Erro ao buscar teste TIA por ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtém os grupos de dados relacionados a um teste TIA
     *
     * @param int $tiaId
     * @return array
     */
    public function getDataGroupsByTiaId(int $tiaId): array
    {
        try {
            $query = "SELECT 
                        tdg.*,
                        dg.name as data_group_name,
                        dg.category as data_group_category,
                        dg.is_sensitive as data_group_sensitive
                      FROM lgpd_tia_data_groups tdg
                      JOIN lgpd_data_groups dg ON tdg.data_group_id = dg.id
                      WHERE tdg.tia_id = :tia_id";
            
            $stmt = $this->getConnection()->prepare($query);
            $stmt->bindParam(':tia_id', $tiaId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erro ao buscar grupos de dados do TIA: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Cria um novo teste TIA
     *
     * @param array $data
     * @return bool
     */
    public function create(array $data): bool
    {
        try {
            $this->getConnection()->beginTransaction();
            
            // Gerar código único se não fornecido
            if (empty($data['codigo'])) {
                $data['codigo'] = $this->generateUniqueCode();
            }
            
            $query = "INSERT INTO lgpd_tia 
                      (codigo, titulo, descricao, ropa_id, departamento_id, responsavel_id, 
                       data_teste, resultado, justificativa, recomendacoes, status) 
                      VALUES (:codigo, :titulo, :descricao, :ropa_id, :departamento_id, :responsavel_id, 
                              :data_teste, :resultado, :justificativa, :recomendacoes, :status)";
            
            $stmt = $this->getConnection()->prepare($query);
            
            $stmt->bindParam(':codigo', $data['codigo'], PDO::PARAM_STR);
            $stmt->bindParam(':titulo', $data['titulo'], PDO::PARAM_STR);
            $stmt->bindParam(':descricao', $data['descricao'], PDO::PARAM_STR);
            $stmt->bindParam(':ropa_id', $data['ropa_id'], PDO::PARAM_INT);
            $stmt->bindParam(':departamento_id', $data['departamento_id'], PDO::PARAM_INT);
            $stmt->bindParam(':responsavel_id', $data['responsavel_id'], PDO::PARAM_INT);
            $stmt->bindParam(':data_teste', $data['data_teste'], PDO::PARAM_STR);
            $stmt->bindParam(':resultado', $data['resultado'], PDO::PARAM_STR);
            $stmt->bindParam(':justificativa', $data['justificativa'], PDO::PARAM_STR);
            $stmt->bindParam(':recomendacoes', $data['recomendacoes'], PDO::PARAM_STR);
            $stmt->bindParam(':status', $data['status'], PDO::PARAM_STR);
            
            $result = $stmt->execute();
            
            if ($result && !empty($data['data_groups'])) {
                $tiaId = $this->getConnection()->lastInsertId();
                $this->associateDataGroups($tiaId, $data['data_groups']);
            }
            
            $this->getConnection()->commit();
            
            // Gerar log
            GenerateLog::generateLog('INFO', 'TIA criado com sucesso: ' . $data['codigo'], []);
            
            return true;
        } catch (Exception $e) {
            $this->getConnection()->rollBack();
            error_log("Erro ao criar teste TIA: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Atualiza um teste TIA existente
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        try {
            $this->getConnection()->beginTransaction();
            
            $query = "UPDATE lgpd_tia SET 
                        titulo = :titulo,
                        descricao = :descricao,
                        ropa_id = :ropa_id,
                        departamento_id = :departamento_id,
                        responsavel_id = :responsavel_id,
                        data_teste = :data_teste,
                        resultado = :resultado,
                        justificativa = :justificativa,
                        recomendacoes = :recomendacoes,
                        status = :status,
                        updated_at = CURRENT_TIMESTAMP
                      WHERE id = :id";
            
            $stmt = $this->getConnection()->prepare($query);
            
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':titulo', $data['titulo'], PDO::PARAM_STR);
            $stmt->bindParam(':descricao', $data['descricao'], PDO::PARAM_STR);
            $stmt->bindParam(':ropa_id', $data['ropa_id'], PDO::PARAM_INT);
            $stmt->bindParam(':departamento_id', $data['departamento_id'], PDO::PARAM_INT);
            $stmt->bindParam(':responsavel_id', $data['responsavel_id'], PDO::PARAM_INT);
            $stmt->bindParam(':data_teste', $data['data_teste'], PDO::PARAM_STR);
            $stmt->bindParam(':resultado', $data['resultado'], PDO::PARAM_STR);
            $stmt->bindParam(':justificativa', $data['justificativa'], PDO::PARAM_STR);
            $stmt->bindParam(':recomendacoes', $data['recomendacoes'], PDO::PARAM_STR);
            $stmt->bindParam(':status', $data['status'], PDO::PARAM_STR);
            
            $result = $stmt->execute();
            
            if ($result && isset($data['data_groups'])) {
                $this->updateDataGroups($id, $data['data_groups']);
            }
            
            $this->getConnection()->commit();
            
            // Gerar log
            GenerateLog::generateLog('INFO', 'TIA atualizado com sucesso: ID ' . $id, []);
            
            return true;
        } catch (Exception $e) {
            $this->getConnection()->rollBack();
            error_log("Erro ao atualizar teste TIA: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Exclui um teste TIA
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        try {
            $this->getConnection()->beginTransaction();
            
            // Excluir relacionamentos com grupos de dados
            $query = "DELETE FROM lgpd_tia_data_groups WHERE tia_id = :id";
            $stmt = $this->getConnection()->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            // Excluir o teste TIA
            $query = "DELETE FROM lgpd_tia WHERE id = :id";
            $stmt = $this->getConnection()->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $result = $stmt->execute();
            
            $this->getConnection()->commit();
            
            if ($result) {
                // Gerar log
                GenerateLog::generateLog('INFO', 'TIA excluído com sucesso: ID ' . $id, []);
            }
            
            return $result;
        } catch (Exception $e) {
            $this->getConnection()->rollBack();
            error_log("Erro ao excluir teste TIA: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Associa grupos de dados a um teste TIA
     *
     * @param int $tiaId
     * @param array $dataGroups
     * @return bool
     */
    private function associateDataGroups(int $tiaId, array $dataGroups): bool
    {
        try {
            $query = "INSERT INTO lgpd_tia_data_groups 
                      (tia_id, data_group_id, volume_dados, sensibilidade, observacoes) 
                      VALUES (:tia_id, :data_group_id, :volume_dados, :sensibilidade, :observacoes)";
            
            $stmt = $this->getConnection()->prepare($query);
            
            foreach ($dataGroups as $group) {
                $stmt->bindParam(':tia_id', $tiaId, PDO::PARAM_INT);
                $stmt->bindParam(':data_group_id', $group['data_group_id'], PDO::PARAM_INT);
                $stmt->bindParam(':volume_dados', $group['volume_dados'], PDO::PARAM_STR);
                $stmt->bindParam(':sensibilidade', $group['sensibilidade'], PDO::PARAM_STR);
                $stmt->bindParam(':observacoes', $group['observacoes'], PDO::PARAM_STR);
                
                $stmt->execute();
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Erro ao associar grupos de dados ao TIA: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Atualiza os grupos de dados de um teste TIA
     *
     * @param int $tiaId
     * @param array $dataGroups
     * @return bool
     */
    private function updateDataGroups(int $tiaId, array $dataGroups): bool
    {
        try {
            // Remover associações existentes
            $query = "DELETE FROM lgpd_tia_data_groups WHERE tia_id = :tia_id";
            $stmt = $this->getConnection()->prepare($query);
            $stmt->bindParam(':tia_id', $tiaId, PDO::PARAM_INT);
            $stmt->execute();
            
            // Criar novas associações
            return $this->associateDataGroups($tiaId, $dataGroups);
        } catch (Exception $e) {
            error_log("Erro ao atualizar grupos de dados do TIA: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Gera um código único para o teste TIA
     *
     * @return string
     */
    private function generateUniqueCode(): string
    {
        $prefix = 'TIA';
        $counter = 1;
        
        do {
            $code = $prefix . '-' . str_pad($counter, 3, '0', STR_PAD_LEFT);
            $query = "SELECT COUNT(*) FROM lgpd_tia WHERE codigo = :codigo";
            $stmt = $this->getConnection()->prepare($query);
            $stmt->bindParam(':codigo', $code, PDO::PARAM_STR);
            $stmt->execute();
            
            $exists = $stmt->fetchColumn() > 0;
            $counter++;
        } while ($exists);
        
        return $code;
    }

    /**
     * Obtém estatísticas dos testes TIA
     *
     * @return array
     */
    public function getEstatisticas(): array
    {
        try {
            $estatisticas = [];
            
            // Total geral
            $query = "SELECT COUNT(*) as total FROM lgpd_tia";
            $stmt = $this->getConnection()->prepare($query);
            $stmt->execute();
            $estatisticas['total'] = $stmt->fetchColumn();
            
            // Total por status
            $query = "SELECT status, COUNT(*) as total FROM lgpd_tia GROUP BY status";
            $stmt = $this->getConnection()->prepare($query);
            $stmt->execute();
            $estatisticas['por_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Totais específicos por status
            $query = "SELECT 
                        COUNT(CASE WHEN status = 'Concluído' THEN 1 END) as concluidos,
                        COUNT(CASE WHEN status = 'Em Andamento' THEN 1 END) as em_andamento,
                        COUNT(CASE WHEN status = 'Aprovado' THEN 1 END) as aprovados
                      FROM lgpd_tia";
            $stmt = $this->getConnection()->prepare($query);
            $stmt->execute();
            $statusCounts = $stmt->fetch(PDO::FETCH_ASSOC);
            $estatisticas['concluidos'] = $statusCounts['concluidos'] ?? 0;
            $estatisticas['em_andamento'] = $statusCounts['em_andamento'] ?? 0;
            $estatisticas['aprovados'] = $statusCounts['aprovados'] ?? 0;
            
            // Total por resultado
            $query = "SELECT resultado, COUNT(*) as total FROM lgpd_tia GROUP BY resultado";
            $stmt = $this->getConnection()->prepare($query);
            $stmt->execute();
            $estatisticas['por_resultado'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Total que necessitam AIPD
            $query = "SELECT COUNT(*) as total FROM lgpd_tia WHERE resultado = 'Necessita AIPD'";
            $stmt = $this->getConnection()->prepare($query);
            $stmt->execute();
            $estatisticas['necessitam_aipd'] = $stmt->fetchColumn();
            
            // Total por departamento
            $query = "SELECT d.name as departamento, COUNT(*) as total 
                      FROM lgpd_tia t
                      JOIN adms_departments d ON t.departamento_id = d.id
                      GROUP BY t.departamento_id, d.name";
            $stmt = $this->getConnection()->prepare($query);
            $stmt->execute();
            $estatisticas['por_departamento'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Testes recentes (últimos 30 dias)
            $query = "SELECT COUNT(*) as total FROM lgpd_tia 
                      WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)";
            $stmt = $this->getConnection()->prepare($query);
            $stmt->execute();
            $estatisticas['recentes_30_dias'] = $stmt->fetchColumn();
            
            return $estatisticas;
        } catch (Exception $e) {
            error_log("Erro ao buscar estatísticas dos testes TIA: " . $e->getMessage());
            return [];
        }
    }
}
