<?php
/**
 * Diagnóstico simples de configuração de sessão do PHP.
 *
 * Use apenas para teste; remova após verificar os valores.
 */

header('Content-Type: text/plain; charset=utf-8');

echo "Data/hora servidor: " . date('Y-m-d H:i:s') . "\n\n";

echo "session.gc_maxlifetime  = " . ini_get('session.gc_maxlifetime') . " segundos\n";
echo "session.cookie_lifetime = " . ini_get('session.cookie_lifetime') . " segundos\n";
echo "session.save_path       = " . ini_get('session.save_path') . "\n";
echo "session.name            = " . session_name() . "\n";

