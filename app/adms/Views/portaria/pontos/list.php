<?php
$url = (string) ($_ENV['URL_ADM'] ?? '');
$perms = $this->data['buttonPermission'] ?? [];
$pontos = $this->data['pontos'] ?? [];
?>
<div class="container-fluid px-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-4 mb-3">
        <h1 class="h3 mb-0">Pontos de controle</h1>
        <?php if (in_array('PortariaPontosCreate', $perms, true)): ?>
            <a class="btn btn-success" href="<?= $url ?>portaria-pontos-create">Cadastrar ponto</a>
        <?php endif; ?>
    </div>
    <div class="card shadow-sm">
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <div class="table-responsive d-none d-md-block list-desktop">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Código</th>
                            <th>Filial</th>
                            <th>Status</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($pontos === []): ?>
                            <tr><td colspan="5" class="text-center text-muted">Nenhum ponto cadastrado.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($pontos as $p): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) $p['nome']) ?></td>
                                <td><?= htmlspecialchars((string) ($p['codigo'] ?? '—')) ?></td>
                                <td><?= htmlspecialchars((string) ($p['filial_nome'] ?? '—')) ?></td>
                                <td><?= !empty($p['ativo']) ? 'Ativo' : 'Inativo' ?></td>
                                <td class="text-center">
                                    <?php if (in_array('PortariaPontosUpdate', $perms, true)): ?>
                                        <a class="btn btn-warning btn-sm" href="<?= $url ?>portaria-pontos-update/<?= (int) $p['id'] ?>">Editar</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-block d-md-none list-mobile">
                <?php if ($pontos === []): ?>
                    <p class="text-muted text-center mb-0">Nenhum ponto cadastrado.</p>
                <?php endif; ?>
                <?php foreach ($pontos as $p): ?>
                    <div class="card mb-3 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title mb-2"><?= htmlspecialchars((string) $p['nome']) ?></h5>
                            <div class="small mb-1"><b>Código:</b> <?= htmlspecialchars((string) ($p['codigo'] ?? '—')) ?></div>
                            <div class="small mb-1"><b>Filial:</b> <?= htmlspecialchars((string) ($p['filial_nome'] ?? '—')) ?></div>
                            <div class="small mb-2"><b>Status:</b> <?= !empty($p['ativo']) ? 'Ativo' : 'Inativo' ?></div>
                            <?php if (in_array('PortariaPontosUpdate', $perms, true)): ?>
                                <a class="btn btn-warning btn-sm w-100" href="<?= $url ?>portaria-pontos-update/<?= (int) $p['id'] ?>">Editar</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
