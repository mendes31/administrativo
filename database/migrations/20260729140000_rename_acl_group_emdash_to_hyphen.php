<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Padroniza nomes dos subgrupos ACL GP/SST/LGPD: em-dash (—) → hífen ASCII (-)
 * para a busca na matriz (ex.: "gp-") encontrar os grupos.
 */
final class RenameAclGroupEmdashToHyphen extends AbstractMigration
{
    /** @return array<string, string> old => new */
    private function nameMap(): array
    {
        $names = [
            'GP - Talentos (ATS)',
            'GP - Portal / Solicitações',
            'GP - Desempenho e Carreira',
            'GP - Organização / Políticas',
            'SST - Medicina / ASO / Exames',
            'SST - Cadastros e vínculos',
            'SST - Treinamentos / GHE / PPP',
            'SST - EPI',
            'SST - Equipamentos / Vistoria',
            'SST - Acidentes / Afastamentos',
            'SST - Dashboard / Relatórios',
            'LGPD - Taxonomia',
            'LGPD - Inventário / ROPA / Mapping',
            'LGPD - AIPD',
            'LGPD - TIA',
            'LGPD - Consentimentos',
            'LGPD - Dashboard / Termos / Legal',
            'LGPD - RIPD',
            'LGPD - Titulares',
        ];

        $map = [];
        $em = "\u{2014}";
        foreach ($names as $new) {
            $withSpaces = str_replace(' - ', ' ' . $em . ' ', $new);
            $noSpaces = str_replace(' - ', $em, $new);
            $map[$withSpaces] = $new;
            $map[$noSpaces] = $new;
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
            if ($old === $new) {
                continue;
            }
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
        $em = "\u{2014}";
        foreach ($this->nameMap() as $old => $new) {
            // Reverte só a variante com espaços ao redor do em-dash.
            if (!str_contains($old, ' ' . $em . ' ')) {
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
