<?php

declare(strict_types=1);

namespace App\adms\Helpers;

use App\adms\Models\Repository\UsersRepository;

/**
 * Usuário institucional do sistema (ex.: manager / Grupo Tiaraju).
 *
 * Não é colaborador: pode receber notificações e executar ações automáticas,
 * mas não exige ciência/leitura obrigatória nem entra em indicadores de pessoas.
 */
final class InstitutionalSystemUserHelper
{
    public const INSTITUTIONAL_USERNAME = 'manager';

    /** Nomes em adms_users.name (comparados com TRIM) */
    public const INSTITUTIONAL_DISPLAY_NAMES = [
        'Grupo Tiaraju',
    ];

    /** @var list<int>|null */
    private static ?array $resolvedIds = null;

    public static function isInstitutionalSession(): bool
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId > 0 && self::isInstitutionalUserId($userId)) {
            return true;
        }

        $username = strtolower(trim((string) ($_SESSION['user_username'] ?? $_SESSION['username'] ?? '')));
        if ($username !== '' && $username === strtolower(self::INSTITUTIONAL_USERNAME)) {
            return true;
        }

        return false;
    }

    /**
     * @param array<string, mixed>|null $user Linha de adms_users (id, username, name).
     */
    public static function isInstitutionalUser(?array $user): bool
    {
        if ($user === null || $user === []) {
            return false;
        }

        $id = (int) ($user['id'] ?? 0);
        if ($id > 0 && self::isInstitutionalUserId($id)) {
            return true;
        }

        $username = strtolower(trim((string) ($user['username'] ?? '')));
        if ($username !== '' && $username === strtolower(self::INSTITUTIONAL_USERNAME)) {
            return true;
        }

        $name = trim((string) ($user['name'] ?? ''));
        if ($name !== '' && in_array($name, self::INSTITUTIONAL_DISPLAY_NAMES, true)) {
            return true;
        }

        return false;
    }

    public static function isInstitutionalUserId(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        return in_array($userId, self::resolveInstitutionalUserIds(), true);
    }

    /** Isento de ciência/leitura obrigatória e de indicadores de pendência. */
    public static function isExemptFromAcknowledgment(int $userId): bool
    {
        return self::isInstitutionalUserId($userId);
    }

    /**
     * Remove usuários institucionais de listas usadas em relatórios e KPIs.
     *
     * @param list<array<string, mixed>> $users
     * @return list<array<string, mixed>>
     */
    public static function filterReportUsers(array $users): array
    {
        return array_values(array_filter(
            $users,
            static fn (array $u): bool => !self::isInstitutionalUser($u)
        ));
    }

    /**
     * Condição SQL: coluna de user_id não pertence a contas institucionais.
     *
     * @param string $userIdColumn ex.: "u.id" ou "l.user_id"
     */
    public static function sqlExcludeUserIdColumn(string $userIdColumn): string
    {
        return "{$userIdColumn} NOT IN (" . self::sqlInstitutionalUserIdsSubquery() . ')';
    }

    /**
     * Condição SQL sobre alias de adms_users (ex.: u).
     */
    public static function sqlExcludeUsersAlias(string $userAlias = 'u'): string
    {
        return self::sqlExcludeUserIdColumn("{$userAlias}.id");
    }

    /**
     * @return list<int>
     */
    public static function resolveInstitutionalUserIds(): array
    {
        if (self::$resolvedIds !== null) {
            return self::$resolvedIds;
        }

        self::$resolvedIds = [];

        try {
            $namesIn = self::sqlQuotedNameList();
            $username = str_replace("'", "''", strtolower(self::INSTITUTIONAL_USERNAME));
            $sql = 'SELECT id FROM adms_users WHERE LOWER(TRIM(username)) = :manager';
            if ($namesIn !== '') {
                $sql .= " OR TRIM(name) IN ({$namesIn})";
            }

            $stmt = (new UsersRepository())->getConnection()->prepare($sql);
            $stmt->bindValue(':manager', $username);
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            foreach ($rows as $row) {
                $id = (int) ($row['id'] ?? 0);
                if ($id > 0) {
                    self::$resolvedIds[] = $id;
                }
            }
            self::$resolvedIds = array_values(array_unique(self::$resolvedIds));
        } catch (\Throwable) {
            self::$resolvedIds = [];
        }

        return self::$resolvedIds;
    }

    private static function sqlInstitutionalUserIdsSubquery(): string
    {
        $username = str_replace("'", "''", strtolower(self::INSTITUTIONAL_USERNAME));
        $namesIn = self::sqlQuotedNameList();
        $sql = "SELECT id FROM adms_users WHERE LOWER(TRIM(username)) = '{$username}'";
        if ($namesIn !== '') {
            $sql .= " OR TRIM(name) IN ({$namesIn})";
        }

        return $sql;
    }

    private static function sqlQuotedNameList(): string
    {
        $parts = [];
        foreach (self::INSTITUTIONAL_DISPLAY_NAMES as $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }
            $parts[] = "'" . str_replace("'", "''", $name) . "'";
        }

        return implode(',', $parts);
    }
}
