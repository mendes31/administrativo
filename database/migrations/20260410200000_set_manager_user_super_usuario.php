<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Utilizador técnico padrão «manager»: nasce como super usuário em instalações novas (seed)
 * e esta migração alinha bases já existentes (outra empresa / deploy).
 */
final class SetManagerUserSuperUsuario extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_users')) {
            return;
        }
        if (!$this->table('adms_users')->hasColumn('super_usuario')) {
            return;
        }
        $this->execute("UPDATE adms_users SET super_usuario = 1 WHERE username = 'manager'");
    }

    public function down(): void
    {
        // Sem reversão intencional: rollback não deve retirar super usuário do manager em produção.
    }
}
