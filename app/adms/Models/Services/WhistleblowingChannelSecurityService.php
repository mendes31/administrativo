<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\WhistleblowingConfigRepository;

/**
 * Disponibilidade do canal público e validação da chave de criptografia.
 */
final class WhistleblowingChannelSecurityService
{
    public const MIN_KEY_LENGTH = 32;

    public static function hasStrongEncryptionKey(): bool
    {
        $repo = new WhistleblowingConfigRepository();
        if (strlen($repo->getEncryptionKey()) >= self::MIN_KEY_LENGTH) {
            return true;
        }

        $envKey = trim((string) ($_ENV['WHISTLEBLOWING_ENCRYPTION_KEY'] ?? ''));

        return strlen($envKey) >= self::MIN_KEY_LENGTH;
    }

    public static function isPublicChannelAvailable(): bool
    {
        return self::hasStrongEncryptionKey();
    }

    public static function publicUnavailableMessage(): string
    {
        return 'O canal está temporariamente indisponível por configuração de segurança. '
            . 'A equipe de Compliance está ajustando a criptografia. Tente novamente mais tarde '
            . 'ou utilize os canais alternativos informados pela empresa.';
    }
}
