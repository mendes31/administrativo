<?php
namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdConsentimentosRepository;
use App\adms\Views\Services\LoadViewService;
use Exception;

class LgpdConsentimentoColeta {
    private array $data = [];
    private LgpdConsentimentosRepository $consentimentosRepo;
    
    public function __construct() {
        // Iniciar sessão para páginas públicas
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->consentimentosRepo = new LgpdConsentimentosRepository();
    }
    
    /**
     * Método padrão - redireciona para coletar
     */
    public function index(): void {
        $this->coletar();
    }
    
    /**
     * Página pública para coleta de consentimento
     */
    public function coletar(): void {
        // Buscar dados do token se existir
        $dadosPreenchidos = [];
        if (isset($_GET['token'])) {
            $dadosPreenchidos = $this->buscarDadosPorToken($_GET['token']);
        }
        
        // Gerar CSRF token
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        // Para páginas públicas, não usar PageLayoutService
        $this->data = [
            'title_head' => 'Coleta de Consentimento - LGPD',
            'menu' => 'LgpdConsentimentoColeta',
            'buttonPermission' => [],
            'menuPermission' => [],
            'dados_preenchidos' => $dadosPreenchidos,
            'token' => $_GET['token'] ?? ''
        ];
        
        // Para páginas públicas, carregar diretamente sem layout administrativo
        $viewPath = './app/adms/Views/lgpd/consentimento-coleta/form.php';
        if (file_exists($viewPath)) {
            // Passar dados para a view
            $dados = $this->data;
            include $viewPath;
        } else {
            die("Erro: Página não encontrada. Entre em contato com o administrador.");
        }
    }
    
    /**
     * Processa o consentimento coletado
     */
    public function processar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'lgpd-consentimento-coleta');
            exit;
        }
        
        // Validar CSRF token
        if (!isset($_POST['csrf_token']) || !$this->validarCSRFToken($_POST['csrf_token'])) {
            $this->retornarErro('Token de segurança inválido');
            return;
        }
        
        // Validar dados obrigatórios
        $dados = $this->validarDados($_POST);
        if (!$dados) {
            return;
        }
        
        // Salvar consentimento
        try {
            $sucesso = $this->consentimentosRepo->create([
                'titular_nome' => $dados['nome'],
                'titular_email' => $dados['email'],
                'finalidade' => $dados['finalidade'],
                'canal' => 'formulario_web',
                'data_consentimento' => date('Y-m-d H:i:s'),
                'status' => 'ativo',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'politica_privacidade_aceita' => true,
                'termos_uso_aceitos' => $dados['termos_aceitos'] ?? false
            ]);
            
            if ($sucesso) {
                $this->retornarSucesso('Consentimento registrado com sucesso!');
            } else {
                $this->retornarErro('Erro ao registrar consentimento');
            }
            
        } catch (Exception $e) {
            $this->retornarErro('Erro interno: ' . $e->getMessage());
        }
    }
    
    /**
     * API para coleta via JavaScript/AJAX
     */
    public function apiColetar(): void {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['erro' => 'Método não permitido']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            http_response_code(400);
            echo json_encode(['erro' => 'Dados inválidos']);
            return;
        }
        
        // Validar dados
        $dados = $this->validarDados($input);
        if (!$dados) {
            http_response_code(400);
            echo json_encode(['erro' => 'Dados obrigatórios não fornecidos']);
            return;
        }
        
        // Salvar consentimento
        try {
            $sucesso = $this->consentimentosRepo->create([
                'titular_nome' => $dados['nome'],
                'titular_email' => $dados['email'],
                'finalidade' => $dados['finalidade'],
                'canal' => 'api_web',
                'data_consentimento' => date('Y-m-d H:i:s'),
                'status' => 'ativo',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'politica_privacidade_aceita' => true,
                'termos_uso_aceitos' => $dados['termos_aceitos'] ?? false
            ]);
            
            if ($sucesso) {
                echo json_encode(['sucesso' => true, 'mensagem' => 'Consentimento registrado']);
            } else {
                http_response_code(500);
                echo json_encode(['erro' => 'Erro ao registrar consentimento']);
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro interno: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Widget JavaScript para coleta
     */
    public function widget(): void {
        header('Content-Type: application/javascript');
        
        $widgetCode = "
        // Widget de Consentimento LGPD
        (function() {
            'use strict';
            
            // Configurações do widget
            const config = {
                apiUrl: '" . $_ENV['URL_ADM'] . "lgpd-consentimento-coleta/api-coletar',
                politicaUrl: '" . $_ENV['URL_ADM'] . "politica-privacidade',
                termosUrl: '" . $_ENV['URL_ADM'] . "termos-uso'
            };
            
            // Criar banner de consentimento
            function criarBanner() {
                const banner = document.createElement('div');
                banner.id = 'lgpd-consent-banner';
                banner.innerHTML = `
                    <div style='
                        position: fixed;
                        bottom: 0;
                        left: 0;
                        right: 0;
                        background: #2c3e50;
                        color: white;
                        padding: 20px;
                        z-index: 9999;
                        box-shadow: 0 -2px 10px rgba(0,0,0,0.3);
                    '>
                        <div style='max-width: 1200px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between;'>
                            <div style='flex: 1;'>
                                <p style='margin: 0 0 10px 0; font-size: 14px;'>
                                    Utilizamos cookies e dados pessoais para melhorar sua experiência. 
                                    Ao continuar navegando, você concorda com nossa 
                                    <a href=\"\${config.politicaUrl}\" target=\"_blank\" style='color: #3498db;'>Política de Privacidade</a>.
                                </p>
                            </div>
                            <div style='display: flex; gap: 10px;'>
                                <button id='lgpd-reject' style='
                                    background: transparent;
                                    border: 1px solid #fff;
                                    color: white;
                                    padding: 8px 16px;
                                    cursor: pointer;
                                    border-radius: 4px;
                                '>Rejeitar</button>
                                <button id='lgpd-accept' style='
                                    background: #27ae60;
                                    border: none;
                                    color: white;
                                    padding: 8px 16px;
                                    cursor: pointer;
                                    border-radius: 4px;
                                '>Aceitar</button>
                            </div>
                        </div>
                    </div>
                `;
                
                document.body.appendChild(banner);
                
                // Event listeners
                document.getElementById('lgpd-accept').addEventListener('click', aceitarConsentimento);
                document.getElementById('lgpd-reject').addEventListener('click', rejeitarConsentimento);
            }
            
            // Função para aceitar consentimento
            async function aceitarConsentimento() {
                try {
                    const response = await fetch(config.apiUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            nome: 'Usuário Web',
                            email: 'usuario@web.com',
                            finalidade: 'Melhorar experiência do usuário',
                            termos_aceitos: true
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.sucesso) {
                        // Salvar no localStorage
                        localStorage.setItem('lgpd_consent', 'aceito');
                        localStorage.setItem('lgpd_consent_date', new Date().toISOString());
                        
                        // Remover banner
                        const banner = document.getElementById('lgpd-consent-banner');
                        if (banner) {
                            banner.remove();
                        }
                    }
                } catch (error) {
                    console.error('Erro ao registrar consentimento:', error);
                }
            }
            
            // Função para rejeitar consentimento
            function rejeitarConsentimento() {
                localStorage.setItem('lgpd_consent', 'rejeitado');
                localStorage.setItem('lgpd_consent_date', new Date().toISOString());
                
                const banner = document.getElementById('lgpd-consent-banner');
                if (banner) {
                    banner.remove();
                }
            }
            
            // Verificar se já existe consentimento
            function verificarConsentimento() {
                const consent = localStorage.getItem('lgpd_consent');
                const consentDate = localStorage.getItem('lgpd_consent_date');
                
                if (!consent || !consentDate) {
                    // Não há consentimento, mostrar banner
                    criarBanner();
                } else {
                    // Verificar se o consentimento não expirou (1 ano)
                    const consentTime = new Date(consentDate).getTime();
                    const now = new Date().getTime();
                    const oneYear = 365 * 24 * 60 * 60 * 1000;
                    
                    if (now - consentTime > oneYear) {
                        // Consentimento expirado, mostrar banner novamente
                        localStorage.removeItem('lgpd_consent');
                        localStorage.removeItem('lgpd_consent_date');
                        criarBanner();
                    }
                }
            }
            
            // Inicializar quando o DOM estiver pronto
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', verificarConsentimento);
            } else {
                verificarConsentimento();
            }
            
            // Expor funções globalmente
            window.LGPDWidget = {
                aceitar: aceitarConsentimento,
                rejeitar: rejeitarConsentimento,
                verificar: verificarConsentimento
            };
            
        })();
        ";
        
        echo $widgetCode;
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
            $this->retornarErro(implode(', ', $erros));
            return null;
        }
        
        return [
            'nome' => trim($dados['nome']),
            'email' => trim($dados['email']),
            'finalidade' => trim($dados['finalidade']),
            'termos_aceitos' => isset($dados['termos_aceitos']) && $dados['termos_aceitos']
        ];
    }
    
    /**
     * Buscar dados por token
     */
    private function buscarDadosPorToken(string $token): array {
        // Buscar dados temporários salvos na sessão
        if (isset($_SESSION['consentimento_temp'][$token])) {
            $dados = $_SESSION['consentimento_temp'][$token];
            
            // Verificar se não expirou (7 dias)
            $expirado = strtotime($dados['expirado_em']) < time();
            if ($expirado) {
                unset($_SESSION['consentimento_temp'][$token]);
                return [];
            }
            
            return [
                'nome' => $dados['nome'] ?? '',
                'email' => $dados['email'] ?? '',
                'finalidade' => $dados['finalidade'] ?? ''
            ];
        }
        
        return [];
    }
    
    /**
     * Validar CSRF token
     */
    private function validarCSRFToken(string $token): bool {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Retornar erro
     */
    private function retornarErro(string $mensagem): void {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode(['erro' => $mensagem]);
        } else {
            $_SESSION['erro'] = $mensagem;
            header('Location: ' . $_ENV['URL_ADM'] . 'lgpd-consentimento-coleta');
        }
        exit;
    }
    
    /**
     * Retornar sucesso
     */
    private function retornarSucesso(string $mensagem): void {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => true, 'mensagem' => $mensagem]);
        } else {
            $_SESSION['sucesso'] = $mensagem;
            header('Location: ' . $_ENV['URL_ADM'] . 'lgpd-consentimento-coleta');
        }
        exit;
    }
}
