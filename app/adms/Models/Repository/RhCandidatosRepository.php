<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Helpers\GenerateLog;
use PDO;
use Exception;

class RhCandidatosRepository extends DbConnection
{
    /**
     * Cria um novo candidato (currículo), já aplicando as regras de LGPD:
     * - Define status_processo (default: recebido)
     * - Define lgpd_status = Ativo
     * - Calcula lgpd_data_expiracao com base na política de retenção.
     */
    public function create(array $data): int|bool
    {
        try {
            $pdo = $this->getConnection();

            $nome            = trim($data['nome'] ?? '');
            $email           = $data['email'] ?? null;
            $telefone        = $data['telefone'] ?? null;
            $cidade          = $data['cidade'] ?? null;
            $estado          = $data['estado'] ?? null;
            $origem          = $data['origem'] ?? 'manual';
            $statusProcesso  = $data['status_processo'] ?? 'recebido';
            $observacoes     = $data['observacoes'] ?? null;
            $lgpdTermoId     = !empty($data['lgpd_termo_id']) ? (int)$data['lgpd_termo_id'] : null;
            $lgpdConsentId   = !empty($data['lgpd_consentimento_id']) ? (int)$data['lgpd_consentimento_id'] : null;
            $dataCadastroStr = $data['data_cadastramento'] ?? date('Y-m-d H:i:s');

            if ($nome === '') {
                throw new Exception('Nome do candidato é obrigatório.');
            }

            // Definir contexto de retenção com base no status_processo
            $contexto = $this->resolveContextoRetencao($statusProcesso);
            $prazoMeses = $contexto
                ? $this->buscarPrazoMesesPorContexto($contexto)
                : 12;

            $dataCadastro = new \DateTime($dataCadastroStr);
            $dataExpiracao = clone $dataCadastro;
            $dataExpiracao->modify('+' . $prazoMeses . ' months');

            $areaInteresse = $data['area_interesse'] ?? null;
            $graduacao = $data['graduacao'] ?? null;
            $ultimaExperiencia = $data['ultima_experiencia'] ?? null;
            $score = !empty($data['score']) ? max(0, min(100, (int)$data['score'])) : null;
            $classificacao = $data['classificacao'] ?? null;
            $classificacaoObservacoes = $data['classificacao_observacoes'] ?? null;

            $sql = 'INSERT INTO rh_candidatos 
                        (nome, email, telefone, cidade, estado, area_interesse, graduacao, ultima_experiencia,
                         score, classificacao, classificacao_observacoes,
                         origem, status_processo, data_cadastramento, data_ultimo_movimento, observacoes,
                         lgpd_termo_id, lgpd_consentimento_id, lgpd_status,
                         lgpd_data_consentimento, lgpd_data_expiracao, lgpd_motivo_anonimizacao,
                         created_at)
                    VALUES
                        (:nome, :email, :telefone, :cidade, :estado, :area_interesse, :graduacao, :ultima_experiencia,
                         :score, :classificacao, :classificacao_observacoes,
                         :origem, :status_processo, :data_cadastramento, NULL, :observacoes,
                         :lgpd_termo_id, :lgpd_consentimento_id, :lgpd_status,
                         :lgpd_data_consentimento, :lgpd_data_expiracao, NULL,
                         NOW())';

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':nome', $nome, PDO::PARAM_STR);
            $stmt->bindValue(':email', $email, $email !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':telefone', $telefone, $telefone !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':cidade', $cidade, $cidade !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':estado', $estado, $estado !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':area_interesse', $areaInteresse, $areaInteresse !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':graduacao', $graduacao, $graduacao !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':ultima_experiencia', $ultimaExperiencia, $ultimaExperiencia !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':score', $score, $score !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':classificacao', $classificacao, $classificacao !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':classificacao_observacoes', $classificacaoObservacoes, $classificacaoObservacoes !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':origem', $origem, PDO::PARAM_STR);
            $stmt->bindValue(':status_processo', $statusProcesso, PDO::PARAM_STR);
            $stmt->bindValue(':data_cadastramento', $dataCadastro->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':observacoes', $observacoes, $observacoes !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':lgpd_termo_id', $lgpdTermoId, $lgpdTermoId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':lgpd_consentimento_id', $lgpdConsentId, $lgpdConsentId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':lgpd_status', 'Ativo', PDO::PARAM_STR);

            $dataConsent = $data['lgpd_data_consentimento'] ?? $dataCadastro->format('Y-m-d H:i:s');
            $stmt->bindValue(':lgpd_data_consentimento', $dataConsent, PDO::PARAM_STR);
            $stmt->bindValue(':lgpd_data_expiracao', $dataExpiracao->format('Y-m-d H:i:s'), PDO::PARAM_STR);

            if (!$stmt->execute()) {
                return false;
            }

            $id = (int)$pdo->lastInsertId();

            if ($id > 0 && !empty($_SESSION['user_id'])) {
                $dadosDepois = $data;
                $dadosDepois['id'] = $id;
                $dadosDepois['lgpd_status'] = 'Ativo';
                $dadosDepois['lgpd_data_expiracao'] = $dataExpiracao->format('Y-m-d H:i:s');

                \App\adms\Models\Services\LogAlteracaoService::registrarAlteracao(
                    'rh_candidatos',
                    $id,
                    (int)$_SESSION['user_id'],
                    'INSERT',
                    [],
                    $dadosDepois
                );
            }

            return $id;
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Candidato (currículo) não foi criado.', [
                'data'  => $data,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Atualiza um candidato existente e, se necessário, recalcula a data de expiração LGPD
     * quando o status_processo muda de grupo (não aproveitado / banco_talentos).
     */
    public function update(int $id, array $data): bool
    {
        try {
            $pdo = $this->getConnection();

            $stmtOld = $pdo->prepare('SELECT * FROM rh_candidatos WHERE id = :id');
            $stmtOld->bindValue(':id', $id, PDO::PARAM_INT);
            $stmtOld->execute();
            $dadosAntes = $stmtOld->fetch(PDO::FETCH_ASSOC);

            if (!$dadosAntes) {
                throw new Exception('Candidato não encontrado para edição.');
            }

            $nome           = trim($data['nome'] ?? $dadosAntes['nome']);
            $email          = $data['email'] ?? $dadosAntes['email'];
            $telefone       = $data['telefone'] ?? $dadosAntes['telefone'];
            $cidade         = $data['cidade'] ?? $dadosAntes['cidade'];
            $estado         = $data['estado'] ?? $dadosAntes['estado'];
            $areaInteresse  = $data['area_interesse'] ?? $dadosAntes['area_interesse'] ?? null;
            $graduacao      = $data['graduacao'] ?? $dadosAntes['graduacao'] ?? null;
            $ultimaExperiencia = $data['ultima_experiencia'] ?? $dadosAntes['ultima_experiencia'] ?? null;
            $score = isset($data['score']) ? (($data['score'] !== '' && $data['score'] !== null) ? max(0, min(100, (int)$data['score'])) : null) : ($dadosAntes['score'] ?? null);
            $classificacao = $data['classificacao'] ?? $dadosAntes['classificacao'] ?? null;
            $classificacaoObservacoes = $data['classificacao_observacoes'] ?? $dadosAntes['classificacao_observacoes'] ?? null;
            $origem         = $data['origem'] ?? $dadosAntes['origem'];
            $statusProcesso = $data['status_processo'] ?? $dadosAntes['status_processo'];
            $observacoes    = $data['observacoes'] ?? $dadosAntes['observacoes'];

            if ($nome === '') {
                throw new Exception('Nome do candidato é obrigatório.');
            }

            // Recalcular lgpd_data_expiracao se o status_processo mudou
            $lgpdDataExpiracao = $dadosAntes['lgpd_data_expiracao'];
            if ($statusProcesso !== $dadosAntes['status_processo']) {
                $contexto = $this->resolveContextoRetencao($statusProcesso);
                if ($contexto) {
                    $prazoMeses = $this->buscarPrazoMesesPorContexto($contexto);
                    $baseDate = new \DateTime($dadosAntes['data_cadastramento'] ?? 'now');
                    $exp = clone $baseDate;
                    $exp->modify('+' . $prazoMeses . ' months');
                    $lgpdDataExpiracao = $exp->format('Y-m-d H:i:s');
                }
            }

            $sql = 'UPDATE rh_candidatos
                    SET nome = :nome,
                        email = :email,
                        telefone = :telefone,
                        cidade = :cidade,
                        estado = :estado,
                        area_interesse = :area_interesse,
                        graduacao = :graduacao,
                        ultima_experiencia = :ultima_experiencia,
                        score = :score,
                        classificacao = :classificacao,
                        classificacao_observacoes = :classificacao_observacoes,
                        origem = :origem,
                        status_processo = :status_processo,
                        observacoes = :observacoes,
                        lgpd_data_expiracao = :lgpd_data_expiracao,
                        updated_at = NOW()
                    WHERE id = :id';

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':nome', $nome, PDO::PARAM_STR);
            $stmt->bindValue(':email', $email, $email !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':telefone', $telefone, $telefone !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':cidade', $cidade, $cidade !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':estado', $estado, $estado !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':area_interesse', $areaInteresse, $areaInteresse !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':graduacao', $graduacao, $graduacao !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':ultima_experiencia', $ultimaExperiencia, $ultimaExperiencia !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':score', $score, $score !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':classificacao', $classificacao, $classificacao !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':classificacao_observacoes', $classificacaoObservacoes, $classificacaoObservacoes !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':origem', $origem, PDO::PARAM_STR);
            $stmt->bindValue(':status_processo', $statusProcesso, PDO::PARAM_STR);
            $stmt->bindValue(':observacoes', $observacoes, $observacoes !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':lgpd_data_expiracao', $lgpdDataExpiracao, PDO::PARAM_STR);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);

            $ok = $stmt->execute();

            if ($ok && !empty($_SESSION['user_id'])) {
                $stmtNew = $pdo->prepare('SELECT * FROM rh_candidatos WHERE id = :id');
                $stmtNew->bindValue(':id', $id, PDO::PARAM_INT);
                $stmtNew->execute();
                $dadosDepois = $stmtNew->fetch(PDO::FETCH_ASSOC) ?: [];

                \App\adms\Models\Services\LogAlteracaoService::registrarAlteracao(
                    'rh_candidatos',
                    $id,
                    (int)$_SESSION['user_id'],
                    'UPDATE',
                    $dadosAntes,
                    $dadosDepois
                );
            }

            return $ok;
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Candidato (currículo) não foi atualizado.', [
                'id'    => $id,
                'data'  => $data,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Aplica a política de retenção/anonimização de currículos com base em:
     * - lgpd_status = 'Ativo'
     * - lgpd_data_expiracao <= hoje
     * - status_processo IN ('recebido', 'reprovado', 'banco_talentos')
     *
     * Retorna um array com contadores para monitoramento.
     */
    public function aplicarPoliticaRetencao(): array
    {
        $result = [
            'total_encontrados'   => 0,
            'total_anonimizados'  => 0,
            'total_erros'         => 0,
        ];

        try {
            $pdo = $this->getConnection();

            $sql = "SELECT * 
                    FROM rh_candidatos 
                    WHERE lgpd_status = 'Ativo'
                      AND lgpd_data_expiracao IS NOT NULL
                      AND lgpd_data_expiracao <= CURDATE()
                      AND status_processo IN ('recebido', 'reprovado', 'banco_talentos')";

            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $candidatos = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $result['total_encontrados'] = count($candidatos);

            foreach ($candidatos as $cand) {
                try {
                    $this->anonimizarCandidato($cand);
                    $result['total_anonimizados']++;
                } catch (Exception $e) {
                    $result['total_erros']++;
                    GenerateLog::generateLog('error', 'Erro ao anonimizar candidato (retenção LGPD).', [
                        'rh_candidato_id' => $cand['id'] ?? null,
                        'error'           => $e->getMessage(),
                    ]);
                }
            }
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Erro geral ao aplicar política de retenção de currículos.', [
                'error' => $e->getMessage(),
            ]);
        }

        return $result;
    }

    /**
     * Anonimiza um candidato e remove/apaga anexos associados.
     *
     * @param array $candidato Registro completo de rh_candidatos.
     * @throws Exception
     */
    private function anonimizarCandidato(array $candidato): void
    {
        $pdo = $this->getConnection();
        $pdo->beginTransaction();

        try {
            $id = (int) $candidato['id'];

            // 1) Buscar anexos para remover arquivos físicos
            $sqlAnexos = 'SELECT id, arquivo_caminho FROM rh_candidatos_anexos WHERE rh_candidato_id = :id';
            $stmtAnexos = $pdo->prepare($sqlAnexos);
            $stmtAnexos->bindValue(':id', $id, PDO::PARAM_INT);
            $stmtAnexos->execute();
            $anexos = $stmtAnexos->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $projectRoot = dirname(__DIR__, 4);

            foreach ($anexos as $anexo) {
                if (!empty($anexo['arquivo_caminho'])) {
                    $path = $projectRoot . DIRECTORY_SEPARATOR . ltrim($anexo['arquivo_caminho'], DIRECTORY_SEPARATOR);
                    if (file_exists($path) && is_file($path)) {
                        @unlink($path);
                    }
                }
            }

            // Opcional: manter registros de anexos ou apagar
            if (!empty($anexos)) {
                $delStmt = $pdo->prepare('DELETE FROM rh_candidatos_anexos WHERE rh_candidato_id = :id');
                $delStmt->bindValue(':id', $id, PDO::PARAM_INT);
                $delStmt->execute();
            }

            $dadosAntes = $candidato;

            // 2) Anonimizar campos pessoais do candidato
            $sqlUpdate = "UPDATE rh_candidatos
                          SET 
                              nome = 'Anonimizado',
                              email = NULL,
                              telefone = NULL,
                              cidade = NULL,
                              estado = NULL,
                              observacoes = NULL,
                              lgpd_status = 'Anonimizado',
                              lgpd_motivo_anonimizacao = :motivo,
                              updated_at = NOW()
                          WHERE id = :id";

            $motivo = 'Prazo de retenção expirado';

            $stmtUpdate = $pdo->prepare($sqlUpdate);
            $stmtUpdate->bindValue(':motivo', $motivo, PDO::PARAM_STR);
            $stmtUpdate->bindValue(':id', $id, PDO::PARAM_INT);
            $stmtUpdate->execute();

            // Montar dadosDepois para log
            $dadosDepois = $dadosAntes;
            $dadosDepois['nome'] = 'Anonimizado';
            $dadosDepois['email'] = null;
            $dadosDepois['telefone'] = null;
            $dadosDepois['cidade'] = null;
            $dadosDepois['estado'] = null;
            $dadosDepois['observacoes'] = null;
            $dadosDepois['lgpd_status'] = 'Anonimizado';
            $dadosDepois['lgpd_motivo_anonimizacao'] = $motivo;

            // 3) Registrar log de alteração (se houver usuário logado)
            if (!empty($_SESSION['user_id'])) {
                \App\adms\Models\Services\LogAlteracaoService::registrarAlteracao(
                    'rh_candidatos',
                    $id,
                    (int) $_SESSION['user_id'],
                    'UPDATE',
                    $dadosAntes,
                    $dadosDepois
                );
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Resolve o contexto de retenção baseado no status_processo.
     * - recebido / reprovado -> curriculo_nao_aproveitado
     * - banco_talentos       -> banco_talentos
     * - em_entrevista / contratado -> null (tratados fora da retenção de currículo)
     */
    private function resolveContextoRetencao(string $statusProcesso): ?string
    {
        $statusProcesso = strtolower($statusProcesso);

        if (in_array($statusProcesso, ['recebido', 'reprovado'], true)) {
            return 'curriculo_nao_aproveitado';
        }

        if ($statusProcesso === 'banco_talentos') {
            return 'banco_talentos';
        }

        return null;
    }

    /**
     * Busca o prazo de retenção (em meses) na tabela lgpd_politicas_retencao.
     * Caso não encontre, retorna 12 meses como padrão seguro.
     */
    private function buscarPrazoMesesPorContexto(string $contexto): int
    {
        try {
            $sql = 'SELECT prazo_meses FROM lgpd_politicas_retencao WHERE contexto = :contexto AND status = "Ativo" LIMIT 1';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':contexto', $contexto, PDO::PARAM_STR);
            $stmt->execute();
            $prazo = $stmt->fetchColumn();

            if ($prazo === false || $prazo === null) {
                return 12;
            }

            $prazoInt = (int)$prazo;
            return $prazoInt > 0 ? $prazoInt : 12;
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Erro ao buscar prazo de retenção LGPD.', [
                'contexto' => $contexto,
                'error'    => $e->getMessage(),
            ]);
            return 12;
        }
    }

    /**
     * Registra anexo de currículo/documento para o candidato.
     */
    public function addAnexo(int $candidatoId, array $data): int|bool
    {
        try {
            $sql = 'INSERT INTO rh_candidatos_anexos 
                        (rh_candidato_id, tipo, arquivo_caminho, nome_original, created_at)
                    VALUES 
                        (:rh_candidato_id, :tipo, :arquivo_caminho, :nome_original, NOW())';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':rh_candidato_id', $candidatoId, PDO::PARAM_INT);
            $stmt->bindValue(':tipo', $data['tipo'] ?? 'curriculo', PDO::PARAM_STR);
            $stmt->bindValue(':arquivo_caminho', $data['arquivo_caminho'] ?? '', PDO::PARAM_STR);
            $stmt->bindValue(':nome_original', $data['nome_original'] ?? null, PDO::PARAM_STR);
            $stmt->execute();
            return (int)$this->getConnection()->lastInsertId();
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Erro ao registrar anexo de candidato.', [
                'candidato_id' => $candidatoId,
                'data'         => $data,
                'error'        => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Retorna anexo(s) de um candidato.
     */
    public function getAnexosByCandidato(int $candidatoId): array
    {
        $sql = 'SELECT * FROM rh_candidatos_anexos WHERE rh_candidato_id = :id ORDER BY created_at DESC, id DESC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $candidatoId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Retorna um candidato por ID.
     */
    public function getById(int $id): ?array
    {
        $sql = 'SELECT * FROM rh_candidatos WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Retorna lista paginada de candidatos + total para paginação.
     *
     * @return array ['data' => [], 'total' => int]
     */
    public function getAll(array $filters, int $page, int $perPage): array
    {
        $page    = max(1, $page);
        $perPage = max(1, $perPage);
        $offset  = ($page - 1) * $perPage;

        $where = ['1=1'];
        $params = [];

        if (!empty($filters['nome'])) {
            $where[] = 'nome LIKE :nome';
            $params[':nome'] = '%' . $filters['nome'] . '%';
        }
        if (!empty($filters['email'])) {
            $where[] = 'email LIKE :email';
            $params[':email'] = '%' . $filters['email'] . '%';
        }
        if (!empty($filters['origem'])) {
            $where[] = 'origem = :origem';
            $params[':origem'] = $filters['origem'];
        }
        if (!empty($filters['status_processo'])) {
            $where[] = 'status_processo = :status_processo';
            $params[':status_processo'] = $filters['status_processo'];
        }
        if (!empty($filters['area_interesse'])) {
            $where[] = 'area_interesse = :area_interesse';
            $params[':area_interesse'] = $filters['area_interesse'];
        }
        if (!empty($filters['score_min'])) {
            $where[] = 'score >= :score_min';
            $params[':score_min'] = (int)$filters['score_min'];
        }
        if (!empty($filters['score_max'])) {
            $where[] = 'score <= :score_max';
            $params[':score_max'] = (int)$filters['score_max'];
        }
        if (!empty($filters['classificacao'])) {
            $where[] = 'classificacao = :classificacao';
            $params[':classificacao'] = $filters['classificacao'];
        }

        $whereSql = implode(' AND ', $where);

        $sql = "SELECT * FROM rh_candidatos WHERE {$whereSql} ORDER BY data_cadastramento DESC, id DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $paramType = PDO::PARAM_STR;
            if (strpos($k, 'score') !== false) {
                $paramType = PDO::PARAM_INT;
            }
            $stmt->bindValue($k, $v, $paramType);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $sqlCount = "SELECT COUNT(*) FROM rh_candidatos WHERE {$whereSql}";
        $stmtCount = $this->getConnection()->prepare($sqlCount);
        foreach ($params as $k => $v) {
            $paramType = PDO::PARAM_STR;
            if (strpos($k, 'score') !== false) {
                $paramType = PDO::PARAM_INT;
            }
            $stmtCount->bindValue($k, $v, $paramType);
        }
        $stmtCount->execute();
        $total = (int)$stmtCount->fetchColumn();

        return [
            'data'  => $data,
            'total' => $total,
        ];
    }

    /**
     * Estatísticas resumidas para o mini dashboard de Recrutamento / Currículos.
     *
     * - total_candidatos: todos os registros (inclui anonimizado)
     * - por_status_processo: contagem por status_processo
     * - por_origem: contagem por origem (e-mail, WhatsApp, formulário, manual, etc.)
     * - lgpd_resumo: total por lgpd_status (Ativo, Vencido, Anonimizado)
     */
    public function getDashboardStats(): array
    {
        $pdo = $this->getConnection();

        // Total geral de candidatos
        $stmtTotal = $pdo->query('SELECT COUNT(*) AS total FROM rh_candidatos');
        $rowTotal = $stmtTotal->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0];
        $totalCandidatos = (int)($rowTotal['total'] ?? 0);

        // Contagem por status_processo
        $sqlStatus = 'SELECT status_processo, COUNT(*) AS total 
                      FROM rh_candidatos 
                      GROUP BY status_processo
                      ORDER BY status_processo';
        $stmtStatus = $pdo->query($sqlStatus);
        $statusRows = $stmtStatus->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $porStatus = [];
        foreach ($statusRows as $row) {
            $key = $row['status_processo'] ?? 'indefinido';
            $porStatus[$key] = (int)($row['total'] ?? 0);
        }

        // Contagem por origem
        $sqlOrigem = 'SELECT origem, COUNT(*) AS total 
                      FROM rh_candidatos 
                      GROUP BY origem
                      ORDER BY origem';
        $stmtOrigem = $pdo->query($sqlOrigem);
        $origemRows = $stmtOrigem->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $porOrigem = [];
        foreach ($origemRows as $row) {
            $key = $row['origem'] ?? 'indefinido';
            $porOrigem[$key] = (int)($row['total'] ?? 0);
        }

        // Resumo LGPD por lgpd_status
        $sqlLgpd = "SELECT lgpd_status, COUNT(*) AS total 
                    FROM rh_candidatos 
                    GROUP BY lgpd_status
                    ORDER BY lgpd_status";
        $stmtLgpd = $pdo->query($sqlLgpd);
        $lgpdRows = $stmtLgpd->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $lgpdResumo = [];
        foreach ($lgpdRows as $row) {
            $key = $row['lgpd_status'] ?? 'indefinido';
            $lgpdResumo[$key] = (int)($row['total'] ?? 0);
        }

        return [
            'total_candidatos'   => $totalCandidatos,
            'por_status'         => $porStatus,
            'por_origem'         => $porOrigem,
            'lgpd_resumo'        => $lgpdResumo,
        ];
    }
}

