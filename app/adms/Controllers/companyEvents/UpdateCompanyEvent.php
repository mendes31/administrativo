<?php

namespace App\adms\Controllers\companyEvents;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CompanyEventMediaService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\TextEncodingHelper;
use App\adms\Models\Repository\CompanyEventsRepository;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Views\Services\LoadViewService;

class UpdateCompanyEvent
{
    private CompanyEventMediaService $mediaService;

    private array $data = [];

    public function __construct()
    {
        $this->mediaService = new CompanyEventMediaService();
    }

    public function index(string|null $id = null): void
    {
        $eventId = (int)($id ?? 0);
        if ($eventId <= 0) {
            header('Location: ' . $_ENV['URL_ADM'] . 'list-company-events');
            exit;
        }
        $repo = new CompanyEventsRepository();
        $event = $repo->getById($eventId);
        if (!$event) {
            $_SESSION['msg'] = $this->mediaService->alertHtml('danger', 'Evento não encontrado.');
            header('Location: ' . $_ENV['URL_ADM'] . 'list-company-events');
            exit;
        }
        $this->data['event'] = $event;
        $this->data['event_images'] = $repo->getImagesForEvent($eventId);

        $deptRepo = new DepartmentsRepository();
        $this->data['departments'] = $deptRepo->getAllDepartmentsSelect();

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $this->update($eventId, $repo, $event);
        }

        $pageElements = [
            'title_head' => 'Editar evento',
            'menu' => 'update-company-event',
            'buttonPermission' => [],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/companyEvents/edit', $this->data);
        $loadView->loadView();
    }

    /**
     * @param array<string, mixed> $existing
     */
    private function update(int $eventId, CompanyEventsRepository $repo, array $existing): void
    {
        if (!CSRFHelper::validateCSRFToken('company_event_update', $_POST['csrf_token'] ?? '')) {
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
            'anexo' => $existing['anexo'] ?? null,
            'starts_at' => $starts,
            'ends_at' => $ends,
            'publish_at' => $this->normalizeDatetimeLocal(trim((string)($_POST['publish_at'] ?? ''))),
            'expire_at' => $this->normalizeDatetimeLocal(trim((string)($_POST['expire_at'] ?? ''))),
            'rsvp_deadline' => $this->normalizeDatetimeLocal(trim((string)($_POST['rsvp_deadline'] ?? ''))),
            'cancellation_deadline' => $this->normalizeDatetimeLocal(trim((string)($_POST['cancellation_deadline'] ?? ''))),
            'requires_rsvp' => isset($_POST['requires_rsvp']),
            'allows_guests' => isset($_POST['allows_guests']),
            'max_guests_per_user' => (int)($_POST['max_guests_per_user'] ?? 0),
            'department_id' => (int)($_POST['department_id'] ?? 0) ?: null,
            'ativo' => isset($_POST['ativo']),
        ];

        if (strtotime($data['starts_at']) >= strtotime($data['ends_at'])) {
            $_SESSION['msg'] = $this->mediaService->alertHtml('danger', 'Data/hora de término deve ser após o início.');
            return;
        }

        if (!$repo->updateEvent($eventId, $data)) {
            $_SESSION['msg'] = $this->mediaService->alertHtml('danger', 'Erro ao atualizar.');
            return;
        }

        if (!$this->mediaService->persistMediaAfterUpdate($eventId, $repo, $existing)) {
            return;
        }

        $_SESSION['msg'] = $this->mediaService->alertHtml('success', 'Evento atualizado.');
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
