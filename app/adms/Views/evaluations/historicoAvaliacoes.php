<?php
// View: Histórico de Avaliações Respondidas do Colaborador
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Histórico de Avaliações</h2>
    </div>
    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-history me-2"></i>Avaliações/Questionários Respondidos</h5>
        </div>
        <div class="card-body">
            <?php if (!empty($this->data['error'])): ?>
                <div class="alert alert-danger mb-3"> <?= htmlspecialchars($this->data['error']) ?> </div>
            <?php endif; ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Modelo</th>
                            <th>Pergunta</th>
                            <th>Tipo</th>
                            <th>Resposta</th>
                            <th>Data</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($this->data['answeredEvaluations'])): ?>
                            <?php foreach ($this->data['answeredEvaluations'] as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['modelo_titulo']) ?></td>
                                    <td><?= htmlspecialchars($item['pergunta']) ?></td>
                                    <td><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $item['tipo']))) ?></td>
                                    <td><?= htmlspecialchars($item['resposta']) ?></td>
                                    <td><?= $item['created_at'] ? date('d/m/Y H:i', strtotime($item['created_at'])) : '-' ?></td>
                                    <td><?= htmlspecialchars(ucfirst($item['status'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center text-muted">Nenhuma avaliação/questionário respondido.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div> 