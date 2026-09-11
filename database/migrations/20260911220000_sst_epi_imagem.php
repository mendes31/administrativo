<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/** Foto ilustrativa no cadastro do EPI (listagem, ficha e catálogo visual). */
final class SstEpiImagem extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_sst_epis')) {
            return;
        }

        $epis = $this->table('adms_sst_epis');
        if ($epis->hasColumn('imagem')) {
            return;
        }

        $after = $epis->hasColumn('descricao') ? 'descricao' : null;
        $opts = [
            'limit' => 255,
            'null' => true,
            'comment' => 'Caminho relativo em public/adms/uploads (ex.: sst/epis/arquivo.jpg)',
        ];
        if ($after !== null) {
            $opts['after'] = $after;
        }
        $epis->addColumn('imagem', 'string', $opts)->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_sst_epis')) {
            return;
        }
        $epis = $this->table('adms_sst_epis');
        if ($epis->hasColumn('imagem')) {
            $epis->removeColumn('imagem')->update();
        }
    }
}
