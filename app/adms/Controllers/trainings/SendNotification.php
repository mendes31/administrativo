<?php

namespace App\adms\Controllers\trainings;

use App\adms\Controllers\Services\NotificacaoTreinamentosService;

/**
 * Controller para enviar notificações de treinamentos
 *
 * Esta classe é responsável por processar o envio de notificações de treinamentos
 * pendentes, vencidos e próximos do vencimento para os usuários.
 *
 * @package App\adms\Controllers\trainings
 * @author Rafael Mendes
 */
class SendNotification
{
    /**
     * Método principal para enviar notificações
     *
     * @return void
     */
    public function index(): void
    {
        // die('DEBUG: Entrou no SendNotification!');
        
        // Verificar se é POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'test-notification');
            exit;
        }

        try {
            // Instanciar o serviço de notificações
            $notificationService = new NotificacaoTreinamentosService();
            
            // Enviar notificações
            $notificationService->notificarTreinamentosPendentes();
            
            $_SESSION['msg'] = 'Notificações enviadas com sucesso!';
            $_SESSION['msg_type'] = 'success';
        } catch (\Exception $e) {
            $_SESSION['msg'] = 'Erro: ' . $e->getMessage();
            $_SESSION['msg_type'] = 'danger';
        }
        
        // Redirecionar de volta para a página de teste
        header('Location: ' . $_ENV['URL_ADM'] . 'test-notification');
        exit;
    }
} 