<?php
use App\adms\Helpers\CSRFHelper;
?>

<div class="container-fluid px-4">
    <div class="mb-1 d-flex flex-column flex-sm-row gap-2">
        <h2 class="mt-3">Níveis de Acesso</h2>
        <ol class="breadcrumb mb-3 mt-0 mt-sm-3 ms-auto">
            <li class="breadcrumb-item"><a class="text-decoration-none" href="<?php echo $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a class="text-decoration-none" href="<?php echo $_ENV['URL_ADM']; ?>list-access-levels">Níveis de Acesso</a></li>
            <li class="breadcrumb-item">Importar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span>Importar Níveis de Acesso via CSV</span>
            <span class="ms-auto d-sm-flex flex-row">
                <a href="<?php echo $_ENV['URL_ADM']; ?>import-access-levels/template" class="btn btn-outline-secondary btn-sm me-1 mb-1">
                    <i class="fa-solid fa-download"></i> Baixar Template
                </a>
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-access-levels" class="btn btn-info btn-sm me-1 mb-1"><i class="fa-solid fa-list"></i> Listar</a>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <div class="alert alert-info">
                <h6><i class="fa-solid fa-info-circle"></i> Novo Formato CSV com Permissões</h6>
                <p class="mb-2">O arquivo CSV agora deve conter 2 colunas:</p>
                <ul class="mb-2">
                    <li><strong>name:</strong> Nome do nível de acesso</li>
                    <li><strong>permissions:</strong> IDs das páginas permitidas (separados por vírgula) ou "ALL" para permissão total</li>
                </ul>
                <p class="mb-0"><strong>Importante:</strong> 
                <ul class="mb-0">
                    <li>Níveis existentes terão suas permissões <strong>substituídas completamente</strong></li>
                    <li>Novos níveis serão criados com permissões específicas</li>
                    <li>Use "ALL" para dar permissão total a um nível</li>
                    <li>Páginas não especificadas receberão permission = 0 (negado)</li>
                    <li>Dashboard (ID 1) é sempre incluído automaticamente</li>
                </ul>
                </p>
            </div>

            <form action="" method="POST" enctype="multipart/form-data" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_import_access_levels'); ?>">

                <div class="col-12">
                    <label class="form-label">Arquivo CSV</label>
                    <input type="file" name="file" class="form-control" accept=".csv">
                    <small class="text-muted">Use o template. Separador: ponto e vírgula (;) ou vírgula (,). Codificação: UTF-8/Windows-1252 aceito.</small>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-success btn-sm">Importar</button>
                </div>
            </form>

            <?php if (!empty($this->data['summary'])): 
                $s = $this->data['summary']; ?>
                <hr>
                <div>
                    <strong>Resumo:</strong>
                    <ul class="mb-2">
                        <li>Criados: <?php echo (int)$s['created']; ?></li>
                        <li>Atualizados: <?php echo (int)$s['updated']; ?></li>
                        <li>Ignorados: <?php echo (int)$s['skipped']; ?></li>
                        <li>Erros: <?php echo (int)$s['errors']; ?></li>
                    </ul>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Linha</th>
                                <th>Ação</th>
                                <th>Mensagem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (($this->data['report'] ?? []) as $r): ?>
                            <tr>
                                <td><?php echo htmlspecialchars((string)($r['linha'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars((string)($r['acao'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars((string)($r['msg'] ?? '')); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>


