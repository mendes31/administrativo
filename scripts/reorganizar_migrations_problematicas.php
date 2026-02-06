<?php
/**
 * Script para reorganizar migrations problemáticas
 * Move migrations que dependem de tabelas criadas depois para datas apropriadas
 */

$migrationsDir = __DIR__ . '/../database/migrations';

// Problemas identificados: migrations que modificam tabelas criadas depois
$problemas = [
    // LGPD Consentimentos - modificam tabela criada em 20250725181000
    [
        'arquivo' => '20250206000000_add_audit_fields_to_lgpd_consentimentos.php',
        'depende_de' => '20250725181000_create_lgpd_consentimentos.php',
        'nova_data' => '20250725182000' // Depois da criação da tabela
    ],
    [
        'arquivo' => '20250206090000_add_adms_user_id_to_lgpd_consentimentos.php',
        'depende_de' => '20250725181000_create_lgpd_consentimentos.php',
        'nova_data' => '20250725182000'
    ],
    [
        'arquivo' => '20250206100000_add_lgpd_termo_id_to_lgpd_consentimentos.php',
        'depende_de' => '20250725181000_create_lgpd_consentimentos.php',
        'nova_data' => '20250725182000'
    ],
    [
        'arquivo' => '20250206103000_create_lgpd_consentimento_arquivos.php',
        'depende_de' => '20250725181000_create_lgpd_consentimentos.php',
        'nova_data' => '20250725182000'
    ],
    // Strategic Plan Observations - depende de adms_strategic_plans criada em 20250710160000
    [
        'arquivo' => '20250120130000_create_adms_strategic_plan_observations.php',
        'depende_de' => '20250710160000_create_adms_strategic_plans.php',
        'nova_data' => '20250710161000' // Depois da criação de strategic_plans
    ],
];

echo "=== REORGANIZAÇÃO DE MIGRATIONS ===\n\n";

foreach ($problemas as $problema) {
    $arquivoAntigo = $migrationsDir . '/' . $problema['arquivo'];
    $novaData = $problema['nova_data'];
    
    // Extrair nome da migration do arquivo
    if (preg_match('/^\d{14}_(.+)\.php$/', $problema['arquivo'], $matches)) {
        $nomeMigration = $matches[1];
        $arquivoNovo = $migrationsDir . '/' . $novaData . '_' . $nomeMigration . '.php';
        
        if (file_exists($arquivoAntigo)) {
            // Ler conteúdo
            $conteudo = file_get_contents($arquivoAntigo);
            
            // Criar novo arquivo
            file_put_contents($arquivoNovo, $conteudo);
            
            // Remover arquivo antigo
            unlink($arquivoAntigo);
            
            echo "✅ {$problema['arquivo']}\n";
            echo "   → Movido para: {$novaData}_{$nomeMigration}.php\n";
            echo "   → Depende de: {$problema['depende_de']}\n\n";
        } else {
            echo "⚠️  Arquivo não encontrado: {$problema['arquivo']}\n\n";
        }
    }
}

echo "✅ Reorganização concluída!\n";

