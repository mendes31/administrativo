<?php

use Phinx\Migration\AbstractMigration;

class CreateAdmsWhatsappConfig extends AbstractMigration
{
    public function change(): void
    {
        if (!$this->hasTable('adms_whatsapp_config')) {
            $table = $this->table('adms_whatsapp_config', ['id' => true]);
            
            $table->addColumn('api_provider', 'string', ['limit' => 50, 'comment' => 'Provedor da API (Evolution, Twilio, Meta, etc)'])
                  ->addColumn('api_url', 'string', ['limit' => 255, 'comment' => 'URL base da API'])
                  ->addColumn('api_key', 'string', ['limit' => 255, 'comment' => 'Chave da API'])
                  ->addColumn('api_token', 'text', ['null' => true, 'comment' => 'Token de autenticação'])
                  ->addColumn('instance_name', 'string', ['limit' => 100, 'null' => true, 'comment' => 'Nome da instância/número'])
                  ->addColumn('phone_number', 'string', ['limit' => 20, 'null' => true, 'comment' => 'Número WhatsApp'])
                  ->addColumn('webhook_url', 'string', ['limit' => 255, 'null' => true, 'comment' => 'URL para receber webhooks'])
                  ->addColumn('is_active', 'boolean', ['default' => true, 'comment' => 'Configuração ativa'])
                  ->addColumn('created_at', 'datetime', ['null' => true])
                  ->addColumn('updated_at', 'datetime', ['null' => true])
                  ->create();
        }
    }
}

