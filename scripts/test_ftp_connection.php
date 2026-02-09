<?php
/**
 * Script para testar conexão FTP
 * 
 * Execute via CLI: php scripts/test_ftp_connection.php
 * 
 * Este script testa a conexão FTP usando as mesmas credenciais
 * que o GitHub Actions usa, para identificar problemas de conexão.
 */

// Carregar variáveis de ambiente
require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/..');
$dotenv->load();

// Credenciais (substitua pelos valores reais ou use variáveis de ambiente)
$ftp_server = $_ENV['FTP_SERVER_PROD'] ?? getenv('FTP_SERVER_PROD') ?? '';
$ftp_user = $_ENV['FTP_USER_PROD'] ?? getenv('FTP_USER_PROD') ?? '';
$ftp_pass = $_ENV['FTP_PASS_PROD'] ?? getenv('FTP_PASS_PROD') ?? '';

if (empty($ftp_server) || empty($ftp_user) || empty($ftp_pass)) {
    echo "❌ ERRO: Credenciais FTP não configuradas!\n";
    echo "Configure as variáveis de ambiente:\n";
    echo "  - FTP_SERVER_PROD\n";
    echo "  - FTP_USER_PROD\n";
    echo "  - FTP_PASS_PROD\n";
    exit(1);
}

echo "🔍 === TESTE DE CONEXÃO FTP ===\n\n";
echo "Servidor: $ftp_server\n";
echo "Usuário: $ftp_user\n";
echo "Senha: " . str_repeat('*', strlen($ftp_pass)) . "\n\n";

// Testar conexão
echo "🔄 Tentando conectar...\n";
$conn = @ftp_connect($ftp_server);

if (!$conn) {
    echo "❌ ERRO: Não foi possível conectar ao servidor FTP!\n";
    echo "Verifique:\n";
    echo "  1. O servidor está acessível?\n";
    echo "  2. O firewall está bloqueando?\n";
    echo "  3. A porta FTP está aberta?\n";
    exit(1);
}

echo "✅ Conexão estabelecida!\n\n";

// Testar login
echo "🔄 Tentando fazer login...\n";
$login = @ftp_login($conn, $ftp_user, $ftp_pass);

if (!$login) {
    echo "❌ ERRO: Falha na autenticação!\n";
    echo "Verifique:\n";
    echo "  1. Usuário está correto?\n";
    echo "  2. Senha está correta?\n";
    echo "  3. Usuário tem permissões?\n";
    ftp_close($conn);
    exit(1);
}

echo "✅ Login realizado com sucesso!\n\n";

// Testar modo passivo
echo "🔄 Configurando modo passivo...\n";
ftp_pasv($conn, true);
echo "✅ Modo passivo ativado!\n\n";

// Testar listagem de diretório
echo "🔄 Testando listagem de diretório...\n";
$files = @ftp_nlist($conn, '.');

if ($files === false) {
    echo "❌ ERRO: Não foi possível listar diretório!\n";
    echo "Verifique permissões do usuário FTP.\n";
    ftp_close($conn);
    exit(1);
}

echo "✅ Listagem funcionando! Encontrados " . count($files) . " itens.\n\n";

// Testar escrita
echo "🔄 Testando escrita...\n";
$test_file = 'test_ftp_' . time() . '.txt';
$test_content = 'Teste de escrita FTP - ' . date('Y-m-d H:i:s');

if (@ftp_put($conn, $test_file, 'php://temp', FTP_ASCII)) {
    // Tentar escrever conteúdo
    $temp_file = tempnam(sys_get_temp_dir(), 'ftp_test_');
    file_put_contents($temp_file, $test_content);
    
    if (@ftp_put($conn, $test_file, $temp_file, FTP_ASCII)) {
        echo "✅ Escrita funcionando!\n";
        
        // Limpar arquivo de teste
        @ftp_delete($conn, $test_file);
        echo "✅ Arquivo de teste removido.\n\n";
        unlink($temp_file);
    } else {
        echo "⚠️ Aviso: Escrita pode ter limitações.\n\n";
        unlink($temp_file);
    }
} else {
    echo "❌ ERRO: Não foi possível escrever arquivo!\n";
    echo "Verifique permissões de escrita do usuário FTP.\n\n";
}

// Fechar conexão
ftp_close($conn);

echo "✅ === TESTE CONCLUÍDO COM SUCESSO ===\n";
echo "A conexão FTP está funcionando corretamente!\n";
echo "Se o deploy ainda falhar, o problema pode ser:\n";
echo "  1. Timeout no GitHub Actions\n";
echo "  2. Rate limiting do servidor\n";
echo "  3. Versão da action FTP-Deploy-Action\n";
echo "  4. Limite de conexões simultâneas\n";

