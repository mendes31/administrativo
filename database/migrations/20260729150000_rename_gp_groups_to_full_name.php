<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Renomeia subgrupos ACL "GP - …" para "Gestão de Pessoas - …"
 * (evita ambiguidade com Gestão de Projetos).
 */
final class RenameGpGroupsToFullName extends AbstractMigration
{
    /** @return array<string, string> old => new */
    private function nameMap(): array
    {
        $suffixes = [
            'Talentos (ATS)',
            'Portal / Solicitações',
            'Desempenho e Carreira',
            'Organização / Políticas',
        ];

        $map = [];
        $em = "\u{2014}";
        foreach ($suffixes as $suffix) {
            $new = 'Gestão de Pessoas - ' . $suffix;
            $map['GP - ' . $suffix] = $new;
            $map['GP ' . $em . ' ' . $suffix] = $new;
            $map['GP' . $em . $suffix] = $new;
        }

        return $map;
    }

    public function up(): void
    {
        if (!$this->hasTable('adms_groups_pages')) {
            return;
        }

        $now = $this->quote(date('Y-m-d H:i:s'));
        foreach ($this->nameMap() as $old => $new) {
            $this->execute(
                'UPDATE adms_groups_pages SET name = ' . $this->quote($new)
                . ', updated_at = ' . $now
                . ' WHERE name = ' . $this->quote($old)
            );
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_groups_pages')) {
            return;
        }

        $now = $this->quote(date('Y-m-d H:i:s'));
        foreach ($this->nameMap() as $old => $new) {
            if (!str_starts_with($old, 'GP - ')) {
                continue;
            }
            $this->execute(
                'UPDATE adms_groups_pages SET name = ' . $this->quote($old)
                . ', updated_at = ' . $now
                . ' WHERE name = ' . $this->quote($new)
            );
        }
    }

    private function quote(string $value): string
    {
        return "'" . str_replace("'", "''", $value) . "'";
    }
}
