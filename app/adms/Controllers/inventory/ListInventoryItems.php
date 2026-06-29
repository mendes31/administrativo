<?php

namespace App\adms\Controllers\inventory;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\inventory\InvCategoriesRepository;
use App\adms\Models\Repository\inventory\InvInventorySapSyncRunsRepository;
use App\adms\Models\Repository\inventory\InvItemsRepository;
use App\adms\Models\Repository\inventory\InvPharmaFormsRepository;
use App\adms\Models\Services\InventorySapSyncService;
use App\adms\Models\Services\InventorySapSyncWebRunner;
use App\adms\Views\Services\LoadViewService;

class ListInventoryItems
{
    private array|string|null $data = null;

    private int $limitResult = 10;

    public function index(): void
    {
        if (isset($_GET['sap_sync_status'], $_GET['run_id']) && is_numeric($_GET['run_id'])) {
            $this->respondSyncStatus((int) $_GET['run_id']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sap_sync_cancel'])) {
            $this->handleSyncCancelRequest();
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sync_sap_all'])) {
            $this->handleUnifiedSyncRequest();
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sync_sap_items'])) {
            $this->handleItemsSyncRequest();
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sync_sap_structures'])) {
            $this->handleStructuresSyncRequest();
            return;
        }

        if (!isset($_SESSION['filtros_list_inventory_items'])) {
            $_SESSION['filtros_list_inventory_items'] = [];
        }

        if (isset($_GET['limpar_filtros']) && $_GET['limpar_filtros'] == '1') {
            unset($_SESSION['filtros_list_inventory_items']);
            header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-items');
            exit;
        }

        $page = 1;
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int) $_GET['page'];
        }

        $filtros = [
            'code' => $_GET['code'] ?? $_SESSION['filtros_list_inventory_items']['code'] ?? '',
            'description' => $_GET['description'] ?? $_SESSION['filtros_list_inventory_items']['description'] ?? '',
            'active' => $_GET['active'] ?? $_SESSION['filtros_list_inventory_items']['active'] ?? '',
            'categoria_id' => $_GET['categoria_id'] ?? $_SESSION['filtros_list_inventory_items']['categoria_id'] ?? '',
            'production_line' => $_GET['production_line'] ?? $_SESSION['filtros_list_inventory_items']['production_line'] ?? '',
            'inv_pharma_form_id' => $_GET['inv_pharma_form_id'] ?? $_SESSION['filtros_list_inventory_items']['inv_pharma_form_id'] ?? '',
        ];

        if (
            isset($_GET['code'])
            || isset($_GET['description'])
            || isset($_GET['active'])
            || isset($_GET['categoria_id'])
            || isset($_GET['production_line'])
            || isset($_GET['inv_pharma_form_id'])
        ) {
            $_SESSION['filtros_list_inventory_items'] = $filtros;
        }

        if (isset($_GET['per_page']) && in_array((int) $_GET['per_page'], [10, 20, 50, 100], true)) {
            $this->limitResult = (int) $_GET['per_page'];
            $_SESSION['filtros_list_inventory_items']['per_page'] = $this->limitResult;
        } elseif (isset($_SESSION['filtros_list_inventory_items']['per_page'])) {
            $this->limitResult = (int) $_SESSION['filtros_list_inventory_items']['per_page'];
        }

        $repo = new InvItemsRepository();
        $categoriesRepo = new InvCategoriesRepository();
        $this->data['categories'] = $categoriesRepo->getImportedForSelect();
        $this->data['pharmaForms'] = (new InvPharmaFormsRepository())->getAllForSelect();

        $total = $repo->countAll($filtros);
        $this->data['items'] = $repo->getAll($page, $this->limitResult, $filtros);

        $pagination = PaginationService::generatePagination(
            $total,
            $this->limitResult,
            $page,
            'list-inventory-items',
            array_merge($filtros, ['per_page' => $this->limitResult])
        );
        $this->data['pagination'] = $pagination;
        $this->data['per_page'] = $this->limitResult;
        $this->data['filtros'] = $filtros;

        $pageElements = [
            'title_head' => 'Itens de Estoque',
            'menu' => 'estoque',
            'buttonPermission' => ['CreateInventoryItem', 'UpdateInventoryItem', 'ViewInventoryItem', 'DeleteInventoryItem'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/inventory/items/list', $this->data);
        $loadView->loadView();
    }

    private function respondSyncStatus(int $runId): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        $status = InventorySapSyncService::getSyncRunStatus($runId);
        if ($status === null) {
            echo json_encode(['ok' => false, 'message' => 'Execução não encontrada.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        echo json_encode(array_merge(['ok' => true], $status), JSON_UNESCAPED_UNICODE);
    }

    private function handleSyncCancelRequest(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $runId = (int) ($_POST['run_id'] ?? 0);
        $token = (string) ($_POST['csrf_token'] ?? '');
        $syncType = (string) ($_POST['sync_type'] ?? 'items');
        $csrfForm = match ($syncType) {
            'structures' => 'form_sync_inventory_structures',
            'all' => 'form_sync_inventory_all',
            default => 'form_sync_inventory_items',
        };

        if ($runId <= 0 || !CSRFHelper::validateCSRFToken($csrfForm, $token, false)) {
            echo json_encode(['ok' => false, 'message' => 'Não foi possível cancelar a sincronização.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $runsRepo = new InvInventorySapSyncRunsRepository();
        $run = $runsRepo->getById($runId);
        if ($run === null || (string)($run['status'] ?? '') !== 'running') {
            echo json_encode(['ok' => false, 'message' => 'Nenhuma sincronização em andamento para cancelar.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $cancelled = $runsRepo->requestCancel($runId);

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        echo json_encode([
            'ok' => $cancelled,
            'message' => $cancelled
                ? 'Sincronização cancelada.'
                : 'Não foi possível cancelar a sincronização.',
        ], JSON_UNESCAPED_UNICODE);
    }

    private function handleItemsSyncRequest(): void
    {
        $token = (string) ($_POST['csrf_token'] ?? '');
        $isAjax = !empty($_POST['sync_sap_ajax']);
        $phase = (string) ($_POST['sync_sap_phase'] ?? 'legacy');
        $consumeCsrf = !$isAjax;

        if (!CSRFHelper::validateCSRFToken('form_sync_inventory_items', $token, $consumeCsrf)) {
            $this->finishSyncRequest(false, 'Token CSRF inválido para sincronização.');
            return;
        }

        @set_time_limit(0);

        $fullSync = !empty($_POST['sync_sap_items_full']);
        $filterCode = trim((string)($_POST['sync_sap_item_code'] ?? ''));
        $filterGroup = trim((string)($_POST['sync_sap_group_prefix'] ?? ''));
        $autoContinue = !isset($_POST['sync_sap_items_continue']) || !empty($_POST['sync_sap_items_continue']);
        if ($filterCode !== '') {
            $fullSync = false;
            $filterGroup = '';
        }

        $isScopedItem = $filterCode !== '';
        $syncMode = $isScopedItem
            ? 'item'
            : ($filterGroup !== ''
                ? ('group-' . $filterGroup . ($fullSync ? '-full' : ''))
                : ($fullSync ? 'full' : 'diff'));
        $scopeLabel = $isScopedItem
            ? ('item:' . $filterCode)
            : ($filterGroup !== '' ? ('group:' . $filterGroup) : null);

        $runId = (int) ($_POST['run_id'] ?? 0);

        if ($isAjax && $phase === 'start') {
            $runId = (new InvInventorySapSyncRunsRepository())->create([
                'sync_type' => 'items',
                'sync_mode' => $syncMode,
                'filter_from_date' => $scopeLabel,
                'status' => 'running',
                'started_at' => date('Y-m-d H:i:s'),
            ]);
            if ($runId <= 0) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => 'Não foi possível iniciar o acompanhamento da sincronização.'], JSON_UNESCAPED_UNICODE);
                return;
            }

            $runsRepo = new InvInventorySapSyncRunsRepository();
            $runsRepo->updateProgress($runId, [
                'progress_examined' => 0,
                'progress_total' => null,
                'progress_label' => 'Iniciando sincronização SAP…',
                'rows_created' => 0,
                'rows_updated' => 0,
                'rows_unchanged' => 0,
                'rows_failed' => 0,
            ]);

            $spawned = InventorySapSyncWebRunner::spawn($runId, 'items', [
                'full' => $fullSync,
                'code' => $filterCode,
                'group' => $filterGroup,
                'auto_continue' => $autoContinue,
            ]);

            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => true,
                'run_id' => $runId,
                'sync_type' => 'items',
                'spawned' => $spawned,
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($isAjax && $phase === 'execute') {
            if ($runId <= 0 || !$this->assertRunningSyncRun($runId, 'items')) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => 'Execução inválida ou já finalizada.'], JSON_UNESCAPED_UNICODE);
                return;
            }

            $this->flushJsonResponse(['ok' => true, 'run_id' => $runId, 'executing' => true]);

            $service = new InventorySapSyncService();
            $service->syncItemsAndCostsInteractive(
                $fullSync,
                $filterCode !== '' ? $filterCode : null,
                $filterGroup !== '' ? $filterGroup : null,
                $autoContinue,
                40,
                $runId
            );
            exit;
        } elseif ($isAjax && $phase === 'legacy') {
            // AJAX legado sem fases — não usar iframe.
        } elseif (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $service = new InventorySapSyncService();
        $result = $service->syncItemsAndCostsInteractive(
            $fullSync,
            $filterCode !== '' ? $filterCode : null,
            $filterGroup !== '' ? $filterGroup : null,
            $autoContinue,
            40,
            $runId > 0 ? $runId : null
        );

        if ($isAjax) {
            exit;
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $this->finishSyncRequest(!empty($result['success']), (string) ($result['message'] ?? 'Erro desconhecido na sincronização SAP.'));
    }

    private function handleUnifiedSyncRequest(): void
    {
        $token = (string) ($_POST['csrf_token'] ?? '');
        $isAjax = !empty($_POST['sync_sap_ajax']);
        $phase = (string) ($_POST['sync_sap_phase'] ?? 'legacy');
        $consumeCsrf = !$isAjax;

        if (!CSRFHelper::validateCSRFToken('form_sync_inventory_all', $token, $consumeCsrf)) {
            $this->finishSyncRequest(false, 'Token CSRF inválido para sincronização SAP.');
            return;
        }

        $runsRepo = new InvInventorySapSyncRunsRepository();

        @set_time_limit(0);

        $fullSync = !empty($_POST['sync_sap_items_full']);
        $filterCode = trim((string)($_POST['sync_sap_item_code'] ?? ''));
        $filterGroup = trim((string)($_POST['sync_sap_group_prefix'] ?? ''));
        $autoContinue = !isset($_POST['sync_sap_items_continue']) || !empty($_POST['sync_sap_items_continue']);
        if ($filterCode !== '') {
            $fullSync = false;
            $filterGroup = '';
        }

        $isScopedItem = $filterCode !== '';
        $syncMode = $isScopedItem
            ? 'item'
            : ($filterGroup !== ''
                ? ('group-' . $filterGroup . ($fullSync ? '-full' : ''))
                : ($fullSync ? 'full' : 'incremental'));
        $scopeLabel = $isScopedItem
            ? ('item:' . $filterCode)
            : ($filterGroup !== '' ? ('group:' . $filterGroup) : null);

        $runId = (int) ($_POST['run_id'] ?? 0);

        if ($isAjax && $phase === 'start') {
            $runsRepo->expireStaleActiveRuns();
            $runsRepo->expireLongRunningRuns();
            if ($runsRepo->hasActiveRun()) {
                $this->finishSyncRequest(false, 'Já existe uma sincronização SAP em andamento. Aguarde a conclusão ou interrompa antes de iniciar outra.');
                return;
            }

            $runId = $runsRepo->create([
                'sync_type' => 'all',
                'sync_mode' => $syncMode,
                'filter_from_date' => $scopeLabel,
                'status' => 'running',
                'started_at' => date('Y-m-d H:i:s'),
            ]);
            if ($runId <= 0) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => 'Não foi possível iniciar o acompanhamento da sincronização.'], JSON_UNESCAPED_UNICODE);
                return;
            }

            $runsRepo->setCurrentPhase($runId, 'items');
            $runsRepo->updateProgress($runId, [
                'progress_examined' => 0,
                'progress_total' => null,
                'progress_label' => 'Iniciando sincronização SAP…',
                'rows_created' => 0,
                'rows_updated' => 0,
                'rows_unchanged' => 0,
                'rows_failed' => 0,
            ]);

            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => true,
                'run_id' => $runId,
                'sync_type' => 'all',
                'spawned' => false,
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($isAjax && $phase === 'execute') {
            if ($runId <= 0 || !$this->assertRunningSyncRun($runId, 'all')) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => 'Execução inválida ou já finalizada.'], JSON_UNESCAPED_UNICODE);
                return;
            }

            $this->flushJsonResponse(['ok' => true, 'run_id' => $runId, 'executing' => true]);

            (new InventorySapSyncService())->syncSapUnifiedInteractive(
                $fullSync,
                $filterCode !== '' ? $filterCode : null,
                $filterGroup !== '' ? $filterGroup : null,
                $autoContinue,
                40,
                $runId
            );
            exit;
        } elseif (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        if ($runsRepo->hasActiveRun()) {
            $this->finishSyncRequest(false, 'Já existe uma sincronização SAP em andamento. Aguarde a conclusão ou interrompa antes de iniciar outra.');
            return;
        }

        $result = (new InventorySapSyncService())->syncSapUnifiedInteractive(
            $fullSync,
            $filterCode !== '' ? $filterCode : null,
            $filterGroup !== '' ? $filterGroup : null,
            $autoContinue,
            40,
            $runId > 0 ? $runId : null
        );

        if ($isAjax) {
            exit;
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $this->finishSyncRequest(!empty($result['success']), (string) ($result['message'] ?? 'Erro desconhecido na sincronização SAP.'));
    }

    private function handleStructuresSyncRequest(): void
    {
        $token = (string) ($_POST['csrf_token'] ?? '');
        $isAjax = !empty($_POST['sync_sap_ajax']);
        $phase = (string) ($_POST['sync_sap_phase'] ?? 'legacy');
        $consumeCsrf = !$isAjax;

        if (!CSRFHelper::validateCSRFToken('form_sync_inventory_structures', $token, $consumeCsrf)) {
            $this->finishSyncRequest(false, 'Token CSRF inválido para sincronização de estruturas.');
            return;
        }

        @set_time_limit(0);

        $runId = (int) ($_POST['run_id'] ?? 0);

        if ($isAjax && $phase === 'start') {
            $runId = (new InvInventorySapSyncRunsRepository())->create([
                'sync_type' => 'structures',
                'sync_mode' => 'full',
                'status' => 'running',
                'started_at' => date('Y-m-d H:i:s'),
            ]);
            if ($runId <= 0) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => 'Não foi possível iniciar o acompanhamento da sincronização.'], JSON_UNESCAPED_UNICODE);
                return;
            }

            (new InvInventorySapSyncRunsRepository())->updateProgress($runId, [
                'progress_examined' => 0,
                'progress_total' => null,
                'progress_label' => 'Iniciando sincronização de estruturas…',
                'rows_created' => 0,
                'rows_updated' => 0,
                'rows_unchanged' => 0,
                'rows_failed' => 0,
            ]);

            $spawned = InventorySapSyncWebRunner::spawn($runId, 'structures');

            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => true,
                'run_id' => $runId,
                'sync_type' => 'structures',
                'spawned' => $spawned,
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($isAjax && $phase === 'execute') {
            if ($runId <= 0 || !$this->assertRunningSyncRun($runId, 'structures')) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => 'Execução inválida ou já finalizada.'], JSON_UNESCAPED_UNICODE);
                return;
            }

            $this->flushJsonResponse(['ok' => true, 'run_id' => $runId, 'executing' => true]);

            $service = new InventorySapSyncService();
            $service->syncAllItemStructures($runId);
            exit;
        } elseif (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $service = new InventorySapSyncService();
        $result = $service->syncAllItemStructures($runId > 0 ? $runId : null);

        if ($isAjax) {
            exit;
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $this->finishSyncRequest(!empty($result['success']), (string) ($result['message'] ?? 'Erro desconhecido na sincronização de estruturas.'));
    }

    private function assertRunningSyncRun(int $runId, string $syncType): bool
    {
        $run = (new InvInventorySapSyncRunsRepository())->getById($runId);
        if ($run === null) {
            return false;
        }

        return (string)($run['status'] ?? '') === 'running'
            && (string)($run['sync_type'] ?? '') === $syncType;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function flushJsonResponse(array $payload): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        header('Content-Type: application/json; charset=utf-8');
        header('Connection: close');
        header('Content-Length: ' . strlen($json));
        echo $json;
        flush();

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        ignore_user_abort(true);
        @set_time_limit(0);
    }

    private function finishSyncRequest(bool $success, string $message): void
    {
        if (!empty($_POST['sync_sap_ajax'])) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => $success,
                'message' => $message,
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        if ($success) {
            $_SESSION['msg'] = "<div class='alert alert-success' role='alert'>{$safeMessage}</div>";
        } else {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>{$safeMessage}</div>";
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-items');
    }
}
