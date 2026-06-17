<?php

declare(strict_types=1);

if (!function_exists('colLabel')) {
function colLabel(string $col): string
{
    return match ($col) {
        'colaborador_nome' => 'Colaborador',
        'epi_nome' => 'EPI',
        'exame_nome' => 'Exame',
        'cargo_nome' => 'Cargo',
        'departamento_nome' => 'Departamento',
        'risco_nome' => 'Risco',
        'periodicidade_meses' => 'Periodicidade',
        'data_realizacao' => 'Realização',
        'data_validade' => 'Validade',
        'data_movimento' => 'Data',
        'data_ocorrencia' => 'Ocorrência',
        'tipo_movimento' => 'Movimento',
        'ca_numero' => 'Nº CA',
        'estoque_atual' => 'Estoque',
        default => ucfirst(str_replace('_', ' ', $col)),
    };
}

function formatCellValue(string $col, mixed $value): string
{
    if ($value === null || $value === '') {
        return '-';
    }
    if (is_bool($value) || $col === 'obrigatorio' || $col === 'termo_assinado') {
        return ($value === true || $value === 1 || $value === '1') ? 'Sim' : 'Não';
    }
    if (str_contains($col, 'data_') && is_string($value)) {
        if (strlen($value) > 10) {
            return date('d/m/Y H:i', strtotime($value));
        }
        return date('d/m/Y', strtotime($value));
    }
    return htmlspecialchars((string) $value);
}

function generateSstViews(string $root, string $key, array $e, array $names, bool $hasView): void
{
    $url = $e['url'];
    $menu = $e['menu'];
    $plural = $e['plural'];
    $singular = $e['singular'];
    $icon = $e['icon'];
    $prefix = $e['prefix'];
    $listCols = $e['list_cols'];
    $hasStatus = isset($e['fields']['status']);
    $isEmployee = $e['type'] === 'employee';
    $hasAnexos = !empty($e['has_anexos']);

    $createCtrl = $names['create'];
    $viewCtrl = $names['view'];
    $updateCtrl = $names['update'];
    $deleteCtrl = $names['delete'];

    $tableHeaders = '';
    foreach ($listCols as $col) {
        $tableHeaders .= '<th>' . colLabel($col) . '</th>' . "\n";
    }

    $tableCells = '';
    foreach ($listCols as $col) {
        if ($col === 'status') {
            $tableCells .= "<td class=\"text-center\"><span class=\"badge bg-secondary\"><?= htmlspecialchars(\$item['status'] ?? '') ?></span></td>\n";
        } else {
            $tableCells .= "<td><?= formatCellValue('{$col}', \$item['{$col}'] ?? null) ?></td>\n";
        }
    }

    $mobilePrimary = $listCols[1] ?? 'id';
    $mobileSecondary = $listCols[2] ?? null;

    $statusFilter = '';
    if ($hasStatus) {
        $opts = $e['fields']['status']['options'] ?? ['Ativo', 'Inativo'];
        $statusFilter = '<div class="col-6 col-sm-4 col-md-2">
            <label for="status" class="form-label" style="font-size:.7rem;">Status</label>
            <select name="status" id="status" class="form-select form-select-sm">
                <option value="">Todos</option>';
        foreach ($opts as $opt) {
            $statusFilter .= "<?php \$sel = (\$this->data['filters']['status'] ?? '') === '{$opt}' ? 'selected' : ''; ?>\n";
            $statusFilter .= "<option value=\"{$opt}\" <?= \$sel ?>>{$opt}</option>\n";
        }
        $statusFilter .= '</select></div>';
    }

    $userFilter = '';
    if ($isEmployee) {
        $userFilter = '<div class="col-6 col-sm-4 col-md-3">
            <label for="adms_user_id" class="form-label" style="font-size:.7rem;">Colaborador</label>
            <select name="adms_user_id" id="adms_user_id" class="form-select form-select-sm">
                <option value="">Todos</option>
                <?php foreach ($this->data[\'users\'] ?? [] as $u): ?>
                    <option value="<?= (int)$u[\'id\'] ?>" <?= ((string)($this->data[\'filters\'][\'adms_user_id\'] ?? \'\') === (string)$u[\'id\']) ? \'selected\' : \'\' ?>>
                        <?= htmlspecialchars($u[\'name\'] ?? \'\') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>';
    }

    $viewBtn = $hasView ? "if (in_array('{$viewCtrl}', \$perms)) {
                        echo \"<a href='{\$_ENV['URL_ADM']}sst-view-{$url}/{\$id}' class='btn btn-info btn-sm' title='Visualizar'><i class='fa-regular fa-eye'></i></a>\";
                    }" : '';

    $listPhp = <<<PHP
<?php

use App\adms\Helpers\CSRFHelper;

function formatCellValue(string \$col, mixed \$value): string
{
    if (\$value === null || \$value === '') {
        return '-';
    }
    if (is_bool(\$value) || \$col === 'obrigatorio' || \$col === 'termo_assinado') {
        return (\$value === true || \$value === 1 || \$value === '1') ? 'Sim' : 'Não';
    }
    if (str_contains(\$col, 'data_') && is_string(\$value)) {
        return strlen(\$value) > 10 ? date('d/m/Y H:i', strtotime(\$value)) : date('d/m/Y', strtotime(\$value));
    }
    return htmlspecialchars((string) \$value);
}

\$entity = \$this->data['entity'];
\$perms = \$this->data['buttonPermission'] ?? [];
\$csrf_token = CSRFHelper::generateCSRFToken('form_delete_sst_{$key}');
\$filtersId = 'sstFilters{$prefix}';
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3 mobile-hide-page-title"><i class="fas {$icon} me-2"></i><?= htmlspecialchars('{$plural}') ?></h2>
        <ol class="breadcrumb mb-3 ms-auto mobile-hide-breadcrumb">
            <li class="breadcrumb-item"><a href="<?= \$_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= \$_ENV['URL_ADM']; ?>sst-dashboard">SST</a></li>
            <li class="breadcrumb-item"><?= htmlspecialchars('{$plural}') ?></li>
        </ol>
    </div>
    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span>Listar</span>
            <span class="ms-auto">
                <?php if (in_array('{$createCtrl}', \$perms)): ?>
                    <a href="<?= \$_ENV['URL_ADM']; ?>sst-create-{$url}" class="btn btn-success btn-sm"><i class="fa-regular fa-square-plus"></i> Cadastrar</a>
                <?php endif; ?>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <div class="d-md-none mb-2">
                <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#<?= \$filtersId ?>">
                    <i class="fa fa-filter me-1"></i> Filtros
                </button>
            </div>
            <div class="collapse d-md-block" id="<?= \$filtersId ?>">
                <form method="get" class="row g-2 mb-3 align-items-end">
                    <div class="col-6 col-sm-4 col-md-3">
                        <label class="form-label" style="font-size:.7rem;">Pesquisar</label>
                        <input type="text" name="search" class="form-control form-control-sm" value="<?= htmlspecialchars(\$this->data['filters']['search'] ?? '') ?>">
                    </div>
                    {$userFilter}
                    {$statusFilter}
                    <div class="col-12 col-sm-auto d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-search"></i> Filtrar</button>
                        <a href="<?= \$_ENV['URL_ADM']; ?>{$menu}" class="btn btn-secondary btn-sm">Limpar</a>
                    </div>
                </form>
            </div>
            <?php if (!empty(\$this->data['items'])): ?>
                <div class="d-none d-md-block table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead><tr>
                            {$tableHeaders}
                            <th class="text-center">Ações</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach (\$this->data['items'] as \$item):
                            \$id = (int)(\$item['id'] ?? 0);
                        ?>
                            <tr>
                                {$tableCells}
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        {$viewBtn}
                                        <?php if (in_array('{$updateCtrl}', \$perms)): ?>
                                            <a href="<?= \$_ENV['URL_ADM']; ?>sst-update-{$url}/<?= \$id ?>" class="btn btn-warning btn-sm"><i class="fa-regular fa-pen-to-square"></i></a>
                                        <?php endif; ?>
                                        <?php if (in_array('{$deleteCtrl}', \$perms)): ?>
                                            <form action="<?= \$_ENV['URL_ADM']; ?>sst-delete-{$url}" method="POST" class="d-inline" onsubmit="return confirm('Excluir registro?');">
                                                <input type="hidden" name="csrf_token" value="<?= \$csrf_token ?>">
                                                <input type="hidden" name="id" value="<?= \$id ?>">
                                                <button type="submit" class="btn btn-danger btn-sm"><i class="fa-regular fa-trash-can"></i></button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="d-block d-md-none">
                    <?php foreach (\$this->data['items'] as \$item):
                        \$id = (int)(\$item['id'] ?? 0);
                        \$canView = in_array('{$viewCtrl}', \$perms);
                        \$viewUrl = \$_ENV['URL_ADM'] . 'sst-view-{$url}/' . \$id;
                    ?>
                        <div class="card mb-2 shadow-sm"<?php if (\$canView): ?> onclick="window.location.href='<?= \$viewUrl ?>';" style="cursor:pointer;"<?php endif; ?>>
                            <div class="card-body py-2 px-3">
                                <div class="fw-bold small"><?= formatCellValue('{$mobilePrimary}', \$item['{$mobilePrimary}'] ?? \$id) ?></div>
                                <?php if (!empty(\$item['{$mobileSecondary}'])): ?>
                                    <div class="small text-muted"><?= formatCellValue('{$mobileSecondary}', \$item['{$mobileSecondary}']) ?></div>
                                <?php endif; ?>
                                <?php if (!empty(\$item['status'])): ?>
                                    <span class="badge bg-secondary"><?= htmlspecialchars(\$item['status']) ?></span>
                                <?php endif; ?>
                                <div class="d-flex gap-1 mt-2 pt-2 border-top" onclick="event.stopPropagation();">
                                    <?php if (\$canView): ?><a href="<?= \$viewUrl ?>" class="btn btn-outline-info btn-sm flex-fill">Ver</a><?php endif; ?>
                                    <?php if (in_array('{$updateCtrl}', \$perms)): ?><a href="<?= \$_ENV['URL_ADM']; ?>sst-update-{$url}/<?= \$id ?>" class="btn btn-outline-warning btn-sm flex-fill">Editar</a><?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="d-flex justify-content-end mt-2 d-none d-md-flex"><?= \$this->data['pagination']['html'] ?? '' ?></div>
                <div class="d-flex justify-content-center mt-2 d-md-none"><?= \$this->data['pagination']['html'] ?? '' ?></div>
            <?php else: ?>
                <div class="alert alert-warning">Nenhum registro encontrado.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
PHP;
    writeFile("{$root}/app/adms/Views/sst/{$key}/list.php", $listPhp);

    $formFields = '';
    foreach ($e['fields'] as $fname => $fdef) {
        $label = $fdef['label'];
        $required = !empty($fdef['required']) ? 'required' : '';
        $type = $fdef['type'];
        $formFields .= "<div class=\"col-md-6 mb-3\">\n";
        $formFields .= "<label class=\"form-label\" for=\"{$fname}\">{$label}" . ($required ? ' *' : '') . "</label>\n";

        if ($type === 'textarea') {
            $formFields .= "<textarea name=\"{$fname}\" id=\"{$fname}\" class=\"form-control\" rows=\"3\" {$required}><?= htmlspecialchars(\$item['{$fname}'] ?? '') ?></textarea>\n";
        } elseif ($type === 'select') {
            $formFields .= "<select name=\"{$fname}\" id=\"{$fname}\" class=\"form-select\" {$required}>\n";
            foreach ($fdef['options'] as $opt) {
                $formFields .= "<option value=\"{$opt}\" <?= ((\$item['{$fname}'] ?? '') === '{$opt}') ? 'selected' : '' ?>>{$opt}</option>\n";
            }
            $formFields .= "</select>\n";
        } elseif ($type === 'checkbox') {
            $formFields .= "<div class=\"form-check\"><input type=\"checkbox\" name=\"{$fname}\" id=\"{$fname}\" class=\"form-check-input\" value=\"1\" <?= !empty(\$item['{$fname}']) ? 'checked' : '' ?>><label class=\"form-check-label\" for=\"{$fname}\">{$label}</label></div>\n";
        } elseif ($type === 'user') {
            $formFields .= "<select name=\"{$fname}\" id=\"{$fname}\" class=\"form-select\" {$required}><option value=\"\">Selecione...</option>\n";
            $formFields .= "<?php foreach (\$this->data['users'] ?? [] as \$u): ?><option value=\"<?= (int)\$u['id'] ?>\" <?= ((int)(\$item['{$fname}'] ?? 0) === (int)\$u['id']) ? 'selected' : '' ?>><?= htmlspecialchars(\$u['name'] ?? '') ?></option><?php endforeach; ?>\n</select>\n";
        } elseif ($type === 'fk_position') {
            $formFields .= "<select name=\"{$fname}\" id=\"{$fname}\" class=\"form-select\"><option value=\"\">Selecione...</option>\n";
            $formFields .= "<?php foreach (\$this->data['positions'] ?? [] as \$p): ?><option value=\"<?= (int)\$p['id'] ?>\" <?= ((int)(\$item['{$fname}'] ?? 0) === (int)\$p['id']) ? 'selected' : '' ?>><?= htmlspecialchars(\$p['name'] ?? '') ?></option><?php endforeach; ?>\n</select>\n";
        } elseif ($type === 'fk_department') {
            $formFields .= "<select name=\"{$fname}\" id=\"{$fname}\" class=\"form-select\"><option value=\"\">Selecione...</option>\n";
            $formFields .= "<?php foreach (\$this->data['departments'] ?? [] as \$d): ?><option value=\"<?= (int)\$d['id'] ?>\" <?= ((int)(\$item['{$fname}'] ?? 0) === (int)\$d['id']) ? 'selected' : '' ?>><?= htmlspecialchars(\$d['name'] ?? '') ?></option><?php endforeach; ?>\n</select>\n";
        } elseif ($type === 'fk_exame') {
            $formFields .= "<select name=\"{$fname}\" id=\"{$fname}\" class=\"form-select\"><option value=\"\">Selecione...</option>\n";
            $formFields .= "<?php foreach (\$this->data['exames'] ?? [] as \$x): ?><option value=\"<?= (int)\$x['id'] ?>\" <?= ((int)(\$item['{$fname}'] ?? 0) === (int)\$x['id']) ? 'selected' : '' ?>><?= htmlspecialchars(\$x['nome'] ?? '') ?></option><?php endforeach; ?>\n</select>\n";
        } elseif ($type === 'fk_epi') {
            $formFields .= "<select name=\"{$fname}\" id=\"{$fname}\" class=\"form-select\" {$required}><option value=\"\">Selecione...</option>\n";
            $formFields .= "<?php foreach (\$this->data['epis'] ?? [] as \$x): ?><option value=\"<?= (int)\$x['id'] ?>\" <?= ((int)(\$item['{$fname}'] ?? 0) === (int)\$x['id']) ? 'selected' : '' ?>><?= htmlspecialchars(\$x['nome'] ?? '') ?></option><?php endforeach; ?>\n</select>\n";
        } elseif ($type === 'fk_medico') {
            $formFields .= "<select name=\"{$fname}\" id=\"{$fname}\" class=\"form-select\"><option value=\"\">Selecione...</option>\n";
            $formFields .= "<?php foreach (\$this->data['medicos'] ?? [] as \$x): ?><option value=\"<?= (int)\$x['id'] ?>\" <?= ((int)(\$item['{$fname}'] ?? 0) === (int)\$x['id']) ? 'selected' : '' ?>><?= htmlspecialchars(\$x['nome'] ?? '') ?></option><?php endforeach; ?>\n</select>\n";
        } elseif ($type === 'fk_cid') {
            $formFields .= "<select name=\"{$fname}\" id=\"{$fname}\" class=\"form-select\"><option value=\"\">Selecione...</option>\n";
            $formFields .= "<?php foreach (\$this->data['cids'] ?? [] as \$x): ?><option value=\"<?= (int)\$x['id'] ?>\" <?= ((int)(\$item['{$fname}'] ?? 0) === (int)\$x['id']) ? 'selected' : '' ?>><?= htmlspecialchars((\$x['codigo'] ?? '') . ' - ' . (\$x['descricao'] ?? '')) ?></option><?php endforeach; ?>\n</select>\n";
        } elseif ($type === 'fk_risco') {
            $formFields .= "<select name=\"{$fname}\" id=\"{$fname}\" class=\"form-select\" {$required}><option value=\"\">Selecione...</option>\n";
            $formFields .= "<?php foreach (\$this->data['riscos'] ?? [] as \$x): ?><option value=\"<?= (int)\$x['id'] ?>\" <?= ((int)(\$item['{$fname}'] ?? 0) === (int)\$x['id']) ? 'selected' : '' ?>><?= htmlspecialchars(\$x['nome'] ?? '') ?></option><?php endforeach; ?>\n</select>\n";
        } elseif ($type === 'datetime') {
            $formFields .= "<?php \$dt = \$item['{$fname}'] ?? ''; \$dtVal = \$dt ? date('Y-m-d\\TH:i', strtotime(\$dt)) : ''; ?>\n";
            $formFields .= "<input type=\"datetime-local\" name=\"{$fname}\" id=\"{$fname}\" class=\"form-control\" value=\"<?= \$dtVal ?>\" {$required}>\n";
        } else {
            $inputType = in_array($type, ['number', 'date', 'email'], true) ? $type : 'text';
            $formFields .= "<input type=\"{$inputType}\" name=\"{$fname}\" id=\"{$fname}\" class=\"form-control\" value=\"<?= htmlspecialchars(\$item['{$fname}'] ?? '') ?>\" {$required}>\n";
        }
        $formFields .= "</div>\n";
    }

    $formPhp = <<<PHP
<?php
use App\adms\Helpers\CSRFHelper;
\$item = \$this->data['item'] ?? [];
\$isEdit = !empty(\$item['id']);
\$csrfToken = CSRFHelper::generateCSRFToken('sst_{$key}_form');
\$action = \$isEdit ? 'sst-update-{$url}/' . (int)\$item['id'] : 'sst-create-{$url}';
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas {$icon} me-2"></i><?= \$isEdit ? 'Editar' : 'Novo' ?> <?= htmlspecialchars('{$singular}') ?></h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= \$_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= \$_ENV['URL_ADM']; ?>sst-dashboard">SST</a></li>
            <li class="breadcrumb-item"><a href="<?= \$_ENV['URL_ADM']; ?>{$menu}"><?= htmlspecialchars('{$plural}') ?></a></li>
            <li class="breadcrumb-item active"><?= \$isEdit ? 'Editar' : 'Novo' ?></li>
        </ol>
    </div>
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="<?= \$_ENV['URL_ADM']; ?><?= \$action ?>">
                <input type="hidden" name="csrf_token" value="<?= \$csrfToken ?>">
                <div class="row">
                    {$formFields}
                </div>
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Salvar</button>
                    <a href="<?= \$_ENV['URL_ADM']; ?>{$menu}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
PHP;
    writeFile("{$root}/app/adms/Views/sst/{$key}/form.php", $formPhp);

    if (!$hasView) {
        return;
    }

    $viewRows = '';
    foreach ($e['fields'] as $fname => $fdef) {
        $label = $fdef['label'];
        $viewRows .= "<tr><th width=\"35%\">{$label}:</th><td><?= formatCellValue('{$fname}', \$item['{$fname}'] ?? null) ?></td></tr>\n";
    }
    if ($isEmployee) {
        $viewRows .= "<tr><th>Colaborador:</th><td><?= htmlspecialchars(\$item['colaborador_nome'] ?? '-') ?></td></tr>\n";
    }

    $anexosBlock = '';
    if ($hasAnexos) {
        $anexosBlock = <<<'HTML'
            <div class="card mb-4 shadow-sm">
                <div class="card-header"><h5 class="mb-0"><i class="fas fa-paperclip me-2"></i>Anexos</h5></div>
                <div class="card-body">
                    <?php if (empty($this->data['anexos'])): ?>
                        <p class="text-muted mb-0">Nenhum anexo.</p>
                    <?php else: foreach ($this->data['anexos'] as $anexo): ?>
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <span><?= htmlspecialchars($anexo['file_name'] ?? '') ?></span>
                            <small class="text-muted"><?= !empty($anexo['created_at']) ? date('d/m/Y H:i', strtotime($anexo['created_at'])) : '' ?></small>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
HTML;
    }

    $viewPhp = <<<PHP
<?php
\$item = \$this->data['item'];
\$perms = \$this->data['buttonPermission'] ?? [];
function formatCellValue(string \$col, mixed \$value): string {
    if (\$value === null || \$value === '') return '-';
    if (is_bool(\$value) || \$col === 'obrigatorio' || \$col === 'termo_assinado') return (\$value === true || \$value === 1 || \$value === '1') ? 'Sim' : 'Não';
    if (str_contains(\$col, 'data_') && is_string(\$value)) return strlen(\$value) > 10 ? date('d/m/Y H:i', strtotime(\$value)) : date('d/m/Y', strtotime(\$value));
    return htmlspecialchars((string)\$value);
}
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas {$icon} me-2"></i><?= htmlspecialchars('{$singular}') ?> #<?= (int)\$item['id'] ?></h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= \$_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= \$_ENV['URL_ADM']; ?>sst-dashboard">SST</a></li>
            <li class="breadcrumb-item"><a href="<?= \$_ENV['URL_ADM']; ?>{$menu}"><?= htmlspecialchars('{$plural}') ?></a></li>
            <li class="breadcrumb-item active">Visualizar</li>
        </ol>
    </div>
    <div class="card shadow-sm mb-4">
        <div class="card-body d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <h4 class="mb-0">Registro #<?= (int)\$item['id'] ?></h4>
            <div>
                <?php if (in_array('{$updateCtrl}', \$perms)): ?><a href="<?= \$_ENV['URL_ADM']; ?>sst-update-{$url}/<?= (int)\$item['id'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> Editar</a><?php endif; ?>
                <a href="<?= \$_ENV['URL_ADM']; ?>{$menu}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Voltar</a>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4 shadow-sm">
                <div class="card-header"><h5 class="mb-0">Dados</h5></div>
                <div class="card-body"><table class="table table-sm mb-0">
                    {$viewRows}
                    <tr><th>Cadastrado em:</th><td><?= !empty(\$item['created_at']) ? date('d/m/Y H:i', strtotime(\$item['created_at'])) : '-' ?></td></tr>
                    <tr><th>Atualizado em:</th><td><?= !empty(\$item['updated_at']) ? date('d/m/Y H:i', strtotime(\$item['updated_at'])) : '-' ?></td></tr>
                </table></div>
            </div>
            {$anexosBlock}
        </div>
        <div class="col-md-4">
            <?php if (!empty(\$this->data['log_resumo'])): \$log_resumo = \$this->data['log_resumo']; \$log_btn_class = 'btn btn-outline-info w-100 mb-4'; include './app/adms/Views/partials/button_log_alteracoes.php'; endif; ?>
        </div>
    </div>
</div>
PHP;
    writeFile("{$root}/app/adms/Views/sst/{$key}/view.php", $viewPhp);
}
}
