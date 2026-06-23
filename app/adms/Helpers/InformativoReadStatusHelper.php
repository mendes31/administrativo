<?php

namespace App\adms\Helpers;

/**
 * Texto e classes para exibir situação de leitura / ciência do usuário na listagem de informativos.
 */
final class InformativoReadStatusHelper
{
    /**
     * @param array<string, mixed> $informativo
     * @param array<string, mixed>|null $read linha de adms_informativos_reads ou null
     * @return array{text: string, badge_class: string, icon: string}
     */
    public static function forCurrentUser(array $informativo, ?array $read, ?int $userId = null): array
    {
        $userId = $userId ?? (int) ($_SESSION['user_id'] ?? 0);
        if ($userId > 0 && InstitutionalSystemUserHelper::isExemptFromAcknowledgment($userId)) {
            return [
                'text' => 'Isento (usuário sistema)',
                'badge_class' => 'bg-light text-muted border',
                'icon' => 'fa-solid fa-robot',
            ];
        }

        $requiresAck = !empty($informativo['requires_ack']);
        $readAt = $read && !empty($read['read_at']);
        $ack = $read && !empty($read['acknowledged']);

        if ($requiresAck) {
            if ($ack) {
                return [
                    'text' => 'Ciente',
                    'badge_class' => 'bg-success',
                    'icon' => 'fa-solid fa-circle-check',
                ];
            }
            if ($readAt) {
                return [
                    'text' => 'Visualizado — aguardando ciência',
                    'badge_class' => 'bg-warning text-dark',
                    'icon' => 'fa-solid fa-eye',
                ];
            }

            return [
                'text' => 'Não visualizado',
                'badge_class' => 'bg-secondary',
                'icon' => 'fa-solid fa-eye-slash',
            ];
        }

        if ($readAt) {
            return [
                'text' => 'Visualizado',
                'badge_class' => 'bg-info',
                'icon' => 'fa-solid fa-eye',
            ];
        }

        return [
            'text' => 'Não lido',
            'badge_class' => 'bg-secondary',
            'icon' => 'fas fa-envelope',
        ];
    }
}
