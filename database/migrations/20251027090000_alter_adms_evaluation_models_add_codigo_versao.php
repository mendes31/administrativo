<?php

use Phinx\Migration\AbstractMigration;

/**
 * Migration para adicionar campos codigo_documento e versao_documento
 * na tabela adms_evaluation_models
 */
class AlterAdmsEvaluationModelsAddCodigoVersao extends AbstractMigration
{
    /**
     * Adiciona campos de código e versão do documento
     */
    public function change()
    {
        $table = $this->table('adms_evaluation_models');
        
        $table->addColumn('codigo_documento', 'string', [
            'limit' => 50,
            'null' => true,
            'comment' => 'Código de identificação do documento (ex: AVAL-NOR-TI-0004)',
            'after' => 'titulo'
        ])
        ->addColumn('versao_documento', 'string', [
            'limit' => 20,
            'null' => true,
            'comment' => 'Versão do documento (ex: 1.0, v01)',
            'after' => 'codigo_documento'
        ])
        ->update();
    }
}

