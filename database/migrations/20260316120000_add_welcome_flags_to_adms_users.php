<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddWelcomeFlagsToAdmsUsers extends AbstractMigration
{
    public function change(): void
    {
        if (!$this->hasTable('adms_users')) {
            return;
        }

        $table = $this->table('adms_users');

        if (!$table->hasColumn('enviar_boas_vindas_email')) {
            $table->addColumn('enviar_boas_vindas_email', 'boolean', [
                'default' => 0,
                'null' => false,
                'after' => 'modificar_senha_proximo_logon',
                'comment' => 'Enviar mensagem de boas-vindas por e-mail ao criar usuário',
            ]);
        }

        if (!$table->hasColumn('enviar_boas_vindas_whatsapp')) {
            $table->addColumn('enviar_boas_vindas_whatsapp', 'boolean', [
                'default' => 0,
                'null' => false,
                'after' => 'enviar_boas_vindas_email',
                'comment' => 'Enviar mensagem de boas-vindas por WhatsApp ao criar usuário',
            ]);
        }

        if (!$table->hasColumn('boas_vindas_enviado_em')) {
            $table->addColumn('boas_vindas_enviado_em', 'datetime', [
                'null' => true,
                'default' => null,
                'after' => 'enviar_boas_vindas_whatsapp',
                'comment' => 'Data/hora em que a mensagem de boas-vindas foi enviada',
            ]);
        }

        if (!$table->hasColumn('boas_vindas_enviado_por')) {
            $table->addColumn('boas_vindas_enviado_por', 'integer', [
                'null' => true,
                'signed' => false,
                'after' => 'boas_vindas_enviado_em',
                'comment' => 'ID do usuário que disparou a mensagem de boas-vindas',
            ]);
        }

        $table->update();
    }
}

