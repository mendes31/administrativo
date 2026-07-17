<?php
/**
 * Campos do formulário de usuário organizados em abas.
 * Esperado: $userFormMode = 'create'|'update' e $this->data['form'].
 */
$userFormMode = $userFormMode ?? 'create';
$isUpdate = $userFormMode === 'update';
$activeTab = (string)($_POST['user_form_active_tab'] ?? ($this->data['form']['user_form_active_tab'] ?? 'usuario'));
$allowedTabs = ['usuario', 'pessoais', 'endereco', 'contratuais', 'formacoes'];
if (!in_array($activeTab, $allowedTabs, true)) {
    $activeTab = 'usuario';
}
$form = $this->data['form'] ?? [];
?>
<input type="hidden" name="user_form_active_tab" id="user_form_active_tab" value="<?php echo htmlspecialchars($activeTab, ENT_QUOTES, 'UTF-8'); ?>">

<ul class="nav nav-tabs flex-column flex-sm-row mb-3" id="userFormTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link <?php echo $activeTab === 'usuario' ? 'active' : ''; ?>" id="tab-usuario-btn" data-bs-toggle="tab" data-bs-target="#tab-usuario" type="button" role="tab" data-tab-key="usuario">
            <i class="fas fa-user me-1"></i>Usuário
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?php echo $activeTab === 'pessoais' ? 'active' : ''; ?>" id="tab-pessoais-btn" data-bs-toggle="tab" data-bs-target="#tab-pessoais" type="button" role="tab" data-tab-key="pessoais">
            <i class="fas fa-id-card me-1"></i>Dados Pessoais
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?php echo $activeTab === 'endereco' ? 'active' : ''; ?>" id="tab-endereco-btn" data-bs-toggle="tab" data-bs-target="#tab-endereco" type="button" role="tab" data-tab-key="endereco">
            <i class="fas fa-map-marker-alt me-1"></i>Endereço
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?php echo $activeTab === 'contratuais' ? 'active' : ''; ?>" id="tab-contratuais-btn" data-bs-toggle="tab" data-bs-target="#tab-contratuais" type="button" role="tab" data-tab-key="contratuais">
            <i class="fas fa-briefcase me-1"></i>Dados Contratuais
        </button>
    </li>
    <?php if ($isUpdate): ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php echo $activeTab === 'formacoes' ? 'active' : ''; ?>" id="tab-formacoes-btn" data-bs-toggle="tab" data-bs-target="#tab-formacoes" type="button" role="tab" data-tab-key="formacoes">
                <i class="fas fa-graduation-cap me-1"></i>Formações
            </button>
        </li>
    <?php endif; ?>
</ul>

<div class="tab-content" id="userFormTabsContent">
    <div class="tab-pane fade <?php echo $activeTab === 'usuario' ? 'show active' : ''; ?>" id="tab-usuario" role="tabpanel">
        <div class="row g-3">
            <div class="col-md-4">
                <label for="name" class="form-label">Nome</label>
                <input type="text" name="name" class="form-control" id="name" placeholder="Nome completo" value="<?php echo htmlspecialchars((string)($form['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-4">
                <label for="email" class="form-label">E-mail corporativo</label>
                <input type="email" name="email" class="form-control" id="email" placeholder="E-mail corporativo" value="<?php echo htmlspecialchars((string)($form['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-4">
                <label for="username" class="form-label">Usuário</label>
                <input type="text" name="username" class="form-control" id="username" placeholder="Nome de usuário" value="<?php echo htmlspecialchars((string)($form['username'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-6">
                <label for="cpf" class="form-label">CPF</label>
                <input type="text" name="cpf" class="form-control" id="cpf" placeholder="000.000.000-00" value="<?php echo htmlspecialchars((string)($form['cpf'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" maxlength="14">
            </div>
            <div class="col-md-6">
                <label for="celular" class="form-label">Celular</label>
                <input type="text" name="celular" class="form-control" id="celular" placeholder="(00) 00000-0000" value="<?php echo htmlspecialchars((string)($form['celular'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" maxlength="20">
            </div>
            <div class="col-md-6">
                <label for="user_department_id" class="form-label">Departamento</label>
                <select name="user_department_id" class="form-select" id="user_department_id">
                    <option value="" selected>Selecione</option>
                    <?php if ($this->data['listDepartments'] ?? false): ?>
                        <?php foreach ($this->data['listDepartments'] as $listDepartment): ?>
                            <?php
                            $depId = (int)($listDepartment['id'] ?? 0);
                            $depName = (string)($listDepartment['name'] ?? '');
                            $selected = isset($form['user_department_id']) && (int)$form['user_department_id'] === $depId ? 'selected' : '';
                            ?>
                            <option value="<?php echo $depId; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($depName, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label for="user_position_id" class="form-label">Cargo</label>
                <select name="user_position_id" class="form-select" id="user_position_id">
                    <option value="" selected>Selecione</option>
                    <?php if ($this->data['listPositions'] ?? false): ?>
                        <?php foreach ($this->data['listPositions'] as $listPosition): ?>
                            <?php
                            $posId = (int)($listPosition['id'] ?? 0);
                            $posName = (string)($listPosition['name'] ?? '');
                            $selected = isset($form['user_position_id']) && (int)$form['user_position_id'] === $posId ? 'selected' : '';
                            ?>
                            <option value="<?php echo $posId; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($posName, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label for="immediate_supervisor_id" class="form-label">
                    Supervisor Imediato
                    <i class="fas fa-info-circle text-info" title="Usuário ao qual este colaborador se reporta diretamente."></i>
                </label>
                <select name="immediate_supervisor_id" class="form-select" id="immediate_supervisor_id">
                    <option value="">Sem supervisor (nível superior)</option>
                    <?php if ($this->data['listSupervisors'] ?? false): ?>
                        <?php foreach ($this->data['listSupervisors'] as $supervisor): ?>
                            <?php
                            if ($isUpdate && isset($form['id']) && (int)$supervisor['id'] === (int)$form['id']) {
                                continue;
                            }
                            $selected = isset($form['immediate_supervisor_id']) && (int)$form['immediate_supervisor_id'] === (int)$supervisor['id'] ? 'selected' : '';
                            ?>
                            <option value="<?php echo (int)$supervisor['id']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars((string)$supervisor['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <div class="form-text">
                    <?php if ($isUpdate && isset($this->data['subordinates_count']) && $this->data['subordinates_count'] > 0): ?>
                        <span class="text-success"><strong>Este usuário é supervisor de <?php echo (int)$this->data['subordinates_count']; ?> colaborador(es)</strong></span>
                    <?php else: ?>
                        Deixe vazio apenas para cargos de direção/CEO.
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <label for="adms_work_shift_id" class="form-label">Turno de trabalho</label>
                <select name="adms_work_shift_id" class="form-select" id="adms_work_shift_id">
                    <option value="">Selecione</option>
                    <?php if ($this->data['listWorkShifts'] ?? false): ?>
                        <?php foreach ($this->data['listWorkShifts'] as $listWorkShift): ?>
                            <?php
                            $wid = (int)($listWorkShift['id'] ?? 0);
                            $wname = (string)($listWorkShift['name'] ?? '');
                            $selected = isset($form['adms_work_shift_id']) && (int)$form['adms_work_shift_id'] === $wid ? 'selected' : '';
                            ?>
                            <option value="<?php echo $wid; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($wname, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <?php if (!$isUpdate): ?>
                <div class="col-md-6">
                    <label for="password" class="form-label">Senha</label>
                    <input type="password" name="password" class="form-control" id="password" placeholder="Senha mínimo 6 caracteres..." value="<?php echo htmlspecialchars((string)($form['password'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                        oninput="this.value = this.value.replace(/\s/g, '')"
                        onpaste="this.value = this.value.replace(/\s/g, '')"
                        autocomplete="new-password">
                </div>
                <div class="col-md-6">
                    <label for="confirm_password" class="form-label">Confirmar Senha</label>
                    <input type="password" name="confirm_password" class="form-control" id="confirm_password" placeholder="Confirmar a senha." value="<?php echo htmlspecialchars((string)($form['confirm_password'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                        oninput="this.value = this.value.replace(/\s/g, '')"
                        onpaste="this.value = this.value.replace(/\s/g, '')"
                        autocomplete="new-password">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Gerar senha automática</label><br>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="gerar_senha" name="gerar_senha" value="1" <?php echo !empty($form['gerar_senha']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="gerar_senha">Usar data de nascimento como senha inicial (ddmmaaaa)</label>
                    </div>
                </div>
            <?php endif; ?>

            <div class="col-md-4">
                <label for="tentativas_login" class="form-label">Tentativas de Login</label>
                <input type="number" name="tentativas_login" class="form-control" id="tentativas_login" value="<?php echo htmlspecialchars((string)($form['tentativas_login'] ?? '0'), ENT_QUOTES, 'UTF-8'); ?>" readonly>
            </div>
            <div class="col-md-6">
                <label for="image" class="form-label">Imagem do Usuário</label>
                <input type="file" name="image" class="form-control" id="image" accept="image/*">
                <small class="form-text text-muted">Formatos permitidos: JPG, PNG, GIF. Tamanho máximo: 2MB.</small>
                <?php if ($isUpdate): ?>
                    <div class="mt-2">
                        <?php
                        $editUserId = (int)($form['id'] ?? 0);
                        $editImageName = (string)($form['image'] ?? '');
                        if (\App\adms\Helpers\ImageHelper::userImageExists($editUserId, $editImageName)) {
                            echo \App\adms\Helpers\ImageHelper::displayImage('users/' . $editUserId . '/' . $editImageName, [
                                'alt' => 'Imagem atual',
                                'style' => 'max-width: 120px; max-height: 120px; border-radius: 8px; object-fit: cover;',
                            ], 'icon_user.png', 'users');
                        } else {
                            echo \App\adms\Helpers\ImageHelper::renderInitialsAvatar((string)($form['name'] ?? 'Usuário'), 120, [
                                'style' => 'border-radius: 8px;',
                            ]);
                        }
                        ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-12"><hr class="my-1"></div>

            <div class="col-md-3">
                <label class="form-label">Status</label><br>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="status" name="status" value="Ativo" <?php
                        if ($isUpdate) {
                            echo (isset($form['status']) && $form['status'] == 'Ativo') ? 'checked' : '';
                        } else {
                            echo 'checked';
                        }
                    ?>>
                    <label class="form-check-label" for="status">Ativo</label>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Bloqueado</label><br>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="bloqueado" name="bloqueado" value="Sim" <?php echo (isset($form['bloqueado']) && $form['bloqueado'] == 'Sim') ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="bloqueado">Sim</label>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Senha Nunca Expira</label><br>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="senha_nunca_expira" name="senha_nunca_expira" value="Sim" <?php echo (isset($form['senha_nunca_expira']) && $form['senha_nunca_expira'] == 'Sim') ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="senha_nunca_expira">Sim</label>
                </div>
            </div>

            <?php if (!$isUpdate): ?>
                <div class="col-md-3">
                    <label class="form-label">Modificar Senha no Próximo Logon</label><br>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="modificar_senha_proximo_logon" name="modificar_senha_proximo_logon" value="Sim" <?php echo (isset($form['modificar_senha_proximo_logon']) && $form['modificar_senha_proximo_logon'] == 'Sim') ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="modificar_senha_proximo_logon">Sim</label>
                    </div>
                </div>
            <?php endif; ?>

            <div class="col-md-3">
                <label class="form-label" for="super_usuario">Super usuário <i class="fas fa-user-shield text-warning"></i></label><br>
                <?php if ($isUpdate): ?>
                    <?php if (!empty($this->data['can_manage_super_usuario_for_this_user'])): ?>
                        <?php $superUsuarioAtivo = (int)($form['super_usuario'] ?? 0) === 1; ?>
                        <input type="hidden" name="super_usuario" value="0">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="super_usuario" name="super_usuario" value="1" <?php echo $superUsuarioAtivo ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="super_usuario">Sim</label>
                        </div>
                    <?php else: ?>
                        <p class="mb-1 fw-semibold"><?php echo (int)($form['super_usuario'] ?? 0) === 1 ? 'Sim' : 'Não'; ?></p>
                        <p class="text-muted small mb-0">
                            <?php if (!empty($this->data['editing_own_user'])): ?>
                                Não é possível alterar o seu próprio perfil Super usuário.
                            <?php else: ?>
                                Apenas Super Administrador ou Super usuário pode alterar esta opção.
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                <?php else: ?>
                    <?php if (!empty($this->data['can_manage_super_usuario_on_create'])): ?>
                        <input type="hidden" name="super_usuario" value="0">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="super_usuario" name="super_usuario" value="1" <?php echo (int)($form['super_usuario'] ?? 0) === 1 ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="super_usuario">Sim</label>
                        </div>
                    <?php else: ?>
                        <p class="mb-0 small text-muted">Novo utilizador será criado sem perfil Super usuário.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="col-12">
                <div class="alert alert-warning border py-2 px-3 mb-0 small rounded-1" role="note">
                    <div class="fw-semibold text-dark mb-1">Sobre o perfil «Super usuário»</div>
                    <p class="mb-0">Concede acesso total ao sistema, equivalente ao Super Administrador. Só pode ser definido por Super Administrador ou Super usuário, e não no próprio cadastro ao editar.</p>
                </div>
            </div>

            <?php if (!$isUpdate): ?>
                <div class="col-md-6">
                    <label class="form-label">Mensagem de boas-vindas</label>
                    <div class="form-text mb-1">Ao marcar, será enviada mensagem com link de acesso e orientação para troca de senha.</div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="enviar_boas_vindas_email" name="enviar_boas_vindas_email" value="1" <?php echo !empty($form['enviar_boas_vindas_email']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="enviar_boas_vindas_email">Enviar por e-mail</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="enviar_boas_vindas_whatsapp" name="enviar_boas_vindas_whatsapp" value="1" <?php echo !empty($form['enviar_boas_vindas_whatsapp']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="enviar_boas_vindas_whatsapp">Enviar por WhatsApp</label>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="tab-pane fade <?php echo $activeTab === 'pessoais' ? 'show active' : ''; ?>" id="tab-pessoais" role="tabpanel">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="email_pessoal" class="form-label">E-mail pessoal</label>
                <input type="email" name="email_pessoal" class="form-control" id="email_pessoal" placeholder="E-mail pessoal (opcional)" value="<?php echo htmlspecialchars((string)($form['email_pessoal'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-text">Contato pessoal; distinto do e-mail corporativo de acesso.</div>
            </div>
            <div class="col-md-4">
                <label for="data_nascimento" class="form-label">Data de Nascimento</label>
                <input type="date" name="data_nascimento" class="form-control" id="data_nascimento" value="<?php echo htmlspecialchars((string)($form['data_nascimento'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-4">
                <label for="sexo" class="form-label">Sexo</label>
                <select name="sexo" id="sexo" class="form-select">
                    <?php $sx = (string)($form['sexo'] ?? ''); ?>
                    <option value="" <?php echo $sx === '' ? 'selected' : ''; ?>>Selecione</option>
                    <option value="M" <?php echo $sx === 'M' ? 'selected' : ''; ?>>Masculino</option>
                    <option value="F" <?php echo $sx === 'F' ? 'selected' : ''; ?>>Feminino</option>
                    <option value="O" <?php echo $sx === 'O' ? 'selected' : ''; ?>>Outros</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="estado_civil" class="form-label">Estado civil</label>
                <select name="estado_civil" id="estado_civil" class="form-select">
                    <?php $ecVal = (string)($form['estado_civil'] ?? ''); ?>
                    <option value="" <?php echo $ecVal === '' ? 'selected' : ''; ?>>Selecione</option>
                    <?php foreach (\App\adms\Helpers\UserFormHelper::estadoCivilOptions() as $slug => $ecLabel): ?>
                        <option value="<?php echo htmlspecialchars($slug); ?>" <?php echo $ecVal === $slug ? 'selected' : ''; ?>><?php echo htmlspecialchars($ecLabel); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="escolaridade" class="form-label">Escolaridade</label>
                <select name="escolaridade" id="escolaridade" class="form-select">
                    <?php $escVal = (string)($form['escolaridade'] ?? ''); ?>
                    <option value="" <?php echo $escVal === '' ? 'selected' : ''; ?>>Selecione</option>
                    <?php foreach (\App\adms\Helpers\UserFormHelper::escolaridadeOptions() as $slug => $escLabel): ?>
                        <option value="<?php echo htmlspecialchars($slug); ?>" <?php echo $escVal === $slug ? 'selected' : ''; ?>><?php echo htmlspecialchars($escLabel); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="raca" class="form-label">Raça/cor</label>
                <select name="raca" id="raca" class="form-select">
                    <?php $racaVal = (string)($form['raca'] ?? ''); ?>
                    <option value="" <?php echo $racaVal === '' ? 'selected' : ''; ?>>Selecione</option>
                    <?php foreach (\App\adms\Helpers\UserFormHelper::racaOptions() as $slug => $racaLabel): ?>
                        <option value="<?php echo htmlspecialchars($slug); ?>" <?php echo $racaVal === $slug ? 'selected' : ''; ?>><?php echo htmlspecialchars($racaLabel); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="filhos" class="form-label">Filho(s)</label>
                <select name="filhos" id="filhos" class="form-select">
                    <?php $fh = (string)($form['filhos'] ?? ''); ?>
                    <option value="" <?php echo $fh === '' ? 'selected' : ''; ?>>Selecione</option>
                    <option value="S" <?php echo $fh === 'S' ? 'selected' : ''; ?>>Sim</option>
                    <option value="N" <?php echo $fh === 'N' ? 'selected' : ''; ?>>Não</option>
                </select>
            </div>
        </div>
    </div>

    <div class="tab-pane fade <?php echo $activeTab === 'endereco' ? 'show active' : ''; ?>" id="tab-endereco" role="tabpanel">
        <div class="row g-3" id="user-address-fields"
             data-has-address="<?php echo (!empty($form['cep']) || !empty($form['endereco']) || !empty($form['municipio'])) ? '1' : '0'; ?>">
            <div class="col-md-4">
                <label for="cep" class="form-label">CEP</label>
                <div class="input-group">
                    <input type="text" name="cep" class="form-control" id="cep" placeholder="00000-000"
                           value="<?php echo htmlspecialchars((string)($form['cep'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" maxlength="10"
                           inputmode="numeric" autocomplete="postal-code">
                    <button type="button" class="btn btn-success" id="btn-search-cep" title="Buscar CEP">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                </div>
                <div class="form-text">
                    <i class="fas fa-lightbulb text-warning"></i>
                    Digite o CEP e clique em Buscar para auto-completar (ViaCEP), como no CRM.
                </div>
                <div class="form-check mt-2">
                    <input type="checkbox" class="form-check-input" id="no-cep-checkbox">
                    <label class="form-check-label" for="no-cep-checkbox">
                        <strong>Não sei o CEP</strong> — preencher endereço manualmente
                    </label>
                </div>
                <div id="cep-loading" class="text-primary mt-2 d-none"><i class="fas fa-spinner fa-spin"></i> Buscando CEP...</div>
                <div id="cep-success" class="text-success mt-2 d-none"><i class="fas fa-check-circle"></i> CEP encontrado!</div>
                <div id="cep-error" class="text-danger mt-2 d-none" role="alert"></div>
            </div>

            <div class="col-md-4">
                <label for="pais_residencia_iso" class="form-label">País de residência</label>
                <select name="pais_residencia_iso" id="pais_residencia_iso" class="form-select">
                    <?php
                    $paisVal = (string)($form['pais_residencia_iso'] ?? '');
                    if ($paisVal === '' && (!empty($form['cep']) || !empty($form['uf']))) {
                        $paisVal = 'BR';
                    }
                    $countries = \App\adms\Helpers\CountryHelper::getCountries();
                    uasort($countries, static fn ($a, $b) => strcmp($a['name'] ?? '', $b['name'] ?? ''));
                    ?>
                    <option value="" <?php echo $paisVal === '' ? 'selected' : ''; ?>>Selecione</option>
                    <?php foreach ($countries as $code => $info): ?>
                        <option value="<?php echo htmlspecialchars($code); ?>" <?php echo strtoupper($paisVal) === $code ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(($info['flag'] ?? '') . ' ' . ($info['name'] ?? $code)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Auto-preenchido pelo CEP (Brasil) ou selecione manualmente.</div>
            </div>

            <div class="col-md-4">
                <label for="uf" class="form-label">UF</label>
                <select name="uf" id="uf" class="form-select">
                    <?php $ufVal = strtoupper((string)($form['uf'] ?? '')); ?>
                    <option value="" <?php echo $ufVal === '' ? 'selected' : ''; ?>>Selecione</option>
                    <?php foreach (\App\adms\Helpers\BrazilStatesHelper::getStates() as $ufCode => $ufName): ?>
                        <option value="<?php echo htmlspecialchars($ufCode); ?>" <?php echo $ufVal === $ufCode ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($ufCode . ' - ' . $ufName); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label for="municipio" class="form-label">Município</label>
                <input type="text" name="municipio" class="form-control" id="municipio"
                       value="<?php echo htmlspecialchars((string)($form['municipio'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" maxlength="120"
                       autocomplete="address-level2">
            </div>
            <div class="col-md-4">
                <label for="bairro" class="form-label">Bairro</label>
                <input type="text" name="bairro" class="form-control" id="bairro"
                       value="<?php echo htmlspecialchars((string)($form['bairro'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" maxlength="120"
                       autocomplete="address-level3">
            </div>
            <div class="col-md-8">
                <label for="endereco" class="form-label">Logradouro</label>
                <input type="text" name="endereco" class="form-control" id="endereco" placeholder="Rua, avenida..."
                       value="<?php echo htmlspecialchars((string)($form['endereco'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" maxlength="255"
                       autocomplete="street-address">
            </div>
            <div class="col-md-2">
                <label for="numero_endereco" class="form-label">Número</label>
                <input type="text" name="numero_endereco" class="form-control" id="numero_endereco" placeholder="Nº"
                       value="<?php echo htmlspecialchars((string)($form['numero_endereco'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" maxlength="20">
            </div>
            <div class="col-md-2">
                <label for="complemento_endereco" class="form-label">Complemento</label>
                <input type="text" name="complemento_endereco" class="form-control" id="complemento_endereco" placeholder="Apto, bloco..."
                       value="<?php echo htmlspecialchars((string)($form['complemento_endereco'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" maxlength="80">
            </div>
        </div>
    </div>

    <div class="tab-pane fade <?php echo $activeTab === 'contratuais' ? 'show active' : ''; ?>" id="tab-contratuais" role="tabpanel">
        <div class="row g-3">
            <div class="col-md-4">
                <label for="empresa_contratante" class="form-label">Empresa contratante</label>
                <select name="empresa_contratante" id="empresa_contratante" class="form-select">
                    <?php $empVal = (string)($form['empresa_contratante'] ?? ''); ?>
                    <option value="" <?php echo $empVal === '' ? 'selected' : ''; ?>>Selecione</option>
                    <?php foreach (\App\adms\Helpers\UserFormHelper::empresaContratanteOptions() as $slug => $empLabel): ?>
                        <option value="<?php echo htmlspecialchars($slug); ?>" <?php echo $empVal === $slug ? 'selected' : ''; ?>><?php echo htmlspecialchars($empLabel); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="matricula" class="form-label">Matrícula</label>
                <input type="text" name="matricula" class="form-control" id="matricula" placeholder="Matrícula funcional"
                       value="<?php echo htmlspecialchars((string)($form['matricula'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" maxlength="40"
                       autocomplete="off">
            </div>
            <div class="col-md-4">
                <label for="data_admissao" class="form-label">Data de Admissão</label>
                <input type="date" name="data_admissao" class="form-control" id="data_admissao" value="<?php echo htmlspecialchars((string)($form['data_admissao'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-text">Data em que o colaborador foi admitido na empresa</div>
            </div>

            <?php if ($isUpdate): ?>
                <div class="col-md-4">
                    <label for="data_desligamento" class="form-label">Data de Desligamento</label>
                    <input type="date" name="data_desligamento" class="form-control" id="data_desligamento" value="<?php echo htmlspecialchars((string)($form['data_desligamento'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="form-text">Deixe em branco se o colaborador ainda está ativo</div>
                </div>
                <div class="col-md-12" id="motivo_desligamento_container" style="display: <?php echo !empty($form['data_desligamento']) ? 'block' : 'none'; ?>;">
                    <label for="motivo_desligamento" class="form-label">Motivo do Desligamento</label>
                    <input type="text" name="motivo_desligamento" class="form-control" id="motivo_desligamento" placeholder="Ex: Pedido de demissão..." value="<?php echo htmlspecialchars((string)($form['motivo_desligamento'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" maxlength="255">
                    <?php $ti = $form['tipo_impacto_desligamento'] ?? ''; ?>
                    <label for="tipo_impacto_desligamento" class="form-label mt-3">Classificação do desligamento (People Analytics)</label>
                    <select name="tipo_impacto_desligamento" id="tipo_impacto_desligamento" class="form-select">
                        <option value="" <?php echo $ti === '' || $ti === null ? 'selected' : ''; ?>>Não informado</option>
                        <option value="regrettable" <?php echo $ti === 'regrettable' ? 'selected' : ''; ?>>Regrettable (desejável reter)</option>
                        <option value="non_regrettable" <?php echo $ti === 'non_regrettable' ? 'selected' : ''; ?>>Non-regrettable</option>
                        <option value="nao_classificado" <?php echo $ti === 'nao_classificado' ? 'selected' : ''; ?>>Não classificado (explícito)</option>
                    </select>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($isUpdate): ?>
        <?php
        $educationRows = is_array($this->data['educations'] ?? null) ? $this->data['educations'] : [];
        $educationTypes = \App\adms\Helpers\UserEducationHelper::typeOptions();
        $educationStatuses = \App\adms\Helpers\UserEducationHelper::statusOptions();
        ?>
        <div class="tab-pane fade <?php echo $activeTab === 'formacoes' ? 'show active' : ''; ?>" id="tab-formacoes" role="tabpanel">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h5 class="mb-1">Formações acadêmicas e cursos</h5>
                    <p class="text-muted small mb-0">Cadastre quantas graduações, pós-graduações, cursos técnicos ou livres forem necessárias.</p>
                </div>
                <button type="button" class="btn btn-outline-success btn-sm" id="add-user-education">
                    <i class="fas fa-plus me-1"></i>Adicionar formação
                </button>
            </div>

            <div id="user-educations-list">
                <?php foreach ($educationRows as $rowIndex => $education):
                    if (!is_array($education)) {
                        continue;
                    }
                    $educationId = (int)($education['id'] ?? 0);
                    $rowKey = $educationId > 0 ? 'existing_' . $educationId : (string)$rowIndex;
                    ?>
                    <div class="card border mb-3 user-education-row" data-new="<?php echo $educationId > 0 ? '0' : '1'; ?>">
                        <div class="card-header py-2 d-flex justify-content-between align-items-center">
                            <strong><i class="fas fa-graduation-cap me-1"></i>Formação</strong>
                            <?php if ($educationId > 0): ?>
                                <div class="form-check">
                                    <input class="form-check-input education-delete" type="checkbox" name="educations[<?php echo htmlspecialchars($rowKey); ?>][delete]" value="1" id="education_delete_<?php echo $educationId; ?>">
                                    <label class="form-check-label text-danger" for="education_delete_<?php echo $educationId; ?>">Excluir</label>
                                </div>
                            <?php else: ?>
                                <button type="button" class="btn btn-outline-danger btn-sm remove-user-education"><i class="fas fa-trash"></i></button>
                            <?php endif; ?>
                        </div>
                        <div class="card-body row g-3">
                            <input type="hidden" name="educations[<?php echo htmlspecialchars($rowKey); ?>][id]" value="<?php echo $educationId; ?>">
                            <div class="col-md-4">
                                <label class="form-label">Tipo <span class="text-danger">*</span></label>
                                <select name="educations[<?php echo htmlspecialchars($rowKey); ?>][tipo]" class="form-select">
                                    <option value="">Selecione</option>
                                    <?php foreach ($educationTypes as $slug => $label): ?>
                                        <option value="<?php echo htmlspecialchars($slug); ?>" <?php echo (string)($education['tipo'] ?? '') === $slug ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Curso/Formação <span class="text-danger">*</span></label>
                                <input type="text" name="educations[<?php echo htmlspecialchars($rowKey); ?>][curso]" class="form-control" maxlength="191" value="<?php echo htmlspecialchars((string)($education['curso'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Situação <span class="text-danger">*</span></label>
                                <select name="educations[<?php echo htmlspecialchars($rowKey); ?>][situacao]" class="form-select">
                                    <?php foreach ($educationStatuses as $slug => $label): ?>
                                        <option value="<?php echo htmlspecialchars($slug); ?>" <?php echo (string)($education['situacao'] ?? 'concluido') === $slug ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Instituição</label>
                                <input type="text" name="educations[<?php echo htmlspecialchars($rowKey); ?>][instituicao]" class="form-control" maxlength="191" value="<?php echo htmlspecialchars((string)($education['instituicao'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Início</label>
                                <input type="date" name="educations[<?php echo htmlspecialchars($rowKey); ?>][data_inicio]" class="form-control" value="<?php echo htmlspecialchars((string)($education['data_inicio'] ?? '')); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Conclusão</label>
                                <input type="date" name="educations[<?php echo htmlspecialchars($rowKey); ?>][data_conclusao]" class="form-control" value="<?php echo htmlspecialchars((string)($education['data_conclusao'] ?? '')); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Carga horária</label>
                                <input type="number" min="1" max="100000" name="educations[<?php echo htmlspecialchars($rowKey); ?>][carga_horaria]" class="form-control" value="<?php echo htmlspecialchars((string)($education['carga_horaria'] ?? '')); ?>">
                            </div>
                            <div class="col-md-7">
                                <label class="form-label">Comprovante (opcional)</label>
                                <input type="file" name="education_files[<?php echo htmlspecialchars($rowKey); ?>]" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx">
                                <div class="form-text">PDF, imagem, DOC ou DOCX, até 10 MB.</div>
                                <?php if ($educationId > 0 && !empty($education['comprovante_path'])): ?>
                                    <div class="mt-1">
                                        <a href="<?php echo $_ENV['URL_ADM']; ?>view-user/download-formacao/<?php echo $educationId; ?>" class="small">
                                            <i class="fas fa-paperclip me-1"></i><?php echo htmlspecialchars((string)($education['comprovante_nome_original'] ?? 'Baixar comprovante')); ?>
                                        </a>
                                        <div class="form-check form-check-inline ms-2">
                                            <input class="form-check-input" type="checkbox" name="educations[<?php echo htmlspecialchars($rowKey); ?>][remove_comprovante]" value="1" id="remove_proof_<?php echo $educationId; ?>">
                                            <label class="form-check-label small text-danger" for="remove_proof_<?php echo $educationId; ?>">Remover comprovante</label>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Observações</label>
                                <textarea name="educations[<?php echo htmlspecialchars($rowKey); ?>][observacoes]" class="form-control" rows="2" maxlength="2000"><?php echo htmlspecialchars((string)($education['observacoes'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div id="user-educations-empty" class="alert alert-light border text-muted <?php echo $educationRows !== [] ? 'd-none' : ''; ?>">
                Nenhuma formação cadastrada. Clique em “Adicionar formação”.
            </div>
        </div>

        <template id="user-education-template">
            <div class="card border mb-3 user-education-row" data-new="1">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <strong><i class="fas fa-graduation-cap me-1"></i>Nova formação</strong>
                    <button type="button" class="btn btn-outline-danger btn-sm remove-user-education"><i class="fas fa-trash"></i></button>
                </div>
                <div class="card-body row g-3">
                    <input type="hidden" name="educations[__KEY__][id]" value="0">
                    <div class="col-md-4"><label class="form-label">Tipo <span class="text-danger">*</span></label><select name="educations[__KEY__][tipo]" class="form-select"><option value="">Selecione</option><?php foreach ($educationTypes as $slug => $label): ?><option value="<?php echo htmlspecialchars($slug); ?>"><?php echo htmlspecialchars($label); ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-5"><label class="form-label">Curso/Formação <span class="text-danger">*</span></label><input type="text" name="educations[__KEY__][curso]" class="form-control" maxlength="191"></div>
                    <div class="col-md-3"><label class="form-label">Situação <span class="text-danger">*</span></label><select name="educations[__KEY__][situacao]" class="form-select"><?php foreach ($educationStatuses as $slug => $label): ?><option value="<?php echo htmlspecialchars($slug); ?>" <?php echo $slug === 'concluido' ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-6"><label class="form-label">Instituição</label><input type="text" name="educations[__KEY__][instituicao]" class="form-control" maxlength="191"></div>
                    <div class="col-md-2"><label class="form-label">Início</label><input type="date" name="educations[__KEY__][data_inicio]" class="form-control"></div>
                    <div class="col-md-2"><label class="form-label">Conclusão</label><input type="date" name="educations[__KEY__][data_conclusao]" class="form-control"></div>
                    <div class="col-md-2"><label class="form-label">Carga horária</label><input type="number" min="1" max="100000" name="educations[__KEY__][carga_horaria]" class="form-control"></div>
                    <div class="col-md-7"><label class="form-label">Comprovante (opcional)</label><input type="file" name="education_files[__KEY__]" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx"><div class="form-text">PDF, imagem, DOC ou DOCX, até 10 MB.</div></div>
                    <div class="col-md-5"><label class="form-label">Observações</label><textarea name="educations[__KEY__][observacoes]" class="form-control" rows="2" maxlength="2000"></textarea></div>
                </div>
            </div>
        </template>
    <?php endif; ?>
</div>

<?php if ($isUpdate): ?>
<script>
(function () {
    var list = document.getElementById('user-educations-list');
    var empty = document.getElementById('user-educations-empty');
    var template = document.getElementById('user-education-template');
    var add = document.getElementById('add-user-education');
    if (!list || !template || !add) return;

    function refreshEmpty() {
        if (empty) empty.classList.toggle('d-none', list.querySelectorAll('.user-education-row').length > 0);
    }
    function bindRemove(scope) {
        scope.querySelectorAll('.remove-user-education').forEach(function (button) {
            button.addEventListener('click', function () {
                var row = button.closest('.user-education-row');
                if (row) row.remove();
                refreshEmpty();
            });
        });
    }
    add.addEventListener('click', function () {
        var key = 'new_' + Date.now() + '_' + Math.floor(Math.random() * 10000);
        var wrapper = document.createElement('div');
        wrapper.innerHTML = template.innerHTML.replaceAll('__KEY__', key).trim();
        var row = wrapper.firstElementChild;
        if (row) {
            list.appendChild(row);
            bindRemove(row);
        }
        refreshEmpty();
    });
    bindRemove(list);
    refreshEmpty();
})();
</script>
<?php endif; ?>
