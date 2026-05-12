<?php

use App\adms\Helpers\CSRFHelper;

$settings = $this->data['settings'] ?? [
    'week_start_day' => 1,
    'weekend_start_day' => 6,
    'weekend_end_day' => 7,
    'valid_for_one_year' => 1,
];
$year = (int)($this->data['year'] ?? date('Y'));
$holidays = $this->data['holidays'] ?? [];
$csrfToken = CSRFHelper::generateCSRFToken('form_calendar_config');

$weekDays = [
    1 => 'Segunda-feira',
    2 => 'Terça-feira',
    3 => 'Quarta-feira',
    4 => 'Quinta-feira',
    5 => 'Sexta-feira',
    6 => 'Sábado',
    7 => 'Domingo',
];
?>

<div class="container-fluid px-4">

    <?php include __DIR__ . '/../partials/alerts.php'; ?>

    <div class="mb-1 hstack gap-2 flex-wrap">
        <h2 class="mt-3">Calendário - Feriados e Dias Úteis</h2>

        <div class="ms-auto d-flex flex-wrap gap-2 align-items-center mb-3 mt-3">
            <?php
            $log_resumo = $this->data['log_resumo'] ?? [];
            $log_btn_class = 'btn btn-outline-info btn-sm';
            include __DIR__ . '/../partials/button_log_alteracoes.php';
            ?>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Administração</li>
            <li class="breadcrumb-item">Configurações</li>
            <li class="breadcrumb-item active">Calendário</li>
        </ol>
        </div>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span>Configuração de Semana e Feriados</span>
        </div>

        <div class="card-body">
            <form action="" method="POST" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

                <div class="col-12 col-md-4">
                    <label for="year" class="form-label">Ano</label>
                    <input type="number" name="year" id="year" class="form-control"
                           min="2000" max="2100"
                           value="<?php echo htmlspecialchars((string)$year); ?>">
                    <small class="text-muted">Os feriados cadastrados serão associados a este ano.</small>
                </div>

                <div class="col-12 col-md-8">
                    <label class="form-label d-block">Numeração da semana</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="week_start_day" id="week_start_monday"
                               value="1" <?php echo ((int)$settings['week_start_day'] === 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="week_start_monday">
                            A primeira semana começa na segunda-feira (padrão)
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="week_start_day" id="week_start_sunday"
                               value="7" <?php echo ((int)$settings['week_start_day'] === 7) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="week_start_sunday">
                            A primeira semana começa no domingo
                        </label>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label d-block">Fim de semana de</label>
                    <div class="row g-2 align-items-center">
                        <div class="col-6">
                            <select name="weekend_start_day" id="weekend_start_day" class="form-select">
                                <?php foreach ($weekDays as $val => $label): ?>
                                    <option value="<?php echo $val; ?>" <?php echo ((int)$settings['weekend_start_day'] === $val) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-1 text-center">
                            até
                        </div>
                        <div class="col-5">
                            <select name="weekend_end_day" id="weekend_end_day" class="form-select">
                                <?php foreach ($weekDays as $val => $label): ?>
                                    <option value="<?php echo $val; ?>" <?php echo ((int)$settings['weekend_end_day'] === $val) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <small class="text-muted">
                        Estes dias serão sempre considerados como não úteis no cálculo de programação.
                    </small>
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label d-block">Opções</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="valid_for_one_year"
                               name="valid_for_one_year" value="1"
                            <?php echo !empty($settings['valid_for_one_year']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="valid_for_one_year">
                            Válido por apenas um ano
                        </label>
                    </div>
                </div>

                <div class="col-12">
                    <hr>
                    <h5>Feriados</h5>
                    <p class="text-muted mb-2">
                        Cadastre os feriados que devem ser desconsiderados no cálculo de dias úteis para o ano selecionado.
                    </p>
                </div>

                <div class="col-12">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle" id="holidays-table">
                            <thead class="table-light">
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th style="min-width: 120px;">Data início</th>
                                <th style="min-width: 120px;">Data término</th>
                                <th style="min-width: 220px;">Nome / Observações</th>
                                <th style="width: 80px;" class="text-end">Ações</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if ($holidays): ?>
                                <?php foreach ($holidays as $idx => $h): ?>
                                    <tr>
                                        <td>
                                            <?php echo $idx + 1; ?>
                                            <input type="hidden" name="holiday_id[<?php echo $idx; ?>]"
                                                   value="<?php echo (int)$h['id']; ?>">
                                        </td>
                                        <td>
                                            <input type="date" name="holiday_start_date[<?php echo $idx; ?>]"
                                                   class="form-control form-control-sm"
                                                   value="<?php echo htmlspecialchars((string)$h['start_date']); ?>">
                                        </td>
                                        <td>
                                            <input type="date" name="holiday_end_date[<?php echo $idx; ?>]"
                                                   class="form-control form-control-sm"
                                                   value="<?php echo htmlspecialchars((string)($h['end_date'] ?? '')); ?>">
                                        </td>
                                        <td>
                                            <input type="text" name="holiday_name[<?php echo $idx; ?>]"
                                                   class="form-control form-control-sm mb-1"
                                                   placeholder="Nome do feriado"
                                                   value="<?php echo htmlspecialchars((string)$h['name']); ?>">
                                            <input type="text" name="holiday_observations[<?php echo $idx; ?>]"
                                                   class="form-control form-control-sm"
                                                   placeholder="Observações (opcional)"
                                                   value="<?php echo htmlspecialchars((string)($h['observations'] ?? '')); ?>">
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                    onclick="removeHolidayRow(this)">Remover
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addHolidayRow()">
                        Adicionar feriado
                    </button>
                </div>

                <div class="col-12 mt-3">
                    <button type="submit" class="btn btn-primary btn-sm">Salvar</button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
function addHolidayRow() {
    var tbody = document.querySelector('#holidays-table tbody');
    if (!tbody) return;
    var idx = tbody.querySelectorAll('tr').length;
    var tr = document.createElement('tr');
    tr.innerHTML =
        '<td>' + (idx + 1) +
        '<input type="hidden" name="holiday_id[' + idx + ']" value="">' +
        '</td>' +
        '<td><input type="date" name="holiday_start_date[' + idx + ']" class="form-control form-control-sm"></td>' +
        '<td><input type="date" name="holiday_end_date[' + idx + ']" class="form-control form-control-sm"></td>' +
        '<td>' +
        '<input type="text" name="holiday_name[' + idx + ']" class="form-control form-control-sm mb-1" placeholder="Nome do feriado">' +
        '<input type="text" name="holiday_observations[' + idx + ']" class="form-control form-control-sm" placeholder="Observações (opcional)">' +
        '</td>' +
        '<td class="text-end">' +
        '<button type="button" class="btn btn-sm btn-outline-danger" onclick="removeHolidayRow(this)">Remover</button>' +
        '</td>';
    tbody.appendChild(tr);
}

function removeHolidayRow(btn) {
    var row = btn.closest('tr');
    if (!row) return;
    row.remove();
    renumberHolidayRows();
}

function renumberHolidayRows() {
    var tbody = document.querySelector('#holidays-table tbody');
    if (!tbody) return;
    var rows = tbody.querySelectorAll('tr');
    rows.forEach(function (row, idx) {
        row.cells[0].innerText = (idx + 1).toString();
        var idInput = row.querySelector('input[name^="holiday_id"]');
        var startInput = row.querySelector('input[name^="holiday_start_date"]');
        var endInput = row.querySelector('input[name^="holiday_end_date"]');
        var nameInput = row.querySelector('input[name^="holiday_name"]');
        var obsInput = row.querySelector('input[name^="holiday_observations"]');
        if (idInput) idInput.name = 'holiday_id[' + idx + ']';
        if (startInput) startInput.name = 'holiday_start_date[' + idx + ']';
        if (endInput) endInput.name = 'holiday_end_date[' + idx + ']';
        if (nameInput) nameInput.name = 'holiday_name[' + idx + ']';
        if (obsInput) obsInput.name = 'holiday_observations[' + idx + ']';
    });
}
</script>

