<?php

declare(strict_types=1);

namespace App\adms\Controllers\rh;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\LgpdTermosRepository;
use App\adms\Models\Repository\RhVagasRepository;
use App\adms\Models\Services\RhCandidaturaPublicaService;
use App\adms\Models\Services\RhVagasPublicasCaptchaService;
use App\adms\Models\Services\WhistleblowingRateLimitService;

/**
 * Portal público de vagas + candidatura (Expand Fase 3).
 * Sem login; só vagas publicada=1 + status aberta + dentro do prazo.
 */
final class RhVagasPublicas
{
    private const CSRF_FORM = 'form_vagas_abertas_candidatar';
    private const RL_SCOPE = 'rh_vagas_publicas_apply';

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function index(int|string $id = 0): void
    {
        $idInt = (int) $id;
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

        if ($idInt > 0 && $method === 'POST') {
            $this->candidatar($idInt);
            return;
        }

        if ($method !== 'GET' && $method !== 'HEAD') {
            http_response_code(405);
            header('Allow: GET, HEAD, POST');
            echo 'Método não permitido.';
            return;
        }

        if ($idInt > 0) {
            $this->show($idInt);
            return;
        }

        $this->list();
    }

    private function list(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $titulo = trim((string) ($_GET['q'] ?? ''));
        $repo = new RhVagasRepository();
        $result = $repo->listPublicadas(
            $titulo !== '' ? ['titulo' => $titulo] : [],
            $page,
            12
        );

        $this->render('list', [
            'title' => 'Vagas abertas',
            'vagas' => $result['data'],
            'total' => $result['total'],
            'page' => $page,
            'per_page' => 12,
            'q' => $titulo,
        ]);
    }

    private function show(int $id, array $form = [], ?string $flashError = null, ?string $flashSuccess = null): void
    {
        $vaga = (new RhVagasRepository())->getPublicadaById($id);
        if ($vaga === null) {
            http_response_code(404);
            $this->render('not_found', [
                'title' => 'Vaga não encontrada',
            ]);
            return;
        }

        $captcha = new RhVagasPublicasCaptchaService();
        $termo = (new LgpdTermosRepository())->getTermoAtivoPorTipo(
            RhCandidaturaPublicaService::LGPD_TERMO_TIPO
        );

        $this->render('view', [
            'title' => (string) ($vaga['titulo'] ?? 'Vaga'),
            'vaga' => $vaga,
            'form' => $form,
            'flash_error' => $flashError ?? ($_SESSION['portal_vagas_error'] ?? null),
            'flash_success' => $flashSuccess ?? ($_SESSION['portal_vagas_success'] ?? null),
            'csrf_token' => CSRFHelper::generateCSRFToken(self::CSRF_FORM),
            'lgpd_termo' => $termo,
            'captcha_enabled' => $captcha->isEnabled(),
            'captcha_site_key' => $captcha->getSiteKey(),
            'captcha_provider' => $captcha->getProvider(),
            'candidatura_habilitada' => $termo !== null,
        ]);
        unset($_SESSION['portal_vagas_error'], $_SESSION['portal_vagas_success']);
    }

    private function candidatar(int $vagaId): void
    {
        $form = $_POST['form'] ?? [];
        if (!is_array($form)) {
            $form = [];
        }
        $form['lgpd_consent'] = $_POST['lgpd_consent'] ?? '';
        $form['website'] = $_POST['website'] ?? '';

        $rateLimit = new WhistleblowingRateLimitService();
        if ($rateLimit->isBlocked(self::RL_SCOPE)) {
            $secs = $rateLimit->secondsUntilUnblock(self::RL_SCOPE);
            $this->show(
                $vagaId,
                $form,
                'Muitas tentativas. Aguarde '
                . max(1, (int) ceil($secs / 60))
                . ' minuto(s) e tente novamente.'
            );
            return;
        }

        $csrfToken = (string) ($_POST['csrf_token'] ?? '');
        if (!CSRFHelper::validateCSRFToken(self::CSRF_FORM, $csrfToken)) {
            $rateLimit->recordAttempt(self::RL_SCOPE);
            $this->show($vagaId, $form, 'Token de segurança inválido. Recarregue a página e tente novamente.');
            return;
        }

        $captcha = new RhVagasPublicasCaptchaService();
        if (!$captcha->verifyFromRequest()) {
            $rateLimit->recordAttempt(self::RL_SCOPE);
            $this->show($vagaId, $form, 'Confirme o CAPTCHA para continuar.');
            return;
        }

        $rateLimit->recordAttempt(self::RL_SCOPE);

        try {
            (new RhCandidaturaPublicaService())->candidatar($vagaId, $form);
            $_SESSION['portal_vagas_success'] = 'Candidatura registrada com sucesso. O RH poderá entrar em contato pelo e-mail informado.';
            header('Location: ' . rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/vagas-abertas/' . $vagaId);
            exit;
        } catch (\Throwable $e) {
            GenerateLog::generateLog('warning', 'Candidatura pública rejeitada ou falhou.', [
                'vaga_id' => $vagaId,
                'error' => $e->getMessage(),
            ]);
            $this->show($vagaId, $form, $e->getMessage());
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function render(string $view, array $data): void
    {
        $base = rtrim((string) ($_ENV['URL_ADM'] ?? '/'), '/') . '/';
        $data['base_url'] = $base . 'vagas-abertas';
        $data['url_adm'] = $base;
        $data['view'] = $view;

        extract($data, EXTR_SKIP);
        require dirname(__DIR__, 2) . '/Views/rh/public/layout.php';
    }
}
