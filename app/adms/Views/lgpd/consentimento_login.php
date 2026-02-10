<?php
use App\adms\Helpers\CSRFHelper;
?>

<div class="d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="col-lg-6 col-md-8 col-sm-11">
        <div class="card shadow-lg border-0 rounded-lg">
            <div class="card-header bg-success text-white py-2">
                <h3 class="text-center font-weight-light my-1">Uso de Dados Pessoais (LGPD)</h3>
            </div>
            <div class="card-body py-3 px-4">
                <?php include './app/adms/Views/partials/alerts.php'; ?>

                <?php if (!empty($this->data['term_content'])): ?>
                    <div class="small mb-2 lgpd-term-content" style="max-height: 320px; overflow-y: auto; line-height: 1.3;">
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

                    <div class="form-check mb-2 small">
                        <input class="form-check-input" type="checkbox" name="lgpd_consent" id="lgpd_consent" value="1">
                        <label class="form-check-label" for="lgpd_consent">
                            Declaro que <strong>li e concordo</strong> com o uso dos meus dados pessoais para as finalidades acima,
                            nos termos da LGPD (Versão do termo: <?php echo htmlspecialchars($this->data['term_version'] ?? '1.0'); ?>).
                        </label>
                    </div>

                    <div class="d-flex justify-content-between mt-3">
                        <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-consentimento-login/recusar" class="btn btn-outline-secondary btn-sm">
                            Não concordo
                        </a>
                        <button type="submit" class="btn btn-success btn-sm">
                            Aceitar e continuar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    /* Ajustes finos de tipografia para o conteúdo do termo LGPD */
    .lgpd-term-content p {
        margin-bottom: 0.35rem;
    }
    .lgpd-term-content ul {
        margin-left: 1.4rem;
        margin-bottom: 0.35rem;
    }
    .lgpd-term-content li {
        margin-bottom: 0.15rem;
    }
    .lgpd-term-content h1,
    .lgpd-term-content h2,
    .lgpd-term-content h3,
    .lgpd-term-content h4 {
        margin-top: 0.75rem;
        margin-bottom: 0.35rem;
        font-weight: 600;
    }
</style>
