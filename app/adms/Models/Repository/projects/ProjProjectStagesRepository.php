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
     *  - stage_id (opcional), name, sequence
     *  - start_date, expected_end_date, end_date, activity, description
     *  - responsible_user_id, completed
     *  - depends_on_index (opcional): índice da linha da qual esta depende (0-based); resolvido após insert
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

            // Inserir novas etapas (depends_on_stage_id = null); dependências são resolvidas depois
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
                                NULL,
                                :created_at
                              )";

                $stmtInsert = $conn->prepare($sqlInsert);
                $insertedCount = 0;

                foreach ($lines as $line) {
                    $name = trim((string)($line['name'] ?? ''));
                    if ($name === '') {
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
                    $stmtInsert->bindValue(':created_at', date('Y-m-d H:i:s'));

                    $stmtInsert->execute();
                    $insertedCount++;
                }

                // Resolver dependências: obter novos IDs na ordem de inserção e atualizar depends_on_stage_id
                $newIdsOrdered = $this->getProjectStageIdsOrdered($conn, $projectId);
                $stmtUpdate = $conn->prepare('UPDATE proj_project_stages SET depends_on_stage_id = :dep_id WHERE id = :id');

                foreach ($lines as $idx => $line) {
                    $depIdx = isset($line['depends_on_index']) && $line['depends_on_index'] !== '' && $line['depends_on_index'] !== null
                        ? (int)$line['depends_on_index'] : null;
                    if ($depIdx === null || $depIdx < 0 || $depIdx >= count($newIdsOrdered) || $depIdx === $idx) {
                        continue;
                    }
                    $myId = $newIdsOrdered[$idx] ?? null;
                    $depId = $newIdsOrdered[$depIdx] ?? null;
                    if ($myId && $depId) {
                        $stmtUpdate->bindValue(':dep_id', $depId, PDO::PARAM_INT);
                        $stmtUpdate->bindValue(':id', $myId, PDO::PARAM_INT);
                        $stmtUpdate->execute();
                    }
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

    /**
     * Retorna os IDs das etapas do projeto na ordem (sequence, id).
     *
     * @param \PDO $conn
     * @param int  $projectId
     * @return array<int, int> índice => id
     */
    private function getProjectStageIdsOrdered(\PDO $conn, int $projectId): array
    {
        $stmt = $conn->prepare(
            'SELECT id FROM proj_project_stages WHERE project_id = :project_id ORDER BY sequence ASC, id ASC'
        );
        $stmt->bindValue(':project_id', $projectId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $ids = [];
        foreach ($rows as $row) {
            $ids[] = (int)$row['id'];
        }
        return $ids;
    }
}

