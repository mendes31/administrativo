<?php

namespace App\adms\Models\Repository\projects;

use App\adms\Models\Services\DbConnection;
use App\adms\Helpers\GenerateLog;
use PDO;
use Exception;

class ProjProjectStagesRepository extends DbConnection
{
    /**
     * Retorna todas as etapas de um projeto, com informações auxiliares.
     *
     * @param int $projectId
     * @return array
     */
    public function getByProject(int $projectId): array
    {
        if ($projectId <= 0) {
            return [];
        }

        $sql = "SELECT 
                    s.id,
                    s.project_id,
                    s.stage_id,
                    s.name,
                    s.activity,
                    s.description,
                    s.sequence,
                    s.is_cost_stage,
                    s.status,
                    s.percent_complete,
                    s.start_date,
                    s.expected_end_date,
                    s.end_date,
                    s.completed,
                    s.responsible_user_id,
                    s.depends_on_stage_id,
                    u.name AS responsible_name
                FROM proj_project_stages s
                LEFT JOIN adms_users u ON u.id = s.responsible_user_id
                WHERE s.project_id = :project_id
                ORDER BY s.sequence ASC, s.start_date ASC, s.id ASC";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':project_id', $projectId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Substitui completamente as etapas de um projeto pelas linhas informadas.
     *
     * Cada linha do array deve conter, ao menos:
     *  - stage_id (opcional, pode ser null se for etapa livre)
     *  - name
     *  - sequence
     *  - start_date, expected_end_date, end_date
     *  - activity, description
     *  - responsible_user_id, depends_on_stage_id, completed
     *
     * @param int   $projectId
     * @param array $lines
     * @return bool
     */
    public function replaceForProject(int $projectId, array $lines): bool
    {
        if ($projectId <= 0) {
            return false;
        }

        $conn = $this->getConnection();

        try {
            $conn->beginTransaction();

            // Apagar etapas atuais
            $stmtDelete = $conn->prepare('DELETE FROM proj_project_stages WHERE project_id = :project_id');
            $stmtDelete->bindValue(':project_id', $projectId, PDO::PARAM_INT);
            $stmtDelete->execute();

            // Inserir novas etapas, se houver
            if (!empty($lines)) {
                $sqlInsert = "INSERT INTO proj_project_stages (
                                project_id,
                                stage_id,
                                name,
                                activity,
                                description,
                                sequence,
                                is_cost_stage,
                                status,
                                percent_complete,
                                start_date,
                                expected_end_date,
                                end_date,
                                completed,
                                responsible_user_id,
                                depends_on_stage_id,
                                created_at
                              ) VALUES (
                                :project_id,
                                :stage_id,
                                :name,
                                :activity,
                                :description,
                                :sequence,
                                :is_cost_stage,
                                :status,
                                :percent_complete,
                                :start_date,
                                :expected_end_date,
                                :end_date,
                                :completed,
                                :responsible_user_id,
                                :depends_on_stage_id,
                                :created_at
                              )";

                $stmtInsert = $conn->prepare($sqlInsert);

                foreach ($lines as $line) {
                    // Sanitizar mínimos
                    $name = trim((string)($line['name'] ?? ''));
                    if ($name === '') {
                        // Ignora linhas sem nome
                        continue;
                    }

                    $stmtInsert->bindValue(':project_id', $projectId, PDO::PARAM_INT);
                    $stmtInsert->bindValue(':stage_id', !empty($line['stage_id']) ? (int)$line['stage_id'] : null, !empty($line['stage_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
                    $stmtInsert->bindValue(':name', $name);
                    $stmtInsert->bindValue(':activity', $line['activity'] ?? null, PDO::PARAM_STR);
                    $stmtInsert->bindValue(':description', $line['description'] ?? null, PDO::PARAM_STR);
                    $stmtInsert->bindValue(':sequence', (int)($line['sequence'] ?? 1), PDO::PARAM_INT);
                    $stmtInsert->bindValue(':is_cost_stage', !empty($line['is_cost_stage']) ? 1 : 0, PDO::PARAM_INT);
                    $stmtInsert->bindValue(':status', $line['status'] ?? 'NAO_INICIADO');
                    $stmtInsert->bindValue(':percent_complete', (float)($line['percent_complete'] ?? 0));
                    $stmtInsert->bindValue(':start_date', $line['start_date'] ?? null);
                    $stmtInsert->bindValue(':expected_end_date', $line['expected_end_date'] ?? null);
                    $stmtInsert->bindValue(':end_date', $line['end_date'] ?? null);
                    $stmtInsert->bindValue(':completed', !empty($line['completed']) ? 1 : 0, PDO::PARAM_INT);
                    $stmtInsert->bindValue(':responsible_user_id', !empty($line['responsible_user_id']) ? (int)$line['responsible_user_id'] : null, !empty($line['responsible_user_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
                    $stmtInsert->bindValue(':depends_on_stage_id', !empty($line['depends_on_stage_id']) ? (int)$line['depends_on_stage_id'] : null, !empty($line['depends_on_stage_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
                    $stmtInsert->bindValue(':created_at', date('Y-m-d H:i:s'));

                    $stmtInsert->execute();
                }
            }

            $conn->commit();
            return true;
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            GenerateLog::generateLog('error', 'Falha ao salvar etapas do projeto', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}

