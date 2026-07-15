<?php
use App\adms\Helpers\CSRFHelper;

$mapping = $this->data['mapping'] ?? null;
$mappable = $this->data['mappable_fields'] ?? [];
$suggested = $this->data['suggested_map'] ?? [];
$hasMapping = is_array($mapping) && !empty($mapping['headers']);

/** @var array<string, int> $suggestedByField colIdx sugerido por campo */
$suggestedByField = [];
if (is_array($suggested)) {
    foreach ($suggested as $colIdx => $field) {
        $field = (string)$field;
        if ($field !== '' && !isset($suggestedByField[$field])) {
            $suggestedByField[$field] = (int)$colIdx;
        }
    }
}
?>

<div class="container-fluid px-4">
    <div class="mb-1 d-flex flex-column flex-sm-row gap-2">
        <h2 class="mt-3">Usuários</h2>
        <ol class="breadcrumb mb-3 mt-0 mt-sm-3 ms-auto">
            <li class="breadcrumb-item"><a class="text-decoration-none" href="<?php echo $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a class="text-decoration-none" href="<?php echo $_ENV['URL_ADM']; ?>list-users">Usuários</a></li>
            <li class="breadcrumb-item">Importar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span>Importar Usuários via planilha</span>
            <span class="ms-auto d-sm-flex flex-row">
                <a href="<?php echo $_ENV['URL_ADM']; ?>import-users/template" class="btn btn-outline-secondary btn-sm me-1 mb-1">
                    <i class="fa-solid fa-download"></i> Baixar Template
                </a>
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-users" class="btn btn-info btn-sm me-1 mb-1"><i class="fa-solid fa-list"></i> Listar</a>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <?php if (!empty($this->data['errors'])): ?>
                <div class="alert alert-danger">
                    <?php foreach ($this->data['errors'] as $err): ?>
                        <div><?php echo htmlspecialchars((string)$err, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($hasMapping):
                $headers = $mapping['headers'] ?? [];
                $letters = $mapping['letters'] ?? [];
                $preview0 = $mapping['preview'][0] ?? [];
                $keyGuess = '';
                foreach (['cpf', 'username', 'id'] as $k) {
                    if (isset($suggestedByField[$k])) {
                        $keyGuess = $k;
                        break;
                    }
                }
                ?>
                <div class="alert alert-info small">
                    Arquivo: <strong><?php echo htmlspecialchars((string)($mapping['original_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                    (<?php echo count($headers); ?> colunas).
                    Para cada campo do sistema, escolha a coluna do arquivo — ou deixe em “não importar”.
                </div>

                <form method="POST" class="row g-3">
                    <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_import_users_map'); ?>">
                    <input type="hidden" name="apply_mapping" value="1">

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Chave única</label>
                        <select name="key_field" class="form-select" required>
                            <option value="cpf" <?php echo $keyGuess === 'cpf' ? 'selected' : ''; ?>>CPF</option>
                            <option value="username" <?php echo $keyGuess === 'username' || $keyGuess === '' ? 'selected' : ''; ?>>Usuário / login</option>
                            <option value="id" <?php echo $keyGuess === 'id' ? 'selected' : ''; ?>>ID do usuário</option>
                        </select>
                        <div class="form-text">A coluna dessa chave precisa estar associada abaixo.</div>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Método de importação</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="import_method" id="method_update" value="update_only" checked>
                            <label class="form-check-label" for="method_update">Somente atualizar existentes</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="import_method" id="method_upsert" value="upsert">
                            <label class="form-check-label" for="method_upsert">Adicionar novos e atualizar existentes</label>
                        </div>
                        <div class="form-text">Para criar novos, associe também nome, e-mail, usuário, departamento e cargo.</div>
                    </div>

                    <div class="col-12">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 28%;">Campo do sistema</th>
                                        <th style="width: 42%;">Coluna no arquivo</th>
                                        <th>Exemplo (1ª linha)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    foreach ($mappable as $field => $label):
                                        if ($field === '') {
                                            continue;
                                        }
                                        $selectedCol = $suggestedByField[$field] ?? '';
                                        ?>
                                        <tr>
                                            <td>
                                                <label class="form-label mb-0 fw-semibold" for="field_map_<?php echo htmlspecialchars((string)$field, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <?php echo htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8'); ?>
                                                </label>
                                            </td>
                                            <td>
                                                <select
                                                    id="field_map_<?php echo htmlspecialchars((string)$field, ENT_QUOTES, 'UTF-8'); ?>"
                                                    name="field_map[<?php echo htmlspecialchars((string)$field, ENT_QUOTES, 'UTF-8'); ?>]"
                                                    class="form-select form-select-sm js-import-field-map"
                                                >
                                                    <option value="">(não importar)</option>
                                                    <?php foreach ($headers as $i => $headerName):
                                                        $letter = (string)($letters[$i] ?? chr(65 + min((int)$i, 25)));
                                                        $optLabel = $letter . ' — ' . (string)$headerName;
                                                        ?>
                                                        <option
                                                            value="<?php echo (int)$i; ?>"
                                                            data-example="<?php echo htmlspecialchars((string)($preview0[$i] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                            <?php echo $selectedCol !== '' && (int)$selectedCol === (int)$i ? 'selected' : ''; ?>
                                                        >
                                                            <?php echo htmlspecialchars($optLabel, ENT_QUOTES, 'UTF-8'); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td class="text-muted small js-import-example">
                                                <?php
                                                if ($selectedCol !== '' && isset($preview0[(int)$selectedCol])) {
                                                    $ex = (string)$preview0[(int)$selectedCol];
                                                    echo htmlspecialchars(mb_strlen($ex) > 60 ? mb_substr($ex, 0, 57) . '…' : $ex, ENT_QUOTES, 'UTF-8');
                                                } else {
                                                    echo '—';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="col-12 d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-file-import me-1"></i>Importar com mapeamento
                        </button>
                        <a href="<?php echo $_ENV['URL_ADM']; ?>import-users/cancel-map" class="btn btn-outline-secondary">Cancelar mapeamento</a>
                    </div>
                </form>
                <script>
                (function () {
                    var selects = Array.prototype.slice.call(document.querySelectorAll('.js-import-field-map'));

                    function updateExample(sel) {
                        var opt = sel.options[sel.selectedIndex];
                        var cell = sel.closest('tr') && sel.closest('tr').querySelector('.js-import-example');
                        if (!cell) return;
                        var ex = opt && opt.getAttribute('data-example') ? opt.getAttribute('data-example') : '';
                        cell.textContent = ex ? (ex.length > 60 ? ex.slice(0, 57) + '…' : ex) : '—';
                    }

                    function refreshAvailableColumns() {
                        var used = {};
                        selects.forEach(function (sel) {
                            var v = sel.value;
                            if (v !== '') {
                                used[v] = sel;
                            }
                        });

                        selects.forEach(function (sel) {
                            Array.prototype.forEach.call(sel.options, function (opt) {
                                if (opt.value === '') {
                                    opt.disabled = false;
                                    opt.hidden = false;
                                    return;
                                }
                                var takenByOther = used[opt.value] && used[opt.value] !== sel;
                                opt.disabled = !!takenByOther;
                                opt.hidden = !!takenByOther;
                            });
                        });
                    }

                    selects.forEach(function (sel) {
                        sel.addEventListener('change', function () {
                            updateExample(sel);
                            refreshAvailableColumns();
                        });
                    });

                    refreshAvailableColumns();
                })();
                </script>

            <?php else: ?>

                <div class="alert alert-primary small mb-3">
                    <ol class="mb-0 ps-3">
                        <li>Escolha o arquivo Excel (<strong>.xlsx</strong>) ou CSV.</li>
                        <li>Clique em <strong>Abrir mapeamento</strong>.</li>
                        <li>Na tela seguinte, para cada campo (CPF, nome, matrícula…), escolha a coluna do arquivo.</li>
                        <li>Confirme a importação.</li>
                    </ol>
                </div>

                <form action="" method="POST" enctype="multipart/form-data" class="row g-3">
                    <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_import_users'); ?>">

                    <div class="col-12">
                        <label class="form-label fw-semibold">Arquivo</label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv" required>
                        <small class="text-muted">Recomendado: <strong>Salvar como → Pasta de Trabalho do Excel (.xlsx)</strong> e enviar esse arquivo. CSV também é aceito.</small>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Modo</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="import_mode" id="mode_map" value="map" checked>
                            <label class="form-check-label" for="mode_map">
                                <strong>Mapeamento de colunas</strong> — escolher quais campos importar
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="import_mode" id="mode_template" value="template">
                            <label class="form-check-label" for="mode_template">
                                Template oficial — cabeçalhos fixos (use “Baixar Template”)
                            </label>
                        </div>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-success">
                            <i class="fa-solid fa-table-columns me-1"></i>Abrir mapeamento
                        </button>
                    </div>
                </form>
            <?php endif; ?>

            <?php if (!empty($this->data['summary'])):
                $s = $this->data['summary'];
                $acaoLabel = [
                    'criado' => 'Criado',
                    'atualizado' => 'Atualizado',
                    'ignorado' => 'Ignorado',
                    'erro' => 'Erro',
                    'aviso' => 'Aviso',
                ];
                $acaoClass = [
                    'criado' => 'success',
                    'atualizado' => 'primary',
                    'ignorado' => 'secondary',
                    'erro' => 'danger',
                    'aviso' => 'warning',
                ];
                ?>
                <hr>
                <h5 class="mb-2">Resultado da importação</h5>
                <p class="text-muted small mb-2">
                    Totais do processamento. A tabela abaixo detalha cada linha da planilha (a linha 1 é o cabeçalho).
                </p>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge text-bg-success">Criados: <?php echo (int)$s['created']; ?></span>
                    <span class="badge text-bg-primary">Atualizados: <?php echo (int)$s['updated']; ?></span>
                    <?php if (isset($s['skipped'])): ?>
                        <span class="badge text-bg-secondary">Ignorados: <?php echo (int)$s['skipped']; ?></span>
                    <?php endif; ?>
                    <span class="badge text-bg-danger">Erros: <?php echo (int)$s['errors']; ?></span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th title="Número da linha na planilha (1 = cabeçalho)">Linha na planilha</th>
                                <th>Resultado</th>
                                <th>Usuário / e-mail</th>
                                <th>Detalhe</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (($this->data['report'] ?? []) as $r):
                                $acao = (string)($r['acao'] ?? '');
                                $label = $acaoLabel[$acao] ?? $acao;
                                $badge = $acaoClass[$acao] ?? 'secondary';
                                $who = trim((string)($r['usuario'] ?? ''));
                                $email = trim((string)($r['email'] ?? ''));
                                if ($who !== '' && $email !== '') {
                                    $ident = $who . ' — ' . $email;
                                } else {
                                    $ident = $who !== '' ? $who : ($email !== '' ? $email : '—');
                                }
                                $msg = trim((string)($r['msg'] ?? ''));
                                if ($msg === '' && in_array($acao, ['criado', 'atualizado'], true)) {
                                    $msg = $acao === 'criado' ? 'Usuário criado.' : 'Cadastro atualizado.';
                                }
                                ?>
                            <tr>
                                <td><?php echo htmlspecialchars((string)($r['linha'] ?? '')); ?></td>
                                <td><span class="badge text-bg-<?php echo htmlspecialchars($badge, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td><?php echo htmlspecialchars($ident, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($msg !== '' ? $msg : '—', ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
