<?php
/**
 * SCRIPT DE DIAGNÓSTICO - PRODUÇÃO
 * 
 * Execute este arquivo diretamente no navegador em produção para identificar problemas
 * URL: https://seu-dominio.com/scripts/diagnostico_producao.php
 * 
 * IMPORTANTE: REMOVER APÓS DIAGNÓSTICO (contém informações sensíveis)
 */

// Carregar variáveis de ambiente
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
}

// Carregar .env
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Produção</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #2E9263; }
        h2 { color: #495057; border-bottom: 2px solid #2E9263; padding-bottom: 10px; }
        .status { padding: 10px; border-radius: 4px; margin: 10px 0; }
        .ok { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 10px; text-align: left; border: 1px solid #dee2e6; }
        th { background: #2E9263; color: white; }
        tr:nth-child(even) { background: #f8f9fa; }
        .code { background: #f4f4f4; padding: 10px; border-left: 4px solid #2E9263; margin: 10px 0; font-family: monospace; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Diagnóstico de Produção - Sistema Tiaraju</h1>
        <p><strong>Data/Hora:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
        <p><strong>Servidor:</strong> <?php echo $_SERVER['SERVER_NAME'] ?? 'N/A'; ?></p>

        <!-- 1. INFORMAÇÕES DO PHP -->
        <h2>1. Versão e Configuração do PHP</h2>
        <table>
            <tr>
                <th>Item</th>
                <th>Valor</th>
                <th>Status</th>
            </tr>
            <tr>
                <td>Versão PHP</td>
                <td><?php echo phpversion(); ?></td>
                <td class="status <?php echo version_compare(phpversion(), '8.0', '>=') ? 'ok' : 'warning'; ?>">
                    <?php echo version_compare(phpversion(), '8.0', '>=') ? '✓ OK' : '⚠ Recomendado 8.0+'; ?>
                </td>
            </tr>
            <tr>
                <td>display_errors</td>
                <td><?php echo ini_get('display_errors') ? 'ON' : 'OFF'; ?></td>
                <td class="status <?php echo !ini_get('display_errors') ? 'ok' : 'warning'; ?>">
                    <?php echo !ini_get('display_errors') ? '✓ OFF (correto para produção)' : '⚠ Deveria estar OFF'; ?>
                </td>
            </tr>
            <tr>
                <td>log_errors</td>
                <td><?php echo ini_get('log_errors') ? 'ON' : 'OFF'; ?></td>
                <td class="status <?php echo ini_get('log_errors') ? 'ok' : 'error'; ?>">
                    <?php echo ini_get('log_errors') ? '✓ ON' : '✗ DEVE ESTAR ON'; ?>
                </td>
            </tr>
            <tr>
                <td>error_log</td>
                <td><?php echo ini_get('error_log') ?: 'padrão'; ?></td>
                <td class="status ok">📁 Caminho do log de erros</td>
            </tr>
            <tr>
                <td>post_max_size</td>
                <td><?php echo ini_get('post_max_size'); ?></td>
                <td class="status <?php echo (int)ini_get('post_max_size') >= 8 ? 'ok' : 'warning'; ?>">
                    <?php echo (int)ini_get('post_max_size') >= 8 ? '✓ OK' : '⚠ Pode ser insuficiente'; ?>
                </td>
            </tr>
            <tr>
                <td>upload_max_filesize</td>
                <td><?php echo ini_get('upload_max_filesize'); ?></td>
                <td class="status ok">ℹ Info</td>
            </tr>
            <tr>
                <td>max_execution_time</td>
                <td><?php echo ini_get('max_execution_time'); ?>s</td>
                <td class="status <?php echo (int)ini_get('max_execution_time') >= 30 ? 'ok' : 'warning'; ?>">
                    <?php echo (int)ini_get('max_execution_time') >= 30 ? '✓ OK' : '⚠ Muito baixo'; ?>
                </td>
            </tr>
            <tr>
                <td>memory_limit</td>
                <td><?php echo ini_get('memory_limit'); ?></td>
                <td class="status ok">ℹ Info</td>
            </tr>
        </table>

        <!-- 2. EXTENSÕES PHP -->
        <h2>2. Extensões PHP Necessárias</h2>
        <table>
            <tr>
                <th>Extensão</th>
                <th>Status</th>
            </tr>
            <?php
            $extensoes = ['pdo', 'pdo_mysql', 'mysqli', 'mbstring', 'json', 'session', 'fileinfo'];
            foreach ($extensoes as $ext) {
                $loaded = extension_loaded($ext);
                echo "<tr>";
                echo "<td>$ext</td>";
                echo "<td class='status " . ($loaded ? 'ok' : 'error') . "'>";
                echo $loaded ? '✓ Instalada' : '✗ NÃO INSTALADA';
                echo "</td>";
                echo "</tr>";
            }
            ?>
        </table>

        <!-- 3. BANCO DE DADOS -->
        <h2>3. Conexão com Banco de Dados</h2>
        <?php
        try {
            require_once __DIR__ . '/../app/adms/Models/Services/DbConnection.php';
            $db = new \App\adms\Models\Services\DbConnection();
            $conn = $db->getConnection();
            
            if ($conn) {
                echo "<div class='status ok'>✓ Conexão com banco de dados: <strong>OK</strong></div>";
                
                // Verificar tabela
                $stmt = $conn->prepare("SHOW TABLES LIKE 'adms_training_applications'");
                $stmt->execute();
                $tableExists = $stmt->fetch();
                
                if ($tableExists) {
                    echo "<div class='status ok'>✓ Tabela adms_training_applications: <strong>EXISTE</strong></div>";
                    
                    // Verificar colunas
                    $stmt = $conn->prepare("DESCRIBE adms_training_applications");
                    $stmt->execute();
                    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo "<h3>Colunas da tabela:</h3>";
                    echo "<table>";
                    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th></tr>";
                    foreach ($columns as $col) {
                        echo "<tr>";
                        echo "<td>{$col['Field']}</td>";
                        echo "<td>{$col['Type']}</td>";
                        echo "<td>{$col['Null']}</td>";
                        echo "<td>{$col['Key']}</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                    
                    // Verificar último registro
                    $stmt = $conn->prepare("SELECT * FROM adms_training_applications ORDER BY id DESC LIMIT 1");
                    $stmt->execute();
                    $lastRecord = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($lastRecord) {
                        echo "<h3>Último registro inserido:</h3>";
                        echo "<div class='code'>";
                        echo "ID: " . $lastRecord['id'] . "<br>";
                        echo "Usuário: " . $lastRecord['adms_user_id'] . "<br>";
                        echo "Treinamento: " . $lastRecord['adms_training_id'] . "<br>";
                        echo "Data Realização: " . $lastRecord['data_realizacao'] . "<br>";
                        echo "Instrutor: " . $lastRecord['instrutor_nome'] . "<br>";
                        echo "Status: " . $lastRecord['status'] . "<br>";
                        echo "Criado em: " . $lastRecord['created_at'] . "<br>";
                        echo "</div>";
                    } else {
                        echo "<div class='status warning'>⚠ Nenhum registro encontrado na tabela</div>";
                    }
                } else {
                    echo "<div class='status error'>✗ Tabela adms_training_applications: <strong>NÃO EXISTE</strong></div>";
                    echo "<p>Execute as migrations: <code>php vendor/bin/phinx migrate</code></p>";
                }
            } else {
                echo "<div class='status error'>✗ Falha ao conectar ao banco de dados</div>";
            }
        } catch (Exception $e) {
            echo "<div class='status error'>✗ Erro: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
        ?>

        <!-- 4. SESSÕES -->
        <h2>4. Configuração de Sessões</h2>
        <?php
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        ?>
        <table>
            <tr>
                <th>Item</th>
                <th>Valor</th>
            </tr>
            <tr>
                <td>Session Status</td>
                <td>
                    <?php 
                    $status = session_status();
                    echo $status === PHP_SESSION_ACTIVE ? '<span class="status ok">✓ ATIVA</span>' : '<span class="status error">✗ INATIVA</span>';
                    ?>
                </td>
            </tr>
            <tr>
                <td>Session ID</td>
                <td><?php echo session_id(); ?></td>
            </tr>
            <tr>
                <td>Session Save Path</td>
                <td><?php echo session_save_path(); ?></td>
            </tr>
            <tr>
                <td>Save Path Writable</td>
                <td>
                    <?php 
                    $path = session_save_path();
                    $writable = is_writable($path);
                    echo $writable ? '<span class="status ok">✓ SIM</span>' : '<span class="status error">✗ NÃO (PROBLEMA!)</span>';
                    ?>
                </td>
            </tr>
        </table>

        <!-- 5. PERMISSÕES DE ARQUIVOS -->
        <h2>5. Permissões de Diretórios</h2>
        <table>
            <tr>
                <th>Diretório</th>
                <th>Existe</th>
                <th>Gravável</th>
            </tr>
            <?php
            $dirs = [
                'app/logs',
                'logs',
                'public/adms/uploads'
            ];
            foreach ($dirs as $dir) {
                $fullPath = __DIR__ . '/../' . $dir;
                $exists = file_exists($fullPath);
                $writable = is_writable($fullPath);
                echo "<tr>";
                echo "<td>$dir</td>";
                echo "<td class='status " . ($exists ? 'ok' : 'error') . "'>" . ($exists ? '✓ SIM' : '✗ NÃO') . "</td>";
                echo "<td class='status " . ($writable ? 'ok' : 'error') . "'>" . ($writable ? '✓ SIM' : '✗ NÃO') . "</td>";
                echo "</tr>";
            }
            ?>
        </table>

        <!-- 6. VARIÁVEIS DE AMBIENTE -->
        <h2>6. Variáveis de Ambiente</h2>
        <table>
            <tr>
                <th>Variável</th>
                <th>Valor</th>
            </tr>
            <tr>
                <td>URL_ADM</td>
                <td><?php echo $_ENV['URL_ADM'] ?? '<span class="status error">NÃO DEFINIDA</span>'; ?></td>
            </tr>
            <tr>
                <td>DB_HOST</td>
                <td><?php echo $_ENV['DB_HOST'] ?? '<span class="status error">NÃO DEFINIDA</span>'; ?></td>
            </tr>
            <tr>
                <td>DB_NAME</td>
                <td><?php echo $_ENV['DB_NAME'] ?? '<span class="status error">NÃO DEFINIDA</span>'; ?></td>
            </tr>
            <tr>
                <td>DB_USER</td>
                <td><?php echo isset($_ENV['DB_USER']) ? '✓ Definido' : '<span class="status error">NÃO DEFINIDA</span>'; ?></td>
            </tr>
        </table>

        <!-- 7. TESTE DE POST -->
        <h2>7. Teste de Envio POST</h2>
        
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <div class="status ok">
                <strong>✓ POST Recebido com Sucesso!</strong>
            </div>
            <h3>Dados Recebidos:</h3>
            <div class="code">
                <?php print_r($_POST); ?>
            </div>
            <h3>Content-Type:</h3>
            <div class="code">
                <?php echo $_SERVER['CONTENT_TYPE'] ?? 'Não definido'; ?>
            </div>
            <h3>Content-Length:</h3>
            <div class="code">
                <?php echo $_SERVER['CONTENT_LENGTH'] ?? 'Não definido'; ?>
            </div>
        <?php else: ?>
            <form method="POST" action="">
                <p>Use este formulário para testar se o POST funciona:</p>
                <input type="text" name="teste_campo" placeholder="Digite algo" style="padding: 10px; width: 300px;">
                <button type="submit" style="padding: 10px 20px; background: #2E9263; color: white; border: none; cursor: pointer;">
                    Testar POST
                </button>
            </form>
        <?php endif; ?>

        <!-- 8. LOGS RECENTES -->
        <h2>8. Logs de Erro Recentes</h2>
        <?php
        $logFiles = [
            'app/logs/debug_training_applications.log',
            ini_get('error_log'),
            __DIR__ . '/../logs/apply_training_debug.log'
        ];
        
        foreach ($logFiles as $logFile) {
            if (!$logFile || !file_exists($logFile)) continue;
            
            echo "<h3>📄 " . basename($logFile) . "</h3>";
            
            if (is_readable($logFile)) {
                $lines = file($logFile);
                $recentLines = array_slice($lines, -50); // Últimas 50 linhas
                
                echo "<div class='code' style='max-height: 300px; overflow-y: auto;'>";
                echo htmlspecialchars(implode('', $recentLines));
                echo "</div>";
            } else {
                echo "<div class='status error'>✗ Arquivo não tem permissão de leitura</div>";
            }
        }
        ?>

        <!-- 9. INFORMAÇÕES DO SERVIDOR -->
        <h2>9. Informações do Servidor</h2>
        <table>
            <tr>
                <th>Item</th>
                <th>Valor</th>
            </tr>
            <tr>
                <td>Sistema Operacional</td>
                <td><?php echo php_uname(); ?></td>
            </tr>
            <tr>
                <td>Servidor Web</td>
                <td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?></td>
            </tr>
            <tr>
                <td>Document Root</td>
                <td><?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'N/A'; ?></td>
            </tr>
            <tr>
                <td>Script Filename</td>
                <td><?php echo __FILE__; ?></td>
            </tr>
        </table>

        <!-- 10. PRÓXIMOS PASSOS -->
        <h2>10. ✅ Próximos Passos</h2>
        <div class="status warning">
            <strong>⚠️ IMPORTANTE:</strong> Após o diagnóstico, <strong>REMOVA</strong> este arquivo por segurança:
            <div class="code">
                rm scripts/diagnostico_producao.php
            </div>
        </div>
        
        <ol>
            <li>Verifique se todos os itens acima estão <strong>OK</strong></li>
            <li>Se houver itens em <strong>ERRO</strong>, corrija-os primeiro</li>
            <li>Tente salvar uma aplicação de treinamento</li>
            <li>Verifique os logs acima para ver se aparecem as mensagens de debug</li>
            <li>Envie as informações relevantes para análise</li>
        </ol>
    </div>
</body>
</html>

