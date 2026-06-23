<?php
use App\adms\Helpers\CSRFHelper;
$vinculo = $this->data['vinculo'] ?? null;
$csrfToken = CSRFHelper::generateCSRFToken('sst_apply_treinamento_form');
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <h2 class="mt-3"><i class="fas fa-check-circle me-2"></i>Aplicar Treinamento SST</h2>
    <div class="card shadow-sm mt-3">
        <div class="card-body">
            <form method="POST" action="<?= $_ENV['URL_ADM']; ?>sst-apply-treinamento">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <?php if ($vinculo): ?>
                    <input type="hidden" name="adms_sst_treinamento_vinculo_id" value="<?= (int)$vinculo['id'] ?>">
                    <input type="hidden" name="adms_user_id" value="<?= (int)$vinculo['adms_user_id'] ?>">
                    <input type="hidden" name="adms_sst_treinamento_id" value="<?= (int)$vinculo['adms_sst_treinamento_id'] ?>">
                    <div class="alert alert-info"><?= htmlspecialchars($vinculo['colaborador_nome'] ?? '') ?> — <?= htmlspecialchars($vinculo['treinamento_nome'] ?? '') ?></div>
                <?php else: ?>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Colaborador *</label>
                            <select name="adms_user_id" class="form-select" required><option value="">Selecione...</option>
                            <?php foreach ($this->data['users'] ?? [] as $u): ?><option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars($u['name'] ?? '') ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Treinamento *</label>
                            <select name="adms_sst_treinamento_id" class="form-select" required><option value="">Selecione...</option>
                            <?php foreach ($this->data['treinamentos'] ?? [] as $t): ?><option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['nome'] ?? '') ?></option><?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tipo de registro</label>
                        <select name="status_aplicacao" id="status_aplicacao" class="form-select">
                            <option value="concluido">Conclusão (realizado)</option>
                            <option value="agendado">Agendamento</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3" id="wrap-data-realizacao">
                        <label class="form-label">Data realização</label>
                        <input type="date" name="data_realizacao" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3 d-none" id="wrap-data-agendada">
                        <label class="form-label">Data agendada</label>
                        <input type="date" name="data_agendada" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Nota</label>
                        <input type="number" step="0.01" name="nota" class="form-control" min="0" max="10">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Modalidade aplicada</label>
                        <select name="modalidade_aplicada" class="form-select">
                            <option value="">Padrão do catálogo</option>
                            <option value="Presencial">Presencial</option>
                            <option value="EAD">EAD</option>
                            <option value="Hibrido">Híbrido</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Instrutor</label>
                        <input type="text" name="instrutor_nome" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Registro instrutor</label>
                        <input type="text" name="instrutor_registro" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Certificado / referência</label>
                        <input type="text" name="certificado" class="form-control">
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Observações</label>
                        <textarea name="observacoes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <button type="submit" class="btn btn-success">Salvar</button>
                <a href="<?= $_ENV['URL_ADM']; ?>sst-list-treinamento-vinculos" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>
<script>
(function () {
    const sel = document.getElementById('status_aplicacao');
    const wrapR = document.getElementById('wrap-data-realizacao');
    const wrapA = document.getElementById('wrap-data-agendada');
    function toggle() {
        const ag = sel?.value === 'agendado';
        wrapR?.classList.toggle('d-none', ag);
        wrapA?.classList.toggle('d-none', !ag);
    }
    sel?.addEventListener('change', toggle);
    toggle();
})();
</script>
