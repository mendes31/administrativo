<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Helpers\GenerateLog;
use PDO;
use Exception;

class RhVagasRepository extends DbConnection
{
    /**
     * Cria uma nova vaga.
     */
    public function create(array $data): int|bool
    {
        try {
            $pdo = $this->getConnection();

            $titulo = trim($data['titulo'] ?? '');
            if ($titulo === '') {
                throw new Exception('Título da vaga é obrigatório.');
            }

            $sql = 'INSERT INTO rh_vagas 
                        (titulo, descricao, requisitos, beneficios,
                         area_id, cargo_id, tipo_contrato,
                         salario_min, salario_max, mostrar_salario,
                         status, data_abertura, data_limite_inscricao,
                         quantidade_vagas, local_trabalho, jornada_trabalho,
                         observacoes, responsavel_id, created_at)
                    VALUES
                        (:titulo, :descricao, :requisitos, :beneficios,
                         :area_id, :cargo_id, :tipo_contrato,
                         :salario_min, :salario_max, :mostrar_salario,
                         :status, :data_abertura, :data_limite_inscricao,
                         :quantidade_vagas, :local_trabalho, :jornada_trabalho,
                         :observacoes, :responsavel_id, NOW())';

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':titulo', $titulo, PDO::PARAM_STR);
            $stmt->bindValue(':descricao', $data['descricao'] ?? null, $data['descricao'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':requisitos', $data['requisitos'] ?? null, $data['requisitos'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':beneficios', $data['beneficios'] ?? null, $data['beneficios'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':area_id', !empty($data['area_id']) ? (int)$data['area_id'] : null, PDO::PARAM_INT);
            $stmt->bindValue(':cargo_id', !empty($data['cargo_id']) ? (int)$data['cargo_id'] : null, PDO::PARAM_INT);
            $stmt->bindValue(':tipo_contrato', $data['tipo_contrato'] ?? 'CLT', PDO::PARAM_STR);
            $stmt->bindValue(':salario_min', !empty($data['salario_min']) ? $data['salario_min'] : null, PDO::PARAM_STR);
            $stmt->bindValue(':salario_max', !empty($data['salario_max']) ? $data['salario_max'] : null, PDO::PARAM_STR);
            $stmt->bindValue(':mostrar_salario', !empty($data['mostrar_salario']) ? 1 : 0, PDO::PARAM_BOOL);
            $stmt->bindValue(':status', $data['status'] ?? 'aberta', PDO::PARAM_STR);
            $stmt->bindValue(':data_abertura', $data['data_abertura'] ?? date('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':data_limite_inscricao', !empty($data['data_limite_inscricao']) ? $data['data_limite_inscricao'] : null, $data['data_limite_inscricao'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':quantidade_vagas', !empty($data['quantidade_vagas']) ? (int)$data['quantidade_vagas'] : 1, PDO::PARAM_INT);
            $stmt->bindValue(':local_trabalho', $data['local_trabalho'] ?? null, $data['local_trabalho'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':jornada_trabalho', $data['jornada_trabalho'] ?? null, $data['jornada_trabalho'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':observacoes', $data['observacoes'] ?? null, $data['observacoes'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':responsavel_id', !empty($data['responsavel_id']) ? (int)$data['responsavel_id'] : null, PDO::PARAM_INT);

            if (!$stmt->execute()) {
                return false;
            }

            $id = (int)$pdo->lastInsertId();

            if ($id > 0 && !empty($_SESSION['user_id'])) {
                $dadosDepois = $data;
                $dadosDepois['id'] = $id;
                \App\adms\Models\Services\LogAlteracaoService::registrarAlteracao(
                    'rh_vagas',
                    $id,
                    (int)$_SESSION['user_id'],
                    'INSERT',
                    [],
                    $dadosDepois
                );
            }

            return $id;
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Vaga não cadastrada.', [
                'titulo' => $data['titulo'] ?? '',
                'error'  => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Atualiza uma vaga existente.
     */
    public function update(int $id, array $data): bool
    {
        try {
            $pdo = $this->getConnection();
            $dadosAntes = $this->getById($id);

            if (!$dadosAntes) {
                throw new Exception('Vaga não encontrada.');
            }

            $titulo = trim($data['titulo'] ?? '');
            if ($titulo === '') {
                throw new Exception('Título da vaga é obrigatório.');
            }

            $sql = 'UPDATE rh_vagas SET
                        titulo = :titulo,
                        descricao = :descricao,
                        requisitos = :requisitos,
                        beneficios = :beneficios,
                        area_id = :area_id,
                        cargo_id = :cargo_id,
                        tipo_contrato = :tipo_contrato,
                        salario_min = :salario_min,
                        salario_max = :salario_max,
                        mostrar_salario = :mostrar_salario,
                        status = :status,
                        data_limite_inscricao = :data_limite_inscricao,
                        quantidade_vagas = :quantidade_vagas,
                        local_trabalho = :local_trabalho,
                        jornada_trabalho = :jornada_trabalho,
                        observacoes = :observacoes,
                        responsavel_id = :responsavel_id,
                        updated_at = NOW()
                    WHERE id = :id';

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':titulo', $titulo, PDO::PARAM_STR);
            $stmt->bindValue(':descricao', $data['descricao'] ?? null, $data['descricao'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':requisitos', $data['requisitos'] ?? null, $data['requisitos'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':beneficios', $data['beneficios'] ?? null, $data['beneficios'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':area_id', !empty($data['area_id']) ? (int)$data['area_id'] : null, PDO::PARAM_INT);
            $stmt->bindValue(':cargo_id', !empty($data['cargo_id']) ? (int)$data['cargo_id'] : null, PDO::PARAM_INT);
            $stmt->bindValue(':tipo_contrato', $data['tipo_contrato'] ?? 'CLT', PDO::PARAM_STR);
            $stmt->bindValue(':salario_min', !empty($data['salario_min']) ? $data['salario_min'] : null, PDO::PARAM_STR);
            $stmt->bindValue(':salario_max', !empty($data['salario_max']) ? $data['salario_max'] : null, PDO::PARAM_STR);
            $stmt->bindValue(':mostrar_salario', !empty($data['mostrar_salario']) ? 1 : 0, PDO::PARAM_BOOL);
            $stmt->bindValue(':status', $data['status'] ?? 'aberta', PDO::PARAM_STR);
            $stmt->bindValue(':data_limite_inscricao', !empty($data['data_limite_inscricao']) ? $data['data_limite_inscricao'] : null, $data['data_limite_inscricao'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':quantidade_vagas', !empty($data['quantidade_vagas']) ? (int)$data['quantidade_vagas'] : 1, PDO::PARAM_INT);
            $stmt->bindValue(':local_trabalho', $data['local_trabalho'] ?? null, $data['local_trabalho'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':jornada_trabalho', $data['jornada_trabalho'] ?? null, $data['jornada_trabalho'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':observacoes', $data['observacoes'] ?? null, $data['observacoes'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':responsavel_id', !empty($data['responsavel_id']) ? (int)$data['responsavel_id'] : null, PDO::PARAM_INT);

            $ok = $stmt->execute();

            if ($ok && $stmt->rowCount() > 0 && !empty($_SESSION['user_id'])) {
                $dadosDepois = $this->getById($id);
                \App\adms\Models\Services\LogAlteracaoService::registrarAlteracao(
                    'rh_vagas',
                    $id,
                    (int)$_SESSION['user_id'],
                    'UPDATE',
                    $dadosAntes,
                    $dadosDepois
                );
            }

            return $ok;
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Vaga não editada.', [
                'id'     => $id,
                'titulo' => $data['titulo'] ?? '',
                'error'  => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Busca uma vaga por ID.
     */
    public function getById(int $id): ?array
    {
        $sql = 'SELECT v.*,
                    d.name AS area_nome,
                    p.name AS cargo_nome,
                    u.name AS responsavel_nome
                FROM rh_vagas v
                LEFT JOIN adms_departments d ON d.id = v.area_id
                LEFT JOIN adms_positions p ON p.id = v.cargo_id
                LEFT JOIN adms_users u ON u.id = v.responsavel_id
                WHERE v.id = :id
                LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Lista vagas com filtros e paginação.
     */
    public function getAll(array $filters, int $page, int $perPage): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $where = [];
        $params = [];

        if (!empty($filters['titulo'])) {
            $where[] = 'v.titulo LIKE :titulo';
            $params[':titulo'] = '%' . $filters['titulo'] . '%';
        }
        if (!empty($filters['status'])) {
            $where[] = 'v.status = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['area_id'])) {
            $where[] = 'v.area_id = :area_id';
            $params[':area_id'] = (int)$filters['area_id'];
        }
        if (!empty($filters['cargo_id'])) {
            $where[] = 'v.cargo_id = :cargo_id';
            $params[':cargo_id'] = (int)$filters['cargo_id'];
        }
        if (!empty($filters['tipo_contrato'])) {
            $where[] = 'v.tipo_contrato = :tipo_contrato';
            $params[':tipo_contrato'] = $filters['tipo_contrato'];
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // Query para contar total
        $sqlCount = "SELECT COUNT(*) AS total
                     FROM rh_vagas v
                     {$whereSql}";
        $stmtCount = $this->getConnection()->prepare($sqlCount);
        foreach ($params as $key => $value) {
            $stmtCount->bindValue($key, $value);
        }
        $stmtCount->execute();
        $total = (int)($stmtCount->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

        // Query para buscar dados
        $sql = "SELECT v.*,
                    d.name AS area_nome,
                    p.name AS cargo_nome,
                    u.name AS responsavel_nome,
                    (SELECT COUNT(*) FROM rh_candidatos_vagas cv WHERE cv.rh_vaga_id = v.id) AS total_candidatos
                FROM rh_vagas v
                LEFT JOIN adms_departments d ON d.id = v.area_id
                LEFT JOIN adms_positions p ON p.id = v.cargo_id
                LEFT JOIN adms_users u ON u.id = v.responsavel_id
                {$whereSql}
                ORDER BY v.data_abertura DESC, v.id DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data'  => $data,
            'total' => $total,
        ];
    }

    /**
     * Deleta uma vaga.
     */
    public function delete(int $id): bool
    {
        try {
            $pdo = $this->getConnection();
            $dadosAntes = $this->getById($id);

            if (!$dadosAntes) {
                return false;
            }

            $stmt = $pdo->prepare('DELETE FROM rh_vagas WHERE id = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $ok = $stmt->execute();

            if ($ok && $stmt->rowCount() > 0 && !empty($_SESSION['user_id'])) {
                \App\adms\Models\Services\LogAlteracaoService::registrarAlteracao(
                    'rh_vagas',
                    $id,
                    (int)$_SESSION['user_id'],
                    'DELETE',
                    $dadosAntes,
                    []
                );
            }

            return $ok;
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Vaga não excluída.', [
                'id'    => $id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Vincula um candidato a uma vaga.
     */
    public function vincularCandidato(int $vagaId, int $candidatoId, ?string $observacoes = null): bool
    {
        try {
            $pdo = $this->getConnection();

            // Garantir que a vaga esteja em status que permita novos vínculos
            $vaga = $this->getById($vagaId);
            if (!$vaga) {
                throw new Exception('Vaga não encontrada.');
            }
            if (in_array($vaga['status'] ?? '', ['fechada', 'cancelada'], true)) {
                throw new Exception('Não é possível vincular candidatos a uma vaga fechada ou cancelada.');
            }

            // Verificar se já existe vínculo
            $stmtCheck = $pdo->prepare('SELECT id FROM rh_candidatos_vagas WHERE rh_candidato_id = :candidato_id AND rh_vaga_id = :vaga_id');
            $stmtCheck->bindValue(':candidato_id', $candidatoId, PDO::PARAM_INT);
            $stmtCheck->bindValue(':vaga_id', $vagaId, PDO::PARAM_INT);
            $stmtCheck->execute();
            if ($stmtCheck->fetch()) {
                throw new Exception('Candidato já está vinculado a esta vaga.');
            }

            $sql = 'INSERT INTO rh_candidatos_vagas
                        (rh_candidato_id, rh_vaga_id, status, data_candidatura, observacoes, created_at)
                    VALUES
                        (:candidato_id, :vaga_id, :status, NOW(), :observacoes, NOW())';

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':candidato_id', $candidatoId, PDO::PARAM_INT);
            $stmt->bindValue(':vaga_id', $vagaId, PDO::PARAM_INT);
            $stmt->bindValue(':status', 'candidatado', PDO::PARAM_STR);
            $stmt->bindValue(':observacoes', $observacoes, $observacoes !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);

            return $stmt->execute();
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Erro ao vincular candidato à vaga.', [
                'vaga_id'     => $vagaId,
                'candidato_id' => $candidatoId,
                'error'       => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Atualiza status do vínculo candidato-vaga.
     */
    public function atualizarStatusVinculo(int $vagaId, int $candidatoId, string $status, ?string $observacoes = null): bool
    {
        try {
            $pdo = $this->getConnection();

            // Bloquear movimentação de pipeline para vagas encerradas/canceladas
            $vaga = $this->getById($vagaId);
            if (!$vaga) {
                throw new Exception('Vaga não encontrada.');
            }
            if (in_array($vaga['status'] ?? '', ['fechada', 'cancelada'], true)) {
                throw new Exception('Não é possível alterar o status de candidaturas de uma vaga fechada ou cancelada.');
            }

            $sql = 'UPDATE rh_candidatos_vagas
                    SET status = :status,
                        observacoes = :observacoes,
                        data_ultima_atualizacao = NOW(),
                        updated_at = NOW()
                    WHERE rh_candidato_id = :candidato_id
                      AND rh_vaga_id = :vaga_id';

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            $stmt->bindValue(':observacoes', $observacoes, $observacoes !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':candidato_id', $candidatoId, PDO::PARAM_INT);
            $stmt->bindValue(':vaga_id', $vagaId, PDO::PARAM_INT);

            $ok = $stmt->execute();
            if (!$ok) {
                $errorInfo = $stmt->errorInfo();
                $msg = $errorInfo[2] ?? 'Erro desconhecido ao atualizar vínculo candidato-vaga.';
                throw new Exception($msg);
            }

            // Se atualizou o vínculo, recalcular o status_processo geral do candidato
            // baseado em todos os seus vínculos (considerando múltiplas vagas)
            if ($ok) {
                $candRepo = new \App\adms\Models\Repository\RhCandidatosRepository();
                $novoStatusProcesso = $candRepo->calcularStatusGeralPorVinculos($candidatoId);
                
                if ($novoStatusProcesso) {
                    $candRepo->atualizarStatusProcessoSimples($candidatoId, $novoStatusProcesso);
                }

                // Ao mover para "Em Entrevista", criar registro de entrevista (padrão em ATS)
                if ($status === 'em_entrevista') {
                    $entrevistasRepo = new \App\adms\Models\Repository\RhEntrevistasRepository();
                    $entrevistasRepo->criarAoMoverParaEmEntrevista($candidatoId, $vagaId);
                }

                // Ao mover para Aprovado/Reprovado no pipeline, refletir na entrevista
                if (in_array($status, ['aprovado', 'reprovado'], true)) {
                    $entrevistasRepo = new \App\adms\Models\Repository\RhEntrevistasRepository();
                    $entrevistasRepo->atualizarResultadoPorCandidatoVaga($candidatoId, $vagaId, $status);
                }
            }

            return $ok;
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Erro ao atualizar status do vínculo.', [
                'vaga_id'      => $vagaId,
                'candidato_id' => $candidatoId,
                'status'       => $status,
                'error'        => $e->getMessage(),
            ]);
            // Propagar a exceção para que o controller possa retornar a mensagem ao frontend
            throw $e;
        }
    }

    /**
     * Busca candidatos vinculados a uma vaga.
     */
    public function getCandidatosByVaga(int $vagaId): array
    {
        $sql = 'SELECT cv.*,
                    c.nome AS candidato_nome,
                    c.email AS candidato_email,
                    c.telefone AS candidato_telefone,
                    c.status_processo AS candidato_status_processo
                FROM rh_candidatos_vagas cv
                INNER JOIN rh_candidatos c ON c.id = cv.rh_candidato_id
                WHERE cv.rh_vaga_id = :vaga_id
                ORDER BY cv.data_candidatura DESC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':vaga_id', $vagaId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Busca vagas vinculadas a um candidato.
     */
    public function getVagasByCandidato(int $candidatoId): array
    {
        $sql = 'SELECT cv.*,
                    v.titulo AS vaga_titulo,
                    v.status AS vaga_status,
                    d.name AS area_nome,
                    p.name AS cargo_nome
                FROM rh_candidatos_vagas cv
                INNER JOIN rh_vagas v ON v.id = cv.rh_vaga_id
                LEFT JOIN adms_departments d ON d.id = v.area_id
                LEFT JOIN adms_positions p ON p.id = v.cargo_id
                WHERE cv.rh_candidato_id = :candidato_id
                ORDER BY cv.data_candidatura DESC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':candidato_id', $candidatoId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Desvincula um candidato de uma vaga.
     */
    public function desvincularCandidato(int $vagaId, int $candidatoId): bool
    {
        try {
            $pdo = $this->getConnection();
            $stmt = $pdo->prepare('DELETE FROM rh_candidatos_vagas WHERE rh_candidato_id = :candidato_id AND rh_vaga_id = :vaga_id');
            $stmt->bindValue(':candidato_id', $candidatoId, PDO::PARAM_INT);
            $stmt->bindValue(':vaga_id', $vagaId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Erro ao desvincular candidato da vaga.', [
                'vaga_id'      => $vagaId,
                'candidato_id' => $candidatoId,
                'error'        => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Fecha uma vaga (atualiza status e data_fechamento).
     */
    public function fecharVaga(int $id, ?string $motivo = null): bool
    {
        try {
            $pdo = $this->getConnection();
            $dadosAntes = $this->getById($id);

            $sql = 'UPDATE rh_vagas
                    SET status = :status,
                        data_fechamento = NOW(),
                        observacoes = CONCAT(COALESCE(observacoes, ""), "\n\nVaga fechada em: ", NOW(), IF(:motivo IS NOT NULL, CONCAT("\nMotivo: ", :motivo), ""))
                    WHERE id = :id';

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':status', 'fechada', PDO::PARAM_STR);
            $stmt->bindValue(':motivo', $motivo, $motivo !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);

            $ok = $stmt->execute();

            if ($ok && $stmt->rowCount() > 0 && !empty($_SESSION['user_id'])) {
                $dadosDepois = $this->getById($id);
                \App\adms\Models\Services\LogAlteracaoService::registrarAlteracao(
                    'rh_vagas',
                    $id,
                    (int)$_SESSION['user_id'],
                    'UPDATE',
                    $dadosAntes,
                    $dadosDepois
                );
            }

            return $ok;
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Erro ao fechar vaga.', [
                'id'    => $id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}

