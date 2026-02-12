<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Cria a tabela rh_vagas para gestão de vagas de emprego
 * no módulo de Recrutamento e Seleção.
 */
final class CreateRhVagas extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('rh_vagas')) {
            return;
        }

        $this->table('rh_vagas')
            // Informações básicas da vaga
            ->addColumn('titulo', 'string', [
                'limit' => 255,
                'null'  => false,
                'comment' => 'Título da vaga (ex: Analista de Qualidade Jr)',
            ])
            ->addColumn('descricao', 'text', [
                'null'  => true,
                'comment' => 'Descrição completa da vaga, responsabilidades, etc.',
            ])
            ->addColumn('requisitos', 'text', [
                'null'  => true,
                'comment' => 'Requisitos obrigatórios e desejáveis',
            ])
            ->addColumn('beneficios', 'text', [
                'null'  => true,
                'comment' => 'Benefícios oferecidos',
            ])

            // Relacionamentos com estrutura organizacional
            ->addColumn('area_id', 'integer', [
                'null'   => true,
                'signed' => false,
                'comment' => 'ID do departamento/área (adms_departments.id)',
            ])
            ->addColumn('cargo_id', 'integer', [
                'null'   => true,
                'signed' => false,
                'comment' => 'ID do cargo (adms_positions.id)',
            ])

            // Tipo de contrato e remuneração
            ->addColumn('tipo_contrato', 'string', [
                'limit'   => 50,
                'null'    => false,
                'default' => 'CLT',
                'comment' => 'CLT, PJ, Estágio, Temporário, etc.',
            ])
            ->addColumn('salario_min', 'decimal', [
                'precision' => 10,
                'scale'     => 2,
                'null'      => true,
                'comment'   => 'Salário mínimo (opcional)',
            ])
            ->addColumn('salario_max', 'decimal', [
                'precision' => 10,
                'scale'     => 2,
                'null'      => true,
                'comment'   => 'Salário máximo (opcional)',
            ])
            ->addColumn('mostrar_salario', 'boolean', [
                'null'    => false,
                'default' => 0,
                'comment' => 'Se deve mostrar salário na vaga pública',
            ])

            // Status e datas
            ->addColumn('status', 'string', [
                'limit'   => 20,
                'null'    => false,
                'default' => 'aberta',
                'comment' => 'aberta, pausada, fechada, cancelada',
            ])
            ->addColumn('data_abertura', 'datetime', [
                'null'    => false,
                'default' => 'CURRENT_TIMESTAMP',
            ])
            ->addColumn('data_fechamento', 'datetime', [
                'null'    => true,
                'default' => null,
            ])
            ->addColumn('data_limite_inscricao', 'datetime', [
                'null'    => true,
                'default' => null,
                'comment' => 'Data limite para receber candidaturas',
            ])

            // Informações adicionais
            ->addColumn('quantidade_vagas', 'integer', [
                'null'    => false,
                'default' => 1,
                'comment' => 'Número de vagas disponíveis',
            ])
            ->addColumn('local_trabalho', 'string', [
                'limit' => 255,
                'null'  => true,
                'comment' => 'Local de trabalho (presencial, remoto, híbrido, endereço)',
            ])
            ->addColumn('jornada_trabalho', 'string', [
                'limit' => 50,
                'null'  => true,
                'comment' => 'Jornada de trabalho (40h, 44h, parcial, etc.)',
            ])
            ->addColumn('observacoes', 'text', [
                'null' => true,
            ])

            // Responsável pela vaga
            ->addColumn('responsavel_id', 'integer', [
                'null'   => true,
                'signed' => false,
                'comment' => 'ID do usuário responsável pela vaga (adms_users.id)',
            ])

            // Auditoria
            ->addColumn('created_at', 'datetime', [
                'null'    => false,
                'default' => 'CURRENT_TIMESTAMP',
            ])
            ->addColumn('updated_at', 'datetime', [
                'null'    => true,
                'default' => null,
            ])

            // Índices
            ->addIndex(['status'])
            ->addIndex(['area_id'])
            ->addIndex(['cargo_id'])
            ->addIndex(['data_abertura'])
            ->addIndex(['data_fechamento'])

            // Foreign Keys
            ->addForeignKey('area_id', 'adms_departments', 'id', [
                'delete' => 'SET NULL',
                'update' => 'CASCADE',
            ])
            ->addForeignKey('cargo_id', 'adms_positions', 'id', [
                'delete' => 'SET NULL',
                'update' => 'CASCADE',
            ])
            ->addForeignKey('responsavel_id', 'adms_users', 'id', [
                'delete' => 'SET NULL',
                'update' => 'CASCADE',
            ])
            ->create();
    }

    public function down(): void
    {
        if ($this->hasTable('rh_vagas')) {
            $this->table('rh_vagas')->drop()->save();
        }
    }
}

