<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;
use App\adms\Helpers\GenerateLog;

class TrainingPositionsRepository extends DbConnection
{
    /**
     * Retorna um array com os dados dos cargos vinculados a um treinamento
     */
    public function getPositionsByTraining(int $trainingId): array
    {
        $sql = 'SELECT adms_position_id, obrigatorio, reciclagem_periodo 
                FROM adms_training_positions 
                WHERE adms_training_id = :training_id AND obrigatorio = 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':training_id', $trainingId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Retorna apenas os IDs dos cargos vinculados (para compatibilidade)
     */
    public function getPositionIdsByTraining(int $trainingId): array
    {
        $sql = 'SELECT adms_position_id FROM adms_training_positions WHERE adms_training_id = :training_id AND obrigatorio = 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':training_id', $trainingId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0) ?: [];
    }

    /**
     * Salva os vínculos entre um treinamento e os cargos (remove antigos e insere novos)
     */
    public function saveTrainingPositions(int $trainingId, array $obrigatorio, array $reciclagem = []): bool
    {
        try {
            $positionsRepo = new PositionsRepository();
            $allPositions = $positionsRepo->getAllPositions(1, 1000);

            // Buscar todos os vínculos existentes para o treinamento
            $sql = 'SELECT id, adms_position_id FROM adms_training_positions WHERE adms_training_id = :training_id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':training_id', $trainingId, PDO::PARAM_INT);
            $stmt->execute();
            $existingLinks = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $existingLinks[$row['adms_position_id']] = $row['id'];
            }

            foreach ($allPositions as $position) {
                $cargoId = $position['id'];
                $isObrigatorio = isset($obrigatorio[$cargoId]) ? 1 : 0;
                $reciclagemPeriodo = (isset($reciclagem[$cargoId]) && is_numeric($reciclagem[$cargoId]) && $isObrigatorio) ? (int)$reciclagem[$cargoId] : null;
                
                // Captura os dados antigos antes da alteração
                $dadosAntes = null;
                if (isset($existingLinks[$cargoId])) {
                    $sqlAntes = 'SELECT * FROM adms_training_positions WHERE id = :id';
                    $stmtAntes = $this->getConnection()->prepare($sqlAntes);
                    $stmtAntes->bindValue(':id', $existingLinks[$cargoId], PDO::PARAM_INT);
                    $stmtAntes->execute();
                    $dadosAntes = $stmtAntes->fetch(PDO::FETCH_ASSOC);
                }
                
                if (isset($existingLinks[$cargoId])) {
                    // UPDATE
                    $sqlUpdate = 'UPDATE adms_training_positions 
                                 SET obrigatorio = :obrigatorio, 
                                     reciclagem_periodo = :reciclagem_periodo, 
                                     updated_at = NOW() 
                                 WHERE id = :id';
                    $stmtUpdate = $this->getConnection()->prepare($sqlUpdate);
                    $stmtUpdate->bindValue(':obrigatorio', $isObrigatorio, PDO::PARAM_INT);
                    $stmtUpdate->bindValue(':reciclagem_periodo', $reciclagemPeriodo, PDO::PARAM_INT);
                    $stmtUpdate->bindValue(':id', $existingLinks[$cargoId], PDO::PARAM_INT);
                    $result = $stmtUpdate->execute();
                    
                    // Log de alteração para UPDATE
                    if ($result && $dadosAntes) {
                        $dadosDepois = [
                            'id' => $existingLinks[$cargoId],
                            'adms_training_id' => $trainingId,
                            'adms_position_id' => $cargoId,
                            'obrigatorio' => $isObrigatorio,
                            'reciclagem_periodo' => $reciclagemPeriodo,
                        ];
                        \App\adms\Models\Services\LogAlteracaoService::registrarAlteracao(
                            'adms_training_positions',
                            $existingLinks[$cargoId],
                            $_SESSION['user_id'] ?? 0,
                            'update',
                            $dadosAntes,
                            $dadosDepois
                        );
                    }
                } else {
                    // INSERT
                    $sqlInsert = 'INSERT INTO adms_training_positions 
                                 (adms_training_id, adms_position_id, obrigatorio, reciclagem_periodo, created_at, updated_at) 
                                 VALUES (:training_id, :position_id, :obrigatorio, :reciclagem_periodo, NOW(), NOW())';
                    $stmtInsert = $this->getConnection()->prepare($sqlInsert);
                    $stmtInsert->bindValue(':training_id', $trainingId, PDO::PARAM_INT);
                    $stmtInsert->bindValue(':position_id', $cargoId, PDO::PARAM_INT);
                    $stmtInsert->bindValue(':obrigatorio', $isObrigatorio, PDO::PARAM_INT);
                    $stmtInsert->bindValue(':reciclagem_periodo', $reciclagemPeriodo, PDO::PARAM_INT);
                    $stmtInsert->execute();
                    $novoId = $this->getConnection()->lastInsertId();
                    
                    // Log de alteração para INSERT
                    if ($novoId) {
                        $dadosDepois = [
                            'id' => $novoId,
                            'adms_training_id' => $trainingId,
                            'adms_position_id' => $cargoId,
                            'obrigatorio' => $isObrigatorio,
                            'reciclagem_periodo' => $reciclagemPeriodo,
                        ];
                        \App\adms\Models\Services\LogAlteracaoService::registrarAlteracao(
                            'adms_training_positions',
                            $novoId,
                            $_SESSION['user_id'] ?? 0,
                            'insert',
                            [],
                            $dadosDepois
                        );
                    }
                }
            }

            return true;
        } catch (\Exception $e) {
            GenerateLog::generateLog("error", "Erro ao salvar vínculos de treinamento", [
                'training_id' => $trainingId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Retorna os IDs dos treinamentos obrigatórios para um cargo
     */
    public function getTrainingsByPosition(int $positionId): array
    {
        $sql = 'SELECT adms_training_id FROM adms_training_positions WHERE adms_position_id = :position_id AND obrigatorio = 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':position_id', $positionId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0) ?: [];
    }

    /**
     * Retorna estatísticas de vínculos por treinamento
     */
    public function getTrainingPositionsStats(int $trainingId): array
    {
        $sql = 'SELECT 
                    COUNT(*) as total_positions,
                    SUM(CASE WHEN obrigatorio = 1 THEN 1 ELSE 0 END) as mandatory_positions,
                    SUM(CASE WHEN reciclagem_periodo IS NOT NULL THEN 1 ELSE 0 END) as with_reciclagem
                FROM adms_training_positions 
                WHERE adms_training_id = :training_id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':training_id', $trainingId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Retorna todos os vínculos de treinamentos com cargos
     */
    public function getAllTrainingPositions(): array
    {
        $sql = 'SELECT tp.*, t.nome as training_name, p.name as position_name
                FROM adms_training_positions tp
                INNER JOIN adms_trainings t ON t.id = tp.adms_training_id
                INNER JOIN adms_positions p ON p.id = tp.adms_position_id
                WHERE tp.obrigatorio = 1
                ORDER BY t.nome, p.name';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
} 