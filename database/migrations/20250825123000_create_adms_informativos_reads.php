<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAdmsInformativosReads extends AbstractMigration
{
    public function up(): void
    {
        // add requires_ack to adms_informativos if not exists
        $infTable = $this->table('adms_informativos');
        if (!$infTable->hasColumn('requires_ack')) {
            $infTable->addColumn('requires_ack', 'boolean', [
                'default' => false,
                'after' => 'urgente',
                'comment' => 'Se exige ciência do usuário'
            ])->save();
        }

        $table = $this->table('adms_informativos_reads', [
            'id' => false,
            'primary_key' => ['id']
        ]);
        $table->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('informativo_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('read_at', 'datetime', ['null' => true])
            ->addColumn('acknowledged', 'boolean', ['default' => false])
            ->addColumn('ack_at', 'datetime', ['null' => true])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['informativo_id', 'user_id'], ['unique' => true, 'name' => 'uq_inf_user'])
            ->addIndex(['user_id'])
            ->create();

        // add FKs separately for compatibility
        $this->execute('ALTER TABLE adms_informativos_reads ADD CONSTRAINT fk_read_inf FOREIGN KEY (informativo_id) REFERENCES adms_informativos(id) ON DELETE CASCADE ON UPDATE CASCADE');
        $this->execute('ALTER TABLE adms_informativos_reads ADD CONSTRAINT fk_read_user FOREIGN KEY (user_id) REFERENCES adms_users(id) ON DELETE CASCADE ON UPDATE CASCADE');
    }

    public function down(): void
    {
        $this->execute('ALTER TABLE adms_informativos_reads DROP FOREIGN KEY fk_read_inf');
        $this->execute('ALTER TABLE adms_informativos_reads DROP FOREIGN KEY fk_read_user');
        $this->table('adms_informativos_reads')->drop()->save();

        // keep requires_ack column for forward-compat; uncomment if need to drop
        // $infTable = $this->table('adms_informativos');
        // if ($infTable->hasColumn('requires_ack')) {
        //     $infTable->removeColumn('requires_ack')->save();
        // }
    }
}


