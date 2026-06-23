<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class SeedSstTreinamentosCatalogoBasico extends AbstractMigration
{
    /** @var list<array<string, mixed>> */
    private const TREINAMENTOS = [
        [
            'codigo' => 'TR0001',
            'nome' => 'Uso, Conservação e Responsabilidade sobre EPIs',
            'descricao' => 'Orientação sobre utilização, conservação, guarda e responsabilidades relativas aos EPIs.',
            'nr_referencia' => 'NR-6',
            'aplicacao_momentos' => '["admissional","reciclagem","periodico"]',
            'tipo' => 'Ambos',
            'modalidade' => 'Presencial',
            'carga_horaria_minutos' => 60,
            'validade_meses' => 12,
            'prazo_primeiro_dias' => 7,
        ],
        [
            'codigo' => 'TR0002',
            'nome' => 'Integração de Segurança do Trabalho',
            'descricao' => 'Treinamento de integração abordando normas internas de segurança, uso de EPIs, prevenção de acidentes e rotas de emergência.',
            'nr_referencia' => 'Não se aplica',
            'aplicacao_momentos' => '["admissional"]',
            'tipo' => 'Inicial',
            'modalidade' => 'Presencial',
            'carga_horaria_minutos' => 120,
            'validade_meses' => 0,
            'prazo_primeiro_dias' => 1,
        ],
        [
            'codigo' => 'TR0003',
            'nome' => 'Brigada de Incêndio',
            'descricao' => 'Formação e reciclagem de brigadistas conforme NBR 14276.',
            'nr_referencia' => 'NBR 14276',
            'aplicacao_momentos' => '["admissional","reciclagem","periodico"]',
            'tipo' => 'Ambos',
            'modalidade' => 'Presencial',
            'carga_horaria_minutos' => 480,
            'validade_meses' => 12,
            'prazo_primeiro_dias' => 30,
        ],
        [
            'codigo' => 'TR0004',
            'nome' => 'NR-35 Trabalho em Altura',
            'descricao' => 'Treinamento teórico e prático para trabalho em altura conforme NR-35.',
            'nr_referencia' => 'NR-35',
            'aplicacao_momentos' => '["admissional","reciclagem","periodico"]',
            'tipo' => 'Ambos',
            'modalidade' => 'Presencial',
            'carga_horaria_minutos' => 480,
            'validade_meses' => 24,
            'prazo_primeiro_dias' => 0,
        ],
    ];

    public function up(): void
    {
        if (!$this->hasTable('adms_sst_treinamentos')) {
            return;
        }

        $hasAplicacao = $this->table('adms_sst_treinamentos')->hasColumn('aplicacao_momentos');
        $now = date('Y-m-d H:i:s');

        foreach (self::TREINAMENTOS as $t) {
            $codigo = addslashes($t['codigo']);
            $exists = $this->fetchRow("SELECT id FROM adms_sst_treinamentos WHERE codigo = '{$codigo}' LIMIT 1");
            if ($exists) {
                continue;
            }

            $cols = ['codigo', 'nome', 'descricao', 'nr_referencia', 'tipo', 'modalidade',
                'carga_horaria_minutos', 'validade_meses', 'prazo_primeiro_dias', 'status', 'created_at', 'updated_at'];
            $vals = [
                "'" . addslashes($t['codigo']) . "'",
                "'" . addslashes($t['nome']) . "'",
                "'" . addslashes($t['descricao']) . "'",
                "'" . addslashes($t['nr_referencia']) . "'",
                "'" . addslashes($t['tipo']) . "'",
                "'" . addslashes($t['modalidade']) . "'",
                (int) $t['carga_horaria_minutos'],
                (int) $t['validade_meses'],
                (int) $t['prazo_primeiro_dias'],
                "'Ativo'",
                "'{$now}'",
                "'{$now}'",
            ];

            if ($hasAplicacao) {
                $cols[] = 'aplicacao_momentos';
                $vals[] = "'" . addslashes($t['aplicacao_momentos']) . "'";
            }

            $this->execute(
                'INSERT INTO adms_sst_treinamentos (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $vals) . ')'
            );
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_sst_treinamentos')) {
            return;
        }
        $codigos = array_map(static fn(array $t): string => "'" . addslashes($t['codigo']) . "'", self::TREINAMENTOS);
        $this->execute('DELETE FROM adms_sst_treinamentos WHERE codigo IN (' . implode(',', $codigos) . ')');
    }
}
