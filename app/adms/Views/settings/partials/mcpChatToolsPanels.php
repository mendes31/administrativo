<?php
$builtin = $this->data['builtin_tools'] ?? [];
$reports = $this->data['chat_reports'] ?? [];
$csrf = $this->data['csrf_token_tools'] ?? ($this->data['csrf_token'] ?? '');
$canSave = in_array('SaveMcpChatTool', $this->data['buttonPermission'] ?? [], true);
?>
<p class="text-muted">
    Catálogo do chat em modo <code>local:internal</code>. Tools RH são fixas no código;
    relatórios dinâmicos entram quando marcados como disponíveis no chat.
</p>

<div class="card mb-4">
    <div class="card-header">
        <strong><i class="fas fa-cogs me-1"></i> Tools internas (RH)</strong>
    </div>
    <div class="card-body">
        <?php if ($builtin === []): ?>
            <p class="text-muted mb-0">Nenhuma tool built-in cadastrada.</p>
        <?php else: ?>
            <div class="vstack gap-3">
                <?php foreach ($builtin as $tool): ?>
                    <div class="border rounded-3 p-3 bg-light bg-opacity-50">
                        <div class="d-flex flex-wrap align-items-baseline gap-2 mb-1">
                            <code class="small"><?= htmlspecialchars((string) $tool['tool']) ?></code>
                            <strong><?= htmlspecialchars((string) $tool['name']) ?></strong>
                        </div>
                        <p class="small mb-2 text-body-secondary">
                            <?= htmlspecialchars((string) $tool['description']) ?>
                        </p>
                        <?php if (!empty($tool['examples']) && is_array($tool['examples'])): ?>
                            <div class="d-flex flex-wrap gap-1">
                                <?php foreach ($tool['examples'] as $example): ?>
                                    <span class="badge text-bg-secondary fw-normal text-wrap text-start">
                                        <?= htmlspecialchars((string) $example) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <strong><i class="fas fa-file-alt me-1"></i> Relatórios dinâmicos → chat</strong>
        <a class="btn btn-sm btn-outline-primary" href="<?= $_ENV['URL_ADM'] ?>list-dynamic-reports">
            Abrir Relatórios
        </a>
    </div>
    <div class="card-body">
        <?php if ($reports === []): ?>
            <p class="text-muted mb-0">Nenhum relatório ativo encontrado. Crie um em Relatórios Dinâmicos.</p>
        <?php else: ?>
            <div class="accordion" id="mcpChatToolsAccordion">
                <?php foreach ($reports as $idx => $report): ?>
                    <?php
                    $rid = (int) ($report['id'] ?? 0);
                    $examplesText = '';
                    if (!empty($report['chat_example_prompts']) && is_array($report['chat_example_prompts'])) {
                        $examplesText = implode("\n", $report['chat_example_prompts']);
                    }
                    $headingId = 'headingChatTool' . $rid;
                    $collapseId = 'collapseChatTool' . $rid;
                    ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="<?= $headingId ?>">
                            <button class="accordion-button <?= $idx === 0 ? '' : 'collapsed' ?>" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#<?= $collapseId ?>"
                                    aria-expanded="<?= $idx === 0 ? 'true' : 'false' ?>"
                                    aria-controls="<?= $collapseId ?>">
                                <?php if (!empty($report['chat_enabled'])): ?>
                                    <span class="badge bg-success me-2">No chat</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary me-2">Off</span>
                                <?php endif; ?>
                                <span class="text-break">
                                    <?= htmlspecialchars((string) ($report['name'] ?? '')) ?>
                                    <span class="text-muted small ms-1">
                                        #<?= $rid ?>
                                        <?php if (!empty($report['chat_tool_name'])): ?>
                                            · <code><?= htmlspecialchars((string) $report['chat_tool_name']) ?></code>
                                        <?php endif; ?>
                                    </span>
                                </span>
                            </button>
                        </h2>
                        <div id="<?= $collapseId ?>" class="accordion-collapse collapse <?= $idx === 0 ? 'show' : '' ?>"
                             aria-labelledby="<?= $headingId ?>" data-bs-parent="#mcpChatToolsAccordion">
                            <div class="accordion-body">
                                <form method="POST" action="<?= $_ENV['URL_ADM'] ?>save-mcp-chat-tool" class="row g-3">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string) $csrf) ?>">
                                    <input type="hidden" name="report_id" value="<?= $rid ?>">
                                    <div class="col-12">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="chat_enabled"
                                                   id="chatEnabled<?= $rid ?>" value="1"
                                                   <?= !empty($report['chat_enabled']) ? 'checked' : '' ?>
                                                   <?= $canSave ? '' : 'disabled' ?>>
                                            <label class="form-check-label" for="chatEnabled<?= $rid ?>">
                                                Disponível no Tiarajuzinho
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" for="toolName<?= $rid ?>">Nome da tool</label>
                                        <input type="text" class="form-control" name="chat_tool_name"
                                               id="toolName<?= $rid ?>" maxlength="100"
                                               value="<?= htmlspecialchars((string) ($report['chat_tool_name'] ?? '')) ?>"
                                               <?= $canSave ? '' : 'readonly' ?>>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label" for="toolDesc<?= $rid ?>">Descrição para a IA</label>
                                        <input type="text" class="form-control" name="chat_description"
                                               id="toolDesc<?= $rid ?>"
                                               value="<?= htmlspecialchars((string) ($report['chat_description'] ?? '')) ?>"
                                               <?= $canSave ? '' : 'readonly' ?>>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="toolEx<?= $rid ?>">Exemplos de perguntas (um por linha)</label>
                                        <textarea class="form-control" name="chat_example_prompts" id="toolEx<?= $rid ?>"
                                                  rows="3" <?= $canSave ? '' : 'readonly' ?>><?= htmlspecialchars($examplesText) ?></textarea>
                                    </div>
                                    <div class="col-12 d-flex flex-wrap gap-2">
                                        <?php if ($canSave): ?>
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i class="fas fa-save me-1"></i> Salvar
                                            </button>
                                        <?php endif; ?>
                                        <a class="btn btn-outline-secondary btn-sm"
                                           href="<?= $_ENV['URL_ADM'] ?>dynamic-report-builder/<?= $rid ?>">
                                            Abrir construtor
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
