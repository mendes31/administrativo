<?php

namespace App\adms\Controllers\positions;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Views\Services\LoadViewService;

class ImportPositions
{
    private array|string|null $data = null;

    public function index(string $action = ''): void
    {
        if (empty($_SESSION['user_id'])) {
            $_SESSION['error'] = 'Sessão inválida! Faça login para continuar.';
            header('Location: ' . $_ENV['URL_ADM'] . 'login');
            return;
        }

        if ($action === 'template') {
            $this->template();
            return;
        }

        $this->data['form'] = $_POST ?? [];

        if (!empty($_FILES['file']) && isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_import_positions', $this->data['form']['csrf_token'])) {
            $this->processFile();
            return;
        }

        $this->view();
    }

    private function view(): void
    {
        $pageElements = [
            'title_head' => 'Importar Cargos',
            'menu' => 'list-positions',
            'buttonPermission' => ['ListPositions'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/positions/importPositions', $this->data);
        $loadView->loadView();
    }

    private function processFile(): void
    {
        $file = $_FILES['file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->data['errors'][] = 'Falha ao enviar o arquivo.';
            $this->view();
            return;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv'], true)) {
            $this->data['errors'][] = 'Formato inválido. Envie um arquivo CSV (recomendado).';
            $this->view();
            return;
        }

        $handled = $this->processCsv($file['tmp_name']);
        if (!$handled) {
            $this->data['errors'][] = 'Não foi possível processar o arquivo. Verifique o template.';
        }
        $this->view();
    }

    private function processCsv(string $tmpPath): bool
    {
        $fp = fopen($tmpPath, 'r');
        if (!$fp) return false;

        $header = fgetcsv($fp, 0, ';');
        if (!$header) { fclose($fp); return false; }

        $expected = ['name'];
        $map = [];
        foreach ($expected as $col) {
            $idx = array_search($col, $header, true);
            $map[$col] = $idx !== false ? (int)$idx : null;
        }

        $repo = new PositionsRepository();
        $created = 0; $updated = 0; $skipped = 0; $errors = 0; $rows = 1;
        $this->data['report'] = [];

        while (($row = fgetcsv($fp, 0, ';')) !== false) {
            $rows++;
            if (count(array_filter($row, fn($v)=> trim((string)$v) !== '')) === 0) continue;

            $name = trim((string)($row[$map['name']] ?? ''));
            if ($name === '') { $skipped++; $this->data['report'][] = ['linha'=>$rows,'acao'=>'ignorado','email'=>'','msg'=>'Nome vazio']; continue; }

            try {
                $existing = $repo->getByName($name);
                if ($existing) {
                    if (trim($existing['name']) === $name) {
                        $skipped++;
                        $this->data['report'][] = ['linha'=>$rows,'acao'=>'ignorado','email'=>'','msg'=>'Nenhuma alteração'];
                    } else {
                        $ok = $repo->updatePosition(['id'=>(int)$existing['id'],'name'=>$name]);
                        if ($ok) { $updated++; $this->data['report'][] = ['linha'=>$rows,'acao'=>'atualizado','email'=>'']; }
                        else { $errors++; $this->data['report'][] = ['linha'=>$rows,'acao'=>'erro','msg'=>'Falha ao atualizar']; }
                    }
                } else {
                    $okId = $repo->createPosition(['name'=>$name]);
                    if ($okId) { $created++; $this->data['report'][] = ['linha'=>$rows,'acao'=>'criado']; }
                    else { $errors++; $this->data['report'][] = ['linha'=>$rows,'acao'=>'erro','msg'=>'Falha ao criar']; }
                }
            } catch (\Throwable $e) {
                $errors++;
                $this->data['report'][] = ['linha'=>$rows,'acao'=>'erro','msg'=>$e->getMessage()];
                GenerateLog::generateLog('error','Falha ao importar cargo.', ['name'=>$name, 'e'=>$e->getMessage()]);
            }
        }
        fclose($fp);

        $this->data['summary'] = compact('created','updated','skipped','errors');
        $_SESSION['success'] = "Importação concluída: criados {$created}, atualizados {$updated}, erros {$errors}.";
        return true;
    }

    public function template(): void
    {
        $filename = 'template_importacao_cargos.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        $out = fopen('php://output', 'w');
        fputcsv($out, ['name'], ';');
        fputcsv($out, ['Administrador'], ';');
        fclose($out);
        exit;
    }
}


