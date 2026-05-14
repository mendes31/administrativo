<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Tabela `sales_portal_user_links` (histórico): foi criada numa fase inicial e removida
 * pela migration `RemoveSalesPortalUserPartnerLinks` — o produto não fixa utilizador ↔ CardCode.
 */
final class CreateSalesPortalUserLinks extends AbstractMigration
{
    public function change(): void
    {
        if ($this->hasTable('sales_portal_user_links')) {
            return;
        }

        if (!$this->hasTable('adms_users')) {
            return;
        }

        $this->table('sales_portal_user_links')
            ->addColumn('adms_user_id', 'integer', [
                'signed' => false,
                'null' => false,
                'comment' => 'Utilizador do administrativo',
            ])
            ->addColumn('sap_card_code', 'string', [
                'limit' => 50,
                'null' => false,
                'comment' => 'CardCode do PN no SAP B1 (OCRD.CardCode)',
            ])
            ->addColumn('default_whscode', 'string', [
                'limit' => 20,
                'null' => true,
                'comment' => 'Depósito padrão B1 (opcional)',
            ])
            ->addColumn('active', 'boolean', [
                'default' => true,
                'null' => false,
                'comment' => 'Vínculo ativo',
            ])
            ->addColumn('notes', 'text', ['null' => true])
            ->addColumn('created_by', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('updated_by', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
            ])
            ->addIndex(['adms_user_id'], ['unique' => true])
            ->addIndex(['sap_card_code'])
            ->addIndex(['active'])
            ->addForeignKey('adms_user_id', 'adms_users', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
            ])
            ->addForeignKey('created_by', 'adms_users', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
            ])
            ->addForeignKey('updated_by', 'adms_users', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
            ])
            ->create();
    }
}
