<?php

declare(strict_types=1);

namespace App\adms\Controllers\rh;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\RhOfertasRepository;
use App\adms\Models\Services\RhOfertaService;
use App\adms\Models\Services\RhPermissionService;
use App\adms\Views\Services\LoadViewService;
use Exception;

final class RhOfertasView
{
    private array $data = [];

    public function index(int|string $id): void
    {
        $ofertaId = (int) $id;
        if ($ofertaId <= 0) {
            $_SESSION['msg'] = 'Oferta não encontrada.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'rh-candidatos');
            exit;
        }

        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
            $this->handleAction($ofertaId);
            return;
        }

        $repo = new RhOfertasRepository();
        $oferta = $repo->getById($ofertaId);
        if ($oferta === null) {
            $_SESSION['msg'] = 'Oferta não encontrada.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'rh-candidatos');
            exit;
        }

        $canManage = RhPermissionService::canManagePipelineByVagaId((int) $oferta['rh_vaga_id']);
        $conversao = null;
        $onboardingPlano = null;
        $experiencia = null;
        if (!empty($oferta['rh_conversao_id'])) {
            $conversaoId = (int) $oferta['rh_conversao_id'];
            $conversao = (new \App\adms\Models\Repository\RhConversoesAdmissaoRepository())
                ->getByOfertaId($ofertaId);
            $onboardingPlano = (new \App\adms\Models\Repository\RhOnboardingRepository())
                ->getPlanoByConversaoId($conversaoId);
            $experiencia = (new \App\adms\Models\Repository\RhPeriodosExperienciaRepository())
                ->getByConversaoId($conversaoId);
        }

        $this->data = [
            'title_head' => 'Oferta #' . $ofertaId,
            'menu' => 'rh-candidatos',
            'buttonPermission' => ['RhOfertasView', 'RhOfertasConvert', 'RhPreAdmissaoDownloadDoc', 'RhOnboardingView', 'RhExperienciaView', 'RhCandidatos', 'RhVagas'],
            'csrf_token' => CSRFHelper::generateCSRFToken('form_rh_oferta_action'),
            'oferta' => $oferta,
            'documentos' => $repo->listDocumentos($ofertaId),
            'can_manage' => $canManage,
            'docs_request_url' => !empty($oferta['docs_request_token'])
                ? rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/pre-admissao-documentos?token=' . rawurlencode((string) $oferta['docs_request_token'])
                : null,
            'conversao' => $conversao,
            'onboarding_plano' => $onboardingPlano,
            'experiencia' => $experiencia,
        ];

        $pageLayout = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayout->configurePageElements($this->data));
        (new LoadViewService('adms/Views/rh/ofertas/view', $this->data))->loadView();
    }

    private function handleAction(int $ofertaId): void
    {
        if (!CSRFHelper::validateCSRFToken('form_rh_oferta_action', (string) ($_POST['csrf_token'] ?? ''))) {
            $_SESSION['msg'] = 'Token inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'rh-ofertas-view/' . $ofertaId);
            exit;
        }

        $action = (string) ($_POST['action'] ?? '');
        $obs = trim((string) ($_POST['observacoes'] ?? ''));
        $service = new RhOfertaService();
        $userId = (int) ($_SESSION['user_id'] ?? 0);

        try {
            match ($action) {
                'enviar' => $service->enviar($ofertaId),
                'aceitar' => $service->aceitar($ofertaId, $obs !== '' ? $obs : null),
                'recusar' => $service->recusar($ofertaId, $obs !== '' ? $obs : null),
                'cancelar' => $service->cancelar($ofertaId, $obs !== '' ? $obs : null),
                'solicitar_documentos' => (function () use ($service, $ofertaId): void {
                    $dias = (int) ($_POST['docs_valid_days'] ?? 14);
                    $result = $service->solicitarDocumentos($ofertaId, $dias);
                    $_SESSION['msg'] = 'Link gerado (válido por ' . (int) $result['valid_days']
                        . ' dia(s), até ' . $result['expires_at'] . '). Envie ao candidato.';
                    $_SESSION['msg_type'] = 'success';
                })(),
                'upload_documento' => $service->uploadDocumentoRh(
                    $ofertaId,
                    (int) ($_POST['documento_id'] ?? 0),
                    is_array($_FILES['arquivo'] ?? null) ? $_FILES['arquivo'] : [],
                    $userId
                ),
                'remover_documento' => $service->removerDocumentoArquivo(
                    $ofertaId,
                    (int) ($_POST['documento_id'] ?? 0)
                ),
                'documento' => $service->atualizarDocumento(
                    $ofertaId,
                    (int) ($_POST['documento_id'] ?? 0),
                    (string) ($_POST['documento_status'] ?? ''),
                    $obs !== '' ? $obs : null,
                    $userId
                ),
                default => throw new Exception('Ação inválida.'),
            };
            if ($action !== 'solicitar_documentos') {
                $_SESSION['msg'] = 'Operação realizada com sucesso.';
                $_SESSION['msg_type'] = 'success';
            }
        } catch (Exception $e) {
            $_SESSION['msg'] = $e->getMessage();
            $_SESSION['msg_type'] = 'danger';
        }

        header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'rh-ofertas-view/' . $ofertaId);
        exit;
    }
}
