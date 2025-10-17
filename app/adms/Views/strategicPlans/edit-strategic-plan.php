<?php
$plan = $this->data['plan'] ?? [];
$departments = $this->data['departments'] ?? [];
$users = $this->data['users'] ?? [];
$canManageOtherDepartments = $this->data['canManageOtherDepartments'] ?? false;
$currentUser = $this->data['currentUser'] ?? [];
// Cabeçalho já incluso pelo controller
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Editar Plano Estratégico</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-strategic-plans" class="text-decoration-none">Planos Estratégicos</a>
            </li>
            <li class="breadcrumb-item">Editar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <span><i class="fas fa-project-diagram me-2"></i>Editar Plano Estratégico</span>
        </div>
        <div class="card-body">
            <form method="post" action="<?php echo $_ENV['URL_ADM']; ?>edit-strategic-plan/<?= $plan['id'] ?? '' ?>">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label for="title" class="form-label">
                            <strong>Título do Plano *</strong>
                        </label>
                        <input type="text" 
                               name="title" 
                               id="title" 
                               class="form-control" 
                               placeholder="Digite o título do plano estratégico"
                               value="<?= htmlspecialchars($plan['title'] ?? '') ?>"
                               required>
                        <div class="form-text">Título descritivo do plano estratégico</div>
                    </div>

                    <div class="col-md-6">
                        <label for="department_id" class="form-label">
                            <strong>Departamento *</strong>
                        </label>
                        <select name="department_id" 
                                id="department_id" 
                                class="form-select" 
                                required
                                <?= !$canManageOtherDepartments ? 'readonly' : '' ?>
                                onchange="<?= $canManageOtherDepartments ? 'loadUsersByDepartment(this.value)' : '' ?>">
                            <option value="">Selecione o departamento</option>
                            <?php foreach ($departments as $department): ?>
                                <option value="<?= $department['id'] ?>" 
                                        <?= ($plan['department_id'] ?? '') == $department['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($department['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Departamento responsável pelo plano</div>
                    </div>

                    <div class="col-md-6">
                        <label for="responsible_id" class="form-label">
                            <strong>Responsável *</strong>
                        </label>
                        <select name="responsible_id" 
                                id="responsible_id" 
                                class="form-select" 
                                required
                                <?= !$canManageOtherDepartments ? 'readonly' : '' ?>>
                            <option value="">Selecione o responsável</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= $user['id'] ?>" 
                                        <?= ($plan['responsible_id'] ?? '') == $user['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($user['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Pessoa responsável pelo plano</div>
                    </div>

                    <div class="col-md-6">
                        <label for="start_date" class="form-label">
                            <strong>Data de Início *</strong>
                        </label>
                        <input type="date" 
                               name="start_date" 
                               id="start_date" 
                               class="form-control"
                               value="<?= $plan['start_date'] ?? '' ?>"
                               required>
                        <div class="form-text">Data de início do plano</div>
                    </div>

                    <div class="col-md-6">
                        <label for="end_date" class="form-label">
                            <strong>Data de Término *</strong>
                        </label>
                        <input type="date" 
                               name="end_date" 
                               id="end_date" 
                               class="form-control"
                               value="<?= $plan['end_date'] ?? '' ?>"
                               required>
                        <div class="form-text">Data de término do plano</div>
                    </div>

                    <!-- 5W2H -->
                    <div class="col-12">
                        <h5 class="text-primary mt-4 mb-3">Metodologia 5W2H</h5>
                    </div>

                    <div class="col-md-6">
                        <label for="what" class="form-label">
                            <strong>O QUE (What) *</strong>
                        </label>
                        <textarea name="what" 
                                  id="what" 
                                  class="form-control" 
                                  rows="3" 
                                  placeholder="O que será feito?"
                                  required><?= htmlspecialchars($plan['what'] ?? '') ?></textarea>
                        <div class="form-text">Descrição do que será realizado</div>
                    </div>

                    <div class="col-md-6">
                        <label for="why" class="form-label">
                            <strong>POR QUE (Why) *</strong>
                        </label>
                        <textarea name="why" 
                                  id="why" 
                                  class="form-control" 
                                  rows="3" 
                                  placeholder="Por que será feito?"
                                  required><?= htmlspecialchars($plan['why'] ?? '') ?></textarea>
                        <div class="form-text">Justificativa e motivação</div>
                    </div>

                    <div class="col-md-6">
                        <label for="where_field" class="form-label">
                            <strong>ONDE (Where) *</strong>
                        </label>
                        <textarea name="where_field" 
                                  id="where_field" 
                                  class="form-control" 
                                  rows="3" 
                                  placeholder="Onde será feito?"
                                  required><?= htmlspecialchars($plan['where_field'] ?? '') ?></textarea>
                        <div class="form-text">Local ou área de atuação</div>
                    </div>

                    <div class="col-md-6">
                        <label for="who_field" class="form-label">
                            <strong>QUEM (Who) *</strong>
                        </label>
                        <textarea name="who_field" 
                                  id="who_field" 
                                  class="form-control" 
                                  rows="3" 
                                  placeholder="Quem fará?"
                                  required><?= htmlspecialchars($plan['who_field'] ?? '') ?></textarea>
                        <div class="form-text">Responsáveis e equipe envolvida</div>
                    </div>

                    <div class="col-md-6">
                        <label for="how" class="form-label">
                            <strong>COMO (How) *</strong>
                        </label>
                        <textarea name="how" 
                                  id="how" 
                                  class="form-control" 
                                  rows="3" 
                                  placeholder="Como será feito?"
                                  required><?= htmlspecialchars($plan['how'] ?? '') ?></textarea>
                        <div class="form-text">Metodologia e processos</div>
                    </div>

                    <div class="col-md-6">
                        <label for="how_much" class="form-label">
                            <strong>QUANTO (How Much) *</strong>
                        </label>
                        <textarea name="how_much" 
                                  id="how_much" 
                                  class="form-control" 
                                  rows="3" 
                                  placeholder="Quanto custará?"
                                  required><?= htmlspecialchars($plan['how_much'] ?? '') ?></textarea>
                        <div class="form-text">Recursos financeiros necessários</div>
                    </div>

                    <div class="col-md-6">
                        <label for="status" class="form-label">
                            <strong>Status *</strong>
                        </label>
                        <select name="status" id="status" class="form-select" required>
                            <option value="">Selecione o status</option>
                            <option value="Não iniciado" <?= ($plan['status'] ?? '') == 'Não iniciado' ? 'selected' : '' ?>>Não iniciado</option>
                            <option value="Em andamento" <?= ($plan['status'] ?? '') == 'Em andamento' ? 'selected' : '' ?>>Em andamento</option>
                            <option value="Concluído" <?= ($plan['status'] ?? '') == 'Concluído' ? 'selected' : '' ?>>Concluído</option>
                            <option value="Atrasado" <?= ($plan['status'] ?? '') == 'Atrasado' ? 'selected' : '' ?>>Atrasado</option>
                        </select>
                        <div class="form-text">Status atual do plano</div>
                    </div>

                    <div class="col-md-6">
                        <label for="completed" class="form-label">
                            <strong>Percentual de Conclusão</strong>
                        </label>
                        <input type="number" 
                               name="completed" 
                               id="completed" 
                               class="form-control" 
                               min="0" 
                               max="100" 
                               placeholder="0"
                               value="<?= $plan['completed'] ?? '0' ?>">
                        <div class="form-text">Percentual de conclusão (0-100%)</div>
                    </div>

                    <div class="col-md-12">
                        <label for="comment" class="form-label">
                            <strong>Comentários</strong>
                        </label>
                        <textarea name="comment" 
                                  id="comment" 
                                  class="form-control" 
                                  rows="3" 
                                  placeholder="Observações adicionais"><?= htmlspecialchars($plan['comment'] ?? '') ?></textarea>
                        <div class="form-text">Comentários e observações (opcional)</div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-2"></i>Salvar Alterações
                            </button>
                            <a href="<?php echo $_ENV['URL_ADM']; ?>view-strategic-plan/<?= $plan['id'] ?? '' ?>" class="btn btn-info">
                                <i class="fas fa-eye me-2"></i>Visualizar
                            </a>
                            <a href="<?php echo $_ENV['URL_ADM']; ?>list-strategic-plans" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Voltar
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($canManageOtherDepartments): ?>
<script>
function loadUsersByDepartment(departmentId) {
    const responsibleSelect = document.getElementById('responsible_id');

    // Limpar opções atuais
    responsibleSelect.innerHTML = '<option value="">Selecione o responsável</option>';

    if (!departmentId) {
        return;
    }

    // Mostrar loading
    responsibleSelect.innerHTML = '<option value="">Carregando...</option>';

    // Fazer requisição AJAX
    fetch('<?php echo $_ENV['URL_ADM']; ?>create-strategic-plan?method=getUsersByDepartment&department_id=' + departmentId, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Limpar e adicionar nova opção padrão
            responsibleSelect.innerHTML = '<option value="">Selecione o responsável</option>';

            // Adicionar usuários
            data.users.forEach(user => {
                const option = document.createElement('option');
                option.value = user.id;
                option.textContent = user.name;
                
                // Verificar se é o usuário selecionado atualmente
                if (user.id == '<?= $plan['responsible_id'] ?? '' ?>') {
                    option.selected = true;
                }
                
                responsibleSelect.appendChild(option);
            });
        } else {
            console.error('Erro ao carregar usuários:', data.error);
            responsibleSelect.innerHTML = '<option value="">Erro ao carregar usuários</option>';
        }
    })
    .catch(error => {
        console.error('Erro na requisição:', error);
        responsibleSelect.innerHTML = '<option value="">Erro ao carregar usuários</option>';
    });
}
</script>
<?php endif; ?>
