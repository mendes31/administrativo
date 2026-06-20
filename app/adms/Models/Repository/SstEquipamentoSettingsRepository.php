<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

class SstEquipamentoSettingsRepository extends DbConnection
{
  /** @return array<string, mixed> */
  public function get(): array
  {
    if (!$this->hasTable()) {
      return $this->defaults();
    }
    $sql = 'SELECT * FROM adms_sst_equipamento_settings ORDER BY id ASC LIMIT 1';
    $row = $this->getConnection()->query($sql)->fetch(PDO::FETCH_ASSOC);

    return $row ? array_merge($this->defaults(), $row) : $this->defaults();
  }

  /** @param array<string, mixed> $data */
  public function save(array $data): bool
  {
    if (!$this->hasTable()) {
      return false;
    }
    $current = $this->get();
    $uid = (int) ($_SESSION['user_id'] ?? 1);
    $sql = 'UPDATE adms_sst_equipamento_settings SET
                dia_geracao_vistorias = :dia_geracao,
                dia_previsto_padrao = :dia_previsto,
                periodicidade_meses_padrao = :periodicidade,
                gerar_vistoria_na_criacao = :gerar_criacao,
                dias_tolerancia_vencimento = :tolerancia,
                updated_by = :uid,
                updated_at = NOW()
            WHERE id = :id';
    $stmt = $this->getConnection()->prepare($sql);
    $stmt->bindValue(':dia_geracao', (int) ($data['dia_geracao_vistorias'] ?? $current['dia_geracao_vistorias']), PDO::PARAM_INT);
    $stmt->bindValue(':dia_previsto', (int) ($data['dia_previsto_padrao'] ?? $current['dia_previsto_padrao']), PDO::PARAM_INT);
    $stmt->bindValue(':periodicidade', (int) ($data['periodicidade_meses_padrao'] ?? $current['periodicidade_meses_padrao']), PDO::PARAM_INT);
    $stmt->bindValue(':gerar_criacao', !empty($data['gerar_vistoria_na_criacao']) ? 1 : 0, PDO::PARAM_INT);
    $stmt->bindValue(':tolerancia', (int) ($data['dias_tolerancia_vencimento'] ?? 0), PDO::PARAM_INT);
    $stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
    $stmt->bindValue(':id', (int) ($current['id'] ?? 1), PDO::PARAM_INT);

    return $stmt->execute();
  }

  /** @return array<string, mixed> */
  public function defaults(): array
  {
    return [
      'id' => 1,
      'dia_geracao_vistorias' => 1,
      'dia_previsto_padrao' => 1,
      'periodicidade_meses_padrao' => 1,
      'gerar_vistoria_na_criacao' => 1,
      'dias_tolerancia_vencimento' => 0,
    ];
  }

  private function hasTable(): bool
  {
    $stmt = $this->getConnection()->query("SHOW TABLES LIKE 'adms_sst_equipamento_settings'");

    return (bool) $stmt->fetchColumn();
  }
}
