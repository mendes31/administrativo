<?php

declare(strict_types=1);

namespace App\adms\Controllers\lgpd;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SendEmailService;
use App\adms\Models\Repository\LgpdSolicitacoesTitularesRepository;
use App\adms\Models\Repository\LgpdTermosRepository;
use App\adms\Models\Services\LgpdPublicCaptchaService;
use App\adms\Models\Services\LgpdPublicConfig;
use App\adms\Models\Services\LgpdTitularRights;

/**
 * Portal público LGPD — sem login (landing, políticas, formulário Art. 18).
 * URL interna: {URL_ADM}lgpd | URL pública: URL_LGPD ou /lgpd na raiz (deploy/lgpd).
 */
final class LgpdPublico
{
    private const CSRF = 'lgpd_requisicao_titular';
    private const RL_SCOPE = 'lgpd_requisicao';

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function index(?string $slug = null): void
    {
        $slug = is_string($slug) ? trim($slug, '/') : '';
        if ($slug !== '' && !str_contains($slug, '/')) {
            $this->showPublicTerm($slug);

            return;
        }

        $docs = LgpdPublicConfig::documentPaths();
        $this->render('home', [
            'title' => 'LGPD — ' . LgpdPublicConfig::companyName(),
            'has_cartilha' => $docs['cartilha'] !== null,
            'has_carta' => $docs['carta'] !== null,
            'termos_publicos' => (new LgpdTermosRepository())->listPublicosAtivos(),
        ]);
    }

    private function showPublicTerm(string $slug): void
    {
        $repo = new LgpdTermosRepository();
        $termo = $repo->getPublicoAtivoPorSlug($slug);
        if ($termo === null) {
            http_response_code(404);
            $docs = LgpdPublicConfig::documentPaths();
            $this->render('home', [
                'title' => 'LGPD — ' . LgpdPublicConfig::companyName(),
                'error' => 'Documento não disponível no canal público.',
                'has_cartilha' => $docs['cartilha'] !== null,
                'has_carta' => $docs['carta'] !== null,
                'termos_publicos' => $repo->listPublicosAtivos(),
            ]);

            return;
        }

        $this->render('termo', [
            'title' => (string) ($termo['titulo'] ?? 'Documento'),
            'termo' => $termo,
            'has_termo' => !empty($termo['conteudo']),
            'empty_hint' => 'Documento sem conteúdo.',
        ]);
    }

    public function requisicao(): void
    {
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
            $this->enviar();

            return;
        }

        $this->renderForm();
    }

    public function enviar(): void
    {
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
            header('Location: ' . LgpdPublicConfig::url('requisicao'));
            exit;
        }

        $captcha = new LgpdPublicCaptchaService();
        $old = $_POST;

        if ($captcha->isBlocked(self::RL_SCOPE)) {
            $mins = max(1, (int) ceil($captcha->secondsUntilUnblock(self::RL_SCOPE) / 60));
            $this->renderForm($old, 'Muitos envios deste endereço. Aguarde cerca de ' . $mins . ' minuto(s).');

            return;
        }

        if ($captcha->honeypotFilled()) {
            $this->render('protocolo', [
                'title' => 'Requisição registrada',
                'protocolo' => 'LGPD-' . date('Ymd') . '-OK',
            ]);

            return;
        }

        if (!CSRFHelper::validateCSRFToken(self::CSRF, (string) ($_POST['csrf_token'] ?? ''))) {
            $this->renderForm($old, 'Sessão expirada. Atualize a página e tente novamente.');

            return;
        }

        if (!$captcha->verify((string) ($_POST['captcha'] ?? ''))) {
            $captcha->recordAttempt(self::RL_SCOPE);
            $this->renderForm($old, 'Confirme a verificação de texto (soma) antes de enviar.');

            return;
        }

        $captcha->recordAttempt(self::RL_SCOPE);

        $parsed = $this->parseForm($_POST);
        if ($parsed['error'] !== null) {
            $this->renderForm($old, $parsed['error']);

            return;
        }

        $result = (new LgpdSolicitacoesTitularesRepository())->createFromPublicForm($parsed['data']);
        if ($result === null) {
            $this->renderForm($old, 'Não foi possível registrar a requisição. Tente novamente em alguns minutos.');

            return;
        }

        $this->notifyDpo($result['protocolo'], $parsed['data']);

        $this->render('protocolo', [
            'title' => 'Requisição registrada',
            'protocolo' => $result['protocolo'],
            'email' => $parsed['data']['titular_email'],
        ]);
    }

    public function documento(): void
    {
        $tipo = strtolower(trim((string) ($_GET['tipo'] ?? '')));
        $docs = LgpdPublicConfig::documentPaths();
        $path = $docs[$tipo] ?? null;
        if ($path === null || !is_readable($path)) {
            http_response_code(404);
            $this->render('home', [
                'title' => 'Documento indisponível',
                'error' => 'Este documento ainda não foi publicado. Entre em contacto com o encarregado (DPO).',
                'has_cartilha' => $docs['cartilha'] !== null,
                'has_carta' => $docs['carta'] !== null,
            ]);

            return;
        }

        $name = $tipo === 'carta' ? 'carta-compromisso.pdf' : 'cartilha-lgpd.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $name . '"');
        header('Content-Length: ' . (string) filesize($path));
        readfile($path);
        exit;
    }

    /**
     * @param array<string, mixed> $old
     */
    private function renderForm(array $old = [], ?string $error = null): void
    {
        $captcha = new LgpdPublicCaptchaService();
        $this->render('form', [
            'title' => 'Requisição de Direitos do Titular',
            'csrf_token' => CSRFHelper::generateCSRFToken(self::CSRF),
            'captcha_question' => $captcha->generateQuestion(),
            'rights' => LgpdTitularRights::catalog(),
            'categorias' => LgpdTitularRights::categorias(),
            'old' => $old,
            'error' => $error,
            'empresa' => LgpdPublicConfig::companyName(),
        ]);
    }

    /**
     * @param array<string, mixed> $post
     * @return array{error:?string, data:array<string, mixed>}
     */
    private function parseForm(array $post): array
    {
        $nome = trim((string) ($post['titular_nome'] ?? ''));
        $email = trim((string) ($post['titular_email'] ?? ''));
        $cpf = preg_replace('/\D+/', '', (string) ($post['titular_cpf'] ?? '')) ?? '';
        $categoria = trim((string) ($post['titular_categoria'] ?? ''));
        $porProcurador = ((string) ($post['por_procurador'] ?? '')) === 'sim';
        $comunicacao = (string) ($post['comunicacao_meio'] ?? '');
        $direitosPost = is_array($post['direitos'] ?? null) ? $post['direitos'] : [];

        if ($nome === '' || mb_strlen($nome) < 3) {
            return ['error' => 'Informe o nome completo do titular.', 'data' => []];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['error' => 'Informe um e-mail de contacto válido.', 'data' => []];
        }
        if (strlen($cpf) !== 11) {
            return ['error' => 'Informe o CPF do titular com 11 dígitos.', 'data' => []];
        }
        if (!in_array($categoria, LgpdTitularRights::categorias(), true)) {
            return ['error' => 'Selecione a categoria do titular.', 'data' => []];
        }
        if ($categoria === 'Outro' && trim((string) ($post['titular_categoria_outro'] ?? '')) === '') {
            return ['error' => 'Descreva a categoria «Outro».', 'data' => []];
        }
        if (!in_array((string) ($post['por_procurador'] ?? ''), ['sim', 'nao'], true)) {
            return ['error' => 'Indique se a solicitação é por meio de procurador.', 'data' => []];
        }

        $procuradorNome = trim((string) ($post['procurador_nome'] ?? ''));
        $procuradorCpf = preg_replace('/\D+/', '', (string) ($post['procurador_cpf'] ?? '')) ?? '';
        $procuradorEmail = trim((string) ($post['procurador_email'] ?? ''));
        if ($porProcurador) {
            if ($procuradorNome === '' || strlen($procuradorCpf) !== 11 || !filter_var($procuradorEmail, FILTER_VALIDATE_EMAIL)) {
                return ['error' => 'Com procurador, informe nome, CPF e e-mail do procurador.', 'data' => []];
            }
        }

        $direitos = [];
        $algumSim = false;
        foreach (array_keys(LgpdTitularRights::catalog()) as $key) {
            $val = (string) ($direitosPost[$key] ?? 'nao');
            $direitos[$key] = $val === 'sim' ? 'sim' : 'nao';
            if ($direitos[$key] === 'sim') {
                $algumSim = true;
            }
        }
        if (!$algumSim) {
            return ['error' => 'Assinale ao menos um direito (Sim) previsto na LGPD.', 'data' => []];
        }

        if ($comunicacao !== 'email' && $comunicacao !== 'outro') {
            return ['error' => 'Indique como deseja ser comunicado do resultado.', 'data' => []];
        }
        if ($comunicacao === 'outro' && trim((string) ($post['comunicacao_outro'] ?? '')) === '') {
            return ['error' => 'Descreva o outro meio de comunicação.', 'data' => []];
        }

        $nasc = trim((string) ($post['titular_nascimento'] ?? ''));
        if ($nasc !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $nasc)) {
            $nasc = '';
        }

        return [
            'error' => null,
            'data' => [
                'titular_nome' => $nome,
                'titular_email' => $email,
                'titular_cpf' => $cpf,
                'titular_endereco' => trim((string) ($post['titular_endereco'] ?? '')),
                'titular_nascimento' => $nasc,
                'titular_telefone' => trim((string) ($post['titular_telefone'] ?? '')),
                'titular_categoria' => $categoria,
                'titular_categoria_outro' => trim((string) ($post['titular_categoria_outro'] ?? '')),
                'informacoes_adicionais' => trim((string) ($post['informacoes_adicionais'] ?? '')),
                'por_procurador' => $porProcurador,
                'procurador_nome' => $procuradorNome,
                'procurador_cpf' => $procuradorCpf,
                'procurador_email' => $procuradorEmail,
                'direitos' => $direitos,
                'comunicacao_meio' => $comunicacao,
                'comunicacao_outro' => trim((string) ($post['comunicacao_outro'] ?? '')),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function notifyDpo(string $protocolo, array $data): void
    {
        $to = LgpdPublicConfig::notifyEmail();
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $empresa = LgpdPublicConfig::companyName();
        $link = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/lgpd-solicitacoes-titulares';
        $html = '<p>Nova requisição de direitos do titular (LGPD).</p>'
            . '<p><strong>Protocolo:</strong> ' . htmlspecialchars($protocolo, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p><strong>Titular:</strong> ' . htmlspecialchars((string) $data['titular_nome'], ENT_QUOTES, 'UTF-8')
            . ' &lt;' . htmlspecialchars((string) $data['titular_email'], ENT_QUOTES, 'UTF-8') . '&gt;</p>'
            . '<p>Prazo legal de atendimento: 15 dias. Abrir no portal: '
            . '<a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '">'
            . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '</a></p>';

        try {
            SendEmailService::sendEmail(
                $to,
                LgpdPublicConfig::dpoNome() ?: 'DPO',
                "[{$empresa}] Nova requisição LGPD {$protocolo}",
                $html,
                "Nova requisição LGPD {$protocolo}. Titular: {$data['titular_nome']}. Prazo: 15 dias."
            );
        } catch (\Throwable $e) {
            error_log('Falha ao notificar DPO da requisição LGPD: ' . $e->getMessage());
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function render(string $viewName, array $data): void
    {
        $data['view'] = $viewName;
        $data['base_url'] = LgpdPublicConfig::url();
        $data['url_adm'] = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/';
        $data['empresa'] = $data['empresa'] ?? LgpdPublicConfig::companyName();
        $data['dpo_nome'] = LgpdPublicConfig::dpoNome();
        $data['dpo_email'] = LgpdPublicConfig::dpoEmail();
        $data['dpo_telefone'] = LgpdPublicConfig::dpoTelefone();
        $data['comite_titulo'] = LgpdPublicConfig::comiteTitulo();
        $data['comite_descricao'] = LgpdPublicConfig::comiteDescricao();
        $data['comite_membros'] = LgpdPublicConfig::comiteMembrosAtivos();
        $data['title'] = (string) ($data['title'] ?? 'LGPD');
        $data['logged_in'] = !empty($_SESSION['user_id']);
        $data['show_hero'] = ($viewName === 'home');

        $viewPath = dirname(__DIR__, 2) . '/Views/lgpd/publico/' . $viewName . '.php';
        if (!is_readable($viewPath)) {
            http_response_code(500);
            echo 'Página não encontrada.';

            return;
        }

        extract($data, EXTR_SKIP);
        include dirname(__DIR__, 2) . '/Views/lgpd/publico/layout.php';
    }

    /**
     * Política e termos no layout público (mesmo com sessão ativa — sem menu do portal).
     */
    public static function renderTermoPublico(string $title, ?array $termo, string $emptyHint): void
    {
        $self = new self();
        $self->render('termo', [
            'title' => $title,
            'termo' => $termo,
            'has_termo' => !empty($termo['conteudo']),
            'empty_hint' => $emptyHint,
        ]);
    }
}
