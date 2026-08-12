<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $this->data
 */
$config = is_array($this->data['config'] ?? null) ? $this->data['config'] : [];
$effective = is_array($this->data['effective'] ?? null) ? $this->data['effective'] : [];
$membros = is_array($this->data['comite_membros'] ?? null) ? $this->data['comite_membros'] : [];
$editMembro = is_array($this->data['edit_membro'] ?? null) ? $this->data['edit_membro'] : null;
$portalUrl = (string) ($this->data['portal_url'] ?? '');
$csrf = (string) ($this->data['csrf_token'] ?? '');
$urlAdm = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/');
$actionUrl = $urlAdm . '/lgpd-publico-config';

$val = static function (string $field) use ($config, $effective): string {
    $fromDb = trim((string) ($config[$field] ?? ''));

    return $fromDb !== '' ? $fromDb : trim((string) ($effective[$field] ?? ''));
};

$envHint = static function (string $field, string $envKey) use ($config): ?string {
    if (trim((string) ($config[$field] ?? '')) !== '') {
        return null;
    }
    $env = trim((string) ($_ENV[$envKey] ?? ''));

    return $env !== '' ? $env : null;
};
?>
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <div class="row">
        <div class="col-12 col-xl-10 mx-auto">
            <div class="card mt-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h4 class="mb-0">Portal público LGPD</h4>
                    <a href="<?= htmlspecialchars($portalUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="btn btn-sm btn-light">
                        <i class="fas fa-external-link-alt me-1"></i>Abrir página pública
                    </a>
                </div>
                <div class="card-body">
                    <?php include './app/adms/Views/partials/alerts.php'; ?>

                    <p class="text-muted small">
                        Dados exibidos em <code>/lgpd</code> sem login. As alterações aqui têm prioridade sobre o arquivo <code>.env</code>.
                    </p>

                    <form method="post" action="<?= htmlspecialchars($actionUrl, ENT_QUOTES, 'UTF-8') ?>" class="mb-4">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="action" value="save_config">

                        <h5 class="border-bottom pb-2 mb-3">Empresa e DPO / Encarregado</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="empresa_nome">Nome da empresa</label>
                                <input type="text" name="empresa_nome" id="empresa_nome" class="form-control"
                                       value="<?= htmlspecialchars($val('empresa_nome'), ENT_QUOTES, 'UTF-8') ?>"
                                       placeholder="<?= htmlspecialchars((string) ($_ENV['APP_NAME'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <?php if ($hint = $envHint('empresa_nome', 'LGPD_EMPRESA_NOME')): ?>
                                    <div class="form-text">Valor atual do .env: <?= htmlspecialchars($hint, ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="dpo_nome">Nome do DPO / encarregado</label>
                                <input type="text" name="dpo_nome" id="dpo_nome" class="form-control"
                                       value="<?= htmlspecialchars($val('dpo_nome'), ENT_QUOTES, 'UTF-8') ?>">
                                <?php if ($hint = $envHint('dpo_nome', 'LGPD_DPO_NOME')): ?>
                                    <div class="form-text">Valor atual do .env: <?= htmlspecialchars($hint, ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="dpo_email">E-mail do DPO</label>
                                <input type="email" name="dpo_email" id="dpo_email" class="form-control"
                                       value="<?= htmlspecialchars($val('dpo_email'), ENT_QUOTES, 'UTF-8') ?>">
                                <?php if ($hint = $envHint('dpo_email', 'LGPD_DPO_EMAIL')): ?>
                                    <div class="form-text">Valor atual do .env: <?= htmlspecialchars($hint, ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="dpo_telefone">Telefone do DPO</label>
                                <input type="text" name="dpo_telefone" id="dpo_telefone" class="form-control"
                                       value="<?= htmlspecialchars($val('dpo_telefone'), ENT_QUOTES, 'UTF-8') ?>">
                                <?php if ($hint = $envHint('dpo_telefone', 'LGPD_DPO_TELEFONE')): ?>
                                    <div class="form-text">Valor atual do .env: <?= htmlspecialchars($hint, ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <h5 class="border-bottom pb-2 mb-3 mt-4">Documentos PDF (opcional)</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="cartilha_path">Caminho da cartilha</label>
                                <input type="text" name="cartilha_path" id="cartilha_path" class="form-control"
                                       value="<?= htmlspecialchars($val('cartilha_path'), ENT_QUOTES, 'UTF-8') ?>"
                                       placeholder="storage/lgpd/publico/cartilha.pdf">
                                <div class="form-text">Relativo à raiz do projeto. O cartão só aparece se o PDF existir.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="carta_compromisso_path">Caminho da carta de compromisso</label>
                                <input type="text" name="carta_compromisso_path" id="carta_compromisso_path" class="form-control"
                                       value="<?= htmlspecialchars($val('carta_compromisso_path'), ENT_QUOTES, 'UTF-8') ?>"
                                       placeholder="storage/lgpd/publico/carta-compromisso.pdf">
                            </div>
                        </div>

                        <h5 class="border-bottom pb-2 mb-3 mt-4">Comitê de privacidade (texto introdutório)</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="comite_titulo">Título do comitê</label>
                                <input type="text" name="comite_titulo" id="comite_titulo" class="form-control"
                                       value="<?= htmlspecialchars($val('comite_titulo'), ENT_QUOTES, 'UTF-8') ?>"
                                       placeholder="Comitê de Privacidade e Proteção de Dados">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="comite_descricao">Descrição (opcional)</label>
                                <textarea name="comite_descricao" id="comite_descricao" class="form-control" rows="3"
                                          placeholder="Texto exibido acima da lista de membros na página pública."><?= htmlspecialchars($val('comite_descricao'), ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Salvar configuração
                            </button>
                            <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($urlAdm . '/lgpd-dashboard', ENT_QUOTES, 'UTF-8') ?>">
                                Voltar ao dashboard
                            </a>
                        </div>
                    </form>

                    <h5 class="border-bottom pb-2 mb-3">Membros do comitê</h5>
                    <p class="text-muted small">
                        Cadastre os membros quando houver comitê formal. A seção só aparece na página pública se houver ao menos um membro ativo.
                    </p>

                    <?php if ($membros !== []): ?>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Ordem</th>
                                    <th>Nome</th>
                                    <th>Cargo</th>
                                    <th>Contato</th>
                                    <th>Ativo</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($membros as $m): ?>
                                <tr>
                                    <td><?= (int) ($m['ordem'] ?? 0) ?></td>
                                    <td><?= htmlspecialchars((string) ($m['nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string) ($m['cargo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="small">
                                        <?php if (!empty($m['email'])): ?>
                                            <?= htmlspecialchars((string) $m['email'], ENT_QUOTES, 'UTF-8') ?><br>
                                        <?php endif; ?>
                                        <?php if (!empty($m['telefone'])): ?>
                                            <?= htmlspecialchars((string) $m['telefone'], ENT_QUOTES, 'UTF-8') ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ((int) ($m['ativo'] ?? 0) === 1): ?>
                                            <span class="badge bg-success">Sim</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Não</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <a class="btn btn-sm btn-outline-primary"
                                           href="<?= htmlspecialchars($actionUrl . '?edit_membro=' . (int) ($m['id'] ?? 0), ENT_QUOTES, 'UTF-8') ?>">
                                            Editar
                                        </a>
                                        <form method="post" action="<?= htmlspecialchars($actionUrl, ENT_QUOTES, 'UTF-8') ?>" class="d-inline"
                                              onsubmit="return confirm('Remover este membro do comitê?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="action" value="delete_membro">
                                            <input type="hidden" name="membro_id" value="<?= (int) ($m['id'] ?? 0) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-light border mb-4">Nenhum membro cadastrado.</div>
                    <?php endif; ?>

                    <div class="card bg-light border-0">
                        <div class="card-body">
                            <h6 class="mb-3"><?= $editMembro ? 'Editar membro' : 'Adicionar membro' ?></h6>
                            <form method="post" action="<?= htmlspecialchars($actionUrl, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="action" value="save_membro">
                                <input type="hidden" name="membro_id" value="<?= (int) ($editMembro['id'] ?? 0) ?>">

                                <div class="row g-3">
                                    <div class="col-md-5">
                                        <label class="form-label" for="membro_nome">Nome *</label>
                                        <input type="text" name="membro_nome" id="membro_nome" class="form-control" required
                                               value="<?= htmlspecialchars((string) ($editMembro['nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" for="membro_cargo">Cargo / função</label>
                                        <input type="text" name="membro_cargo" id="membro_cargo" class="form-control"
                                               value="<?= htmlspecialchars((string) ($editMembro['cargo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label" for="membro_ordem">Ordem</label>
                                        <input type="number" name="membro_ordem" id="membro_ordem" class="form-control" min="0"
                                               value="<?= (int) ($editMembro['ordem'] ?? 0) ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" for="membro_email">E-mail</label>
                                        <input type="email" name="membro_email" id="membro_email" class="form-control"
                                               value="<?= htmlspecialchars((string) ($editMembro['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" for="membro_telefone">Telefone</label>
                                        <input type="text" name="membro_telefone" id="membro_telefone" class="form-control"
                                               value="<?= htmlspecialchars((string) ($editMembro['telefone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    </div>
                                    <div class="col-md-4 d-flex align-items-end">
                                        <div class="form-check mb-2">
                                            <input type="checkbox" name="membro_ativo" value="1" class="form-check-input" id="membro_ativo"
                                                <?= ($editMembro === null || (int) ($editMembro['ativo'] ?? 1) === 1) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="membro_ativo">Ativo na página pública</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-user-plus me-1"></i><?= $editMembro ? 'Atualizar membro' : 'Adicionar membro' ?>
                                    </button>
                                    <?php if ($editMembro): ?>
                                    <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($actionUrl, ENT_QUOTES, 'UTF-8') ?>">Cancelar edição</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
