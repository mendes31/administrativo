<?php
?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
/* Responsividade para mobile */
@media (max-width: 767px) {
    .select2-container { width: 100% !important; }
    #lista-colaboradores th, #lista-colaboradores td { font-size: 0.95em; }
    #lista-colaboradores { font-size: 0.95em; }
    .btn { font-size: 0.95em; padding: 0.4em 0.7em; }
}
</style>
<div class="container-fluid px-4">
    <h2 class="mt-3">Vincular Colaboradores ao Treinamento</h2>
    <?php if (!empty($this->data['training_info'])): ?>
        <div class="card mb-3 border-info">
            <div class="card-body py-2">
                <span class="fw-bold"><i class="fas fa-link"></i> <?php echo htmlspecialchars($this->data['training_info']['nome']); ?></span>
                <span class="ms-3 text-muted">Código: <?php echo htmlspecialchars($this->data['training_info']['codigo']); ?></span>
                <span class="ms-3 text-muted">Versão: <?php echo htmlspecialchars($this->data['training_info']['versao']); ?></span>
                <span class="ms-3 text-muted">Tipo: <?php echo htmlspecialchars($this->data['training_info']['tipo']); ?></span>
            </div>
        </div>
    <?php endif; ?>
    <form method="post" action="<?php echo $_ENV['URL_ADM']; ?>save-training-user-link" id="form-vincular-colaboradores">
        <input type="hidden" name="training_id" value="<?php echo htmlspecialchars($this->data['training_id']); ?>">
        <div class="card mb-4 border-light shadow">
            <div class="card-header">
                <strong>Selecione o colaborador:</strong>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-12 col-md-8 mb-2 mb-md-0">
                        <select id="select-colaborador" class="form-control" style="width:100%">
                            <option value="">Digite para buscar...</option>
                            <?php foreach ($this->data['users'] as $user): ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo $user['name'] . ' (' . $user['email'] . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-4 d-grid d-md-flex align-items-center">
                        <button type="button" class="btn btn-primary w-100 w-md-auto" id="btn-adicionar-colaborador">Adicionar</button>
                    </div>
                </div>
            </div>
        </div>
        <div id="hidden-users"></div>
        <div class="d-flex flex-column flex-md-row justify-content-end gap-2">
            <button type="submit" class="btn btn-success">Vincular Selecionados</button>
            <a href="<?php echo $_ENV['URL_ADM']; ?>list-trainings" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>

    <div class="row">
        <div class="col-12">
            <div class="table-responsive">
                <table class="table table-bordered align-middle" id="lista-colaboradores" style="table-layout: fixed; width: 100%;">
                    <thead class="table-light">
                        <tr>
                            <th class="col-nome">Nome</th>
                            <th>E-mail</th>
                            <th style="width: 90px;">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($this->data['vinculados'])): ?>
                            <?php foreach ($this->data['vinculados'] as $user): ?>
                                <tr id="user-row-<?php echo $user['id']; ?>">
                                    <td class="col-nome">
                                        <?php echo htmlspecialchars($user['name']) . ' (' . htmlspecialchars($user['cargo_nome']) . ')'; ?>
                                        <?php if ($user['tipo'] === 'cargo'): ?>
                                            <span title="Vínculo pelo cargo" style="color:#888; font-size:0.95em;">(cargo)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <?php if (isset($user['tipo']) && strtolower(trim($user['tipo'])) === 'individual'): ?>
                                            <form method="post" action="<?php echo $_ENV['URL_ADM']; ?>delete-training-user-link" style="display: inline;" id="form-remove-<?php echo $user['id']; ?>">
                                                <input type="hidden" name="training_id" value="<?php echo htmlspecialchars($this->data['training_id']); ?>">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" data-user-id="<?php echo $user['id']; ?>">Remover</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted" title="Vínculo pelo cargo">Não removível</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
    // Inicializar Select2
    $(document).ready(function() {
        $('#select-colaborador').select2({
            placeholder: 'Digite para buscar...',
            width: '100%',
            allowClear: true,
            language: {
                noResults: function() { return 'Nenhum colaborador encontrado'; }
            }
        });
    });

    // Adicionar colaborador à lista
    document.getElementById('btn-adicionar-colaborador').onclick = function() {
        console.log('Botão Adicionar clicado');
        var select = document.getElementById('select-colaborador');
        var userId = select.value;
        var userText = select.options[select.selectedIndex].text;
        console.log('User ID selecionado:', userId);
        console.log('User Text selecionado:', userText);
        
        if (!userId) {
            console.log('Nenhum usuário selecionado');
            return;
        }
        
        // Evitar duplicidade
        if (document.getElementById('user-row-' + userId)) {
            console.log('Usuário já existe na lista');
            return;
        }
        
        var parts = userText.match(/^(.*) \((.*)\)$/);
        var name = parts ? parts[1] : userText;
        var email = parts ? parts[2] : '';
        console.log('Nome extraído:', name);
        console.log('Email extraído:', email);
        
        var tbody = document.querySelector('#lista-colaboradores tbody');
        var tr = document.createElement('tr');
        tr.id = 'user-row-' + userId;
        tr.innerHTML = '<td class="col-nome">' + name + '</td>' +
                       '<td>' + email + '</td>' +
                       '<td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest(\'tr\').remove(); document.getElementById(\'hidden-user-' + userId + '\').remove();">Remover</button></td>';
        tbody.appendChild(tr);
        console.log('Linha adicionada à tabela');
        
        // Adicionar input hidden no form
        var hiddenDiv = document.getElementById('hidden-users');
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'user_ids[]';
        input.value = userId;
        input.id = 'hidden-user-' + userId;
        hiddenDiv.appendChild(input);
        console.log('Input hidden adicionado:', input.outerHTML);
        
        // Limpar seleção do Select2
        $('#select-colaborador').val(null).trigger('change');
        console.log('Select2 limpo');
        
        // Verificar inputs hidden existentes
        var allHiddenInputs = document.querySelectorAll('input[name="user_ids[]"]');
        console.log('Total de inputs hidden:', allHiddenInputs.length);
        allHiddenInputs.forEach(function(input, index) {
            console.log('Input ' + index + ':', input.value);
        });
    };

    // Forçar submit dos formulários de remoção e adicionar logs de debug
    document.addEventListener('DOMContentLoaded', function() {
        // Adicionar log de debug para o formulário principal
        document.getElementById('form-vincular-colaboradores').addEventListener('submit', function(e) {
            console.log('Formulário sendo enviado...');
            
            // Verificar se há inputs hidden com user_ids
            var hiddenInputs = document.querySelectorAll('input[name="user_ids[]"]');
            console.log('Inputs hidden encontrados:', hiddenInputs.length);
            
            var userIds = [];
            hiddenInputs.forEach(function(input) {
                userIds.push(input.value);
                console.log('User ID encontrado:', input.value);
            });
            
            console.log('Todos os User IDs:', userIds);
            
            // Se não há user_ids, prevenir envio
            if (userIds.length === 0) {
                e.preventDefault();
                alert('Selecione pelo menos um colaborador antes de vincular.');
                return false;
            }
            
            console.log('Formulário será enviado com:', {
                training_id: document.querySelector('input[name="training_id"]').value,
                user_ids: userIds
            });
            
            // Alert temporário para debug - REMOVIDO
            // alert('Formulário sendo enviado com ' + userIds.length + ' usuário(s): ' + userIds.join(', '));
        });
        
        document.querySelectorAll('button[data-user-id]').forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                var userId = this.getAttribute('data-user-id');
                var form = this.closest('form');
                // SweetAlert2 para confirmação
                Swal.fire({
                    title: 'Tem certeza que deseja excluir esse registro?',
                    text: 'Você não poderá reverter esta ação!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sim, excluir!',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    });
    </script>
</div> 