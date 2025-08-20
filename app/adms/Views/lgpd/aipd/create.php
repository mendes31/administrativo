<?php

use App\adms\Helpers\CSRFHelper;

?>
<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Avaliação de Impacto à Proteção de Dados (AIPD)</h2>

        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-aipd" class="text-decoration-none">LGPD</a>
            </li>
            <li class="breadcrumb-item">Cadastrar</li>
        </ol>

    </div>

    <div class="card mb-4 border-light shadow">

        <div class="card-header hstack gap-2">
            <span>Cadastrar</span>

            <span class="ms-auto d-sm-flex flex-row">
            <?php
                if (in_array('LgpdAipd', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}lgpd-aipd' class='btn btn-info btn-sm me-1 mb-1'><i class='fa-solid fa-list'></i> Listar</a> ";
                }
                ?>
            </span>

        </div>

        <div class="card-body">

            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <!-- Formulário para cadastrar uma nova AIPD -->
            <form action="" method="POST" class="row g-3">

                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_create_aipd'); ?>">

                <div class="col-12">
                    <label for="titulo" class="form-label">Título da AIPD *</label>
                    <input type="text" name="titulo" class="form-control" id="titulo" placeholder="Título da avaliação de impacto" value="<?php echo $this->data['form']['titulo'] ?? ''; ?>" required>
                </div>

                <div class="col-md-6 col-sm-12">
                    <label for="departamento_id" class="form-label">Departamento *</label>
                    <select name="departamento_id" class="form-select" id="departamento_id" required>
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
                    <label for="ropa_id" class="form-label">ROPA Relacionada</label>
                    <select name="ropa_id" class="form-select" id="ropa_id">
                        <option value="" selected>Selecione (opcional)</option>

                        <?php
                        // Verificar se existe ROPAs
                        if ($this->data['ropas'] ?? false) {

                            // percorrer o array de ROPAs
                            foreach ($this->data['ropas'] as $ropa) {

                                // Extrair as variáveis do array
                                extract($ropa);

                                // Verificar se deve manter selecionado a opção
                                $selected = isset($this->data['form']['ropa_id']) && $this->data['form']['ropa_id'] == $id ? 'selected' : '';

                                echo "<option value='$id' $selected >$atividade</option>";
                            }
                        }

                        ?>
                    </select>
                </div>

                <div class="col-md-6 col-sm-12">
                    <label for="responsavel_id" class="form-label">Responsável *</label>
                    <select name="responsavel_id" class="form-select" id="responsavel_id" required>
                        <option value="" selected>Selecione</option>

                        <?php
                        // Verificar se existe usuários
                        if ($this->data['usuarios'] ?? false) {

                            // percorrer o array de usuários
                            foreach ($this->data['usuarios'] as $usuario) {

                                // Extrair as variáveis do array
                                extract($usuario);

                                // Verificar se deve manter selecionado a opção
                                $selected = isset($this->data['form']['responsavel_id']) && $this->data['form']['responsavel_id'] == $id ? 'selected' : '';

                                echo "<option value='$id' $selected >$name</option>";
                            }
                        }

                        ?>
                    </select>
                </div>

                <div class="col-md-6 col-sm-12">
                    <label for="data_inicio" class="form-label">Data de Início *</label>
                    <input type="date" name="data_inicio" class="form-control" id="data_inicio" value="<?php echo $this->data['form']['data_inicio'] ?? ''; ?>" required>
                </div>

                <div class="col-md-6 col-sm-12">
                    <label for="data_fim_prevista" class="form-label">Data Fim Prevista</label>
                    <input type="date" name="data_fim_prevista" class="form-control" id="data_fim_prevista" value="<?php echo $this->data['form']['data_fim_prevista'] ?? ''; ?>">
                </div>

                <div class="col-md-6 col-sm-12">
                    <label for="nivel_risco" class="form-label">Nível de Risco *</label>
                    <select name="nivel_risco" class="form-select" id="nivel_risco" required>
                        <option value="" selected>Selecione</option>
                        <option value="Baixo" <?php echo isset($this->data['form']['nivel_risco']) && $this->data['form']['nivel_risco'] == 'Baixo' ? 'selected' : ''; ?>>Baixo</option>
                        <option value="Médio" <?php echo isset($this->data['form']['nivel_risco']) && $this->data['form']['nivel_risco'] == 'Médio' ? 'selected' : ''; ?>>Médio</option>
                        <option value="Alto" <?php echo isset($this->data['form']['nivel_risco']) && $this->data['form']['nivel_risco'] == 'Alto' ? 'selected' : ''; ?>>Alto</option>
                        <option value="Crítico" <?php echo isset($this->data['form']['nivel_risco']) && $this->data['form']['nivel_risco'] == 'Crítico' ? 'selected' : ''; ?>>Crítico</option>
                    </select>
                </div>

                <div class="col-md-6 col-sm-12">
                    <label for="status" class="form-label">Status *</label>
                    <select name="status" class="form-select" id="status" required>
                        <option value="" selected>Selecione</option>
                        <option value="Em Andamento" <?php echo isset($this->data['form']['status']) && $this->data['form']['status'] == 'Em Andamento' ? 'selected' : ''; ?>>Em Andamento</option>
                        <option value="Concluída" <?php echo isset($this->data['form']['status']) && $this->data['form']['status'] == 'Concluída' ? 'selected' : ''; ?>>Concluída</option>
                        <option value="Aprovada" <?php echo isset($this->data['form']['status']) && $this->data['form']['status'] == 'Aprovada' ? 'selected' : ''; ?>>Aprovada</option>
                        <option value="Revisão" <?php echo isset($this->data['form']['status']) && $this->data['form']['status'] == 'Revisão' ? 'selected' : ''; ?>>Revisão</option>
                    </select>
                </div>

                <div class="col-12">
                    <label for="objetivo" class="form-label">Objetivo da AIPD *</label>
                    <textarea name="objetivo" class="form-control" id="objetivo" placeholder="Descreva o objetivo da avaliação de impacto" rows="3" required><?php echo $this->data['form']['objetivo'] ?? ''; ?></textarea>
                </div>

                <div class="col-12">
                    <label for="escopo" class="form-label">Escopo da AIPD *</label>
                    <textarea name="escopo" class="form-control" id="escopo" placeholder="Descreva o escopo da avaliação" rows="3" required><?php echo $this->data['form']['escopo'] ?? ''; ?></textarea>
                </div>

                <div class="col-12">
                    <label for="metodologia" class="form-label">Metodologia</label>
                    <textarea name="metodologia" class="form-control" id="metodologia" placeholder="Descreva a metodologia utilizada" rows="3"><?php echo $this->data['form']['metodologia'] ?? ''; ?></textarea>
                </div>

                <div class="col-12">
                    <label for="riscos_identificados" class="form-label">Riscos Identificados</label>
                    <textarea name="riscos_identificados" class="form-control" id="riscos_identificados" placeholder="Descreva os riscos identificados" rows="3"><?php echo $this->data['form']['riscos_identificados'] ?? ''; ?></textarea>
                </div>

                <div class="col-12">
                    <label for="medidas_mitigacao" class="form-label">Medidas de Mitigação</label>
                    <textarea name="medidas_mitigacao" class="form-control" id="medidas_mitigacao" placeholder="Descreva as medidas de mitigação propostas" rows="3"><?php echo $this->data['form']['medidas_mitigacao'] ?? ''; ?></textarea>
                </div>

                <div class="col-12">
                    <label for="conclusoes" class="form-label">Conclusões</label>
                    <textarea name="conclusoes" class="form-control" id="conclusoes" placeholder="Conclusões da avaliação" rows="3"><?php echo $this->data['form']['conclusoes'] ?? ''; ?></textarea>
                </div>

                <div class="col-12">
                    <label for="recomendacoes" class="form-label">Recomendações</label>
                    <textarea name="recomendacoes" class="form-control" id="recomendacoes" placeholder="Recomendações da avaliação" rows="3"><?php echo $this->data['form']['recomendacoes'] ?? ''; ?></textarea>
                </div>

                <div class="col-12">
                    <label for="observacoes" class="form-label">Observações</label>
                    <textarea name="observacoes" class="form-control" id="observacoes" placeholder="Observações adicionais" rows="3"><?php echo $this->data['form']['observacoes'] ?? ''; ?></textarea>
                </div>

                <!-- Seção de Grupos de Dados -->
                <div class="col-12">
                    <div class="card border-secondary">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Grupos de Dados Envolvidos</h6>
                        </div>
                        <div class="card-body">
                            <?php
                            // Verificar se existe grupos de dados
                            if ($this->data['data_groups'] ?? false) {
                                echo '<div class="row">';
                                foreach ($this->data['data_groups'] as $data_group) {
                                    extract($data_group);
                                    $checked = isset($this->data['form']['data_groups']) && in_array($id, $this->data['form']['data_groups']) ? 'checked' : '';
                                    echo '<div class="col-md-6 col-lg-4 mb-2">';
                                    echo '<div class="form-check">';
                                    echo "<input class='form-check-input' type='checkbox' name='data_groups[]' value='$id' id='data_group_$id' $checked>";
                                    echo "<label class='form-check-label' for='data_group_$id'>$name</label>";
                                    echo '</div>';
                                    echo '</div>';
                                }
                                echo '</div>';
                            } else {
                                echo '<p class="text-muted">Nenhum grupo de dados disponível.</p>';
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary btn-sm">Cadastrar AIPD</button>
                    <a href="<?= $_ENV['URL_ADM'] ?>lgpd-aipd" class="btn btn-secondary btn-sm ms-2">Cancelar</a>
                </div>

            </form>

        </div>

    </div>

</div>
