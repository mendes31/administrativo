<?php

use App\adms\Helpers\CSRFHelper;

?>

<div class="wrapper">
    <div class="row">
        <div class="top-list">
            <span class="title-content">Editar AIPD</span>
            <div class="top-list-right">
                <a href="<?= $_ENV['URL_ADM'] ?>lgpd-aipd" class="btn-info">Listar</a>
            </div>
        </div>

        <div class="content-adm-alert">
            <?php
            if (isset($_SESSION['msg'])) {
                echo $_SESSION['msg'];
                unset($_SESSION['msg']);
            }
            ?>
        </div>

        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Editar Avaliação de Impacto à Proteção de Dados (AIPD)</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="form-aipd" class="form-max-width">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="nome">Nome da AIPD <span class="text-danger">*</span></label>
                                <input type="text" name="nome" id="nome" class="form-control" 
                                       value="<?= $this->data['form']['nome'] ?? '' ?>" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="departamento_id">Departamento <span class="text-danger">*</span></label>
                                <select name="departamento_id" id="departamento_id" class="form-control" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($this->data['departamentos'] as $dept): ?>
                                        <option value="<?= $dept['id'] ?>" 
                                                <?= ($this->data['form']['departamento_id'] ?? '') == $dept['id'] ? 'selected' : '' ?>>
                                            <?= $dept['name'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="responsavel_id">Responsável <span class="text-danger">*</span></label>
                                <select name="responsavel_id" id="responsavel_id" class="form-control" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($this->data['usuarios'] as $user): ?>
                                        <option value="<?= $user['id'] ?>" 
                                                <?= ($this->data['form']['responsavel_id'] ?? '') == $user['id'] ? 'selected' : '' ?>>
                                            <?= $user['name'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="status">Status <span class="text-danger">*</span></label>
                                <select name="status" id="status" class="form-control" required>
                                    <option value="">Selecione...</option>
                                    <option value="rascunho" <?= ($this->data['form']['status'] ?? '') == 'rascunho' ? 'selected' : '' ?>>Rascunho</option>
                                    <option value="em_analise" <?= ($this->data['form']['status'] ?? '') == 'em_analise' ? 'selected' : '' ?>>Em Análise</option>
                                    <option value="aprovada" <?= ($this->data['form']['status'] ?? '') == 'aprovada' ? 'selected' : '' ?>>Aprovada</option>
                                    <option value="reprovada" <?= ($this->data['form']['status'] ?? '') == 'reprovada' ? 'selected' : '' ?>>Reprovada</option>
                                    <option value="concluida" <?= ($this->data['form']['status'] ?? '') == 'concluida' ? 'selected' : '' ?>>Concluída</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="data_inicio">Data de Início</label>
                                <input type="date" name="data_inicio" id="data_inicio" class="form-control" 
                                       value="<?= $this->data['form']['data_inicio'] ?? '' ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="data_fim">Data de Conclusão</label>
                                <input type="date" name="data_fim" id="data_fim" class="form-control" 
                                       value="<?= $this->data['form']['data_fim'] ?? '' ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="descricao">Descrição</label>
                        <textarea name="descricao" id="descricao" class="form-control" rows="4"><?= $this->data['form']['descricao'] ?? '' ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="objetivo">Objetivo</label>
                        <textarea name="objetivo" id="objetivo" class="form-control" rows="3"><?= $this->data['form']['objetivo'] ?? '' ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="escopo">Escopo</label>
                        <textarea name="escopo" id="escopo" class="form-control" rows="3"><?= $this->data['form']['escopo'] ?? '' ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="metodologia">Metodologia</label>
                        <textarea name="metodologia" id="metodologia" class="form-control" rows="3"><?= $this->data['form']['metodologia'] ?? '' ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="observacoes">Observações</label>
                        <textarea name="observacoes" id="observacoes" class="form-control" rows="3"><?= $this->data['form']['observacoes'] ?? '' ?></textarea>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-success" name="SendEditAipd" value="Salvar">
                            <i class="fas fa-save"></i> Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validação do formulário
    const form = document.getElementById('form-aipd');
    form.addEventListener('submit', function(e) {
        const nome = document.getElementById('nome').value.trim();
        const departamento = document.getElementById('departamento_id').value;
        const responsavel = document.getElementById('responsavel_id').value;
        const status = document.getElementById('status').value;
        
        if (!nome) {
            e.preventDefault();
            alert('Por favor, preencha o nome da AIPD.');
            document.getElementById('nome').focus();
            return false;
        }
        
        if (!departamento) {
            e.preventDefault();
            alert('Por favor, selecione o departamento.');
            document.getElementById('departamento_id').focus();
            return false;
        }
        
        if (!responsavel) {
            e.preventDefault();
            alert('Por favor, selecione o responsável.');
            document.getElementById('responsavel_id').focus();
            return false;
        }
        
        if (!status) {
            e.preventDefault();
            alert('Por favor, selecione o status.');
            document.getElementById('status').focus();
            return false;
        }
    });
});
</script>
