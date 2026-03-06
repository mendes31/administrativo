<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateProjComments extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('proj_comments')) {
            return;
        }

        $this->table('proj_comments')
            ->addColumn('project_id', 'integer', [
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('proj_project_stage_id', 'integer', [
                'null' => true,
                'signed' => false,
                'comment' => 'Etapa do projeto (opcional); NULL = comentário no projeto',
            ])
            ->addColumn('user_id', 'integer', [
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('body', 'text', [
                'null' => false,
            ])
            ->addColumn('created_at', 'datetime', [
                'default' => 'CURRENT_TIMESTAMP',
            ])
            ->addIndex(['project_id'])
            ->addIndex(['proj_project_stage_id'])
            ->addIndex(['user_id'])
            ->addForeignKey('project_id', 'proj_projects', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
            ])
            ->addForeignKey('proj_project_stage_id', 'proj_project_stages', 'id', [
                'delete' => 'SET NULL',
                'update' => 'CASCADE',
            ])
            ->addForeignKey('user_id', 'adms_users', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
            ])
            ->create();

        if ($this->hasTable('proj_comment_mentions')) {
            return;
        }

        $this->table('proj_comment_mentions')
            ->addColumn('comment_id', 'integer', [
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('user_id', 'integer', [
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('created_at', 'datetime', [
                'default' => 'CURRENT_TIMESTAMP',
            ])
            ->addIndex(['comment_id'])
            ->addIndex(['user_id'])
            ->addForeignKey('comment_id', 'proj_comments', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
            ])
            ->addForeignKey('user_id', 'adms_users', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
            ])
            ->create();
    }

    public function down(): void
    {
        if ($this->hasTable('proj_comment_mentions')) {
            $this->table('proj_comment_mentions')->drop()->save();
        }
        if ($this->hasTable('proj_comments')) {
            $this->table('proj_comments')->drop()->save();
        }
    }
}
