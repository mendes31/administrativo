<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;
use Exception;

class LgpdFontesColetaRepository extends DbConnection
{

    public function listAllActive(): array
    {
        $sql = "SELECT * FROM lgpd_fontes_coleta WHERE ativo = 1 ORDER BY nome";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listAll(): array
    {
        $sql = "SELECT * FROM lgpd_fontes_coleta ORDER BY nome";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM lgpd_fontes_coleta WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(array $data): bool
    {
        try {
            $sql = "INSERT INTO lgpd_fontes_coleta (nome, descricao, ativo) VALUES (:nome, :descricao, :ativo)";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':nome', $data['nome'], PDO::PARAM_STR);
            $stmt->bindValue(':descricao', $data['descricao'], PDO::PARAM_STR);
            $stmt->bindValue(':ativo', $data['ativo'] ?? 1, PDO::PARAM_INT);
            $ok = $stmt->execute();
            if ($ok) {
                $newId = (int) $this->getConnection()->lastInsertId();
                if ($newId > 0) {
                    $newData = $this->findById($newId);
                    if (is_array($newData)) {
                        $usuarioId = $_SESSION['user_id'] ?? 1;
                        LogAlteracaoService::registrarAlteracao(
                            'lgpd_fontes_coleta',
                            $newId,
                            $usuarioId,
                            'INSERT',
                            [],
                            $newData
                        );
                    }
                }
            }

            return $ok;
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Fonte de coleta não cadastrada.", ['nome' => $data['nome'], 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function update(int $id, array $data): bool
    {
        try {
            $oldData = $this->findById($id);
            $sql = "UPDATE lgpd_fontes_coleta SET nome = :nome, descricao = :descricao, ativo = :ativo, updated_at = NOW() WHERE id = :id";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':nome', $data['nome'], PDO::PARAM_STR);
            $stmt->bindValue(':descricao', $data['descricao'], PDO::PARAM_STR);
            $stmt->bindValue(':ativo', $data['ativo'] ?? 1, PDO::PARAM_INT);
            $ok = $stmt->execute();
            if ($ok && $oldData !== null) {
                $newData = $this->findById($id);
                if (is_array($newData)) {
                    $usuarioId = $_SESSION['user_id'] ?? 1;
                    LogAlteracaoService::registrarAlteracao(
                        'lgpd_fontes_coleta',
                        $id,
                        $usuarioId,
                        'UPDATE',
                        $oldData,
                        $newData
                    );
                }
            }

            return $ok;
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Fonte de coleta não editada.", ['id' => $id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function delete(int $id): bool
    {
        try {
            $oldData = $this->findById($id);
            $sql = "DELETE FROM lgpd_fontes_coleta WHERE id = :id";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $ok = $stmt->execute();
            if ($ok && $oldData !== null && $stmt->rowCount() > 0) {
                $usuarioId = $_SESSION['user_id'] ?? 1;
                LogAlteracaoService::registrarAlteracao(
                    'lgpd_fontes_coleta',
                    $id,
                    $usuarioId,
                    'DELETE',
                    $oldData,
                    []
                );
            }

            return $ok;
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Fonte de coleta não apagada.", ['id' => $id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function getFontesByDataMapping(int $dataMappingId): array
    {
        $sql = "SELECT fc.*, dmf.observacoes 
                FROM lgpd_fontes_coleta fc 
                INNER JOIN lgpd_data_mapping_fontes dmf ON fc.id = dmf.fonte_coleta_id 
                WHERE dmf.data_mapping_id = :data_mapping_id 
                ORDER BY fc.nome";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':data_mapping_id', $dataMappingId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function saveFontesForDataMapping(int $dataMappingId, array $fontesIds, array $observacoes = []): bool
    {
        try {
            // Primeiro, remove todas as fontes existentes para este data mapping
            $sql = "DELETE FROM lgpd_data_mapping_fontes WHERE data_mapping_id = :data_mapping_id";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':data_mapping_id', $dataMappingId, PDO::PARAM_INT);
            $stmt->execute();

            // Depois, insere as novas fontes
            if (!empty($fontesIds)) {
                $sql = "INSERT INTO lgpd_data_mapping_fontes (data_mapping_id, fonte_coleta_id, observacoes) VALUES (:data_mapping_id, :fonte_coleta_id, :observacoes)";
                $stmt = $this->getConnection()->prepare($sql);
                
                foreach ($fontesIds as $fonteId) {
                    $stmt->bindValue(':data_mapping_id', $dataMappingId, PDO::PARAM_INT);
                    $stmt->bindValue(':fonte_coleta_id', $fonteId, PDO::PARAM_INT);
                    $stmt->bindValue(':observacoes', $observacoes[$fonteId] ?? null, PDO::PARAM_STR);
                    $stmt->execute();
                }
            }
            
            return true;
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Erro ao salvar fontes para data mapping.", ['data_mapping_id' => $dataMappingId, 'error' => $e->getMessage()]);
            return false;
        }
    }
} 