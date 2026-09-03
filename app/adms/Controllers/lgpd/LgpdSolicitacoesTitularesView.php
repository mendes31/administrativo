<?php

declare(strict_types=1);

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SendEmailService;
use App\adms\Models\Repository\LgpdSolicitacoesTitularesRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Models\Services\LgpdPublicConfig;
use App\adms\Models\Services\LgpdTitularRights;
use App\adms\Views\Services\LoadViewService;

class LgpdSolicitacoesTitularesView
{
    private const CSRF = 'lgpd_solicitacao_atender';

    /** @var array<string, array{label:string, status:?string, enviar:bool, subject:string}> */
    private const ACOES = [
        'guardar' => [
            'label' => 'Guardar atendimento',
            'status' => null,
            'enviar' => false,
            'subject' => '',
        ],
        'contatar' => [
            'label' => 'Contatar (confirmação de recebimento)',
            'status' => 'Em andamento',
            'enviar' => true,
            'subject' => 'Recebemos a sua requisição LGPD',
        ],
        'solicitar_info' => [
            'label' => 'Solicitar mais informações',
            'status' => 'Aguardando titular',
            'enviar' => true,
            'subject' => 'Precisamos de mais informações — requisição LGPD',
        ],
        'responder' => [
            'label' => 'Responder (parcial / intermediária)',
            'status' => 'Em andamento',
            'enviar' => true,
            'subject' => 'Resposta à sua requisição LGPD',
        ],
        'finalizar' => [
            'label' => 'Finalizar e enviar resposta',
            'status' => 'Concluída',
            'enviar' => true,
            'subject' => 'Conclusão do atendimento — requisição LGPD',
        ],
    ];

    private array $data = [];

    public function index(int|string $id = 0): void
    {
        $id = $this->resolveId((int) $id);
        $repo = new LgpdSolicitacoesTitularesRepository();
        $row = $repo->getById($id);
        if ($row === null) {
            $_SESSION['error'] = 'Solicitação não encontrada.';
            header('Location: ' . $_ENV['URL_ADM'] . 'lgpd-solicitacoes-titulares');
            exit;
        }

        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) === 'POST') {
            $this->salvar($id, $repo, $row);

            return;
        }

        $this->show($row, $repo);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function salvar(int $id, LgpdSolicitacoesTitularesRepository $repo, array $row): void
    {
        if (!CSRFHelper::validateCSRFToken(self::CSRF, (string) ($_POST['csrf_token'] ?? ''))) {
            $_SESSION['error'] = 'Sessão expirada. Tente novamente.';
            header('Location: ' . $_ENV['URL_ADM'] . 'lgpd-solicitacoes-titulares-view/' . $id);
            exit;
        }

        $acao = (string) ($_POST['acao'] ?? 'guardar');
        if (!isset(self::ACOES[$acao])) {
            $acao = 'guardar';
        }
        $meta = self::ACOES[$acao];
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $mensagem = trim((string) ($_POST['mensagem_email'] ?? ''));
        $observacao = trim((string) ($_POST['observacao_atendimento'] ?? ''));

        if ($meta['enviar'] && $mensagem === '') {
            $_SESSION['error'] = 'Informe a mensagem para enviar ao titular.';
            header('Location: ' . $_ENV['URL_ADM'] . 'lgpd-solicitacoes-titulares-view/' . $id);
            exit;
        }

        $status = $meta['status'] ?? (string) ($_POST['status'] ?? $row['status']);
        $payload = [
            'status' => $status,
            'prioridade' => $_POST['prioridade'] ?? $row['prioridade'],
            'responsavel' => $_POST['responsavel'] ?? ($row['responsavel'] ?? ''),
            'observacao_atendimento' => $observacao,
        ];

        $emailOk = true;
        $emailErro = null;
        $dest = $this->resolveDestinatario($row);

        if ($meta['enviar']) {
            if ($dest['email'] === '' || !filter_var($dest['email'], FILTER_VALIDATE_EMAIL)) {
                $_SESSION['error'] = 'Não há e-mail válido do titular/procurador para envio.';
                header('Location: ' . $_ENV['URL_ADM'] . 'lgpd-solicitacoes-titulares-view/' . $id);
                exit;
            }

            $protocolo = (string) ($row['protocolo'] ?? '');
            $empresa = LgpdPublicConfig::companyName();
            $assunto = '[' . $empresa . '] ' . $meta['subject'] . ' — ' . $protocolo;
            $html = $this->buildEmailHtml($row, $mensagem, $acao);
            $alt = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html));
            $from = $this->resolveRemetenteEncarregado();

            try {
                $emailOk = SendEmailService::sendEmail(
                    $dest['email'],
                    $dest['nome'],
                    $assunto,
                    $html,
                    $alt,
                    $from['email'],
                    $from['nome'],
                    $from['nome'],
                    $from['email'],
                    true
                );
                if (!$emailOk) {
                    $emailErro = 'Falha no envio SMTP (verifique Configuração de E-mail).';
                }
            } catch (\Throwable $e) {
                $emailOk = false;
                $emailErro = mb_substr($e->getMessage(), 0, 250);
            }

            $repo->addComunicacao($id, [
                'tipo' => $acao,
                'assunto' => $assunto,
                'mensagem' => $mensagem,
                'destinatario_email' => $dest['email'],
                'destinatario_nome' => $dest['nome'],
                'enviado' => $emailOk,
                'erro_envio' => $emailErro,
                'user_id' => $userId,
            ]);

            $stamp = date('d/m/Y H:i');
            $logLine = sprintf(
                '[%s] %s → %s (%s)%s',
                $stamp,
                self::ACOES[$acao]['label'],
                $dest['email'],
                $emailOk ? 'enviado' : 'falha',
                $emailErro ? ' — ' . $emailErro : ''
            );
            $payload['observacao_atendimento'] = trim($observacao . ($observacao !== '' ? "\n\n" : '') . $logLine);
        }

        $ok = $repo->updateAtendimento($id, $payload, $userId);

        if (!$ok) {
            $_SESSION['error'] = 'Não foi possível gravar o atendimento.';
        } elseif ($meta['enviar'] && !$emailOk) {
            $_SESSION['error'] = 'Atendimento gravado, mas o e-mail não foi enviado. '
                . ($emailErro ?? 'Verifique a configuração SMTP.');
        } elseif ($meta['enviar']) {
            $_SESSION['success'] = 'E-mail enviado e atendimento atualizado.';
        } else {
            $_SESSION['success'] = 'Atendimento atualizado.';
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'lgpd-solicitacoes-titulares-view/' . $id);
        exit;
    }

    /**
     * Remetente oficial do canal LGPD (From + Reply-To).
     *
     * @return array{email:string,nome:string}
     */
    private function resolveRemetenteEncarregado(): array
    {
        $email = trim(LgpdPublicConfig::dpoEmail());
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = 'encarregado@tiaraju.com.br';
        }
        $nome = trim(LgpdPublicConfig::dpoNome());
        if ($nome === '') {
            $nome = 'Encarregado de Proteção de Dados';
        }
        $empresa = LgpdPublicConfig::companyName();
        if ($empresa !== '') {
            $nome .= ' — ' . $empresa;
        }

        return ['email' => $email, 'nome' => $nome];
    }

    /**
     * @param array<string, mixed> $row
     * @return array{email:string,nome:string}
     */
    private function resolveDestinatario(array $row): array
    {
        if (!empty($row['por_procurador']) && !empty($row['procurador_email'])) {
            return [
                'email' => trim((string) $row['procurador_email']),
                'nome' => trim((string) ($row['procurador_nome'] ?? 'Procurador')) ?: 'Procurador',
            ];
        }

        return [
            'email' => trim((string) ($row['titular_email'] ?? '')),
            'nome' => trim((string) ($row['titular_nome'] ?? 'Titular')) ?: 'Titular',
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function buildEmailHtml(array $row, string $mensagem, string $acao): string
    {
        $empresa = htmlspecialchars(LgpdPublicConfig::companyName(), ENT_QUOTES, 'UTF-8');
        $protocolo = htmlspecialchars((string) ($row['protocolo'] ?? ''), ENT_QUOTES, 'UTF-8');
        $dpo = htmlspecialchars(LgpdPublicConfig::dpoNome() ?: 'Encarregado (DPO)', ENT_QUOTES, 'UTF-8');
        $body = nl2br(htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8'));

        $intro = match ($acao) {
            'contatar' => 'Confirmamos o recebimento da sua requisição de direitos do titular.',
            'solicitar_info' => 'Para dar continuidade ao atendimento, precisamos de informações adicionais.',
            'responder' => 'Segue resposta relacionada à sua requisição de direitos do titular.',
            'finalizar' => 'Informamos a conclusão do atendimento da sua requisição de direitos do titular.',
            default => 'Mensagem sobre a sua requisição LGPD.',
        };

        return '<p>Prezado(a),</p>'
            . '<p>' . $intro . '</p>'
            . '<p><strong>Protocolo:</strong> ' . $protocolo . '</p>'
            . '<div style="margin:1rem 0;padding:1rem;border-left:4px solid #00995D;background:#f7fbf9;">'
            . $body
            . '</div>'
            . '<p>Atenciosamente,<br>' . $dpo . '<br>' . $empresa . '<br>'
            . 'Canal LGPD / Encarregado de Proteção de Dados</p>';
    }

    /**
     * @param array<string, mixed> $row
     */
    private function show(array $row, LgpdSolicitacoesTitularesRepository $repo): void
    {
        $direitos = json_decode((string) ($row['direitos_json'] ?? ''), true);
        $dest = $this->resolveDestinatario($row);

        $this->data['solicitacao'] = $row;
        $this->data['direitos'] = is_array($direitos) ? $direitos : [];
        $this->data['catalogo'] = LgpdTitularRights::catalog();
        $this->data['csrf_token'] = CSRFHelper::generateCSRFToken(self::CSRF);
        $this->data['acoes'] = self::ACOES;
        $this->data['destinatario'] = $dest;
        $this->data['remetente'] = $this->resolveRemetenteEncarregado();
        $this->data['comunicacoes'] = $repo->listComunicacoes((int) $row['id']);
        $this->data['templates'] = $this->defaultTemplates($row);
        $this->data['log_resumo'] = LogResumoService::getResumo(
            'lgpd_solicitacoes_titulares',
            (int) $row['id'],
            $_ENV['URL_ADM'] . 'lgpd-solicitacoes-titulares-view/' . (int) $row['id']
        );

        $pageElements = [
            'title_head' => 'Solicitação ' . ($row['protocolo'] ?? ''),
            'menu' => 'lgpd-solicitacoes-titulares',
            'buttonPermission' => ['LgpdSolicitacoesTitulares', 'LgpdSolicitacoesTitularesView'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));

        (new LoadViewService('adms/Views/lgpd/solicitacoes/view', $this->data))->loadView();
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, string>
     */
    private function defaultTemplates(array $row): array
    {
        $protocolo = (string) ($row['protocolo'] ?? '');
        $nome = (string) ($row['titular_nome'] ?? '');

        return [
            'contatar' => "Olá, {$nome}.\n\n"
                . "Recebemos a sua requisição de direitos do titular (protocolo {$protocolo}). "
                . "O prazo legal de atendimento é de até 15 dias.\n\n"
                . 'Em breve retornaremos com o andamento.',
            'solicitar_info' => "Olá, {$nome}.\n\n"
                . "Sobre o protocolo {$protocolo}, precisamos de informações adicionais para concluir a análise:\n\n"
                . "- \n\n"
                . 'Aguardando o seu retorno para darmos continuidade.',
            'responder' => "Olá, {$nome}.\n\n"
                . "Em relação ao protocolo {$protocolo}, informamos o seguinte:\n\n"
                . '',
            'finalizar' => "Olá, {$nome}.\n\n"
                . "Concluímos o atendimento da requisição {$protocolo}.\n\n"
                . "Resumo do que foi realizado:\n\n"
                . "- \n\n"
                . 'Caso ainda tenha dúvidas, responda a este e-mail citando o protocolo.',
        ];
    }

    private function resolveId(int $id): int
    {
        if ($id > 0) {
            return $id;
        }
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        if (preg_match('~/lgpd-solicitacoes-titulares-view/(\d+)~', $uri, $m)) {
            return (int) $m[1];
        }

        return 0;
    }
}
