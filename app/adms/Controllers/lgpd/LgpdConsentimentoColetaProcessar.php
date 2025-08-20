<?php
namespace App\adms\Controllers\lgpd;

use App\adms\Models\Repository\LgpdConsentimentosRepository;
use Exception;

class LgpdConsentimentoColetaProcessar {
    private LgpdConsentimentosRepository $consentimentosRepo;
    
    public function __construct() {
        // Iniciar sessão para páginas públicas
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->consentimentosRepo = new LgpdConsentimentosRepository();
    }
    
    public function index(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'lgpd-consentimento-coleta');
            exit;
        }
        
        // Validar dados obrigatórios
        $dados = $this->validarDados($_POST);
        if (!$dados) {
            header('Location: ' . $_ENV['URL_ADM'] . 'lgpd-consentimento-coleta');
            exit;
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
                $_SESSION['sucesso'] = 'Consentimento registrado com sucesso! Obrigado por autorizar o tratamento de seus dados.';
            } else {
                $_SESSION['erro'] = 'Erro ao registrar consentimento. Tente novamente.';
            }
            
        } catch (Exception $e) {
            $_SESSION['erro'] = 'Erro interno: ' . $e->getMessage();
        }
        
        header('Location: ' . $_ENV['URL_ADM'] . 'lgpd-consentimento-coleta');
        exit;
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
        
        if (!isset($dados['politica_privacidade']) || !$dados['politica_privacidade']) {
            $erros[] = 'É obrigatório aceitar a Política de Privacidade';
        }
        
        if (!empty($erros)) {
            $_SESSION['erro'] = implode(', ', $erros);
            return null;
        }
        
        return [
            'nome' => trim($dados['nome']),
            'email' => trim($dados['email']),
            'finalidade' => trim($dados['finalidade']),
            'termos_aceitos' => isset($dados['termos_uso']) && $dados['termos_uso']
        ];
    }
}
