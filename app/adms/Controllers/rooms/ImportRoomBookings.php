<?php

namespace App\adms\Controllers\rooms;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Repository\ButtonPermissionUserRepository;
use App\adms\Models\Repository\MeetingRoomsRepository;
use App\adms\Models\Repository\RoomBookingsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Services\RoomBookingSpreadsheetImportService;

/**
 * Download do modelo CSV e importação de reservas para uma sala específica.
 *
 * GET  ?room_id=N&template=1  → arquivo modelo
 * POST room_id + import_file  → processa arquivo
 */
class ImportRoomBookings
{
    public function index(): void
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: ' . $_ENV['URL_ADM'] . 'login');
            exit;
        }

        if (!UserAccessHelper::hasFullSystemAccess()) {
            $perm = new ButtonPermissionUserRepository();
            $allowed = $perm->buttonPermission(['UpdateMeetingRoom']);
            if (!is_array($allowed) || !in_array('UpdateMeetingRoom', $allowed, true)) {
                $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Sem permissão para importar reservas.</div>';
                header('Location: ' . $_ENV['URL_ADM'] . 'list-meeting-rooms');
                exit;
            }
        }

        $roomId = (int) ($_GET['room_id'] ?? $_POST['room_id'] ?? 0);
        if ($roomId <= 0) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Sala inválida.</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-meeting-rooms');
            exit;
        }

        $roomsRepo = new MeetingRoomsRepository();
        $room = $roomsRepo->getById($roomId);
        if (!$room) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Sala não encontrada.</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-meeting-rooms');
            exit;
        }

        if (!empty($_GET['template'])) {
            $this->sendTemplateDownload($room);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CSRFHelper::validateCSRFToken('import_room_bookings', $_POST['csrf_token'] ?? '')) {
                $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Token de segurança inválido. Atualize a página e tente novamente.</div>';
                header('Location: ' . $_ENV['URL_ADM'] . 'list-meeting-rooms');
                exit;
            }

            $fileErr = (int) ($_FILES['import_file']['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($fileErr !== UPLOAD_ERR_OK) {
                $mapErr = [
                    UPLOAD_ERR_INI_SIZE => 'Arquivo excede upload_max_filesize do PHP.',
                    UPLOAD_ERR_FORM_SIZE => 'Arquivo excede o limite do formulário.',
                    UPLOAD_ERR_PARTIAL => 'Upload incompleto. Tente novamente.',
                    UPLOAD_ERR_NO_FILE => 'Nenhum arquivo enviado. Selecione .csv ou .xlsx.',
                    UPLOAD_ERR_NO_TMP_DIR => 'Servidor sem pasta temporária para upload.',
                    UPLOAD_ERR_CANT_WRITE => 'Falha ao gravar o arquivo no servidor.',
                    UPLOAD_ERR_EXTENSION => 'Upload bloqueado por extensão no servidor.',
                ];
                $msg = $mapErr[$fileErr] ?? ('Erro de upload (código ' . $fileErr . ').');
                $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">' . htmlspecialchars($msg) . '</div>';
                header('Location: ' . $_ENV['URL_ADM'] . 'list-meeting-rooms');
                exit;
            }

            if (empty($_FILES['import_file']['tmp_name']) || !is_uploaded_file($_FILES['import_file']['tmp_name'])) {
                $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Selecione um arquivo (.csv ou .xlsx) para importar.</div>';
                header('Location: ' . $_ENV['URL_ADM'] . 'list-meeting-rooms');
                exit;
            }

            $orig = (string) ($_FILES['import_file']['name'] ?? 'import');
            $tmp = (string) $_FILES['import_file']['tmp_name'];

            $bookingsRepo = new RoomBookingsRepository();
            $usersRepo = new UsersRepository();
            $result = RoomBookingSpreadsheetImportService::importFile($tmp, $orig, $roomId, $bookingsRepo, $usersRepo);

            $parts = [];
            $parts[] = '<strong>Importação concluída.</strong> Criadas: ' . (int) $result['created'] . '. Ignoradas (conflito): ' . (int) $result['skipped'] . '.';
            if ((int) $result['created'] === 0 && empty($result['errors']) && empty($result['warnings'])) {
                $parts[] = '<p class="small mb-0 mt-2">Nenhuma reserva foi gravada. Confira se o arquivo tem linhas de dados abaixo do cabeçalho e se o separador é <strong>;</strong> ou <strong>,</strong> (o sistema detecta automaticamente).</p>';
            }
            if (!empty($result['warnings'])) {
                $parts[] = '<ul class="mb-0 small">' . implode('', array_map(static fn ($w) => '<li>' . htmlspecialchars($w) . '</li>', $result['warnings'])) . '</ul>';
            }
            if (!empty($result['errors'])) {
                $parts[] = '<div class="text-danger small fw-semibold mt-2">Erros:</div><ul class="mb-0 small">' . implode('', array_map(static fn ($w) => '<li>' . htmlspecialchars($w) . '</li>', $result['errors'])) . '</ul>';
            }
            $class = (!empty($result['errors']) || ((int) $result['created'] === 0))
                ? 'warning'
                : 'success';
            $_SESSION['msg'] = '<div class="alert alert-' . $class . '" role="alert">' . implode('', $parts) . '</div>';

            header('Location: ' . $_ENV['URL_ADM'] . 'list-meeting-rooms');
            exit;
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'list-meeting-rooms');
        exit;
    }

    /**
     * @param array<string, mixed> $room
     */
    private function sendTemplateDownload(array $room): void
    {
        $name = (string) ($room['name'] ?? 'sala');
        $slug = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $name) ?: 'sala';
        $slug = trim($slug, '-');
        $csv = RoomBookingSpreadsheetImportService::buildCsvTemplate($name);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="modelo-reservas-' . $slug . '.csv"');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        echo $csv;
    }
}
