<?php
use Phinx\Migration\AbstractMigration;

class CreateAdmsTrainingApplications extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('adms_training_applications');
        $table
            ->addColumn('adms_user_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('adms_training_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('data_realizacao', 'date', ['null' => true])
            ->addColumn('data_agendada', 'date', ['null' => true])
            ->addColumn('instrutor_nome', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('instrutor_email', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('aplicado_por', 'integer', ['null' => true, 'signed' => false, 'comment' => 'ID do usuário que aplicou'])
            ->addColumn('nota', 'decimal', ['precision' => 5, 'scale' => 2, 'null' => true])
            ->addColumn('observacoes', 'text', ['null' => true])
            ->addColumn('status', 'string', ['limit' => 30, 'default' => 'agendado'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addForeignKey('adms_user_id', 'adms_users', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
            ->addForeignKey('adms_training_id', 'adms_trainings', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
            ->create();
    }
} 