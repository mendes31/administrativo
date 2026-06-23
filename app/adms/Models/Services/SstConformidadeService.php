<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\SstEsocialEventosRepository;
use App\adms\Models\Repository\SstProgramasRepository;
use App\adms\Models\Services\DbConnection;
use PDO;

class SstConformidadeService extends DbConnection
{
    /**
     * @return array<string, mixed>
     */
    public function getResumo(): array
    {
        $programasRepo = new SstProgramasRepository();
        $esocialRepo = new SstEsocialEventosRepository();

        return [
            'pgr_vigente' => $programasRepo->countSemVigentePorTipo('PGR') > 0,
            'pcmso_vigente' => $programasRepo->countSemVigentePorTipo('PCMSO') > 0,
            'programas_vencendo' => $programasRepo->getVigentesOuAVencer(60),
            'esocial_pendentes' => $esocialRepo->countByStatus('Pendente'),
            'esocial_gerados' => $esocialRepo->countByStatus('Gerado'),
            'esocial_enviados' => $esocialRepo->countByStatus('Enviado'),
            'esocial_erros' => $esocialRepo->countByStatus('Erro'),
            'origens_sem_evento' => $this->countOrigensSemEvento(),
        ];
    }

    private function countOrigensSemEvento(): array
    {
        $sqlAcid = "SELECT COUNT(*) AS total FROM adms_sst_acidentes a
                    LEFT JOIN adms_sst_esocial_eventos e ON e.origem_tabela = 'adms_sst_acidentes'
                        AND e.origem_id = a.id AND e.tipo_evento = 'S-2210' AND e.status <> 'Cancelado'
                    WHERE e.id IS NULL";
        $sqlAso = "SELECT COUNT(*) AS total FROM adms_sst_asos a
                   LEFT JOIN adms_sst_esocial_eventos e ON e.origem_tabela = 'adms_sst_asos'
                       AND e.origem_id = a.id AND e.tipo_evento = 'S-2220' AND e.status <> 'Cancelado'
                   WHERE e.id IS NULL";
        $sqlEpi = "SELECT COUNT(*) AS total FROM adms_sst_epi_entregas a
                   LEFT JOIN adms_sst_esocial_eventos e ON e.origem_tabela = 'adms_sst_epi_entregas'
                       AND e.origem_id = a.id AND e.tipo_evento = 'S-2240' AND e.status <> 'Cancelado'
                   WHERE e.id IS NULL AND a.tipo_movimento = 'Entrega'";

        $conn = $this->getConnection();
        $out = [
            'acidentes' => (int) ($conn->query($sqlAcid)->fetch(PDO::FETCH_ASSOC)['total'] ?? 0),
            'asos' => (int) ($conn->query($sqlAso)->fetch(PDO::FETCH_ASSOC)['total'] ?? 0),
            'epi_entregas' => (int) ($conn->query($sqlEpi)->fetch(PDO::FETCH_ASSOC)['total'] ?? 0),
            'treinamentos' => 0,
        ];

        if ($this->hasTable('adms_sst_treinamento_aplicacoes')) {
            $sqlTrein = "SELECT COUNT(*) AS total FROM adms_sst_treinamento_aplicacoes a
                         LEFT JOIN adms_sst_esocial_eventos e ON e.origem_tabela = 'adms_sst_treinamento_aplicacoes'
                             AND e.origem_id = a.id AND e.tipo_evento = 'S-2245' AND e.status <> 'Cancelado'
                         WHERE e.id IS NULL AND a.status = 'concluido' AND a.data_realizacao IS NOT NULL";
            $out['treinamentos'] = (int) ($conn->query($sqlTrein)->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
        }

        return $out;
    }

    private function hasTable(string $table): bool
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :t LIMIT 1'
        );
        $stmt->bindValue(':t', $table);
        $stmt->execute();

        return (bool) $stmt->fetchColumn();
    }
}
