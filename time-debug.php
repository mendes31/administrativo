<?php
declare(strict_types=1);

// Script simples para diagnosticar fuso horário / horário efetivo

// Garante autoload e .env carregado
require __DIR__ . '/vendor/autoload.php';

if (!isset($_ENV['DB_HOST'])) {
    require_once __DIR__ . '/app/adms/Helpers/EnvLoader.php';
    \App\adms\Helpers\EnvLoader::load();
}

// Timezone vindo da aplicação
$appTimezone = $_ENV['APP_TIMEZONE'] ?? ini_get('date.timezone') ?: date_default_timezone_get();

// Informação de data/hora no PHP
$phpTimezoneIni   = ini_get('date.timezone') ?: '(não definido em php.ini)';
$phpTimezoneFunc  = date_default_timezone_get();
$phpNow           = date('Y-m-d H:i:s');
$phpTimestamp     = time();

// Tenta obter horário do banco (se possível)
$dbInfo = null;
try {
    $conn = (new \App\adms\Models\Services\DbConnection())->getConnection();
    $stmt = $conn->query('SELECT NOW() AS db_now, @@global.time_zone AS global_tz, @@session.time_zone AS session_tz');
    $dbInfo = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
} catch (\Throwable $e) {
    $dbInfo = ['error' => $e->getMessage()];
}

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Debug de Horário / Timezone</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        code { background: #f4f4f4; padding: 2px 4px; border-radius: 3px; }
        table { border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ccc; padding: 6px 10px; }
        th { background: #eee; text-align: left; }
    </style>
</head>
<body>
    <h2>Diagnóstico de Horário / Timezone</h2>

    <table>
        <tr>
            <th>Origem</th>
            <th>Informação</th>
        </tr>
        <tr>
            <td>APP_TIMEZONE (.env)</td>
            <td><code><?= htmlspecialchars((string)$appTimezone, ENT_QUOTES, 'UTF-8') ?></code></td>
        </tr>
        <tr>
            <td>php.ini &mdash; date.timezone</td>
            <td><code><?= htmlspecialchars((string)$phpTimezoneIni, ENT_QUOTES, 'UTF-8') ?></code></td>
        </tr>
        <tr>
            <td>PHP &mdash; date_default_timezone_get()</td>
            <td><code><?= htmlspecialchars((string)$phpTimezoneFunc, ENT_QUOTES, 'UTF-8') ?></code></td>
        </tr>
        <tr>
            <td>PHP &mdash; date('Y-m-d H:i:s')</td>
            <td><code><?= htmlspecialchars($phpNow, ENT_QUOTES, 'UTF-8') ?></code></td>
        </tr>
        <tr>
            <td>PHP &mdash; time()</td>
            <td><code><?= (string)$phpTimestamp ?></code></td>
        </tr>
        <?php if ($dbInfo): ?>
            <tr>
                <td>Banco &mdash; NOW()</td>
                <td><code><?= htmlspecialchars((string)($dbInfo['db_now'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code></td>
            </tr>
            <tr>
                <td>Banco &mdash; @@global.time_zone</td>
                <td><code><?= htmlspecialchars((string)($dbInfo['global_tz'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code></td>
            </tr>
            <tr>
                <td>Banco &mdash; @@session.time_zone</td>
                <td><code><?= htmlspecialchars((string)($dbInfo['session_tz'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code></td>
            </tr>
            <?php if (!empty($dbInfo['error'])): ?>
                <tr>
                    <td>Banco &mdash; erro</td>
                    <td><code><?= htmlspecialchars((string)$dbInfo['error'], ENT_QUOTES, 'UTF-8') ?></code></td>
                </tr>
            <?php endif; ?>
        <?php endif; ?>
        <tr>
            <td>Navegador &mdash; hora local (JS)</td>
            <td><code id="browser-time">Carregando...</code></td>
        </tr>
    </table>

    <p style="margin-top: 15px; font-size: 0.95em;">
        Abra este endereço em diferentes máquinas/navegadores para comparar se o horário do PHP, do banco e do navegador estão alinhados.
    </p>

    <script>
        (function () {
            const el = document.getElementById('browser-time');
            function update() {
                const now = new Date();
                el.textContent = now.toISOString() + ' (ISO) | ' + now.toLocaleString();
            }
            update();
            setInterval(update, 1000);
        })();
    </script>
</body>
</html>

