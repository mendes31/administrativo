<?php

namespace App\adms\Controllers\settings;

/**
 * URL legada: tools ficam na aba de mcp-api-config.
 */
class ListMcpChatTools
{
    public function index(): void
    {
        header('Location: ' . $_ENV['URL_ADM'] . 'mcp-api-config?tab=tools');
        exit;
    }
}
