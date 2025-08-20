<?php
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
        <div class="card-header hstack gap-2 bg-white border-bottom-0">
            <span class="fw-bold">Documento</span>
            <?php if (!empty($this->data['documentos'])): ?>
                <span class="ms-3 text-primary">
                    <?php 
                        // Exibe o primeiro documento como exemplo
                        $primeiroDoc = reset($this->data['documentos']);
                        echo $primeiroDoc;
                    ?>
                </span>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 120px;">Obrigatório</th>
                            <th style="width: 60px;">ID</th>
                            <th>Cargo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($this->data['cargos'])): ?>
                            <?php foreach ($this->data['cargos'] as $cargo): ?>
                                <tr>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="mandatory_<?php echo $cargo['id']; ?>" data-cargo-id="<?php echo $cargo['id']; ?>" <?php echo $cargo['mandatory'] ? 'checked' : ''; ?> onchange="toggleMandatory(this)">
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($cargo['id']); ?></td>
                                    <td><?php echo htmlspecialchars($cargo['name']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="text-center">Nenhum cargo encontrado.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
function toggleMandatory(checkbox) {
    const cargoId = checkbox.getAttribute('data-cargo-id');
    const mandatory = checkbox.checked ? 1 : 0;
    // Aqui você pode fazer um fetch/ajax para atualizar no backend
    // Exemplo:
    // fetch('URL_PARA_ATUALIZAR', { method: 'POST', body: ... })
    // alert('Cargo ' + cargoId + ' obrigatório: ' + mandatory);
}
</script> 