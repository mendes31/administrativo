<?php
use App\adms\Helpers\FormatHelper;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Currículos / Candidatos</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">RH</li>
            <li class="breadcrumb-item active">Currículos</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2 flex-wrap">
            <span><i class="fas fa-id-card me-2"></i>Listar Currículos / Candidatos</span>
            <span class="ms-auto d-sm-flex flex-row flex-wrap gap-1">
                <a href="<?php echo $_ENV['URL_ADM']; ?>rh-candidatos-create" class="btn btn-success btn-sm mb-1 btn-min-width-90">
                    <i class="fa-solid fa-plus"></i> Cadastrar
                </a>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <form method="GET" class="row g-2 mb-3 align-items-end">
                <div class="col-md-3 mb-2">
                    <label for="nome" class="form-label mb-1">Nome</label>
                    <input type="text" name="nome" id="nome" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($_GET['nome'] ?? '') ?>">
                </div>
                <div class="col-md-3 mb-2">
                    <label for="email" class="form-label mb-1">E-mail</label>
                    <input type="text" name="email" id="email" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($_GET['email'] ?? '') ?>">
                </div>
                <div class="col-md-2 mb-2">
                    <label for="origem" class="form-label mb-1">Origem</label>
                    <?php
                    $origemAtual = $_GET['origem'] ?? '';
                    $origens = [
                        ''                    => 'Todas',
                        'email'               => 'E-mail',
                        'whatsapp'            => 'WhatsApp',
                        'form_trabalhe_conosco' => 'Trabalhe Conosco',
                        'manual'              => 'Manual',
                        'outro'               => 'Outro',
                    ];
                    ?>
                    <select name="origem" id="origem" class="form-select form-select-sm">
                        <?php foreach ($origens as $valor => $label): ?>
                            <option value="<?= $valor ?>" <?= $origemAtual === $valor ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label for="status_processo" class="form-label mb-1">Status do Processo</label>
                    <?php
                    $statusAtual = $_GET['status_processo'] ?? '';
                    $statusLista = [
                        ''              => 'Todos',
                        'recebido'      => 'Recebido',
                        'em_entrevista' => 'Em entrevista',
                        'reprovado'     => 'Reprovado',
                        'banco_talentos'=> 'Banco de talentos',
                        'contratado'    => 'Contratado',
                        'anonimizado'   => 'Anonimizado',
                    ];
                    ?>
                    <select name="status_processo" id="status_processo" class="form-select form-select-sm">
                        <?php foreach ($statusLista as $valor => $label): ?>
                            <option value="<?= $valor ?>" <?= $statusAtual === $valor ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label for="per_page" class="form-label mb-1">Mostrar</label>
                    <div class="d-flex align-items-center">
                        <select name="per_page" id="per_page" class="form-select form-select-sm" style="min-width: 80px;" onchange="this.form.submit()">
                            <?php foreach ([10, 20, 50, 100] as $opt): ?>
                                <option value="<?= $opt ?>" <?= ($_GET['per_page'] ?? 10) == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="form-label mb-1 ms-1">registros</span>
                    </div>
                </div>
                <div class="col-md-2 mb-2 filtros-btns-row w-100 mt-2">
                    <button type="submit" class="btn btn-primary btn-sm btn-filtros-mobile">
                        <i class="fas fa-search me-1"></i>Filtrar
                    </button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>rh-candidatos" class="btn btn-secondary btn-sm btn-filtros-mobile ms-1">
                        <i class="fas fa-times me-1"></i>Limpar
                    </a>
                </div>
            </form>

            <?php if (!empty($this->data['candidatos'])): ?>
                <div class="d-none d-md-block list-desktop">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Nome</th>
                                    <th>E-mail</th>
                                    <th>Origem</th>
                                    <th>Status</th>
                                    <th>LGPD</th>
                                    <th>Cadastrado em</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($this->data['candidatos'] as $cand): ?>
                                    <tr>
                                        <td><?= $cand['id'] ?></td>
                                        <td><?= htmlspecialchars($cand['nome']) ?></td>
                                        <td><?= htmlspecialchars($cand['email'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($cand['origem'] ?? '') ?></td>
                                        <td>
                                            <?php
                                            $st = $cand['status_processo'] ?? '';
                                            $badgeClass = 'badge bg-secondary';
                                            if ($st === 'recebido') $badgeClass = 'badge bg-info text-dark';
                                            elseif ($st === 'em_entrevista') $badgeClass = 'badge bg-warning text-dark';
                                            elseif ($st === 'reprovado') $badgeClass = 'badge bg-danger';
                                            elseif ($st === 'banco_talentos') $badgeClass = 'badge bg-primary';
                                            elseif ($st === 'contratado') $badgeClass = 'badge bg-success';
                                            elseif ($st === 'anonimizado') $badgeClass = 'badge bg-dark';
                                            ?>
                                            <span class="<?= $badgeClass ?>">
                                                <?= htmlspecialchars($st) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $lgpdStatus = $cand['lgpd_status'] ?? 'Ativo';
                                            $lgpdBadge  = 'badge bg-secondary';
                                            if ($lgpdStatus === 'Ativo') {
                                                // Verificar se está próximo de expirar (30 dias)
                                                $prox = false;
                                                if (!empty($cand['lgpd_data_expiracao']) && $cand['lgpd_data_expiracao'] !== '0000-00-00 00:00:00') {
                                                    try {
                                                        $exp = new DateTime($cand['lgpd_data_expiracao']);
                                                        $hoje = new DateTime();
                                                        $diff = (int)$hoje->diff($exp)->format('%r%a');
                                                        if ($diff >= 0 && $diff <= 30) {
                                                            $prox = true;
                                                        }
                                                    } catch (\Throwable $e) {
                                                        $prox = false;
                                                    }
                                                }
                                                if ($prox) {
                                                    $lgpdBadge = 'badge bg-warning text-dark';
                                                    $lgpdLabel = 'Próx. expiração';
                                                } else {
                                                    $lgpdBadge = 'badge bg-success';
                                                    $lgpdLabel = 'Ativo';
                                                }
                                            } elseif ($lgpdStatus === 'Anonimizado') {
                                                $lgpdBadge = 'badge bg-dark';
                                                $lgpdLabel = 'Anonimizado';
                                            } else {
                                                // Qualquer outro status é tratado como vencido
                                                $lgpdBadge = 'badge bg-danger';
                                                $lgpdLabel = 'Vencido';
                                            }
                                            ?>
                                            <span class="<?= $lgpdBadge ?>">
                                                <?= htmlspecialchars($lgpdLabel ?? $lgpdStatus) ?>
                                            </span>
                                        </td>
                                        <td><?= FormatHelper::formatDate($cand['data_cadastramento'] ?? '') ?></td>
                                        <td>
                                            <div class="btn-group tabela-acoes" role="group">
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>rh-candidatos-view/<?= $cand['id'] ?>" class="btn btn-info btn-sm" title="Visualizar">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>rh-candidatos-edit/<?= $cand['id'] ?>" class="btn btn-warning btn-sm" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button"
                                                        class="btn btn-danger btn-sm"
                                                        title="Excluir"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#modalDeleteCandidato"
                                                        data-id="<?= $cand['id'] ?>"
                                                        data-nome="<?= htmlspecialchars($cand['nome']) ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Paginação -->
                <div class="w-100 mt-2 d-flex justify-content-between align-items-center flex-wrap">
                    <div class="text-secondary small mb-2">
                        Exibindo <?= count($this->data['candidatos']) ?> registro(s) nesta página.
                    </div>
                    <div>
                        <?= $this->data['paginator'] ?? '' ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>
                    Nenhum candidato encontrado.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal exclusão com senha + justificativa -->
<div class="modal fade" id="modalDeleteCandidato" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Excluir candidato</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-2">
            Informe sua senha e uma justificativa para excluir o candidato:
            <strong id="delete-cand-nome"></strong>
        </p>
        <form id="formDeleteCandidato">
            <input type="hidden" name="id" id="delete-cand-id">
            <div class="mb-2">
                <label for="delete-motivo" class="form-label mb-1">Justificativa</label>
                <textarea name="motivo" id="delete-motivo" class="form-control" rows="2" required></textarea>
            </div>
            <div class="mb-2">
                <label for="delete-password" class="form-label mb-1">Senha</label>
                <input type="password" name="password" id="delete-password" class="form-control" required>
            </div>
            <div class="alert alert-danger d-none mt-2" id="delete-error"></div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger btn-sm" id="btn-confirm-delete-candidato">
            <i class="fas fa-trash me-1"></i>Excluir
        </button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('modalDeleteCandidato');
    if (!modal) return;

    modal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var id = button.getAttribute('data-id');
        var nome = button.getAttribute('data-nome');

        document.getElementById('delete-cand-id').value = id;
        document.getElementById('delete-cand-nome').textContent = nome;
        document.getElementById('delete-motivo').value = '';
        document.getElementById('delete-password').value = '';
        var err = document.getElementById('delete-error');
        err.classList.add('d-none');
        err.textContent = '';
    });

    var btnConfirm = document.getElementById('btn-confirm-delete-candidato');
    btnConfirm.addEventListener('click', function () {
        var form = document.getElementById('formDeleteCandidato');
        var id = document.getElementById('delete-cand-id').value;
        var formData = new FormData(form);

        fetch('<?php echo $_ENV['URL_ADM']; ?>rh-candidatos-delete/' + id, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(resp => resp.json())
        .then(json => {
            if (json.success) {
                window.location.reload();
            } else {
                var err = document.getElementById('delete-error');
                err.textContent = json.message || 'Erro ao excluir candidato.';
                err.classList.remove('d-none');
            }
        })
        .catch(() => {
            var err = document.getElementById('delete-error');
            err.textContent = 'Erro inesperado ao excluir candidato.';
            err.classList.remove('d-none');
        });
    });
});
</script>


