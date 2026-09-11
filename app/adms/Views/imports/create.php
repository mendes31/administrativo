<?php

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Services\Imports\ImportProfileInterface;

/** @var ImportProfileInterface $profile */
$profile = $this->data['profile'];
$form = $this->data['form'] ?? [];
$urlAdm = $_ENV['URL_ADM'] ?? '';
$operation = (string) ($form['operation'] ?? 'upsert');
$emptyPolicy = (string) ($form['empty_policy'] ?? 'skip');
$dryRun = array_key_exists('dry_run', $form) ? !empty($form['dry_run']) : true;
?>

<div class="container-fluid px-4">
    <div class="mb-1 d-flex flex-column flex-sm-row gap-2">
        <h2 class="mt-3">Importar — <?php echo htmlspecialchars($profile->label(), ENT_QUOTES, 'UTF-8'); ?></h2>
        <ol class="breadcrumb mb-3 mt-0 mt-sm-3 ms-auto">
            <li class="breadcrumb-item"><a class="text-decoration-none" href="<?php echo $urlAdm; ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a class="text-decoration-none" href="<?php echo $urlAdm; ?>import-center">Importações</a></li>
            <li class="breadcrumb-item">Enviar arquivo</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span>Arquivo e operação</span>
            <span class="ms-auto">
                <a class="btn btn-outline-secondary btn-sm" href="<?php echo $urlAdm; ?>import-center-template?profile=<?php echo urlencode($profile->key()); ?>">
                    <i class="fa-solid fa-download"></i> Modelo CSV
                </a>
                <a class="btn btn-info btn-sm" href="<?php echo $urlAdm; ?>import-center"><i class="fa-solid fa-list"></i> Central</a>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <?php if (!empty($this->data['errors'])): ?>
                <div class="alert alert-danger">
                    <?php foreach ($this->data['errors'] as $err): ?>
                        <div><?php echo htmlspecialchars((string) $err, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_import_center_upload'); ?>">
                <input type="hidden" name="profile" value="<?php echo htmlspecialchars($profile->key(), ENT_QUOTES, 'UTF-8'); ?>">

                <div class="col-md-8">
                    <label class="form-label" for="file">Planilha (.xlsx ou .csv)</label>
                    <input class="form-control" type="file" name="file" id="file" accept=".xlsx,.xls,.csv,.txt" required>
                    <div class="form-text">O modelo CSV traz duas linhas de cabeçalho: rótulos da tela e, abaixo, os nomes dos campos. A importação ignora os rótulos e usa só os nomes dos campos. Planilhas antigas (só a linha de campos) continuam válidas.</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="operation">Operação</label>
                    <select class="form-select" name="operation" id="operation">
                        <option value="upsert" <?php echo $operation === 'upsert' ? 'selected' : ''; ?>>Inserir e atualizar (upsert)</option>
                        <option value="insert" <?php echo $operation === 'insert' ? 'selected' : ''; ?>>Somente inserir</option>
                        <option value="update" <?php echo $operation === 'update' ? 'selected' : ''; ?>>Somente atualizar</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="empty_policy">Campo vazio na planilha</label>
                    <select class="form-select" name="empty_policy" id="empty_policy">
                        <option value="skip" <?php echo $emptyPolicy === 'skip' ? 'selected' : ''; ?>>Não alterar o valor atual (skip)</option>
                        <option value="clear" <?php echo $emptyPolicy === 'clear' ? 'selected' : ''; ?>>Limpar o valor no cadastro (clear)</option>
                    </select>
                </div>

                <div class="col-md-8 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="dry_run" id="dry_run" value="1" <?php echo $dryRun ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="dry_run">Somente simular (não grava no banco)</label>
                    </div>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-upload"></i> Enviar e mapear colunas</button>
                </div>
            </form>
        </div>
    </div>
</div>
