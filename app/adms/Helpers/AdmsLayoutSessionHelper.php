<?php

declare(strict_types=1);

namespace App\adms\Helpers;

use App\adms\Models\Repository\AdmsPasswordPolicyRepository;
use App\adms\Models\Repository\AdmsSessionsRepository;

/**
 * Validação de sessão e heartbeat com cache leve — evita SELECT + UPDATE em toda troca de página.
 */
final class AdmsLayoutSessionHelper
{
    private const SESSION_CACHE_KEY = 'adms_layout_session_cache';
    private const VALIDATE_TTL_SECONDS = 45;
    private const ACTIVITY_WRITE_INTERVAL_SECONDS = 60;

    /**
     * @return array{limite: int, lock_offset_minutes: int}
     */
    public static function validateAndTouchSession(): array
    {
        $defaults = ['limite' => 1800, 'lock_offset_minutes' => 1];

        if (!isset($_SESSION['user_id'], $_SESSION['session_id'])) {
            return $defaults;
        }

        $userId = (int) $_SESSION['user_id'];
        $sessionId = (string) $_SESSION['session_id'];
        $policy = self::getPasswordPolicySnapshot();
        $limite = ($policy['expirar_por_tempo'] && $policy['limite_segundos'] > 0)
            ? $policy['limite_segundos']
            : 1800;
        $lockOffset = $policy['lock_offset_minutes'];

        $cached = $_SESSION[self::SESSION_CACHE_KEY] ?? null;
        $now = time();
        if (
            is_array($cached)
            && (int) ($cached['user_id'] ?? 0) === $userId
            && (string) ($cached['session_id'] ?? '') === $sessionId
            && (int) ($cached['validated_at'] ?? 0) + self::VALIDATE_TTL_SECONDS > $now
            && ($cached['status'] ?? '') === 'ativa'
        ) {
            if (self::isSessionExpiredByPolicy($cached, $policy)) {
                self::forceLogoutExpired($userId, $sessionId);
            }

            if ((int) ($cached['activity_written_at'] ?? 0) + self::ACTIVITY_WRITE_INTERVAL_SECONDS <= $now) {
                (new AdmsSessionsRepository())->updateSessionActivity($userId, $sessionId);
                $cached['activity_written_at'] = $now;
                $cached['updated_at'] = date('Y-m-d H:i:s');
                $_SESSION[self::SESSION_CACHE_KEY] = $cached;
            }

            return ['limite' => $limite, 'lock_offset_minutes' => $lockOffset];
        }

        $sessionRepo = new AdmsSessionsRepository();
        $sess = $sessionRepo->getSessionByUserIdAndSessionId($userId, $sessionId);

        if (!$sess || ($sess['status'] ?? null) !== 'ativa') {
            self::invalidatePhpSessionAndRedirect($sess);
        }

        if (self::isSessionExpiredByPolicy([
            'updated_at' => $sess['updated_at'] ?? $sess['created_at'] ?? null,
        ], $policy)) {
            self::forceLogoutExpired($userId, $sessionId);
        }

        $sessionRepo->updateSessionActivity($userId, $sessionId);
        $_SESSION[self::SESSION_CACHE_KEY] = [
            'user_id' => $userId,
            'session_id' => $sessionId,
            'status' => 'ativa',
            'validated_at' => $now,
            'activity_written_at' => $now,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        return ['limite' => $limite, 'lock_offset_minutes' => $lockOffset];
    }

    public static function clearCache(): void
    {
        unset($_SESSION[self::SESSION_CACHE_KEY]);
    }

    /**
     * @param array<string, mixed>|null $sess
     */
    private static function invalidatePhpSessionAndRedirect(?array $sess): void
    {
        $motivos = [];
        if (!$sess) {
            $motivos[] = 'Sessão não encontrada no banco';
        } else {
            $motivos[] = 'Sessão inativa';
        }

        $msg = implode(' e ', $motivos) . '! Contate o Administrador do sistema.';
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }

        if (!str_contains($_SERVER['REQUEST_URI'] ?? '', 'login')) {
            header('Location: ' . ($_ENV['URL_ADM'] ?? '/') . 'login?msg=' . urlencode($msg));
            exit;
        }
    }

  /**
     * @param array<string, mixed> $sessionRow
     * @param array{expirar_por_tempo: bool, limite_segundos: int, lock_offset_minutes: int} $policy
     */
    private static function isSessionExpiredByPolicy(array $sessionRow, array $policy): bool
    {
        if (!$policy['expirar_por_tempo'] || $policy['limite_segundos'] <= 0) {
            return false;
        }

        $updatedAt = $sessionRow['updated_at'] ?? null;
        if (!is_string($updatedAt) || $updatedAt === '') {
            return false;
        }

        $ultimaAtividade = strtotime($updatedAt);
        if ($ultimaAtividade === false) {
            return false;
        }

        return (time() - $ultimaAtividade) > $policy['limite_segundos'];
    }

    /**
     * @return array{expirar_por_tempo: bool, limite_segundos: int, lock_offset_minutes: int}
     */
    private static function getPasswordPolicySnapshot(): array
    {
        try {
            $policy = (new AdmsPasswordPolicyRepository())->getPolicy();
        } catch (\Throwable) {
            $policy = null;
        }

        $expirar = $policy && isset($policy->expirar_sessao_por_tempo)
            && $policy->expirar_sessao_por_tempo === 'Sim';

        return [
            'expirar_por_tempo' => $expirar,
            'limite_segundos' => ($policy && isset($policy->tempo_expiracao_sessao))
                ? max(60, (int) $policy->tempo_expiracao_sessao * 60)
                : 1800,
            'lock_offset_minutes' => ($policy && isset($policy->tempo_bloqueio_tela))
                ? (int) $policy->tempo_bloqueio_tela
                : 1,
        ];
    }

    private static function forceLogoutExpired(int $userId, string $sessionId): void
    {
        (new AdmsSessionsRepository())->invalidateSessionByUserIdAndSessionId($userId, $sessionId);
        self::clearCache();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        header('Location: ' . ($_ENV['URL_ADM'] ?? '/') . 'login?error=' . urlencode(
            'Sua sessão expirou por inatividade. Faça login novamente.'
        ));
        exit;
    }
}
