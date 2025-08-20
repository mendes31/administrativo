<?php
namespace App\adms\Controllers\lgpd;

use App\adms\Models\Repository\LgpdConsentimentosRepository;
use App\adms\Helpers\SendEmailService;
use Exception;

class LgpdConsentimentoEmailProcessar {
    private LgpdConsentimentosRepository $consentimentosRepo;
    
    public function __construct() {
        $this->consentimentosRepo = new LgpdConsentimentosRepository();
    }
    
    public function index(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'lgpd-consentimento-email');
            exit;
        }
        
        // Validar dados
        $dados = $this->validarDados($_POST);
        if (!$dados) {
            header('Location: ' . $_ENV['URL_ADM'] . 'lgpd-consentimento-email');
            exit;
        }
        
        try {
            // Gerar link único para o formulário
            $token = $this->gerarTokenUnico();
            $linkFormulario = $_ENV['URL_ADM'] . 'lgpd-consentimento-coleta?token=' . $token;
            
            // Salvar dados temporários
            $this->salvarDadosTemporarios($token, $dados);
            
            // Enviar e-mail
            $sucesso = $this->enviarEmail($dados['email'], $dados['nome'], $linkFormulario);
            
            if ($sucesso) {
                $_SESSION['sucesso'] = 'Formulário de consentimento enviado com sucesso para ' . $dados['email'];
            } else {
                $_SESSION['erro'] = 'Erro ao enviar e-mail. Tente novamente.';
            }
            
        } catch (Exception $e) {
            $_SESSION['erro'] = 'Erro interno: ' . $e->getMessage();
        }
        
        header('Location: ' . $_ENV['URL_ADM'] . 'lgpd-consentimento-email');
        exit;
    }
    
    /**
     * Enviar e-mail com formulário
     */
    private function enviarEmail(string $email, string $nome, string $linkFormulario): bool {
        $assunto = 'Formulário de Consentimento LGPD - ' . $_ENV['APP_NAME'];
        
        $corpo = $this->gerarCorpoEmail($nome, $linkFormulario);
        $corpoTexto = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $corpo));
        
        // Usar o serviço de e-mail configurado do sistema
        return SendEmailService::sendEmail($email, $nome, $assunto, $corpo, $corpoTexto);
    }
    
    /**
     * Gerar corpo do e-mail
     */
    private function gerarCorpoEmail(string $nome, string $linkFormulario): string {
        return "
        <!DOCTYPE html>
        <html lang='pt-BR'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Formulário de Consentimento LGPD</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2c3e50; color: white; padding: 20px; text-align: center; }
                .content { padding: 30px; background: #f9f9f9; }
                .button { display: inline-block; background: #3498db; color: white; padding: 15px 30px; 
                         text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { background: #ecf0f1; padding: 20px; text-align: center; font-size: 12px; }
                .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔒 Formulário de Consentimento LGPD</h1>
                    <p>" . $_ENV['APP_NAME'] . "</p>
                </div>
                
                <div class='content'>
                    <h2>Olá, {$nome}!</h2>
                    
                    <p>Você recebeu este e-mail porque precisamos do seu consentimento para o tratamento de dados pessoais, conforme exigido pela Lei Geral de Proteção de Dados (LGPD).</p>
                    
                    <div class='warning'>
                        <strong>⚠️ Importante:</strong> Este link é válido por 7 dias e é de uso exclusivo para você.
                    </div>
                    
                    <p>Para prosseguir, clique no botão abaixo e preencha o formulário de consentimento:</p>
                    
                    <div style='text-align: center;'>
                        <a href='{$linkFormulario}' class='button'>
                            📝 Preencher Formulário de Consentimento
                        </a>
                    </div>
                    
                    <p><strong>O que você precisa fazer:</strong></p>
                    <ol>
                        <li>Clique no botão acima</li>
                        <li>Preencha seus dados pessoais</li>
                        <li>Selecione as finalidades do tratamento</li>
                        <li>Leia e aceite a Política de Privacidade</li>
                        <li>Clique em 'Autorizar Tratamento de Dados'</li>
                    </ol>
                    
                    <p><strong>Seus direitos:</strong></p>
                    <ul>
                        <li>Você pode revogar o consentimento a qualquer momento</li>
                        <li>Você tem acesso aos seus dados pessoais</li>
                        <li>Você pode solicitar a exclusão dos seus dados</li>
                        <li>Você pode solicitar a portabilidade dos dados</li>
                    </ul>
                    
                    <p>Se você não conseguir acessar o link, copie e cole esta URL no seu navegador:</p>
                    <p style='word-break: break-all; background: #f8f9fa; padding: 10px; border-radius: 5px;'>
                        {$linkFormulario}
                    </p>
                </div>
                
                <div class='footer'>
                    <p>Este e-mail foi enviado por " . $_ENV['APP_NAME'] . "</p>
                    <p>Para dúvidas, entre em contato: lgpd@" . ($_ENV['MAIL_DOMAIN'] ?? 'empresa.com') . "</p>
                    <p>© " . date('Y') . " " . $_ENV['APP_NAME'] . " - Todos os direitos reservados</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Gerar token único
     */
    private function gerarTokenUnico(): string {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Salvar dados temporários
     */
    private function salvarDadosTemporarios(string $token, array $dados): void {
        // Aqui você pode salvar em uma tabela temporária ou cache
        // Por simplicidade, vou usar sessão
        $_SESSION['consentimento_temp'][$token] = [
            'email' => $dados['email'],
            'nome' => $dados['nome'],
            'finalidade' => $dados['finalidade'],
            'criado_em' => date('Y-m-d H:i:s'),
            'expirado_em' => date('Y-m-d H:i:s', strtotime('+7 days'))
        ];
    }
    
    /**
     * Validar dados do formulário
     */
    private function validarDados(array $dados): ?array {
        $erros = [];
        
        if (empty($dados['nome'])) {
            $erros[] = 'Nome é obrigatório';
        }
        
        if (empty($dados['email']) || !filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
            $erros[] = 'E-mail válido é obrigatório';
        }
        
        if (empty($dados['finalidade'])) {
            $erros[] = 'Finalidade é obrigatória';
        }
        
        if (!empty($erros)) {
            $_SESSION['erro'] = implode(', ', $erros);
            return null;
        }
        
        return [
            'nome' => trim($dados['nome']),
            'email' => trim($dados['email']),
            'finalidade' => trim($dados['finalidade'])
        ];
    }
}
