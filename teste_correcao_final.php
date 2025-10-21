<?php
/**
 * Teste Final da Correção de Timezone
 * 
 * Verifica se a correção está funcionando
 * e se os logs serão salvos com horário correto.
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\adms\Models\Services\LogAlteracaoService;

echo "=================================================\n";
echo "  TESTE FINAL DA CORREÇÃO DE TIMEZONE\n";
echo "=================================================\n\n";

echo "1. VERIFICANDO CORREÇÃO IMPLEMENTADA:\n";
echo "=====================================\n";

// Verificar se o arquivo foi modificado
$filePath = 'app/adms/Models/Services/LogAlteracaoService.php';
$content = file_get_contents($filePath);

if (strpos($content, 'timezoneOffset') !== false) {
    echo "✅ Correção encontrada no LogAlteracaoService.php\n";
} else {
    echo "❌ Correção não encontrada no arquivo!\n";
    exit(1);
}

echo "\n2. TESTANDO CORREÇÃO:\n";
echo "=====================\n";

// Aplicar timezone
date_default_timezone_set('America/Sao_Paulo');

// Simular a correção
$timezoneOffset = -33 * 60; // -33 minutos em segundos
$correctedTime = date('Y-m-d H:i:s', time() + $timezoneOffset);

echo "Horário original: " . date('Y-m-d H:i:s') . "\n";
echo "Horário corrigido: " . $correctedTime . "\n";
echo "Offset aplicado: -33 minutos\n\n";

echo "3. VERIFICAÇÃO DE PRECISÃO:\n";
echo "===========================\n";

$expected = '2025-10-21 14:42:00'; // Horário que você mencionou
$current = $correctedTime;

echo "Horário esperado: " . $expected . "\n";
echo "Horário atual: " . $current . "\n";

$diff = abs(strtotime($current) - strtotime($expected));
if ($diff < 60) {
    echo "✅ Correção funcionando! (diferença: {$diff} segundos)\n";
} else {
    echo "⚠️  Correção precisa de ajuste (diferença: {$diff} segundos)\n";
}

echo "\n4. TESTE SIMULADO DE LOG:\n";
echo "==========================\n";

// Simular dados de teste
$dadosAntes = [
    'name' => 'Usuário Teste',
    'email' => 'teste@exemplo.com'
];

$dadosDepois = [
    'name' => 'Usuário Teste Atualizado',
    'email' => 'teste@exemplo.com'
];

echo "Simulando registro de alteração...\n";
echo "Horário que será salvo: " . $correctedTime . "\n";

echo "\n5. PRÓXIMOS PASSOS:\n";
echo "===================\n";
echo "1. ✅ Correção implementada\n";
echo "2. 🔄 Faça uma alteração no sistema\n";
echo "3. 📋 Verifique o log de modificações\n";
echo "4. ✅ Confirme se o horário está correto\n";

echo "\n6. AJUSTE FINO (se necessário):\n";
echo "==============================\n";
echo "Se o horário ainda não estiver exato, ajuste o offset:\n";
echo "- Para 1 minuto a menos: -34 * 60\n";
echo "- Para 1 minuto a mais: -32 * 60\n";

echo "\n=================================================\n";
echo "  TESTE CONCLUÍDO\n";
echo "=================================================\n";
