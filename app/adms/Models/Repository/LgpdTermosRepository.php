<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;
use Exception;

class LgpdTermosRepository extends DbConnection
{
    /**
     * Buscar termo ativo mais recente por tipo (ex.: 'login').
     */
    public function getTermoAtivoPorTipo(string $tipo): ?array
    {
        try {
            $sql = "SELECT *
                    FROM lgpd_termos
                    WHERE tipo = :tipo
                      AND status = 'Ativo'
                      AND data_inicio_vigencia <= NOW()
                      AND (data_fim_vigencia IS NULL OR data_fim_vigencia >= NOW())
                    ORDER BY data_inicio_vigencia DESC, id DESC
                    LIMIT 1";

            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':tipo', $tipo, PDO::PARAM_STR);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (Exception $e) {
            error_log('Erro ao buscar termo LGPD por tipo: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Listar termos com paginação simples (para tela administrativa).
     */
    public function getAll(int $page = 1, int $perPage = 10): array
    {
        $offset = max(0, ($page - 1) * $perPage);

        $sql = "SELECT *
                FROM lgpd_termos
                ORDER BY created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAmount(): int
    {
        $sql = "SELECT COUNT(*) AS total FROM lgpd_termos";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT * FROM lgpd_termos WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Buscar o último termo ativo (independente do tipo).
     * Usado como fallback para o consentimento de login.
     */
    public function getLastActiveTerm(): ?array
    {
        try {
            $sql = "SELECT *
                    FROM lgpd_termos
                    WHERE status = 'Ativo'
                    ORDER BY data_inicio_vigencia DESC, id DESC
                    LIMIT 1";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (Exception $e) {
            error_log('Erro ao buscar último termo LGPD ativo: ' . $e->getMessage());
            return null;
        }
    }

    public function create(array $data): bool
    {
        $sql = "INSERT INTO lgpd_termos 
                    (versao, titulo, tipo, conteudo, data_inicio_vigencia, data_fim_vigencia, status, created_at) 
                VALUES 
                    (:versao, :titulo, :tipo, :conteudo, :data_inicio_vigencia, :data_fim_vigencia, :status, NOW())";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':versao', $data['versao'], PDO::PARAM_STR);
        $stmt->bindValue(':titulo', $data['titulo'], PDO::PARAM_STR);
        $stmt->bindValue(':tipo', $data['tipo'], PDO::PARAM_STR);
        $stmt->bindValue(':conteudo', $data['conteudo'], PDO::PARAM_STR);
        $stmt->bindValue(':data_inicio_vigencia', $data['data_inicio_vigencia'], PDO::PARAM_STR);
        $stmt->bindValue(':data_fim_vigencia', $data['data_fim_vigencia'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':status', $data['status'] ?? 'Ativo', PDO::PARAM_STR);

        return $stmt->execute();
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE lgpd_termos
                SET versao = :versao,
                    titulo = :titulo,
                    tipo = :tipo,
                    conteudo = :conteudo,
                    data_inicio_vigencia = :data_inicio_vigencia,
                    data_fim_vigencia = :data_fim_vigencia,
                    status = :status,
                    updated_at = NOW()
                WHERE id = :id";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':versao', $data['versao'], PDO::PARAM_STR);
        $stmt->bindValue(':titulo', $data['titulo'], PDO::PARAM_STR);
        $stmt->bindValue(':tipo', $data['tipo'], PDO::PARAM_STR);
        $stmt->bindValue(':conteudo', $data['conteudo'], PDO::PARAM_STR);
        $stmt->bindValue(':data_inicio_vigencia', $data['data_inicio_vigencia'], PDO::PARAM_STR);
        $stmt->bindValue(':data_fim_vigencia', $data['data_fim_vigencia'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':status', $data['status'] ?? 'Ativo', PDO::PARAM_STR);

        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM lgpd_termos WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}


