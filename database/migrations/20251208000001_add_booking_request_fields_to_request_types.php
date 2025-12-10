<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddBookingRequestFieldsToRequestTypes extends AbstractMigration
{
    public function change(): void
    {
        // Adicionar colunas necessárias para solicitações de reserva de salas
        if ($this->hasTable('adms_request_types')) {
            $table = $this->table('adms_request_types');
            
            // Verificar se as colunas já existem antes de adicionar usando query SQL
            $columns = $this->fetchAll("SHOW COLUMNS FROM adms_request_types LIKE 'requires_responsible'");
            if (empty($columns)) {
                $table->addColumn('requires_responsible', 'boolean', [
                    'default' => true, 
                    'null' => true,
                    'comment' => 'Requer seleção de responsável (para reserva de salas)'
                ])->update();
            }
            
            $columns = $this->fetchAll("SHOW COLUMNS FROM adms_request_types LIKE 'default_responsible_user_id'");
            if (empty($columns)) {
                $table->addColumn('default_responsible_user_id', 'integer', [
                    'signed' => false, 
                    'null' => true, 
                    'comment' => 'ID do responsável padrão (para reserva de salas)'
                ])->update();
                
                // Adicionar índice e foreign key
                $table->addIndex(['default_responsible_user_id'])->update();
                $table->addForeignKey('default_responsible_user_id', 'adms_users', 'id', [
                    'delete' => 'SET_NULL', 
                    'update' => 'NO_ACTION'
                ])->update();
            }
            
            $columns = $this->fetchAll("SHOW COLUMNS FROM adms_request_types LIKE 'requires_quantity'");
            if (empty($columns)) {
                $table->addColumn('requires_quantity', 'boolean', [
                    'default' => false, 
                    'null' => true,
                    'comment' => 'Requer quantidade (para reserva de salas)'
                ])->update();
            }
            
            // Adicionar 'is_active' se não existir (já existe 'status', mas vamos adicionar 'is_active' também)
            $columns = $this->fetchAll("SHOW COLUMNS FROM adms_request_types LIKE 'is_active'");
            if (empty($columns)) {
                $table->addColumn('is_active', 'boolean', [
                    'default' => true, 
                    'null' => true,
                    'comment' => 'Se está ativo (para reserva de salas)'
                ])->update();
                
                $table->addIndex(['is_active'])->update();
            }
        }
    }
}

