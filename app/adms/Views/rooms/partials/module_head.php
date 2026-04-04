<?php
/**
 * CSS do módulo Reserva de Salas (mobile + desktop).
 * Incluir no topo de cada view em app/adms/Views/rooms/*.php
 */
$urlAdm = rtrim((string)($_ENV['URL_ADM'] ?? ''), '/');
?>
<link rel="stylesheet" href="<?= htmlspecialchars($urlAdm, ENT_QUOTES, 'UTF-8'); ?>/public/adms/css/rooms-module.css?v=20260405">
