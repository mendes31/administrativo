<?php

use App\adms\Helpers\CSRFHelper;

?>

<div class="wrapper">
    <div class="row">
        <div class="top-list">
            <span class="title-content">Visualizar AIPD</span>
            <div class="top-list-right">
                <a href="<?= $_ENV['URL_ADM'] ?>lgpd-aipd" class="btn-info">Listar</a>
                <a href="<?= $_ENV['URL_ADM'] ?>lgpd-aipd-edit/<?= $this->data['aipd']['id'] ?>" class="btn-warning">Editar</a>
            </div>
        </div>

        <div class="content-adm-alert">
            <?php
            if (isset($_SESSION['msg'])) {
                echo $_SESSION['msg'];
                unset($_SESSION['msg']);
            }
            ?>
        </div>

        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Avaliação de Impacto à Proteção de Dados (AIPD)</h4>
            </div>
            <div class="card-body">
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label"><strong>Nome da AIPD:</strong></label>
                            <p class="form-control-static"><?= $this->data['aipd']['nome'] ?? 'N/A' ?></p>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label"><strong>Status:</strong></label>
                            <p class="form-control-static">
                                <?php
                                $status = $this->data['aipd']['status'] ?? '';
                                $statusClass = '';
                                switch ($status) {
                                    case 'rascunho':
                                        $statusClass = 'badge bg-secondary';
                                        break;
                                    case 'em_analise':
                                        $statusClass = 'badge bg-warning';
                                        break;
                                    case 'aprovada':
                                        $statusClass = 'badge bg-success';
                                        break;
                                    case 'reprovada':
                                        $statusClass = 'badge bg-danger';
                                        break;
                                    case 'concluida':
                                        $statusClass = 'badge bg-info';
                                        break;
                                    default:
                                        $statusClass = 'badge bg-secondary';
                                }
                                ?>
                                <span class="<?= $statusClass ?>"><?= ucfirst(str_replace('_', ' ', $status)) ?></span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label"><strong>Departamento:</strong></label>
                            <p class="form-control-static"><?= $this->data['aipd']['departamento_nome'] ?? 'N/A' ?></p>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label"><strong>Responsável:</strong></label>
                            <p class="form-control-static"><?= $this->data['aipd']['responsavel_nome'] ?? 'N/A' ?></p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label"><strong>Data de Início:</strong></label>
                            <p class="form-control-static">
                                <?= $this->data['aipd']['data_inicio'] ? date('d/m/Y', strtotime($this->data['aipd']['data_inicio'])) : 'N/A' ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label"><strong>Data de Conclusão:</strong></label>
                            <p class="form-control-static">
                                <?= $this->data['aipd']['data_fim'] ? date('d/m/Y', strtotime($this->data['aipd']['data_fim'])) : 'N/A' ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label"><strong>Data de Criação:</strong></label>
                            <p class="form-control-static">
                                <?= $this->data['aipd']['created'] ? date('d/m/Y H:i', strtotime($this->data['aipd']['created'])) : 'N/A' ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label"><strong>Última Atualização:</strong></label>
                            <p class="form-control-static">
                                <?= $this->data['aipd']['modified'] ? date('d/m/Y H:i', strtotime($this->data['aipd']['modified'])) : 'N/A' ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label"><strong>Descrição:</strong></label>
                    <p class="form-control-static"><?= nl2br($this->data['aipd']['descricao'] ?? 'N/A') ?></p>
                </div>

                <div class="form-group">
                    <label class="form-label"><strong>Objetivo:</strong></label>
                    <p class="form-control-static"><?= nl2br($this->data['aipd']['objetivo'] ?? 'N/A') ?></p>
                </div>

                <div class="form-group">
                    <label class="form-label"><strong>Escopo:</strong></label>
                    <p class="form-control-static"><?= nl2br($this->data['aipd']['escopo'] ?? 'N/A') ?></p>
                </div>

                <div class="form-group">
                    <label class="form-label"><strong>Metodologia:</strong></label>
                    <p class="form-control-static"><?= nl2br($this->data['aipd']['metodologia'] ?? 'N/A') ?></p>
                </div>

                <div class="form-group">
                    <label class="form-label"><strong>Observações:</strong></label>
                    <p class="form-control-static"><?= nl2br($this->data['aipd']['observacoes'] ?? 'N/A') ?></p>
                </div>

                <?php if (!empty($this->data['data_groups'])): ?>
                <div class="form-group">
                    <label class="form-label"><strong>Grupos de Dados Relacionados:</strong></label>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Nome do Grupo</th>
                                    <th>Descrição</th>
                                    <th>Classificação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($this->data['data_groups'] as $group): ?>
                                <tr>
                                    <td><?= $group['nome'] ?? 'N/A' ?></td>
                                    <td><?= $group['descricao'] ?? 'N/A' ?></td>
                                    <td>
                                        <?php
                                        $classificacao = $group['classificacao'] ?? '';
                                        $classificacaoClass = '';
                                        switch ($classificacao) {
                                            case 'publico':
                                                $classificacaoClass = 'badge bg-success';
                                                break;
                                            case 'interno':
                                                $classificacaoClass = 'badge bg-warning';
                                                break;
                                            case 'confidencial':
                                                $classificacaoClass = 'badge bg-danger';
                                                break;
                                            case 'restrito':
                                                $classificacaoClass = 'badge bg-dark';
                                                break;
                                            default:
                                                $classificacaoClass = 'badge bg-secondary';
                                        }
                                        ?>
                                        <span class="<?= $classificacaoClass ?>"><?= ucfirst($classificacao) ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<style>
.form-control-static {
    padding: 0.375rem 0.75rem;
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    min-height: 38px;
    display: flex;
    align-items: center;
}

.form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
}
</style>
