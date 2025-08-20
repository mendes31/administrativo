<?php
// View: Minhas Avaliações Pendentes do Colaborador
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Minhas Avaliações</h2>
    </div>
    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Avaliações/Questionários Pendentes</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Modelo</th>
                            <th>Pergunta</th>
                            <th>Tipo</th>
                            <th>Data Limite</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($this->data['pendingEvaluations'])): ?>
                            <?php foreach ($this->data['pendingEvaluations'] as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['modelo_titulo']) ?></td>
                                    <td><?= htmlspecialchars($item['pergunta']) ?></td>
                                    <td><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $item['tipo']))) ?></td>
                                    <td><?= $item['data_limite'] ? date('d/m/Y', strtotime($item['data_limite'])) : '-' ?></td>
                                    <td>
                                        <a href="<?= $_ENV['URL_ADM'] ?>responder-avaliacao/<?= $item['modelo_id'] ?>/<?= $item['pergunta_id'] ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit me-1"></i>Responder
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center text-muted">Nenhuma avaliação/questionário pendente.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div> 