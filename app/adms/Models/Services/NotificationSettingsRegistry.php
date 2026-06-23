<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

/**
 * Metadados das chaves de notificação configuráveis no painel administrativo.
 *
 * Escopo: apenas alertas automáticos de SST (cron) e Treinamentos (cron/vínculo).
 * Não controla informativos, políticas, SAC, folha, avaliações, projetos etc.
 */
final class NotificationSettingsRegistry
{
    /**
     * @return array<string, array{module: string, module_order: int, label: string, description: string, order: int, is_notification: bool}>
     */
    public static function definitions(): array
    {
        return [
            'sst_pendencias_email' => [
                'module' => 'SST — Saúde e Segurança do Trabalho',
                'module_order' => 10,
                'label' => 'E-mail de pendências SST',
                'description' => 'Envia resumo por e-mail aos colaboradores com pendências de EPI ou exame/ASO (cron SST).',
                'order' => 1,
                'is_notification' => true,
            ],
            'sst_pendencias_inapp' => [
                'module' => 'SST — Saúde e Segurança do Trabalho',
                'module_order' => 10,
                'label' => 'Notificação interna / push de pendências SST',
                'description' => 'Alerta no sino e push PWA para pendências SST (cron SST).',
                'order' => 2,
                'is_notification' => true,
            ],
            'sst_pendencias_incluir_treinamentos' => [
                'module' => 'SST — Saúde e Segurança do Trabalho',
                'module_order' => 10,
                'label' => 'Incluir treinamentos nas pendências SST',
                'description' => 'Inclui treinamentos SST obrigatórios (matriz cargo/risco) nas telas e alertas de pendências. Não altera o módulo de Treinamentos.',
                'order' => 3,
                'is_notification' => false,
            ],
            'training_pending_email' => [
                'module' => 'Treinamentos (alertas automáticos)',
                'module_order' => 20,
                'label' => 'E-mail — treinamentos pendentes',
                'description' => 'E-mail para colaboradores com treinamento obrigatório pendente (cron/rotinas de treinamentos).',
                'order' => 1,
                'is_notification' => true,
            ],
            'training_pending_inapp' => [
                'module' => 'Treinamentos (alertas automáticos)',
                'module_order' => 20,
                'label' => 'Notificação interna / push — treinamentos pendentes',
                'description' => 'Alerta interno e push PWA para treinamentos pendentes.',
                'order' => 2,
                'is_notification' => true,
            ],
            'training_expiring_email' => [
                'module' => 'Treinamentos (alertas automáticos)',
                'module_order' => 20,
                'label' => 'E-mail — treinamentos a vencer',
                'description' => 'E-mail de reciclagem próxima do vencimento.',
                'order' => 3,
                'is_notification' => true,
            ],
            'training_expiring_inapp' => [
                'module' => 'Treinamentos (alertas automáticos)',
                'module_order' => 20,
                'label' => 'Notificação interna / push — treinamentos a vencer',
                'description' => 'Alerta interno e push PWA para reciclagem a vencer.',
                'order' => 4,
                'is_notification' => true,
            ],
            'training_expired_email' => [
                'module' => 'Treinamentos (alertas automáticos)',
                'module_order' => 20,
                'label' => 'E-mail — treinamentos vencidos',
                'description' => 'E-mail urgente para treinamentos com reciclagem vencida.',
                'order' => 5,
                'is_notification' => true,
            ],
            'training_expired_inapp' => [
                'module' => 'Treinamentos (alertas automáticos)',
                'module_order' => 20,
                'label' => 'Notificação interna / push — treinamentos vencidos',
                'description' => 'Alerta interno e push PWA para treinamentos vencidos.',
                'order' => 6,
                'is_notification' => true,
            ],
            'training_new_mandatory_email' => [
                'module' => 'Treinamentos (alertas automáticos)',
                'module_order' => 20,
                'label' => 'E-mail — novo treinamento obrigatório',
                'description' => 'E-mail ao vincular treinamento obrigatório ao cargo do colaborador.',
                'order' => 7,
                'is_notification' => true,
            ],
            'training_new_mandatory_inapp' => [
                'module' => 'Treinamentos (alertas automáticos)',
                'module_order' => 20,
                'label' => 'Notificação interna / push — novo treinamento obrigatório',
                'description' => 'Alerta interno e push PWA ao vincular treinamento obrigatório.',
                'order' => 8,
                'is_notification' => true,
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::definitions());
    }
}
