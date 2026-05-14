<?php

use App\adms\Helpers\CSRFHelper;

$connections = $this->data['connections'] ?? [];
$editRow = $this->data['edit_row'] ?? null;
$csrfList = $this->data['csrf_token_list'] ?? CSRFHelper::generateCSRFToken('form_sap_sl_conn_list');
$csrfForm = $this->data['csrf_token_form'] ?? CSRFHelper::generateCSRFToken('form_sap_sl_conn_save');
$isEdit = is_array($editRow) && !empty($editRow['id']);
?>

<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="mb-1 hstack gap-2 flex-wrap">
        <h2 class="mt-3">
            <i class="fas fa-cloud me-2"></i>API SAP (integração)
        </h2>
        <div class="ms-auto d-flex flex-wrap gap-2 align-items-center mb-3 mt-3">
            <?php
            $log_resumo = $this->data['log_resumo'] ?? [];
            $log_btn_class = 'btn btn-outline-info btn-sm';
            include __DIR__ . '/../partials/button_log_alteracoes.php';
            ?>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= htmlspecialchars($_ENV['URL_ADM'], ENT_QUOTES, 'UTF-8'); ?>dashboard">Dashboard</a></li>
                <li class="breadcrumb-item active">API SAP (integração)</li>
            </ol>
        </div>
    </div>

    <p class="text-muted mb-4">
        O administrativo <strong>não</strong> liga directamente à Service Layer do SAP B1: liga à <strong>sua API</strong> (gateway),
        que por sua vez trata da autenticação e chamadas ao B1. Aqui regista uma ou mais <strong>conexões nomeadas</strong>
        (URL da API, caminho de health-check, credenciais que a API espera). O código PHP usa a conexão <strong>padrão</strong> quando não indica outra.
    </p>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Conexões</h5>
                    <?php if ($isEdit) { ?>
                        <a href="<?= htmlspecialchars($_ENV['URL_ADM'] . 'sap-service-layer-connections', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-light">+ Nova conexão</a>
                    <?php } ?>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Nome</th>
                                    <th>URL base</th>
                                    <th>Health</th>
                                    <th>Extra / tenant</th>
                                    <th>Utilizador API</th>
                                    <th class="text-center">Activa</th>
                                    <th class="text-center">Padrão</th>
                                    <th class="text-end">Acções</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($connections === []) { ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">Nenhuma conexão cadastrada. Utilize o formulário abaixo.</td>
                                    </tr>
                                <?php } else { ?>
                                    <?php foreach ($connections as $row) {
                                        $rid = (int) ($row['id'] ?? 0);
                                        ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars((string) ($row['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                            <td class="small text-break"><?= htmlspecialchars((string) ($row['base_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="small"><?= htmlspecialchars((string) ($row['health_path'] ?? '/health'), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="small text-muted"><?= htmlspecialchars((string) ($row['company_db'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?= htmlspecialchars((string) ($row['username'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="text-center"><?= !empty($row['is_active']) ? '<span class="badge bg-success">Sim</span>' : '<span class="badge bg-secondary">Não</span>'; ?></td>
                                            <td class="text-center"><?= !empty($row['is_default']) ? '<span class="badge bg-primary">Sim</span>' : '—'; ?></td>
                                            <td class="text-end text-nowrap">
                                                <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($_ENV['URL_ADM'] . 'sap-service-layer-connections?edit=' . $rid, ENT_QUOTES, 'UTF-8'); ?>">Editar</a>
                                                <?php if (in_array('TestSapServiceLayerConnection', $this->data['buttonPermission'] ?? [], true)) { ?>
                                                    <form class="d-inline" method="post" action="<?= htmlspecialchars($_ENV['URL_ADM'] . 'test-sap-service-layer-connection', ENT_QUOTES, 'UTF-8'); ?>" onsubmit="return confirm('Chamar o GET de health na API (URL base + caminho configurado)?');">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfList, ENT_QUOTES, 'UTF-8'); ?>">
                                                        <input type="hidden" name="connection_id" value="<?= $rid; ?>">
                                                        <button type="submit" class="btn btn-sm btn-warning">Testar</button>
                                                    </form>
                                                <?php } ?>
                                                <?php if (in_array('DeleteSapServiceLayerConnection', $this->data['buttonPermission'] ?? [], true)) { ?>
                                                    <form class="d-inline" method="post" action="<?= htmlspecialchars($_ENV['URL_ADM'] . 'delete-sap-service-layer-connection', ENT_QUOTES, 'UTF-8'); ?>" onsubmit="return confirm('Remover esta conexão?');">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfList, ENT_QUOTES, 'UTF-8'); ?>">
                                                        <input type="hidden" name="connection_id" value="<?= $rid; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                                                    </form>
                                                <?php } ?>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-<?= $isEdit ? 'edit' : 'plus-circle'; ?> me-2"></i><?= $isEdit ? 'Editar conexão' : 'Nova conexão'; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!in_array('SaveSapServiceLayerConnection', $this->data['buttonPermission'] ?? [], true)) { ?>
                        <p class="text-muted mb-0">Sem permissão para gravar conexões.</p>
                    <?php } else { ?>
                        <form method="post" action="<?= htmlspecialchars($_ENV['URL_ADM'] . 'save-sap-service-layer-connection', ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfForm, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php if ($isEdit) { ?>
                                <input type="hidden" name="id" value="<?= (int) $editRow['id']; ?>">
                            <?php } ?>

                            <div class="mb-3">
                                <label class="form-label">Nome (identificação) *</label>
                                <input type="text" name="name" class="form-control" required maxlength="191"
                                       placeholder="Ex.: Portal vendas — produção"
                                       value="<?= htmlspecialchars((string) ($editRow['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">URL base da sua API *</label>
                                <input type="url" name="base_url" class="form-control" required
                                       placeholder="https://api.suaempresa.com/v1"
                                       value="<?= htmlspecialchars((string) ($editRow['base_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="form-text">Raiz do serviço que o administrativo chama (não use URL do <code>b1s/v1</code> aqui, a menos que a própria API o exponha).</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Caminho de health-check (GET)</label>
                                <input type="text" name="health_path" class="form-control" maxlength="191"
                                       placeholder="/health"
                                       value="<?= htmlspecialchars((string) ($editRow['health_path'] ?? '/health'), ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="form-text">O botão «Testar» faz <code>GET</code> em <code>URL base + este caminho</code> (por defeito <code>/health</code>).</div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Identificador extra / tenant (opcional)</label>
                                    <input type="text" name="company_db" class="form-control"
                                           placeholder="Ex.: nome da empresa no gateway"
                                           value="<?= htmlspecialchars((string) ($editRow['company_db'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                    <div class="form-text">Texto livre se a sua API precisar de contexto (não é o CompanyDB do B1 no PHP).</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Utilizador da API (opcional)</label>
                                    <input type="text" name="username" class="form-control" autocomplete="off"
                                           placeholder="Client ID ou utilizador Basic"
                                           value="<?= htmlspecialchars((string) ($editRow['username'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                    <div class="form-text">Se preencher juntamente com o segredo abaixo, o teste usa <strong>Basic Auth</strong>.</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Token ou segredo<?= $isEdit ? '' : ' *'; ?></label>
                                <div class="input-group">
                                    <input type="password" name="password" id="sap_sl_password" class="form-control"
                                           <?= $isEdit ? '' : 'required'; ?> autocomplete="new-password"
                                           value="">
                                    <button class="btn btn-outline-secondary" type="button" onclick="toggleSlPw(this)"><i class="fas fa-eye"></i></button>
                                </div>
                                <div class="form-text">
                                    <?php if ($isEdit) { ?>
                                        Deixe em branco para <strong>manter</strong> o token actual.
                                        <?php if (!empty($editRow['password'])) { ?><span class="text-success">(já existe segredo gravado)</span><?php } ?>
                                    <?php } else { ?>
                                        Com utilizador vazio, o teste envia <code>Authorization: Bearer &lt;valor&gt;</code>. Com utilizador, usa Basic Auth.
                                    <?php } ?>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Ordem</label>
                                    <input type="number" name="sort_order" class="form-control" step="1" value="<?= htmlspecialchars((string) ($editRow['sort_order'] ?? '0'), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="col-md-8 mb-3 d-flex flex-column justify-content-end">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_active" id="sl_is_active" value="1"
                                            <?= ($isEdit ? !empty($editRow['is_active']) : true) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="sl_is_active">Conexão activa</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_default" id="sl_is_default" value="1"
                                            <?= ($isEdit && !empty($editRow['is_default'])) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="sl_is_default">Usar como conexão padrão quando o código não especifica outra</label>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-2"></i><?= $isEdit ? 'Guardar alterações' : 'Criar conexão'; ?>
                            </button>
                        </form>
                    <?php } ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Notas</h6>
                </div>
                <div class="card-body small">
                    <ul class="mb-0">
                        <li>A API HTTP de <strong>relatórios SQL</strong> continua em <strong>Configuração SAP API</strong>.</li>
                        <li>Para chamadas de negócio (pedidos, PN, etc.), o PHP deve usar <strong>HTTP à sua API</strong>; a classe <code>SapGatewayHttpClient</code> no projecto mostra o padrão do teste (GET health).</li>
                        <li>Guarde credenciais com o mesmo critério de segurança que para a API de relatórios (acesso à base e backups).</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleSlPw(btn) {
    const input = document.getElementById('sap_sl_password');
    const icon = btn.querySelector('i');
    if (!input) return;
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>
