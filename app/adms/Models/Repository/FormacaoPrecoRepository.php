<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\FormacaoPrecoCalculator;
use App\adms\Models\Services\LogAlteracaoService;
use Exception;
use PDO;
use PDOException;

class FormacaoPrecoRepository extends DbConnection
{
    /**
     * @return array{0: list<string>, 1: array<string, mixed>}
     */
    public function validateHeader(array $form): array
    {
        $errors = [];
        $nome = trim((string) ($form['nome'] ?? ''));
        if ($nome === '') {
            $errors[] = 'Informe o nome desta precificação.';
        } elseif (mb_strlen($nome) > 180) {
            $errors[] = 'O nome deve ter no máximo 180 caracteres.';
        }

        $icms = FormacaoPrecoCalculator::parsePercentInput($form['icms_padrao'] ?? '12');
        $pis = FormacaoPrecoCalculator::parsePercentInput($form['pis_padrao'] ?? '1,65');
        $cofins = FormacaoPrecoCalculator::parsePercentInput($form['cofins_padrao'] ?? '7,60');
        if ($icms === null || $icms < 0 || $icms >= 1) {
            $errors[] = 'ICMS padrão inválido.';
            $icms = 0.12;
        }
        if ($pis === null || $pis < 0 || $pis >= 1) {
            $errors[] = 'PIS padrão inválido.';
            $pis = 0.0165;
        }
        if ($cofins === null || $cofins < 0 || $cofins >= 1) {
            $errors[] = 'COFINS padrão inválido.';
            $cofins = 0.076;
        }

        return [$errors, [
            'nome' => $nome,
            'observacao' => trim((string) ($form['observacao'] ?? '')) ?: null,
            'icms_padrao' => $icms,
            'pis_padrao' => $pis,
            'cofins_padrao' => $cofins,
        ]];
    }

    /**
     * @param array<string, mixed> $form
     * @return list<array<string, mixed>>
     */
    public function parseItensFromForm(array $form): array
    {
        $raw = $form['itens'] ?? [];
        if (!is_array($raw)) {
            return [];
        }

        $out = [];
        $ordem = 0;
        foreach ($raw as $row) {
            if (!is_array($row)) {
                continue;
            }
            $produto = trim((string) ($row['produto'] ?? ''));
            $codigo = trim((string) ($row['codigo_produto'] ?? ''));
            $hasMoney = FormacaoPrecoCalculator::parseDecimalInput($row['custo_variavel'] ?? '') !== null
                || FormacaoPrecoCalculator::parseDecimalInput($row['custo_fixo'] ?? '') !== null
                || FormacaoPrecoCalculator::parseDecimalInput($row['preco_informado'] ?? '') !== null;
            if ($produto === '' && $codigo === '' && !$hasMoney) {
                continue;
            }
            $ordem++;
            $out[] = [
                'ordem' => $ordem,
                'codigo_produto' => $codigo !== '' ? mb_substr($codigo, 0, 40) : null,
                'produto' => $produto !== '' ? mb_substr($produto, 0, 255) : 'Produto ' . $ordem,
                'forecast' => FormacaoPrecoCalculator::parseDecimalInput($row['forecast'] ?? ''),
                'icms' => FormacaoPrecoCalculator::parsePercentInput($row['icms'] ?? '') ?? 0.0,
                'pis' => FormacaoPrecoCalculator::parsePercentInput($row['pis'] ?? '') ?? 0.0,
                'cofins' => FormacaoPrecoCalculator::parsePercentInput($row['cofins'] ?? '') ?? 0.0,
                'custo_variavel' => FormacaoPrecoCalculator::parseDecimalInput($row['custo_variavel'] ?? '') ?? 0.0,
                'custo_fixo' => FormacaoPrecoCalculator::parseDecimalInput($row['custo_fixo'] ?? '') ?? 0.0,
                'meta_pb' => FormacaoPrecoCalculator::parsePercentInput($row['meta_pb'] ?? ''),
                'meta_rl' => FormacaoPrecoCalculator::parsePercentInput($row['meta_rl'] ?? ''),
                'meta_mk' => FormacaoPrecoCalculator::parsePercentInput($row['meta_mk'] ?? ''),
                'preco_informado' => FormacaoPrecoCalculator::parseDecimalInput($row['preco_informado'] ?? ''),
                'frete_pct' => FormacaoPrecoCalculator::parsePercentInput($row['frete'] ?? '') ?? 0.0,
                'comissao_pct' => FormacaoPrecoCalculator::parsePercentInput($row['comissao'] ?? '') ?? 0.0,
                'desconto_pct' => FormacaoPrecoCalculator::parsePercentInput($row['desconto'] ?? '') ?? 0.0,
            ];
        }

        return $out;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getAll(int $page, int $limit, string $filter): array
    {
        $offset = max(0, ($page - 1) * $limit);
        [$where, $params] = $this->buildWhere($filter);
        $sql = "SELECT p.id, p.nome, p.observacao, p.updated_at, p.created_at,
                       u.name AS created_by_name,
                       (SELECT COUNT(*) FROM formacao_preco_itens i WHERE i.formacao_preco_id = p.id) AS itens_count
                FROM formacao_precos p
                LEFT JOIN adms_users u ON u.id = p.created_by_user_id
                {$where}
                ORDER BY p.updated_at DESC, p.id DESC
                LIMIT :lim OFFSET :off";
        try {
            $stmt = $this->getConnection()->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'FormacaoPrecoRepository::getAll', ['error' => $e->getMessage()]);

            return [];
        }
    }

    public function countAll(string $filter): int
    {
        [$where, $params] = $this->buildWhere($filter);
        try {
            $stmt = $this->getConnection()->prepare("SELECT COUNT(*) FROM formacao_precos p {$where}");
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->execute();

            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'FormacaoPrecoRepository::countAll', ['error' => $e->getMessage()]);

            return 0;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getById(int $id, bool $withItens = true): ?array
    {
        if ($id <= 0) {
            return null;
        }
        try {
            $stmt = $this->getConnection()->prepare(
                'SELECT p.*, u.name AS created_by_name, uu.name AS updated_by_name
                 FROM formacao_precos p
                 LEFT JOIN adms_users u ON u.id = p.created_by_user_id
                 LEFT JOIN adms_users uu ON uu.id = p.updated_by_user_id
                 WHERE p.id = :id LIMIT 1'
            );
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return null;
            }
            if ($withItens) {
                $row['itens'] = $this->getItens($id);
            }

            return $row;
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'FormacaoPrecoRepository::getById', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getItens(int $headerId): array
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT * FROM formacao_preco_itens WHERE formacao_preco_id = :id ORDER BY ordem ASC, id ASC'
        );
        $stmt->bindValue(':id', $headerId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($rows as &$row) {
            $row['calc'] = FormacaoPrecoCalculator::calcularLinha($this->itemToCalcInput($row));
        }
        unset($row);

        return $rows;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data, int $actorId): int|false
    {
        try {
            $sql = 'INSERT INTO formacao_precos
                        (nome, observacao, icms_padrao, pis_padrao, cofins_padrao,
                         created_by_user_id, updated_by_user_id, created_at)
                    VALUES
                        (:nome, :observacao, :icms_padrao, :pis_padrao, :cofins_padrao,
                         :created_by, :updated_by, NOW())';
            $stmt = $this->getConnection()->prepare($sql);
            $this->bindHeader($stmt, $data);
            $stmt->bindValue(':created_by', $actorId > 0 ? $actorId : null, $actorId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':updated_by', $actorId > 0 ? $actorId : null, $actorId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->execute();
            $id = (int) $this->getConnection()->lastInsertId();
            if ($id > 0) {
                LogAlteracaoService::registrarAlteracao(
                    'formacao_precos',
                    $id,
                    $actorId > 0 ? $actorId : 1,
                    'INSERT',
                    [],
                    $data
                );
            }

            return $id > 0 ? $id : false;
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'FormacaoPrecoRepository::create', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param list<array<string, mixed>> $itens
     */
    public function update(int $id, array $data, array $itens, int $actorId): bool
    {
        $antes = $this->getById($id, false);
        if ($antes === null) {
            return false;
        }
        $conn = $this->getConnection();
        try {
            $conn->beginTransaction();
            $sql = 'UPDATE formacao_precos SET
                        nome = :nome, observacao = :observacao,
                        icms_padrao = :icms_padrao, pis_padrao = :pis_padrao, cofins_padrao = :cofins_padrao,
                        updated_by_user_id = :updated_by
                    WHERE id = :id';
            $stmt = $conn->prepare($sql);
            $this->bindHeader($stmt, $data);
            $stmt->bindValue(':updated_by', $actorId > 0 ? $actorId : null, $actorId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $conn->prepare('DELETE FROM formacao_preco_itens WHERE formacao_preco_id = :id')
                ->execute([':id' => $id]);

            $ins = $conn->prepare(
                'INSERT INTO formacao_preco_itens
                    (formacao_preco_id, ordem, codigo_produto, produto, forecast,
                     icms, pis, cofins, custo_variavel, custo_fixo,
                     meta_pb, meta_rl, meta_mk, preco_informado,
                     frete_pct, comissao_pct, desconto_pct, created_at)
                 VALUES
                    (:hid, :ordem, :codigo_produto, :produto, :forecast,
                     :icms, :pis, :cofins, :custo_variavel, :custo_fixo,
                     :meta_pb, :meta_rl, :meta_mk, :preco_informado,
                     :frete_pct, :comissao_pct, :desconto_pct, NOW())'
            );
            foreach ($itens as $item) {
                $ins->bindValue(':hid', $id, PDO::PARAM_INT);
                $ins->bindValue(':ordem', (int) $item['ordem'], PDO::PARAM_INT);
                $this->bindNullable($ins, ':codigo_produto', $item['codigo_produto']);
                $ins->bindValue(':produto', $item['produto']);
                $this->bindNullable($ins, ':forecast', $item['forecast']);
                $ins->bindValue(':icms', $item['icms']);
                $ins->bindValue(':pis', $item['pis']);
                $ins->bindValue(':cofins', $item['cofins']);
                $ins->bindValue(':custo_variavel', $item['custo_variavel']);
                $ins->bindValue(':custo_fixo', $item['custo_fixo']);
                $this->bindNullable($ins, ':meta_pb', $item['meta_pb']);
                $this->bindNullable($ins, ':meta_rl', $item['meta_rl']);
                $this->bindNullable($ins, ':meta_mk', $item['meta_mk']);
                $this->bindNullable($ins, ':preco_informado', $item['preco_informado']);
                $ins->bindValue(':frete_pct', $item['frete_pct']);
                $ins->bindValue(':comissao_pct', $item['comissao_pct']);
                $ins->bindValue(':desconto_pct', $item['desconto_pct']);
                $ins->execute();
            }

            $conn->commit();
            LogAlteracaoService::registrarAlteracao(
                'formacao_precos',
                $id,
                $actorId > 0 ? $actorId : 1,
                'UPDATE',
                ['nome' => $antes['nome'] ?? ''],
                ['nome' => $data['nome'], 'itens' => count($itens)]
            );

            return true;
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            GenerateLog::generateLog('error', 'FormacaoPrecoRepository::update', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function delete(int $id, int $actorId): bool
    {
        $antes = $this->getById($id, false);
        if ($antes === null) {
            return false;
        }
        try {
            $stmt = $this->getConnection()->prepare('DELETE FROM formacao_precos WHERE id = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $ok = $stmt->execute();
            if ($ok) {
                LogAlteracaoService::registrarAlteracao(
                    'formacao_precos',
                    $id,
                    $actorId > 0 ? $actorId : 1,
                    'DELETE',
                    ['nome' => $antes['nome'] ?? ''],
                    []
                );
            }

            return $ok;
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'FormacaoPrecoRepository::delete', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public function itemToCalcInput(array $row): array
    {
        return [
            'icms' => (float) ($row['icms'] ?? 0),
            'pis' => (float) ($row['pis'] ?? 0),
            'cofins' => (float) ($row['cofins'] ?? 0),
            'custo_variavel' => (float) ($row['custo_variavel'] ?? 0),
            'custo_fixo' => (float) ($row['custo_fixo'] ?? 0),
            'frete' => (float) ($row['frete_pct'] ?? 0),
            'comissao' => (float) ($row['comissao_pct'] ?? 0),
            'desconto' => (float) ($row['desconto_pct'] ?? 0),
            'meta_pb' => $row['meta_pb'] !== null && $row['meta_pb'] !== '' ? (float) $row['meta_pb'] : null,
            'meta_rl' => $row['meta_rl'] !== null && $row['meta_rl'] !== '' ? (float) $row['meta_rl'] : null,
            'meta_mk' => $row['meta_mk'] !== null && $row['meta_mk'] !== '' ? (float) $row['meta_mk'] : null,
            'preco_informado' => $row['preco_informado'] !== null && $row['preco_informado'] !== ''
                ? (float) $row['preco_informado'] : null,
            'forecast' => $row['forecast'] !== null && $row['forecast'] !== '' ? (float) $row['forecast'] : null,
        ];
    }

    /**
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildWhere(string $filter): array
    {
        $filter = trim($filter);
        if ($filter === '') {
            return ['', []];
        }

        return ['WHERE p.nome LIKE :q OR p.observacao LIKE :q', [':q' => '%' . $filter . '%']];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function bindHeader(\PDOStatement $stmt, array $data): void
    {
        $stmt->bindValue(':nome', $data['nome']);
        $this->bindNullable($stmt, ':observacao', $data['observacao']);
        $stmt->bindValue(':icms_padrao', $data['icms_padrao']);
        $stmt->bindValue(':pis_padrao', $data['pis_padrao']);
        $stmt->bindValue(':cofins_padrao', $data['cofins_padrao']);
    }

    private function bindNullable(\PDOStatement $stmt, string $param, mixed $value): void
    {
        if ($value === null || $value === '') {
            $stmt->bindValue($param, null, PDO::PARAM_NULL);
            return;
        }
        $stmt->bindValue($param, $value);
    }
}
