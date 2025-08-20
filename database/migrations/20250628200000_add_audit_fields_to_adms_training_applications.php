<?php
use Phinx\Migration\AbstractMigration;

class AddAuditFieldsToAdmsTrainingApplications extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('adms_training_applications');
        $table
            ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false, 'after' => 'aplicado_por', 'comment' => 'ID do usuário que realizou o último update'])
            ->addColumn('instructor_user_id', 'integer', ['null' => true, 'signed' => false, 'after' => 'instrutor_email', 'comment' => 'ID do instrutor cadastrado'])
            ->addColumn('real_instructor_user_id', 'integer', ['null' => true, 'signed' => false, 'after' => 'instructor_user_id', 'comment' => 'ID do instrutor que realmente aplicou'])
            ->addColumn('real_instructor_nome', 'string', ['limit' => 255, 'null' => true, 'after' => 'real_instructor_user_id'])
            ->addColumn('real_instructor_email', 'string', ['limit' => 255, 'null' => true, 'after' => 'real_instructor_nome'])
            ->update();
    }
} 