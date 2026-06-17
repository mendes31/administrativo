<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\EmploymentHistoryRepository;
use App\adms\Models\Repository\SstAsosRepository;
use App\adms\Models\Repository\SstEpiEntregasRepository;
use App\adms\Models\Repository\SstPppRepository;
use App\adms\Models\Repository\UsersRepository;

/**
 * Gera PPP (Perfil Profissiográfico Previdenciário) a partir do histórico SST do colaborador.
 */
class SstPppService
{
    public function gerarESalvar(int $userId, ?string $observacoes = null): array
    {
        $payload = $this->buildPayload($userId);
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if ($json === false) {
            throw new \RuntimeException('Falha ao serializar dados do PPP.');
        }

        $repo = new SstPppRepository();
        $versao = $repo->getNextVersao($userId);
        $id = $repo->create($userId, $versao, $json, $observacoes);
        if (!$id) {
            throw new \RuntimeException('Não foi possível salvar o PPP.');
        }

        return ['id' => $id, 'versao' => $versao, 'payload' => $payload];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildPayload(int $userId): array
    {
        $usersRepo = new UsersRepository();
        $user = $usersRepo->getUser($userId);
        if (!$user) {
            throw new \RuntimeException('Colaborador não encontrado.');
        }

        $profileService = new SstEmployeeProfileService();
        $riscos = $profileService->getRiscosVinculados($userId);
        $asos = (new SstAsosRepository())->getByUserId($userId, 200);
        $epis = (new SstEpiEntregasRepository())->getByUserId($userId, 200);
        $historico = (new EmploymentHistoryRepository())->getByUserId($userId);

        $registrosAmbientais = [];
        foreach ($riscos as $r) {
            $registrosAmbientais[] = [
                'periodo' => $this->periodoAtual($user),
                'cargo' => $user['name_pos'] ?? '-',
                'setor' => $user['name_dep'] ?? '-',
                'agente_nocivo' => $r['risco_nome'] ?? '',
                'tipo' => $r['risco_tipo'] ?? '',
                'intensidade_concentracao' => $r['nivel'] ?? '-',
                'tecnica_utilizada' => 'Avaliação qualitativa — PGR',
                'epi_eficaz' => 'Conforme entregas registradas',
                'epc_eficaz' => 'N/A',
                'ca_epi' => null,
            ];
        }

        $atividades = [];
        foreach ($historico as $h) {
            $atividades[] = [
                'data_inicio' => $h['data_admissao'] ?? null,
                'data_fim' => $h['data_desligamento'] ?? null,
                'tipo' => $h['tipo_periodo'] ?? 'Admissão',
                'observacoes' => $h['observacoes'] ?? null,
            ];
        }
        if (empty($atividades)) {
            $atividades[] = [
                'data_inicio' => $user['data_admissao'] ?? null,
                'data_fim' => $user['data_desligamento'] ?? null,
                'cargo' => $user['name_pos'] ?? '-',
                'setor' => $user['name_dep'] ?? '-',
            ];
        }

        $monitoracao = [];
        foreach ($asos as $a) {
            $monitoracao[] = [
                'data' => $a['data_realizacao'] ?? null,
                'tipo_exame' => $a['tipo'] ?? null,
                'exame' => $a['exame_nome'] ?? null,
                'resultado' => $a['resultado'] ?? null,
                'validade' => $a['data_validade'] ?? null,
            ];
        }

        $entregasEpi = [];
        foreach ($epis as $e) {
            if (($e['tipo_movimento'] ?? '') !== 'Entrega') {
                continue;
            }
            $entregasEpi[] = [
                'data' => $e['data_movimento'] ?? null,
                'epi' => $e['epi_nome'] ?? null,
                'quantidade' => (int) ($e['quantidade'] ?? 0),
                'termo_assinado' => !empty($e['termo_assinado']),
            ];
        }

        return [
            'documento' => 'PPP — Perfil Profissiográfico Previdenciário',
            'layout_referencia' => 'IN 85/PRES/INSS (estrutura simplificada)',
            'gerado_em' => date('c'),
            'versao_sistema' => 'SST Administrativo',
            'trabalhador' => [
                'id' => $userId,
                'nome' => $user['name'] ?? '',
                'cpf' => $this->formatCpf((string) ($user['cpf'] ?? '')),
                'data_nascimento' => $user['data_nascimento'] ?? null,
                'sexo' => $user['sexo'] ?? null,
                'cargo_atual' => $user['name_pos'] ?? '-',
                'departamento_atual' => $user['name_dep'] ?? '-',
                'data_admissao' => $user['data_admissao'] ?? null,
            ],
            'registros_ambientais' => $registrosAmbientais,
            'atividades_exposicoes' => $atividades,
            'monitoracao_biologica' => $monitoracao,
            'entregas_epi' => $entregasEpi,
            'responsavel_geracao' => [
                'usuario_id' => (int) ($_SESSION['user_id'] ?? 0),
                'data' => date('Y-m-d H:i:s'),
            ],
            'observacao_legal' => 'Documento gerado automaticamente a partir dos registros SST. Revisar e complementar dados exigidos pelo INSS antes de uso oficial.',
        ];
    }

    private function periodoAtual(array $user): string
    {
        $ini = !empty($user['data_admissao']) ? date('d/m/Y', strtotime($user['data_admissao'])) : '?';
        $fim = !empty($user['data_desligamento']) ? date('d/m/Y', strtotime($user['data_desligamento'])) : 'atual';

        return "{$ini} a {$fim}";
    }

    private function formatCpf(string $cpf): string
    {
        $n = preg_replace('/\D/', '', $cpf);
        if (strlen($n) !== 11) {
            return $cpf;
        }

        return substr($n, 0, 3) . '.' . substr($n, 3, 3) . '.' . substr($n, 6, 3) . '-' . substr($n, 9, 2);
    }
}
