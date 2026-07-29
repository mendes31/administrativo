<?php

declare(strict_types=1);

/**
 * Cisão de mega-grupos ACL (GP / SST / LGPD / Estoque / CRM / Comunicação Social).
 * Usado por migration Expand e seed - resolve grupos por nome (nunca por ID fixo).
 * Não altera adms_access_levels_pages.
 */
final class AdmsPageGroupSplit
{
    /** @return list<string> */
    public static function newGroupNames(): array
    {
        return [
            'Gestão de Pessoas - Talentos (ATS)',
            'Gestão de Pessoas - Portal / Solicitações',
            'Gestão de Pessoas - Desempenho e Carreira',
            'Gestão de Pessoas - Organização / Políticas',
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
            'Estoque - Itens e movimentações',
            'Estoque - Custeio',
            'CRM - Operação',
            'CRM - Integrações e configurações',
            'Comunicação Social - Timeline',
            'Comunicação Social - Gamificação',
            'Comunicação Social - Eventos',
        ];
    }

    /**
     * @param callable(string): (array|false|null) $fetchRow
     * @param callable(string): void $execute
     * @return array<string, int> name => id
     */
    public static function ensureGroups(callable $fetchRow, callable $execute, string $now): array
    {
        $ids = [];
        foreach (self::newGroupNames() as $name) {
            $quoted = self::quote($name);
            $row = $fetchRow("SELECT id FROM adms_groups_pages WHERE name = {$quoted} LIMIT 1");
            if ($row && !empty($row['id'])) {
                $ids[$name] = (int) $row['id'];
                continue;
            }

            // Nomes legados: em-dash, sem espaços, ou prefixo curto "GP -"
            $em = "\u{2014}";
            $legacyCandidates = [
                str_replace(' - ', ' ' . $em . ' ', $name),
                str_replace(' - ', $em, $name),
            ];
            if (str_starts_with($name, 'Gestão de Pessoas - ')) {
                $suffix = substr($name, strlen('Gestão de Pessoas - '));
                $legacyCandidates[] = 'GP - ' . $suffix;
                $legacyCandidates[] = 'GP ' . $em . ' ' . $suffix;
                $legacyCandidates[] = 'GP' . $em . $suffix;
            }
            $found = false;
            foreach (array_unique($legacyCandidates) as $legacy) {
                if ($legacy === $name) {
                    continue;
                }
                $legacyRow = $fetchRow(
                    'SELECT id FROM adms_groups_pages WHERE name = ' . self::quote($legacy) . ' LIMIT 1'
                );
                if ($legacyRow && !empty($legacyRow['id'])) {
                    $execute(
                        'UPDATE adms_groups_pages SET name = ' . $quoted
                        . ', updated_at = ' . self::quote($now)
                        . ' WHERE id = ' . (int) $legacyRow['id']
                    );
                    $ids[$name] = (int) $legacyRow['id'];
                    $found = true;
                    break;
                }
            }
            if ($found) {
                continue;
            }

            $obs = self::quote('Grupo ACL cisão Expand ' . date('Y-m-d') . ' - ' . $name);
            $execute(
                "INSERT INTO adms_groups_pages (name, obs, created_at, updated_at)
                 VALUES ({$quoted}, {$obs}, " . self::quote($now) . ', ' . self::quote($now) . ')'
            );
            $row = $fetchRow("SELECT id FROM adms_groups_pages WHERE name = {$quoted} LIMIT 1");
            $ids[$name] = (int) ($row['id'] ?? 0);
        }

        return $ids;
    }

    /**
     * @param callable(string): (array|false|null) $fetchRow
     * @param callable(string): mixed $fetchAll
     * @param callable(string): void $execute
     */
    public static function reassignPages(callable $fetchRow, callable $fetchAll, callable $execute, string $now): void
    {
        $ids = self::ensureGroups($fetchRow, $execute, $now);

        $parents = [
            'gp' => self::groupIdByName($fetchRow, 'Gestão de Pessoas'),
            'sst' => self::groupIdByName($fetchRow, 'Segurança e Medicina'),
            'lgpd' => self::groupIdByName($fetchRow, 'LGPD'),
            'estoque' => self::groupIdByName($fetchRow, 'Estoque'),
            'crm' => self::groupIdByName($fetchRow, 'CRM'),
            'comsoc' => self::groupIdByName($fetchRow, 'Comunicação Social'),
        ];

        foreach (['gp', 'sst', 'lgpd', 'estoque', 'crm', 'comsoc'] as $key) {
            if ($parents[$key] <= 0) {
                continue;
            }
            $parentId = $parents[$key];
            $rows = $fetchAll(
                "SELECT id, controller, directory FROM adms_pages WHERE adms_groups_page_id = {$parentId}"
            );
            if (!is_array($rows)) {
                continue;
            }
            foreach ($rows as $row) {
                $pageId = (int) ($row['id'] ?? 0);
                $controller = (string) ($row['controller'] ?? '');
                $directory = (string) ($row['directory'] ?? '');
                if ($pageId <= 0 || $controller === '') {
                    continue;
                }
                $targetName = match ($key) {
                    'gp' => self::classifyGp($controller, $directory),
                    'sst' => self::classifySst($controller),
                    'lgpd' => self::classifyLgpd($controller, $directory),
                    'estoque' => self::classifyEstoque($controller),
                    'crm' => self::classifyCrm($controller, $directory),
                    'comsoc' => self::classifyComunicacaoSocial($controller, $directory),
                    default => null,
                };
                if ($targetName === null || !isset($ids[$targetName]) || $ids[$targetName] <= 0) {
                    continue;
                }
                $newId = $ids[$targetName];
                $execute(
                    "UPDATE adms_pages
                     SET adms_groups_page_id = {$newId}, updated_at = " . self::quote($now) . "
                     WHERE id = {$pageId} AND adms_groups_page_id = {$parentId}
                     LIMIT 1"
                );
            }
        }
    }

    /** @param callable(string): (array|false|null) $fetchRow */
    private static function groupIdByName(callable $fetchRow, string $name): int
    {
        $row = $fetchRow('SELECT id FROM adms_groups_pages WHERE name = ' . self::quote($name) . ' LIMIT 1');

        return (int) ($row['id'] ?? 0);
    }

    public static function classifyGp(string $controller, string $directory): string
    {
        $lc = strtolower($controller);
        if ($directory === 'rh' || str_starts_with($controller, 'Rh') || str_contains($lc, 'personnel')) {
            return 'Gestão de Pessoas - Talentos (ATS)';
        }
        if (
            $directory === 'portal'
            || str_contains($lc, 'employee')
            || str_contains($lc, 'approval')
            || str_contains($lc, 'requesttype')
            || str_contains($lc, 'delegation')
        ) {
            return 'Gestão de Pessoas - Portal / Solicitações';
        }
        if (
            in_array($directory, ['performance', 'pdi'], true)
            || preg_match('/Performance|Competenc|Pdi|Pulse|Career|Succession|Nine|Calibration|TalentNomin/i', $controller)
        ) {
            return 'Gestão de Pessoas - Desempenho e Carreira';
        }
        // Políticas, turnos, headcount, analytics RH, histórico emprego
        return 'Gestão de Pessoas - Organização / Políticas';
    }

    public static function classifySst(string $controller): string
    {
        $lc = strtolower($controller);
        if (
            str_contains($lc, 'equip')
            || str_contains($lc, 'vistoria')
            || str_contains($lc, 'recarga')
            || str_contains($lc, 'naoconform')
            || str_contains($lc, 'nao_conform')
        ) {
            return 'SST - Equipamentos / Vistoria';
        }
        if (str_contains($lc, 'epi')) {
            return 'SST - EPI';
        }
        if (
            str_contains($lc, 'aso')
            || str_contains($lc, 'exame')
            || str_contains($lc, 'medico')
            || str_contains($lc, 'cid')
            || str_contains($lc, 'encaminh')
        ) {
            return 'SST - Medicina / ASO / Exames';
        }
        if (
            str_contains($lc, 'trein')
            || str_contains($lc, 'lnt')
            || str_contains($lc, 'ppp')
            || str_contains($lc, 'ghe')
            || str_contains($lc, 'matriz')
        ) {
            return 'SST - Treinamentos / GHE / PPP';
        }
        if (str_contains($lc, 'acid') || str_contains($lc, 'afast') || str_contains($lc, 'cat')) {
            return 'SST - Acidentes / Afastamentos';
        }
        if (
            str_contains($lc, 'dashboard')
            || str_contains($lc, 'employee')
            || str_contains($lc, 'minhas')
            || str_contains($lc, 'report')
            || str_contains($lc, 'export')
        ) {
            return 'SST - Dashboard / Relatórios';
        }

        return 'SST - Cadastros e vínculos';
    }

    public static function classifyLgpd(string $controller, string $directory): string
    {
        $lc = strtolower($controller);
        if (str_contains($lc, 'aipd')) {
            return 'LGPD - AIPD';
        }
        if (str_contains($lc, 'tia')) {
            return 'LGPD - TIA';
        }
        if (str_contains($lc, 'ripd')) {
            return 'LGPD - RIPD';
        }
        if (str_contains($lc, 'consent')) {
            return 'LGPD - Consentimentos';
        }
        if (str_contains($lc, 'titular') || str_contains($lc, 'categoria')) {
            return 'LGPD - Titulares';
        }
        if (
            str_contains($lc, 'inventory')
            || str_contains($lc, 'ropa')
            || str_contains($lc, 'datamapping')
            || str_contains($lc, 'workflow')
        ) {
            return 'LGPD - Inventário / ROPA / Mapping';
        }
        if (
            str_contains($lc, 'finalidad')
            || str_contains($lc, 'baseslegais')
            || str_contains($lc, 'baselegal')
            || str_contains($lc, 'tiposdado')
            || str_contains($lc, 'classific')
        ) {
            return 'LGPD - Taxonomia';
        }
        if (
            $directory === 'legal'
            || str_contains($lc, 'termo')
            || str_contains($lc, 'politica')
            || str_contains($lc, 'dashboard')
        ) {
            return 'LGPD - Dashboard / Termos / Legal';
        }

        return 'LGPD - Dashboard / Termos / Legal';
    }

    public static function classifyEstoque(string $controller): string
    {
        $lc = strtolower($controller);
        if (
            str_contains($lc, 'invcost')
            || str_contains($lc, 'cost')
            || str_contains($lc, 'complexity')
            || str_contains($lc, 'energyclass')
            || str_contains($lc, 'laborrole')
            || str_contains($lc, 'productionresource')
            || str_contains($lc, 'inventoryoperation')
        ) {
            return 'Estoque - Custeio';
        }

        return 'Estoque - Itens e movimentações';
    }

    public static function classifyCrm(string $controller, string $directory): string
    {
        if (
            $directory === 'settings'
            || preg_match('/WhatsAppConfig|SapApi|McpChat|CalendarConfig/i', $controller) === 1
        ) {
            return 'CRM - Integrações e configurações';
        }

        return 'CRM - Operação';
    }

    public static function classifyComunicacaoSocial(string $controller, string $directory): string
    {
        $lc = strtolower($controller);
        if (
            $directory === 'gamification'
            || str_contains($lc, 'gamification')
        ) {
            return 'Comunicação Social - Gamificação';
        }
        if (
            $directory === 'companyEvents'
            || str_contains($lc, 'companyevent')
            || str_contains($lc, 'eventrsvp')
        ) {
            return 'Comunicação Social - Eventos';
        }

        return 'Comunicação Social - Timeline';
    }

    private static function quote(string $value): string
    {
        return "'" . str_replace("'", "''", $value) . "'";
    }
}
