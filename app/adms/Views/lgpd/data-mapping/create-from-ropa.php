<?php
use App\adms\Helpers\CSRFHelper;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Data Mapping LGPD</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-data-mapping" class="text-decoration-none">Data Mapping</a>
            </li>
            <li class="breadcrumb-item">Criar a partir da ROPA</li>
        </ol>
    </div>

    <!-- DETALHES DA ROPA ORIGEM -->
    <div class="card mb-4 border-primary">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fa-solid fa-info-circle"></i> ROPA ORIGEM</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Código:</strong> <?php echo htmlspecialchars($this->data['ropa']['codigo']); ?></p>
                    <p><strong>Atividade:</strong> <?php echo htmlspecialchars($this->data['ropa']['atividade']); ?></p>
                    <p><strong>Departamento:</strong> <?php echo htmlspecialchars($this->data['ropa']['departamento_nome']); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Finalidade:</strong> <?php echo htmlspecialchars($this->data['ropa']['processing_purpose']); ?></p>
                    <p><strong>Base Legal:</strong> <?php echo htmlspecialchars($this->data['ropa']['base_legal']); ?></p>
                    <p><strong>Riscos:</strong> <?php echo htmlspecialchars($this->data['ropa']['riscos']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- FLUXOS TÉCNICOS SUGERIDOS -->
    <?php if (!empty($this->data['suggested_flows'])): ?>
    <div class="card mb-4 border-info">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fa-solid fa-lightbulb"></i> FLUXOS TÉCNICOS SUGERIDOS</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-info">
                        <tr>
                            <th>Sistema Origem</th>
                            <th>Campo Origem</th>
                            <th>Regra de Transformação</th>
                            <th>Sistema Destino</th>
                            <th>Campo Destino</th>
                            <th>Observação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($this->data['suggested_flows'] as $index => $flow): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($flow['source_system']); ?></td>
                            <td><?php echo htmlspecialchars($flow['source_field']); ?></td>
                            <td><?php echo htmlspecialchars($flow['transformation_rule']); ?></td>
                            <td><?php echo htmlspecialchars($flow['destination_system']); ?></td>
                            <td><?php echo htmlspecialchars($flow['destination_field']); ?></td>
                            <td><?php echo htmlspecialchars($flow['observation']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- FORMULÁRIO DE CRIAÇÃO -->
    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <h5 class="mb-0"><i class="fa-solid fa-plus-circle"></i> Criar Data Mapping</h5>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form method="POST" action="">
                <?php CSRFHelper::generateCSRFToken('form_create_data_mapping'); ?>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="source_system" class="form-label">Sistema Origem *</label>
                            <input type="text" class="form-control" id="source_system" name="source_system" 
                                   value="<?php echo htmlspecialchars($this->data['prefilled_data']['source_system']); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="source_field" class="form-label">Campo Origem *</label>
                            <input type="text" class="form-control" id="source_field" name="source_field" 
                                   value="<?php echo htmlspecialchars($this->data['prefilled_data']['source_field']); ?>" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="destination_system" class="form-label">Sistema Destino *</label>
                            <input type="text" class="form-control" id="destination_system" name="destination_system" 
                                   value="<?php echo htmlspecialchars($this->data['prefilled_data']['destination_system']); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="destination_field" class="form-label">Campo Destino *</label>
                            <input type="text" class="form-control" id="destination_field" name="destination_field" 
                                   value="<?php echo htmlspecialchars($this->data['prefilled_data']['destination_field']); ?>" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="transformation_rule" class="form-label">Regra de Transformação</label>
                            <textarea class="form-control" id="transformation_rule" name="transformation_rule" rows="3"><?php echo htmlspecialchars($this->data['prefilled_data']['transformation_rule']); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="observation" class="form-label">Observação</label>
                            <textarea class="form-control" id="observation" name="observation" rows="3"><?php echo htmlspecialchars($this->data['prefilled_data']['observation']); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info">
                    <i class="fa-solid fa-info-circle"></i>
                    <strong>Dica:</strong> Os campos foram pré-preenchidos com base nos dados da ROPA. 
                    Revise e ajuste conforme necessário antes de salvar.
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" name="SendAddDataMapping" class="btn btn-success">
                        <i class="fa-solid fa-save"></i> Criar Data Mapping
                    </button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-ropa-view/<?php echo $this->data['ropa']['id']; ?>" class="btn btn-secondary">
                        <i class="fa-solid fa-arrow-left"></i> Voltar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div> 