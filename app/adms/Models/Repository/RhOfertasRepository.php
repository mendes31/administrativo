<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use PDO;
use PDOException;

class RhOfertasRepository extends DbConnection
{
    public const STATUS_RASCUNHO = 'rascunho';
    public const STATUS_ENVIADA = 'enviada';
    public const STATUS_ACEITA = 'aceita';
    public const STATUS_RECUSADA = 'recusada';
    public const STATUS_CANCELADA = 'cancelada';

    public const ATIVOS = [
        self::STATUS_RASCUNHO,
        self::STATUS_ENVIADA,
        self::STATUS_ACEITA,
    ];

    /**
     * @return array<string, mixed>|null
     */
    public function getById(int $id): ?array
    {
        try {
            $sql = 'SELECT o.*,
                           c.nome AS candidato_nome,
                           c.email AS candidato_email,
                           v.titulo AS vaga_titulo,
                           v.status AS vaga_status
                    FROM rh_ofertas o
                    INNER JOIN rh_candidatos c ON c.id = o.rh_candidato_id
                    INNER JOIN rh_vagas v ON v.id = o.rh_vaga_id
                    WHERE o.id = :id
                    LIMIT 1';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return is_array($row) ? $row : null;
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao buscar oferta.', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findAtivaByCandidatura(int $candidaturaId): ?array
    {
        try {
            $in = "'" . implode("','", self::ATIVOS) . "'";
            $sql = "SELECT * FROM rh_ofertas
                    WHERE rh_candidatura_id = :cid AND status IN ({$in})
                    ORDER BY id DESC LIMIT 1";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':cid', $candidaturaId, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return is_array($row) ? $row : null;
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao buscar oferta ativa.', [
                'candidatura_id' => $candidaturaId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listByCandidato(int $candidatoId): array
    {
        try {
            $sql = 'SELECT o.*, v.titulo AS vaga_titulo
                    FROM rh_ofertas o
                    INNER JOIN rh_vagas v ON v.id = o.rh_vaga_id
                    WHERE o.rh_candidato_id = :id
                    ORDER BY o.id DESC';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $candidatoId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao listar ofertas do candidato.', [
                'candidato_id' => $candidatoId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int|false
    {
        try {
            $sql = 'INSERT INTO rh_ofertas
                        (rh_candidatura_id, rh_candidato_id, rh_vaga_id, status,
                         salario_oferecido, tipo_contrato, data_inicio_prevista, validade_ate,
                         observacoes, created_by_user_id, created_at, updated_at)
                    VALUES
                        (:candidatura_id, :candidato_id, :vaga_id, :status,
                         :salario, :tipo_contrato, :data_inicio, :validade,
                         :observacoes, :created_by, NOW(), NOW())';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':candidatura_id', (int) $data['rh_candidatura_id'], PDO::PARAM_INT);
            $stmt->bindValue(':candidato_id', (int) $data['rh_candidato_id'], PDO::PARAM_INT);
            $stmt->bindValue(':vaga_id', (int) $data['rh_vaga_id'], PDO::PARAM_INT);
            $stmt->bindValue(':status', (string) ($data['status'] ?? self::STATUS_RASCUNHO), PDO::PARAM_STR);
            $salario = $data['salario_oferecido'] ?? null;
            $stmt->bindValue(
                ':salario',
                $salario !== null && $salario !== '' ? $salario : null,
                $salario !== null && $salario !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
            );
            $tipo = $data['tipo_contrato'] ?? null;
            $stmt->bindValue(
                ':tipo_contrato',
                $tipo !== null && $tipo !== '' ? $tipo : null,
                $tipo !== null && $tipo !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
            );
            $inicio = $data['data_inicio_prevista'] ?? null;
            $stmt->bindValue(
                ':data_inicio',
                $inicio !== null && $inicio !== '' ? $inicio : null,
                $inicio !== null && $inicio !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
            );
            $validade = $data['validade_ate'] ?? null;
            $stmt->bindValue(
                ':validade',
                $validade !== null && $validade !== '' ? $validade : null,
                $validade !== null && $validade !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
            );
            $obs = $data['observacoes'] ?? null;
            $stmt->bindValue(
                ':observacoes',
                $obs !== null && $obs !== '' ? $obs : null,
                $obs !== null && $obs !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
            );
            $by = $data['created_by_user_id'] ?? null;
            $stmt->bindValue(
                ':created_by',
                $by !== null ? (int) $by : null,
                $by !== null ? PDO::PARAM_INT : PDO::PARAM_NULL
            );

            if (!$stmt->execute()) {
                return false;
            }

            return (int) $this->getConnection()->lastInsertId();
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao criar oferta.', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function updateStatus(
        int $id,
        string $status,
        ?string $respostaObservacoes = null,
        bool $setEnviado = false,
        bool $setRespondido = false
    ): bool {
        try {
            $sets = ['status = :status', 'updated_at = NOW()'];
            if ($setEnviado) {
                $sets[] = 'enviado_em = NOW()';
            }
            if ($setRespondido) {
                $sets[] = 'respondido_em = NOW()';
            }
            if ($respostaObservacoes !== null) {
                $sets[] = 'resposta_observacoes = :resposta';
            }

            $sql = 'UPDATE rh_ofertas SET ' . implode(', ', $sets) . ' WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            if ($respostaObservacoes !== null) {
                $stmt->bindValue(':resposta', $respostaObservacoes, PDO::PARAM_STR);
            }

            return $stmt->execute();
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao atualizar status da oferta.', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listDocumentos(int $ofertaId): array
    {
        try {
            $stmt = $this->getConnection()->prepare(
                'SELECT * FROM rh_pre_admissao_documentos
                 WHERE rh_oferta_id = :id
                 ORDER BY obrigatorio DESC, titulo ASC'
            );
            $stmt->bindValue(':id', $ofertaId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao listar documentos de pré-admissão.', [
                'oferta_id' => $ofertaId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * @param list<array{codigo: string, titulo: string, obrigatorio: bool}> $itens
     */
    public function seedDocumentos(int $ofertaId, array $itens): bool
    {
        try {
            $pdo = $this->getConnection();
            $stmt = $pdo->prepare(
                'INSERT INTO rh_pre_admissao_documentos
                    (rh_oferta_id, codigo, titulo, obrigatorio, status, created_at, updated_at)
                 VALUES
                    (:oferta_id, :codigo, :titulo, :obrigatorio, :status, NOW(), NOW())'
            );

            foreach ($itens as $item) {
                $stmt->bindValue(':oferta_id', $ofertaId, PDO::PARAM_INT);
                $stmt->bindValue(':codigo', $item['codigo'], PDO::PARAM_STR);
                $stmt->bindValue(':titulo', $item['titulo'], PDO::PARAM_STR);
                $stmt->bindValue(':obrigatorio', !empty($item['obrigatorio']) ? 1 : 0, PDO::PARAM_INT);
                $stmt->bindValue(':status', 'pendente', PDO::PARAM_STR);
                $stmt->execute();
            }

            return true;
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao semear documentos de pré-admissão.', [
                'oferta_id' => $ofertaId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function updateDocumentoStatus(
        int $documentoId,
        int $ofertaId,
        string $status,
        ?string $observacoes,
        ?int $userId
    ): bool {
        $allowed = ['pendente', 'recebido', 'aprovado', 'recusado'];
        if (!in_array($status, $allowed, true)) {
            return false;
        }

        try {
            $sets = [
                'status = :status',
                'observacoes = :observacoes',
                'updated_at = NOW()',
            ];
            if ($status === 'recebido') {
                $sets[] = 'received_at = COALESCE(received_at, NOW())';
            }
            if (in_array($status, ['aprovado', 'recusado'], true)) {
                $sets[] = 'reviewed_at = NOW()';
                $sets[] = 'reviewed_by_user_id = :reviewed_by';
            }

            $sql = 'UPDATE rh_pre_admissao_documentos SET ' . implode(', ', $sets)
                . ' WHERE id = :id AND rh_oferta_id = :oferta_id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            $stmt->bindValue(
                ':observacoes',
                $observacoes !== null && $observacoes !== '' ? $observacoes : null,
                $observacoes !== null && $observacoes !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
            );
            $stmt->bindValue(':id', $documentoId, PDO::PARAM_INT);
            $stmt->bindValue(':oferta_id', $ofertaId, PDO::PARAM_INT);
            if (in_array($status, ['aprovado', 'recusado'], true)) {
                $stmt->bindValue(
                    ':reviewed_by',
                    $userId,
                    $userId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL
                );
            }

            return $stmt->execute();
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao atualizar documento de pré-admissão.', [
                'documento_id' => $documentoId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function getDocumentoById(int $documentoId, int $ofertaId): ?array
    {
        try {
            $stmt = $this->getConnection()->prepare(
                'SELECT * FROM rh_pre_admissao_documentos
                 WHERE id = :id AND rh_oferta_id = :oferta_id
                 LIMIT 1'
            );
            $stmt->bindValue(':id', $documentoId, PDO::PARAM_INT);
            $stmt->bindValue(':oferta_id', $ofertaId, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return is_array($row) ? $row : null;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function findByDocsRequestToken(string $token): ?array
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }
        try {
            $stmt = $this->getConnection()->prepare(
                "SELECT o.*,
                        c.nome AS candidato_nome,
                        c.email AS candidato_email,
                        v.titulo AS vaga_titulo
                 FROM rh_ofertas o
                 INNER JOIN rh_candidatos c ON c.id = o.rh_candidato_id
                 INNER JOIN rh_vagas v ON v.id = o.rh_vaga_id
                 WHERE o.docs_request_token = :token
                 LIMIT 1"
            );
            $stmt->bindValue(':token', $token, PDO::PARAM_STR);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return is_array($row) ? $row : null;
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao buscar oferta por token de documentos.', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function saveDocsRequestToken(int $ofertaId, string $token, string $expiresAt): bool
    {
        try {
            $stmt = $this->getConnection()->prepare(
                'UPDATE rh_ofertas
                 SET docs_request_token = :token,
                     docs_requested_at = NOW(),
                     docs_request_expires_at = :expires_at,
                     updated_at = NOW()
                 WHERE id = :id'
            );
            $stmt->bindValue(':token', $token, PDO::PARAM_STR);
            $stmt->bindValue(':expires_at', $expiresAt, PDO::PARAM_STR);
            $stmt->bindValue(':id', $ofertaId, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao gravar token de solicitação de documentos.', [
                'oferta_id' => $ofertaId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * @param array{
     *   caminho: string,
     *   nome_original: string,
     *   mime: string,
     *   tamanho: int,
     *   uploaded_by: string,
     *   uploaded_by_user_id: ?int
     * } $fileMeta
     */
    public function attachDocumentoArquivo(int $documentoId, int $ofertaId, array $fileMeta): bool
    {
        try {
            $stmt = $this->getConnection()->prepare(
                "UPDATE rh_pre_admissao_documentos
                 SET arquivo_caminho = :caminho,
                     arquivo_nome_original = :nome,
                     arquivo_mime = :mime,
                     arquivo_tamanho = :tamanho,
                     uploaded_by = :uploaded_by,
                     uploaded_at = NOW(),
                     uploaded_by_user_id = :uploaded_by_user_id,
                     status = 'recebido',
                     received_at = NOW(),
                     reviewed_at = NULL,
                     reviewed_by_user_id = NULL,
                     updated_at = NOW()
                 WHERE id = :id AND rh_oferta_id = :oferta_id"
            );
            $stmt->bindValue(':caminho', $fileMeta['caminho'], PDO::PARAM_STR);
            $stmt->bindValue(':nome', $fileMeta['nome_original'], PDO::PARAM_STR);
            $stmt->bindValue(':mime', $fileMeta['mime'], PDO::PARAM_STR);
            $stmt->bindValue(':tamanho', $fileMeta['tamanho'], PDO::PARAM_INT);
            $stmt->bindValue(':uploaded_by', $fileMeta['uploaded_by'], PDO::PARAM_STR);
            $stmt->bindValue(
                ':uploaded_by_user_id',
                $fileMeta['uploaded_by_user_id'],
                $fileMeta['uploaded_by_user_id'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL
            );
            $stmt->bindValue(':id', $documentoId, PDO::PARAM_INT);
            $stmt->bindValue(':oferta_id', $ofertaId, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao anexar arquivo de pré-admissão.', [
                'documento_id' => $documentoId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Remove o arquivo anexado e volta o item para pendente.
     */
    public function clearDocumentoArquivo(int $documentoId, int $ofertaId): ?string
    {
        try {
            $doc = $this->getDocumentoById($documentoId, $ofertaId);
            if ($doc === null) {
                return null;
            }
            $caminhoAnterior = (string) ($doc['arquivo_caminho'] ?? '');

            $stmt = $this->getConnection()->prepare(
                "UPDATE rh_pre_admissao_documentos
                 SET arquivo_caminho = NULL,
                     arquivo_nome_original = NULL,
                     arquivo_mime = NULL,
                     arquivo_tamanho = NULL,
                     uploaded_by = NULL,
                     uploaded_at = NULL,
                     uploaded_by_user_id = NULL,
                     status = 'pendente',
                     received_at = NULL,
                     reviewed_at = NULL,
                     reviewed_by_user_id = NULL,
                     updated_at = NOW()
                 WHERE id = :id AND rh_oferta_id = :oferta_id"
            );
            $stmt->bindValue(':id', $documentoId, PDO::PARAM_INT);
            $stmt->bindValue(':oferta_id', $ofertaId, PDO::PARAM_INT);
            if (!$stmt->execute()) {
                return null;
            }

            return $caminhoAnterior !== '' ? $caminhoAnterior : '';
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'Erro ao remover arquivo de pré-admissão.', [
                'documento_id' => $documentoId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
