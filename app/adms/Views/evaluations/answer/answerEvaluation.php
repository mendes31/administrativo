<!-- CSS Moderno para Formulários de Avaliação -->
<link rel="stylesheet" href="<?= $_ENV['URL_ADM'] ?>public/adms/css/evaluation-forms-modern.css">

<?php
$assignment = $this->data['assignment'];
$questoes = $this->data['questoes'];
$totalPontos = $this->data['total_pontos'];
?>

<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">
            <i class="fas fa-edit"></i> <?= htmlspecialchars($assignment['model_titulo'] ?? 'Avaliação') ?>
        </h2>
        <ol class="breadcrumb mb-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?= $_ENV['URL_ADM'] ?>my-evaluations" class="text-decoration-none">Minhas Avaliações</a>
            </li>
            <li class="breadcrumb-item active">Responder</li>
        </ol>
    </div>

    <!-- INFORMAÇÕES DO QUESTIONÁRIO -->
    <div class="card mb-4 border-primary shadow">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-info-circle"></i> Informações da Avaliação</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <?php if (!empty($assignment['descricao'])): ?>
                        <div class="alert alert-info mb-3">
                            <strong><i class="fas fa-book-reader"></i> Instruções:</strong><br>
                            <?= nl2br(htmlspecialchars($assignment['descricao'])) ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2"><i class="fas fa-graduation-cap"></i> <strong>Treinamento:</strong> 
                                <?= htmlspecialchars($assignment['training_name'] ?? 'N/A') ?>
                            </p>
                            <p class="mb-2"><i class="fas fa-list-ol"></i> <strong>Total de Questões:</strong> 
                                <?= count($questoes) ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><i class="fas fa-star"></i> <strong>Total de Pontos:</strong> 
                                <?= number_format($totalPontos, 2) ?>
                            </p>
                            <p class="mb-2"><i class="fas fa-check-circle"></i> <strong>Nota Mínima:</strong> 
                                <span class="badge bg-warning text-dark"><?= $assignment['nota_minima_aprovacao'] ?></span>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <?php if ($assignment['data_limite']): ?>
                        <div class="alert alert-warning mb-2">
                            <i class="fas fa-calendar-alt"></i> <strong>Prazo:</strong><br>
                            <span class="fs-5"><?= date('d/m/Y', strtotime($assignment['data_limite'])) ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-redo"></i> <strong>Tentativa:</strong> 
                        <?= $assignment['tentativas'] + 1 ?>
                        <?php if ($assignment['max_tentativas']): ?>
                            / <?= $assignment['max_tentativas'] ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FORMULÁRIO DE RESPOSTAS -->
    <form id="formResposta" method="POST" onsubmit="return confirmarEnvio()">
        
        <?php foreach ($questoes as $index => $questao): ?>
            <div class="card mb-4 questao-item" id="questao-<?= $questao['id'] ?>">
                <div class="card-header bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <span class="badge bg-primary me-2"><?= $index + 1 ?></span>
                            <?= htmlspecialchars($questao['pergunta']) ?>
                        </h5>
                        <span class="badge bg-secondary"><?= number_format($questao['pontos'] ?? 1.00, 2) ?> pts</span>
                    </div>
                </div>
                <div class="card-body">
                    
                    <?php if ($questao['tipo'] === 'multipla_escolha'): ?>
                        <!-- MÚLTIPLA ESCOLHA -->
                        <?php foreach ($questao['alternativas'] as $altIndex => $alternativa): ?>
                            <div class="form-check mb-2 p-3 border rounded alternativa-opcao">
                                <input class="form-check-input" type="radio" 
                                       name="respostas[<?= $questao['id'] ?>]" 
                                       id="q<?= $questao['id'] ?>_<?= $altIndex ?>"
                                       value="<?= htmlspecialchars(trim($alternativa)) ?>" 
                                       required>
                                <label class="form-check-label w-100" for="q<?= $questao['id'] ?>_<?= $altIndex ?>">
                                    <?= htmlspecialchars(trim($alternativa)) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                        
                    <?php elseif ($questao['tipo'] === 'verdadeiro_falso'): ?>
                        <!-- VERDADEIRO OU FALSO -->
                        <div class="form-check mb-3 p-3 border rounded alternativa-opcao">
                            <input class="form-check-input" type="radio" 
                                   name="respostas[<?= $questao['id'] ?>]" 
                                   id="q<?= $questao['id'] ?>_v"
                                   value="Verdadeiro" required>
                            <label class="form-check-label w-100" for="q<?= $questao['id'] ?>_v">
                                <i class="fas fa-check-circle text-success"></i> Verdadeiro
                            </label>
                        </div>
                        <div class="form-check mb-3 p-3 border rounded alternativa-opcao">
                            <input class="form-check-input" type="radio" 
                                   name="respostas[<?= $questao['id'] ?>]" 
                                   id="q<?= $questao['id'] ?>_f"
                                   value="Falso" required>
                            <label class="form-check-label w-100" for="q<?= $questao['id'] ?>_f">
                                <i class="fas fa-times-circle text-danger"></i> Falso
                            </label>
                        </div>
                        
                    <?php elseif ($questao['tipo'] === 'texto'): ?>
                        <!-- TEXTO LIVRE -->
                        <textarea name="respostas[<?= $questao['id'] ?>]" 
                                  class="form-control" rows="5" 
                                  placeholder="Digite sua resposta detalhada..." required></textarea>
                        <small class="text-muted">Mínimo 10 caracteres</small>
                    
                    <?php else: ?>
                        <!-- NUMÉRICA -->
                        <input type="number" name="respostas[<?= $questao['id'] ?>]" 
                               class="form-control" step="any" 
                               placeholder="Digite o número" required>
                    <?php endif; ?>
                    
                </div>
            </div>
        <?php endforeach; ?>

        <!-- BOTÕES DE AÇÃO -->
        <div class="card border-success shadow mb-4">
            <div class="card-body text-center bg-light">
                <h5 class="mb-3">Pronto para enviar suas respostas?</h5>
                <p class="text-muted mb-3">
                    Revise suas respostas antes de enviar. 
                    <?php if ($assignment['permitir_refazer']): ?>
                        Você poderá refazer esta avaliação se necessário.
                    <?php else: ?>
                        <strong>Atenção:</strong> Você não poderá refazer esta avaliação!
                    <?php endif; ?>
                </p>
                <button type="submit" class="btn btn-success btn-lg me-2">
                    <i class="fas fa-paper-plane"></i> Enviar Respostas
                </button>
                <a href="<?= $_ENV['URL_ADM'] ?>my-evaluations" class="btn btn-secondary btn-lg">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </div>

    </form>
</div>

<style>
.alternativa-opcao {
    cursor: pointer;
    transition: all 0.2s ease;
}

.alternativa-opcao:hover {
    background-color: #f8f9fa;
    border-color: #007bff !important;
}

.alternativa-opcao:has(input:checked) {
    background-color: #e7f3ff;
    border-color: #007bff !important;
    border-width: 2px;
}

.form-check-label {
    cursor: pointer;
}
</style>

<script>
let totalQuestoes = <?= count($questoes) ?>;
let questoesRespondidas = 0;

// Contar questões respondidas
function atualizarProgresso() {
    let respondidas = 0;
    
    document.querySelectorAll('.questao-item').forEach(card => {
        const radio = card.querySelector('input[type="radio"]:checked');
        const textarea = card.querySelector('textarea');
        const number = card.querySelector('input[type="number"]');
        
        if (radio || (textarea && textarea.value.trim()) || (number && number.value)) {
            respondidas++;
        }
    });
    
    questoesRespondidas = respondidas;
    console.log(`Progresso: ${respondidas}/${totalQuestoes} questões respondidas`);
}

// Atualizar ao mudar resposta
document.querySelectorAll('input[type="radio"], textarea, input[type="number"]').forEach(input => {
    input.addEventListener('change', atualizarProgresso);
    input.addEventListener('input', atualizarProgresso);
});

function confirmarEnvio() {
    atualizarProgresso();
    
    if (questoesRespondidas < totalQuestoes) {
        const faltam = totalQuestoes - questoesRespondidas;
        return confirm(
            `Você respondeu ${questoesRespondidas} de ${totalQuestoes} questões.\n\n` +
            `Faltam ${faltam} questão(ões).\n\n` +
            `Questões não respondidas serão consideradas ERRADAS.\n\n` +
            `Deseja enviar mesmo assim?`
        );
    }
    
    return confirm(
        `Você está prestes a enviar suas respostas.\n\n` +
        `Total de questões: ${totalQuestoes}\n` +
        `Todas respondidas: SIM ✓\n\n` +
        `Confirmar envio?`
    );
}

// Aviso ao sair da página
window.addEventListener('beforeunload', function (e) {
    e.preventDefault();
    e.returnValue = 'Você tem uma avaliação em andamento. Deseja realmente sair?';
});

// Remover aviso ao submeter
document.getElementById('formResposta').addEventListener('submit', function() {
    window.onbeforeunload = null;
});

// Auto-save de progresso (opcional - salvar em localStorage)
function salvarProgresso() {
    const respostas = {};
    
    document.querySelectorAll('input[type="radio"]:checked').forEach(input => {
        respostas[input.name] = input.value;
    });
    
    document.querySelectorAll('textarea, input[type="number"]').forEach(input => {
        if (input.value) {
            respostas[input.name] = input.value;
        }
    });
    
    localStorage.setItem('avaliacao_progresso_<?= $assignment['id'] ?>', JSON.stringify(respostas));
}

// Salvar a cada 30 segundos
setInterval(salvarProgresso, 30000);
</script>

