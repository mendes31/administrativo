<?php
use App\adms\Helpers\CSRFHelper;
$csrf_token = CSRFHelper::generateCSRFToken('form_update_document_positions');
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Treinamento Obrigatório por Cargo</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Documentos Obrigatórios</li>
        </ol>
    </div>
    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span>
                <?php
                // Exibe o documento selecionado como título
                if (!empty($this->data['documentos'][$this->data['document_id']])) {
                    echo $this->data['documentos'][$this->data['document_id']];
                } else {
                    echo 'Documento';
                }
                ?>
            </span>
            <span class="ms-auto d-sm-flex flex-row">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-documents" class="btn btn-info btn-sm me-1 mb-1">
                    <i class="fa-solid fa-list"></i> Listar
                </a>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form action="<?php echo $_ENV['URL_ADM']; ?>list-document-positions/<?php echo htmlspecialchars($this->data['document_id']); ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="document_id" value="<?php echo htmlspecialchars($this->data['document_id']); ?>">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th scope="col" class="text-center">Obrigatório</th>
                            <th scope="col" class="text-start">ID</th>
                            <th scope="col" class="text-start">Cargo</th>
                            <!-- <th scope="col" class="text-start"></th>Documento</th> -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($this->data['cargos'])): ?>
                            <?php foreach ($this->data['cargos'] as $cargo): ?>
                                <tr>
                                    <td class="text-center">
                                        <div class="form-check form-switch d-flex justify-content-center">
                                            <input type="checkbox" name="mandatory[<?php echo $cargo['id']; ?>]" class="form-check-input" id="mandatory_<?php echo $cargo['id']; ?>" value="1" <?php echo $cargo['mandatory'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="mandatory_<?php echo $cargo['id']; ?>"></label>
                                        </div>
                                    </td>
                                    <td class="text-start"><?php echo htmlspecialchars($cargo['id']); ?></td>
                                    <td class="text-start"><?php echo htmlspecialchars($cargo['name']); ?></td>
                                    <td>
                                        <?php 
                                        // Exibe o documento obrigatório relacionado ao cargo, se houver
                                        if (!empty($this->data['documentos'])) {
                                            // Aqui você pode ajustar para buscar o documento correto por cargo
                                            // Exemplo: $docId = $cargo['document_id'];
                                            // echo $this->data['documentos'][$docId] ?? '-';
                                            // Por enquanto, exibe o primeiro documento como exemplo
                                            //echo reset($this->data['documentos']);
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center">Nenhum cargo encontrado.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="col-12 mt-3 text-start">
                    <button type="submit" class="btn btn-warning btn-sm">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div> 