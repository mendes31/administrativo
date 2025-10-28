<?php
/**
 * TESTE DO FLUXO COMPLETO DE APPLY TRAINING
 * 
 * Simula exatamente o que acontece quando você salva uma aplicação
 * 
 * EXECUTE: http://seu-dominio.com/administrativo/scripts/teste_apply_training.php
 * REMOVER APÓS O TESTE!
 */

// Configurar exibição de erros
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Carregar autoload
require __DIR__ . '/../vendor/autoload.php';

// Carregar .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Iniciar sessão
session_start();

// Simular usuário logado
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Manager';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Teste Apply Training</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #2E9263; }
        .status { padding: 15px; margin: 10px 0; border-radius: 4px; font-weight: bold; }
        .ok { background: #d4edda; color: #155724; border-left: 5px solid #28a745; }
        .error { background: #f8d7da; color: #721c24; border-left: 5px solid #dc3545; }
        .warning { background: #fff3cd; color: #856404; border-left: 5px solid #ffc107; }
        .info { background: #d1ecf1; color: #0c5460; border-left: 5px solid #17a2b8; }
        pre { background: #f4f4f4; padding: 15px; border-left: 4px solid #2E9263; overflow-x: auto; }
        .step { background: #e7f3ff; padding: 10px; margin: 10px 0; border-left: 4px solid #0d6efd; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Teste do Fluxo Apply Training</h1>
        <p><strong>Data/Hora:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>

        <?php
        try {
            // ====================================
            // PASSO 1: Conectar ao banco
            // ====================================
            echo "<div class='step'>PASSO 1: Conectando ao banco de dados</div>";
            
            $host = $_ENV['DB_HOST'];
            $dbname = $_ENV['DB_NAME'];
            $dbuser = $_ENV['DB_USER'];
            $dbpass = $_ENV['DB_PASS'];
            
            $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
            $pdo = new PDO($dsn, $dbuser, $dbpass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            
            echo "<div class='status ok'>✓ Conexão estabelecida</div>";
            
            // ====================================
            // PASSO 2: Buscar dados de teste
            // ====================================
            echo "<div class='step'>PASSO 2: Buscando dados de teste</div>";
            
            $stmtUser = $pdo->query("SELECT id, name FROM adms_users ORDER BY id ASC LIMIT 1");
            $testUser = $stmtUser->fetch();
            
            $stmtTraining = $pdo->query("SELECT id, nome FROM adms_trainings ORDER BY id ASC LIMIT 1");
            $testTraining = $stmtTraining->fetch();
            
            echo "<pre>";
            echo "Usuário: ID={$testUser['id']}, Nome={$testUser['name']}\n";
            echo "Treinamento: ID={$testTraining['id']}, Nome={$testTraining['nome']}";
            echo "</pre>";
            
            // ====================================
            // PASSO 3: Instanciar Repositories (como o código real faz)
            // ====================================
            echo "<div class='step'>PASSO 3: Instanciando Repositories</div>";
            
            require_once __DIR__ . '/../app/adms/Models/Repository/TrainingApplicationsRepository.php';
            require_once __DIR__ . '/../app/adms/Models/Repository/TrainingUsersRepository.php';
            require_once __DIR__ . '/../app/adms/Models/Repository/TrainingsRepository.php';
            require_once __DIR__ . '/../app/adms/Models/Repository/UsersRepository.php';
            
            $applicationsRepo = new \App\adms\Models\Repository\TrainingApplicationsRepository();
            $trainingUsersRepo = new \App\adms\Models\Repository\TrainingUsersRepository();
            $usersRepo = new \App\adms\Models\Repository\UsersRepository();
            
            echo "<div class='status ok'>✓ Repositories instanciados</div>";
            
            // ====================================
            // PASSO 4: Simular dados do POST (instrutor interno)
            // ====================================
            echo "<div class='step'>PASSO 4: Simulando dados do POST</div>";
            
            $user_id = $testUser['id'];
            $training_id = $testTraining['id'];
            $data_realizacao = date('Y-m-d');
            $data_avaliacao = date('Y-m-d');
            $nota = 8.5;
            $observacoes = 'Teste via script de diagnóstico';
            $instructor_type = 'internal';
            $instructor_user_id = $testUser['id'];
            $aplicado_por = $_SESSION['user_id'];
            
            echo "<pre>";
            echo "user_id: $user_id\n";
            echo "training_id: $training_id\n";
            echo "data_realizacao: $data_realizacao\n";
            echo "data_avaliacao: $data_avaliacao\n";
            echo "nota: $nota\n";
            echo "instructor_type: $instructor_type\n";
            echo "instructor_user_id: $instructor_user_id\n";
            echo "aplicado_por: $aplicado_por";
            echo "</pre>";
            
            // ====================================
            // PASSO 5: Processar instrutor (como o código real)
            // ====================================
            echo "<div class='step'>PASSO 5: Processando dados do instrutor</div>";
            
            $real_instructor_nome = null;
            $real_instructor_email = null;
            $instructor_user_id_to_save = null;
            
            if (!empty($instructor_user_id)) {
                $user = $usersRepo->getUser((int)$instructor_user_id);
                if ($user) {
                    $real_instructor_nome = $user['name'];
                    $real_instructor_email = $user['email'];
                    $instructor_user_id_to_save = $user['id'];
                    
                    echo "<div class='status ok'>✓ Instrutor interno encontrado:</div>";
                    echo "<pre>";
                    echo "Nome: $real_instructor_nome\n";
                    echo "Email: $real_instructor_email\n";
                    echo "ID: $instructor_user_id_to_save";
                    echo "</pre>";
                } else {
                    echo "<div class='status error'>✗ Instrutor não encontrado!</div>";
                }
            }
            
            // ====================================
            // PASSO 6: Preparar dados (EXATAMENTE como ApplyTraining.php)
            // ====================================
            echo "<div class='step'>PASSO 6: Preparando dados para insert</div>";
            
            $dados = [
                'adms_user_id' => $user_id,
                'adms_training_id' => $training_id,
                'data_realizacao' => $data_realizacao,
                'data_avaliacao' => $data_avaliacao,
                'data_agendada' => null,
                'nota' => $nota,
                'observacoes' => $observacoes,
                'instrutor_nome' => $real_instructor_nome,
                'instrutor_email' => $real_instructor_email,
                'instructor_user_id' => $instructor_user_id_to_save,
                'real_instructor_nome' => $real_instructor_nome,
                'real_instructor_email' => $real_instructor_email,
                'aplicado_por' => $aplicado_por,
                'status' => $data_realizacao ? 'concluido' : 'agendado'
            ];
            
            echo "<h3>Array \$dados preparado:</h3>";
            echo "<pre>" . print_r($dados, true) . "</pre>";
            
            // ====================================
            // PASSO 7: Tentar INSERT via Repository
            // ====================================
            echo "<div class='step'>PASSO 7: Executando INSERT via Repository</div>";
            
            echo "<div class='info'>📝 Chamando: \$applicationsRepo->insert(\$dados)</div>";
            
            $newId = $applicationsRepo->insert($dados);
            
            if ($newId) {
                echo "<div class='status ok'>✓✓✓ INSERT VIA REPOSITORY: SUCESSO! ✓✓✓</div>";
                echo "<div class='status ok'>ID Gerado: <strong>$newId</strong></div>";
                
                // Buscar o registro
                $stmt = $pdo->prepare("SELECT * FROM adms_training_applications WHERE id = ?");
                $stmt->execute([$newId]);
                $registro = $stmt->fetch();
                
                echo "<h3>Registro salvo via Repository:</h3>";
                echo "<pre>" . print_r($registro, true) . "</pre>";
                
            } else {
                echo "<div class='status error'>✗✗✗ INSERT VIA REPOSITORY: FALHOU! ✗✗✗</div>";
                echo "<div class='status error'>Retornou: " . var_export($newId, true) . "</div>";
            }
            
            // ====================================
            // PASSO 8: Verificar total de registros
            // ====================================
            echo "<div class='step'>PASSO 8: Verificando registros na tabela</div>";
            
            $stmtCount = $pdo->query("SELECT COUNT(*) as total FROM adms_training_applications");
            $total = $stmtCount->fetch();
            
            echo "<div class='status info'>Total de registros na tabela: <strong>{$total['total']}</strong></div>";
            
            if ($total['total'] > 0) {
                $stmtRecentes = $pdo->query("SELECT id, adms_user_id, adms_training_id, data_realizacao, nota, status, created_at 
                                            FROM adms_training_applications 
                                            ORDER BY id DESC LIMIT 5");
                $recentes = $stmtRecentes->fetchAll();
                
                echo "<h3>Últimos 5 registros (incluindo testes):</h3>";
                echo "<table border='1' cellpadding='8' style='width:100%; border-collapse: collapse;'>";
                echo "<tr style='background: #2E9263; color: white;'>";
                echo "<th>ID</th><th>User</th><th>Training</th><th>Data Realiz.</th><th>Nota</th><th>Status</th><th>Criado em</th>";
                echo "</tr>";
                
                foreach ($recentes as $reg) {
                    $isTeste = strpos($reg['status'], 'teste') !== false;
                    $bgColor = $isTeste ? '#fff3cd' : '#ffffff';
                    
                    echo "<tr style='background: $bgColor;'>";
                    echo "<td>{$reg['id']}</td>";
                    echo "<td>{$reg['adms_user_id']}</td>";
                    echo "<td>{$reg['adms_training_id']}</td>";
                    echo "<td>{$reg['data_realizacao']}</td>";
                    echo "<td>{$reg['nota']}</td>";
                    echo "<td>{$reg['status']}</td>";
                    echo "<td>{$reg['created_at']}</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
                echo "<p><em>Registros em amarelo são de teste e serão removidos.</em></p>";
            }
            
            // ====================================
            // PASSO 9: Limpeza
            // ====================================
            echo "<div class='step'>PASSO 9: Limpando registros de teste</div>";
            
            $stmtDelete = $pdo->prepare("DELETE FROM adms_training_applications WHERE status LIKE 'teste%'");
            $stmtDelete->execute();
            $deleted = $stmtDelete->rowCount();
            
            echo "<div class='status ok'>✓ Registros de teste removidos: $deleted</div>";
            
        } catch (PDOException $e) {
            echo "<div class='status error'>";
            echo "<strong>✗ ERRO PDO:</strong><br>";
            echo "Código: " . $e->getCode() . "<br>";
            echo "Mensagem: " . htmlspecialchars($e->getMessage());
            echo "</div>";
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
            <strong>✅ Se o INSERT via Repository funcionou:</strong>
            <ul>
                <li>O código do Repository está correto</li>
                <li>O problema está nas validações do Controller</li>
                <li>Alguma validação está falhando silenciosamente</li>
                <li>Precisa ativar log_errors para ver onde está falhando</li>
            </ul>
        </div>
        
        <div class="status error">
            <strong>❌ Se o INSERT via Repository falhou:</strong>
            <ul>
                <li>Há um problema no código do Repository</li>
                <li>Verifique o erro SQL acima</li>
                <li>Pode ser problema de autoload ou namespace</li>
            </ul>
        </div>
        
        <hr>
        
        <h2>📋 Próximos Passos</h2>
        
        <ol>
            <li><strong>Se funcionou:</strong> O problema está nas validações do ApplyTraining.php. Ative log_errors para capturar.</li>
            <li><strong>Se falhou:</strong> Verifique o erro mostrado acima e corrija o Repository.</li>
            <li><strong>Tente salvar</strong> uma aplicação real via tela normal e compare com este teste.</li>
            <li><strong>REMOVA</strong> este arquivo após o diagnóstico!</li>
        </ol>
        
        <hr>
        <p><strong>⚠️ IMPORTANTE:</strong> Após o teste, <strong>DELETE ESTE ARQUIVO</strong>!</p>
        <div style="background: #f4f4f4; padding: 10px; font-family: monospace;">
            rm scripts/teste_apply_training.php
        </div>
    </div>
</body>
</html>

