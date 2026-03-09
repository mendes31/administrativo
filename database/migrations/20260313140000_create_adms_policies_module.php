<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAdmsPoliciesModule extends AbstractMigration
{
    public function up(): void
    {
        /**
         * Categorias de Políticas Internas
         */
        if (!$this->hasTable('adms_policies_categorias')) {
            $table = $this->table('adms_policies_categorias', [
                'id' => false,
                'primary_key' => ['id'],
            ]);

            $table
                ->addColumn('id', 'integer', [
                    'identity' => true,
                    'signed'   => false,
                ])
                ->addColumn('name', 'string', [
                    'limit'   => 120,
                    'null'    => false,
                    'comment' => 'Nome da categoria de política interna',
                ])
                ->addColumn('ativo', 'boolean', [
                    'default' => true,
                    'comment' => 'Se a categoria está ativa',
                ])
                ->addColumn('created_at', 'datetime', [
                    'default' => 'CURRENT_TIMESTAMP',
                    'comment' => 'Data de criação',
                ])
                ->addColumn('updated_at', 'datetime', [
                    'default' => 'CURRENT_TIMESTAMP',
                    'update'  => 'CURRENT_TIMESTAMP',
                    'comment' => 'Data de atualização',
                ])
                ->addIndex(['name'], ['unique' => true])
                ->addIndex(['ativo'])
                ->create();
        }

        /**
         * Tabela principal de Políticas Internas
         * Inspirada em adms_informativos + colunas extras de agendamento/notificação.
         */
        if (!$this->hasTable('adms_policies')) {
            $table = $this->table('adms_policies', [
                'id'          => false,
                'primary_key' => ['id'],
            ]);

            $table
                ->addColumn('id', 'integer', [
                    'identity' => true,
                    'signed'   => false,
                ])
                ->addColumn('titulo', 'string', [
                    'limit'   => 255,
                    'null'    => false,
                    'comment' => 'Título da política interna',
                ])
                ->addColumn('conteudo', 'text', [
                    'null'    => false,
                    'comment' => 'Conteúdo completo da política interna',
                ])
                ->addColumn('resumo', 'text', [
                    'null'    => true,
                    'comment' => 'Resumo da política (primeiras 150 letras)',
                ])
                ->addColumn('categoria', 'string', [
                    'limit'   => 100,
                    'null'    => false,
                    'default' => 'Geral',
                    'comment' => 'Categoria textual da política',
                ])
                ->addColumn('categoria_id', 'integer', [
                    'null'    => true,
                    'signed'  => false,
                    'comment' => 'FK para adms_policies_categorias',
                ])
                ->addColumn('imagem', 'string', [
                    'limit'   => 255,
                    'null'    => true,
                    'comment' => 'Caminho da imagem da política',
                ])
                ->addColumn('anexo', 'string', [
                    'limit'   => 255,
                    'null'    => true,
                    'comment' => 'Caminho do anexo da política',
                ])
                ->addColumn('urgente', 'boolean', [
                    'default' => false,
                    'comment' => 'Se a política é urgente/destaque',
                ])
                ->addColumn('notificar', 'boolean', [
                    'default' => false,
                    'null'    => false,
                    'comment' => 'Indica se deve disparar notificações (WhatsApp, etc.)',
                ])
                ->addColumn('requires_ack', 'boolean', [
                    'default' => false,
                    'comment' => 'Se exige ciência formal do usuário',
                ])
                ->addColumn('ativo', 'boolean', [
                    'default' => true,
                    'comment' => 'Status da política (ativo/inativo)',
                ])
                ->addColumn('usuario_id', 'integer', [
                    'signed'  => false,
                    'null'    => false,
                    'comment' => 'ID do usuário que criou a política',
                ])
                ->addColumn('department_id', 'integer', [
                    'null'    => true,
                    'signed'  => false,
                    'comment' => 'Departamento responsável pela política',
                ])
                ->addColumn('publish_at', 'datetime', [
                    'null'    => true,
                    'comment' => 'Data/hora de início de publicação',
                ])
                ->addColumn('expire_at', 'datetime', [
                    'null'    => true,
                    'comment' => 'Data/hora de expiração',
                ])
                ->addColumn('created_at', 'datetime', [
                    'default' => 'CURRENT_TIMESTAMP',
                    'comment' => 'Data de criação',
                ])
                ->addColumn('updated_at', 'datetime', [
                    'default' => 'CURRENT_TIMESTAMP',
                    'update'  => 'CURRENT_TIMESTAMP',
                    'comment' => 'Data de atualização',
                ])
                ->addIndex(['categoria'])
                ->addIndex(['categoria_id'])
                ->addIndex(['ativo'])
                ->addIndex(['urgente'])
                ->addIndex(['publish_at'])
                ->addIndex(['expire_at'])
                ->addIndex(['usuario_id'])
                ->addIndex(['department_id'])
                ->create();

            // FKs (criadas separadamente para maior compatibilidade entre bancos)
            $this->execute('ALTER TABLE adms_policies 
                ADD CONSTRAINT fk_policies_usuario 
                FOREIGN KEY (usuario_id) REFERENCES adms_users(id) 
                ON DELETE CASCADE ON UPDATE CASCADE');

            $this->execute('ALTER TABLE adms_policies 
                ADD CONSTRAINT fk_policies_categoria 
                FOREIGN KEY (categoria_id) REFERENCES adms_policies_categorias(id) 
                ON DELETE RESTRICT ON UPDATE CASCADE');

            $this->execute('ALTER TABLE adms_policies 
                ADD CONSTRAINT fk_policies_department 
                FOREIGN KEY (department_id) REFERENCES adms_departments(id) 
                ON DELETE RESTRICT ON UPDATE CASCADE');
        }

        /**
         * Leitura / Ciência de Políticas Internas
         */
        if (!$this->hasTable('adms_policies_reads')) {
            $table = $this->table('adms_policies_reads', [
                'id'          => false,
                'primary_key' => ['id'],
            ]);

            $table
                ->addColumn('id', 'integer', [
                    'identity' => true,
                    'signed'   => false,
                ])
                ->addColumn('policy_id', 'integer', [
                    'signed' => false,
                    'null'   => false,
                ])
                ->addColumn('user_id', 'integer', [
                    'signed' => false,
                    'null'   => false,
                ])
                ->addColumn('read_at', 'datetime', [
                    'null' => true,
                ])
                ->addColumn('acknowledged', 'boolean', [
                    'default' => false,
                ])
                ->addColumn('ack_at', 'datetime', [
                    'null' => true,
                ])
                ->addColumn('created_at', 'datetime', [
                    'default' => 'CURRENT_TIMESTAMP',
                ])
                ->addIndex(['policy_id', 'user_id'], [
                    'unique' => true,
                    'name'   => 'uq_policy_user',
                ])
                ->addIndex(['user_id'])
                ->create();

            $this->execute('ALTER TABLE adms_policies_reads 
                ADD CONSTRAINT fk_policies_reads_policy 
                FOREIGN KEY (policy_id) REFERENCES adms_policies(id) 
                ON DELETE CASCADE ON UPDATE CASCADE');

            $this->execute('ALTER TABLE adms_policies_reads 
                ADD CONSTRAINT fk_policies_reads_user 
                FOREIGN KEY (user_id) REFERENCES adms_users(id) 
                ON DELETE CASCADE ON UPDATE CASCADE');
        }

        /**
         * Relação políticas x departamentos notificados
         */
        if (!$this->hasTable('adms_policies_notify_departments')) {
            $notifyTable = $this->table('adms_policies_notify_departments');

            $notifyTable
                ->addColumn('policy_id', 'integer', [
                    'signed' => false,
                    'null'   => false,
                ])
                ->addColumn('department_id', 'integer', [
                    'signed' => false,
                    'null'   => false,
                ])
                ->addColumn('created_at', 'datetime', [
                    'default' => 'CURRENT_TIMESTAMP',
                ])
                ->addIndex(['policy_id'])
                ->addIndex(['department_id'])
                ->create();

            $this->execute('ALTER TABLE adms_policies_notify_departments 
                ADD CONSTRAINT fk_policies_notify_policy 
                FOREIGN KEY (policy_id) REFERENCES adms_policies(id) 
                ON DELETE CASCADE ON UPDATE CASCADE');

            $this->execute('ALTER TABLE adms_policies_notify_departments 
                ADD CONSTRAINT fk_policies_notify_department 
                FOREIGN KEY (department_id) REFERENCES adms_departments(id) 
                ON DELETE CASCADE ON UPDATE CASCADE');
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_policies_notify_departments')) {
            $this->table('adms_policies_notify_departments')->drop()->save();
        }

        if ($this->hasTable('adms_policies_reads')) {
            $this->table('adms_policies_reads')->drop()->save();
        }

        if ($this->hasTable('adms_policies')) {
            $this->table('adms_policies')->drop()->save();
        }

        if ($this->hasTable('adms_policies_categorias')) {
            $this->table('adms_policies_categorias')->drop()->save();
        }
    }
}

