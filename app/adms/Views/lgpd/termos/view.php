<?php
use App\adms\Helpers\FormatHelper;
?>
<div class="container-fluid px-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-lg border-0 rounded-lg mt-4">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <h3 class="font-weight-light my-2 mb-0">Visualizar Termo LGPD</h3>
                    <div class="btn-group">
                        <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-termos-new-version/<?= $this->data['termo']['id'] ?>" class="btn btn-warning btn-sm">
                            <i class="fas fa-copy me-1"></i>Nova versão
                        </a>
                        <?php if (in_array('LgpdTermosExportPdf', $this->data['buttonPermission'] ?? [], true)): ?>
                            <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-termos-export-pdf/<?= $this->data['termo']['id'] ?>"
                               class="btn btn-primary btn-sm" target="_blank" rel="noopener">
                                <i class="fas fa-file-pdf me-1"></i>Gerar PDF
                            </a>
                        <?php endif; ?>
                        <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-termos-delete?id=<?= $this->data['termo']['id'] ?>"
                           class="btn btn-danger btn-sm"
                           onclick="return confirm('Tem certeza que deseja excluir este termo?')">
                            <i class="fas fa-trash me-1"></i>Excluir
                        </a>
                        <?php
                        $log_resumo = $this->data['log_resumo'] ?? [];
                        $log_btn_class = 'btn btn-outline-light btn-sm';
                        include __DIR__ . '/../../partials/button_log_alteracoes.php';
                        ?>
                    </div>
                </div>
                <div class="card-body">
                    <?php include './app/adms/Views/partials/alerts.php'; ?>

                    <dl class="row mb-3">
                        <dt class="col-sm-2">ID</dt>
                        <dd class="col-sm-10"><?= $this->data['termo']['id'] ?></dd>

                        <dt class="col-sm-2">Versão</dt>
                        <dd class="col-sm-10"><?= htmlspecialchars($this->data['termo']['versao']) ?></dd>

                        <dt class="col-sm-2">Título</dt>
                        <dd class="col-sm-10"><?= htmlspecialchars($this->data['termo']['titulo']) ?></dd>

                        <dt class="col-sm-2">Tipo</dt>
                        <dd class="col-sm-10"><?= htmlspecialchars($this->data['termo']['tipo']) ?></dd>

                        <dt class="col-sm-2">Início Vigência</dt>
                        <dd class="col-sm-10"><?= FormatHelper::formatDate($this->data['termo']['data_inicio_vigencia'] ?? null, 'd/m/Y H:i') ?></dd>

                        <dt class="col-sm-2">Fim Vigência</dt>
                        <dd class="col-sm-10">
                            <?= !empty($this->data['termo']['data_fim_vigencia'])
                                ? FormatHelper::formatDate($this->data['termo']['data_fim_vigencia'], 'd/m/Y H:i')
                                : '<span class="text-muted">Sem data de fim</span>' ?>
                        </dd>

                        <dt class="col-sm-2">Status</dt>
                        <dd class="col-sm-10">
                            <?php if ($this->data['termo']['status'] === 'Ativo'): ?>
                                <span class="badge bg-success"><i class="fas fa-check me-1"></i>Ativo</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><i class="fas fa-ban me-1"></i>Inativo</span>
                            <?php endif; ?>
                        </dd>

                        <dt class="col-sm-2">Criado em</dt>
                        <dd class="col-sm-10"><?= FormatHelper::formatDate($this->data['termo']['created_at'] ?? null, 'd/m/Y H:i') ?></dd>
                    </dl>

                    <hr>
                    <h5>Conteúdo do Termo</h5>
                    <div class="border rounded p-3 lgpd-term-content" style="background-color: #f8f9fa; max-height: 500px; overflow-y: auto; line-height: 1.3;">
                        <?php echo $this->data['termo']['conteudo']; ?>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-termos" class="btn btn-secondary btn-sm">Voltar</a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Reaproveita a mesma tipografia usada no consentimento para a visualização do termo */
    .lgpd-term-content p {
        margin-bottom: 0.35rem;
    }
    .lgpd-term-content ul {
        margin-left: 1.4rem;
        margin-bottom: 0.35rem;
    }
    .lgpd-term-content li {
        margin-bottom: 0.15rem;
    }
    .lgpd-term-content h1,
    .lgpd-term-content h2,
    .lgpd-term-content h3,
    .lgpd-term-content h4 {
        margin-top: 0.75rem;
        margin-bottom: 0.35rem;
        font-weight: 600;
    }
</style>
