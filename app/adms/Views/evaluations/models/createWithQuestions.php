<?php
use App\adms\Helpers\CSRFHelper;
$csrf_token = CSRFHelper::generateCSRFToken('form_create_evaluation_full');
?>

<!-- CSS Moderno para Formulários de Avaliação -->
<link rel="stylesheet" href="<?= $_ENV['URL_ADM'] ?>public/adms/css/evaluation-forms-modern.css">

<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">
            <i class="fas fa-clipboard-list"></i> Criar Questionário Completo
        </h2>
        <ol class="breadcrumb mb-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?= $_ENV['URL_ADM'] ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= $_ENV['URL_ADM'] ?>list-evaluation-models" class="text-decoration-none">Avaliações</a>
            </li>
            <li class="breadcrumb-item active">Criar Questionário</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <form id="formQuestionarioCompleto" method="POST" action="<?= $_ENV['URL_ADM'] ?>create-evaluation-model-with-questions">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

        <!-- CARD 1: DADOS BÁSICOS -->
        <div class="card mb-4 border-light shadow">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-info-circle"></i> Dados do Questionário</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Treinamento <span class="text-danger">*</span></label>
                        <select name="adms_training_id" class="form-select" required>
                            <option value="">Selecione um treinamento...</option>
                            <?php foreach ($this->data['trainings'] as $training): ?>
                                <option value="<?= $training['id'] ?>"><?= htmlspecialchars($training['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Título do Questionário <span class="text-danger">*</span></label>
                        <input type="text" name="titulo" class="form-control" required 
                               placeholder="Ex: Avaliação de Segurança do Trabalho">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Código do Documento <span class="text-danger">*</span></label>
                        <input type="text" name="codigo_documento" class="form-control" required 
                               placeholder="Ex: AVAL-NOR-TI-0004">
                        <small class="text-muted">Código de identificação do documento</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Versão <span class="text-danger">*</span></label>
                        <input type="text" name="versao_documento" class="form-control" required 
                               placeholder="Ex: 1.0 ou v01">
                        <small class="text-muted">Versão do documento</small>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Descrição/Instruções</label>
                    <textarea name="descricao" class="form-control" rows="3" 
                              placeholder="Instruções para o usuário que responderá..."></textarea>
                </div>

                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Nota Mínima Aprovação</label>
                        <input type="number" name="nota_minima_aprovacao" class="form-control" 
                               value="7.00" step="0.01" min="0" max="10">
                        <small class="text-muted">De 0 a 10</small>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Tempo Limite (minutos)</label>
                        <input type="number" name="tempo_limite" class="form-control" 
                               placeholder="Deixe vazio se ilimitado">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Máximo de Tentativas</label>
                        <input type="number" name="max_tentativas" class="form-control" 
                               placeholder="Deixe vazio se ilimitado">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Opções</label>
                        <div class="form-check">
                            <input type="checkbox" name="permitir_refazer" value="1" checked 
                                   class="form-check-input" id="chk_refazer">
                            <label class="form-check-label" for="chk_refazer">Permitir refazer</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="mostrar_gabarito" value="1" checked 
                                   class="form-check-input" id="chk_gabarito">
                            <label class="form-check-label" for="chk_gabarito">Mostrar gabarito</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CARD 2: QUESTÕES -->
        <div class="card mb-4 border-light shadow">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-question-circle"></i> Questões do Questionário</h5>
                <button type="button" class="btn btn-light btn-sm" id="btnAdicionarQuestao">
                    <i class="fas fa-plus"></i> Adicionar Questão
                </button>
            </div>
            <div class="card-body">
                <div id="questoesContainer">
                    <!-- Questões serão adicionadas aqui via JavaScript -->
                </div>
            </div>
        </div>

        <!-- BOTÕES FINAIS -->
        <div class="mb-4 text-end">
            <a href="<?= $_ENV['URL_ADM'] ?>list-evaluation-models" class="btn btn-secondary btn-lg">
                <i class="fas fa-times"></i> Cancelar
            </a>
            <button type="submit" class="btn btn-success btn-lg">
                <i class="fas fa-save"></i> Salvar Questionário Completo
            </button>
        </div>
    </form>
</div>

<!-- TEMPLATE DE QUESTÃO -->
<template id="templateQuestao">
    <div class="card questao-card mb-3 border-start border-primary border-4" data-questao-index="">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <span class="questao-numero fw-bold">Questão #<span class="numero">1</span></span>
            <button type="button" class="btn btn-sm btn-danger btnRemoverQuestao">
                <i class="fas fa-trash"></i> Remover
            </button>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-8">
                    <label class="form-label">Tipo de Questão <span class="text-danger">*</span></label>
                    <select name="questoes[INDEX][tipo]" class="form-select tipoQuestao" required>
                        <option value="">Selecione o tipo...</option>
                        <option value="multipla_escolha">Múltipla Escolha</option>
                        <option value="verdadeiro_falso">Verdadeiro ou Falso</option>
                        <option value="texto">Texto Livre (dissertativa)</option>
                        <option value="numerica">Numérica</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">
                        Pontos <span class="text-danger">*</span>
                        <i class="fas fa-info-circle text-info" title="Quanto vale esta questão"></i>
                    </label>
                    <input type="number" name="questoes[INDEX][pontos]" class="form-control inputPontos" 
                           value="1.00" step="0.01" min="0.01" max="100" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Pergunta <span class="text-danger">*</span></label>
                <textarea name="questoes[INDEX][pergunta]" class="form-control" 
                          rows="2" required placeholder="Digite a pergunta..."></textarea>
            </div>

            <!-- ALTERNATIVAS (múltipla escolha) -->
            <div class="alternativas-container" style="display: none;">
                <label class="form-label">
                    Alternativas <span class="text-danger">*</span>
                    <small class="text-muted">(Marque o ○ da alternativa correta)</small>
                </label>
                <div class="alternativas-list"></div>
                <button type="button" class="btn btn-sm btn-outline-primary btnAdicionarAlternativa mt-2">
                    <i class="fas fa-plus"></i> Adicionar Alternativa
                </button>
            </div>

            <!-- GABARITO VERDADEIRO/FALSO -->
            <div class="gabarito-vf-container" style="display: none;">
                <label class="form-label">Resposta Correta <span class="text-danger">*</span></label>
                <select name="questoes[INDEX][resposta_correta_vf]" class="form-select">
                    <option value="Verdadeiro">Verdadeiro</option>
                    <option value="Falso">Falso</option>
                </select>
            </div>

            <!-- GABARITO TEXTO/NUMÉRICA -->
            <div class="gabarito-texto-container" style="display: none;">
                <label class="form-label">Resposta Correta (opcional)</label>
                <input type="text" name="questoes[INDEX][resposta_correta_texto]" class="form-control" 
                       placeholder="Digite a resposta esperada (para correção automática)">
                <small class="text-muted">Deixe vazio se a correção for manual</small>
            </div>

            <div class="mt-3">
                <label class="form-label">Explicação/Feedback (opcional)</label>
                <textarea name="questoes[INDEX][explicacao]" class="form-control" 
                          rows="2" placeholder="Explicação que aparecerá após o usuário responder..."></textarea>
            </div>
        </div>
    </div>
</template>

<!-- TEMPLATE DE ALTERNATIVA -->
<template id="templateAlternativa">
    <div class="input-group mb-2 alternativa-item">
        <span class="input-group-text bg-light">
            <input type="radio" name="questoes[INDEX][resposta_correta]" value="INDEX" 
                   title="Marcar como correta" class="form-check-input mt-0">
        </span>
        <input type="text" name="questoes[INDEX][alternativas][]" class="form-control" 
               placeholder="Digite a alternativa...">
        <button type="button" class="btn btn-outline-danger btnRemoverAlternativa" type="button">
            <i class="fas fa-times"></i>
        </button>
    </div>
</template>

<style>
.questao-card {
    transition: all 0.3s ease;
}

.questao-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.questao-numero {
    font-size: 1.1em;
}

.alternativa-item input[type="radio"]:checked {
    background-color: #28a745;
}

#questoesContainer:empty::before {
    content: 'Nenhuma questão adicionada. Clique em "Adicionar Questão" para começar.';
    display: block;
    text-align: center;
    padding: 60px 20px;
    color: #6c757d;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 8px;
    border: 2px dashed #dee2e6;
    font-size: 1.1em;
}
</style>

<script src="<?= $_ENV['URL_ADM'] ?>public/adms/js/evaluation-form-builder.js"></script>

