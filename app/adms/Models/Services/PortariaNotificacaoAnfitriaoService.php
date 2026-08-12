<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\GenerateLog;
use App\adms\Helpers\SendWhatsAppService;
use App\adms\Models\Repository\PortariaAutorizacoesRepository;
use App\adms\Models\Repository\UsersRepository;
use Throwable;

/**
 * Notifica o anfitrião sobre solicitação de autorização de visita (push e/ou WhatsApp).
 */
final class PortariaNotificacaoAnfitriaoService
{
    /**
     * @param list<string> $canais push, whatsapp
     * @return array{push:bool,whatsapp:bool,mensagens:list<string>}
     */
    public function notificarSolicitacao(int $autorizacaoId, int $porteiroUserId = 0, array $canais = ['push', 'whatsapp']): array
    {
        $out = ['push' => false, 'whatsapp' => false, 'mensagens' => []];
        $canais = array_values(array_intersect($canais, ['push', 'whatsapp']));
        if ($canais === []) {
            $canais = ['push', 'whatsapp'];
        }
        $repo = new PortariaAutorizacoesRepository();
        $aut = $repo->getById($autorizacaoId);
        if ($aut === null) {
            $out['mensagens'][] = 'Autorização não encontrada.';
            return $out;
        }

        $anfitriaoId = (int) ($aut['anfitriao_user_id'] ?? 0);
        if ($anfitriaoId <= 0) {
            $out['mensagens'][] = 'Autorização sem anfitrião.';
            return $out;
        }

        $visitante = (string) ($aut['visitante_nome'] ?? 'Visitante');
        $protocolo = (string) ($aut['protocolo'] ?? '');
        $motivo = trim((string) ($aut['motivo'] ?? ''));
        $links = $repo->linksDecisaoPublica($autorizacaoId);
        $urlEscolha = $links['escolha']
            ?? (rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/portaria-autorizacoes-view/' . $autorizacaoId);
        $urlAutorizar = $links['autorizar'] ?? $urlEscolha;
        $urlRecusar = $links['recusar'] ?? $urlEscolha;

        $titulo = 'Portaria: autorização pendente';
        $corpo = $visitante . ' aguarda sua autorização'
            . ($protocolo !== '' ? ' (' . $protocolo . ')' : '')
            . ($motivo !== '' ? '. Motivo: ' . $motivo : '.')
            . ' Use os links para autorizar ou recusar (sem login).';

        if (in_array('push', $canais, true)) {
            try {
                $push = (new PushNotificationService())->sendToUser($anfitriaoId, $titulo, $corpo, $urlEscolha);
                $out['push'] = !empty($push['success']);
                $repo->registrarContato($autorizacaoId, [
                    'canal' => 'push',
                    'resultado' => $out['push'] ? 'enviado' : 'falha',
                    'observacoes' => $out['push']
                        ? 'Notificação push enviada com link de decisão.'
                        : ('Push não enviado: ' . implode('; ', $push['errors'] ?? ['sem inscrição'])),
                ], $porteiroUserId);
                $out['mensagens'][] = $out['push'] ? 'Push enviado ao anfitrião.' : 'Push não disponível para o anfitrião.';
            } catch (Throwable $e) {
                GenerateLog::generateLog('error', 'PortariaNotificacaoAnfitriaoService::push', ['error' => $e->getMessage()]);
                $out['mensagens'][] = 'Falha ao enviar push.';
                $repo->registrarContato($autorizacaoId, [
                    'canal' => 'push',
                    'resultado' => 'falha',
                    'observacoes' => $e->getMessage(),
                ], $porteiroUserId);
            }
        }

        if (in_array('whatsapp', $canais, true)) {
            try {
                $user = (new UsersRepository())->getUser($anfitriaoId);
                $celular = is_array($user) ? trim((string) ($user['celular'] ?? '')) : '';
                if ($celular === '') {
                    $out['mensagens'][] = 'Anfitrião sem celular cadastrado para WhatsApp.';
                    $repo->registrarContato($autorizacaoId, [
                        'canal' => 'whatsapp',
                        'resultado' => 'falha',
                        'observacoes' => 'Celular não cadastrado no usuário.',
                    ], $porteiroUserId);
                } else {
                    $waMsg = "*{$titulo}*\n\n{$corpo}\n\n"
                        . "✅ *Autorizar:*\n{$urlAutorizar}\n\n"
                        . "❌ *Recusar:*\n{$urlRecusar}";
                    $wa = SendWhatsAppService::sendMessage($celular, $waMsg);
                    $out['whatsapp'] = !empty($wa['success']);
                    $repo->registrarContato($autorizacaoId, [
                        'canal' => 'whatsapp',
                        'resultado' => $out['whatsapp'] ? 'enviado' : 'falha',
                        'observacoes' => $out['whatsapp']
                            ? 'WhatsApp enviado com links Autorizar/Recusar.'
                            : ('WhatsApp: ' . (string) ($wa['message'] ?? $wa['error'] ?? 'falha')),
                    ], $porteiroUserId);
                    $out['mensagens'][] = $out['whatsapp'] ? 'WhatsApp enviado ao anfitrião.' : 'WhatsApp não enviado.';
                }
            } catch (Throwable $e) {
                GenerateLog::generateLog('error', 'PortariaNotificacaoAnfitriaoService::whatsapp', ['error' => $e->getMessage()]);
                $out['mensagens'][] = 'Falha ao enviar WhatsApp.';
                $repo->registrarContato($autorizacaoId, [
                    'canal' => 'whatsapp',
                    'resultado' => 'falha',
                    'observacoes' => $e->getMessage(),
                ], $porteiroUserId);
            }
        }

        return $out;
    }
}
