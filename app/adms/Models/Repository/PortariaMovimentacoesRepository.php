<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use PDO;
use Throwable;

final class PortariaMovimentacoesRepository extends DbConnection
{
    /** @param array<string, mixed> $data */
    public function registrarEntrada(array $data): int|false
    {
        return $this->insertMovimento('entrada', $data);
    }

    /** @param array<string, mixed> $data */
    public function registrarSaida(array $data): int|false
    {
        $entrada = $this->getEntradaAberta((int) ($data['visitante_id'] ?? 0));
        if ($entrada !== null) {
            $data['movimentacao_entrada_id'] = (int) $entrada['id'];
            $data['autorizacao_id'] = (int) ($data['autorizacao_id'] ?? $entrada['autorizacao_id'] ?? 0);
        }
        $data['permanencia_excedida'] = $this->permanenciaExcedida((int) ($data['autorizacao_id'] ?? 0));
        return $this->insertMovimento('saida', $data);
    }

    /** @return array<string, mixed>|null */
    public function getEntradaAberta(int $visitanteId): ?array
    {
        try {
            $stmt = $this->getConnection()->prepare(
                "SELECT e.* FROM portaria_movimentacoes e
                 WHERE e.visitante_id = :visitante_id AND e.tipo = 'entrada'
                   AND NOT EXISTS (
                       SELECT 1 FROM portaria_movimentacoes s
                       WHERE s.tipo = 'saida' AND s.visitante_id = e.visitante_id
                         AND s.ocorrido_em >= e.ocorrido_em
                   )
                 ORDER BY e.ocorrido_em DESC, e.id DESC LIMIT 1"
            );
            $stmt->bindValue(':visitante_id', $visitanteId, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Throwable $e) {
            GenerateLog::generateLog('error', 'PortariaMovimentacoesRepository::getEntradaAberta', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /** @return list<array<string, mixed>> */
    public function listPresentesAgora(): array
    {
        try {
            $stmt = $this->getConnection()->query(
                "SELECT e.*, v.nome AS visitante_nome, v.empresa, p.nome AS ponto_nome,
                        a.protocolo, u.name AS anfitriao_nome
                 FROM portaria_movimentacoes e
                 INNER JOIN portaria_visitantes v ON v.id = e.visitante_id
                 LEFT JOIN portaria_pontos_controle p ON p.id = e.ponto_controle_id
                 LEFT JOIN portaria_autorizacoes a ON a.id = e.autorizacao_id
                 LEFT JOIN adms_users u ON u.id = a.anfitriao_user_id
                 WHERE e.tipo = 'entrada'
                   AND NOT EXISTS (
                       SELECT 1 FROM portaria_movimentacoes s
                       WHERE s.tipo = 'saida' AND s.visitante_id = e.visitante_id
                         AND s.ocorrido_em >= e.ocorrido_em
                   )
                 ORDER BY e.ocorrido_em ASC"
            );
            return $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
        } catch (Throwable $e) {
            GenerateLog::generateLog('error', 'PortariaMovimentacoesRepository::listPresentesAgora', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /** @param array<string, mixed> $data */
    public function regularizarSaidaEEntrar(array $data): bool
    {
        $entrada = $this->getEntradaAberta((int) ($data['visitante_id'] ?? 0));
        if ($entrada === null || trim((string) ($data['motivo_regularizacao'] ?? '')) === '') {
            return false;
        }
        $connection = $this->getConnection();
        try {
            $connection->beginTransaction();
            $saida = $data;
            $saida['autorizacao_id'] = $entrada['autorizacao_id'];
            $saida['movimentacao_entrada_id'] = $entrada['id'];
            $saida['regularizada'] = 1;
            if (!$this->insertMovimento('saida', $saida)) {
                throw new \RuntimeException('Falha ao registrar saída regularizada.');
            }
            $entradaNova = $data;
            $entradaNova['regularizada'] = 0;
            $entradaNova['motivo_regularizacao'] = null;
            if (!$this->insertMovimento('entrada', $entradaNova)) {
                throw new \RuntimeException('Falha ao registrar nova entrada.');
            }
            $connection->commit();
            return true;
        } catch (Throwable $e) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            GenerateLog::generateLog('error', 'PortariaMovimentacoesRepository::regularizarSaidaEEntrar', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /** @param array<string, mixed> $data */
    private function insertMovimento(string $tipo, array $data): int|false
    {
        try {
            $stmt = $this->getConnection()->prepare(
                'INSERT INTO portaria_movimentacoes
                 (autorizacao_id, visitante_id, tipo, ponto_controle_id, porteiro_user_id, ocorrido_em,
                  regularizada, motivo_regularizacao, movimentacao_entrada_id, permanencia_excedida, observacoes)
                 VALUES (:autorizacao_id, :visitante_id, :tipo, :ponto_id, :porteiro_id, :ocorrido_em,
                         :regularizada, :motivo, :entrada_id, :excedida, :observacoes)'
            );
            $stmt->execute([
                ':autorizacao_id' => (int) ($data['autorizacao_id'] ?? 0) ?: null,
                ':visitante_id' => (int) ($data['visitante_id'] ?? 0),
                ':tipo' => $tipo,
                ':ponto_id' => (int) ($data['ponto_controle_id'] ?? 0),
                ':porteiro_id' => (int) ($data['porteiro_user_id'] ?? 0),
                ':ocorrido_em' => (string) ($data['ocorrido_em'] ?? date('Y-m-d H:i:s')),
                ':regularizada' => !empty($data['regularizada']) ? 1 : 0,
                ':motivo' => trim((string) ($data['motivo_regularizacao'] ?? '')) ?: null,
                ':entrada_id' => (int) ($data['movimentacao_entrada_id'] ?? 0) ?: null,
                ':excedida' => !empty($data['permanencia_excedida']) ? 1 : 0,
                ':observacoes' => trim((string) ($data['observacoes'] ?? '')) ?: null,
            ]);
            $id = (int) $this->getConnection()->lastInsertId();
            return $id > 0 ? $id : false;
        } catch (Throwable $e) {
            GenerateLog::generateLog('error', 'PortariaMovimentacoesRepository::insertMovimento', ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function permanenciaExcedida(int $autorizacaoId): bool
    {
        if ($autorizacaoId <= 0) {
            return false;
        }
        $stmt = $this->getConnection()->prepare(
            "SELECT (NOW() > TIMESTAMP(data_fim, COALESCE(hora_fim, '23:59:59')))
             FROM portaria_autorizacoes WHERE id = :id"
        );
        $stmt->bindValue(':id', $autorizacaoId, PDO::PARAM_INT);
        $stmt->execute();
        return (bool) $stmt->fetchColumn();
    }
}
