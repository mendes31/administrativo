<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';
Dotenv\Dotenv::createUnsafeImmutable(dirname(__DIR__))->load();

$pdo = new PDO(
    sprintf('mysql:host=%s;dbname=%s', $_ENV['DB_HOST'], $_ENV['DB_NAME']),
    $_ENV['DB_USER'],
    $_ENV['DB_PASS']
);
$stmt = $pdo->query("SHOW INDEX FROM adms_timeline_comments WHERE Key_name = 'idx_timeline_comments_post_status'");
$rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
echo empty($rows) ? "Indice NAO existe\n" : "Indice idx_timeline_comments_post_status EXISTE (" . count($rows) . " colunas no indice)\n";
