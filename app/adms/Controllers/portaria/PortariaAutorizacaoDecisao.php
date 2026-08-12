<?php

declare(strict_types=1);

namespace App\adms\Controllers\portaria;

use App\adms\Models\Repository\PortariaAutorizacoesRepository;

/**
 * Decisão pública do anfitrião (sem login).
 * URL: portaria-autorizacao-decisao/{token} ou .../{token}/autorizar|recusar
 */
final class PortariaAutorizacaoDecisao
{
    public function index(?string $path = null): void
    {
        $path = $path !== null ? trim($path, '/') : '';
        if ($path === '') {
            $this->output([
                'mode' => 'result',
                'heading' => 'Link inválido',
                'message' => 'Este link está incompleto ou expirado.',
                'success' => false,
            ]);
            return;
        }

        $parts = explode('/', $path, 2);
        $token = trim((string) ($parts[0] ?? ''));
        $action = isset($parts[1]) ? strtolower(trim((string) $parts[1])) : null;

        $repo = new PortariaAutorizacoesRepository();
        $row = $repo->findByDecisaoToken($token);
        if ($row === null) {
            $this->output([
                'mode' => 'result',
                'heading' => 'Link inválido',
                'message' => 'Não encontramos esta solicitação. O link pode ter expirado ou já ter sido usado.',
                'success' => false,
            ]);
            return;
        }

        $status = (string) ($row['status'] ?? '');
        $base = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/');
        $t = rawurlencode($token);
        $autorizarUrl = $base . '/portaria-autorizacao-decisao/' . $t . '/autorizar';
        $recusarUrl = $base . '/portaria-autorizacao-decisao/' . $t . '/recusar';

        if ($status !== 'aguardando') {
            $label = match ($status) {
                'autorizada' => 'já foi autorizada',
                'recusada' => 'já foi recusada',
                'cancelada' => 'foi cancelada',
                default => 'não está mais aguardando decisão',
            };
            $this->output([
                'mode' => 'result',
                'heading' => 'Solicitação encerrada',
                'message' => 'Esta visita ' . $label . ' (protocolo '
                    . (string) ($row['protocolo'] ?? '') . ').',
                'success' => $status === 'autorizada',
            ]);
            return;
        }

        if ($action === null || $action === '') {
            $this->output([
                'mode' => 'choice',
                'heading' => 'Autorização de visita',
                'protocolo' => (string) ($row['protocolo'] ?? ''),
                'visitante' => (string) ($row['visitante_nome'] ?? ''),
                'documento' => (string) ($row['visitante_documento'] ?? ''),
                'motivo' => (string) ($row['motivo'] ?? ''),
                'periodo' => trim((string) ($row['data_inicio'] ?? '') . ' a ' . (string) ($row['data_fim'] ?? '')),
                'anfitriao' => (string) ($row['anfitriao_nome'] ?? ''),
                'autorizar_url' => $autorizarUrl,
                'recusar_url' => $recusarUrl,
            ]);
            return;
        }

        if (!in_array($action, ['autorizar', 'recusar'], true)) {
            $this->output([
                'mode' => 'result',
                'heading' => 'Ação inválida',
                'message' => 'Use Autorizar ou Recusar.',
                'success' => false,
            ]);
            return;
        }

        $novoStatus = $action === 'autorizar' ? 'autorizada' : 'recusada';
        $actorId = (int) ($row['anfitriao_user_id'] ?? 0);
        $ok = $repo->updateStatus((int) $row['id'], $novoStatus, $actorId);
        if (!$ok) {
            $this->output([
                'mode' => 'result',
                'heading' => 'Erro',
                'message' => 'Não foi possível registrar a decisão. Tente novamente ou fale com a portaria.',
                'success' => false,
            ]);
            return;
        }

        $repo->registrarContato((int) $row['id'], [
            'canal' => 'link_publico',
            'resultado' => $novoStatus,
            'observacoes' => 'Decisão pelo link público (WhatsApp/e-mail), sem login.',
        ], $actorId);

        $msg = $novoStatus === 'autorizada'
            ? 'Visita autorizada com sucesso. A portaria já pode liberar a entrada.'
            : 'Visita recusada. A portaria foi informada pelo registro no sistema.';

        $this->output([
            'mode' => 'result',
            'heading' => $novoStatus === 'autorizada' ? 'Autorizado' : 'Recusado',
            'message' => $msg . "\nProtocolo: " . (string) ($row['protocolo'] ?? ''),
            'success' => true,
        ]);
    }

    /** @param array<string, mixed> $vars */
    private function output(array $vars): void
    {
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }
        extract($vars, EXTR_OVERWRITE);
        $file = dirname(__DIR__, 2) . '/Views/portaria/autorizacoes/decisao_publica.php';
        if (!is_file($file)) {
            echo '<!DOCTYPE html><html><body><p>Erro ao carregar a página.</p></body></html>';
            exit;
        }
        include $file;
        exit;
    }
}
