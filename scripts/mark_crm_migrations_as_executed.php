<?php
/**
 * Script para marcar migrations do CRM como já executadas na produção
 * 
 * Este script verifica quais tabelas do CRM já existem e marca as migrations
 * correspondentes como executadas na tabela phinxlog.
 * 
 * USO:
 * php scripts/mark_crm_migrations_as_executed.php
 */

require __DIR__ . '/../vendor/autoload.php';

// Carregar variáveis de ambiente
$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/..');
$dotenv->load();

// Configuração do banco de dados
$host = $_ENV['DB_HOST'];
$dbname = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];
$port = $_ENV['DB_PORT'] ?? 3306;

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    echo "=== MARCAR MIGRATIONS DO CRM COMO EXECUTADAS ===\n\n";

    // Mapeamento de migrations do CRM e suas tabelas principais
    $crmMigrations = [
        '20251028100000' => ['class' => 'CreateCrmPartners', 'table' => 'crm_partners'],
        '20251028100001' => ['class' => 'CreateCrmPipelineStages', 'table' => 'crm_pipeline_stages'],
        '20251028100002' => ['class' => 'CreateCrmOpportunities', 'table' => 'crm_opportunities'],
        '20251028100003' => ['class' => 'CreateCrmActivities', 'table' => 'crm_activities'],
        '20251028100004' => ['class' => 'CreateCrmStageHistory', 'table' => 'crm_stage_history'],
        '20251028100005' => ['class' => 'CreateCrmNotes', 'table' => 'crm_notes'],
        '20251028100006' => ['class' => 'CreateCrmDocuments', 'table' => 'crm_documents'],
        '20251028100007' => ['class' => 'CreateCrmTags', 'table' => 'crm_tags'],
        '20251028100008' => ['class' => 'CreateCrmPartnerTags', 'table' => 'crm_partner_tags'],
        '20251029110000' => ['class' => 'CreateCrmCustomFields', 'table' => 'crm_custom_fields'],
        '20251029120000' => ['class' => 'CreateCrmAutomations', 'table' => 'crm_automations'],
        '20251029130000' => ['class' => 'AddCountryToCrmPartners', 'table' => 'crm_partners'], // Alteração de tabela existente
        '20251030120001' => ['class' => 'RemoveTagsColumnFromCrmPartners', 'table' => 'crm_partners'], // Alteração de tabela existente
    ];

    // Verificar se a tabela phinxlog existe
    $checkPhinxlog = $pdo->query("SHOW TABLES LIKE 'phinxlog'");
    if ($checkPhinxlog->rowCount() === 0) {
        echo "ERRO: A tabela 'phinxlog' não existe!\n";
        echo "Execute primeiro: vendor/bin/phinx init\n";
        exit(1);
    }

    $now = date('Y-m-d H:i:s');
    $marked = 0;
    $skipped = 0;

    echo "Verificando tabelas existentes e marcando migrations...\n\n";

    foreach ($crmMigrations as $version => $info) {
        $className = $info['class'];
        $tableName = $info['table'];

        // Verificar se a migration já está marcada
        $checkExisting = $pdo->prepare("SELECT COUNT(*) as count FROM phinxlog WHERE version = ?");
        $checkExisting->execute([$version]);
        $exists = $checkExisting->fetch()['count'] > 0;

        if ($exists) {
            echo "⏭️  Migration {$version} ({$className}) já está marcada. Pulando...\n";
            $skipped++;
            continue;
        }

        // Para migrations que alteram tabelas existentes, assumir que estão OK se a tabela base existe
        // Para migrations que criam tabelas, verificar se a tabela existe
        if (in_array($className, ['AddCountryToCrmPartners', 'RemoveTagsColumnFromCrmPartners'])) {
            // Para alterações, verificar se a tabela base existe
            $checkTable = $pdo->query("SHOW TABLES LIKE '{$tableName}'");
            if ($checkTable->rowCount() === 0) {
                echo "⚠️  Tabela '{$tableName}' não existe. Pulando migration {$version}...\n";
                $skipped++;
                continue;
            }
        } else {
            // Para criações, verificar se a tabela existe
            $checkTable = $pdo->query("SHOW TABLES LIKE '{$tableName}'");
            if ($checkTable->rowCount() === 0) {
                echo "⚠️  Tabela '{$tableName}' não existe. Pulando migration {$version}...\n";
                $skipped++;
                continue;
            }
        }

        // Inserir registro na phinxlog
        $insert = $pdo->prepare("
            INSERT INTO phinxlog (version, migration_name, start_time, end_time)
            VALUES (?, ?, ?, ?)
        ");
        $insert->execute([$version, $className, $now, $now]);

        echo "✅ Migration {$version} ({$className}) marcada como executada.\n";
        $marked++;
    }

    echo "\n=== RESUMO ===\n";
    echo "Migrations marcadas: {$marked}\n";
    echo "Migrations puladas: {$skipped}\n";
    echo "\n✅ Processo concluído!\n";
    echo "\nAgora você pode executar: vendor/bin/phinx migrate\n";

} catch (PDOException $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    exit(1);
}

