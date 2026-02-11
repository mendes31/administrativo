<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Cria a tabela lgpd_politicas_retencao para parametrizar prazos de retenção
 * de dados (ex.: currículos não aproveitados, banco de talentos).
 *
 * Mantém a estrutura simples e segura:
 * - Verifica existência da tabela antes de criar;
 * - Adiciona índices em campos de busca;
 * - Evita recriar dados em ambientes já configurados.
 */
final class CreateLgpdPoliticasRetencao extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('lgpd_politicas_retencao')) {
            return;
        }

        $this->table('lgpd_politicas_retencao')
            ->addColumn('contexto', 'string', [
                'limit'   => 100,
                'null'    => false,
                'comment' => 'Ex.: curriculo_nao_aproveitado, banco_talentos',
            ])
            ->addColumn('descricao', 'string', [
                'limit'   => 255,
                'null'    => true,
            ])
            ->addColumn('prazo_meses', 'integer', [
                'null'    => false,
                'default' => 12,
                'comment' => 'Prazo de retenção em meses',
            ])
            ->addColumn('status', 'string', [
                'limit'   => 20,
                'null'    => false,
                'default' => 'Ativo',
            ])
            ->addColumn('created_at', 'datetime', [
                'null'    => false,
                'default' => 'CURRENT_TIMESTAMP',
            ])
            ->addColumn('updated_at', 'datetime', [
                'null'    => true,
                'default' => null,
            ])
            ->addIndex(['contexto'], ['unique' => true])
            ->addIndex(['status'])
            ->create();

        // Seeds básicos, idempotentes usando INSERT IGNORE para evitar erro
        // caso a migration seja executada mais de uma vez.
        $rows = [
            [
                'contexto'   => 'curriculo_nao_aproveitado',
                'descricao'  => 'Currículos não aproveitados (recebido/reprovado sem contratação)',
                'prazo_meses'=> 6,
                'status'     => 'Ativo',
            ],
            [
                'contexto'   => 'banco_talentos',
                'descricao'  => 'Currículos mantidos em banco de talentos',
                'prazo_meses'=> 12,
                'status'     => 'Ativo',
            ],
        ];

        foreach ($rows as $row) {
            $this->execute(sprintf(
                "INSERT IGNORE INTO lgpd_politicas_retencao (contexto, descricao, prazo_meses, status, created_at)
                 VALUES ('%s', '%s', %d, '%s', NOW())",
                addslashes($row['contexto']),
                addslashes($row['descricao']),
                (int) $row['prazo_meses'],
                addslashes($row['status'])
            ));
        }
    }

    public function down(): void
    {
        if ($this->hasTable('lgpd_politicas_retencao')) {
            $this->table('lgpd_politicas_retencao')->drop()->save();
        }
    }
}


