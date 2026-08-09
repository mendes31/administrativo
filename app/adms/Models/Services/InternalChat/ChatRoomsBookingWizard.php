<?php

declare(strict_types=1);

namespace App\adms\Models\Services\InternalChat;

use App\adms\Helpers\ImageHelper;

/**
 * Fluxo guiado de reserva no chat: sala → data → horários vagos → confirmar.
 */
class ChatRoomsBookingWizard
{
    private const SESSION_KEY = 'internal_chat_rooms_wizard';

    private ChatRoomsService $rooms;

    public function __construct(?ChatRoomsService $rooms = null)
    {
        $this->rooms = $rooms ?? new ChatRoomsService();
    }

    public function isActive(): bool
    {
        $state = $this->getState();

        return is_array($state) && !empty($state['step']);
    }

    /**
     * @return array{resposta:string, tool:string, data?:mixed, provider:string}
     */
    public function start(int $userId): array
    {
        if (!$this->rooms->userCanReserve() && !$this->rooms->userCanListRooms()) {
            return [
                'resposta' => 'Sem permissão para agendar salas. Solicite CreateBooking / BookRoom no seu nível (além de McpChat).',
                'tool' => 'rooms.wizard',
                'provider' => 'local-rules',
            ];
        }

        $list = $this->rooms->listRooms(null);
        $rooms = $list['data']['rooms'] ?? [];
        if (!is_array($rooms) || $rooms === []) {
            $this->clear();

            return [
                'resposta' => 'Nenhuma sala ativa disponível para agendar.',
                'tool' => 'rooms.wizard',
                'provider' => 'local-rules',
            ];
        }

        $options = [];
        $map = [];
        $n = 1;
        foreach ($rooms as $room) {
            $id = (int) ($room['id'] ?? 0);
            if ($id < 1) {
                continue;
            }
            $full = $this->rooms->resolveRoom('#' . $id);
            $imagePath = is_array($full) ? ($full['image'] ?? null) : null;
            $imageUrl = $this->imageUrl(is_string($imagePath) ? $imagePath : null);
            $label = (string) ($room['name'] ?? ('Sala #' . $id));
            $cap = (int) ($room['capacity'] ?? (is_array($full) ? ($full['capacity'] ?? 0) : 0));
            $building = trim((string) ($room['building'] ?? (is_array($full) ? ($full['building'] ?? '') : '')));
            $loc = trim((string) ($room['location'] ?? (is_array($full) ? ($full['location'] ?? '') : '')));
            $options[] = [
                'value' => (string) $n,
                'label' => $n . ' — ' . $label,
                'sub' => implode(' · ', $this->rooms->formatRoomPlaceExtras($cap, $building, $loc)),
                'image_url' => $imageUrl,
            ];
            $map[$n] = [
                'id' => $id,
                'name' => $label,
            ];
            $n++;
        }

        $this->setState([
            'step' => 'pick_room',
            'user_id' => $userId,
            'room_map' => $map,
            'room_id' => null,
            'room_name' => null,
            'date' => null,
            'slot_map' => [],
        ]);

        return [
            'resposta' => "Vamos agendar uma sala. Escolha o número:\n\n"
                . "Dica: digite só o número (ex.: 1) ou toque na opção abaixo.\n"
                . "Para sair: «cancelar agendamento».",
            'tool' => 'rooms.wizard',
            'data' => [
                'ui' => [
                    'type' => 'rooms_wizard',
                    'step' => 'pick_room',
                    'options' => $options,
                ],
            ],
            'provider' => 'local-rules',
        ];
    }

    /**
     * Continua o fluxo com a resposta do usuário.
     *
     * @return array{resposta:string, tool:string, data?:mixed, provider:string}|null null se não for passo do wizard
     */
    public function continue(int $userId, string $message): ?array
    {
        $state = $this->getState();
        if (!is_array($state) || empty($state['step'])) {
            return null;
        }

        $m = mb_strtolower(trim($message));
        if (preg_match('/^(cancelar(\s+agendamento)?|sair|abortar|parar)$/u', $m)) {
            $this->clear();

            return [
                'resposta' => 'Agendamento cancelado. Quando quiser, diga «agendar» ou «reservar».',
                'tool' => 'rooms.wizard',
                'provider' => 'local-rules',
            ];
        }
        if (preg_match('/^voltar$/u', $m)) {
            return $this->goBack($userId);
        }
        if (preg_match('/^(outra\s+data|mudar\s+data|escolher\s+outra\s+data)$/u', $m)) {
            return $this->askDateAgain($state);
        }

        $step = (string) ($state['step'] ?? '');
        // Se a mensagem não parece resposta do passo, encerra o fluxo e deixa o agente tratar.
        if (!$this->messageLooksLikeWizardInput($step, $m, $message)) {
            $this->clear();

            return null;
        }

        return match ($step) {
            'pick_room' => $this->stepPickRoom($userId, $m, $state),
            'pick_date' => $this->stepPickDate($userId, $m, $message, $state),
            'pick_slots' => $this->stepPickSlots($userId, $m, $message, $state),
            'pick_title' => $this->stepPickTitle($userId, $message, $state),
            default => null,
        };
    }

    private function messageLooksLikeWizardInput(string $step, string $normalized, string $original): bool
    {
        return match ($step) {
            'pick_room' => (bool) preg_match('/^#?\d+$/', $normalized),
            'pick_date' => $this->parseDay($normalized) !== null,
            // Em pick_slots aceita horários (3 / 3-5) ou nova data (dd/mm/aaaa, hoje…).
            'pick_slots' => $this->parseDay($normalized) !== null || $this->parseSlotSelection($normalized) !== [],
            'pick_title' => trim($original) !== '',
            default => false,
        };
    }

    /**
     * @param array<string, mixed> $state
     * @return array{resposta:string, tool:string, data?:mixed, provider:string}
     */
    private function askDateAgain(array $state): array
    {
        $state['step'] = 'pick_date';
        $state['slot_map'] = [];
        $state['ranges'] = [];
        $state['date'] = null;
        if (empty($state['room_image_url']) && !empty($state['room_id'])) {
            $state['room_image_url'] = $this->resolveRoomImageUrl((int) $state['room_id']);
        }
        $this->setState($state);

        return [
            'resposta' => 'Escolha outra data (hoje, amanhã ou dd/mm/aaaa).',
            'tool' => 'rooms.wizard',
            'data' => [
                'ui' => [
                    'type' => 'rooms_wizard',
                    'step' => 'pick_date',
                    'room' => $this->roomUiFromState($state),
                    'options' => [
                        ['value' => 'hoje', 'label' => 'Hoje'],
                        ['value' => 'amanhã', 'label' => 'Amanhã'],
                        ['value' => date('d/m/Y', strtotime('+2 day') ?: time()), 'label' => date('d/m/Y', strtotime('+2 day') ?: time())],
                    ],
                    'nav' => [
                        ['value' => 'voltar', 'label' => 'Voltar'],
                        ['value' => 'cancelar agendamento', 'label' => 'Cancelar'],
                    ],
                ],
            ],
            'provider' => 'local-rules',
        ];
    }

    public function clear(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            unset($_SESSION[self::SESSION_KEY]);
        }
    }

    /**
     * Após «salas», permite responder só com o número (1, 2…) para seguir o fluxo.
     *
     * @param list<array{id?:int,name?:string}> $rooms
     */
    public function armPickRoomFromList(int $userId, array $rooms): void
    {
        $map = [];
        $n = 1;
        foreach ($rooms as $room) {
            $id = (int) ($room['id'] ?? 0);
            if ($id < 1) {
                continue;
            }
            $map[$n] = [
                'id' => $id,
                'name' => (string) ($room['name'] ?? ('Sala #' . $id)),
            ];
            $n++;
        }
        if ($map === []) {
            return;
        }
        $this->setState([
            'step' => 'pick_room',
            'user_id' => $userId,
            'room_map' => $map,
            'room_id' => null,
            'room_name' => null,
            'date' => null,
            'slot_map' => [],
        ]);
    }

    /**
     * @param array<string, mixed> $state
     * @return array{resposta:string, tool:string, data?:mixed, provider:string}
     */
    private function stepPickRoom(int $userId, string $normalized, array $state): array
    {
        $map = $state['room_map'] ?? [];
        if (!is_array($map)) {
            $map = [];
        }
        if (!preg_match('/^#?(\d+)$/', $normalized, $mm)) {
            return [
                'resposta' => 'Digite o número da sala (ex.: 1) ou toque numa opção. «cancelar agendamento» para sair.',
                'tool' => 'rooms.wizard',
                'data' => ['ui' => ['type' => 'rooms_wizard', 'step' => 'pick_room', 'options' => $this->optionsFromRoomMap($map)]],
                'provider' => 'local-rules',
            ];
        }
        $n = (int) $mm[1];
        if (!isset($map[$n])) {
            return [
                'resposta' => 'Número inválido. Escolha uma das opções listadas.',
                'tool' => 'rooms.wizard',
                'data' => ['ui' => ['type' => 'rooms_wizard', 'step' => 'pick_room', 'options' => $this->optionsFromRoomMap($map)]],
                'provider' => 'local-rules',
            ];
        }

        $state['room_id'] = (int) $map[$n]['id'];
        $state['room_name'] = (string) $map[$n]['name'];
        $state['room_image_url'] = $this->resolveRoomImageUrl((int) $state['room_id']);
        $state['step'] = 'pick_date';
        $this->setState($state);

        return [
            'resposta' => 'Qual a data? Digite hoje, amanhã ou dd/mm/aaaa.',
            'tool' => 'rooms.wizard',
            'data' => [
                'ui' => [
                    'type' => 'rooms_wizard',
                    'step' => 'pick_date',
                    'layout' => 'row',
                    'room' => $this->roomUiFromState($state),
                    'options' => [
                        ['value' => 'hoje', 'label' => 'Hoje'],
                        ['value' => 'amanhã', 'label' => 'Amanhã'],
                        ['value' => date('d/m/Y', strtotime('+2 day') ?: time()), 'label' => date('d/m/Y', strtotime('+2 day') ?: time())],
                    ],
                    'nav' => [
                        ['value' => 'voltar', 'label' => 'Voltar'],
                        ['value' => 'cancelar agendamento', 'label' => 'Cancelar'],
                    ],
                ],
            ],
            'provider' => 'local-rules',
        ];
    }

    /**
     * @param array<string, mixed> $state
     * @return array{resposta:string, tool:string, data?:mixed, provider:string}
     */
    private function stepPickDate(int $userId, string $normalized, string $original, array $state): array
    {
        $day = $this->parseDay($normalized);
        if ($day === null) {
            return [
                'resposta' => 'Não entendi a data. Use hoje, amanhã ou dd/mm/aaaa.',
                'tool' => 'rooms.wizard',
                'data' => [
                    'ui' => [
                        'type' => 'rooms_wizard',
                        'step' => 'pick_date',
                        'room' => $this->roomUiFromState($state),
                        'options' => [
                            ['value' => 'hoje', 'label' => 'Hoje'],
                            ['value' => 'amanhã', 'label' => 'Amanhã'],
                        ],
                    ],
                ],
                'provider' => 'local-rules',
            ];
        }

        $roomId = (int) ($state['room_id'] ?? 0);
        $daySlots = $this->rooms->listDayHalfHourSlots($roomId, $day);
        if ($daySlots === []) {
            $state['step'] = 'pick_date';
            $this->setState($state);

            return [
                'resposta' => 'Não foi possível listar horários em ' . date('d/m/Y', strtotime($day) ?: time())
                    . " para «{$state['room_name']}». Escolha outra data ou «voltar».",
                'tool' => 'rooms.wizard',
                'data' => [
                    'ui' => [
                        'type' => 'rooms_wizard',
                        'step' => 'pick_date',
                        'options' => [
                            ['value' => 'hoje', 'label' => 'Hoje'],
                            ['value' => 'amanhã', 'label' => 'Amanhã'],
                            ['value' => 'voltar', 'label' => 'Voltar'],
                        ],
                    ],
                ],
                'provider' => 'local-rules',
            ];
        }

        $built = $this->buildSlotMapAndOptions($daySlots);
        $state['date'] = $day;
        $state['slot_map'] = $built['slot_map'];
        $state['step'] = 'pick_slots';
        if (empty($state['room_image_url']) && $roomId > 0) {
            $state['room_image_url'] = $this->resolveRoomImageUrl($roomId);
        }
        $this->setState($state);

        $dayBr = date('d/m/Y', strtotime($day) ?: time());
        $freeCount = $built['free_count'];
        $msg = $freeCount > 0
            ? "{$dayBr} — toque num horário livre ou digite o número (ex.: 3 ou 3-5)."
            : "{$dayBr} — nenhum horário livre. Veja os ocupados ou escolha outra data.";

        return [
            'resposta' => $msg,
            'tool' => 'rooms.wizard',
            'data' => [
                'ui' => [
                    'type' => 'rooms_wizard',
                    'step' => 'pick_slots',
                    'layout' => 'chips',
                    'multi' => true,
                    'room' => $this->roomUiFromState($state),
                    'options' => $built['options'],
                    'nav' => [
                        ['value' => 'outra data', 'label' => 'Outra data'],
                        ['value' => 'voltar', 'label' => 'Voltar'],
                        ['value' => 'cancelar agendamento', 'label' => 'Cancelar'],
                    ],
                ],
            ],
            'provider' => 'local-rules',
        ];
    }

    /**
     * @param array<string, mixed> $state
     * @return array{resposta:string, tool:string, data?:mixed, provider:string}
     */
    private function stepPickSlots(int $userId, string $normalized, string $original, array $state): array
    {
        // Digitar outra data (ex.: 10/08/2026) troca o dia sem precisar do botão «Outra data».
        if ($this->parseDay($normalized) !== null) {
            return $this->stepPickDate($userId, $normalized, $original, $state);
        }

        $indices = $this->parseSlotSelection($normalized);
        $slotMap = $state['slot_map'] ?? [];
        if (!is_array($slotMap) || $indices === []) {
            return [
                'resposta' => 'Informe os números dos horários livres (ex.: 2 ou 2,3,4).',
                'tool' => 'rooms.wizard',
                'data' => [
                    'ui' => [
                        'type' => 'rooms_wizard',
                        'step' => 'pick_slots',
                        'layout' => 'chips',
                        'multi' => true,
                        'options' => $this->optionsFromSlotMap(is_array($slotMap) ? $slotMap : []),
                        'nav' => [
                            ['value' => 'outra data', 'label' => 'Outra data'],
                            ['value' => 'voltar', 'label' => 'Voltar'],
                            ['value' => 'cancelar agendamento', 'label' => 'Cancelar'],
                        ],
                    ],
                ],
                'provider' => 'local-rules',
            ];
        }

        $selected = [];
        foreach ($indices as $i) {
            if (!isset($slotMap[$i]) || !is_array($slotMap[$i])) {
                return [
                    'resposta' => "Número {$i} inválido. Escolha só entre as opções listadas.",
                    'tool' => 'rooms.wizard',
                    'data' => [
                        'ui' => [
                            'type' => 'rooms_wizard',
                            'step' => 'pick_slots',
                            'layout' => 'chips',
                            'multi' => true,
                            'options' => $this->optionsFromSlotMap($slotMap),
                            'nav' => [
                                ['value' => 'outra data', 'label' => 'Outra data'],
                                ['value' => 'voltar', 'label' => 'Voltar'],
                                ['value' => 'cancelar agendamento', 'label' => 'Cancelar'],
                            ],
                        ],
                    ],
                    'provider' => 'local-rules',
                ];
            }
            $slot = $slotMap[$i];
            if (empty($slot['available'])) {
                $status = (string) ($slot['status'] ?? '');
                if ($status === 'busy') {
                    $who = trim((string) ($slot['reserved_by'] ?? 'alguém'));
                    $dept = trim((string) ($slot['reserved_department'] ?? ''));

                    return [
                        'resposta' => "O horário {$i} ({$slot['start']}–{$slot['end']}) está ocupado por {$who}"
                            . ($dept !== '' ? " ({$dept})" : '')
                            . '. Escolha um horário livre ou digite outra data.',
                        'tool' => 'rooms.wizard',
                        'data' => [
                            'ui' => [
                                'type' => 'rooms_wizard',
                                'step' => 'pick_slots',
                                'layout' => 'chips',
                                'multi' => true,
                                'options' => $this->optionsFromSlotMap($slotMap),
                                'nav' => [
                                    ['value' => 'outra data', 'label' => 'Outra data'],
                                    ['value' => 'voltar', 'label' => 'Voltar'],
                                    ['value' => 'cancelar agendamento', 'label' => 'Cancelar'],
                                ],
                            ],
                        ],
                        'provider' => 'local-rules',
                    ];
                }

                return [
                    'resposta' => "O horário {$i} ({$slot['start']}–{$slot['end']}) já passou. Escolha um livre.",
                    'tool' => 'rooms.wizard',
                    'data' => [
                        'ui' => [
                            'type' => 'rooms_wizard',
                            'step' => 'pick_slots',
                            'layout' => 'chips',
                            'multi' => true,
                            'options' => $this->optionsFromSlotMap($slotMap),
                            'nav' => [
                                ['value' => 'outra data', 'label' => 'Outra data'],
                                ['value' => 'voltar', 'label' => 'Voltar'],
                                ['value' => 'cancelar agendamento', 'label' => 'Cancelar'],
                            ],
                        ],
                    ],
                    'provider' => 'local-rules',
                ];
            }
            $selected[] = [
                'start' => (string) $slot['start'],
                'end' => (string) $slot['end'],
            ];
        }

        usort($selected, static fn ($a, $b) => strcmp($a['start'], $b['start']));
        $ranges = $this->mergeConsecutiveSlots($selected, (string) $state['date']);
        if ($ranges === []) {
            return [
                'resposta' => 'Não foi possível montar o horário. Tente de novo.',
                'tool' => 'rooms.wizard',
                'provider' => 'local-rules',
            ];
        }

        $state['ranges'] = $ranges;
        $state['step'] = 'pick_title';
        $this->setState($state);

        $preview = [];
        foreach ($ranges as $r) {
            $preview[] = date('H:i', strtotime($r['start'])) . '–' . date('H:i', strtotime($r['end']));
        }

        return [
            'resposta' => 'Horário(s): ' . implode(', ', $preview) . ".\n\n"
                . 'Qual o título da reunião? (ou digite «ok» para usar «Reunião»)',
            'tool' => 'rooms.wizard',
            'data' => [
                'ui' => [
                    'type' => 'rooms_wizard',
                    'step' => 'pick_title',
                    'layout' => 'row',
                    'options' => [
                        ['value' => 'ok', 'label' => 'Usar «Reunião»'],
                    ],
                    'nav' => [
                        ['value' => 'voltar', 'label' => 'Voltar'],
                        ['value' => 'outra data', 'label' => 'Outra data'],
                        ['value' => 'cancelar agendamento', 'label' => 'Cancelar'],
                    ],
                ],
            ],
            'provider' => 'local-rules',
        ];
    }

    /**
     * @param array<string, mixed> $state
     * @return array{resposta:string, tool:string, data?:mixed, provider:string}
     */
    private function stepPickTitle(int $userId, string $message, array $state): array
    {
        $title = trim($message);
        if ($title === '' || preg_match('/^(ok|sim|pode|reuni[aã]o)$/iu', $title)) {
            $title = 'Reunião';
        }

        if (!$this->rooms->userCanReserve()) {
            $this->clear();

            return [
                'resposta' => 'Sem permissão CreateBooking para concluir a reserva.',
                'tool' => 'rooms.wizard',
                'provider' => 'local-rules',
            ];
        }

        $roomId = (int) ($state['room_id'] ?? 0);
        $roomName = (string) ($state['room_name'] ?? 'Sala');
        $ranges = $state['ranges'] ?? [];
        if (!is_array($ranges) || $ranges === []) {
            $this->clear();

            return [
                'resposta' => 'Fluxo incompleto. Digite «agendar» para recomeçar.',
                'tool' => 'rooms.wizard',
                'provider' => 'local-rules',
            ];
        }

        $created = [];
        $errors = [];
        foreach ($ranges as $range) {
            $run = $this->rooms->reserve(
                $userId,
                '#' . $roomId,
                (string) $range['start'],
                (string) $range['end'],
                $title,
                null
            );
            if (!empty($run['ok'])) {
                $created[] = $run;
            } else {
                $errors[] = $run['resposta'] ?? 'Falha ao reservar.';
            }
        }

        $this->clear();

        if ($created === []) {
            return [
                'resposta' => "Não foi possível reservar.\n• " . implode("\n• ", $errors),
                'tool' => 'rooms.wizard',
                'provider' => 'local-rules',
            ];
        }

        $lines = ['Reserva(s) criada(s) em «' . $roomName . '»:'] ;
        foreach ($created as $c) {
            $d = $c['data'] ?? [];
            $lines[] = sprintf(
                '• #%d %s – %s — %s [%s]',
                (int) ($d['booking_id'] ?? 0),
                date('d/m/Y H:i', strtotime((string) ($d['start_datetime'] ?? '')) ?: time()),
                date('H:i', strtotime((string) ($d['end_datetime'] ?? '')) ?: time()),
                (string) ($d['title'] ?? $title),
                (string) ($d['status'] ?? '')
            );
        }
        if ($errors !== []) {
            $lines[] = '';
            $lines[] = 'Alguns horários falharam:';
            foreach ($errors as $e) {
                $lines[] = '• ' . $e;
            }
        }

        return [
            'resposta' => implode("\n", $lines),
            'tool' => 'rooms.wizard',
            'data' => [
                'bookings' => array_map(static fn ($c) => $c['data'] ?? [], $created),
            ],
            'provider' => 'local-rules',
        ];
    }

    /**
     * @return array{resposta:string, tool:string, data?:mixed, provider:string}
     */
    private function goBack(int $userId): array
    {
        $state = $this->getState();
        if (!is_array($state)) {
            return $this->start($userId);
        }
        $step = (string) ($state['step'] ?? '');
        if ($step === 'pick_title') {
            $state['step'] = 'pick_slots';
            $this->setState($state);

            return [
                'resposta' => 'Escolha de novo o(s) horário(s).',
                'tool' => 'rooms.wizard',
                'data' => [
                    'ui' => [
                        'type' => 'rooms_wizard',
                        'step' => 'pick_slots',
                        'layout' => 'chips',
                        'multi' => true,
                        'room' => $this->roomUiFromState($state),
                        'options' => $this->optionsFromSlotMap($state['slot_map'] ?? []),
                        'nav' => [
                            ['value' => 'outra data', 'label' => 'Outra data'],
                            ['value' => 'voltar', 'label' => 'Voltar'],
                            ['value' => 'cancelar agendamento', 'label' => 'Cancelar'],
                        ],
                    ],
                ],
                'provider' => 'local-rules',
            ];
        }
        if ($step === 'pick_slots') {
            return $this->askDateAgain($state);
        }

        return $this->start($userId);
    }

    /**
     * @param list<array{start:string,end:string}> $slots
     * @return list<array{start:string,end:string}>
     */
    private function mergeConsecutiveSlots(array $slots, string $day): array
    {
        if ($slots === []) {
            return [];
        }
        $ranges = [];
        $curStart = $day . ' ' . $slots[0]['start'] . ':00';
        $curEnd = $day . ' ' . $slots[0]['end'] . ':00';
        for ($i = 1, $c = count($slots); $i < $c; $i++) {
            $nextStart = $day . ' ' . $slots[$i]['start'] . ':00';
            $nextEnd = $day . ' ' . $slots[$i]['end'] . ':00';
            if ($nextStart === $curEnd) {
                $curEnd = $nextEnd;
            } else {
                $ranges[] = ['start' => $curStart, 'end' => $curEnd];
                $curStart = $nextStart;
                $curEnd = $nextEnd;
            }
        }
        $ranges[] = ['start' => $curStart, 'end' => $curEnd];

        return $ranges;
    }

    /**
     * @return list<int>
     */
    private function parseSlotSelection(string $normalized): array
    {
        $normalized = str_replace([' e ', ';'], [',', ','], $normalized);
        $normalized = trim($normalized);
        // Evita tratar "10/08/2026" como índices 10, 8 e 2026.
        if (
            preg_match('/\bhoje\b|\bamanh[aã]\b/u', $normalized)
            || preg_match('/\d{1,2}\/\d{1,2}(?:\/\d{2,4})?/', $normalized)
        ) {
            return [];
        }
        $out = [];
        if (preg_match('/^(\d+)\s*[-–]\s*(\d+)$/', $normalized, $mm)) {
            $a = (int) $mm[1];
            $b = (int) $mm[2];
            // Faixa de slots: valores baixos (ex.: 3-5). Datas tipo 10-08-2026 não entram aqui.
            if ($a > 48 || $b > 48) {
                return [];
            }
            if ($a > $b) {
                [$a, $b] = [$b, $a];
            }
            for ($i = $a; $i <= $b; $i++) {
                $out[] = $i;
            }

            return $out;
        }
        if (preg_match('/^\d+(?:\s*,\s*\d+)*$/', $normalized) && preg_match_all('/\d+/', $normalized, $mm)) {
            foreach ($mm[0] as $n) {
                $v = (int) $n;
                if ($v < 1 || $v > 48) {
                    continue;
                }
                $out[] = $v;
            }
        }

        return array_values(array_unique($out));
    }

    private function parseDay(string $normalized): ?string
    {
        if (preg_match('/\bhoje\b/u', $normalized)) {
            return date('Y-m-d');
        }
        if (preg_match('/\bamanh[aã]\b/u', $normalized)) {
            return date('Y-m-d', strtotime('+1 day') ?: time());
        }
        if (preg_match('/\b(\d{1,2})\/(\d{1,2})(?:\/(\d{2,4}))?\b/', $normalized, $mm)) {
            $d = (int) $mm[1];
            $m = (int) $mm[2];
            $y = isset($mm[3]) && $mm[3] !== '' ? (int) $mm[3] : (int) date('Y');
            if ($y < 100) {
                $y += 2000;
            }
            if (!checkdate($m, $d, $y)) {
                return null;
            }

            return sprintf('%04d-%02d-%02d', $y, $m, $d);
        }

        return null;
    }

    /**
     * @param array<int|string, array{id:int,name:string}> $map
     * @return list<array{value:string,label:string,image_url:?string}>
     */
    private function optionsFromRoomMap(array $map): array
    {
        $options = [];
        foreach ($map as $n => $room) {
            $full = $this->rooms->resolveRoom('#' . (int) $room['id']);
            $imagePath = is_array($full) ? ($full['image'] ?? null) : null;
            $cap = is_array($full) ? (int) ($full['capacity'] ?? 0) : 0;
            $building = is_array($full) ? trim((string) ($full['building'] ?? '')) : '';
            $loc = is_array($full) ? trim((string) ($full['location'] ?? '')) : '';
            $options[] = [
                'value' => (string) $n,
                'label' => $n . ' — ' . (string) $room['name'],
                'sub' => implode(' · ', $this->rooms->formatRoomPlaceExtras($cap, $building, $loc)),
                'image_url' => $this->imageUrl(is_string($imagePath) ? $imagePath : null),
            ];
        }

        return $options;
    }

    /**
     * @param list<array<string, mixed>> $daySlots
     * @return array{slot_map:array<int, array<string, mixed>>, options:list<array<string, mixed>>, free_count:int, busy_count:int}
     */
    private function buildSlotMapAndOptions(array $daySlots): array
    {
        $slotMap = [];
        $options = [];
        $freeCount = 0;
        $busyCount = 0;
        $n = 1;
        foreach ($daySlots as $slot) {
            if (!is_array($slot)) {
                continue;
            }
            $slotMap[$n] = $slot;
            $options[] = $this->optionFromSlot($n, $slot);
            if (!empty($slot['available'])) {
                $freeCount++;
            } elseif (($slot['status'] ?? '') === 'busy') {
                $busyCount++;
            }
            $n++;
        }

        return [
            'slot_map' => $slotMap,
            'options' => $options,
            'free_count' => $freeCount,
            'busy_count' => $busyCount,
        ];
    }

    /**
     * @param array<int|string, array<string, mixed>> $map
     * @return list<array<string, mixed>>
     */
    private function optionsFromSlotMap(array $map): array
    {
        $options = [];
        foreach ($map as $n => $slot) {
            if (!is_array($slot)) {
                continue;
            }
            $options[] = $this->optionFromSlot((int) $n, $slot);
        }

        return $options;
    }

    /**
     * @param array<string, mixed> $slot
     * @return array<string, mixed>
     */
    private function optionFromSlot(int $n, array $slot): array
    {
        $start = (string) ($slot['start'] ?? '');
        $end = (string) ($slot['end'] ?? '');
        $status = (string) ($slot['status'] ?? (!empty($slot['available']) ? 'free' : 'busy'));
        $opt = [
            'value' => (string) $n,
            'label' => $n . ' · ' . $start,
            'index' => (string) $n,
            'time' => $start,
            'sub' => $start . '–' . $end,
            'status' => $status,
            'disabled' => empty($slot['available']),
        ];
        if ($status === 'busy') {
            $who = trim((string) ($slot['reserved_by'] ?? '')) ?: 'Reservado';
            $shortWho = $this->shortPersonName($who);
            $dept = trim((string) ($slot['reserved_department'] ?? ''));
            $opt['label'] = $start . ' · ' . $shortWho;
            $opt['index'] = null;
            $opt['time'] = $start;
            $opt['sub'] = $dept !== '' ? $dept : $who;
            $opt['reserved_by'] = $who;
            $opt['department'] = $dept !== '' ? $dept : null;
            $opt['title'] = 'Ocupado por ' . $who . ($dept !== '' ? ' — ' . $dept : '');
        } elseif ($status === 'past') {
            $opt['label'] = $start . ' · passou';
            $opt['index'] = null;
            $opt['sub'] = 'Já passou';
            $opt['title'] = $start . '–' . $end . ' (já passou)';
        } else {
            $opt['title'] = $start . '–' . $end . ' (livre)';
        }

        return $opt;
    }

    private function shortPersonName(string $fullName): string
    {
        $fullName = trim(preg_replace('/\s+/u', ' ', $fullName) ?? $fullName);
        if ($fullName === '') {
            return 'Reservado';
        }
        $parts = preg_split('/\s+/u', $fullName) ?: [];
        if (count($parts) <= 2) {
            return $fullName;
        }

        return $parts[0] . ' ' . $parts[count($parts) - 1];
    }

    private function imageUrl(?string $imagePath): ?string
    {
        $imagePath = trim((string) $imagePath);
        if ($imagePath === '') {
            return null;
        }
        $base = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/');

        return $base . '/serve-file?path=' . ImageHelper::encodePathForServeFile($imagePath);
    }

    private function resolveRoomImageUrl(int $roomId): ?string
    {
        if ($roomId < 1) {
            return null;
        }
        $full = $this->rooms->resolveRoom('#' . $roomId);
        $imagePath = is_array($full) ? ($full['image'] ?? null) : null;

        return $this->imageUrl(is_string($imagePath) ? $imagePath : null);
    }

    /**
     * @param array<string, mixed> $state
     * @return array{name:string, image_url:?string}
     */
    private function roomUiFromState(array $state): array
    {
        $name = trim((string) ($state['room_name'] ?? 'Sala')) ?: 'Sala';
        $imageUrl = $state['room_image_url'] ?? null;
        if (($imageUrl === null || $imageUrl === '') && !empty($state['room_id'])) {
            $imageUrl = $this->resolveRoomImageUrl((int) $state['room_id']);
        }

        return [
            'name' => $name,
            'image_url' => is_string($imageUrl) && $imageUrl !== '' ? $imageUrl : null,
        ];
    }

    /** @return array<string, mixed>|null */
    private function getState(): ?array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return null;
        }
        $state = $_SESSION[self::SESSION_KEY] ?? null;

        return is_array($state) ? $state : null;
    }

    /** @param array<string, mixed> $state */
    private function setState(array $state): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION[self::SESSION_KEY] = $state;
        }
    }
}
