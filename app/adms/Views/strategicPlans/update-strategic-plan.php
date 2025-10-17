<?php
// Verificar se os dados estão definidos
if (!isset($this->data['plan'])) {
    echo "Erro: Dados do plano não encontrados.";
    return;
}

$plan = $this->data['plan'];
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-edit"></i> Atualizar Plano Estratégico
                    </h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?php echo $_ENV['URL_ADM']; ?>update-strategic-plan/<?= $plan['id'] ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="title" class="form-label">Título <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control" 
                                       id="title" 
                                       name="title" 
                                       value="<?= htmlspecialchars($plan['title'] ?? '') ?>" 
                                       required>
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-control" id="status" name="status" required>
                                    <option value="">Selecione o status</option>
                                    <option value="ativo" <?= ($plan['status'] ?? '') === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                                    <option value="inativo" <?= ($plan['status'] ?? '') === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                                    <option value="concluido" <?= ($plan['status'] ?? '') === 'concluido' ? 'selected' : '' ?>>Concluído</option>
                                    <option value="cancelado" <?= ($plan['status'] ?? '') === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12">
                                <label for="description" class="form-label">Descrição</label>
                                <textarea class="form-control" 
                                          id="description" 
                                          name="description" 
                                          rows="4"><?= htmlspecialchars($plan['description'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label for="what" class="form-label">O QUE (What)</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="what" 
                                       name="what" 
                                       value="<?= htmlspecialchars($plan['what'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="why" class="form-label">POR QUE (Why)</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="why" 
                                       name="why" 
                                       value="<?= htmlspecialchars($plan['why'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label for="where" class="form-label">ONDE (Where)</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="where" 
                                       name="where" 
                                       value="<?= htmlspecialchars($plan['where'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="when" class="form-label">QUANDO (When)</label>
                                <input type="date" 
                                       class="form-control" 
                                       id="when" 
                                       name="when" 
                                       value="<?= htmlspecialchars($plan['when'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label for="who" class="form-label">QUEM (Who)</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="who" 
                                       name="who" 
                                       value="<?= htmlspecialchars($plan['who'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="how" class="form-label">COMO (How)</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="how" 
                                       name="how" 
                                       value="<?= htmlspecialchars($plan['how'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label for="how_much" class="form-label">QUANTO (How Much)</label>
                                <div class="input-group">
                                    <span class="input-group-text">R$</span>
                                    <input type="text"
                                           name="how_much"
                                           id="how_much"
                                           class="form-control currency-input"
                                           placeholder="0,00"
                                           value="<?= htmlspecialchars($plan['how_much'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="progress" class="form-label">Progresso (%)</label>
                                <input type="number" 
                                       class="form-control" 
                                       id="progress" 
                                       name="progress" 
                                       min="0" 
                                       max="100" 
                                       value="<?= htmlspecialchars($plan['progress'] ?? '0') ?>">
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Atualizar Plano
                                </button>
                                <a href="<?php echo $_ENV['URL_ADM']; ?>list-strategic-plans" class="btn btn-secondary">
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

<script>
// Formatação de moeda
function formatCurrency(value) {
    // Remove tudo que não é dígito
    value = value.replace(/\D/g, '');

    // Converte para número e divide por 100 para ter centavos
    value = (parseInt(value) / 100).toFixed(2);

    // Formata com pontos para milhares e vírgula para decimais
    value = value.replace('.', ',');
    value = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

    return value;
}

// Aplicar formatação quando o usuário digitar
document.addEventListener('DOMContentLoaded', function() {
    const currencyInput = document.getElementById('how_much');

    if (currencyInput) {
        currencyInput.addEventListener('input', function(e) {
            let value = e.target.value;

            // Se não estiver vazio, formata
            if (value) {
                e.target.value = formatCurrency(value);
            }
        });

        // Formatar valor inicial se existir
        if (currencyInput.value) {
            currencyInput.value = formatCurrency(currencyInput.value);
        }
    }
});
</script>



