<?php

use App\adms\Helpers\CSRFHelper;

?>

<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Usuários</h2>

        <ol class="breadcrumb  mb-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>list-users" class="text-decoration-none">Usuários</a></li>
            <li class="breadcrumb-item">Cadastrar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span>
                Cadastrar
            </span>

            <span class="ms-auto d-sm-flex flex-row">

            <?php
                if (in_array('ListUsers', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}list-users' class='btn btn-info btn-sm me-1 mb-1'><i class='fa-solid fa-list-ul'></i> Listar</a> ";
                }
                ?>
            </span>
        </div>

        <div class="card-body">
            <?php
            // Inclui o arquivo que exibe mensagens de sucesso e erro
            include './app/adms/Views/partials/alerts.php';
            ?>

            <!-- Formulário para cadastrar um novo usuário -->
            <form action="" method="POST" class="row g-3" enctype="multipart/form-data">

                <!-- Campo oculto para o token CSRF para proteger o formulário contra ataques de falsificação de solicitação entre sites -->
                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_create_user'); ?>">

                <div class="col-md-4">
                    <label for="name" class="form-label">Nome</label>
                    <input type="text" name="name" class="form-control" id="name" placeholder="Nome completo" value="<?php echo $this->data['form']['name'] ?? ''; ?>">
                </div>

                <div class="col-md-4">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" id="email" placeholder="Digite o seu melhor email" value="<?php echo $this->data['form']['email'] ?? ''; ?>">
                </div>

                <div class="col-md-4">
                    <label for="username" class="form-label">Usuário</label>
                    <input type="text" name="username" class="form-control" id="username" placeholder="Digite um usuário disponível" value="<?php echo $this->data['form']['username'] ?? ''; ?>">
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
                                // Extrari as variáveis do array
                                extract($listPosition);
                                // Verificar se deve manter selecionado a opção
                                $selected = isset($this->data['form']['user_position_id']) && $this->data['form']['user_position_id'] == $id ? 'selected' : '';
                                echo "<option value='$id' $selected >$name</option>";
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
                                $selected = isset($this->data['form']['immediate_supervisor_id']) && $this->data['form']['immediate_supervisor_id'] == $supervisor['id'] ? 'selected' : '';
                                echo "<option value='{$supervisor['id']}' $selected>{$supervisor['name']}</option>";
                            }
                        }
                        ?>
                    </select>
                    <div class="form-text">
                        <i class="fas fa-sitemap me-1"></i>
                        Este campo define a hierarquia organizacional. Deixe vazio apenas para cargos de direção/CEO.
                    </div>
                </div>

                <div class="col-md-6">
                    <label for="password" class="form-label">Senha</label>
                    <input type="password" name="password" class="form-control" id="password" placeholder="Senha minímo 6 caracteres e deve conter letra, número e caractere especial." value="<?php echo $this->data['form']['password'] ?? ''; ?>"
                       oninput="this.value = this.value.replace(/\s/g, '')" 
                       onpaste="this.value = this.value.replace(/\s/g, '')"
                       autocomplete="new-password">
                </div>

                <div class="col-md-6">
                    <label for="confirm_password" class="form-label">Confirmar Senha</label>
                    <input type="password" name="confirm_password" class="form-control" id="confirm_password" placeholder="Confirmar a senha." value="<?php echo $this->data['form']['confirm_password'] ?? ''; ?>"
                       oninput="this.value = this.value.replace(/\s/g, '')" 
                       onpaste="this.value = this.value.replace(/\s/g, '')"
                       autocomplete="new-password">
                </div>

                <!-- <div class="col-md-4">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" class="form-select" id="status">
                        <option value="Ativo" selected>Ativo</option>
                        <option value="Inativo">Inativo</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="bloqueado" class="form-label">Bloqueado</label>
                    <select name="bloqueado" class="form-select" id="bloqueado">
                        <option value="Não" selected>Não</option>
                        <option value="Sim">Sim</option>
                    </select> -->
                <!-- </div> -->
                <div class="col-md-4">
                    <label for="tentativas_login" class="form-label">Tentativas de Login</label>
                    <input type="number" name="tentativas_login" class="form-control" id="tentativas_login" value="0" readonly>
                </div>
                <div class="col-md-6">
                    <label for="image" class="form-label">Imagem do Usuário</label>
                    <input type="file" name="image" class="form-control" id="image" accept="image/*">
                    <small class="form-text text-muted">Formatos permitidos: JPG, PNG, GIF. Tamanho máximo: 2MB.</small>
                </div>
                <div class="col-md-4">
                    <label for="data_nascimento" class="form-label">Data de Nascimento</label>
                    <input type="date" name="data_nascimento" class="form-control" id="data_nascimento" value="<?php echo $this->data['form']['data_nascimento'] ?? ''; ?>">
                </div>
                <div class="col-md-4">
                    <label for="data_admissao" class="form-label">Data de Admissão</label>
                    <input type="date" name="data_admissao" class="form-control" id="data_admissao" value="<?php echo $this->data['form']['data_admissao'] ?? ''; ?>">
                    <div class="form-text">Data em que o colaborador foi admitido na empresa</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label><br>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="status" name="status" value="Ativo" checked>
                        <label class="form-check-label" for="status">Ativo</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Bloqueado</label><br>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="bloqueado" name="bloqueado" value="Sim">
                        <label class="form-check-label" for="bloqueado">Sim</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Senha Nunca Expira</label><br>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="senha_nunca_expira" name="senha_nunca_expira" value="Sim">
                        <label class="form-check-label" for="senha_nunca_expira">Sim</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Modificar Senha no Próximo Logon</label><br>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="modificar_senha_proximo_logon" name="modificar_senha_proximo_logon" value="Sim">
                        <label class="form-check-label" for="modificar_senha_proximo_logon">Sim</label>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Mensagem de boas-vindas</label>
                    <div class="form-text mb-1">
                        Ao marcar, será enviada uma mensagem de boas-vindas com o link de acesso e orientação para troca de senha.
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="enviar_boas_vindas_email" name="enviar_boas_vindas_email" value="1"
                                    <?php echo !empty($this->data['form']['enviar_boas_vindas_email']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="enviar_boas_vindas_email">
                                    Enviar por e-mail
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="enviar_boas_vindas_whatsapp" name="enviar_boas_vindas_whatsapp" value="1"
                                    <?php echo !empty($this->data['form']['enviar_boas_vindas_whatsapp']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="enviar_boas_vindas_whatsapp">
                                    Enviar por WhatsApp
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-success">Cadastrar</button>
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