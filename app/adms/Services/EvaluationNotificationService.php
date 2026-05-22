<?php

namespace App\adms\Services;

use App\adms\Helpers\EvaluationLogService;
use App\adms\Helpers\InternalPushNotificationHelper;
use App\adms\Helpers\SendEmailService;

/**
 * Serviço para envio de notificações do módulo de avaliações
 * 
 * @package App\adms\Services
 */
class EvaluationNotificationService
{
    /**
     * Enviar notificação de nova atribuição
     * 
     * @param array $user Dados do usuário
     * @param array $model Dados do modelo de avaliação (opcional: assignment_id para deduplicação do push)
     * @param string|null $dataLimite Data limite para conclusão
     * @return bool Sucesso do envio
     */
    public static function notificarNovaAtribuicao(array $user, array $model, ?string $dataLimite): bool
    {
        try {
            $assunto = "Nova Avaliação: {$model['titulo']}";
            
            $prazoTexto = $dataLimite 
                ? date('d/m/Y', strtotime($dataLimite)) 
                : 'sem prazo definido';
            
            $mensagem = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #007bff;'>Olá, {$user['name']}!</h2>
                    
                    <p>Você recebeu uma nova avaliação para responder:</p>
                    
                    <div style='background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;'>
                        <h3 style='margin-top: 0;'>{$model['titulo']}</h3>
                        <p><strong>Prazo:</strong> {$prazoTexto}</p>
                    </div>
                    
                    <p>Acesse o sistema para responder a avaliação:</p>
                    
                    <p style='text-align: center; margin: 30px 0;'>
                        <a href='{$_ENV['URL_ADM']}minhas-avaliacoes' 
                           style='background-color: #007bff; color: white; padding: 12px 30px; 
                                  text-decoration: none; border-radius: 5px; display: inline-block;'>
                            Acessar Minhas Avaliações
                        </a>
                    </p>
                    
                    <hr style='border: none; border-top: 1px solid #dee2e6; margin: 30px 0;'>
                    
                    <p style='color: #6c757d; font-size: 12px;'>
                        Esta é uma notificação automática. Por favor, não responda este e-mail.
                    </p>
                </div>
            ";

            $altBody = "Olá {$user['name']},\n\n" .
                       "Você recebeu uma nova avaliação para responder:\n\n" .
                       "{$model['titulo']}\n" .
                       "Prazo: {$prazoTexto}\n\n" .
                       "Acesse o sistema em: {$_ENV['URL_ADM']}minhas-avaliacoes";

            $sucesso = SendEmailService::sendEmail(
                $user['email'],
                $user['name'],
                $assunto,
                $mensagem,
                $altBody
            );

            EvaluationLogService::logNotificationSent(
                $user['id'],
                $model['id'],
                'nova_atribuicao',
                $sucesso,
                $sucesso ? null : 'Falha no envio de e-mail'
            );

            $base = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/');
            $assignmentId = (int) ($model['assignment_id'] ?? 0);
            $entityId = $assignmentId > 0 ? $assignmentId : (int) ($model['id'] ?? 0);
            InternalPushNotificationHelper::notifyUser([
                'user_id' => (int) ($user['id'] ?? 0),
                'type' => 'evaluation_assignment',
                'title' => 'Nova avaliação',
                'message' => ($model['titulo'] ?? 'Avaliação') . ($dataLimite ? ' — prazo ' . date('d/m/Y', strtotime($dataLimite)) : ''),
                'link_url' => $base . '/minhas-avaliacoes',
                'entity_type' => 'adms_evaluation_assignment',
                'entity_id' => $entityId,
            ]);

            return $sucesso;

        } catch (\Exception $e) {
            EvaluationLogService::logNotificationSent(
                $user['id'] ?? 0,
                $model['id'] ?? 0,
                'nova_atribuicao',
                false,
                $e->getMessage()
            );
            return false;
        }
    }

    /**
     * Enviar notificação de prazo próximo
     * 
     * @param array $user Dados do usuário
     * @param array $assignment Dados da atribuição
     * @param int $diasRestantes Dias restantes até o prazo
     * @return bool
     */
    public static function notificarPrazoProximo(array $user, array $assignment, int $diasRestantes): bool
    {
        try {
            $assunto = "Lembrete: Avaliação próxima do prazo - {$assignment['model_titulo']}";
            
            $mensagem = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #ffc107;'>⚠️ Lembrete de Avaliação Pendente</h2>
                    
                    <p>Olá, {$user['name']}!</p>
                    
                    <p>Você possui uma avaliação pendente que está próxima do prazo:</p>
                    
                    <div style='background-color: #fff3cd; padding: 20px; border-radius: 5px; 
                                border-left: 4px solid #ffc107; margin: 20px 0;'>
                        <h3 style='margin-top: 0;'>{$assignment['model_titulo']}</h3>
                        <p><strong>Prazo:</strong> " . date('d/m/Y', strtotime($assignment['data_limite'])) . "</p>
                        <p><strong>Dias restantes:</strong> {$diasRestantes}</p>
                    </div>
                    
                    <p>Não deixe para a última hora! Acesse o sistema e responda a avaliação:</p>
                    
                    <p style='text-align: center; margin: 30px 0;'>
                        <a href='{$_ENV['URL_ADM']}minhas-avaliacoes' 
                           style='background-color: #ffc107; color: #000; padding: 12px 30px; 
                                  text-decoration: none; border-radius: 5px; display: inline-block;'>
                            Responder Agora
                        </a>
                    </p>
                </div>
            ";

            $altBody = "Lembrete: Avaliação próxima do prazo\n\n" .
                       "{$assignment['model_titulo']}\n" .
                       "Prazo: " . date('d/m/Y', strtotime($assignment['data_limite'])) . "\n" .
                       "Dias restantes: {$diasRestantes}\n\n" .
                       "Acesse: {$_ENV['URL_ADM']}minhas-avaliacoes";

            $sucesso = SendEmailService::sendEmail(
                $user['email'],
                $user['name'],
                $assunto,
                $mensagem,
                $altBody
            );

            $base = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/');
            $assignmentId = (int) ($assignment['id'] ?? 0);
            InternalPushNotificationHelper::notifyUser([
                'user_id' => (int) ($user['id'] ?? 0),
                'type' => 'evaluation_reminder',
                'title' => 'Lembrete de avaliação',
                'message' => ($assignment['model_titulo'] ?? 'Avaliação') . " — {$diasRestantes} dia(s) restante(s)",
                'link_url' => $base . '/minhas-avaliacoes',
                'entity_type' => 'adms_evaluation_assignment',
                'entity_id' => $assignmentId > 0 ? $assignmentId : (int) ($assignment['evaluation_model_id'] ?? 0),
            ]);

            return $sucesso;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Enviar notificação de prazo vencido
     * 
     * @param array $user Dados do usuário
     * @param array $assignment Dados da atribuição
     * @return bool
     */
    public static function notificarPrazoVencido(array $user, array $assignment): bool
    {
        try {
            $assunto = "URGENTE: Avaliação com prazo vencido - {$assignment['model_titulo']}";
            
            $mensagem = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #dc3545;'>🚨 Avaliação com Prazo Vencido</h2>
                    
                    <p>Olá, {$user['name']}!</p>
                    
                    <p>Você possui uma avaliação pendente com prazo <strong>vencido</strong>:</p>
                    
                    <div style='background-color: #f8d7da; padding: 20px; border-radius: 5px; 
                                border-left: 4px solid #dc3545; margin: 20px 0;'>
                        <h3 style='margin-top: 0;'>{$assignment['model_titulo']}</h3>
                        <p><strong>Prazo era:</strong> " . date('d/m/Y', strtotime($assignment['data_limite'])) . "</p>
                        <p style='color: #721c24;'><strong>Status:</strong> VENCIDO</p>
                    </div>
                    
                    <p>Entre em contato com seu gestor ou com o RH para regularizar sua situação.</p>
                    
                    <p style='text-align: center; margin: 30px 0;'>
                        <a href='{$_ENV['URL_ADM']}minhas-avaliacoes' 
                           style='background-color: #dc3545; color: white; padding: 12px 30px; 
                                  text-decoration: none; border-radius: 5px; display: inline-block;'>
                            Acessar Sistema
                        </a>
                    </p>
                </div>
            ";

            $altBody = "URGENTE: Avaliação com prazo vencido\n\n" .
                       "{$assignment['model_titulo']}\n" .
                       "Prazo era: " . date('d/m/Y', strtotime($assignment['data_limite'])) . "\n\n" .
                       "Entre em contato com seu gestor ou RH.";

            $sucesso = SendEmailService::sendEmail(
                $user['email'],
                $user['name'],
                $assunto,
                $mensagem,
                $altBody
            );

            $base = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/');
            $assignmentId = (int) ($assignment['id'] ?? 0);
            InternalPushNotificationHelper::notifyUser([
                'user_id' => (int) ($user['id'] ?? 0),
                'type' => 'evaluation_reminder',
                'title' => 'Avaliação vencida',
                'message' => ($assignment['model_titulo'] ?? 'Avaliação') . ' — prazo vencido',
                'link_url' => $base . '/minhas-avaliacoes',
                'entity_type' => 'adms_evaluation_assignment',
                'entity_id' => $assignmentId > 0 ? $assignmentId : (int) ($assignment['evaluation_model_id'] ?? 0),
                'priority' => 50,
            ]);

            return $sucesso;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Enviar notificação de resultado (aprovação/reprovação)
     * 
     * @param array $user Dados do usuário
     * @param array $attempt Dados da tentativa
     * @param bool $aprovado Se foi aprovado
     * @return bool
     */
    public static function notificarResultado(array $user, array $attempt, bool $aprovado): bool
    {
        try {
            $assunto = $aprovado 
                ? "✅ Aprovado: {$attempt['model_titulo']}" 
                : "❌ Reprovado: {$attempt['model_titulo']}";
            
            $cor = $aprovado ? '#28a745' : '#dc3545';
            $status = $aprovado ? 'APROVADO' : 'REPROVADO';
            
            $mensagem = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: {$cor};'>{$status}</h2>
                    
                    <p>Olá, {$user['name']}!</p>
                    
                    <p>Você finalizou a avaliação:</p>
                    
                    <div style='background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;'>
                        <h3 style='margin-top: 0;'>{$attempt['model_titulo']}</h3>
                        <p><strong>Nota obtida:</strong> {$attempt['nota_obtida']}</p>
                        <p><strong>Nota mínima:</strong> {$attempt['nota_minima_aprovacao']}</p>
                        <p><strong>Percentual de acerto:</strong> {$attempt['percentual']}%</p>
                        <p style='color: {$cor}; font-size: 18px; font-weight: bold;'>{$status}</p>
                    </div>
                    
                    <p style='text-align: center; margin: 30px 0;'>
                        <a href='{$_ENV['URL_ADM']}resultado-avaliacao/{$attempt['id']}' 
                           style='background-color: {$cor}; color: white; padding: 12px 30px; 
                                  text-decoration: none; border-radius: 5px; display: inline-block;'>
                            Ver Resultado Detalhado
                        </a>
                    </p>
                </div>
            ";

            $altBody = "{$status}\n\n" .
                       "{$attempt['model_titulo']}\n" .
                       "Nota: {$attempt['nota_obtida']}\n" .
                       "Acesse: {$_ENV['URL_ADM']}resultado-avaliacao/{$attempt['id']}";

            return SendEmailService::sendEmail(
                $user['email'],
                $user['name'],
                $assunto,
                $mensagem,
                $altBody
            );

        } catch (\Exception $e) {
            return false;
        }
    }
}

