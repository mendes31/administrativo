<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\PulseCampaignsRepository;
use App\adms\Models\Repository\PulseQuestionsRepository;
use App\adms\Models\Repository\PulseResponsesRepository;

/**
 * Campanhas Pulse / eNPS — Fase 6 1º incremento.
 */
class PulseCampaignService
{
    public const TYPES = ['enps', 'pulse'];
    public const STATUSES = ['draft', 'open', 'closed'];
    public const QUESTION_TYPES = ['nps', 'likert', 'text'];

    public function __construct(
        private readonly ?PulseCampaignsRepository $campaigns = null,
        private readonly ?PulseQuestionsRepository $questions = null,
        private readonly ?PulseResponsesRepository $responses = null,
    ) {
    }

    /**
     * @param array<string, mixed> $input
     * @return array{ok: bool, error?: string, id?: int}
     */
    public function create(array $input, int $createdBy): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        $type = (string) ($input['campaign_type'] ?? 'enps');
        if ($name === '') {
            return ['ok' => false, 'error' => 'Nome da campanha é obrigatório.'];
        }
        if (!in_array($type, self::TYPES, true)) {
            return ['ok' => false, 'error' => 'Tipo de campanha inválido.'];
        }

        $id = $this->campaigns()->create([
            'name' => $name,
            'campaign_type' => $type,
            'status' => 'draft',
            'starts_at' => $input['starts_at'] ?? null,
            'ends_at' => $input['ends_at'] ?? null,
            'anonymous' => !isset($input['anonymous']) || (string) $input['anonymous'] !== '0',
            'description' => trim((string) ($input['description'] ?? '')) ?: null,
            'created_by' => $createdBy,
        ]);
        if ($id <= 0) {
            return ['ok' => false, 'error' => 'Erro ao criar campanha.'];
        }

        if ($type === 'enps') {
            $this->questions()->create([
                'campaign_id' => $id,
                'question_text' => 'Em uma escala de 0 a 10, o quanto você recomendaria a empresa como lugar para trabalhar?',
                'question_type' => 'nps',
                'sort_order' => 1,
            ]);
        }

        return ['ok' => true, 'id' => $id];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{ok: bool, error?: string, id?: int}
     */
    public function update(int $id, array $input): array
    {
        $current = $this->campaigns()->getById($id);
        if (!$current) {
            return ['ok' => false, 'error' => 'Campanha não encontrada.'];
        }
        $name = trim((string) ($input['name'] ?? $current['name']));
        $status = (string) ($input['status'] ?? $current['status']);
        if ($name === '') {
            return ['ok' => false, 'error' => 'Nome da campanha é obrigatório.'];
        }
        if (!in_array($status, self::STATUSES, true)) {
            return ['ok' => false, 'error' => 'Status inválido.'];
        }
        if (($current['status'] ?? '') === 'closed' && $status !== 'closed') {
            return ['ok' => false, 'error' => 'Campanha fechada não reabre neste incremento.'];
        }

        if (!$this->campaigns()->update($id, [
            'name' => $name,
            'status' => $status,
            'starts_at' => $input['starts_at'] ?? $current['starts_at'],
            'ends_at' => $input['ends_at'] ?? $current['ends_at'],
            'anonymous' => isset($input['anonymous'])
                ? ((string) $input['anonymous'] !== '0')
                : (bool) $current['anonymous'],
            'description' => array_key_exists('description', $input)
                ? (trim((string) $input['description']) ?: null)
                : $current['description'],
        ])) {
            return ['ok' => false, 'error' => 'Erro ao atualizar campanha.'];
        }

        return ['ok' => true, 'id' => $id];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{ok: bool, error?: string, id?: int}
     */
    public function addQuestion(int $campaignId, array $input): array
    {
        $campaign = $this->campaigns()->getById($campaignId);
        if (!$campaign) {
            return ['ok' => false, 'error' => 'Campanha não encontrada.'];
        }
        if (($campaign['status'] ?? '') === 'closed') {
            return ['ok' => false, 'error' => 'Campanha fechada.'];
        }
        $text = trim((string) ($input['question_text'] ?? ''));
        $qType = (string) ($input['question_type'] ?? 'likert');
        if ($text === '') {
            return ['ok' => false, 'error' => 'Texto da pergunta é obrigatório.'];
        }
        if (!in_array($qType, self::QUESTION_TYPES, true)) {
            return ['ok' => false, 'error' => 'Tipo de pergunta inválido.'];
        }
        $order = count($this->questions()->listByCampaign($campaignId)) + 1;
        $qid = $this->questions()->create([
            'campaign_id' => $campaignId,
            'question_text' => $text,
            'question_type' => $qType,
            'sort_order' => $order,
        ]);

        return $qid > 0 ? ['ok' => true, 'id' => $qid] : ['ok' => false, 'error' => 'Erro ao adicionar pergunta.'];
    }

    /**
     * @param array<string, mixed> $answers question_id => ['score'=>?, 'comment'=>?]
     * @return array{ok: bool, error?: string, saved?: int}
     */
    public function submitAnswers(int $campaignId, int $userId, array $answers): array
    {
        $campaign = $this->campaigns()->getById($campaignId);
        if (!$campaign) {
            return ['ok' => false, 'error' => 'Campanha não encontrada.'];
        }
        if (($campaign['status'] ?? '') !== 'open') {
            return ['ok' => false, 'error' => 'Campanha não está aberta para respostas.'];
        }
        $questions = $this->questions()->listByCampaign($campaignId);
        if ($questions === []) {
            return ['ok' => false, 'error' => 'Campanha sem perguntas.'];
        }

        $anonymous = (bool) ($campaign['anonymous'] ?? true);
        $saved = 0;
        foreach ($questions as $q) {
            $qid = (int) $q['id'];
            $payload = $answers[$qid] ?? $answers[(string) $qid] ?? null;
            if (!is_array($payload)) {
                continue;
            }
            if (!$anonymous && $this->responses()->hasUserAnsweredQuestion($qid, $userId)) {
                continue;
            }
            $score = null;
            if (($q['question_type'] ?? '') !== 'text') {
                if (!isset($payload['score']) || $payload['score'] === '') {
                    return ['ok' => false, 'error' => 'Informe a nota da pergunta: ' . $q['question_text']];
                }
                $score = (int) $payload['score'];
                $max = ($q['question_type'] ?? '') === 'likert' ? 5 : 10;
                if ($score < 0 || $score > $max) {
                    return ['ok' => false, 'error' => "Nota inválida (0–{$max})."];
                }
            }
            $this->responses()->create([
                'campaign_id' => $campaignId,
                'question_id' => $qid,
                'user_id' => $anonymous ? null : $userId,
                'score' => $score,
                'comment_text' => trim((string) ($payload['comment'] ?? '')) ?: null,
                'answered_at' => date('Y-m-d H:i:s'),
            ]);
            $saved++;
        }

        if ($saved === 0) {
            return ['ok' => false, 'error' => 'Nenhuma resposta nova para salvar (já respondeu?).'];
        }

        return ['ok' => true, 'saved' => $saved];
    }

    /**
     * @return array{total:int,promoters:int,passives:int,detractors:int,enps:float|null}
     */
    public function computeEnps(int $campaignId): array
    {
        $scores = $this->responses()->listScoresByCampaign($campaignId);
        $total = count($scores);
        $promoters = 0;
        $passives = 0;
        $detractors = 0;
        foreach ($scores as $s) {
            if ($s >= 9) {
                $promoters++;
            } elseif ($s >= 7) {
                $passives++;
            } else {
                $detractors++;
            }
        }
        $enps = $total > 0 ? round((($promoters - $detractors) / $total) * 100, 1) : null;

        return compact('total', 'promoters', 'passives', 'detractors', 'enps');
    }

    private function campaigns(): PulseCampaignsRepository
    {
        return $this->campaigns ?? new PulseCampaignsRepository();
    }

    private function questions(): PulseQuestionsRepository
    {
        return $this->questions ?? new PulseQuestionsRepository();
    }

    private function responses(): PulseResponsesRepository
    {
        return $this->responses ?? new PulseResponsesRepository();
    }
}
