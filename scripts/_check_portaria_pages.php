<?php
require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->load();
$pdo = new PDO(
    'mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_NAME'] . ';port=' . ($_ENV['DB_PORT'] ?? 3306),
    $_ENV['DB_USER'],
    $_ENV['DB_PASS']
);
$rows = $pdo->query("SELECT controller, controller_url, page_status, public_page FROM adms_pages WHERE directory = 'portaria' ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
echo "pages=" . count($rows) . PHP_EOL;
foreach ($rows as $r) {
    echo ($r['controller'] ?? '') . ' | ' . ($r['controller_url'] ?? '') . ' | status=' . ($r['page_status'] ?? '') . PHP_EOL;
}
$ver = @file_get_contents(dirname(__DIR__) . '/storage/cache/system/menu_permission_version.txt');
echo 'menu_version=' . trim((string) $ver) . PHP_EOL;
