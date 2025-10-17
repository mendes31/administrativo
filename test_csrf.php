<?php
/**
 * Script de Teste para Token CSRF
 * Execute este arquivo para verificar se o token CSRF está funcionando corretamente
 */

require './vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__);
$dotenv->load();

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h2>Teste de Token CSRF</h2>";

// Limpar tokens existentes
unset($_SESSION['csrf_tokens']);

// Gerar novo token
$token = \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_login');

echo "<h3>1. Token Gerado</h3>";
echo "<p>Token: <code>{$token}</code></p>";
echo "<p>Tokens na sessão: <pre>" . json_encode($_SESSION['csrf_tokens'], JSON_PRETTY_PRINT) . "</pre></p>";

// Testar validação do mesmo token
echo "<h3>2. Teste de Validação (Mesmo Token)</h3>";
$isValid = \App\adms\Helpers\CSRFHelper::validateCSRFToken('form_login', $token);
echo "<p>Resultado: " . ($isValid ? '✅ VÁLIDO' : '❌ INVÁLIDO') . "</p>";

// Verificar se o token foi invalidado
echo "<p>Tokens na sessão após validação: <pre>" . json_encode($_SESSION['csrf_tokens'] ?? [], JSON_PRETTY_PRINT) . "</pre></p>";

// Testar validação do token inválido
echo "<h3>3. Teste de Validação (Token Inválido)</h3>";
$isValid2 = \App\adms\Helpers\CSRFHelper::validateCSRFToken('form_login', $token);
echo "<p>Resultado: " . ($isValid2 ? '✅ VÁLIDO' : '❌ INVÁLIDO') . "</p>";

// Gerar novo token para teste de duplo submit
echo "<h3>4. Teste de Duplo Submit</h3>";
$token2 = \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_login');
echo "<p>Novo Token: <code>{$token2}</code></p>";

// Simular primeiro submit
echo "<h4>Primeiro Submit:</h4>";
$isValid3 = \App\adms\Helpers\CSRFHelper::validateCSRFToken('form_login', $token2);
echo "<p>Resultado: " . ($isValid3 ? '✅ VÁLIDO' : '❌ INVÁLIDO') . "</p>";

// Simular segundo submit (deve falhar)
echo "<h4>Segundo Submit (deve falhar):</h4>";
$isValid4 = \App\adms\Helpers\CSRFHelper::validateCSRFToken('form_login', $token2);
echo "<p>Resultado: " . ($isValid4 ? '✅ VÁLIDO' : '❌ INVÁLIDO') . "</p>";

echo "<hr>";
echo "<h3>Conclusão:</h3>";
echo "<p>Se o primeiro teste passou e o segundo falhou, o CSRF está funcionando corretamente.</p>";
echo "<p>Se ambos falharam, há um problema na geração ou validação.</p>";
?>
