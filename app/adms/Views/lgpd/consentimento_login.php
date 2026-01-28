<?php
use App\adms\Helpers\CSRFHelper;
?>

<div class="col-lg-6 offset-lg-3">
    <div class="card shadow-lg border-0 rounded-lg mt-5">
        <div class="card-header bg-success text-white">
            <h3 class="text-center font-weight-light my-2">Uso de Dados Pessoais (LGPD)</h3>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <?php if (!empty($this->data['term_content'])): ?>
                <div class="small mb-3" style="max-height: 320px; overflow-y: auto; white-space: pre-wrap;">
                    <?php echo $this->data['term_content']; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-warning small mb-3">
                    Termo LGPD para login não está configurado com conteúdo. 
                    Cadastre o texto completo em <strong>LGPD &gt; Termos LGPD</strong> (tipo <code>login</code>).
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo $_ENV['URL_ADM']; ?>lgpd-consentimento-login/store">
                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_lgpd_consent_login'); ?>">

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="lgpd_consent" id="lgpd_consent" value="1">
                    <label class="form-check-label" for="lgpd_consent">
                        Declaro que <strong>li e concordo</strong> com o uso dos meus dados pessoais para as finalidades acima,
                        nos termos da LGPD (Versão do termo: <?php echo htmlspecialchars($this->data['term_version'] ?? '1.0'); ?>).
                    </label>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-consentimento-login/recusar" class="btn btn-outline-secondary">
                        Não concordo
                    </a>
                    <button type="submit" class="btn btn-success">
                        Aceitar e continuar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


