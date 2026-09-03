<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use PDO;

/**
 * Checklist operacional do piloto SST (catálogos + regras + rotina).
 *
 * @phpstan-type GoLiveItem array{
 *   id: string,
 *   label: string,
 *   done: bool,
 *   count: int,
 *   hint: string,
 *   url: string,
 *   permission: string,
 *   required: bool
 * }
 */
final class SstGoLiveReadinessService extends DbConnection
{
    /**
     * @return array{
     *   required_done: int,
     *   required_total: int,
     *   complete: bool,
     *   items: list<GoLiveItem>
     * }
     */
    public function getChecklist(): array
    {
        $cids = $this->countTable('adms_sst_cids');
        $medicos = $this->countTable('adms_sst_medicos', "status = 'Ativo'");
        $exames = $this->countTable('adms_sst_exames', "status = 'Ativo'");
        $epis = $this->countTable('adms_sst_epis', "status = 'Ativo'");
        $riscos = $this->countTable('adms_sst_riscos', "status = 'Ativo'");
        $treinamentos = $this->countTable('adms_sst_treinamentos', "status = 'Ativo'");
        $riscoCargo = $this->countTable('adms_sst_riscos_cargo');
        $regrasRisco = $this->countTable('adms_sst_risco_epi')
            + $this->countTable('adms_sst_risco_exame')
            + $this->countTable('adms_sst_risco_treinamento');
        $colaboradoresCargo = $this->countTable(
            'adms_users',
            "user_position_id IS NOT NULL AND (status = 'Ativo' OR status = 1 OR status = '1')"
        );
        $equipamentos = $this->countTable('adms_sst_equipamentos', "status = 'Ativo'");
        $tiposEq = $this->countTable('adms_sst_equipamento_tipos', "status = 'Ativo'");
        $alertasOn = NotificationSettingsService::isAnyEnabled(
            'sst_pendencias_email',
            'sst_pendencias_inapp'
        );
        $lastCron = SstMaintenanceService::lastRunMeta();

        $items = [
            $this->item('cids', 'Catálogo de CIDs', $cids > 0, $cids, 'SstListCids', 'sst-list-cids', true,
                $cids > 0 ? '' : 'Importe o CID-10 (seed DATASUS) ou cadastre os códigos usados no piloto.'),
            $this->item('medicos', 'Médicos ativos', $medicos > 0, $medicos, 'SstListMedicos', 'sst-list-medicos', true,
                'Cadastre o médico do trabalho que assina o ASO.'),
            $this->item('exames', 'Exames ocupacionais', $exames > 0, $exames, 'SstListExames', 'sst-list-exames', true,
                'Inclua pelo menos os exames do ASO admissional/periódico do piloto.'),
            $this->item('epis', 'Catálogo de EPIs', $epis > 0, $epis, 'SstListEpis', 'sst-list-epis', true,
                'Informe CA e periodicidade de troca.'),
            $this->item('riscos', 'Riscos ocupacionais', $riscos > 0, $riscos, 'SstListRiscos', 'sst-list-riscos', true,
                'Cadastre os riscos do setor piloto.'),
            $this->item('treinamentos', 'Treinamentos SST', $treinamentos > 0, $treinamentos, 'SstListTreinamentos', 'sst-list-treinamentos', true,
                'Há catálogo seed (NR-6, integração). Confira se está ativo.'),
            $this->item('risco_cargo', 'Riscos vinculados a cargo/setor', $riscoCargo > 0, $riscoCargo, 'SstListRiscos', 'sst-list-riscos', true,
                'No risco, aba cargos: sem isso a pendência não nasce.'),
            $this->item('regras_risco', 'EPI, exame ou treinamento no risco', $regrasRisco > 0, $regrasRisco, 'SstListRiscos', 'sst-list-riscos', true,
                'Marque os itens obrigatórios nas abas do risco.'),
            $this->item('colaboradores', 'Colaboradores com cargo', $colaboradoresCargo > 0, $colaboradoresCargo, 'ListUsers', 'list-users', true,
                'Cargo e setor ativos no cadastro de usuários definem a exposição.'),
            $this->item('tipos_eq', 'Tipos de equipamento', $tiposEq > 0, $tiposEq, 'SstListEquipamentoTipos', 'sst-list-equipamento-tipos', false,
                'Seed traz extintor, hidrante etc. Ajuste prefixo e checklist.'),
            $this->item('equipamentos', 'Equipamentos ativos', $equipamentos > 0, $equipamentos, 'SstListEquipamentos', 'sst-list-equipamentos', false,
                'Cadastre o inventário do piloto e gere o QR.'),
            $this->item('alertas', 'Alertas de pendência SST', $alertasOn, $alertasOn ? 1 : 0, 'NotificationSettings', 'notification-settings', false,
                'Ligue e-mail e/ou notificação interna em Administração → Notificações automáticas.'),
            $this->item('rotina', 'Rotina diária (vistorias / digest)', $lastCron !== null, $lastCron !== null ? 1 : 0, 'SstDashboard', 'sst-dashboard', false,
                $lastCron !== null
                    ? 'Última execução: ' . ($lastCron['datetime'] ?? '')
                    : 'Dispara no login (1×/dia) ou pelos scripts cron_sst_*.php.'),
        ];

        $required = array_values(array_filter($items, static fn (array $i): bool => $i['required']));
        $requiredDone = count(array_filter($required, static fn (array $i): bool => $i['done']));

        return [
            'required_done' => $requiredDone,
            'required_total' => count($required),
            'complete' => $requiredDone === count($required),
            'items' => $items,
        ];
    }

    /**
     * @return GoLiveItem
     */
    private function item(
        string $id,
        string $label,
        bool $done,
        int $count,
        string $permission,
        string $url,
        bool $required,
        string $hint
    ): array {
        return [
            'id' => $id,
            'label' => $label,
            'done' => $done,
            'count' => $count,
            'hint' => $hint,
            'url' => $url,
            'permission' => $permission,
            'required' => $required,
        ];
    }

    private function countTable(string $table, string $where = '1=1'): int
    {
        if (!$this->tableExists($table)) {
            return 0;
        }
        $sql = "SELECT COUNT(*) FROM `{$table}` WHERE {$where}";
        try {
            $stmt = $this->getConnection()->query($sql);

            return (int) ($stmt?->fetchColumn() ?: 0);
        } catch (\Throwable) {
            return 0;
        }
    }

    private function tableExists(string $table): bool
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT 1 FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = :t LIMIT 1'
        );
        $stmt->execute([':t' => $table]);

        return (bool) $stmt->fetch(PDO::FETCH_COLUMN);
    }
}
