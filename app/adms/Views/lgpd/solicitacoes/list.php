<?php
use App\adms\Helpers\FormatHelper;
$rows = $this->data['solicitacoes'] ?? [];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">
            <i class="fas fa-user-shield text-primary"></i>
            Solicitações de titulares
        </h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-dashboard" class="text-decoration-none">LGPD</a></li>
            <li class="breadcrumb-item">Solicitações</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="alert alert-light border">
        Página pública (sem login):
        <a href="<?php echo htmlspecialchars((string) ($this->data['url_publico'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
            <?php echo htmlspecialchars((string) ($this->data['url_publico'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
        </a>
        · Pendentes/em andamento: <strong><?php echo (int) ($this->data['pendentes'] ?? 0); ?></strong>
        · Prazo legal: 15 dias (Art. 18 §5º).
    </div>

    <div class="card mb-4">
        <div class="card-body table-responsive">
            <table class="table table-hover table-sm align-middle">
                <thead>
                    <tr>
                        <th>Protocolo</th>
                        <th>Titular</th>
                        <th>Categoria</th>
                        <th>Status</th>
                        <th>Prazo</th>
                        <th>Entrada</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($rows === []): ?>
                    <tr><td colspan="7" class="text-muted">Nenhuma requisição recebida ainda.</td></tr>
                <?php endif; ?>
                <?php foreach ($rows as $r): ?>
                    <?php
                    $st = (string) ($r['status'] ?? '');
                    $badge = match ($st) {
                        'Pendente' => 'warning',
                        'Em andamento' => 'info',
                        'Aguardando titular' => 'secondary',
                        'Concluída' => 'success',
                        'Vencida' => 'danger',
                        default => 'secondary',
                    };
                    ?>
                    <tr>
                        <td><code><?php echo htmlspecialchars((string) ($r['protocolo'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></code></td>
                        <td>
                            <?php echo htmlspecialchars((string) ($r['titular_nome'] ?? ''), ENT_QUOTES, 'UTF-8'); ?><br>
                            <small class="text-muted"><?php echo htmlspecialchars((string) ($r['titular_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars((string) ($r['titular_categoria'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><span class="badge bg-<?php echo $badge; ?>"><?php echo htmlspecialchars($st, ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td><?php echo !empty($r['prazo']) ? FormatHelper::formatDate($r['prazo'], 'd/m/Y') : '—'; ?></td>
                        <td><?php echo !empty($r['created_at']) ? FormatHelper::formatDate($r['created_at'], 'd/m/Y H:i') : '—'; ?></td>
                        <td>
                            <?php if (in_array('LgpdSolicitacoesTitularesView', $this->data['buttonPermission'] ?? [], true)): ?>
                                <a class="btn btn-sm btn-outline-primary" href="<?php echo $_ENV['URL_ADM']; ?>lgpd-solicitacoes-titulares-view/<?php echo (int) $r['id']; ?>">
                                    Abrir
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
