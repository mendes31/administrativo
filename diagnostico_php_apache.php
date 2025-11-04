<!DOCTYPE html>
<html>
<head>
    <title>Diagnóstico PHP Apache</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; border: 2px solid #ddd; }
        .success { border-color: #28a745; }
        .error { border-color: #dc3545; }
        .info { border-color: #17a2b8; }
        h2 { margin-top: 0; }
        code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; }
        .badge { padding: 3px 8px; border-radius: 3px; font-size: 12px; font-weight: bold; }
        .badge-success { background: #28a745; color: white; }
        .badge-danger { background: #dc3545; color: white; }
    </style>
</head>
<body>
    <h1>🔍 Diagnóstico PHP Apache</h1>
    
    <?php
    // 1. Configuração PHP
    echo '<div class="box info">';
    echo '<h2>📋 Configuração PHP</h2>';
    echo '<p><strong>PHP Version:</strong> ' . PHP_VERSION . '</p>';
    echo '<p><strong>Loaded php.ini:</strong> <code>' . php_ini_loaded_file() . '</code></p>';
    echo '<p><strong>SAPI:</strong> ' . php_sapi_name() . '</p>';
    echo '</div>';
    
    // 2. Extensões PDO
    echo '<div class="box ' . (extension_loaded('pdo') ? 'success' : 'error') . '">';
    echo '<h2>📦 Extensões PDO</h2>';
    echo '<p>PDO: ';
    if (extension_loaded('pdo')) {
        echo '<span class="badge badge-success">✅ ATIVA</span>';
        echo '<br><strong>Drivers disponíveis:</strong> ' . implode(', ', PDO::getAvailableDrivers());
    } else {
        echo '<span class="badge badge-danger">❌ INATIVA</span>';
    }
    echo '</p>';
    
    echo '<p>PDO_ODBC: ';
    if (extension_loaded('pdo_odbc')) {
        echo '<span class="badge badge-success">✅ ATIVA</span>';
    } else {
        echo '<span class="badge badge-danger">❌ INATIVA</span>';
        echo '<br><br><strong>⚠️ SOLUÇÃO:</strong>';
        echo '<br>1. Edite: <code>' . php_ini_loaded_file() . '</code>';
        echo '<br>2. Procure por: <code>pdo_odbc</code>';
        echo '<br>3. Remova o <code>;</code> de: <code>;extension=pdo_odbc</code>';
        echo '<br>4. Salve e reinicie o Apache (WAMP → Restart All Services)';
    }
    echo '</p>';
    echo '</div>';
    
    // 3. Teste ODBC (se ativo)
    if (extension_loaded('pdo_odbc')) {
        echo '<div class="box info">';
        echo '<h2>🔌 Teste de Conexão ODBC</h2>';
        
        try {
            $dsn = "odbc:SBO_TIARAJU_HOM";
            
            // Buscar credenciais do .env se existirem
            if (file_exists(__DIR__ . '/.env')) {
                require __DIR__ . '/vendor/autoload.php';
                $dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__);
                $dotenv->load();
                $username = $_ENV['SAP_HANA_USERNAME'] ?? 'SYSTEM';
                $password = $_ENV['SAP_HANA_PASSWORD'] ?? '';
            } else {
                $username = 'SYSTEM';
                $password = '';
            }
            
            if (!empty($password)) {
                $pdo = new PDO($dsn, $username, $password);
                echo '<p><span class="badge badge-success">✅ CONECTADO COM SUCESSO!</span></p>';
                echo '<p>DSN: <code>SBO_TIARAJU_HOM</code></p>';
                echo '<p>Usuário: <code>' . htmlspecialchars($username) . '</code></p>';
                
                // IMPORTANTE: Definir schema correto (SBO_TIARAJU_HOM, não SYSTEM)
                $schema = $_ENV['SAP_SL_COMPANY'] ?? 'SBO_TIARAJU_HOM';
                $pdo->exec("SET SCHEMA \"$schema\"");
                echo '<p>Schema: <code>' . htmlspecialchars($schema) . '</code></p>';
                
                // Testar query
                $stmt = $pdo->query('SELECT TOP 3 "ItemCode", "ItemName" FROM OITM');
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo '<p><strong>Query de teste executada:</strong></p>';
                echo '<pre>SELECT TOP 3 "ItemCode", "ItemName" FROM OITM</pre>';
                echo '<p><strong>Registros retornados:</strong> ' . count($results) . '</p>';
                
                if (!empty($results)) {
                    echo '<ul>';
                    foreach ($results as $row) {
                        echo '<li>' . htmlspecialchars($row['ItemCode']) . ': ' . htmlspecialchars($row['ItemName']) . '</li>';
                    }
                    echo '</ul>';
                }
                
                echo '<p><span class="badge badge-success">🎉 HDBODBC FUNCIONANDO NO APACHE!</span></p>';
            } else {
                echo '<p><span class="badge badge-danger">⚠️ Senha não configurada no .env</span></p>';
            }
            
        } catch (PDOException $e) {
            echo '<p><span class="badge badge-danger">❌ ERRO NA CONEXÃO</span></p>';
            echo '<p><strong>Mensagem:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        
        echo '</div>';
    }
    ?>
    
    <div class="box info">
        <h2>📚 Instruções</h2>
        <p>Se <code>pdo_odbc</code> estiver <strong>INATIVA</strong>:</p>
        <ol>
            <li>Edite o php.ini listado acima</li>
            <li>Procure por <code>;extension=pdo_odbc</code></li>
            <li>Remova o <code>;</code> no início da linha</li>
            <li>Salve o arquivo</li>
            <li>Reinicie o Apache (WAMP → Restart All Services)</li>
            <li>Recarregue esta página (F5)</li>
        </ol>
    </div>
    
</body>
</html>

