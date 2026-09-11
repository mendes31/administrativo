<?php

declare(strict_types=1);

namespace App\adms\Models\Services\InternalChat;

use App\adms\Models\Repository\ButtonPermissionUserRepository;

/**
 * Gate ACL do Tiarajuzinho: cada tool exige páginas/controllers do nível do usuário.
 * McpChat só abre o chat; a execução tipada observa as permissões do nível.
 */
final class ChatToolPermissionGate
{
    private ButtonPermissionUserRepository $perms;

    /** @var array<string, bool>|null */
    private ?array $cache = null;

    public function __construct(?ButtonPermissionUserRepository $perms = null)
    {
        $this->perms = $perms ?? new ButtonPermissionUserRepository();
    }

    /**
     * tool => controllers (qualquer um libera). Lista vazia = só McpChat (já validado na API).
     *
     * @return array<string, list<string>>
     */
    public static function toolControllerMap(): array
    {
        return [
            'chat.greeting' => [],
            'chat.help' => [],
            'chat.suggest' => [],
            'chat.clear_context' => [],

            // Relatórios: ACL fina em userCanViewReport / chat_enabled.
            'report.list' => [],
            'report.run' => [],

            // RH / Gestão de Pessoas (cadastro de usuários).
            'rh.lookup_person' => ['ListUsers', 'ViewUser'],
            'rh.count_active' => ['ListUsers'],
            'rh.list_active' => ['ListUsers'],
            'rh.count_inactive' => ['ListUsers'],
            'rh.count_terminated' => ['ListUsers'],
            'rh.list_terminated' => ['ListUsers'],
            'rh.count_terminated_in_month' => ['ListUsers'],
            'rh.count_terminated_by_department' => ['ListUsers'],
            'rh.count_terminated_by_month' => ['ListUsers'],
            'rh.list_hired' => ['ListUsers'],
            'rh.count_hired' => ['ListUsers'],
            'rh.count_blocked' => ['ListUsers'],
            'rh.count_blocked_not_terminated' => ['ListUsers'],
            'rh.count_active_by_department' => ['ListUsers'],
            'rh.list_active_by_age' => ['ListUsers'],

            // Reserva de salas.
            'rooms.list' => ['ListMeetingRooms', 'BookRoom', 'RoomCalendar'],
            'rooms.agenda' => ['RoomCalendar', 'BookRoom', 'ListMeetingRooms', 'ListBookings'],
            'rooms.my' => ['ListBookings', 'BookRoom', 'RoomCalendar', 'ListMeetingRooms'],
            'rooms.reserve' => ['CreateBooking'],
            'rooms.reserve_help' => ['CreateBooking', 'BookRoom', 'ListMeetingRooms'],
            'rooms.cancel' => ['CancelBooking'],
            'rooms.wizard' => ['CreateBooking'],
        ];
    }

    /**
     * Intent do agente → tool ACL.
     *
     * @return array<string, string>
     */
    public static function intentToToolMap(): array
    {
        return [
            'clear_context' => 'chat.clear_context',
            'suggest_invalid' => 'chat.suggest',
            'lookup_person' => 'rh.lookup_person',
            'lookup_person_refine' => 'rh.lookup_person',
            'active' => 'rh.count_active',
            'active_list' => 'rh.list_active',
            'active_by_age' => 'rh.list_active_by_age',
            'by_department' => 'rh.count_active_by_department',
            'inactive' => 'rh.count_inactive',
            'terminated_total' => 'rh.count_terminated',
            'terminated_list' => 'rh.list_terminated',
            'terminated_in_period' => 'rh.count_terminated_in_month',
            'terminated_by_department' => 'rh.count_terminated_by_department',
            'terminated_by_month' => 'rh.count_terminated_by_month',
            'clarify_by_month' => 'rh.count_terminated_by_month',
            'hired_list' => 'rh.list_hired',
            'hired_in_period' => 'rh.count_hired',
            'hired_total' => 'rh.count_hired',
            'blocked' => 'rh.count_blocked',
            'blocked_not_terminated' => 'rh.count_blocked_not_terminated',
            'report_list' => 'report.list',
            'report_run' => 'report.run',
            'report_code_filter' => 'report.run',
            'report_month_filter' => 'report.run',
            'rooms_list' => 'rooms.list',
            'rooms_agenda' => 'rooms.agenda',
            'rooms_my' => 'rooms.my',
            'rooms_reserve' => 'rooms.reserve',
            'rooms_reserve_help' => 'rooms.reserve_help',
            'rooms_cancel' => 'rooms.cancel',
            'rooms_wizard_start' => 'rooms.wizard',
        ];
    }

    public function warmCache(): void
    {
        if ($this->cache !== null) {
            return;
        }

        $needed = [];
        foreach (self::toolControllerMap() as $controllers) {
            foreach ($controllers as $c) {
                $needed[$c] = true;
            }
        }
        $list = array_keys($needed);
        $allowed = $list === [] ? [] : $this->perms->buttonPermission($list);
        $allowedSet = is_array($allowed) ? array_flip($allowed) : [];

        // CLI/scripts sem sessão de login: não bloquear o piloto local (testes).
        if (
            PHP_SAPI === 'cli'
            && empty($_SESSION['user_id'])
            && $allowedSet === []
        ) {
            $this->cache = [];
            foreach (array_keys(self::toolControllerMap()) as $tool) {
                $this->cache[$tool] = true;
            }

            return;
        }

        $this->cache = [];
        foreach (self::toolControllerMap() as $tool => $controllers) {
            if ($controllers === []) {
                $this->cache[$tool] = true;
                continue;
            }
            $ok = false;
            foreach ($controllers as $c) {
                if (isset($allowedSet[$c])) {
                    $ok = true;
                    break;
                }
            }
            $this->cache[$tool] = $ok;
        }
    }

    public function canUseTool(string $tool): bool
    {
        $this->warmCache();
        if (!array_key_exists($tool, $this->cache ?? [])) {
            // Tool futura sem mapa: negar (fail-closed).
            return false;
        }

        return (bool) ($this->cache[$tool] ?? false);
    }

    public function canUseIntent(string $intentName): bool
    {
        $tool = self::intentToToolMap()[$intentName] ?? null;
        if ($tool === null) {
            return false;
        }

        return $this->canUseTool($tool);
    }

    /**
     * @return list<string>
     */
    public function allowedTools(): array
    {
        $this->warmCache();
        $out = [];
        foreach ($this->cache ?? [] as $tool => $ok) {
            if ($ok) {
                $out[] = $tool;
            }
        }

        return $out;
    }

    /**
     * Controllers sugeridos para mensagem de negação.
     *
     * @return list<string>
     */
    public function requiredControllersForTool(string $tool): array
    {
        return self::toolControllerMap()[$tool] ?? [];
    }

    public function denyMessageForTool(string $tool): string
    {
        $required = $this->requiredControllersForTool($tool);
        $hint = $required !== []
            ? 'Solicite no seu nível de acesso: ' . implode(' / ', $required) . ' (além de McpChat).'
            : 'Solicite a permissão adequada no seu nível de acesso (além de McpChat).';

        return 'Sem permissão para esta consulta no Assistente MCP. ' . $hint;
    }

    public function denyMessageForIntent(string $intentName): string
    {
        $tool = self::intentToToolMap()[$intentName] ?? $intentName;

        return $this->denyMessageForTool($tool);
    }
}
