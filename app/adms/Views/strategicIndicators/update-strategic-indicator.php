<?php
// Verificar se os dados estão definidos
if (!isset($this->data['indicator'])) {
    echo "Erro: Dados do indicador não encontrados.";
    return;
}

$indicator = $this->data['indicator'];
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-edit"></i> Atualizar Indicador Estratégico
                    </h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?php echo $_ENV['URL_ADM']; ?>update-strategic-indicator/<?= $indicator['id'] ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Nome <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control" 
                                       id="name" 
                                       name="name" 
                                       value="<?= htmlspecialchars($indicator['name'] ?? '') ?>" 
                                       required>
                            </div>
                            <div class="col-md-6">
                                <label for="type" class="form-label">Tipo <span class="text-danger">*</span></label>
                                <select class="form-control" id="type" name="type" required>
                                    <option value="">Selecione o tipo</option>
                                    <option value="quantitativo" <?= ($indicator['type'] ?? '') === 'quantitativo' ? 'selected' : '' ?>>Quantitativo</option>
                                    <option value="qualitativo" <?= ($indicator['type'] ?? '') === 'qualitativo' ? 'selected' : '' ?>>Qualitativo</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label for="unit" class="form-label">Unidade de Medida</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="unit" 
                                       name="unit" 
                                       value="<?= htmlspecialchars($indicator['unit'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="target_value" class="form-label">Valor Meta</label>
                                <input type="number" 
                                       class="form-control" 
                                       id="target_value" 
                                       name="target_value" 
                                       step="0.01" 
                                       value="<?= htmlspecialchars($indicator['target_value'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label for="current_value" class="form-label">Valor Atual</label>
                                <input type="number" 
                                       class="form-control" 
                                       id="current_value" 
                                       name="current_value" 
                                       step="0.01" 
                                       value="<?= htmlspecialchars($indicator['current_value'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-control" id="status" name="status" required>
                                    <option value="">Selecione o status</option>
                                    <option value="ativo" <?= ($indicator['status'] ?? '') === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                                    <option value="inativo" <?= ($indicator['status'] ?? '') === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                                    <option value="concluido" <?= ($indicator['status'] ?? '') === 'concluido' ? 'selected' : '' ?>>Concluído</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12">
                                <label for="description" class="form-label">Descrição</label>
                                <textarea class="form-control" 
                                          id="description" 
                                          name="description" 
                                          rows="4"><?= htmlspecialchars($indicator['description'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Atualizar Indicador
                                </button>
                                <a href="<?php echo $_ENV['URL_ADM']; ?>strategic-indicators-list" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Voltar
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>



