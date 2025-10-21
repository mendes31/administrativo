<?php

use Phinx\Migration\AbstractMigration;

/**
 * Migration para adicionar coluna hostname nas tabelas de logs
 * 
 * Esta migration adiciona a coluna 'hostname' nas tabelas:
 * - adms_log_alteracoes
 * - adms_log_acessos
 * 
 * A coluna armazenará o hostname do equipamento cliente quando disponível.
 */
class AddHostnameToLogs extends AbstractMigration
{
    /**
     * Adiciona a coluna hostname nas tabelas de logs
     */
    public function up()
    {
        // Adicionar coluna hostname na tabela de logs de alterações
        if ($this->hasTable('adms_log_alteracoes')) {
            $table = $this->table('adms_log_alteracoes');
            if (!$table->hasColumn('hostname')) {
                $table->addColumn('hostname', 'string', [
                    'limit' => 255,
                    'null' => true,
                    'after' => 'ip',
                    'comment' => 'Hostname do equipamento cliente'
                ])
                ->update();
            }
        }

        // Adicionar coluna hostname na tabela de logs de acesso
        if ($this->hasTable('adms_log_acessos')) {
            $table = $this->table('adms_log_acessos');
            if (!$table->hasColumn('hostname')) {
                $table->addColumn('hostname', 'string', [
                    'limit' => 255,
                    'null' => true,
                    'after' => 'ip',
                    'comment' => 'Hostname do equipamento cliente'
                ])
                ->update();
            }
        }

        // Adicionar coluna hostname na tabela de logs LGPD (se existir)
        if ($this->hasTable('lgpd_logs_lgpd')) {
            $table = $this->table('lgpd_logs_lgpd');
            if (!$table->hasColumn('hostname')) {
                $table->addColumn('hostname', 'string', [
                    'limit' => 255,
                    'null' => true,
                    'after' => 'ip',
                    'comment' => 'Hostname do equipamento cliente'
                ])
                ->update();
            }
        }

        // Adicionar coluna hostname na tabela de logs padrão (se existir)
        if ($this->hasTable('adms_logs')) {
            $table = $this->table('adms_logs');
            if (!$table->hasColumn('hostname')) {
                $table->addColumn('hostname', 'string', [
                    'limit' => 255,
                    'null' => true,
                    'comment' => 'Hostname do equipamento cliente'
                ])
                ->update();
            }
        }
    }

    /**
     * Remove a coluna hostname das tabelas de logs
     */
    public function down()
    {
        // Remover coluna hostname da tabela de logs de alterações
        if ($this->hasTable('adms_log_alteracoes')) {
            $table = $this->table('adms_log_alteracoes');
            if ($table->hasColumn('hostname')) {
                $table->removeColumn('hostname')
                    ->update();
            }
        }

        // Remover coluna hostname da tabela de logs de acesso
        if ($this->hasTable('adms_log_acessos')) {
            $table = $this->table('adms_log_acessos');
            if ($table->hasColumn('hostname')) {
                $table->removeColumn('hostname')
                    ->update();
            }
        }

        // Remover coluna hostname da tabela de logs LGPD
        if ($this->hasTable('lgpd_logs_lgpd')) {
            $table = $this->table('lgpd_logs_lgpd');
            if ($table->hasColumn('hostname')) {
                $table->removeColumn('hostname')
                    ->update();
            }
        }

        // Remover coluna hostname da tabela de logs padrão
        if ($this->hasTable('adms_logs')) {
            $table = $this->table('adms_logs');
            if ($table->hasColumn('hostname')) {
                $table->removeColumn('hostname')
                    ->update();
            }
        }
    }
}

