<?php
require __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$pdo = new PDO(
    'mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_NAME'],
    $_ENV['DB_USER'],
    $_ENV['DB_PASS']
);

echo "═══════════════════════════════════════\n";
echo "TABELAS CRM CRIADAS:\n";
echo "═══════════════════════════════════════\n";

$result = $pdo->query("SHOW TABLES LIKE 'crm_%'");
$count = 0;
while ($row = $result->fetch(PDO::FETCH_NUM)) {
    echo "✓ " . $row[0] . "\n";
    $count++;
}

echo "\nTotal: $count tabelas\n\n";

echo "═══════════════════════════════════════\n";
echo "ETAPAS DO PIPELINE:\n";
echo "═══════════════════════════════════════\n";

$stages = $pdo->query("SELECT id, name, color, conversion_probability FROM crm_pipeline_stages ORDER BY display_order")->fetchAll(PDO::FETCH_ASSOC);
foreach ($stages as $stage) {
    echo $stage['id'] . ". " . str_pad($stage['name'], 15) . " (" . $stage['color'] . ") - " . $stage['conversion_probability'] . "%\n";
}

echo "\n✅ BANCO DE DADOS CRM PRONTO!\n";

