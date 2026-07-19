<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use PDO;
use PDOException;

/**
 * Configuração do portal público de vagas (CAPTCHA independente do canal de denúncias).
 */
class RhVagasPublicasConfigRepository extends DbConnection
{
    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        try {
            $stmt = $this->getConnection()->query(
                'SELECT * FROM rh_vagas_publicas_config ORDER BY id ASC LIMIT 1'
            );
            $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;

            return is_array($row) ? $row : [];
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao ler rh_vagas_publicas_config.', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function isCaptchaEnabled(): bool
    {
        return (int) ($this->getConfig()['captcha_enabled'] ?? 0) === 1;
    }

    public function getCaptchaProvider(): string
    {
        $p = strtolower(trim((string) ($this->getConfig()['captcha_provider'] ?? 'hcaptcha')));

        return $p === 'recaptcha' ? 'recaptcha' : 'hcaptcha';
    }

    public function getCaptchaSiteKey(): string
    {
        return trim((string) ($this->getConfig()['captcha_site_key'] ?? ''));
    }

    public function getCaptchaSecretKey(): string
    {
        return trim((string) ($this->getConfig()['captcha_secret_key'] ?? ''));
    }

    /**
     * @param array{
     *   captcha_enabled?: bool|int|string,
     *   captcha_provider?: string,
     *   captcha_site_key?: string,
     *   captcha_secret_key?: string
     * } $data
     */
    public function saveCaptcha(array $data): bool
    {
        $provider = strtolower(trim((string) ($data['captcha_provider'] ?? 'hcaptcha')));
        if (!in_array($provider, ['hcaptcha', 'recaptcha'], true)) {
            $provider = 'hcaptcha';
        }

        $enabled = !empty($data['captcha_enabled']) ? 1 : 0;
        $siteKey = trim((string) ($data['captcha_site_key'] ?? ''));
        $secretKey = trim((string) ($data['captcha_secret_key'] ?? ''));

        // Mantém secret anterior se o campo vier vazio (não obrigar reenvio a cada save).
        if ($secretKey === '') {
            $secretKey = $this->getCaptchaSecretKey();
        }

        try {
            $pdo = $this->getConnection();
            $existing = $this->getConfig();

            if ($existing === []) {
                $stmt = $pdo->prepare(
                    'INSERT INTO rh_vagas_publicas_config
                        (captcha_enabled, captcha_provider, captcha_site_key, captcha_secret_key, created_at, updated_at)
                     VALUES
                        (:enabled, :provider, :site_key, :secret_key, NOW(), NOW())'
                );
            } else {
                $stmt = $pdo->prepare(
                    'UPDATE rh_vagas_publicas_config SET
                        captcha_enabled = :enabled,
                        captcha_provider = :provider,
                        captcha_site_key = :site_key,
                        captcha_secret_key = :secret_key,
                        updated_at = NOW()
                     WHERE id = :id'
                );
                $stmt->bindValue(':id', (int) $existing['id'], PDO::PARAM_INT);
            }

            $stmt->bindValue(':enabled', $enabled, PDO::PARAM_INT);
            $stmt->bindValue(':provider', $provider, PDO::PARAM_STR);
            $stmt->bindValue(':site_key', $siteKey !== '' ? $siteKey : null, $siteKey !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':secret_key', $secretKey !== '' ? $secretKey : null, $secretKey !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);

            return $stmt->execute();
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao salvar CAPTCHA do portal de vagas.', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
