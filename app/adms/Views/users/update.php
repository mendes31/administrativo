<?php

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\ImageHelper;

?>

<div class="container-fluid px-4">

    <div class="mb-1 d-flex flex-column flex-sm-row gap-2">
        <h2 class="mt-3">Usuários</h2>

        <ol class="breadcrumb  mb-3 mt-0 mt-sm-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>list-users" class="text-decoration-none">Usuários</a></li>
            <li class="breadcrumb-item">Editar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">

            <span>Editar </span>

            <span class="ms-auto d-sm-flex flex-row">

            <?php
                if (in_array('ListUsers', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}list-users' class='btn btn-primary btn-sm me-1 mb-1'><i class='fa-solid fa-list-ul'></i> Listar</a> ";
                }

                $id = ($this->data['form']['id'] ?? '');
                if (in_array('ViewUser', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}view-user/$id' class='btn btn-primary btn-sm me-1 mb-1'><i class='fa-regular fa-eye'></i> Visualizar</a> ";
                }
                ?>
            </span>

        </div>

        <div class="card-body">
            <?php
            // Inclui o arquivo que exibe mensagens de sucesso e erro
            include './app/adms/Views/partials/alerts.php';
            ?>

            <form action="" method="POST" class="row g-3" enctype="multipart/form-data">

                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_update_user'); ?>">

                <input type="hidden" name="id" id="id" value="<?php echo $this->data['form']['id'] ?? ''; ?>">

                <div class="col-md-4">
                    <label for="name" class="form-label">Nome</label>
                    <input type="text" name="name" class="form-control" id="name" placeholder="Nome completo" value="<?php echo $this->data['form']['name'] ?? ''; ?>">
                </div>

                <div class="col-md-4">
                    <label for="email" class="form-label">E-mail</label>
                    <input type="email" name="email" class="form-control" id="email" placeholder="Melhor e-mail" value="<?php echo $this->data['form']['email'] ?? ''; ?>">
                </div>

                <div class="col-md-4">
                    <label for="username" class="form-label">Usuário</label>
                    <input type="text" name="username" class="form-control" id="username" placeholder="Nome de usuário" value="<?php echo $this->data['form']['username'] ?? ''; ?>">
                </div>

                <div class="col-md-6">
                    <label for="cpf" class="form-label">CPF</label>
                    <input type="text" name="cpf" class="form-control" id="cpf" placeholder="000.000.000-00" value="<?php echo $this->data['form']['cpf'] ?? ''; ?>" maxlength="14">
                </div>

                <div class="col-md-6">
                    <label for="celular" class="form-label">Celular</label>
                    <input type="text" name="celular" class="form-control" id="celular" placeholder="(00) 00000-0000" value="<?php echo $this->data['form']['celular'] ?? ''; ?>" maxlength="20">
                </div>

                <div class="col-md-6">
                    <label for="user_department_id" class="form-label">Departamento</label>
                    <select name="user_department_id" class="form-select" id="user_department_id">
                        <option value="" selected>Selecione</option>
                        <?php
                        // Verificar se existe pacotes
                        if ($this->data['listDepartments'] ?? false) {
                            // percorrer o array de pacotes
                            foreach ($this->data['listDepartments'] as $listDepartment) {
                                // Extrari as variáveis do array
                                extract($listDepartment);
                                // Verificar se deve manter selecionado a opção
                                $selected = isset($this->data['form']['user_department_id']) && $this->data['form']['user_department_id'] == $id ? 'selected' : '';
                                echo "<option value='$id' $selected >$name</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="user_position_id" class="form-label">Cargo</label>
                    <select name="user_position_id" class="form-select" id="user_position_id">
                        <option value="" selected>Selecione</option>
                        <?php
                        // Verificar se existe o cargo 
                        if ($this->data['listPositions'] ?? false) {
                            // percorrer o array de cargo
                            foreach ($this->data['listPositions'] as $listPosition) {
                                extract($listPosition);
                                $selected = isset($this->data['form']['user_position_id']) && (int)$this->data['form']['user_position_id'] === (int)$id ? 'selected' : '';
                                echo '<option value="' . (int)$id . '" ' . $selected . '>'
                                    . htmlspecialchars((string)$name, ENT_QUOTES, 'UTF-8') . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="immediate_supervisor_id" class="form-label">
                        Supervisor Imediato
                        <i class="fas fa-info-circle text-info" title="Usuário ao qual este colaborador se reporta diretamente. Gerentes podem visualizar dados de seus subordinados no CRM."></i>
                    </label>
                    <select name="immediate_supervisor_id" class="form-select" id="immediate_supervisor_id">
                        <option value="">Sem supervisor (nível superior)</option>
                        <?php
                        // Lista de usuários ativos para selecionar como supervisor
                        if ($this->data['listSupervisors'] ?? false) {
                            foreach ($this->data['listSupervisors'] as $supervisor) {
                                // Não permitir selecionar a si mesmo como supervisor
                                if (isset($this->data['form']['id']) && $supervisor['id'] == $this->data['form']['id']) {
                                    continue;
                                }
                                
                                $selected = isset($this->data['form']['immediate_supervisor_id']) && $this->data['form']['immediate_supervisor_id'] == $supervisor['id'] ? 'selected' : '';
                                echo "<option value='{$supervisor['id']}' $selected>{$supervisor['name']}</option>";
                            }
                        }
                        ?>
                    </select>
                    <div class="form-text">
                        <i class="fas fa-sitemap me-1"></i>
                        <?php
                        // Mostrar quantos subordinados este usuário tem
                        if (isset($this->data['subordinates_count']) && $this->data['subordinates_count'] > 0) {
                            echo "<span class='text-success'><strong>Este usuário é supervisor de {$this->data['subordinates_count']} colaborador(es)</strong></span>";
                        } else {
                            echo "Deixe vazio apenas para cargos de direção/CEO.";
                        }
                        ?>
                    </div>
                </div>

                <div class="col-md-4">
                    <label for="tentativas_login" class="form-label">Tentativas de Login</label>
                    <input type="number" name="tentativas_login" class="form-control" id="tentativas_login" value="<?php echo $this->data['form']['tentativas_login'] ?? '0'; ?>" readonly>
                </div>

                <div class="col-md-6">
                    <label for="image" class="form-label">Imagem do Usuário</label>
                    <input type="file" name="image" class="form-control" id="image" accept="image/*">
                    <small class="form-text text-muted">Formatos permitidos: JPG, PNG, GIF. Tamanho máximo: 2MB.</small>
                    <?php if (!empty($this->data['form']['image']) && $this->data['form']['image'] !== 'icon_user.png'): ?>
                        <div class="mt-2">
                            <?php
                            $editAvatarPath = 'users/' . ($this->data['form']['id'] ?? 0) . '/' . $this->data['form']['image'];
                            echo ImageHelper::displayImage($editAvatarPath, [
                                'alt' => 'Imagem atual',
                                'style' => 'max-width: 120px; max-height: 120px; border-radius: 8px; object-fit: cover;',
                            ], 'icon_user.png', 'users');
                            ?>
                        </div>
                    <?php else: ?>
                        <div class="mt-2 text-muted">Sem imagem</div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label for="data_nascimento" class="form-label">Data de Nascimento</label>
                    <input type="date" name="data_nascimento" class="form-control" id="data_nascimento" value="<?php echo $this->data['form']['data_nascimento'] ?? ''; ?>">
                </div>
                <div class="col-md-4">
                    <label for="sexo" class="form-label">Sexo</label>
                    <select name="sexo" id="sexo" class="form-select">
                        <?php $sx = (string)($this->data['form']['sexo'] ?? ''); ?>
                        <option value="" <?php echo $sx === '' ? 'selected' : ''; ?>>Selecione</option>
                        <option value="M" <?php echo $sx === 'M' ? 'selected' : ''; ?>>Masculino</option>
                        <option value="F" <?php echo $sx === 'F' ? 'selected' : ''; ?>>Feminino</option>
                        <option value="O" <?php echo $sx === 'O' ? 'selected' : ''; ?>>Outros</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="filhos" class="form-label">Filho(s)</label>
                    <select name="filhos" id="filhos" class="form-select">
                        <?php $fh = (string)($this->data['form']['filhos'] ?? ''); ?>
                        <option value="" <?php echo $fh === '' ? 'selected' : ''; ?>>Selecione</option>
                        <option value="S" <?php echo $fh === 'S' ? 'selected' : ''; ?>>Sim</option>
                        <option value="N" <?php echo $fh === 'N' ? 'selected' : ''; ?>>Não</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="data_admissao" class="form-label">Data de Admissão</label>
                    <input type="date" name="data_admissao" class="form-control" id="data_admissao" value="<?php echo $this->data['form']['data_admissao'] ?? ''; ?>">
                    <div class="form-text">Data em que o colaborador foi admitido na empresa</div>
                </div>

                <div class="col-md-4">
                    <label for="data_desligamento" class="form-label">Data de Desligamento</label>
                    <input type="date" name="data_desligamento" class="form-control" id="data_desligamento" value="<?php echo $this->data['form']['data_desligamento'] ?? ''; ?>">
                    <div class="form-text">Deixe em branco se o colaborador ainda está ativo</div>
                </div>

                <div class="col-md-12" id="motivo_desligamento_container" style="display: <?php echo !empty($this->data['form']['data_desligamento']) ? 'block' : 'none'; ?>;">
                    <label for="motivo_desligamento" class="form-label">Motivo do Desligamento</label>
                    <input type="text" name="motivo_desligamento" class="form-control" id="motivo_desligamento" placeholder="Ex: Pedido de demissão, Demissão sem justa causa, Aposentadoria, etc." value="<?php echo $this->data['form']['motivo_desligamento'] ?? ''; ?>" maxlength="255">
                    <div class="form-text">Informe o motivo do desligamento (opcional)</div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Status</label><br>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="status" name="status" value="Ativo" <?php echo (isset($this->data['form']['status']) && $this->data['form']['status'] == 'Ativo') ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="status">Ativo</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Bloqueado</label><br>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="bloqueado" name="bloqueado" value="Sim" <?php echo (isset($this->data['form']['bloqueado']) && $this->data['form']['bloqueado'] == 'Sim') ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="bloqueado">Sim</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Senha Nunca Expira</label><br>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="senha_nunca_expira" name="senha_nunca_expira" value="Sim" <?php echo (isset($this->data['form']['senha_nunca_expira']) && $this->data['form']['senha_nunca_expira'] == 'Sim') ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="senha_nunca_expira">Sim</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="super_usuario">Super usuário <i class="fas fa-user-shield text-warning" title="Acesso total ao sistema, como Super Administrador"></i></label><br>
                    <?php if (!empty($this->data['can_manage_super_usuario_for_this_user'])): ?>
                        <?php $superUsuarioAtivo = (int)($this->data['form']['super_usuario'] ?? 0) === 1; ?>
                        <input type="hidden" name="super_usuario" value="0">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="super_usuario" name="super_usuario" value="1"
                                <?php echo $superUsuarioAtivo ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="super_usuario">Sim</label>
                        </div>
                    <?php else: ?>
                        <p class="mb-1 fw-semibold"><?php echo (int)($this->data['form']['super_usuario'] ?? 0) === 1 ? 'Sim' : 'Não'; ?></p>
                        <p class="text-muted small mb-0">
                            <?php if (!empty($this->data['editing_own_user'])): ?>
                                Não é possível alterar o seu próprio perfil <strong>Super usuário</strong> ao editar o seu cadastro. Peça a outro <strong>Super Administrador</strong> ou a outro utilizador já com este perfil.
                            <?php else: ?>
                                Apenas <strong>Super Administrador</strong> ou utilizador com perfil <strong>Super usuário</strong> pode alterar esta opção em cadastros de terceiros.
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>

                <div class="col-12 mt-2">
                    <div class="alert alert-warning border py-2 px-3 mb-0 small rounded-1" role="note" aria-label="Explicação do campo Super usuário">
                        <div class="d-flex gap-2 align-items-start">
                            <i class="fas fa-exclamation-triangle text-dark mt-1 flex-shrink-0" aria-hidden="true"></i>
                            <div class="text-body">
                                <div class="fw-semibold text-dark mb-1">Sobre o perfil <span class="text-nowrap">«Super usuário»</span></div>
                                <p class="mb-0">Quem o marca (em <strong>outro</strong> cadastro) concede <strong>acesso total ao sistema</strong>, equivalente ao <strong>Super Administrador</strong>, independentemente do nível de permissões associado. Só <strong>Super Administrador</strong> ou utilizador já <strong>Super usuário</strong> pode definir isto; <strong>não pode atribuir a si próprio</strong> ao editar o seu utilizador.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-warning btn-sm">Salvar</button>
                </div>

            </form>

        </div>
    </div>

</div>

<script>
// Máscara para CPF
document.getElementById('cpf').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length <= 11) {
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        e.target.value = value;
    }
});

// Mostrar/ocultar campo de motivo de desligamento e lógica de recontratação
document.addEventListener('DOMContentLoaded', function() {
    const dataDesligamentoInput = document.getElementById('data_desligamento');
    const motivoContainer = document.getElementById('motivo_desligamento_container');
    const motivoInput = document.getElementById('motivo_desligamento');
    const dataAdmissaoInput = document.getElementById('data_admissao');
    const statusCheckbox = document.getElementById('status');
    
    if (dataDesligamentoInput && motivoContainer) {
        // Verificar estado inicial
        if (dataDesligamentoInput.value) {
            motivoContainer.style.display = 'block';
        }
        
        // Evento de mudança na data de desligamento
        dataDesligamentoInput.addEventListener('change', function(e) {
            if (e.target.value) {
                // Data de desligamento preenchida - mostrar campo motivo
                motivoContainer.style.display = 'block';
            } else {
                // Data de desligamento removida - ocultar e limpar motivo (recontratação)
                motivoContainer.style.display = 'none';
                if (motivoInput) {
                    motivoInput.value = '';
                }
                
                // Se havia data de desligamento antes, é uma recontratação
                // Ativar status automaticamente
                if (statusCheckbox && !statusCheckbox.checked) {
                    if (confirm('Este colaborador está sendo recontratado? O status será alterado para Ativo.')) {
                        statusCheckbox.checked = true;
                    }
                }
            }
        });
    }
    
    // Lógica de recontratação: quando data de admissão é alterada e há data de desligamento
    if (dataAdmissaoInput && dataDesligamentoInput) {
        dataAdmissaoInput.addEventListener('change', function(e) {
            // Se há data de desligamento e a nova data de admissão é posterior, pode ser recontratação
            if (dataDesligamentoInput.value && e.target.value) {
                const dataAdmissao = new Date(e.target.value);
                const dataDesligamento = new Date(dataDesligamentoInput.value);
                
                if (dataAdmissao > dataDesligamento) {
                    if (confirm('A data de admissão é posterior à data de desligamento. Deseja limpar a data de desligamento (recontratação)?')) {
                        dataDesligamentoInput.value = '';
                        if (motivoContainer) motivoContainer.style.display = 'none';
                        if (motivoInput) motivoInput.value = '';
                        if (statusCheckbox) statusCheckbox.checked = true;
                    }
                }
            }
        });
    }
});

// Máscara para Celular
document.getElementById('celular').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length <= 11) {
        value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
        value = value.replace(/(\d)(\d{4})$/, '$1-$2');
        e.target.value = value;
    }
});
</script>