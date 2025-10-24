<?php
use App\adms\Helpers\CSRFHelper;
$csrf_token = CSRFHelper::generateCSRFToken('form_assign_evaluation');
$model = $this->data['model'];
$users = $this->data['users'];
$atribuicoesExistentes = $this->data['atribuicoes_existentes'] ?? [];

// IDs de usuários que já têm atribuição
$idsJaAtribuidos = array_column($atribuicoesExistentes, 'adms_user_id');
?>

<!-- CSS Moderno para Formulários de Avaliação -->
<link rel="stylesheet" href="<?= $_ENV['URL_ADM'] ?>public/adms/css/evaluation-forms-modern.css">

<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">
            <i class="fas fa-user-plus"></i> Atribuir Avaliação
        </h2>
        <ol class="breadcrumb mb-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?= $_ENV['URL_ADM'] ?>list-evaluation-models" class="text-decoration-none">Avaliações</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= $_ENV['URL_ADM'] ?>view-evaluation-model/<?= $model['id'] ?>" class="text-decoration-none">
                    <?= htmlspecialchars($model['titulo']) ?>
                </a>
            </li>
            <li class="breadcrumb-item active">Atribuir</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <!-- INFORMAÇÕES DO MODELO -->
    <div class="card mb-4 border-info shadow">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-clipboard-list"></i> <?= htmlspecialchars($model['titulo']) ?></h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <?php if (!empty($model['descricao'])): ?>
                        <p><?= nl2br(htmlspecialchars($model['descricao'])) ?></p>
                    <?php endif; ?>
                    <p class="mb-1"><strong>Treinamento:</strong> <?= htmlspecialchars($model['training_name'] ?? 'N/A') ?></p>
                    <p class="mb-1"><strong>Nota Mínima:</strong> <?= $model['nota_minima_aprovacao'] ?? '7.00' ?></p>
                </div>
                <div class="col-md-4">
                    <?php if (!empty($atribuicoesExistentes)): ?>
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-users"></i>
                            <strong><?= count($atribuicoesExistentes) ?> atribuição(ões) existente(s)</strong>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- FORMULÁRIO DE ATRIBUIÇÃO -->
    <form method="POST" action="<?= $_ENV['URL_ADM'] ?>assign-evaluation">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        <input type="hidden" name="model_id" value="<?= $model['id'] ?>">

        <div class="card mb-4 shadow">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-users"></i> Selecionar Usuários</h5>
            </div>
            <div class="card-body">
                
                <!-- BUSCA RÁPIDA -->
                <div class="mb-3">
                    <input type="text" id="searchUser" class="form-control" 
                           placeholder="🔍 Buscar usuário por nome ou e-mail...">
                </div>

                <!-- AÇÕES EM LOTE -->
                <div class="mb-3">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="selecionarTodos()">
                        <i class="fas fa-check-double"></i> Selecionar Todos
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselecionarTodos()">
                        <i class="fas fa-times"></i> Desmarcar Todos
                    </button>
                    <span class="ms-3 text-muted" id="countSelecionados">0 selecionado(s)</span>
                </div>

                <!-- LISTA DE USUÁRIOS -->
                <div class="row" id="usersList">
                    <?php foreach ($users as $user): ?>
                        <?php 
                        $jaAtribuido = in_array($user['id'], $idsJaAtribuidos);
                        $ativo = ($user['ativo'] ?? 1) == 1;
                        ?>
                        <div class="col-md-6 col-lg-4 mb-2 user-item" data-user-name="<?= strtolower($user['name']) ?>" data-user-email="<?= strtolower($user['email']) ?>">
                            <div class="form-check border p-3 rounded <?= !$ativo ? 'bg-light' : '' ?>">
                                <input class="form-check-input user-checkbox" type="checkbox" 
                                       name="user_ids[]" value="<?= $user['id'] ?>" 
                                       id="user_<?= $user['id'] ?>"
                                       <?= $jaAtribuido ? 'disabled checked' : '' ?>
                                       <?= !$ativo ? 'disabled' : '' ?>
                                       onchange="atualizarContador()">
                                <label class="form-check-label w-100" for="user_<?= $user['id'] ?>">
                                    <strong><?= htmlspecialchars($user['name']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($user['email']) ?></small>
                                    <?php if ($jaAtribuido): ?>
                                        <br><span class="badge bg-info">Já atribuído</span>
                                    <?php endif; ?>
                                    <?php if (!$ativo): ?>
                                        <br><span class="badge bg-secondary">Inativo</span>
                                    <?php endif; ?>
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div id="noResults" class="alert alert-warning" style="display: none;">
                    <i class="fas fa-search"></i> Nenhum usuário encontrado com este filtro.
                </div>
            </div>
        </div>

        <!-- CONFIGURAÇÕES -->
        <div class="card mb-4 shadow">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-cog"></i> Configurações da Atribuição</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Data Limite para Conclusão</label>
                        <input type="date" name="data_limite" class="form-control" 
                               min="<?= date('Y-m-d') ?>">
                        <small class="text-muted">Deixe vazio se não houver prazo</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- BOTÕES -->
        <div class="text-end mb-4">
            <a href="<?= $_ENV['URL_ADM'] ?>view-evaluation-model/<?= $model['id'] ?>" 
               class="btn btn-secondary btn-lg">
                <i class="fas fa-arrow-left"></i> Cancelar
            </a>
            <button type="submit" class="btn btn-success btn-lg">
                <i class="fas fa-paper-plane"></i> Atribuir Avaliação
            </button>
        </div>
    </form>
</div>

<script>
// Busca em tempo real
document.getElementById('searchUser').addEventListener('input', function() {
    const busca = this.value.toLowerCase();
    const items = document.querySelectorAll('.user-item');
    let visibleCount = 0;

    items.forEach(item => {
        const nome = item.getAttribute('data-user-name');
        const email = item.getAttribute('data-user-email');
        
        if (nome.includes(busca) || email.includes(busca)) {
            item.style.display = 'block';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });

    document.getElementById('noResults').style.display = visibleCount === 0 ? 'block' : 'none';
});

// Selecionar todos
function selecionarTodos() {
    document.querySelectorAll('.user-checkbox:not(:disabled)').forEach(cb => {
        cb.checked = true;
    });
    atualizarContador();
}

// Desselecionar todos
function deselecionarTodos() {
    document.querySelectorAll('.user-checkbox:not(:disabled)').forEach(cb => {
        cb.checked = false;
    });
    atualizarContador();
}

// Atualizar contador
function atualizarContador() {
    const selecionados = document.querySelectorAll('.user-checkbox:checked:not(:disabled)').length;
    document.getElementById('countSelecionados').textContent = `${selecionados} selecionado(s)`;
}

// Atualizar ao carregar
atualizarContador();
</script>

