<?php

declare(strict_types=1);

namespace App\adms\Controllers\rooms;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\RoomCalendarSettingsRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Models\Services\RoomExternalCalendarConfig;
use App\adms\Views\Services\LoadViewService;

/**
 * Configuração na aplicação para futura sincronização Outlook / Google Calendar.
 */
final class RoomsCalendarIntegrationSettings
{
    private array $data = [];

    public function index(): void
    {
        $repo = new RoomCalendarSettingsRepository();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CSRFHelper::validateCSRFToken('form_rooms_calendar_integration', $_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token de segurança inválido.';
            } else {
                $ok = $repo->save([
                    'outlook_sync_enabled' => !empty($_POST['outlook_sync_enabled']),
                    'google_sync_enabled' => !empty($_POST['google_sync_enabled']),
                    'outlook_tenant_id' => trim((string) ($_POST['outlook_tenant_id'] ?? '')),
                    'outlook_client_id' => trim((string) ($_POST['outlook_client_id'] ?? '')),
                    'google_client_id' => trim((string) ($_POST['google_client_id'] ?? '')),
                ], (int) ($_SESSION['user_id'] ?? 0));
                RoomExternalCalendarConfig::clearCache();
                if ($ok) {
                    $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Configurações guardadas.</div>';
                } else {
                    $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Não foi possível guardar. Confirme que executou as migrações (tabela adms_room_calendar_settings).</div>';
                }
            }
            header('Location: ' . $_ENV['URL_ADM'] . 'rooms-calendar-integration-settings');
            exit;
        }

        $this->data['settings'] = $repo->getSingleton();
        $this->data['csrf_token'] = CSRFHelper::generateCSRFToken('form_rooms_calendar_integration');
        $rowId = (int) ($this->data['settings']['id'] ?? 1);
        if ($rowId > 0) {
            $returnUrl = $_ENV['URL_ADM'] . 'rooms-calendar-integration-settings';
            $this->data['log_resumo'] = LogResumoService::getResumo('adms_room_calendar_settings', $rowId, $returnUrl);
        }

        $pageElements = [
            'title_head' => 'Integração calendário (Salas)',
            'menu' => 'rooms-calendar-integration-settings',
            'buttonPermission' => [
                'RoomsCalendarIntegrationSettings',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/rooms/calendar_integration_settings', $this->data);
        $loadView->loadView();
    }
}
