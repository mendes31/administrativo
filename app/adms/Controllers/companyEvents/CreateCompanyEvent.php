<?php

namespace App\adms\Controllers\companyEvents;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CompanyEventMediaService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\TextEncodingHelper;
use App\adms\Models\Repository\CompanyEventsRepository;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Views\Services\LoadViewService;

class CreateCompanyEvent
{
    private CompanyEventMediaService $mediaService;

    private array $data = [];

    public function __construct()
    {
        $this->mediaService = new CompanyEventMediaService();
    }

    public function index(): void
    {
        $deptRepo = new DepartmentsRepository();
        $this->data['departments'] = $deptRepo->getAllDepartmentsSelect();

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $this->create();
        }

        $pageElements = [
            'title_head' => 'Novo evento',
            'menu' => 'create-company-event',
            'buttonPermission' => [],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/companyEvents/create', $this->data);
        $loadView->loadView();
    }

    private function create(): void
    {
        if (!CSRFHelper::validateCSRFToken('company_event_create', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = $this->mediaService->alertHtml('danger', 'Token CSRF inválido.');
            return;
        }

        $title = trim(TextEncodingHelper::decodeEntities((string)($_POST['title'] ?? '')));
        $starts = $this->normalizeDatetimeLocal(trim((string)($_POST['starts_at'] ?? '')));
        $ends = $this->normalizeDatetimeLocal(trim((string)($_POST['ends_at'] ?? '')));
        if ($title === '' || $starts === null || $ends === null) {
            $_SESSION['msg'] = $this->mediaService->alertHtml('danger', 'Título e datas são obrigatórios.');
            return;
        }

        $data = [
            'title' => $title,
            'description' => trim(TextEncodingHelper::decodeEntities((string)($_POST['description'] ?? ''))) ?: null,
            'location' => trim(TextEncodingHelper::decodeEntities((string)($_POST['location'] ?? ''))) ?: null,
            'starts_at' => $starts,
            'ends_at' => $ends,
            'publish_at' => $this->normalizeDatetimeLocal(trim((string)($_POST['publish_at'] ?? ''))),
            'expire_at' => $this->normalizeDatetimeLocal(trim((string)($_POST['expire_at'] ?? ''))),
            'rsvp_deadline' => $this->normalizeDatetimeLocal(trim((string)($_POST['rsvp_deadline'] ?? ''))),
            'cancellation_deadline' => $this->normalizeDatetimeLocal(trim((string)($_POST['cancellation_deadline'] ?? ''))),
            'requires_rsvp' => isset($_POST['requires_rsvp']),
            'allows_guests' => isset($_POST['allows_guests']),
            'max_guests_per_user' => (int)($_POST['max_guests_per_user'] ?? 0),
            'created_by' => (int)$_SESSION['user_id'],
            'department_id' => (int)($_POST['department_id'] ?? 0) ?: null,
            'ativo' => isset($_POST['ativo']),
            'anexo' => null,
        ];

        if (strtotime($data['starts_at']) >= strtotime($data['ends_at'])) {
            $_SESSION['msg'] = $this->mediaService->alertHtml('danger', 'Data/hora de término deve ser após o início.');
            return;
        }

        $repo = new CompanyEventsRepository();
        $id = $repo->createEvent($data);
        if ($id <= 0) {
            $_SESSION['msg'] = $this->mediaService->alertHtml('danger', 'Erro ao criar evento.');
            return;
        }

        if (!$this->mediaService->persistMediaAfterCreate($id, $repo)) {
            return;
        }

        $_SESSION['msg'] = $this->mediaService->alertHtml('success', 'Evento criado.');
        header('Location: ' . $_ENV['URL_ADM'] . 'list-company-events');
        exit;
    }

    private function normalizeDatetimeLocal(string $raw): ?string
    {
        $s = str_replace('T', ' ', trim($raw));
        if ($s === '') {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $s)) {
            $s .= ':00';
        }

        return $s;
    }
}
