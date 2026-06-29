<?php

declare(strict_types=1);

/**
 * Bootstrap mínimo igual ao index.php (sem sessão/rotas).
 * Usado por scripts CLI disparados pela interface web.
 */
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

chdir(APP_ROOT);

require APP_ROOT . '/vendor/autoload.php';

Dotenv\Dotenv::createUnsafeImmutable(APP_ROOT)->load();

date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'UTC');
