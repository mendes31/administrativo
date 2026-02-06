<?php
use Phinx\Migration\AbstractMigration;

class AddAuditFieldsToLgpdConsentimentos extends AbstractMigration
{
    public function up()
    {
        // A tabela lgpd_consentimentos já foi criada na migration anterior (20250725181000)
        if (!$this->hasTable('lgpd_consentimentos')) {
            return; // Segurança: se a tabela não existir, pular
        }
        
        $table = $this->table('lgpd_consentimentos');
        
        // Campos de auditoria técnica (ALTA PRIORIDADE)
        if (!$table->hasColumn('ip_address')) {
            $table->addColumn('ip_address', 'string', ['limit' => 45, 'null' => true, 'comment' => 'Endereço IP do titular'])
                  ->addColumn('user_agent', 'text', ['null' => true, 'comment' => 'User Agent do navegador'])
                  ->addColumn('consent_hash', 'string', ['limit' => 64, 'null' => true, 'comment' => 'SHA-256 do conteúdo do consentimento'])
                  ->addColumn('timestamp_milliseconds', 'biginteger', ['null' => true, 'comment' => 'Timestamp com milissegundos'])
                  ->addColumn('created_by_user_id', 'integer', ['null' => true, 'comment' => 'ID do usuário que criou (se foi criado manualmente)'])
                  ->addColumn('revoked_by_user_id', 'integer', ['null' => true, 'comment' => 'ID do usuário que revogou'])
                  ->addColumn('revoked_at', 'datetime', ['null' => true, 'comment' => 'Data/hora da revogação'])
                  ->addColumn('revocation_reason', 'text', ['null' => true, 'comment' => 'Motivo da revogação'])
                  ->addColumn('updated_by_user_id', 'integer', ['null' => true, 'comment' => 'ID do usuário que atualizou'])
                  ->addColumn('collection_method', 'enum', ['values' => ['web_form', 'api', 'email', 'sms', 'sistema_login', 'importacao'], 'default' => 'sistema_login', 'null' => true, 'comment' => 'Método de coleta'])
                  ->addColumn('referrer_url', 'text', ['null' => true, 'comment' => 'URL de origem'])
                  ->addColumn('origin_url', 'text', ['null' => true, 'comment' => 'URL da página onde foi coletado'])
                  ->update();
        }
        
        // Criar tabela de histórico
        if (!$this->hasTable('lgpd_consentimentos_historico')) {
            $this->table('lgpd_consentimentos_historico', ['id' => 'id'])
                ->addColumn('consentimento_id', 'integer', ['null' => false, 'comment' => 'ID do consentimento'])
                ->addColumn('acao', 'enum', ['values' => ['criado', 'atualizado', 'revogado', 'reativado', 'expirado'], 'null' => false, 'comment' => 'Tipo de ação'])
                ->addColumn('usuario_id', 'integer', ['null' => true, 'comment' => 'ID do usuário que fez a ação'])
                ->addColumn('dados_anteriores', 'text', ['null' => true, 'comment' => 'JSON com dados antes da alteração'])
                ->addColumn('dados_novos', 'text', ['null' => true, 'comment' => 'JSON com dados após a alteração'])
                ->addColumn('motivo', 'text', ['null' => true, 'comment' => 'Motivo da alteração'])
                ->addColumn('ip_address', 'string', ['limit' => 45, 'null' => true, 'comment' => 'IP do usuário que fez a ação'])
                ->addColumn('user_agent', 'text', ['null' => true, 'comment' => 'User Agent do usuário'])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'comment' => 'Data/hora da ação'])
                ->addIndex(['consentimento_id'], ['name' => 'idx_consentimento'])
                ->addIndex(['acao'], ['name' => 'idx_acao'])
                ->addIndex(['usuario_id'], ['name' => 'idx_usuario'])
                ->addIndex(['created_at'], ['name' => 'idx_created_at'])
                ->create();
        }
    }

    public function down()
    {
        // Verificar se a tabela existe antes de tentar remover colunas
        if (!$this->hasTable('lgpd_consentimentos')) {
            return; // Tabela não existe, nada a fazer
        }
        
        $table = $this->table('lgpd_consentimentos');
        
        if ($table->hasColumn('ip_address')) {
            $table->removeColumn('ip_address')
                  ->removeColumn('user_agent')
                  ->removeColumn('consent_hash')
                  ->removeColumn('timestamp_milliseconds')
                  ->removeColumn('created_by_user_id')
                  ->removeColumn('revoked_by_user_id')
                  ->removeColumn('revoked_at')
                  ->removeColumn('revocation_reason')
                  ->removeColumn('updated_by_user_id')
                  ->removeColumn('collection_method')
                  ->removeColumn('referrer_url')
                  ->removeColumn('origin_url')
                  ->update();
        }
        
        if ($this->hasTable('lgpd_consentimentos_historico')) {
            $this->table('lgpd_consentimentos_historico')->drop()->save();
        }
    }
}

