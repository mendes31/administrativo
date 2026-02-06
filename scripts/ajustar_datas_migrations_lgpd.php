<?php
/**
 * Ajusta datas de migrations LGPD para evitar conflitos
 */

$migrationsDir = __DIR__ . '/../database/migrations';

// Migrations que precisam ser reorganizadas (em ordem de dependência)
$migrations = [
    '20250725181000_create_lgpd_consentimentos.php', // Base - não mover
    '20250725182000_add_audit_fields_to_lgpd_consentimentos.php', // Depois de criar
    '20250725182000_add_adms_user_id_to_lgpd_consentimentos.php', // Depois de criar
    '20250725182000_add_lgpd_termo_id_to_lgpd_consentimentos.php', // Depois de criar
    '20250725182000_create_lgpd_consentimento_arquivos.php', // Depois de criar
    '20250725182000_create_lgpd_solicitacoes_titulares.php', // Pode ficar depois
];

// Novas datas sequenciais (após 20250725181000)
$novasDatas = [
    '20250725181000', // create_lgpd_consentimentos (não mover)
    '20250725181010', // add_audit_fields
    '20250725181020', // add_adms_user_id
    '20250725181030', // add_lgpd_termo_id
    '20250725181040', // create_lgpd_consentimento_arquivos
    '20250725182000', // create_lgpd_solicitacoes_titulares (manter original)
];

echo "=== AJUSTANDO DATAS DAS MIGRATIONS LGPD ===\n\n";

for ($i = 0; $i < count($migrations); $i++) {
    $arquivo = $migrations[$i];
    $arquivoCompleto = $migrationsDir . '/' . $arquivo;
    
    if (file_exists($arquivoCompleto)) {
        // Extrair nome da migration
        if (preg_match('/^\d{14}_(.+)\.php$/', $arquivo, $matches)) {
            $nomeMigration = $matches[1];
            $novaData = $novasDatas[$i];
            $novoArquivo = $migrationsDir . '/' . $novaData . '_' . $nomeMigration . '.php';
            
            // Se já está na data correta, pular
            if ($arquivoCompleto === $novoArquivo) {
                echo "✓ {$arquivo} já está na data correta\n";
                continue;
            }
            
            // Ler conteúdo
            $conteudo = file_get_contents($arquivoCompleto);
            
            // Criar novo arquivo
            file_put_contents($novoArquivo, $conteudo);
            
            // Remover arquivo antigo (se for diferente)
            if ($arquivoCompleto !== $novoArquivo) {
                unlink($arquivoCompleto);
            }
            
            echo "✅ {$arquivo}\n";
            echo "   → Movido para: {$novaData}_{$nomeMigration}.php\n\n";
        }
    }
}

echo "✅ Ajuste concluído!\n";

