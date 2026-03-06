<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddWhatsappNotificationsFlagToAdmsUsers extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('adms_users');

        if (!$table->hasColumn('receber_notificacoes_whatsapp')) {
            $table
                ->addColumn('receber_notificacoes_whatsapp', 'boolean', [
                    'default' => 1,
                    'null' => false,
                    'after' => 'lgpd_consent_version',
                    'comment' => '0 = não deseja receber notificações via WhatsApp; 1 = deseja receber',
                ])
                ->save();
        }
    }
}

