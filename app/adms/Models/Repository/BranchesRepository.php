<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use Exception;
use PDO;

class BranchesRepository extends DbConnection
{
    public function getAllBranches(int $page = 1, int $limitResult = 10): array
    {
        $offset = max(0, ($page - 1) * $limitResult);
        $sql = 'SELECT id, name, code, address, phone, email, active FROM adms_branches ORDER BY id ASC LIMIT :limit OFFSET :offset';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':limit', $limitResult, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAmountBranches(): int
    {
        $sql = 'SELECT COUNT(id) as amount_records FROM adms_branches';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['amount_records'] ?? 0);
    }

    public function getBranch(int $id): array|bool
    {
        $sql = 'SELECT id, name, code, address, phone, email, active, created_at, updated_at FROM adms_branches WHERE id = :id ORDER BY id DESC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createBranch(array $data): bool|int
    {
        try {
            $sql = 'INSERT INTO adms_branches (name, code, address, phone, email, active, created_at) VALUES (:name, :code, :address, :phone, :email, :active, :created_at)';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
            $stmt->bindValue(':code', $data['code'], PDO::PARAM_STR);
            $stmt->bindValue(':address', $data['address'], PDO::PARAM_STR);
            $stmt->bindValue(':phone', $data['phone'], PDO::PARAM_STR);
            $stmt->bindValue(':email', $data['email'], PDO::PARAM_STR);
            $stmt->bindValue(':active', $data['active'], PDO::PARAM_INT);
            $stmt->bindValue(':created_at', date("Y-m-d H:i:s"));
            $stmt->execute();

            $branchId = $this->getConnection()->lastInsertId();

            if ($branchId) {
                $usuarioId = $_SESSION['user_id'] ?? 1;
                LogAlteracaoService::registrarAlteracao(
                    'adms_branches',
                    $branchId,
                    $usuarioId,
                    'INSERT',
                    [],
                    $data
                );
            }

            return $branchId;
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Filial não cadastrada.", ['name' => $data['name'], 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function updateBranch(array $data): bool
    {
        try {
            $oldData = $this->getBranch($data['id']);
            $sql = 'UPDATE adms_branches SET name = :name, code = :code, address = :address, phone = :phone, email = :email, active = :active, updated_at = :updated_at WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
            $stmt->bindValue(':code', $data['code'], PDO::PARAM_STR);
            $stmt->bindValue(':address', $data['address'], PDO::PARAM_STR);
            $stmt->bindValue(':phone', $data['phone'], PDO::PARAM_STR);
            $stmt->bindValue(':email', $data['email'], PDO::PARAM_STR);
            $stmt->bindValue(':active', $data['active'], PDO::PARAM_INT);
            $stmt->bindValue(':updated_at', date("Y-m-d H:i:s"));
            $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);
            $result = $stmt->execute();

            if ($result && $oldData) {
                $usuarioId = $_SESSION['user_id'] ?? 1;
                LogAlteracaoService::registrarAlteracao(
                    'adms_branches',
                    $data['id'],
                    $usuarioId,
                    'UPDATE',
                    $oldData,
                    $data
                );
            }

            return $result;
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Filial não editada.", ['id' => $data['id'], 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function deleteBranch(int $id): bool
    {
        try {
            $oldData = $this->getBranch($id);
            $sql = 'DELETE FROM adms_branches WHERE id = :id LIMIT 1';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $affectedRows = $stmt->rowCount();

            if ($affectedRows > 0) {
                if ($oldData) {
                    $usuarioId = $_SESSION['user_id'] ?? 1;
                    LogAlteracaoService::registrarAlteracao(
                        'adms_branches',
                        $id,
                        $usuarioId,
                        'DELETE',
                        $oldData,
                        []
                    );
                }
                return true;
            } else {
                GenerateLog::generateLog("error", "Filial não apagada.", ['id' => $id]);
                return false;
            }
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Filial não apagada.", ['id' => $id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function getAllBranchesSelect(): array
    {
        $sql = 'SELECT id, name, code FROM adms_branches WHERE active = 1 ORDER BY name ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 