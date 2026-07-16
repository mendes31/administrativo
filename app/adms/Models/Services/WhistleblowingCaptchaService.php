<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\WhistleblowingConfigRepository;

/**
 * Validação de CAPTCHA (hCaptcha / reCAPTCHA v2) no registro e no acompanhamento público.
 */
final class WhistleblowingCaptchaService
{
    public function isEnabled(): bool
    {
        $repo = new WhistleblowingConfigRepository();

        return $repo->isCaptchaEnabled()
            && $repo->getCaptchaSiteKey() !== ''
            && $repo->getCaptchaSecretKey() !== '';
    }

    public function getSiteKey(): string
    {
        return (new WhistleblowingConfigRepository())->getCaptchaSiteKey();
    }

    public function getProvider(): string
    {
        return (new WhistleblowingConfigRepository())->getCaptchaProvider();
    }

    public function verifyFromRequest(): bool
    {
        if (!$this->isEnabled()) {
            return true;
        }

        $provider = $this->getProvider();
        $token = trim((string) ($_POST['h-captcha-response'] ?? $_POST['g-recaptcha-response'] ?? ''));
        if ($token === '') {
            return false;
        }

        $secret = (new WhistleblowingConfigRepository())->getCaptchaSecretKey();
        $url = $provider === 'recaptcha'
            ? 'https://www.google.com/recaptcha/api/siteverify'
            : 'https://hcaptcha.com/siteverify';

        $post = http_build_query([
            'secret' => $secret,
            'response' => $token,
            'remoteip' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $post,
                'timeout' => 10,
            ],
        ]);

        $raw = @file_get_contents($url, false, $context);
        if ($raw === false) {
            error_log('WhistleblowingCaptchaService: falha ao contactar provedor CAPTCHA');

            return false;
        }

        $json = json_decode($raw, true);

        return is_array($json) && !empty($json['success']);
    }
}
