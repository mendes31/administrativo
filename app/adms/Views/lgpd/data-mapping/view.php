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
            <li class="breadcrumb-item">Visualizar</li>
        </ol>

    </div>

    <div class="card mb-4 border-light shadow">

        <div class="card-header hstack gap-2">
            <span>Visualizar</span>

            <span class="ms-auto d-sm-flex flex-row">
            <?php
                if (in_array('LgpdDataMappingEdit', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}lgpd-data-mapping-edit/{$this->data['dataMapping']['id']}' class='btn btn-warning btn-sm me-1 mb-1'><i class='fa-solid fa-pen-to-square'></i> Editar</a> ";
                }
                if (in_array('LgpdDataMapping', $this->data['buttonPermission'])) {
                    echo "<a href='{$_ENV['URL_ADM']}lgpd-data-mapping' class='btn btn-info btn-sm me-1 mb-1'><i class='fa-solid fa-list'></i> Listar</a> ";
                }
                ?>
            </span>

        </div>

        <div class="card-body">

            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th width="150">ID:</th>
                            <td><?php echo $this->data['data_mapping']['id']; ?></td>
                        </tr>
                        <tr>
                            <th>Sistema Origem:</th>
                            <td><?php echo htmlspecialchars($this->data['data_mapping']['source_system']); ?></td>
                        </tr>
                        <tr>
                            <th>Campo Origem:</th>
                            <td><?php echo htmlspecialchars($this->data['data_mapping']['source_field']); ?></td>
                        </tr>
                        <tr>
                            <th>Regra de Transformação:</th>
                            <td><?php echo htmlspecialchars($this->data['data_mapping']['transformation_rule'] ?: 'Não informado'); ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th width="150">Sistema Destino:</th>
                            <td><?php echo htmlspecialchars($this->data['data_mapping']['destination_system']); ?></td>
                        </tr>
                        <tr>
                            <th>Campo Destino:</th>
                            <td><?php echo htmlspecialchars($this->data['data_mapping']['destination_field']); ?></td>
                        </tr>
                        <tr>
                            <th>ROPA:</th>
                            <td><?php echo htmlspecialchars($this->data['data_mapping']['ropa_atividade'] ?: 'Não vinculado'); ?></td>
                        </tr>
                        <tr>
                            <th>Inventário:</th>
                            <td><?php echo htmlspecialchars($this->data['data_mapping']['inventory_area'] ?: 'Não vinculado'); ?></td>
                        </tr>
                        <tr>
                            <th>Finalidade Relacionada:</th>
                            <td><?php echo htmlspecialchars($this->data['data_mapping']['finalidade_relacionada'] ?: 'Não informado'); ?></td>
                        </tr>
                        <tr>
                            <th>Prazo de Retenção:</th>
                            <td><?php echo htmlspecialchars($this->data['data_mapping']['prazo_retencao_relacionado'] ?: 'Não informado'); ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <?php if (isset($this->data['fontes_data_mapping']) && !empty($this->data['fontes_data_mapping'])): ?>
            <div class="row">
                <div class="col-12">
                    <h5 class="mb-3">Pontos de Coleta</h5>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Fonte de Coleta</th>
                                    <th>Observações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($this->data['fontes_data_mapping'] as $fonte): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($fonte['nome']); ?></td>
                                        <td><?php echo htmlspecialchars($fonte['observacoes'] ?: 'Não informado'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-12">
                    <table class="table table-borderless">
                        <tr>
                            <th width="150">Observações:</th>
                            <td><?php echo htmlspecialchars($this->data['data_mapping']['observation'] ?: 'Não informado'); ?></td>
                        </tr>
                        <tr>
                            <th>Data de Criação:</th>
                            <td><?php echo date('d/m/Y H:i:s', strtotime($this->data['data_mapping']['created_at'])); ?></td>
                        </tr>
                        <tr>
                            <th>Última Atualização:</th>
                            <td>
                                <?php 
                                if (!empty($this->data['data_mapping']['updated_at'])) {
                                    echo date('d/m/Y H:i:s', strtotime($this->data['data_mapping']['updated_at']));
                                } else {
                                    echo '<span class="text-muted">Não atualizado</span>';
                                }
                                ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-12">
                    <?php if (in_array('LgpdDataMappingEdit', $this->data['buttonPermission'])): ?>
                        <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-data-mapping-edit/<?php echo $this->data['data_mapping']['id']; ?>" class="btn btn-warning">
                            <i class="fa-solid fa-pen-to-square"></i> Editar
                        </a>
                    <?php endif; ?>
                    <?php if (in_array('LgpdDataMappingDelete', $this->data['buttonPermission'])): ?>
                        <form id="formDelete<?php echo $this->data['data_mapping']['id']; ?>" action="<?php echo $_ENV['URL_ADM']; ?>lgpd-data-mapping-delete" method="POST" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_delete_data_mapping'); ?>">
                            <input type="hidden" name="id" value="<?php echo $this->data['data_mapping']['id']; ?>">
                            <button type="submit" class="btn btn-danger" onclick="confirmDeletion(event, <?php echo $this->data['data_mapping']['id']; ?>)">
                                <i class="fa-regular fa-trash-can"></i> Apagar
                            </button>
                        </form>
                    <?php endif; ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-data-mapping" class="btn btn-secondary">
                        <i class="fa-solid fa-arrow-left"></i> Voltar
                    </a>
                </div>
            </div>

        </div>

    </div>
</div>