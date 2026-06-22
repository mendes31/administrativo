<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * QR Code por equipamento + evidência de assinatura na vistoria.
 */
final class AddSstEquipamentoQrAndVistoriaAssinatura extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_sst_equipamentos') && !$this->table('adms_sst_equipamentos')->hasColumn('qr_token')) {
            $this->table('adms_sst_equipamentos')
                ->addColumn('qr_token', 'string', ['limit' => 64, 'null' => true, 'after' => 'codigo'])
                ->addIndex(['qr_token'], ['unique' => true, 'name' => 'uq_sst_equipamento_qr_token'])
                ->update();
        }

        if ($this->hasTable('adms_sst_equipamento_vistorias')) {
            $table = $this->table('adms_sst_equipamento_vistorias');
            if (!$table->hasColumn('assinatura_confirmada_em')) {
                $table->addColumn('assinatura_confirmada_em', 'datetime', ['null' => true, 'after' => 'observacao']);
            }
            if (!$table->hasColumn('assinatura_ip')) {
                $table->addColumn('assinatura_ip', 'string', ['limit' => 45, 'null' => true, 'after' => 'assinatura_confirmada_em']);
            }
            if (!$table->hasColumn('assinatura_user_agent')) {
                $table->addColumn('assinatura_user_agent', 'string', ['limit' => 255, 'null' => true, 'after' => 'assinatura_ip']);
            }
            $table->update();
        }

        if (!$this->hasTable('adms_sst_equipamentos') || !$this->table('adms_sst_equipamentos')->hasColumn('qr_token')) {
            return;
        }

        $rows = $this->fetchAll('SELECT id FROM adms_sst_equipamentos WHERE qr_token IS NULL OR qr_token = ""');
        foreach ($rows as $row) {
            $token = bin2hex(random_bytes(16));
            $this->execute(
                'UPDATE adms_sst_equipamentos SET qr_token = ' . $this->getAdapter()->getConnection()->quote($token)
                . ' WHERE id = ' . (int) $row['id']
            );
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_sst_equipamentos') && $this->table('adms_sst_equipamentos')->hasColumn('qr_token')) {
            $this->table('adms_sst_equipamentos')->removeIndexByName('uq_sst_equipamento_qr_token')->update();
            $this->table('adms_sst_equipamentos')->removeColumn('qr_token')->update();
        }

        if ($this->hasTable('adms_sst_equipamento_vistorias')) {
            $table = $this->table('adms_sst_equipamento_vistorias');
            foreach (['assinatura_user_agent', 'assinatura_ip', 'assinatura_confirmada_em'] as $col) {
                if ($table->hasColumn($col)) {
                    $table->removeColumn($col);
                }
            }
            $table->update();
        }
    }
}
