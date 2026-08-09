<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/adms/Helpers/EnvLoader.php';
\App\adms\Helpers\EnvLoader::load();

use App\adms\Models\Services\InternalChat\ChatDynamicReportService;
use App\adms\Models\Services\InternalChat\LocalInternalChatAgent;

$chart = ChatDynamicReportService::buildChartFromRows([
    ['id' => 8, 'name' => 'Analista de CQ Microbiológico Jr'],
    ['id' => 9, 'name' => 'Analista de Desenvolvimento Analítico Jr'],
], 'table');
echo 'chart_id_name=' . ($chart === null ? 'null_ok' : 'FAIL') . "\n";

$chart2 = ChatDynamicReportService::buildChartFromRows([
    ['departamento' => 'TI', 'total' => 3],
    ['departamento' => 'Produção', 'total' => 82],
], 'bar_chart');
echo 'chart_total=' . (($chart2['title'] ?? '') === 'total' ? 'ok' : 'FAIL') . "\n";

$agent = new LocalInternalChatAgent();
$r = $agent->handle('inativos em janeiro', ['user_id' => 0]);
echo 'tool=' . ($r['tool'] ?? '?') . "\n";
echo 'resposta=' . preg_replace('/\s+/u', ' ', (string) ($r['resposta'] ?? '')) . "\n";
