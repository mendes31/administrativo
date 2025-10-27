<?php
use App\adms\Helpers\CSRFHelper;
$csrf_token = CSRFHelper::generateCSRFToken('form_update_evaluation_model');
$model = $this->data['model'];
$trainings = $this->data['trainings'] ?? [];
?>

<!-- CSS Moderno para Formulários de Avaliação -->
<link rel="stylesheet" href="<?= $_ENV['URL_ADM'] ?>public/adms/css/evaluation-forms-modern.css">

<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">
            <i class="fas fa-edit"></i> Editar Modelo de Avaliação
        </h2>
        <ol class="breadcrumb mb-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?= $_ENV['URL_ADM'] ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= $_ENV['URL_ADM'] ?>list-evaluation-models" class="text-decoration-none">Avaliações</a>
            </li>
            <li class="breadcrumb-item active">Editar</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <form method="POST" action="<?= $_ENV['URL_ADM'] ?>update-evaluation-model/<?= $model['id'] ?>" id="formEditarQuestionario">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

        <div class="card mb-4 border-light shadow">
            <div class="card-header">
                <h5 class="mb-0">Dados do Modelo</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Treinamento <span class="text-danger">*</span></label>
                        <select name="adms_training_id" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($trainings as $training): ?>
                                <option value="<?= $training['id'] ?>" 
                                        <?= $model['adms_training_id'] == $training['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($training['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Status</label>
                        <select name="ativo" class="form-select">
                            <option value="1" <?= $model['ativo'] == 1 ? 'selected' : '' ?>>Ativo</option>
                            <option value="0" <?= $model['ativo'] == 0 ? 'selected' : '' ?>>Inativo</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Título <span class="text-danger">*</span></label>
                    <input type="text" name="titulo" class="form-control" 
                           value="<?= htmlspecialchars($model['titulo']) ?>" required>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Código do Documento <span class="text-danger">*</span></label>
                        <input type="text" name="codigo_documento" class="form-control" required
                               value="<?= htmlspecialchars($model['codigo_documento'] ?? '') ?>"
                               placeholder="Ex: AVAL-NOR-TI-0004">
                        <small class="text-muted">Código de identificação do documento</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Versão <span class="text-danger">*</span></label>
                        <input type="text" name="versao_documento" class="form-control" required
                               value="<?= htmlspecialchars($model['versao_documento'] ?? '') ?>"
                               placeholder="Ex: 1.0 ou v01">
                        <small class="text-muted">Versão do documento</small>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Descrição</label>
                    <textarea name="descricao" class="form-control" rows="3"><?= htmlspecialchars($model['descricao'] ?? '') ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Nota Mínima Aprovação</label>
                        <input type="number" name="nota_minima_aprovacao" class="form-control" 
                               value="<?= $model['nota_minima_aprovacao'] ?? 7.00 ?>" 
                               step="0.01" min="0" max="10">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Tempo Limite (min)</label>
                        <input type="number" name="tempo_limite" class="form-control" 
                               value="<?= $model['tempo_limite'] ?? '' ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Máx Tentativas</label>
                        <input type="number" name="max_tentativas" class="form-control" 
                               value="<?= $model['max_tentativas'] ?? '' ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Opções</label>
                        <div class="form-check">
                            <input type="checkbox" name="permitir_refazer" value="1" 
                                   class="form-check-input" id="chk_refazer"
                                   <?= ($model['permitir_refazer'] ?? 1) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="chk_refazer">Permitir refazer</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="mostrar_gabarito" value="1" 
                                   class="form-check-input" id="chk_gabarito"
                                   <?= ($model['mostrar_gabarito'] ?? 1) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="chk_gabarito">Mostrar gabarito</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- GERENCIAR QUESTÕES -->
        <div class="card mb-4 border-light shadow">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-question-circle"></i> Questões do Questionário
                </h5>
                <button type="button" class="btn btn-light btn-sm" id="btnAdicionarQuestao">
                    <i class="fas fa-plus"></i> Adicionar Questão
                </button>
            </div>
            <div class="card-body">
                <div id="questoesContainer"></div>
                <div id="totalPontosArea" class="alert alert-info mt-3">
                    <strong>Total de Pontos:</strong> <span id="totalPontosDisplay">0.00</span>
                </div>
            </div>
        </div>

        <div class="text-end mb-4">
            <a href="<?= $_ENV['URL_ADM'] ?>list-evaluation-models" class="btn btn-secondary btn-lg">
                <i class="fas fa-arrow-left"></i> Cancelar
            </a>
            <button type="submit" class="btn btn-success btn-lg" onclick="console.log('Botão Salvar clicado!');">
                <i class="fas fa-save"></i> Salvar Alterações
            </button>
        </div>
    </form>
</div>

<!-- TEMPLATE DE QUESTÃO -->
<template id="templateQuestao">
    <div class="card mb-3 questao-card border-secondary">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <span class="questao-numero">
                <i class="fas fa-list-ol"></i> Questão <span class="numero">1</span>
            </span>
            <button type="button" class="btn btn-sm btn-danger btnRemoverQuestao">
                <i class="fas fa-trash"></i> Remover
            </button>
        </div>
        <div class="card-body">
            <input type="hidden" name="questoes[INDEX][id]" value="" class="questao-id">
            
            <div class="row mb-3">
                <div class="col-md-8">
                    <label class="form-label">Tipo de Questão <span class="text-danger">*</span></label>
                    <select name="questoes[INDEX][tipo]" class="form-select tipoQuestao" required>
                        <option value="multipla_escolha">Múltipla Escolha</option>
                        <option value="verdadeiro_falso">Verdadeiro/Falso</option>
                        <option value="texto">Texto (Dissertativa)</option>
                        <option value="numerica">Numérica</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Pontos <span class="text-danger">*</span></label>
                    <input type="number" name="questoes[INDEX][pontos]" class="form-control inputPontos" 
                           value="1.00" step="0.01" min="0" required>
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

<!-- CARREGAR QUESTÕES EXISTENTES -->
<script>
const questoesExistentes = <?= json_encode($this->data['questoes'] ?? []) ?>;
console.log('Questões existentes definidas:', questoesExistentes);
</script>

<script src="<?= $_ENV['URL_ADM'] ?>public/adms/js/evaluation-form-builder.js"></script>

<script>
// Carregar questões existentes após o script principal carregar
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOMContentLoaded da página edit disparado');
    console.log('questoesExistentes disponível:', typeof questoesExistentes !== 'undefined');
    console.log('Total de questões:', questoesExistentes ? questoesExistentes.length : 0);
    
    // Aguardar um pouco para garantir que o form builder está inicializado
    setTimeout(function() {
        console.log('Chamando carregarQuestoesExistentesGlobal...');
        if (typeof window.carregarQuestoesExistentesGlobal === 'function') {
            window.carregarQuestoesExistentesGlobal();
        } else {
            console.error('Função carregarQuestoesExistentesGlobal não encontrada!');
        }
    }, 500);
    
    // Configurar submit do formulário de edição
    const formEditar = document.getElementById('formEditarQuestionario');
    if (formEditar) {
        console.log('Formulário de edição encontrado - configurando submit');
        
        formEditar.addEventListener('submit', function(e) {
            console.log('Submit do formulário de edição disparado');
            
            const questoes = document.querySelectorAll('.questao-card');
            console.log('Total de questões ao submeter:', questoes.length);
            
            // Validação básica - pelo menos 1 questão
            if (questoes.length === 0) {
                e.preventDefault();
                alert('Adicione pelo menos uma questão ao questionário!');
                return false;
            }
            
            // Confirmar salvamento
            const confirmacao = confirm(
                `Salvar alterações do modelo com ${questoes.length} questão(ões)?`
            );
            
            if (!confirmacao) {
                console.log('Usuário cancelou o salvamento');
                e.preventDefault();
                return false;
            }
            
            console.log('Formulário será enviado...');
            return true;
        });
    } else {
        console.error('Formulário de edição NÃO encontrado!');
    }
});
</script>

