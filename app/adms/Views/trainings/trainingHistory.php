<?php
use App\adms\Helpers\FormatHelper;

// Tratamento para datas inválidas
function safeDateFormat($dateStr) {
    if (empty($dateStr)) return '-';
    try {
        return (new DateTime($dateStr))->format('d/m/Y');
    } catch (Exception $e) {
        return 'Data Inválida';
    }
}
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Histórico de Reciclagem/Aplicações</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item">Histórico de Reciclagem</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-history me-2"></i>Histórico de Aplicações</h5>
            <div class="mt-2">
                <strong>Colaborador:</strong> <?= htmlspecialchars($this->data['user']['name']) ?> |
                <strong>Treinamento:</strong> <?= htmlspecialchars($this->data['training']['nome']) ?> (<?= htmlspecialchars($this->data['training']['codigo']) ?>)
            </div>
        </div>
        <div class="card-body">
            <?php if (!empty($this->data['history'])): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover align-middle" id="historyTable">
                        <thead class="table-light">
                            <tr>
                                <th>Data Realização</th>
                                <th>Data Agendada</th>
                                <th>Instrutor</th>
                                <th>Nota</th>
                                <th>Status</th>
                                <th>Observações</th>
                                <th>Registrado em</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($this->data['history'] as $app): ?>
                            <tr>
                                <td><?= $app['data_realizacao'] && $app['data_realizacao'] !== '0000-00-00' ? date('d/m/Y', strtotime($app['data_realizacao'])) : '-' ?></td>
                                <td><?= $app['data_agendada'] && $app['data_agendada'] !== '0000-00-00' && $app['data_agendada'] !== '30/11/-0001' ? date('d/m/Y', strtotime($app['data_agendada'])) : '-' ?></td>
                                <td><?= $app['instrutor_nome'] ?? '-' ?><?= $app['instrutor_email'] ? ' <br><small>'.$app['instrutor_email'].'</small>' : '' ?></td>
                                <td><?= $app['nota'] !== null ? number_format($app['nota'], 2, ',', '.') : '-' ?></td>
                                <td>
                                    <?php
                                    $statusClass = match(strtolower($app['status'])) {
                                        'em_dia' => 'bg-success',
                                        'pendente' => 'bg-warning',
                                        'vencido' => 'bg-danger',
                                        'proximo_vencimento' => 'bg-warning',
                                        'agendado' => 'bg-info',
                                        'concluido' => 'bg-primary',
                                        default => 'bg-secondary'
                                    };
                                    $statusText = match(strtolower($app['status'])) {
                                        'em_dia' => 'Em Dia',
                                        'pendente' => 'Pendente',
                                        'vencido' => 'Vencido',
                                        'proximo_vencimento' => 'Próximo Vencimento',
                                        'agendado' => 'Agendado',
                                        'concluido' => 'Concluído',
                                        default => ucfirst($app['status'])
                                    };
                                    ?>
                                    <span class="badge <?= $statusClass ?>"><?= $statusText ?></span>
                                </td>
                                <td><?= $app['observacoes'] ?? '-' ?></td>
                                <td><?= $app['created_at'] ? date('d/m/Y H:i', strtotime($app['created_at'])) : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">Nenhum histórico encontrado para este colaborador neste treinamento.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    $('#historyTable').DataTable({
        pageLength: 25,
        order: [[0, 'desc']]
    });
});
</script> 