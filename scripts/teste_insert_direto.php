<?php
/**
 * TESTE DE INSERT DIRETO - Produção
 * 
 * Este script tenta inserir um registro diretamente na tabela
 * para identificar se o problema é no SQL ou na lógica
 * 
 * EXECUTE: http://seu-dominio.com/administrativo/scripts/teste_insert_direto.php
 * REMOVER APÓS O TESTE!
 */

// Carregar .env
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

// Configurar exibição de erros
ini_set('display_errors', 1);
error_reporting(E_ALL);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Teste de Insert Direto</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #2E9263; }
        .status { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .ok { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .code { background: #f4f4f4; padding: 10px; font-family: monospace; margin: 10px 0; }
        pre { background: #f4f4f4; padding: 15px; border-left: 4px solid #2E9263; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Teste de Insert Direto</h1>
        <p><strong>Data/Hora:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>

        <?php
        try {
            echo "<h2>1. Conectando ao Banco de Dados</h2>";
            
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $dbname = $_ENV['DB_NAME'] ?? '';
            $user = $_ENV['DB_USER'] ?? '';
            $pass = $_ENV['DB_PASS'] ?? '';
            
            echo "<div class='code'>Host: $host<br>Database: $dbname<br>User: $user</div>";
            
            $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            echo "<div class='status ok'>✓ Conexão estabelecida com sucesso!</div>";
            
            // =====================================
            // TESTE 1: Verificar se existem usuários e treinamentos
            // =====================================
            echo "<h2>2. Verificando Dados de Teste</h2>";
            
            $stmtUser = $pdo->query("SELECT id, name FROM adms_users ORDER BY id ASC LIMIT 1");
            $user1 = $stmtUser->fetch();
            
            $stmtTraining = $pdo->query("SELECT id, nome FROM adms_trainings ORDER BY id ASC LIMIT 1");
            $training1 = $stmtTraining->fetch();
            
            if (!$user1 || !$training1) {
                echo "<div class='status error'>✗ Não há dados de teste (usuários ou treinamentos)</div>";
                exit;
            }
            
            echo "<div class='status ok'>✓ Dados de teste encontrados:</div>";
            echo "<div class='code'>";
            echo "Usuário: ID={$user1['id']}, Nome={$user1['name']}<br>";
            echo "Treinamento: ID={$training1['id']}, Nome={$training1['nome']}";
            echo "</div>";
            
            // =====================================
            // TESTE 2: INSERT SIMPLES (mínimo de campos)
            // =====================================
            echo "<h2>3. Teste 1 - INSERT Simples (apenas campos obrigatórios)</h2>";
            
            $sqlSimples = "INSERT INTO adms_training_applications 
                          (adms_user_id, adms_training_id, status, created_at, updated_at) 
                          VALUES (:user_id, :training_id, :status, NOW(), NOW())";
            
            echo "<div class='code'>SQL: " . htmlspecialchars($sqlSimples) . "</div>";
            
            $stmtSimples = $pdo->prepare($sqlSimples);
            $stmtSimples->bindValue(':user_id', $user1['id'], PDO::PARAM_INT);
            $stmtSimples->bindValue(':training_id', $training1['id'], PDO::PARAM_INT);
            $stmtSimples->bindValue(':status', 'teste_simples', PDO::PARAM_STR);
            
            $resultSimples = $stmtSimples->execute();
            $idSimples = $pdo->lastInsertId();
            
            if ($resultSimples && $idSimples) {
                echo "<div class='status ok'>✓ INSERT Simples: SUCESSO! ID gerado: $idSimples</div>";
            } else {
                echo "<div class='status error'>✗ INSERT Simples: FALHOU!</div>";
                echo "<pre>" . print_r($stmtSimples->errorInfo(), true) . "</pre>";
            }
            
            // =====================================
            // TESTE 3: INSERT COMPLETO (todos os campos)
            // =====================================
            echo "<h2>4. Teste 2 - INSERT Completo (todos os campos preenchidos)</h2>";
            
            $sqlCompleto = "INSERT INTO adms_training_applications (
                                adms_user_id, adms_training_id, data_realizacao, data_avaliacao,
                                data_agendada, instrutor_nome, instrutor_email, instructor_user_id,
                                real_instructor_nome, real_instructor_email, aplicado_por,
                                nota, observacoes, status, created_at, updated_at
                            ) VALUES (
                                :adms_user_id, :adms_training_id, :data_realizacao, :data_avaliacao,
                                :data_agendada, :instrutor_nome, :instrutor_email, :instructor_user_id,
                                :real_instructor_nome, :real_instructor_email, :aplicado_por,
                                :nota, :observacoes, :status, NOW(), NOW()
                            )";
            
            echo "<div class='code'>SQL: " . htmlspecialchars($sqlCompleto) . "</div>";
            
            $stmtCompleto = $pdo->prepare($sqlCompleto);
            
            // Dados de teste
            $dadosTeste = [
                ':adms_user_id' => $user1['id'],
                ':adms_training_id' => $training1['id'],
                ':data_realizacao' => date('Y-m-d'),
                ':data_avaliacao' => date('Y-m-d'),
                ':data_agendada' => null,
                ':instrutor_nome' => 'Instrutor Teste',
                ':instrutor_email' => 'teste@teste.com',
                ':instructor_user_id' => $user1['id'],
                ':real_instructor_nome' => 'Instrutor Teste',
                ':real_instructor_email' => 'teste@teste.com',
                ':aplicado_por' => $user1['id'],
                ':nota' => 8.5,
                ':observacoes' => 'Teste de inserção direta',
                ':status' => 'teste_completo'
            ];
            
            foreach ($dadosTeste as $key => $value) {
                if (is_null($value)) {
                    $stmtCompleto->bindValue($key, null, PDO::PARAM_NULL);
                } elseif (is_int($value)) {
                    $stmtCompleto->bindValue($key, $value, PDO::PARAM_INT);
                } elseif (is_float($value)) {
                    $stmtCompleto->bindValue($key, $value, PDO::PARAM_STR);
                } else {
                    $stmtCompleto->bindValue($key, $value, PDO::PARAM_STR);
                }
            }
            
            echo "<h3>Dados que serão inseridos:</h3>";
            echo "<pre>" . print_r($dadosTeste, true) . "</pre>";
            
            $resultCompleto = $stmtCompleto->execute();
            $idCompleto = $pdo->lastInsertId();
            
            if ($resultCompleto && $idCompleto) {
                echo "<div class='status ok'>✓ INSERT Completo: SUCESSO! ID gerado: $idCompleto</div>";
                
                // Buscar o registro inserido
                $stmtVerifica = $pdo->prepare("SELECT * FROM adms_training_applications WHERE id = ?");
                $stmtVerifica->execute([$idCompleto]);
                $registroInserido = $stmtVerifica->fetch();
                
                echo "<h3>Registro inserido:</h3>";
                echo "<pre>" . print_r($registroInserido, true) . "</pre>";
            } else {
                echo "<div class='status error'>✗ INSERT Completo: FALHOU!</div>";
                echo "<pre>" . print_r($stmtCompleto->errorInfo(), true) . "</pre>";
            }
            
            // =====================================
            // TESTE 4: Verificar registros na tabela
            // =====================================
            echo "<h2>5. Registros Atuais na Tabela</h2>";
            
            $stmtCount = $pdo->query("SELECT COUNT(*) as total FROM adms_training_applications");
            $count = $stmtCount->fetch();
            
            echo "<div class='code'>Total de registros: <strong>{$count['total']}</strong></div>";
            
            if ($count['total'] > 0) {
                $stmtAll = $pdo->query("SELECT * FROM adms_training_applications ORDER BY id DESC LIMIT 5");
                $registros = $stmtAll->fetchAll();
                
                echo "<h3>Últimos 5 registros:</h3>";
                echo "<table border='1' cellpadding='10' style='width:100%; border-collapse: collapse;'>";
                echo "<tr style='background: #2E9263; color: white;'>";
                echo "<th>ID</th><th>User</th><th>Training</th><th>Data</th><th>Nota</th><th>Status</th>";
                echo "</tr>";
                
                foreach ($registros as $reg) {
                    echo "<tr>";
                    echo "<td>{$reg['id']}</td>";
                    echo "<td>{$reg['adms_user_id']}</td>";
                    echo "<td>{$reg['adms_training_id']}</td>";
                    echo "<td>{$reg['data_realizacao']}</td>";
                    echo "<td>{$reg['nota']}</td>";
                    echo "<td>{$reg['status']}</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
            }
            
            // =====================================
            // LIMPEZA - Remover registros de teste
            // =====================================
            echo "<h2>6. Limpeza</h2>";
            
            $stmtDelete = $pdo->prepare("DELETE FROM adms_training_applications WHERE status LIKE 'teste_%'");
            $stmtDelete->execute();
            $deleted = $stmtDelete->rowCount();
            
            echo "<div class='status ok'>✓ Registros de teste removidos: $deleted</div>";
            
        } catch (PDOException $e) {
            echo "<div class='status error'>";
            echo "<strong>✗ ERRO PDO:</strong><br>";
            echo "Código: " . $e->getCode() . "<br>";
            echo "Mensagem: " . htmlspecialchars($e->getMessage()) . "<br>";
            echo "</div>";
            echo "<h3>Stack Trace:</h3>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        } catch (Exception $e) {
            echo "<div class='status error'>";
            echo "<strong>✗ ERRO:</strong><br>";
            echo htmlspecialchars($e->getMessage());
            echo "</div>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        }
        ?>

        <hr>
        <h2>🎯 Conclusão</h2>
        <div class="status ok">
            <strong>Se os dois testes acima funcionaram:</strong>
            <ul>
                <li>✅ O banco de dados está funcionando corretamente</li>
                <li>✅ A tabela está acessível</li>
                <li>✅ O problema está na lógica do código (validações, foreign keys, etc)</li>
            </ul>
        </div>
        
        <div class="status error">
            <strong>Se algum teste falhou:</strong>
            <ul>
                <li>❌ Verifique o erro SQL mostrado acima</li>
                <li>❌ Corrija as foreign keys ou campos obrigatórios</li>
                <li>❌ Verifique se os IDs de usuário e treinamento existem</li>
            </ul>
        </div>

        <hr>
        <p><strong>⚠️ LEMBRE-SE:</strong> Após o teste, <strong>DELETE ESTE ARQUIVO</strong> por segurança!</p>
        <div class="code">rm scripts/teste_insert_direto.php</div>
    </div>
</body>
</html>

