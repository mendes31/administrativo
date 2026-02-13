<?php
use App\adms\Helpers\FormatHelper;
use App\adms\Helpers\CSRFHelper;

$csrfTokenStatus = CSRFHelper::generateCSRFToken('form_rh_atualizar_status_candidatura');

$vaga = $this->data['vaga'] ?? [];
$candidatos = $this->data['candidatos'] ?? [];

// Agrupar candidatos por status do vínculo
$colunas = [
    'candidatado' => [
        'titulo' => 'Candidatado',
        'classe' => 'bg-light',
        'itens'  => [],
    ],
    'em_entrevista' => [
        'titulo' => 'Em Entrevista',
        'classe' => 'bg-warning-subtle',
        'itens'  => [],
    ],
    'aprovado' => [
        'titulo' => 'Aprovado',
        'classe' => 'bg-success-subtle',
        'itens'  => [],
    ],
    'reprovado' => [
        'titulo' => 'Reprovado',
        'classe' => 'bg-danger-subtle',
        'itens'  => [],
    ],
    'desistiu' => [
        'titulo' => 'Desistiu',
        'classe' => 'bg-secondary-subtle',
        'itens'  => [],
    ],
];

foreach ($candidatos as $cand) {
    $status = $cand['status'] ?? 'candidatado';
    // Normalizar valor legado para exibição
    if ($status === 'em_analise') {
        $status = 'em_entrevista';
    }
    if (!isset($colunas[$status])) {
        $status = 'candidatado';
    }
    $colunas[$status]['itens'][] = $cand;
}
?>

<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Pipeline da Vaga (Kanban)</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">RH</li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>rh-vagas" class="text-decoration-none">Vagas</a>
            </li>
            <li class="breadcrumb-item active">Pipeline</li>
        </ol>
    </div>

    <div class="card mb-3 border-light shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <strong>Vaga #<?= (int)($vaga['id'] ?? 0) ?></strong> -
                <?= htmlspecialchars($vaga['titulo'] ?? '') ?>
            </div>
            <div class="btn-group">
                <a href="<?php echo $_ENV['URL_ADM']; ?>rh-vagas-view/<?= (int)($vaga['id'] ?? 0) ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Voltar para detalhes
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <div class="row g-3" id="kanban-board"
                 data-vaga-id="<?= (int)($vaga['id'] ?? 0) ?>"
                 data-csrf="<?= $csrfTokenStatus ?>">
                <?php foreach ($colunas as $statusKey => $coluna): ?>
                    <div class="col-12 col-md-6 col-xl">
                        <div class="card h-100">
                            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                <span><?= htmlspecialchars($coluna['titulo']) ?></span>
                                <span class="badge bg-dark-subtle text-dark count-coluna" data-status="<?= $statusKey ?>">
                                    <?= count($coluna['itens']) ?>
                                </span>
                            </div>
                            <div class="card-body <?= $coluna['classe'] ?> p-2">
                                <div class="kanban-column"
                                     data-status="<?= $statusKey ?>"
                                     ondragover="kanbanAllowDrop(event)"
                                     ondrop="kanbanDrop(event)">
                                    <?php foreach ($coluna['itens'] as $cand): ?>
                                        <div class="kanban-card mb-2"
                                             draggable="<?= !empty($this->data['can_manage_pipeline']) ? 'true' : 'false' ?>"
                                             ondragstart="kanbanDrag(event)"
                                             data-candidato-id="<?= (int)$cand['rh_candidato_id'] ?>"
                                             data-status="<?= htmlspecialchars($statusKey) ?>">
                                            <div class="small fw-bold">
                                                <?= htmlspecialchars($cand['candidato_nome'] ?? '') ?>
                                            </div>
                                            <div class="small text-muted">
                                                <?= htmlspecialchars($cand['candidato_email'] ?? '') ?>
                                            </div>
                                            <?php if (!empty($cand['candidato_status_processo'])): ?>
                                                <div class="small mt-1">
                                                    <span class="badge bg-secondary">
                                                        Processo: <?= htmlspecialchars($cand['candidato_status_processo']) ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                            <div class="small mt-1 text-muted">
                                                Candidatado em: <?= FormatHelper::formatDateTime($cand['data_candidatura'] ?? null) ?>
                                            </div>
                                            <div class="mt-1">
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>rh-candidatos-view/<?= (int)$cand['rh_candidato_id'] ?>"
                                                   class="btn btn-xs btn-outline-primary btn-sm">
                                                    <i class="fas fa-user me-1"></i>Ver
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (empty($this->data['can_manage_pipeline'])): ?>
                <div class="alert alert-info mt-3 mb-0">
                    <i class="fas fa-info-circle me-1"></i>
                    Você não tem permissão para movimentar candidatos no pipeline. Visualização apenas.
                </div>
            <?php else: ?>
                <div class="alert alert-secondary mt-3 mb-0 small">
                    <i class="fas fa-hand-pointer me-1"></i>
                    Arraste os cartões entre as colunas para atualizar o status da candidatura.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.kanban-column {
    min-height: 80px;
    border-radius: 4px;
}
.kanban-column.drag-over {
    outline: 2px dashed #0d6efd;
    background-color: rgba(13, 110, 253, 0.05);
}
.kanban-card {
    background-color: #fff;
    border-radius: 4px;
    border: 1px solid rgba(0,0,0,0.08);
    padding: 6px 8px;
    cursor: move;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}
.kanban-card.dragging {
    opacity: 0.6;
}
</style>

<script>
let kanbanDraggedCard = null;

function kanbanAllowDrop(ev) {
    ev.preventDefault();
    if (!ev.currentTarget.classList.contains('kanban-column')) return;
    ev.dataTransfer.dropEffect = 'move';
    ev.currentTarget.classList.add('drag-over');
}

function kanbanDrag(ev) {
    const card = ev.currentTarget;
    kanbanDraggedCard = card;
    card.classList.add('dragging');

    // Alguns navegadores só disparam o drop se houver dados no dataTransfer
    if (ev.dataTransfer) {
        ev.dataTransfer.effectAllowed = 'move';
        ev.dataTransfer.setData('text/plain', card.getAttribute('data-candidato-id') || '');
    }
}

function kanbanDrop(ev) {
    ev.preventDefault();
    const column = ev.currentTarget;
    if (!column.classList.contains('kanban-column')) return;
    column.classList.remove('drag-over');

    if (!kanbanDraggedCard) return;

    const board = document.getElementById('kanban-board');
    const vagaId = board.getAttribute('data-vaga-id');
    const csrfToken = board.getAttribute('data-csrf');
    const novoStatus = column.getAttribute('data-status');
    const candidatoId = kanbanDraggedCard.getAttribute('data-candidato-id');

    if (!vagaId || !csrfToken || !novoStatus || !candidatoId) {
        return;
    }

    // Atualizar visualmente primeiro
    column.appendChild(kanbanDraggedCard);
    kanbanDraggedCard.setAttribute('data-status', novoStatus);
    kanbanDraggedCard.classList.remove('dragging');

    atualizarContadoresKanban();

    // Enviar requisição para atualizar status no backend
    const formData = new FormData();
    formData.append('csrf_token', csrfToken);
    formData.append('vaga_id', vagaId);
    formData.append('candidato_id', candidatoId);
    formData.append('status', novoStatus);

    fetch('<?php echo $_ENV['URL_ADM']; ?>rh-atualizar-status-candidatura', {
        method: 'POST',
        body: formData
    })
        .then(response => response.text())
        .then(text => {
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Resposta não JSON do backend:', text);
                alert(text || 'Erro ao atualizar status da candidatura (resposta inválida do servidor).');
                window.location.reload();
                return;
            }

            if (!data.success) {
                alert(data.message || 'Erro ao atualizar status da candidatura.');
                // Em caso de erro, recarregar para voltar ao estado consistente
                window.location.reload();
            }
        })
        .catch((error) => {
            console.error('Erro na requisição:', error);
            alert('Erro ao atualizar status da candidatura.');
            window.location.reload();
        });

    kanbanDraggedCard = null;
}

document.addEventListener('dragend', function (ev) {
    if (kanbanDraggedCard) {
        kanbanDraggedCard.classList.remove('dragging');
        kanbanDraggedCard = null;
    }
    document.querySelectorAll('.kanban-column.drag-over').forEach(col => col.classList.remove('drag-over'));
});

function atualizarContadoresKanban() {
    document.querySelectorAll('#kanban-board .count-coluna').forEach(badge => {
        const status = badge.getAttribute('data-status');
        const col = document.querySelector('.kanban-column[data-status=\"' + status + '\"]');
        if (col) {
            badge.textContent = col.querySelectorAll('.kanban-card').length;
        }
    });
}
</script>

