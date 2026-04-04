<?php

namespace App\adms\Controllers\logs;

use App\adms\Models\Repository\AdmsPasswordPolicyRepository;
use App\adms\Models\Repository\AdmsSessionsRepository;
use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Views\Services\LoadViewService;

class ListConnectedUsers
{
    private array|string|null $data = null;

    public function index(): void
    {
        $idle = $this->resolveOnlineIdleWindow();
        $repo = new AdmsSessionsRepository();
        $this->data['sessions'] = $repo->listActiveSessionsWithUsers($idle['seconds']);
        $this->data['total'] = count($this->data['sessions']);
        $this->data['current_user_id'] = (int)($_SESSION['user_id'] ?? 0);
        $this->data['idle_seconds'] = $idle['seconds'];
        $this->data['idle_description'] = $idle['description'];
        $this->data['consulted_at'] = date('d/m/Y H:i:s');

        $pageElements = [
            'title_head' => 'Usuários conectados',
            'menu' => 'list-connected-users',
            'buttonPermission' => [],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/logs/listConnectedUsers', $this->data);
        $loadView->loadView();
    }

    /**
     * Janela de “ainda online”: alinhada à política quando expiração por tempo está ativa;
     * caso contrário, usa session.gc_maxlifetime (como o PHP trata sessão) ou 30 min.
     *
     * @return array{seconds: int, description: string}
     */
    private function resolveOnlineIdleWindow(): array
    {
        $policyRepo = new AdmsPasswordPolicyRepository();
        $policy = $policyRepo->getPolicy();
        if ($policy && ($policy->expirar_sessao_por_tempo ?? '') === 'Sim') {
            $minutes = max(1, (int)$policy->tempo_expiracao_sessao);
            $seconds = max(60, $minutes * 60);
            return [
                'seconds' => $seconds,
                'description' => 'última atividade nos últimos ' . $minutes . ' min (política de expiração de sessão)',
            ];
        }
        $gc = (int)ini_get('session.gc_maxlifetime');
        if ($gc > 0) {
            $seconds = max(60, $gc);
            $min = (int)round($seconds / 60);
            return [
                'seconds' => $seconds,
                'description' => 'última atividade nos últimos ' . $min . ' min (tempo de vida da sessão no servidor)',
            ];
        }
        return [
            'seconds' => 1800,
            'description' => 'última atividade nos últimos 30 min (padrão)',
        ];
    }
}
