<?php

declare(strict_types=1);

namespace App\adms\Controllers\portal;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\LgpdTermosRepository;
use App\adms\Models\Repository\RhCandidatosRepository;
use App\adms\Models\Repository\RhVagasRepository;
use App\adms\Models\Services\RhCandidaturaInternaService;
use App\adms\Views\Services\LoadViewService;

/**
 * Portal autenticado: vagas internas/ambas + candidatura do colaborador.
 */
class VagasInternas
{
    private const CSRF_FORM = 'form_vagas_internas_candidatar';

    private array|string|null $data = null;

    public function index(int|string $id = 0): void
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            $_SESSION['error'] = 'Faça login para ver vagas internas.';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'login');
            return;
        }

        $idInt = (int) $id;
        if ($idInt > 0 && strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
            $this->candidatar($idInt, $userId);
            return;
        }

        if ($idInt > 0) {
            $this->show($idInt, $userId);
            return;
        }

        $this->list();
    }

    private function list(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $q = trim((string) ($_GET['q'] ?? ''));
        $result = (new RhVagasRepository())->listInternas(
            $q !== '' ? ['titulo' => $q] : [],
            $page,
            12
        );

        $this->data = [
            'vagas' => $result['data'],
            'total' => $result['total'],
            'page' => $page,
            'per_page' => 12,
            'q' => $q,
        ];

        $this->render('list', 'Vagas internas');
    }

    private function show(int $id, int $userId, array $form = []): void
    {
        $vaga = (new RhVagasRepository())->getInternaById($id);
        if ($vaga === null) {
            $_SESSION['error'] = 'Vaga não encontrada ou indisponível para candidatura interna.';
            header('Location: ' . rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/vagas-internas');
            return;
        }

        $jaCandidatou = false;
        $candRepo = new RhCandidatosRepository();
        $existing = $candRepo->findActiveByAdmsUserId($userId);
        if ($existing === null) {
            $userEmail = strtolower(trim((string) (($_SESSION['user_email'] ?? '') ?: '')));
            if ($userEmail === '') {
                $user = (new \App\adms\Models\Repository\UsersRepository())->getUser($userId);
                $userEmail = is_array($user) ? strtolower(trim((string) ($user['email'] ?? ''))) : '';
            }
            if ($userEmail !== '') {
                $existing = $candRepo->findActiveByEmail($userEmail);
            }
        }
        if ($existing !== null) {
            $jaCandidatou = $candRepo->hasVinculoComVaga((int) $existing['id'], $id);
        }

        $termo = (new LgpdTermosRepository())->getTermoAtivoPorTipo(
            RhCandidaturaInternaService::LGPD_TERMO_TIPO
        );

        $this->data = [
            'vaga' => $vaga,
            'form' => $form,
            'ja_candidatou' => $jaCandidatou,
            'lgpd_termo' => $termo,
            'csrf_token' => CSRFHelper::generateCSRFToken(self::CSRF_FORM),
        ];

        $this->render('view', (string) ($vaga['titulo'] ?? 'Vaga interna'));
    }

    private function candidatar(int $vagaId, int $userId): void
    {
        $csrf = (string) ($_POST['csrf_token'] ?? '');
        if (!CSRFHelper::validateCSRFToken(self::CSRF_FORM, $csrf)) {
            $_SESSION['error'] = 'Token de segurança inválido ou expirado. Recarregue a página.';
            header('Location: ' . rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/vagas-internas/' . $vagaId);
            return;
        }

        $form = [
            'mensagem' => (string) ($_POST['mensagem'] ?? ''),
            'lgpd_consent' => (string) ($_POST['lgpd_consent'] ?? ''),
        ];

        try {
            (new RhCandidaturaInternaService())->candidatar($vagaId, $userId, $form);
            $_SESSION['success'] = 'Candidatura registrada. O RH acompanhará o processo.';
            header('Location: ' . rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/vagas-internas/' . $vagaId);
        } catch (\Throwable $e) {
            GenerateLog::generateLog('warning', 'Candidatura interna rejeitada/falhou.', [
                'vaga_id' => $vagaId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            $_SESSION['error'] = $e->getMessage();
            $this->show($vagaId, $userId, $form);
        }
    }

    private function render(string $view, string $title): void
    {
        $pageElements = [
            'title_head' => $title,
            'menu' => 'vagas-internas',
            'buttonPermission' => ['VagasInternas', 'EmployeePortal'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/portal/vagas_internas/' . $view, $this->data);
        $loadView->loadView();
    }
}
