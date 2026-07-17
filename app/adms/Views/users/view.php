<?php

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\ImageHelper;
use App\adms\Helpers\PositionDisplayHelper;

// Gera o token CSRF para proteger o formulário de deleção
$csrf_token = CSRFHelper::generateCSRFToken('form_delete_user');
$csrf_token_update_access_level = CSRFHelper::generateCSRFToken('form_update_access_level');
// Gera o token CSRF para proteger o formulário de deleção de imagem
$csrf_token_delete_image = CSRFHelper::generateCSRFToken('form_delete_user_image');

?>

<div class="container-fluid px-4">
    <div class="mb-1 d-flex flex-column flex-sm-row gap-2">
        <h2 class="mt-3">Usuários</h2>

        <ol class="breadcrumb  mb-3 mt-0 mt-sm-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>list-users" class="text-decoration-none">Usuários</a></li>
            <li class="breadcrumb-item">Visualizar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex flex-column flex-sm-row gap-2">
            <span>
                Visulaizar
            </span>

            <span class="ms-sm-auto d-sm-flex flex-row">

                <?php
                if (in_array('ListUsers', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}list-users' class='btn btn-info btn-sm me-1 mb-1'><i class='fa-solid fa-list-ul'></i> Listar</a> ";
                }

                $id = ($this->data['user']['id'] ?? '');
                if (in_array('UpdateUser', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}update-user/$id' class='btn btn-warning btn-sm me-1 mb-1'><i class='fa-regular fa-pen-to-square'></i> Editar</a> ";
                }

                if (in_array('UpdatePasswordUser', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}update-password-user/$id' class='btn btn-warning btn-sm me-1 mb-1'><i class='fa-solid fa-key'></i> Editar Senha</a> ";
                }

                if (in_array('UpdateUserImage', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}update-user-image/$id' class='btn btn-warning btn-sm me-1 mb-1'><i class='fa-solid fa-camera'></i> Editar Imagem</a> ";
                }
                if (in_array('SstEmployeeProfile', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}sst-employee-profile/$id' class='btn btn-outline-primary btn-sm me-1 mb-1'><i class='fa-solid fa-heart-pulse'></i> SST</a> ";
                }

                $log_resumo = $this->data['log_resumo'] ?? [];
                $log_btn_class = 'btn btn-outline-info btn-sm me-1 mb-1';
                include __DIR__ . '/../partials/button_log_alteracoes.php';

                if (in_array('DeleteUser', $this->data['buttonPermission'])) {
                ?>
                    <!-- Formulário para deletar usuário -->
                    <form id="formDelete<?php echo ($this->data['user']['id'] ?? ''); ?>" action="<?php echo $_ENV['URL_ADM']; ?>delete-user" method="POST">

                        <!-- Campo oculto para o token CSRF -->
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                        <!-- Campo oculto para o ID do usuário -->
                        <input type="hidden" name="id" id="id" value="<?php echo ($this->data['user']['id'] ?? ''); ?>">

                        <!-- Botão para submeter o formulário -->
                        <button type="submit" class="btn btn-danger btn-sm me-1 mb-1" onclick="confirmDeletion(event, <?php echo ($this->data['user']['id'] ?? ''); ?>)"><i class="fa-regular fa-trash-can"></i> Apagar</button>

                    </form>
                <?php } ?>
                </form>

            </span>
        </div>


        <div class="card-body">
            <?php
            // Inclui o arquivo que exibe mensagens de sucesso e erro
            include './app/adms/Views/partials/alerts.php';

            // Verifica se há usuários no array
            if ($this->data['user'] ?? false) {

                // Extrai variáveis do array $this->data['user'] para fácil acesso
                extract($this->data['user']);
            ?>

                <dl class="row">
                    <dt class="col-sm-3">ID: </dt>
                    <dd class="col-sm-9"><?php echo $id; ?></dd>

                    <dt class="col-sm-3">Nome: </dt>
                    <dd class="col-sm-9"><?php echo $name; ?></dd>

                    <dt class="col-sm-3">Email: </dt>
                    <dd class="col-sm-9"><?php echo $email; ?></dd>

                    <dt class="col-sm-3">E-mail pessoal: </dt>
                    <dd class="col-sm-9"><?php echo !empty($this->data['user']['email_pessoal']) ? htmlspecialchars((string)$this->data['user']['email_pessoal'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted">Não informado</span>'; ?></dd>

                    <dt class="col-sm-3">Usuário: </dt>
                    <dd class="col-sm-9"><?php echo $username; ?></dd>

                    <dt class="col-sm-3">CPF: </dt>
                    <dd class="col-sm-9"><?php echo !empty($cpf) ? $cpf : '<span class="text-muted">Não informado</span>'; ?></dd>

                    <dt class="col-sm-3">Celular: </dt>
                    <dd class="col-sm-9"><?php echo !empty($celular) ? $celular : '<span class="text-muted">Não informado</span>'; ?></dd>

                    <dt class="col-sm-3">Imagem: </dt>
                    <dd class="col-sm-9">
                        <?php
                        // Monta caminho completo somente se houver imagem personalizada
                        $userImagePath = null;
                        if (ImageHelper::userImageExists((int)($id ?? 0), (string)($image ?? ''))) {
                            $userImagePath = 'users/' . $id . '/' . $image;
                        }

                        if ($userImagePath !== null) {
                            echo ImageHelper::displayImage($userImagePath, [
                                'alt' => 'Imagem do usuário',
                                'style' => 'max-width: 120px; max-height: 120px; border-radius: 8px; object-fit: cover;',
                            ], 'icon_user.png', 'users');
                        } else {
                            echo ImageHelper::renderInitialsAvatar((string)($name ?? 'Usuário'), 120, [
                                'style' => 'border-radius: 8px;',
                            ]);
                        }
                        ?>
                        
                        <?php if (ImageHelper::userImageExists((int)($id ?? 0), (string)($image ?? ''))): ?>
                            <!-- Botão para abrir o modal de confirmação (desktop) -->
                            <button type="button" class="btn btn-outline-danger btn-sm d-none d-md-inline-block" data-bs-toggle="modal" data-bs-target="#modalDeleteImage<?php echo $id; ?>-desktop" style="margin-left: 10px;">
                                Remover imagem
                            </button>
                            <!-- Modal de confirmação (desktop) -->
                            <div class="modal fade" id="modalDeleteImage<?php echo $id; ?>-desktop" tabindex="-1" aria-labelledby="modalDeleteImageLabel<?php echo $id; ?>-desktop" aria-hidden="true">
                              <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                  <div class="modal-header">
                                    <h5 class="modal-title" id="modalDeleteImageLabel<?php echo $id; ?>-desktop">
                                      <i class="fas fa-exclamation-triangle text-danger me-2"></i>Confirmar Remoção da Imagem
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                  </div>
                                  <div class="modal-body">
                                    Tem certeza que deseja remover a imagem do usuário?<br>
                                    <small class="text-muted">Você não poderá reverter esta ação.</small>
                                  </div>
                                  <div class="modal-footer">
                                    <form action="<?php echo $_ENV['URL_ADM']; ?>delete-user-image/<?php echo $id; ?>" method="POST" class="d-inline">
                                      <input type="hidden" name="csrf_token" value="<?php echo $csrf_token_delete_image; ?>">
                                      <button type="submit" class="btn btn-danger">Sim, remover!</button>
                                    </form>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                  </div>
                                </div>
                              </div>
                            </div>
                            <!-- Botão para abrir o modal de confirmação (mobile) -->
                            <button type="button" class="btn btn-outline-danger btn-sm d-inline-block d-md-none mt-2" data-bs-toggle="modal" data-bs-target="#modalDeleteImage<?php echo $id; ?>-mobile">
                                Remover imagem
                            </button>
                            <!-- Modal de confirmação (mobile) -->
                            <div class="modal fade" id="modalDeleteImage<?php echo $id; ?>-mobile" tabindex="-1" aria-labelledby="modalDeleteImageLabel<?php echo $id; ?>-mobile" aria-hidden="true">
                              <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                  <div class="modal-header">
                                    <h5 class="modal-title" id="modalDeleteImageLabel<?php echo $id; ?>-mobile">
                                      <i class="fas fa-exclamation-triangle text-danger me-2"></i>Confirmar Remoção da Imagem
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                  </div>
                                  <div class="modal-body">
                                    Tem certeza que deseja remover a imagem do usuário?<br>
                                    <small class="text-muted">Você não poderá reverter esta ação.</small>
                                  </div>
                                  <div class="modal-footer">
                                    <form action="<?php echo $_ENV['URL_ADM']; ?>delete-user-image/<?php echo $id; ?>" method="POST" class="d-inline">
                                      <input type="hidden" name="csrf_token" value="<?php echo $csrf_token_delete_image; ?>">
                                      <button type="submit" class="btn btn-danger">Sim, remover!</button>
                                    </form>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                  </div>
                                </div>
                              </div>
                            </div>
                        <?php endif; ?>
                    </dd>
                    <dt class="col-sm-3">Data de Nascimento: </dt>
                    <dd class="col-sm-9">
                        <?php 
                        $dataNasc = isset($data_nascimento) ? $data_nascimento : ($this->data['user']['data_nascimento'] ?? null);
                        echo !empty($dataNasc) ? date('d/m/Y', strtotime($dataNasc)) : '<span class="text-muted">Não informado</span>'; 
                        ?>
                    </dd>
                    <dt class="col-sm-3">Escolaridade: </dt>
                    <dd class="col-sm-9">
                        <?php
                        $escolaridadeRaw = $this->data['user']['escolaridade'] ?? null;
                        $escolaridadeLabel = \App\adms\Helpers\UserFormHelper::escolaridadeLabel(
                            is_string($escolaridadeRaw) ? $escolaridadeRaw : null
                        );
                        echo $escolaridadeRaw !== null && $escolaridadeRaw !== ''
                            ? htmlspecialchars($escolaridadeLabel, ENT_QUOTES, 'UTF-8')
                            : '<span class="text-muted">Não informado</span>';
                        ?>
                    </dd>
                    <dt class="col-sm-3">Sexo: </dt>
                    <dd class="col-sm-9">
                        <?php echo htmlspecialchars(\App\adms\Helpers\UserFormHelper::sexoLabel($this->data['user']['sexo'] ?? null), ENT_QUOTES, 'UTF-8'); ?>
                    </dd>
                    <dt class="col-sm-3">Filho(s): </dt>
                    <dd class="col-sm-9">
                        <?php echo htmlspecialchars(\App\adms\Helpers\UserFormHelper::filhosLabel($this->data['user']['filhos'] ?? null), ENT_QUOTES, 'UTF-8'); ?>
                    </dd>
                    <dt class="col-sm-3">Estado civil: </dt>
                    <dd class="col-sm-9">
                        <?php echo htmlspecialchars(\App\adms\Helpers\UserFormHelper::estadoCivilLabel($this->data['user']['estado_civil'] ?? null), ENT_QUOTES, 'UTF-8'); ?>
                    </dd>
                    <dt class="col-sm-3">Raça/cor: </dt>
                    <dd class="col-sm-9">
                        <?php
                        $racaRaw = $this->data['user']['raca'] ?? null;
                        echo $racaRaw !== null && $racaRaw !== ''
                            ? htmlspecialchars(\App\adms\Helpers\UserFormHelper::racaLabel(is_string($racaRaw) ? $racaRaw : null), ENT_QUOTES, 'UTF-8')
                            : '<span class="text-muted">Não informado</span>';
                        ?>
                    </dd>
                    <dt class="col-sm-3">País de residência: </dt>
                    <dd class="col-sm-9">
                        <?php echo htmlspecialchars(\App\adms\Helpers\UserFormHelper::paisResidenciaLabel($this->data['user']['pais_residencia_iso'] ?? null), ENT_QUOTES, 'UTF-8'); ?>
                    </dd>
                    <dt class="col-sm-3">Endereço: </dt>
                    <dd class="col-sm-9">
                        <?php
                        $endParts = array_filter([
                            trim((string)($this->data['user']['endereco'] ?? '')),
                            trim((string)($this->data['user']['numero_endereco'] ?? '')),
                            trim((string)($this->data['user']['complemento_endereco'] ?? '')),
                            trim((string)($this->data['user']['bairro'] ?? '')),
                        ], static fn ($v) => $v !== '');
                        $cepMunUf = array_filter([
                            trim((string)($this->data['user']['cep'] ?? '')),
                            trim((string)($this->data['user']['municipio'] ?? '')),
                            trim((string)($this->data['user']['uf'] ?? '')),
                        ], static fn ($v) => $v !== '');
                        if ($endParts || $cepMunUf) {
                            $line1 = implode(', ', $endParts);
                            $line2 = implode(' - ', $cepMunUf);
                            echo htmlspecialchars(trim($line1 . ($line1 && $line2 ? ' | ' : '') . $line2), ENT_QUOTES, 'UTF-8');
                        } else {
                            echo '<span class="text-muted">Não informado</span>';
                        }
                        ?>
                    </dd>

                    <dt class="col-sm-3">Departamento: </dt>
                    <dd class="col-sm-9"><?php echo $dep_name; ?></dd>

                    <dt class="col-sm-3">Empresa contratante: </dt>
                    <dd class="col-sm-9">
                        <?php
                        $empRaw = $this->data['user']['empresa_contratante'] ?? null;
                        echo $empRaw !== null && $empRaw !== ''
                            ? htmlspecialchars(\App\adms\Helpers\UserFormHelper::empresaContratanteLabel(is_string($empRaw) ? $empRaw : null), ENT_QUOTES, 'UTF-8')
                            : '<span class="text-muted">Não informado</span>';
                        ?>
                    </dd>

                    <dt class="col-sm-3">Matrícula: </dt>
                    <dd class="col-sm-9"><?php echo !empty($this->data['user']['matricula']) ? htmlspecialchars((string)$this->data['user']['matricula'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted">Não informado</span>'; ?></dd>

                    <dt class="col-sm-3">Cargo|Função: </dt>
                    <dd class="col-sm-9"><?php echo htmlspecialchars(PositionDisplayHelper::formatForDisplay((string)($pos_name ?? ''))); ?></dd>

                    <dt class="col-sm-3">Turno de trabalho: </dt>
                    <dd class="col-sm-9"><?php
                        $wsLabel = $work_shift_description ?? ($this->data['user']['work_shift_description'] ?? '');
                        echo $wsLabel !== '' && $wsLabel !== null
                            ? htmlspecialchars((string) $wsLabel, ENT_QUOTES, 'UTF-8')
                            : '<span class="text-muted">Não definido</span>';
                    ?></dd>

                    <dt class="col-sm-3">Cadastrado: </dt>
                    <dd class="col-sm-9"><?php echo ($created_at ? date('d/m/Y H:i:s', strtotime($created_at)) : ""); ?></dd>

                    <dt class="col-sm-3">Aeditado: </dt>
                    <dd class="col-sm-9"><?php echo ($updated_at ? date('d/m/Y H:i:s', strtotime($updated_at)) : ""); ?></dd>

                    <dt class="col-sm-3">Status: </dt>
                    <dd class="col-sm-9"><?php echo $status; ?></dd>

                    <dt class="col-sm-3">Super usuário: </dt>
                    <dd class="col-sm-9"><?php echo (int)($this->data['user']['super_usuario'] ?? 0) === 1 ? 'Sim' : 'Não'; ?></dd>

                    <dt class="col-sm-3">Bloqueado: </dt>
                    <dd class="col-sm-9"><?php echo $bloqueado; ?></dd>

                    <dt class="col-sm-3">Tentativas de Login: </dt>
                    <dd class="col-sm-9"><?php echo $tentativas_login; ?></dd>

                    <dt class="col-sm-3">Senha Nunca Expira: </dt>
                    <dd class="col-sm-9"><?php echo $senha_nunca_expira; ?></dd>

                    <dt class="col-sm-3">Modificar Senha no Próximo Logon: </dt>
                    <dd class="col-sm-9"><?php echo $modificar_senha_proximo_logon; ?></dd>

                    <?php if (!empty($this->data['user']['data_admissao'])): ?>
                        <dt class="col-sm-3">Data de Admissão: </dt>
                        <dd class="col-sm-9"><?php echo date('d/m/Y', strtotime($this->data['user']['data_admissao'])); ?></dd>
                    <?php endif; ?>

                    <?php if (!empty($this->data['user']['data_desligamento'])): ?>
                        <dt class="col-sm-3">Data de Desligamento: </dt>
                        <dd class="col-sm-9"><?php echo date('d/m/Y', strtotime($this->data['user']['data_desligamento'])); ?></dd>
                        <dt class="col-sm-3">Classificação do desligamento: </dt>
                        <dd class="col-sm-9"><?php echo htmlspecialchars(\App\adms\Helpers\UserFormHelper::tipoImpactoDesligamentoLabel($this->data['user']['tipo_impacto_desligamento'] ?? null)); ?></dd>
                    <?php endif; ?>

                    <?php if (!empty($this->data['totalTenure'])): ?>
                        <dt class="col-sm-3">Tempo Total de Casa: </dt>
                        <dd class="col-sm-9">
                            <strong><?php echo htmlspecialchars($this->data['totalTenure']['formatted']); ?></strong>
                            <small class="text-muted">(<?php echo $this->data['totalTenure']['total_periodos']; ?> período(s))</small>
                        </dd>
                    <?php endif; ?>
                </dl>


            <?php
            } else {
                // Acessa o ELSE quando o elemento não existir registros
                echo "<div class='alert alert-danger' role='alert'>Usuário não encontrado.</div>";
            }
            ?>
        </div>

    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-graduation-cap me-1"></i> Formações acadêmicas e cursos</h5>
            <?php if (in_array('UpdateUser', $this->data['buttonPermission'] ?? [], true)): ?>
                <a href="<?php echo $_ENV['URL_ADM']; ?>update-user/<?php echo (int)($this->data['user']['id'] ?? 0); ?>#tab-formacoes" class="btn btn-sm btn-outline-warning">
                    <i class="fas fa-edit me-1"></i>Gerenciar
                </a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if (!empty($this->data['educations'])): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Curso/Formação</th>
                                <th>Instituição</th>
                                <th>Situação</th>
                                <th>Período</th>
                                <th>Carga horária</th>
                                <th>Comprovante</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->data['educations'] as $education): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(\App\adms\Helpers\UserEducationHelper::typeLabel((string)($education['tipo'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars((string)($education['curso'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <?php if (!empty($education['observacoes'])): ?>
                                            <div class="small text-muted"><?php echo nl2br(htmlspecialchars((string)$education['observacoes'], ENT_QUOTES, 'UTF-8')); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo !empty($education['instituicao']) ? htmlspecialchars((string)$education['instituicao'], ENT_QUOTES, 'UTF-8') : '—'; ?></td>
                                    <td><?php echo htmlspecialchars(\App\adms\Helpers\UserEducationHelper::statusLabel((string)($education['situacao'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <?php
                                        $start = !empty($education['data_inicio']) ? date('m/Y', strtotime((string)$education['data_inicio'])) : '';
                                        $end = !empty($education['data_conclusao']) ? date('m/Y', strtotime((string)$education['data_conclusao'])) : '';
                                        echo htmlspecialchars(trim($start . ($start !== '' && $end !== '' ? ' a ' : '') . $end) ?: '—');
                                        ?>
                                    </td>
                                    <td><?php echo !empty($education['carga_horaria']) ? (int)$education['carga_horaria'] . ' h' : '—'; ?></td>
                                    <td>
                                        <?php if (!empty($education['comprovante_path'])): ?>
                                            <a class="btn btn-sm btn-outline-primary" href="<?php echo $_ENV['URL_ADM']; ?>view-user/download-formacao/<?php echo (int)$education['id']; ?>">
                                                <i class="fas fa-download me-1"></i>Baixar
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">Não anexado</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted mb-0">Nenhuma formação cadastrada.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($this->data['employmentHistory'])): ?>
        <div class="card mb-4 border-light shadow">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-history"></i> Histórico de Admissões e Desligamentos</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Data de Admissão</th>
                                <th>Data de Desligamento</th>
                                <th>Motivo do Desligamento</th>
                                <th>Impacto (RH)</th>
                                <th>Duração</th>
                                <th>Observações</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->data['employmentHistory'] as $period): ?>
                                <tr class="<?php echo empty($period['data_desligamento']) ? 'table-success' : ''; ?>">
                                    <td>
                                        <span class="badge bg-<?php echo $period['tipo_periodo'] === 'Recontratação' ? 'info' : 'primary'; ?>">
                                            <?php echo htmlspecialchars($period['tipo_periodo']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($period['data_admissao'])); ?></td>
                                    <td>
                                        <?php 
                                        if (!empty($period['data_desligamento'])) {
                                            echo date('d/m/Y', strtotime($period['data_desligamento']));
                                        } else {
                                            echo '<span class="badge bg-success">Ativo</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo !empty($period['motivo_desligamento']) ? htmlspecialchars($period['motivo_desligamento']) : '-'; ?></td>
                                    <td>
                                        <?php
                                        $ti = $period['tipo_impacto_desligamento'] ?? null;
                                        echo $ti ? htmlspecialchars(\App\adms\Helpers\UserFormHelper::tipoImpactoDesligamentoLabel((string) $ti)) : '-';
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $admissao = new \DateTime($period['data_admissao']);
                                        $desligamento = !empty($period['data_desligamento']) 
                                            ? new \DateTime($period['data_desligamento']) 
                                            : new \DateTime();
                                        $diff = $admissao->diff($desligamento);
                                        echo $diff->y > 0 
                                            ? "{$diff->y} ano(s), {$diff->m} mês(es)"
                                            : "{$diff->m} mês(es), {$diff->d} dia(s)";
                                        ?>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo !empty($period['observacoes']) ? htmlspecialchars($period['observacoes']) : '-'; ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <?php if (in_array('UpdateUser', $this->data['buttonPermission'] ?? [])) { ?>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>update-employment-history/<?= $period['id'] ?>" 
                                               class="btn btn-sm btn-warning" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex flex-column flex-sm-row gap-2">
            <span>Departamento</span>

            <span class="ms-sm-auto d-sm-flex flex-row">


            </span>
        </div>


        <div class="card-body">
            <?php

            // Verifica se há niveis de acesso para o usuários no array
            if ($this->data['userDepartments'] ?? false) {

                echo "<dl class='row'>";
                echo "<dt class='col-sm-3'>Departamento: </dt>";
                echo "<dd class='col-sm-9'>";

                //Perceorre o array de usuários
                foreach ($this->data['userDepartments'] as $userDepartment) {
                    // Extrai variáveis do array de usuário
                    extract($userDepartment);
                    echo $name;
                }
                echo '</dd>';
                echo '</dl>';
            } else {
                // Acessa o ELSE quando o elemento não existir registros
                echo "<div class='alert alert-danger' role='alert'>Usuário não possui departamento vinculado.</div>";
            }        ?>
        </div>

    </div> -->

    <?php
    if (in_array('UpdateUserAccessLevels', $this->data['buttonPermission'])) { ?>


        <div class="card mb-4 border-light shadow">
            <div class="card-header d-flex flex-column flex-sm-row gap-2">
                <span>Permissões</span>

                <span class="ms-sm-auto d-sm-flex flex-row">


                </span>
            </div>


            <div class="card-body">
                <?php
                $viewUserIsSuper = (int)($this->data['user']['super_usuario'] ?? 0) === 1;
                if ($viewUserIsSuper) { ?>
                    <div class="alert alert-info mb-0" role="alert">
                        <strong>Super usuário:</strong> acesso total ao sistema (equivalente ao nível Super Administrador).
                        Os níveis de acesso não são editáveis aqui. Para ajustar perfis por nível, remova primeiro o flag
                        <em>Super usuário</em> no cadastro do utilizador.
                    </div>
                <?php } ?>

                <?php

                // // Verifica se há niveis de acesso para o usuários no array
                // if ($this->data['userAccessLevels'] ?? false) {

                //     echo "<dl class='row'>";
                //     echo "<dt class='col-sm-3'>Niveis de Acesso: </dt>";
                //     echo "<dd class='col-sm-9'>";

                //     //Perceorre o array de usuários
                //     foreach ($this->data['userAccessLevels'] as $userAccessLevel) {
                //         // Extrai variáveis do array de usuário
                //         extract($userAccessLevel);
                //         echo $name; 
                //     }
                //     echo '</dd>';
                //     echo '</dl>';
                // } else {
                //     // Acessa o ELSE quando o elemento não existir registros
                //     echo "<div class='alert alert-danger' role='alert'>Usuário não possui nivel de acesso.</div>";
                // }


                // Verifica se há niveis de acesso para o usuários no array

                if ($viewUserIsSuper) {
                    // formulário de níveis oculto para super usuário (alterações bloqueadas no controller)
                } elseif ($this->data['userAllAccessLevelsArray'] ?? false) {
                    $canManageWhistleblowingLevels = !empty($this->data['can_manage_whistleblowing_levels']);
                    $whistleblowingLevelIds = array_map('intval', $this->data['whistleblowing_access_level_ids'] ?? []);
                    ?>

                    <dl class='row'>
                        <dt class='col-sm-3'>Niveis de Acesso: </dt>
                        <dd class='col-sm-9'></dd>
                    </dl>

                    <?php if (!$canManageWhistleblowingLevels): ?>
                        <div class="alert alert-secondary small py-2" role="alert">
                            Os níveis <strong>Canal de Denúncias — Operador</strong> e
                            <strong>Canal de Denúncias — Administrador</strong> só podem ser atribuídos ou removidos por
                            <em>Super Administrador</em> ou <em>Super usuário</em>. Membros de comitê recebem Operador automaticamente ao serem vinculados ao comitê.
                        </div>
                    <?php endif; ?>

                    <form action="<?php echo $_ENV['URL_ADM']; ?>update-user-access-levels" method="POST">

                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token_update_access_level; ?>">

                        <input type="hidden" name="adms_user_id" value="<?php echo ($this->data['user']['id'] ?? ''); ?>">

                        <?php
                        //Perceorre o array de usuários
                        foreach ($this->data['userAllAccessLevelsArray'] as $userAllAccessLevelsArray) {
                            // Extrai variáveis do array de usuário
                            extract($userAllAccessLevelsArray);

                            // Verifica se o nível de acesso atual ($id) está no array de níveis de acesso do usuário
                            $userAccessLevels = $this->data['userAccessLevelsArray'] ? $this->data['userAccessLevelsArray'] : [];
                            $checked = in_array($id, $userAccessLevels) ? 'checked' : '';
                            $isWhistleblowingLevel = in_array((int) $id, $whistleblowingLevelIds, true);
                            $locked = $isWhistleblowingLevel && !$canManageWhistleblowingLevels;

                            echo "<div class='form-check form-switch'>";

                            if ($locked && $checked !== '') {
                                // Checkbox desabilitado não é enviado no POST — preserva o nível atual
                                echo "<input type='hidden' name='userAccessLevelsArray[$id]' value='$id'>";
                            }

                            $disabledAttr = $locked ? ' disabled' : '';
                            echo "<input type='checkbox' name='userAccessLevelsArray[$id]' class='form-check-input' role='switch' id='userAccessLevelsArray$id' value='$id' $checked$disabledAttr>";

                            echo "<label class='form-check-label' for='userAccessLevelsArray$id'>$name";
                            if ($locked) {
                                echo " <span class='badge text-bg-secondary'>Somente Super Administrador / Super usuário</span>";
                            }
                            echo "</label>";
                            
                            echo "</div>";
                        } ?>

                        <div class="col-12">
                            <button type="submit" class="btn btn-warning btn-sm">Salvar</button>
                        </div>
                    </form>

                <?php
                    // var_dump($userAccessLevels);
                } elseif (!$viewUserIsSuper) {
                    // Acessa o ELSE quando o elemento não existir registros
                    echo "<div class='alert alert-danger' role='alert'>Usuário não possui nivel de acesso.</div>";
                }
                ?>
            </div>

        </div>
    <?php } ?>

</div>