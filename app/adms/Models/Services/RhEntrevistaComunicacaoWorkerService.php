<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\AppEnvironmentHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Helpers\SendEmailService;
use App\adms\Models\Repository\AdmsEmailConfigRepository;
use App\adms\Models\Repository\RhEntrevistaComunicacoesRepository;

/**
 * Worker SMTP das comunicações de entrevista.
 *
 * Segurança:
 * - dry-run por padrão;
 * - envio exige o interruptor da tela Configuração de E-mail e --send no script;
 * - fora de produção redireciona para test_recipient da configuração;
 * - falhas ficam em failed e não são reenviadas automaticamente;
 * - sucesso SMTP sem confirmação no banco fica em processing para evitar duplicidade.
 */
final class RhEntrevistaComunicacaoWorkerService
{
    /**
     * @return array{
     *   dry_run: bool,
     *   non_production: bool,
     *   config_errors: list<string>,
     *   totals: array{scanned:int, eligible:int, sent:int, failed:int, skipped:int, uncertain:int},
     *   items: list<array{id:int, entrevista_id:int, decision:string, detail:string}>
     * }
     */
    public function run(int $limit = 20, bool $send = false): array
    {
        $limit = max(1, min(100, $limit));
        $config = (new AdmsEmailConfigRepository())->getConfig();
        $nonProduction = AppEnvironmentHelper::isNonProduction();
        $configErrors = $this->validateConfig($config, $nonProduction);

        if ($send && !self::isSendEnabled()) {
            throw new \RuntimeException(
                'Envio bloqueado: ative "Envio automático de comunicações de entrevista" '
                . 'na tela Configuração de E-mail.'
            );
        }
        if ($send && $configErrors !== []) {
            throw new \RuntimeException(
                'Configuração SMTP inválida: ' . implode('; ', $configErrors)
            );
        }

        $repo = new RhEntrevistaComunicacoesRepository();
        $rows = $repo->listReadyForWorker($limit);
        $totals = [
            'scanned' => 0,
            'eligible' => 0,
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
            'uncertain' => 0,
        ];
        $items = [];

        foreach ($rows as $row) {
            $totals['scanned']++;
            $id = (int) ($row['id'] ?? 0);
            $entrevistaId = (int) ($row['rh_entrevista_id'] ?? 0);
            $outboxId = (int) ($row['outbox_event_id'] ?? 0);
            $rowErrors = $this->validateRow($row);

            if ($rowErrors !== []) {
                $totals['skipped']++;
                $items[] = [
                    'id' => $id,
                    'entrevista_id' => $entrevistaId,
                    'decision' => 'skipped',
                    'detail' => implode('; ', $rowErrors),
                ];
                continue;
            }

            $totals['eligible']++;
            if (!$send) {
                $items[] = [
                    'id' => $id,
                    'entrevista_id' => $entrevistaId,
                    'decision' => $configErrors === [] ? 'would_send' : 'config_blocked',
                    'detail' => $configErrors === []
                        ? ($nonProduction ? 'destinatário de teste configurado' : 'destinatário original')
                        : implode('; ', $configErrors),
                ];
                continue;
            }

            if (!$repo->claimForProcessing($id, $outboxId)) {
                $totals['skipped']++;
                $items[] = [
                    'id' => $id,
                    'entrevista_id' => $entrevistaId,
                    'decision' => 'claim_skipped',
                    'detail' => 'Outro worker ou processo alterou o registro.',
                ];
                continue;
            }

            $emailAccepted = false;
            try {
                $recipient = $nonProduction
                    ? trim((string) ($config['test_recipient'] ?? ''))
                    : trim((string) $row['recipient_address']);
                $recipientName = $nonProduction
                    ? 'Teste de comunicação de entrevista'
                    : trim((string) ($row['recipient_name'] ?? 'Candidato'));

                $emailAccepted = SendEmailService::sendEmail(
                    $recipient,
                    $recipientName,
                    (string) $row['subject_snapshot'],
                    (string) $row['body_html_snapshot'],
                    (string) ($row['body_text_snapshot'] ?? strip_tags((string) $row['body_html_snapshot']))
                );

                if (!$emailAccepted) {
                    $error = 'SMTP recusou/falhou; consulte o log técnico do SendEmailService.';
                    $repo->markFailed($id, $outboxId, $error);
                    $totals['failed']++;
                    $items[] = [
                        'id' => $id,
                        'entrevista_id' => $entrevistaId,
                        'decision' => 'failed',
                        'detail' => $error,
                    ];
                    continue;
                }

                $repo->markSent($id, $outboxId);
                $totals['sent']++;
                $items[] = [
                    'id' => $id,
                    'entrevista_id' => $entrevistaId,
                    'decision' => 'sent',
                    'detail' => $nonProduction ? 'enviado ao destinatário de teste' : 'enviado',
                ];
            } catch (\Throwable $e) {
                $error = mb_substr($e->getMessage(), 0, 2000);
                if (!$emailAccepted) {
                    try {
                        $repo->markFailed($id, $outboxId, $error);
                    } catch (\Throwable $markError) {
                        GenerateLog::generateLog('error', 'Falha ao persistir erro do worker de entrevista.', [
                            'comunicacao_id' => $id,
                            'error' => $markError->getMessage(),
                        ]);
                    }
                    $totals['failed']++;
                    $decision = 'failed';
                } else {
                    // O SMTP aceitou a mensagem. Não liberar retry automático:
                    // repetir poderia enviar duas vezes.
                    $totals['uncertain']++;
                    $decision = 'uncertain';
                }

                GenerateLog::generateLog('error', 'Worker de comunicação de entrevista falhou.', [
                    'comunicacao_id' => $id,
                    'outbox_event_id' => $outboxId,
                    'email_accepted' => $emailAccepted,
                    'error' => $error,
                ]);
                $items[] = [
                    'id' => $id,
                    'entrevista_id' => $entrevistaId,
                    'decision' => $decision,
                    'detail' => $error,
                ];
            }
        }

        return [
            'dry_run' => !$send,
            'non_production' => $nonProduction,
            'config_errors' => $configErrors,
            'totals' => $totals,
            'items' => $items,
        ];
    }

    /**
     * Interruptor específico das comunicações de entrevista, controlado na tela
     * Configuração de E-mail (coluna rh_entrevista_send_enabled). Não afeta os
     * demais e-mails do sistema. Sem coluna/configuração, permanece desligado.
     */
    public static function isSendEnabled(): bool
    {
        try {
            return (new AdmsEmailConfigRepository())->isRhEntrevistaSendEnabled();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param array<string, mixed> $config
     * @return list<string>
     */
    private function validateConfig(array $config, bool $nonProduction): array
    {
        $errors = [];
        foreach (['host', 'username', 'password', 'from_email'] as $field) {
            if (trim((string) ($config[$field] ?? '')) === '') {
                $errors[] = "configuração {$field} ausente";
            }
        }
        if (filter_var((string) ($config['from_email'] ?? ''), FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'from_email inválido';
        }
        if ((int) ($config['port'] ?? 0) <= 0) {
            $errors[] = 'porta SMTP inválida';
        }
        if ($nonProduction
            && filter_var((string) ($config['test_recipient'] ?? ''), FILTER_VALIDATE_EMAIL) === false
        ) {
            $errors[] = 'test_recipient válido é obrigatório fora de produção';
        }

        return array_values(array_unique($errors));
    }

    /**
     * @param array<string, mixed> $row
     * @return list<string>
     */
    private function validateRow(array $row): array
    {
        $errors = [];
        if ((int) ($row['outbox_event_id'] ?? 0) <= 0 || ($row['outbox_status'] ?? '') !== 'pending') {
            $errors[] = 'outbox inconsistente';
        }
        if (filter_var((string) ($row['recipient_address'] ?? ''), FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'destinatário inválido';
        }
        if (trim((string) ($row['subject_snapshot'] ?? '')) === '') {
            $errors[] = 'assunto vazio';
        }
        if (trim((string) ($row['body_html_snapshot'] ?? '')) === '') {
            $errors[] = 'corpo HTML vazio';
        }
        if ((int) ($row['template_version'] ?? 0) < 2) {
            $errors[] = 'template legado não habilitado para envio';
        }
        $body = (string) ($row['body_html_snapshot'] ?? '') . ' '
            . (string) ($row['body_text_snapshot'] ?? '');
        if (stripos($body, 'envio automático ainda não habilitado') !== false
            || stripos($body, 'não é enviada automaticamente') !== false
        ) {
            $errors[] = 'snapshot contém aviso de envio desabilitado';
        }

        return $errors;
    }
}
