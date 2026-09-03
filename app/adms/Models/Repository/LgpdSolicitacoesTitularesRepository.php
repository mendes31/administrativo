<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Helpers\LgpdAuditHelper;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LgpdTitularRights;
use App\adms\Models\Services\LogAlteracaoService;
use Exception;
use PDO;

class LgpdSolicitacoesTitularesRepository extends DbConnection
{
    /**
     * @return list<array<string, mixed>>
     */
    public function getAll(int $limit = 300): array
    {
        try {
            $limit = max(1, min(500, $limit));
            $sql = "SELECT id, protocolo, titular_nome, titular_email, titular_cpf, titular_categoria,
                           tipo, status, prioridade, prazo, por_procurador, created_at, atendido_em
                    FROM lgpd_solicitacoes_titulares
                    ORDER BY
                        CASE status
                            WHEN 'Pendente' THEN 1
                            WHEN 'Em andamento' THEN 2
                            WHEN 'Aguardando titular' THEN 3
                            WHEN 'Vencida' THEN 4
                            ELSE 5
                        END,
                        created_at DESC
                    LIMIT {$limit}";
            $stmt = $this->getConnection()->query($sql);

            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (Exception $e) {
            error_log('Erro ao listar solicitações LGPD: ' . $e->getMessage());

            return [];
        }
    }

    public function getById(int $id): ?array
    {
        try {
            $stmt = $this->getConnection()->prepare(
                'SELECT * FROM lgpd_solicitacoes_titulares WHERE id = :id LIMIT 1'
            );
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row ?: null;
        } catch (Exception $e) {
            error_log('Erro ao buscar solicitação LGPD: ' . $e->getMessage());

            return null;
        }
    }

    public function countPendentes(): int
    {
        try {
            $sql = "SELECT COUNT(*) FROM lgpd_solicitacoes_titulares
                    WHERE status IN ('Pendente', 'Em andamento', 'Aguardando titular', 'Vencida')";
            $val = $this->getConnection()->query($sql)?->fetchColumn();

            return (int) $val;
        } catch (Exception) {
            return 0;
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array{id:int, protocolo:string}|null
     */
    public function createFromPublicForm(array $data): ?array
    {
        $protocolo = $this->nextProtocolo();
        $prazo = (new \DateTimeImmutable('now'))->modify('+15 days')->format('Y-m-d');
        $audit = LgpdAuditHelper::collectTechnicalData();
        $direitos = is_array($data['direitos'] ?? null) ? $data['direitos'] : [];
        $tipo = $this->tipoFromDireitos($direitos);

        try {
            $sql = 'INSERT INTO lgpd_solicitacoes_titulares (
                        protocolo, titular_nome, titular_email, titular_cpf, titular_endereco,
                        titular_nascimento, titular_telefone, titular_categoria, titular_categoria_outro,
                        informacoes_adicionais, por_procurador, procurador_nome, procurador_cpf, procurador_email,
                        direitos_json, comunicacao_meio, comunicacao_outro, tipo, descricao, prioridade, status,
                        prazo, ip_address, user_agent, created_at
                    ) VALUES (
                        :protocolo, :titular_nome, :titular_email, :titular_cpf, :titular_endereco,
                        :titular_nascimento, :titular_telefone, :titular_categoria, :titular_categoria_outro,
                        :informacoes_adicionais, :por_procurador, :procurador_nome, :procurador_cpf, :procurador_email,
                        :direitos_json, :comunicacao_meio, :comunicacao_outro, :tipo, :descricao, :prioridade, :status,
                        :prazo, :ip_address, :user_agent, NOW()
                    )';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute([
                ':protocolo' => $protocolo,
                ':titular_nome' => $data['titular_nome'],
                ':titular_email' => $data['titular_email'],
                ':titular_cpf' => $data['titular_cpf'] ?: null,
                ':titular_endereco' => $data['titular_endereco'] ?: null,
                ':titular_nascimento' => $data['titular_nascimento'] ?: null,
                ':titular_telefone' => $data['titular_telefone'] ?: null,
                ':titular_categoria' => $data['titular_categoria'] ?: null,
                ':titular_categoria_outro' => $data['titular_categoria_outro'] ?: null,
                ':informacoes_adicionais' => $data['informacoes_adicionais'] ?: null,
                ':por_procurador' => !empty($data['por_procurador']) ? 1 : 0,
                ':procurador_nome' => $data['procurador_nome'] ?: null,
                ':procurador_cpf' => $data['procurador_cpf'] ?: null,
                ':procurador_email' => $data['procurador_email'] ?: null,
                ':direitos_json' => json_encode($direitos, JSON_UNESCAPED_UNICODE),
                ':comunicacao_meio' => $data['comunicacao_meio'] ?: 'email',
                ':comunicacao_outro' => $data['comunicacao_outro'] ?: null,
                ':tipo' => mb_substr($tipo, 0, 100),
                ':descricao' => mb_substr((string) ($data['informacoes_adicionais'] ?? ''), 0, 255) ?: null,
                ':prioridade' => 'Média',
                ':status' => 'Pendente',
                ':prazo' => $prazo,
                ':ip_address' => $audit['ip_address'] ?? null,
                ':user_agent' => mb_substr((string) ($audit['user_agent'] ?? ''), 0, 255) ?: null,
            ]);

            $id = (int) $this->getConnection()->lastInsertId();
            if ($id < 1) {
                return null;
            }

            return ['id' => $id, 'protocolo' => $protocolo];
        } catch (Exception $e) {
            error_log('Erro ao gravar solicitação LGPD: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateAtendimento(int $id, array $data, int $userId): bool
    {
        $atual = $this->getById($id);
        if ($atual === null) {
            return false;
        }

        $status = (string) ($data['status'] ?? $atual['status']);
        $allowed = ['Pendente', 'Em andamento', 'Aguardando titular', 'Concluída', 'Vencida'];
        if (!in_array($status, $allowed, true)) {
            $status = (string) $atual['status'];
        }

        $prioridade = (string) ($data['prioridade'] ?? $atual['prioridade']);
        if (!in_array($prioridade, ['Baixa', 'Média', 'Alta', 'Crítica'], true)) {
            $prioridade = (string) $atual['prioridade'];
        }

        $concluir = $status === 'Concluída';

        try {
            $sql = 'UPDATE lgpd_solicitacoes_titulares SET
                        status = :status,
                        prioridade = :prioridade,
                        responsavel = :responsavel,
                        observacao_atendimento = :observacao,
                        atendido_por = :atendido_por,
                        atendido_em = :atendido_em,
                        updated_at = NOW()
                    WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $ok = $stmt->execute([
                ':status' => $status,
                ':prioridade' => $prioridade,
                ':responsavel' => mb_substr(trim((string) ($data['responsavel'] ?? '')), 0, 100) ?: null,
                ':observacao' => trim((string) ($data['observacao_atendimento'] ?? '')) ?: null,
                ':atendido_por' => $concluir ? $userId : ($atual['atendido_por'] ?? null),
                ':atendido_em' => $concluir ? date('Y-m-d H:i:s') : ($atual['atendido_em'] ?? null),
                ':id' => $id,
            ]);

            if ($ok) {
                LogAlteracaoService::registrarAlteracao(
                    'lgpd_solicitacoes_titulares',
                    $id,
                    $userId,
                    'update',
                    $atual,
                    array_merge($atual, [
                        'status' => $status,
                        'prioridade' => $prioridade,
                        'responsavel' => $data['responsavel'] ?? null,
                        'observacao_atendimento' => $data['observacao_atendimento'] ?? null,
                    ])
                );
            }

            return $ok;
        } catch (Exception $e) {
            error_log('Erro ao atualizar solicitação LGPD: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listComunicacoes(int $solicitacaoId): array
    {
        try {
            if (!$this->tableExists('lgpd_solicitacoes_comunicacoes')) {
                return [];
            }
            $stmt = $this->getConnection()->prepare(
                'SELECT c.*, u.name AS user_nome
                 FROM lgpd_solicitacoes_comunicacoes c
                 LEFT JOIN adms_users u ON u.id = c.user_id
                 WHERE c.solicitacao_id = :id
                 ORDER BY c.created_at DESC, c.id DESC'
            );
            $stmt->bindValue(':id', $solicitacaoId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            error_log('Erro ao listar comunicações LGPD: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * @param array{
     *   tipo:string,
     *   assunto:string,
     *   mensagem:string,
     *   destinatario_email:?string,
     *   destinatario_nome:?string,
     *   enviado:bool,
     *   erro_envio:?string,
     *   user_id:int
     * } $data
     */
    public function addComunicacao(int $solicitacaoId, array $data): ?int
    {
        try {
            if (!$this->tableExists('lgpd_solicitacoes_comunicacoes')) {
                return null;
            }
            $stmt = $this->getConnection()->prepare(
                'INSERT INTO lgpd_solicitacoes_comunicacoes
                    (solicitacao_id, tipo, assunto, mensagem, destinatario_email, destinatario_nome,
                     enviado, erro_envio, user_id, created_at)
                 VALUES
                    (:solicitacao_id, :tipo, :assunto, :mensagem, :destinatario_email, :destinatario_nome,
                     :enviado, :erro_envio, :user_id, NOW())'
            );
            $ok = $stmt->execute([
                ':solicitacao_id' => $solicitacaoId,
                ':tipo' => mb_substr((string) $data['tipo'], 0, 40),
                ':assunto' => mb_substr((string) $data['assunto'], 0, 255),
                ':mensagem' => (string) $data['mensagem'],
                ':destinatario_email' => $data['destinatario_email'] ?: null,
                ':destinatario_nome' => $data['destinatario_nome'] ?: null,
                ':enviado' => !empty($data['enviado']) ? 1 : 0,
                ':erro_envio' => $data['erro_envio'] !== null
                    ? mb_substr((string) $data['erro_envio'], 0, 255)
                    : null,
                ':user_id' => $data['user_id'] > 0 ? $data['user_id'] : null,
            ]);

            return $ok ? (int) $this->getConnection()->lastInsertId() : null;
        } catch (Exception $e) {
            error_log('Erro ao gravar comunicação LGPD: ' . $e->getMessage());

            return null;
        }
    }

    private function tableExists(string $table): bool
    {
        try {
            $stmt = $this->getConnection()->query('SHOW TABLES LIKE ' . $this->getConnection()->quote($table));

            return (bool) ($stmt && $stmt->fetchColumn());
        } catch (Exception) {
            return false;
        }
    }

    private function nextProtocolo(): string
    {
        $prefix = 'LGPD-' . date('Ymd') . '-';
        for ($i = 0; $i < 8; $i++) {
            $code = $prefix . strtoupper(bin2hex(random_bytes(2)));
            $stmt = $this->getConnection()->prepare(
                'SELECT id FROM lgpd_solicitacoes_titulares WHERE protocolo = :p LIMIT 1'
            );
            $stmt->execute([':p' => $code]);
            if (!$stmt->fetch()) {
                return $code;
            }
        }

        return $prefix . strtoupper(bin2hex(random_bytes(4)));
    }

    /**
     * @param array<string, string> $direitos
     */
    private function tipoFromDireitos(array $direitos): string
    {
        $catalog = LgpdTitularRights::catalog();
        $labels = [];
        foreach ($direitos as $key => $val) {
            if ($val !== 'sim' || !isset($catalog[$key])) {
                continue;
            }
            $labels[] = $catalog[$key]['titulo'];
        }

        return $labels === [] ? 'Requisição de direitos' : implode('; ', $labels);
    }
}
