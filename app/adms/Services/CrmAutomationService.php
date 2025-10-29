<?php

namespace App\adms\Services;

use App\adms\Models\Repository\CrmAutomationsRepository;
use App\adms\Models\Repository\CrmActivitiesRepository;
use App\adms\Models\Repository\CrmNotesRepository;
use App\adms\Helpers\GenerateLog;

/**
 * Serviço para executar automações do CRM
 * 
 * @package App\adms\Services
 * @author Rafael Mendes
 */
class CrmAutomationService
{
    /**
     * Executar automações para um evento
     */
    public static function executeAutomations(string $entityType, string $triggerEvent, int $entityId, array $entityData = []): void
    {
        try {
            $repo = new CrmAutomationsRepository();
            $automations = $repo->getActiveAutomationsByTrigger($entityType, $triggerEvent);
            
            foreach ($automations as $automation) {
                // Verificar condições
                if (self::checkConditions($automation, $entityData)) {
                    self::executeAction($automation, $entityId, $entityData, $repo);
                }
            }
        } catch (\Exception $e) {
            GenerateLog::generateLog("error", "Erro ao executar automações", [
                'entity_type' => $entityType,
                'trigger_event' => $triggerEvent,
                'entity_id' => $entityId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Verificar se as condições da automação são atendidas
     */
    private static function checkConditions(array $automation, array $entityData): bool
    {
        // Se não há condições, sempre executa
        if (empty($automation['trigger_conditions'])) {
            return true;
        }

        $conditions = json_decode($automation['trigger_conditions'], true);
        if (!is_array($conditions)) {
            return true;
        }

        // Verificar cada condição
        foreach ($conditions as $field => $expectedValue) {
            $actualValue = $entityData[$field] ?? null;
            
            // Condições com operadores
            if (is_array($expectedValue)) {
                $operator = $expectedValue['operator'] ?? '=';
                $value = $expectedValue['value'] ?? null;
                
                switch ($operator) {
                    case '>':
                        if (!($actualValue > $value)) return false;
                        break;
                    case '>=':
                        if (!($actualValue >= $value)) return false;
                        break;
                    case '<':
                        if (!($actualValue < $value)) return false;
                        break;
                    case '<=':
                        if (!($actualValue <= $value)) return false;
                        break;
                    case '!=':
                        if (!($actualValue != $value)) return false;
                        break;
                    case 'contains':
                        if (stripos($actualValue, $value) === false) return false;
                        break;
                    default: // =
                        if ($actualValue != $value) return false;
                }
            } else {
                // Comparação simples
                if ($actualValue != $expectedValue) {
                    return false;
                }
            }
        }
        
        return true;
    }

    /**
     * Executar ação da automação
     */
    private static function executeAction(array $automation, int $entityId, array $entityData, CrmAutomationsRepository $repo): void
    {
        $actionConfig = json_decode($automation['action_config'], true) ?? [];
        $status = 'success';
        $errorMessage = null;
        
        try {
            switch ($automation['action_type']) {
                case 'send_email':
                    self::actionSendEmail($actionConfig, $entityData);
                    break;
                
                case 'send_whatsapp':
                    self::actionSendWhatsApp($actionConfig, $entityData);
                    break;
                
                case 'create_activity':
                    self::actionCreateActivity($actionConfig, $entityId, $entityData);
                    break;
                
                case 'create_note':
                    self::actionCreateNote($actionConfig, $entityId, $entityData);
                    break;
                
                case 'send_notification':
                    self::actionSendNotification($actionConfig, $entityData);
                    break;
                
                default:
                    $status = 'skipped';
                    $errorMessage = 'Tipo de ação não suportado: ' . $automation['action_type'];
            }
        } catch (\Exception $e) {
            $status = 'failed';
            $errorMessage = $e->getMessage();
        }
        
        // Registrar log
        $repo->logExecution(
            $automation['id'],
            $automation['entity_type'],
            $entityId,
            $status,
            $errorMessage,
            ['action_config' => $actionConfig, 'entity_data' => $entityData]
        );
    }

    /**
     * Ação: Enviar E-mail
     */
    private static function actionSendEmail(array $config, array $entityData): void
    {
        // Implementar envio de email (usar PHPMailer já disponível no projeto)
        $to = self::replaceVariables($config['to'] ?? '', $entityData);
        $subject = self::replaceVariables($config['subject'] ?? 'Notificação CRM', $entityData);
        $body = self::replaceVariables($config['body'] ?? '', $entityData);
        
        // TODO: Integrar com sistema de email existente
        GenerateLog::generateLog("info", "Email agendado para envio", [
            'to' => $to,
            'subject' => $subject
        ]);
    }

    /**
     * Ação: Enviar WhatsApp
     */
    private static function actionSendWhatsApp(array $config, array $entityData): void
    {
        $phone = self::replaceVariables($config['phone'] ?? '', $entityData);
        $message = self::replaceVariables($config['message'] ?? '', $entityData);
        
        // TODO: Integrar com WhatsApp API
        GenerateLog::generateLog("info", "WhatsApp agendado para envio", [
            'phone' => $phone,
            'message' => $message
        ]);
    }

    /**
     * Ação: Criar Atividade
     */
    private static function actionCreateActivity(array $config, int $entityId, array $entityData): void
    {
        $activitiesRepo = new CrmActivitiesRepository();
        
        $activityData = [
            'type' => $config['type'] ?? 'Tarefa',
            'title' => self::replaceVariables($config['title'] ?? 'Atividade Automática', $entityData),
            'description' => self::replaceVariables($config['description'] ?? '', $entityData),
            'responsible_user_id' => $config['responsible_user_id'] ?? $_SESSION['user_id'] ?? 1,
            'priority' => $config['priority'] ?? 'Média',
            'status' => 'Pendente'
        ];
        
        // Associar à oportunidade ou parceiro
        if ($entityData['entity_type'] ?? '' === 'opportunity') {
            $activityData['opportunity_id'] = $entityId;
            $activityData['partner_id'] = $entityData['partner_id'] ?? null;
        } else {
            $activityData['partner_id'] = $entityId;
        }
        
        $activitiesRepo->createActivity($activityData);
    }

    /**
     * Ação: Criar Nota
     */
    private static function actionCreateNote(array $config, int $entityId, array $entityData): void
    {
        $notesRepo = new CrmNotesRepository();
        
        $noteData = [
            'content' => self::replaceVariables($config['content'] ?? 'Nota automática', $entityData),
            'is_important' => $config['is_important'] ?? 0
        ];
        
        // Associar à oportunidade ou parceiro
        if ($entityData['entity_type'] ?? '' === 'opportunity') {
            $noteData['opportunity_id'] = $entityId;
            $noteData['partner_id'] = $entityData['partner_id'] ?? null;
        } else {
            $noteData['partner_id'] = $entityId;
        }
        
        $notesRepo->createNote($noteData);
    }

    /**
     * Ação: Enviar Notificação (sistema interno)
     */
    private static function actionSendNotification(array $config, array $entityData): void
    {
        $userId = $config['user_id'] ?? $_SESSION['user_id'] ?? 1;
        $message = self::replaceVariables($config['message'] ?? '', $entityData);
        
        // TODO: Integrar com sistema de notificações internas
        GenerateLog::generateLog("info", "Notificação criada", [
            'user_id' => $userId,
            'message' => $message
        ]);
    }

    /**
     * Substituir variáveis no texto (ex: {partner_name}, {value})
     */
    private static function replaceVariables(string $text, array $data): string
    {
        foreach ($data as $key => $value) {
            if (is_scalar($value)) {
                $text = str_replace('{' . $key . '}', $value, $text);
            }
        }
        
        return $text;
    }
}

