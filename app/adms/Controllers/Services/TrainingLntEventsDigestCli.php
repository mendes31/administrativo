<?php

namespace App\adms\Controllers\Services;

require_once __DIR__ . '/../../../../vendor/autoload.php';

use App\adms\Helpers\EnvLoader;
use App\adms\Models\Services\TrainingLntEventService;

if (!EnvLoader::loadWithTimezone()) {
    echo "Erro ao carregar .env\n";
    exit(1);
}

if (php_sapi_name() !== 'cli') {
    echo "Execute via linha de comando.\n";
    exit(1);
}

$referenceDate = $argv[1] ?? null;
$service = new TrainingLntEventService();
$result = $service->sendDailyDigest($referenceDate);

if ($result['disabled']) {
    echo "Digest LNT desabilitado (training_lnt_event_digest_email).\n";
    exit(0);
}

echo sprintf(
    "Digest LNT: %d evento(s), %d destinatário(s), %d e-mail(s) enviado(s).\n",
    $result['events'],
    $result['recipients'],
    $result['sent']
);
