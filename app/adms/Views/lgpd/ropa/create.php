<?php

use App\adms\Helpers\CSRFHelper;

?>
<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">ROPA</h2>

        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-ropa" class="text-decoration-none">LGPD</a>
            </li>
            <li class="breadcrumb-item">Cadastrar</li>
        </ol>

    </div>

    <div class="card mb-4 border-light shadow">

        <div class="card-header hstack gap-2">
            <span>Cadastrar</span>

            <span class="ms-auto d-sm-flex flex-row">
            <?php
                if (in_array('ListLgpdRopa', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}lgpd-ropa' class='btn btn-info btn-sm me-1 mb-1'><i class='fa-solid fa-list'></i> Listar</a> ";
                }
                ?>
            </span>

        </div>

        <div class="card-body">

            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <!-- Formulário para cadastrar um novo registro ROPA -->
            <form action="" method="POST" class="row g-3">

                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_create_ropa'); ?>">

                <div class="col-12">
                    <label for="codigo" class="form-label">Código</label>
                    <input type="text" name="codigo" class="form-control" id="codigo" placeholder="Código do registro ROPA" value="<?php echo $this->data['form']['codigo'] ?? ''; ?>">
                </div>

                <div class="col-12">
                    <label for="atividade" class="form-label">Atividade</label>
                    <input type="text" name="atividade" class="form-control" id="atividade" placeholder="Descrição da atividade de tratamento" value="<?php echo $this->data['form']['atividade'] ?? ''; ?>">
                </div>

                <div class="col-md-6 col-sm-12">
                    <label for="departamento_id" class="form-label">Departamento</label>
                    <select name="departamento_id" class="form-select" id="departamento_id">
                        <option value="" selected>Selecione</option>

                        <?php
                        // Verificar se existe departamentos
                        if ($this->data['departamentos'] ?? false) {

                            // percorrer o array de departamentos
                            foreach ($this->data['departamentos'] as $departamento) {

                                // Extrair as variáveis do array
                                extract($departamento);

                                // Verificar se deve manter selecionado a opção
                                $selected = isset($this->data['form']['departamento_id']) && $this->data['form']['departamento_id'] == $id ? 'selected' : '';

                                echo "<option value='$id' $selected >$name</option>";
                            }
                        }

                        ?>
                    </select>
                </div>

                <div class="col-md-6 col-sm-12">
                    <label for="base_legal" class="form-label">Base Legal</label>
                    <input type="text" name="base_legal" class="form-control" id="base_legal" placeholder="Base legal para o tratamento" value="<?php echo $this->data['form']['base_legal'] ?? ''; ?>">
                </div>

                <div class="col-md-6 col-sm-12">
                    <label for="retencao" class="form-label">Período de Retenção</label>
                    <input type="text" name="retencao" class="form-control" id="retencao" placeholder="Período de retenção dos dados" value="<?php echo $this->data['form']['retencao'] ?? ''; ?>">
                </div>

                <div class="col-md-6 col-sm-12">
                    <label for="riscos" class="form-label">Riscos</label>
                    <input type="text" name="riscos" class="form-control" id="riscos" placeholder="Riscos identificados" value="<?php echo $this->data['form']['riscos'] ?? ''; ?>">
                </div>

                <div class="col-12">
                    <label for="medidas_seguranca" class="form-label">Medidas de Segurança</label>
                    <textarea name="medidas_seguranca" class="form-control" id="medidas_seguranca" placeholder="Medidas de segurança implementadas" rows="3"><?php echo $this->data['form']['medidas_seguranca'] ?? ''; ?></textarea>
                </div>

                <div class="col-12">
                    <label for="observacoes" class="form-label">Observações</label>
                    <textarea name="observacoes" class="form-control" id="observacoes" placeholder="Observações adicionais" rows="3"><?php echo $this->data['form']['observacoes'] ?? ''; ?></textarea>
                </div>

                <div class="col-md-6 col-sm-12">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" class="form-select" id="status">
                        <option value="" selected>Selecione</option>
                        <option value="Ativo" <?php echo isset($this->data['form']['status']) && $this->data['form']['status'] == 'Ativo' ? 'selected' : ''; ?>>Ativo</option>
                        <option value="Inativo" <?php echo isset($this->data['form']['status']) && $this->data['form']['status'] == 'Inativo' ? 'selected' : ''; ?>>Inativo</option>
                    </select>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary btn-sm">Cadastrar</button>
                </div>

            </form>

        </div>

    </div>

</div> 