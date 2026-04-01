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
        $usersRepo = new UsersRepository();
        $timelineRepo = new TimelineRepository();
        $institutionalUserId = self::resolveInstitutionalUserId($usersRepo);

        $baseUrl = rtrim((string)($_ENV['URL_ADM'] ?? ''), '/');

        // Aniversariantes do dia (data_nascimento)
        $hojeDM = date('d/m');
        $sqlBirthday = 'SELECT u.id, u.name, u.username, d.name AS departamento
                        FROM adms_users u
                        LEFT JOIN adms_departments d ON u.user_department_id = d.id
                        WHERE u.status = 1
                          AND u.data_nascimento IS NOT NULL
                          AND DATE_FORMAT(u.data_nascimento, "%d/%m") = :hoje';
        $stmtB = $usersRepo->getConnection()->prepare($sqlBirthday);
        $stmtB->execute([':hoje' => $hojeDM]);
        $birthdaysToday = $stmtB->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        if ($birthdaysToday !== []) {
            // Se já existir um post institucional de aniversário hoje, não cria outro.
            if (self::hasInstitutionalPostToday($timelineRepo, $institutionalUserId, '👏 Parabéns para:')) {
                return;
            }
            $lines = [];
            foreach ($birthdaysToday as $item) {
                $username = trim((string)($item['username'] ?? ''));
                if ($username === '') {
                    continue;
                }
                $lines[] = '@' . $username;
            }
            $countBirthdays = count($lines);
            $intro  = "🎉🎂 Hoje é dia de celebrar! 🎂🎉\n\n";
            $intro .= "👏 Parabéns para:\n";
            $bulletList = '🔹 ' . implode("\n🔹 ", $lines);
            $footer  = "\n\n💚 Desejamos um dia incrível, cheio de alegrias e conquistas!\n\n";
            $footer .= "🚀 Que este novo ciclo venha com ainda mais sucesso!";
            $content = $intro . $bulletList . $footer;

            if ($institutionalUserId > 0) {
                $timelineRepo->createPost($institutionalUserId, $content, null, null, null, 'regular');
            }
        }

        // Tempo de empresa do dia (data_admissao)
        $sqlTenure = 'SELECT u.id, u.name, u.username, u.data_admissao, d.name AS departamento
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
                $username = trim((string)($item['username'] ?? ''));
                $dept     = trim((string)($item['departamento'] ?? ''));
                $anos = null;
                if (!empty($item['data_admissao'])) {
                    $anoAdm = (int)date('Y', strtotime((string)$item['data_admissao']));
                    if ($anoAdm > 0 && $anoAtual >= $anoAdm) {
                        $anos = max(0, $anoAtual - $anoAdm);
                    }
                }
                if ($username === '') {
                    continue;
                }
                $label = '@' . $username;
                if ($dept !== '') {
                    $label .= ' (' . $dept . ')';
                }
                if ($anos !== null) {
                    $label .= ' — ' . $anos . ' ano(s)';
                }

                $lines[] = $label;
            }
            $countTenure = count($lines);
            $intro  = "🎉🎂 Hoje é dia de comemorar! 🎂🎉\n\n";
            $intro .= "Tem gente fazendo história por aqui 👏\n\n";
            $intro .= "🔥 Tempo de empresa:\n";
            $bulletList = '🔹 ' . implode("\n🔹 ", $lines);
            $footer = "\n\n💚 Valeu demais pela parceria, dedicação e por fazerem parte do time!\n\n🚀 Bora pra mais anos juntos!";
            $content = $intro . $bulletList . $footer;

            if ($institutionalUserId > 0) {
                // Se já existir um post institucional de tempo de empresa hoje, não cria outro.
                if (self::hasInstitutionalPostToday($timelineRepo, $institutionalUserId, '🔥 Tempo de empresa:')) {
                    return;
                }
                $timelineRepo->createPost($institutionalUserId, $content, null, null, null, 'regular');
            }
        }
    }

    /**
     * Verifica se já existe hoje um post institucional do usuário informado
     * cujo conteúdo comece com o marcador passado.
     */
    private static function hasInstitutionalPostToday(
        TimelineRepository $timelineRepo,
        int $authorUserId,
        string $contentPrefix
    ): bool {
        if ($authorUserId <= 0 || $contentPrefix === '') {
            return false;
        }

        $today = date('Y-m-d');
        $sql = 'SELECT 1
                FROM adms_timeline_posts
                WHERE user_id = :uid
                  AND status = "active"
                  AND DATE(created_at) = :today
                  AND content LIKE :prefix
                LIMIT 1';

        $stmt = $timelineRepo->getConnection()->prepare($sql);
        $stmt->execute([
            ':uid'    => $authorUserId,
            ':today'  => $today,
            // Procura o marcador em qualquer parte do conteúdo,
            // não apenas no início da mensagem.
            ':prefix' => '%' . $contentPrefix . '%',
        ]);

        return (bool)$stmt->fetchColumn();
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

