<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Cria a tabela rh_candidatos para controle de currículos e candidatos
 * no módulo de Gestão de Pessoas, com campos específicos para LGPD
 * (retenção, anonimização, vínculo com termos/consentimentos).
 */
final class CreateRhCandidatos extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('rh_candidatos')) {
            return;
        }

        $this->table('rh_candidatos')
            // Dados básicos de identificação/contato
            ->addColumn('nome', 'string', [
                'limit' => 150,
                'null'  => false,
            ])
            ->addColumn('email', 'string', [
                'limit' => 150,
                'null'  => true,
            ])
            ->addColumn('telefone', 'string', [
                'limit' => 50,
                'null'  => true,
            ])
            ->addColumn('cidade', 'string', [
                'limit' => 100,
                'null'  => true,
            ])
            ->addColumn('estado', 'string', [
                'limit' => 2,
                'null'  => true,
            ])

            // Origem e status do processo seletivo
            ->addColumn('origem', 'string', [
                'limit'   => 50,
                'null'    => false,
                'default' => 'manual',
                'comment' => 'email, whatsapp, form_trabalhe_conosco, manual, etc.',
            ])
            ->addColumn('status_processo', 'string', [
                'limit'   => 30,
                'null'    => false,
                'default' => 'recebido',
                'comment' => 'recebido, em_entrevista, reprovado, banco_talentos, contratado',
            ])
            ->addColumn('data_cadastramento', 'datetime', [
                'null'    => false,
                'default' => 'CURRENT_TIMESTAMP',
            ])
            ->addColumn('data_ultimo_movimento', 'datetime', [
                'null'    => true,
                'default' => null,
            ])
            ->addColumn('observacoes', 'text', [
                'null' => true,
            ])

            // Integração com LGPD (termos, consentimentos, retenção)
            ->addColumn('lgpd_termo_id', 'integer', [
                'null'   => true,
                'signed' => false,
            ])
            ->addColumn('lgpd_consentimento_id', 'integer', [
                'null'   => true,
                'signed' => false,
            ])
            ->addColumn('lgpd_status', 'string', [
                'limit'   => 20,
                'null'    => false,
                'default' => 'Ativo',
                'comment' => 'Ativo, Vencido, Anonimizado',
            ])
            ->addColumn('lgpd_data_consentimento', 'datetime', [
                'null'    => true,
                'default' => null,
            ])
            ->addColumn('lgpd_data_expiracao', 'datetime', [
                'null'    => true,
                'default' => null,
            ])
            ->addColumn('lgpd_motivo_anonimizacao', 'string', [
                'limit'   => 255,
                'null'    => true,
                'default' => null,
            ])

            // Auditoria padrão
            ->addColumn('created_at', 'datetime', [
                'null'    => false,
                'default' => 'CURRENT_TIMESTAMP',
            ])
            ->addColumn('updated_at', 'datetime', [
                'null'    => true,
                'default' => null,
            ])

            // Índices para performance e filtros
            ->addIndex(['status_processo'])
            ->addIndex(['origem'])
            ->addIndex(['lgpd_status'])
            ->addIndex(['lgpd_data_expiracao'])
            ->create();
    }

    public function down(): void
    {
        if ($this->hasTable('rh_candidatos')) {
            $this->table('rh_candidatos')->drop()->save();
        }
    }
}


