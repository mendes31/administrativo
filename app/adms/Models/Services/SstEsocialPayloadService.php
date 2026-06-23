<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\SstAcidentesRepository;
use App\adms\Models\Repository\SstAsosRepository;
use App\adms\Models\Repository\SstEpiEntregasRepository;
use App\adms\Models\Repository\SstEsocialEventosRepository;
use App\adms\Models\Repository\SstTreinamentoAplicacoesRepository;
use App\adms\Models\Repository\UsersRepository;

/**
 * Gera payloads estruturados para eventos SST do eSocial (exportação manual).
 * Não envia à API do governo — prepara dados para transmissão externa.
 */
class SstEsocialPayloadService
{
    public const EVENTO_ACIDENTE = 'S-2210';
    public const EVENTO_ASO = 'S-2220';
    public const EVENTO_EPI = 'S-2240';
    public const EVENTO_TREINAMENTO = 'S-2245';

    public function gerarOuAtualizar(string $tipoEvento, string $origemTabela, int $origemId): array
    {
        $payload = match ($tipoEvento) {
            self::EVENTO_ACIDENTE => $this->buildS2210($origemId),
            self::EVENTO_ASO => $this->buildS2220($origemId),
            self::EVENTO_EPI => $this->buildS2240($origemId),
            self::EVENTO_TREINAMENTO => $this->buildS2245($origemId),
            default => throw new \InvalidArgumentException('Tipo de evento inválido.'),
        };

        $repo = new SstEsocialEventosRepository();
        $existente = $repo->findByOrigem($tipoEvento, $origemTabela, $origemId);
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $now = date('Y-m-d H:i:s');

        $data = [
            'payload_json' => $json,
            'status' => 'Gerado',
            'data_geracao' => $now,
            'protocolo' => null,
            'mensagem_retorno' => null,
            'data_envio' => null,
        ];

        if ($existente) {
            $repo->update((int) $existente['id'], $data);
            return ['id' => (int) $existente['id'], 'acao' => 'atualizado', 'payload' => $payload];
        }

        $newId = $repo->create(array_merge($data, [
            'tipo_evento' => $tipoEvento,
            'origem_tabela' => $origemTabela,
            'origem_id' => $origemId,
            'adms_user_id' => (int) $payload['colaborador']['id'],
        ]));

        if (!$newId) {
            throw new \RuntimeException('Não foi possível registrar o evento eSocial.');
        }

        return ['id' => $newId, 'acao' => 'criado', 'payload' => $payload];
    }

    /**
     * @return array{criados: int, erros: array<int, string>}
     */
    public function sincronizarPendentes(): array
    {
        $criados = 0;
        $erros = [];

        foreach ($this->listarOrigensSemEvento(self::EVENTO_ACIDENTE, 'adms_sst_acidentes') as $row) {
            try {
                $this->gerarOuAtualizar(self::EVENTO_ACIDENTE, 'adms_sst_acidentes', (int) $row['id']);
                $criados++;
            } catch (\Throwable $e) {
                $erros[] = 'Acidente #' . $row['id'] . ': ' . $e->getMessage();
            }
        }

        foreach ($this->listarOrigensSemEvento(self::EVENTO_ASO, 'adms_sst_asos') as $row) {
            try {
                $this->gerarOuAtualizar(self::EVENTO_ASO, 'adms_sst_asos', (int) $row['id']);
                $criados++;
            } catch (\Throwable $e) {
                $erros[] = 'ASO #' . $row['id'] . ': ' . $e->getMessage();
            }
        }

        foreach ($this->listarOrigensSemEvento(self::EVENTO_EPI, 'adms_sst_epi_entregas', "tipo_movimento = 'Entrega'") as $row) {
            try {
                $this->gerarOuAtualizar(self::EVENTO_EPI, 'adms_sst_epi_entregas', (int) $row['id']);
                $criados++;
            } catch (\Throwable $e) {
                $erros[] = 'EPI entrega #' . $row['id'] . ': ' . $e->getMessage();
            }
        }

        if ($this->hasTreinamentosTable()) {
            foreach ($this->listarOrigensSemEvento(
                self::EVENTO_TREINAMENTO,
                'adms_sst_treinamento_aplicacoes',
                "status = 'concluido' AND data_realizacao IS NOT NULL"
            ) as $row) {
                try {
                    $this->gerarOuAtualizar(self::EVENTO_TREINAMENTO, 'adms_sst_treinamento_aplicacoes', (int) $row['id']);
                    $criados++;
                } catch (\Throwable $e) {
                    $erros[] = 'Treinamento aplicação #' . $row['id'] . ': ' . $e->getMessage();
                }
            }
        }

        return ['criados' => $criados, 'erros' => $erros];
    }

    private function buildS2210(int $acidenteId): array
    {
        $repo = new SstAcidentesRepository();
        $row = $repo->getById($acidenteId);
        if (!$row) {
            throw new \RuntimeException('Acidente não encontrado.');
        }

        $user = $this->getColaborador((int) $row['adms_user_id']);

        return [
            'evento' => self::EVENTO_ACIDENTE,
            'descricao' => 'Comunicação de Acidente de Trabalho (CAT)',
            'layout_referencia' => 'S-1.2',
            'gerado_em' => date('c'),
            'origem_sistema' => ['tabela' => 'adms_sst_acidentes', 'id' => $acidenteId],
            'colaborador' => $user,
            'dados' => [
                'tipo_ocorrencia' => $row['tipo'] ?? 'Acidente',
                'data_ocorrencia' => $row['data_ocorrencia'] ?? null,
                'local' => $row['local'] ?? null,
                'descricao' => $row['descricao'] ?? null,
                'cat_numero' => $row['cat_numero'] ?? null,
                'cat_data' => $row['cat_data'] ?? null,
                'cid' => $row['cid_nome'] ?? null,
                'status_investigacao' => $row['status'] ?? null,
            ],
            'observacao' => 'Payload simplificado para conferência e exportação. Validar no transmissor eSocial antes do envio.',
        ];
    }

    private function buildS2220(int $asoId): array
    {
        $repo = new SstAsosRepository();
        $row = $repo->getById($asoId);
        if (!$row) {
            throw new \RuntimeException('ASO não encontrado.');
        }

        $user = $this->getColaborador((int) $row['adms_user_id']);

        return [
            'evento' => self::EVENTO_ASO,
            'descricao' => 'Monitoramento da Saúde do Trabalhador',
            'layout_referencia' => 'S-1.2',
            'gerado_em' => date('c'),
            'origem_sistema' => ['tabela' => 'adms_sst_asos', 'id' => $asoId],
            'colaborador' => $user,
            'dados' => [
                'tipo_aso' => $row['tipo'] ?? null,
                'exame' => $row['exame_nome'] ?? null,
                'data_realizacao' => $row['data_realizacao'] ?? null,
                'data_validade' => $row['data_validade'] ?? null,
                'resultado' => $row['resultado'] ?? null,
                'restricoes' => $row['restricoes'] ?? null,
                'clinica' => $row['clinica'] ?? null,
            ],
            'observacao' => 'Payload simplificado para conferência e exportação. Validar no transmissor eSocial antes do envio.',
        ];
    }

    private function buildS2240(int $entregaId): array
    {
        $repo = new SstEpiEntregasRepository();
        $row = $repo->getById($entregaId);
        if (!$row) {
            throw new \RuntimeException('Entrega de EPI não encontrada.');
        }
        if (($row['tipo_movimento'] ?? '') !== 'Entrega') {
            throw new \RuntimeException('Somente movimentos do tipo Entrega geram S-2240.');
        }

        $user = $this->getColaborador((int) $row['adms_user_id']);

        return [
            'evento' => self::EVENTO_EPI,
            'descricao' => 'Condições Ambientais do Trabalho — Agentes Nocivos / EPI',
            'layout_referencia' => 'S-1.2',
            'gerado_em' => date('c'),
            'origem_sistema' => ['tabela' => 'adms_sst_epi_entregas', 'id' => $entregaId],
            'colaborador' => $user,
            'dados' => [
                'epi' => $row['epi_nome'] ?? null,
                'quantidade' => (int) ($row['quantidade'] ?? 0),
                'data_entrega' => $row['data_movimento'] ?? null,
                'data_prevista_troca' => $row['data_prevista_troca'] ?? null,
                'termo_assinado' => !empty($row['termo_assinado']),
            ],
            'observacao' => 'Payload simplificado para conferência e exportação. Validar no transmissor eSocial antes do envio.',
        ];
    }

    private function buildS2245(int $aplicacaoId): array
    {
        $repo = new SstTreinamentoAplicacoesRepository();
        $row = $repo->getByIdDetalhado($aplicacaoId);
        if (!$row) {
            throw new \RuntimeException('Aplicação de treinamento não encontrada.');
        }
        if (($row['status'] ?? '') !== 'concluido' || empty($row['data_realizacao'])) {
            throw new \RuntimeException('Somente aplicações concluídas com data de realização geram S-2245.');
        }

        $user = $this->getColaborador((int) $row['adms_user_id']);
        $cargaMin = (int) ($row['carga_horaria_minutos'] ?? 0);

        return [
            'evento' => self::EVENTO_TREINAMENTO,
            'descricao' => 'Treinamentos, Capacitações, Exercícios Simulados e Outras Anotações',
            'layout_referencia' => 'S-1.2',
            'gerado_em' => date('c'),
            'origem_sistema' => ['tabela' => 'adms_sst_treinamento_aplicacoes', 'id' => $aplicacaoId],
            'colaborador' => $user,
            'dados' => [
                'treinamento' => $row['treinamento_nome'] ?? null,
                'codigo' => $row['treinamento_codigo'] ?? null,
                'nr_referencia' => $row['nr_referencia'] ?? null,
                'tipo_treinamento' => $row['treinamento_tipo'] ?? null,
                'modalidade' => $row['modalidade_aplicada'] ?? $row['treinamento_modalidade'] ?? null,
                'carga_horaria_minutos' => $cargaMin > 0 ? $cargaMin : null,
                'data_realizacao' => $row['data_realizacao'] ?? null,
                'data_validade' => $row['data_validade_vinculo'] ?? null,
                'instrutor_nome' => $row['instrutor_nome'] ?? null,
                'instrutor_registro' => $row['instrutor_registro'] ?? null,
                'nota' => $row['nota'] ?? null,
                'certificado' => !empty($row['certificado']),
                'vinculo_id' => (int) ($row['adms_sst_treinamento_vinculo_id'] ?? 0),
            ],
            'observacao' => 'Payload simplificado para conferência e exportação. Validar no transmissor eSocial antes do envio.',
        ];
    }

    /** @return array{id: int, name: string, cpf: ?string} */
    private function getColaborador(int $userId): array
    {
        $user = (new UsersRepository())->getUser($userId);
        if (!$user) {
            throw new \RuntimeException('Colaborador não encontrado.');
        }

        $cpf = preg_replace('/\D/', '', (string) ($user['cpf'] ?? ''));
        if (strlen($cpf) !== 11) {
            throw new \RuntimeException('CPF do colaborador inválido ou ausente.');
        }

        return [
            'id' => $userId,
            'name' => (string) ($user['name'] ?? ''),
            'cpf' => $cpf,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function listarOrigensSemEvento(string $tipoEvento, string $tabela, string $extraWhere = '1=1'): array
    {
        $conn = (new SstEsocialEventosRepository())->getConnection();
        $sql = "SELECT o.id FROM {$tabela} o
                LEFT JOIN adms_sst_esocial_eventos e ON e.origem_tabela = :tab
                    AND e.origem_id = o.id AND e.tipo_evento = :tipo AND e.status <> 'Cancelado'
                WHERE e.id IS NULL AND {$extraWhere}
                ORDER BY o.id DESC
                LIMIT 200";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':tab', $tabela);
        $stmt->bindValue(':tipo', $tipoEvento);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    private function hasTreinamentosTable(): bool
    {
        $conn = (new SstEsocialEventosRepository())->getConnection();
        $stmt = $conn->prepare(
            'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :t LIMIT 1'
        );
        $stmt->bindValue(':t', 'adms_sst_treinamento_aplicacoes');
        $stmt->execute();

        return (bool) $stmt->fetchColumn();
    }
}
