<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\TimelineRepository;
use App\adms\Models\Repository\UsersRepository;

class TimelineCelebrationsService
{
    /**
     * Gera, no primeiro acesso do dia à Timeline, os posts institucionais de:
     * - aniversariantes do dia
     * - tempo de empresa do dia
     *
     * Usa uma chave em sessão para não repetir o processamento no mesmo request/usuário.
     */
    public static function ensureTodayPostsCreated(): void
    {
        $sessionKey = 'timeline_celebrations_last_run';
        $today = date('Y-m-d');
        if (!empty($_SESSION[$sessionKey]) && $_SESSION[$sessionKey] === $today) {
            return;
        }

        $usersRepo = new UsersRepository();
        $timelineRepo = new TimelineRepository();
        $institutionalUserId = self::resolveInstitutionalUserId($usersRepo);

        // Aniversariantes do dia (data_nascimento)
        $hojeDM = date('d/m');
        $sqlBirthday = 'SELECT u.id, u.name, d.name AS departamento
                        FROM adms_users u
                        LEFT JOIN adms_departments d ON u.user_department_id = d.id
                        WHERE u.status = 1
                          AND u.data_nascimento IS NOT NULL
                          AND DATE_FORMAT(u.data_nascimento, "%d/%m") = :hoje';
        $stmtB = $usersRepo->getConnection()->prepare($sqlBirthday);
        $stmtB->execute([':hoje' => $hojeDM]);
        $birthdaysToday = $stmtB->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        if ($birthdaysToday !== []) {
            $lines = [];
            foreach ($birthdaysToday as $item) {
                $name = (string)($item['name'] ?? '');
                $dept = trim((string)($item['departamento'] ?? ''));
                $label = $name;
                if ($dept !== '') {
                    $label .= ' (' . $dept . ')';
                }
                $lines[] = $label;
            }
            $countBirthdays = count($lines);
            if ($countBirthdays === 1) {
                $content = "🎉 Hoje é aniversário de:\n- " . $lines[0] . "\n\nParabéns!";
            } else {
                $content = "🎉 Hoje é aniversário de:\n- " . implode("\n- ", $lines) . "\n\nParabéns a todos!";
            }

            if ($institutionalUserId > 0) {
                $timelineRepo->createPost($institutionalUserId, $content, null, null, null, 'regular');
            }
        }

        // Tempo de empresa do dia (data_admissao)
        $sqlTenure = 'SELECT u.id, u.name, u.data_admissao, d.name AS departamento
                      FROM adms_users u
                      LEFT JOIN adms_departments d ON u.user_department_id = d.id
                      WHERE u.status = 1
                        AND u.data_admissao IS NOT NULL
                        AND DATE_FORMAT(u.data_admissao, "%d/%m") = :hoje';
        $stmtT = $usersRepo->getConnection()->prepare($sqlTenure);
        $stmtT->execute([':hoje' => $hojeDM]);
        $tenureToday = $stmtT->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        if ($tenureToday !== []) {
            $anoAtual = (int)date('Y');
            $lines = [];
            foreach ($tenureToday as $item) {
                $name = (string)($item['name'] ?? '');
                $dept = trim((string)($item['departamento'] ?? ''));
                $anos = null;
                if (!empty($item['data_admissao'])) {
                    $anoAdm = (int)date('Y', strtotime((string)$item['data_admissao']));
                    if ($anoAdm > 0 && $anoAtual >= $anoAdm) {
                        $anos = max(0, $anoAtual - $anoAdm);
                    }
                }
                $label = $name;
                if ($dept !== '') {
                    $label .= ' (' . $dept . ')';
                }
                if ($anos !== null) {
                    $label .= ' – ' . $anos . ' ano(s) de empresa';
                }
                $lines[] = $label;
            }
            $countTenure = count($lines);
            if ($countTenure === 1) {
                $content = "👏 Hoje completa tempo de empresa:\n- " . $lines[0] . "\n\nParabéns pelo compromisso e dedicação!";
            } else {
                $content = "👏 Hoje completam tempo de empresa:\n- " . implode("\n- ", $lines) . "\n\nParabéns pelo compromisso e dedicação de todos!";
            }

            if ($institutionalUserId > 0) {
                $timelineRepo->createPost($institutionalUserId, $content, null, null, null, 'regular');
            }
        }

        $_SESSION[$sessionKey] = $today;
    }

    /**
     * Resolve o usuário institucional que assina as postagens da Timeline.
     * Prioridade:
     * 1) Usuário com name = 'Grupo Tiaraju'
     * 2) Usuário com username = 'manager' (fallback)
     * 3) Usuário logado atual
     */
    private static function resolveInstitutionalUserId(UsersRepository $usersRepo): int
    {
        try {
            // 1) Tenta encontrar pelo nome "Grupo Tiaraju"
            $stmt = $usersRepo->getConnection()->prepare(
                'SELECT id FROM adms_users WHERE name = :name LIMIT 1'
            );
            $stmt->execute([':name' => 'Grupo Tiaraju']);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($row && !empty($row['id'])) {
                return (int)$row['id'];
            }

            // 2) Fallback: usuário técnico "manager"
            $stmt = $usersRepo->getConnection()->prepare(
                "SELECT id FROM adms_users WHERE username = 'manager' LIMIT 1"
            );
            $stmt->execute();
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($row && !empty($row['id'])) {
                return (int)$row['id'];
            }
        } catch (\Throwable) {
            // Em caso de erro, apenas cai para o fallback do usuário logado
        }

        // 3) Último recurso: usuário logado
        return (int)($_SESSION['user_id'] ?? 0);
    }
}

