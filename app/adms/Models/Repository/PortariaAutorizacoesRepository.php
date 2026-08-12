<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use PDO;
use Throwable;

final class PortariaAutorizacoesRepository extends DbConnection
{
    /** @param array<string, mixed> $filters @return list<array<string, mixed>> */
    public function getAll(array $filters = []): array
    {
        $where = ['1 = 1'];
        $params = [];
        if (trim((string) ($filters['status'] ?? '')) !== '') {
            $where[] = 'a.status = :status';
            $params[':status'] = trim((string) $filters['status']);
        }
        $busca = trim((string) ($filters['busca'] ?? ''));
        if ($busca !== '') {
            $docDigits = preg_replace('/\D+/', '', $busca) ?? '';
            $conds = ['v.nome LIKE :busca', 'v.documento LIKE :busca', 'a.protocolo LIKE :busca'];
            $params[':busca'] = '%' . $busca . '%';
            if ($docDigits !== '') {
                $conds[] = 'REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(v.documento,\'\'), \'.\', \'\'), \'-\', \'\'), \'/\', \'\'), \' \', \'\') LIKE :busca_doc';
                $params[':busca_doc'] = '%' . $docDigits . '%';
            }
            $where[] = '(' . implode(' OR ', $conds) . ')';
        }
        if (!empty($filters['visitante_id'])) {
            $where[] = 'a.visitante_id = :visitante_id';
            $params[':visitante_id'] = (int) $filters['visitante_id'];
        }
        if (!empty($filters['somente_vigentes_hoje'])) {
            $where[] = 'a.data_inicio <= CURDATE() AND a.data_fim >= CURDATE()';
        }
        try {
            $stmt = $this->getConnection()->prepare(
                'SELECT a.*, v.nome AS visitante_nome, v.documento AS visitante_documento,
                        u.name AS anfitriao_nome, d.name AS departamento_nome
                 FROM portaria_autorizacoes a
                 INNER JOIN portaria_visitantes v ON v.id = a.visitante_id
                 LEFT JOIN adms_users u ON u.id = a.anfitriao_user_id
                 LEFT JOIN adms_departments d ON d.id = a.destino_departamento_id
                 WHERE ' . implode(' AND ', $where) . ' ORDER BY a.data_inicio DESC, a.id DESC'
            );
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            GenerateLog::generateLog('error', 'PortariaAutorizacoesRepository::getAll', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Pesquisa agenda/autorização pelo nome ou documento do visitante.
     *
     * @return list<array<string, mixed>>
     */
    public function buscarPorVisitante(string $busca, bool $somenteHoje = true): array
    {
        $busca = trim($busca);
        if ($busca === '') {
            return [];
        }
        $filters = [
            'busca' => $busca,
            'somente_vigentes_hoje' => $somenteHoje,
        ];
        // Prioriza autorizadas; inclui aguardando para o porteiro ver a agenda pendente.
        $rows = $this->getAll($filters);
        usort($rows, static function (array $a, array $b): int {
            $rank = static fn (string $s): int => match ($s) {
                'autorizada' => 0,
                'aguardando' => 1,
                default => 2,
            };
            $cmp = $rank((string) ($a['status'] ?? '')) <=> $rank((string) ($b['status'] ?? ''));
            return $cmp !== 0 ? $cmp : ((int) ($b['id'] ?? 0) <=> (int) ($a['id'] ?? 0));
        });
        return $rows;
    }

    /** @return array<string, mixed>|null */
    public function getById(int $id): ?array
    {
        try {
            $stmt = $this->getConnection()->prepare(
                'SELECT a.*, v.nome AS visitante_nome, v.documento AS visitante_documento,
                        u.name AS anfitriao_nome, d.name AS departamento_nome
                 FROM portaria_autorizacoes a
                 INNER JOIN portaria_visitantes v ON v.id = a.visitante_id
                 LEFT JOIN adms_users u ON u.id = a.anfitriao_user_id
                 LEFT JOIN adms_departments d ON d.id = a.destino_departamento_id
                 WHERE a.id = :id LIMIT 1'
            );
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return null;
            }
            $contacts = $this->getConnection()->prepare(
                'SELECT c.*, u.name AS porteiro_nome FROM portaria_autorizacao_contatos c
                 LEFT JOIN adms_users u ON u.id = c.porteiro_user_id
                 WHERE c.autorizacao_id = :id ORDER BY c.ocorrido_em DESC'
            );
            $contacts->bindValue(':id', $id, PDO::PARAM_INT);
            $contacts->execute();
            $row['contatos'] = $contacts->fetchAll(PDO::FETCH_ASSOC) ?: [];
            return $row;
        } catch (Throwable $e) {
            GenerateLog::generateLog('error', 'PortariaAutorizacoesRepository::getById', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int|false
    {
        try {
            $protocolo = 'PORT-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
            $stmt = $this->getConnection()->prepare(
                'INSERT INTO portaria_autorizacoes
                 (protocolo, visitante_id, anfitriao_user_id, criado_por_user_id, destino_departamento_id,
                  motivo, tipo, data_inicio, data_fim, hora_inicio, hora_fim, dias_permitidos, status,
                  origem, veiculo_placa, observacoes, decisao_token, decisao_token_em)
                 VALUES (:protocolo, :visitante_id, :anfitriao_id, :criador_id, :departamento_id,
                         :motivo, :tipo, :data_inicio, :data_fim, :hora_inicio, :hora_fim, :dias,
                         :status, :origem, :placa, :observacoes, :decisao_token, NOW())'
            );
            $stmt->execute([
                ':protocolo' => $protocolo,
                ':visitante_id' => (int) ($data['visitante_id'] ?? 0),
                ':anfitriao_id' => (int) ($data['anfitriao_user_id'] ?? 0),
                ':criador_id' => (int) ($data['criado_por_user_id'] ?? 0) ?: null,
                ':departamento_id' => (int) ($data['destino_departamento_id'] ?? 0) ?: null,
                ':motivo' => trim((string) ($data['motivo'] ?? '')) ?: null,
                ':tipo' => trim((string) ($data['tipo'] ?? 'periodo')),
                ':data_inicio' => (string) ($data['data_inicio'] ?? date('Y-m-d')),
                ':data_fim' => (string) ($data['data_fim'] ?? $data['data_inicio'] ?? date('Y-m-d')),
                ':hora_inicio' => trim((string) ($data['hora_inicio'] ?? '')) ?: null,
                ':hora_fim' => trim((string) ($data['hora_fim'] ?? '')) ?: null,
                ':dias' => trim((string) ($data['dias_permitidos'] ?? '')) ?: null,
                ':status' => trim((string) ($data['status'] ?? 'aguardando')),
                ':origem' => trim((string) ($data['origem'] ?? 'agendada')),
                ':placa' => strtoupper(trim((string) ($data['veiculo_placa'] ?? ''))) ?: null,
                ':observacoes' => trim((string) ($data['observacoes'] ?? '')) ?: null,
                ':decisao_token' => bin2hex(random_bytes(32)),
            ]);
            $id = (int) $this->getConnection()->lastInsertId();
            return $id > 0 ? $id : false;
        } catch (Throwable $e) {
            GenerateLog::generateLog('error', 'PortariaAutorizacoesRepository::create', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /** @return array<string, mixed>|null */
    public function findByDecisaoToken(string $token): ?array
    {
        $token = trim($token);
        if ($token === '' || strlen($token) < 32) {
            return null;
        }
        try {
            $stmt = $this->getConnection()->prepare(
                'SELECT a.*, v.nome AS visitante_nome, v.documento AS visitante_documento,
                        u.name AS anfitriao_nome, d.name AS departamento_nome
                 FROM portaria_autorizacoes a
                 INNER JOIN portaria_visitantes v ON v.id = a.visitante_id
                 LEFT JOIN adms_users u ON u.id = a.anfitriao_user_id
                 LEFT JOIN adms_departments d ON d.id = a.destino_departamento_id
                 WHERE a.decisao_token = :token LIMIT 1'
            );
            $stmt->bindValue(':token', $token, PDO::PARAM_STR);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Throwable $e) {
            GenerateLog::generateLog('error', 'PortariaAutorizacoesRepository::findByDecisaoToken', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /** Garante token para links públicos Autorizar/Recusar. */
    public function ensureDecisaoToken(int $autorizacaoId): ?string
    {
        if ($autorizacaoId <= 0) {
            return null;
        }
        try {
            $stmt = $this->getConnection()->prepare(
                'SELECT decisao_token FROM portaria_autorizacoes WHERE id = :id LIMIT 1'
            );
            $stmt->bindValue(':id', $autorizacaoId, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return null;
            }
            $token = trim((string) ($row['decisao_token'] ?? ''));
            if ($token !== '') {
                return $token;
            }
            $token = bin2hex(random_bytes(32));
            $upd = $this->getConnection()->prepare(
                'UPDATE portaria_autorizacoes SET decisao_token = :token, decisao_token_em = NOW() WHERE id = :id'
            );
            $upd->execute([':token' => $token, ':id' => $autorizacaoId]);
            return $token;
        } catch (Throwable $e) {
            GenerateLog::generateLog('error', 'PortariaAutorizacoesRepository::ensureDecisaoToken', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * URLs públicas sem login.
     *
     * @return array{escolha:?string,autorizar:?string,recusar:?string,token:?string}
     */
    public function linksDecisaoPublica(int $autorizacaoId): array
    {
        $out = ['escolha' => null, 'autorizar' => null, 'recusar' => null, 'token' => null];
        $token = $this->ensureDecisaoToken($autorizacaoId);
        if ($token === null) {
            return $out;
        }
        $base = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/portaria-autorizacao-decisao/' . rawurlencode($token);
        $out['token'] = $token;
        $out['escolha'] = $base;
        $out['autorizar'] = $base . '/autorizar';
        $out['recusar'] = $base . '/recusar';
        return $out;
    }

    public function updateStatus(int $id, string $status, int $actorId): bool
    {
        if (!in_array($status, ['aguardando', 'autorizada', 'recusada', 'cancelada'], true)) {
            return false;
        }
        try {
            $autorizadoEm = $status === 'autorizada' ? date('Y-m-d H:i:s') : null;
            $autorizadoPor = $status === 'autorizada' && $actorId > 0 ? $actorId : null;
            $stmt = $this->getConnection()->prepare(
                'UPDATE portaria_autorizacoes SET status = :status,
                 autorizado_em = COALESCE(:autorizado_em, autorizado_em),
                 autorizado_por_user_id = COALESCE(:autorizado_por, autorizado_por_user_id)
                 WHERE id = :id'
            );
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            $stmt->bindValue(
                ':autorizado_em',
                $autorizadoEm,
                $autorizadoEm !== null ? PDO::PARAM_STR : PDO::PARAM_NULL
            );
            $stmt->bindValue(
                ':autorizado_por',
                $autorizadoPor,
                $autorizadoPor !== null ? PDO::PARAM_INT : PDO::PARAM_NULL
            );
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (Throwable $e) {
            GenerateLog::generateLog('error', 'PortariaAutorizacoesRepository::updateStatus', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /** @param array<string, mixed> $data */
    public function registrarContato(int $autorizacaoId, array $data, int $actorId): bool
    {
        if (!in_array((string) ($data['canal'] ?? ''), ['push', 'whatsapp', 'ligacao', 'link_publico', 'outro'], true)) {
            return false;
        }
        try {
            $stmt = $this->getConnection()->prepare(
                'INSERT INTO portaria_autorizacao_contatos
                 (autorizacao_id, canal, resultado, porteiro_user_id, ocorrido_em, observacoes)
                 VALUES (:autorizacao_id, :canal, :resultado, :porteiro_id, NOW(), :observacoes)'
            );
            return $stmt->execute([
                ':autorizacao_id' => $autorizacaoId,
                ':canal' => (string) $data['canal'],
                ':resultado' => trim((string) ($data['resultado'] ?? '')) ?: null,
                ':porteiro_id' => $actorId > 0 ? $actorId : null,
                ':observacoes' => trim((string) ($data['observacoes'] ?? '')) ?: null,
            ]);
        } catch (Throwable $e) {
            GenerateLog::generateLog('error', 'PortariaAutorizacoesRepository::registrarContato', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
