<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Cria a tabela rh_candidatos_anexos para armazenar arquivos relacionados
 * aos candidatos (ex.: currículo, certificados), permitindo posterior
 * exclusão segura no processo de anonimização LGPD.
 */
final class CreateRhCandidatosAnexos extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('rh_candidatos_anexos')) {
            return;
        }

        $this->table('rh_candidatos_anexos')
            ->addColumn('rh_candidato_id', 'integer', [
                'null'   => false,
                'signed' => false,
            ])
            ->addColumn('tipo', 'string', [
                'limit'   => 50,
                'null'    => false,
                'default' => 'curriculo',
            ])
            ->addColumn('arquivo_caminho', 'string', [
                'limit' => 255,
                'null'  => false,
            ])
            ->addColumn('nome_original', 'string', [
                'limit'   => 255,
                'null'    => true,
                'default' => null,
            ])
            ->addColumn('created_at', 'datetime', [
                'null'    => false,
                'default' => 'CURRENT_TIMESTAMP',
            ])
            ->addIndex(['rh_candidato_id'])
            ->addIndex(['tipo'])
            ->create();
    }

    public function down(): void
    {
        if ($this->hasTable('rh_candidatos_anexos')) {
            $this->table('rh_candidatos_anexos')->drop()->save();
        }
    }
}


