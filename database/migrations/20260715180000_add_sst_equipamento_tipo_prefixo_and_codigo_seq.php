<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Prefixo de 3 caracteres no tipo + sequência para código automático do equipamento (EXT00001).
 */
final class AddSstEquipamentoTipoPrefixoAndCodigoSeq extends AbstractMigration
{
    /** @var array<string, string> codigo_tipo => prefixo */
    private const PREFIXOS = [
        'EXTINTOR' => 'EXT',
        'HIDRANTE' => 'HID',
        'MANGUEIRA' => 'MAN',
        'PORTA_CORTA_FOGO' => 'PCF',
        'ILUMINACAO_EMERG' => 'ILE',
        'DETECTOR_FUMACA' => 'DFU',
        'CHUVEIRO_LAVA_OLHOS' => 'CHV',
        'ALARME_INCENDIO' => 'ALM',
    ];

    public function up(): void
    {
        if ($this->hasTable('adms_sst_equipamento_tipos')
            && !$this->table('adms_sst_equipamento_tipos')->hasColumn('prefixo')
        ) {
            $this->table('adms_sst_equipamento_tipos')
                ->addColumn('prefixo', 'string', [
                    'limit' => 3,
                    'null' => true,
                    'after' => 'codigo',
                    'comment' => '3 caracteres usados no código do equipamento (ex.: EXT)',
                ])
                ->update();
        }

        if (!$this->hasTable('adms_sst_equipamento_codigo_seq')) {
            $this->table('adms_sst_equipamento_codigo_seq', ['id' => false, 'primary_key' => ['adms_sst_equipamento_tipo_id']])
                ->addColumn('adms_sst_equipamento_tipo_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('ultimo_numero', 'integer', ['null' => false, 'signed' => false, 'default' => 0])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addForeignKey('adms_sst_equipamento_tipo_id', 'adms_sst_equipamento_tipos', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'CASCADE',
                ])
                ->create();
        }

        if (!$this->hasTable('adms_sst_equipamento_tipos')) {
            return;
        }

        foreach (self::PREFIXOS as $codigo => $prefixo) {
            $this->execute(sprintf(
                "UPDATE adms_sst_equipamento_tipos SET prefixo = '%s' WHERE codigo = '%s' AND (prefixo IS NULL OR prefixo = '')",
                addslashes($prefixo),
                addslashes($codigo)
            ));
        }

        // Tipos sem mapeamento: tenta 3 primeiras letras do codigo
        $rows = $this->fetchAll(
            "SELECT id, codigo FROM adms_sst_equipamento_tipos WHERE prefixo IS NULL OR prefixo = ''"
        );
        foreach ($rows as $row) {
            $raw = preg_replace('/[^A-Za-z0-9]/', '', (string) ($row['codigo'] ?? '')) ?? '';
            $prefixo = strtoupper(substr($raw, 0, 3));
            if (strlen($prefixo) !== 3) {
                $prefixo = str_pad($prefixo, 3, 'X');
            }
            $this->execute(sprintf(
                "UPDATE adms_sst_equipamento_tipos SET prefixo = '%s' WHERE id = %d",
                addslashes($prefixo),
                (int) $row['id']
            ));
        }

        // UNIQUE em prefixo (após preencher)
        $table = $this->table('adms_sst_equipamento_tipos');
        if (!$table->hasIndex(['prefixo'])) {
            $table->addIndex(['prefixo'], ['unique' => true, 'name' => 'uq_sst_equipamento_tipos_prefixo'])->update();
        }

        $this->table('adms_sst_equipamento_tipos')
            ->changeColumn('prefixo', 'string', [
                'limit' => 3,
                'null' => false,
                'comment' => '3 caracteres usados no código do equipamento (ex.: EXT)',
            ])
            ->update();

        // Inicializa sequência a partir de equipamentos já cadastrados no padrão PREFIXO#####
        if ($this->hasTable('adms_sst_equipamentos')) {
            $tipos = $this->fetchAll('SELECT id, prefixo FROM adms_sst_equipamento_tipos WHERE prefixo IS NOT NULL');
            foreach ($tipos as $tipo) {
                $tipoId = (int) $tipo['id'];
                $prefixo = strtoupper((string) $tipo['prefixo']);
                if (strlen($prefixo) !== 3) {
                    continue;
                }
                $like = addslashes($prefixo) . '%';
                $maxRow = $this->fetchRow(
                    "SELECT codigo FROM adms_sst_equipamentos
                     WHERE adms_sst_equipamento_tipo_id = {$tipoId}
                       AND codigo LIKE '{$like}'
                     ORDER BY codigo DESC LIMIT 1"
                );
                $ultimo = 0;
                if ($maxRow && preg_match('/^' . preg_quote($prefixo, '/') . '(\d{5})$/', (string) $maxRow['codigo'], $m)) {
                    $ultimo = (int) $m[1];
                }
                $exists = $this->fetchRow(
                    "SELECT adms_sst_equipamento_tipo_id FROM adms_sst_equipamento_codigo_seq
                     WHERE adms_sst_equipamento_tipo_id = {$tipoId} LIMIT 1"
                );
                if ($exists) {
                    $this->execute(
                        "UPDATE adms_sst_equipamento_codigo_seq SET ultimo_numero = GREATEST(ultimo_numero, {$ultimo})
                         WHERE adms_sst_equipamento_tipo_id = {$tipoId}"
                    );
                } else {
                    $this->table('adms_sst_equipamento_codigo_seq')->insert([
                        'adms_sst_equipamento_tipo_id' => $tipoId,
                        'ultimo_numero' => $ultimo,
                    ])->save();
                }
            }
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_sst_equipamento_codigo_seq')) {
            $this->table('adms_sst_equipamento_codigo_seq')->drop()->save();
        }
        if ($this->hasTable('adms_sst_equipamento_tipos')
            && $this->table('adms_sst_equipamento_tipos')->hasColumn('prefixo')
        ) {
            $table = $this->table('adms_sst_equipamento_tipos');
            if ($table->hasIndexByName('uq_sst_equipamento_tipos_prefixo')) {
                $table->removeIndexByName('uq_sst_equipamento_tipos_prefixo')->update();
            }
            $table->removeColumn('prefixo')->update();
        }
    }
}
