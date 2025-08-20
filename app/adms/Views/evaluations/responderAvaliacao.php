<?php
// View: Responder Avaliação/Questionário do Colaborador
$pergunta = $this->data['pergunta'] ?? [];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Responder Avaliação</h2>
    </div>
    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-edit me-2"></i><?= htmlspecialchars($pergunta['pergunta'] ?? 'Pergunta') ?></h5>
        </div>
        <div class="card-body">
            <?php if (!empty($this->data['error'])): ?>
                <div class="alert alert-danger mb-3"> <?= htmlspecialchars($this->data['error']) ?> </div>
            <?php endif; ?>
            <form method="post">
                <input type="hidden" name="modelo_id" value="<?= htmlspecialchars($this->data['modelo_id']) ?>">
                <input type="hidden" name="pergunta_id" value="<?= htmlspecialchars($this->data['pergunta_id']) ?>">
                <?php if (($pergunta['tipo'] ?? '') === 'multipla_escolha' && !empty($pergunta['opcoes'])): ?>
                    <?php $opcoes = array_map('trim', explode(';', $pergunta['opcoes'])); ?>
                    <?php foreach ($opcoes as $i => $opcao): ?>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="resposta" id="opcao<?= $i ?>" value="<?= htmlspecialchars($opcao) ?>" required>
                            <label class="form-check-label" for="opcao<?= $i ?>"> <?= htmlspecialchars($opcao) ?> </label>
                        </div>
                    <?php endforeach; ?>
                <?php elseif (($pergunta['tipo'] ?? '') === 'verdadeiro_falso'): ?>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="resposta" id="verdadeiro" value="Verdadeiro" required>
                        <label class="form-check-label" for="verdadeiro">Verdadeiro</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="resposta" id="falso" value="Falso" required>
                        <label class="form-check-label" for="falso">Falso</label>
                    </div>
                <?php else: ?>
                    <div class="mb-3">
                        <textarea name="resposta" class="form-control" rows="4" required placeholder="Digite sua resposta..."></textarea>
                    </div>
                <?php endif; ?>
                <button type="submit" class="btn btn-success"><i class="fas fa-paper-plane me-2"></i>Enviar Resposta</button>
                <a href="<?= $_ENV['URL_ADM'] ?>minhas-avaliacoes" class="btn btn-secondary ms-2">Voltar</a>
            </form>
        </div>
    </div>
</div> 