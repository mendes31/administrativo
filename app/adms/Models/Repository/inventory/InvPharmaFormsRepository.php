<?php

declare(strict_types=1);

namespace App\adms\Models\Repository\inventory;

use App\adms\Models\Services\DbConnection;
use PDO;

class InvPharmaFormsRepository extends DbConnection
{
    /**
     * @return list<array{id: int|string, name: string}>
     */
    public function getAllForSelect(): array
    {
        $stmt = $this->getConnection()->query(
            'SELECT id, name FROM inv_pharma_forms ORDER BY name ASC'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findIdByName(string $name): ?int
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        $stmt = $this->getConnection()->prepare(
            'SELECT id FROM inv_pharma_forms WHERE name = :name LIMIT 1'
        );
        $stmt->bindValue(':name', $name);
        $stmt->execute();
        $id = $stmt->fetchColumn();

        return $id !== false ? (int)$id : null;
    }

    public function findOrCreateByName(string $name): ?int
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        $id = $this->findIdByName($name);
        if ($id !== null) {
            return $id;
        }

        $stmt = $this->getConnection()->prepare(
            'INSERT INTO inv_pharma_forms (name, created_at, updated_at) VALUES (:name, NOW(), NOW())'
        );
        $stmt->bindValue(':name', mb_substr($name, 0, 100));
        if (!$stmt->execute()) {
            return null;
        }

        $newId = (int)$this->getConnection()->lastInsertId();

        return $newId > 0 ? $newId : null;
    }

    /**
     * Garante cadastro de todas as formas distintas retornadas pelo SAP.
     *
     * @param list<string> $names
     */
    public function ensureNames(array $names): void
    {
        foreach ($names as $name) {
            $this->findOrCreateByName((string)$name);
        }
    }
}
