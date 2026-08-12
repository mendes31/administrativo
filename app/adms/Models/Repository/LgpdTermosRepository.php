<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;
use Exception;

class LgpdTermosRepository extends DbConnection
{
    private function normalizeNullableDateTime(mixed $value): ?string
    {
        $v = is_string($value) ? trim($value) : '';
        if ($v === '' || $v === '0000-00-00 00:00:00' || $v === '0000-00-00') {
            return null;
        }

        return $v;
    }

    /**
     * Buscar termo ativo mais recente por tipo (ex.: 'login').
     */
    public function getTermoAtivoPorTipo(string $tipo): ?array
    {
        try {
            $sql = "SELECT *
                    FROM lgpd_termos
                    WHERE TRIM(tipo) = :tipo
                      AND status = 'Ativo'
                      AND data_inicio_vigencia <= NOW()
                      AND (
                            data_fim_vigencia IS NULL
                            OR data_fim_vigencia = '0000-00-00 00:00:00'
                            OR data_fim_vigencia >= NOW()
                          )
                    ORDER BY data_inicio_vigencia DESC, id DESC
                    LIMIT 1";

            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':tipo', $tipo, PDO::PARAM_STR);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (Exception $e) {
            error_log('Erro ao buscar termo LGPD por tipo: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Termo ativo tipo "site" cujo título contém o texto informado (case-insensitive).
     * Usado para Termos de Uso e Política de Privacidade no rodapé do portal.
     */
    public function getTermoAtivoSitePorTituloContem(string $tituloContem): ?array
    {
        $tituloContem = trim($tituloContem);
        if ($tituloContem === '') {
            return null;
        }

        try {
            $sql = "SELECT *
                    FROM lgpd_termos
                    WHERE TRIM(tipo) = 'site'
                      AND status = 'Ativo'
                      AND data_inicio_vigencia <= NOW()
                      AND (
                            data_fim_vigencia IS NULL
                            OR data_fim_vigencia = '0000-00-00 00:00:00'
                            OR data_fim_vigencia >= NOW()
                          )
                      AND LOWER(titulo) LIKE LOWER(:titulo)
                    ORDER BY data_inicio_vigencia DESC, id DESC
                    LIMIT 1";

            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':titulo', '%' . $tituloContem . '%', PDO::PARAM_STR);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (Exception $e) {
            error_log('Erro ao buscar termo LGPD site por título: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Listar termos com paginação simples (para tela administrativa).
     */
    public function getAll(int $page = 1, int $perPage = 10, array $filters = []): array
    {
        $offset = max(0, ($page - 1) * $perPage);

        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = '(titulo LIKE :search OR versao LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['tipo'])) {
            $where[] = 'tipo = :tipo';
            $params[':tipo'] = $filters['tipo'];
        }

        if (!empty($filters['status'])) {
            $where[] = 'status = :status';
            $params[':status'] = $filters['status'];
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT *
                FROM lgpd_termos
                {$whereSql}
                ORDER BY created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->getConnection()->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }

        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAmount(array $filters = []): int
    {
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = '(titulo LIKE :search OR versao LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['tipo'])) {
            $where[] = 'tipo = :tipo';
            $params[':tipo'] = $filters['tipo'];
        }

        if (!empty($filters['status'])) {
            $where[] = 'status = :status';
            $params[':status'] = $filters['status'];
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT COUNT(*) AS total FROM lgpd_termos {$whereSql}";
        $stmt = $this->getConnection()->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }

        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT * FROM lgpd_termos WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Buscar o último termo ativo (independente do tipo).
     * Usado como fallback para o consentimento de login.
     */
    public function getLastActiveTerm(): ?array
    {
        try {
            $sql = "SELECT *
                    FROM lgpd_termos
                    WHERE status = 'Ativo'
                    ORDER BY data_inicio_vigencia DESC, id DESC
                    LIMIT 1";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (Exception $e) {
            error_log('Erro ao buscar último termo LGPD ativo: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Buscar todos os termos ativos (para select em formulários).
     */
    public function getAllActiveTerms(): array
    {
        try {
            $sql = "SELECT id, versao, titulo, tipo
                    FROM lgpd_termos
                    WHERE status = 'Ativo'
                      AND data_inicio_vigencia <= NOW()
                      AND (
                            data_fim_vigencia IS NULL
                            OR data_fim_vigencia = '0000-00-00 00:00:00'
                            OR data_fim_vigencia >= NOW()
                          )
                    ORDER BY titulo ASC";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Erro ao buscar todos os termos LGPD ativos: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Slugs reservados por métodos do portal público (LgpdPublico).
     *
     * @return list<string>
     */
    public static function reservedPublicSlugs(): array
    {
        return ['requisicao', 'enviar', 'documento'];
    }

    public static function normalizeSlugPublico(string $raw): string
    {
        $raw = trim(mb_strtolower($raw, 'UTF-8'));
        $map = [
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'ê' => 'e', 'è' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c', 'ñ' => 'n',
        ];
        $raw = strtr($raw, $map);
        $raw = preg_replace('/[^a-z0-9]+/', '-', $raw) ?? '';
        return trim($raw, '-');
    }

    /**
     * @return array{publico_canal:int, slug_publico:?string, error:?string}
     */
    public function normalizePublicChannelFields(array $data, ?int $ignoreId = null): array
    {
        $publico = !empty($data['publico_canal']) ? 1 : 0;
        $slug = self::normalizeSlugPublico((string) ($data['slug_publico'] ?? ''));

        if ($publico === 0) {
            return ['publico_canal' => 0, 'slug_publico' => null, 'error' => null];
        }

        if ($slug === '') {
            $slug = self::normalizeSlugPublico((string) ($data['titulo'] ?? ''));
        }
        if ($slug === '') {
            return ['publico_canal' => 1, 'slug_publico' => null, 'error' => 'Informe o slug da URL pública (ex.: politica).'];
        }
        if (in_array($slug, self::reservedPublicSlugs(), true)) {
            return [
                'publico_canal' => 1,
                'slug_publico' => $slug,
                'error' => 'O slug "' . $slug . '" é reservado pelo canal público. Escolha outro (ex.: politica, termos).',
            ];
        }
        if ($this->slugPublicoEmUso($slug, $ignoreId)) {
            return [
                'publico_canal' => 1,
                'slug_publico' => $slug,
                'error' => 'Já existe um termo com o slug público "' . $slug . '".',
            ];
        }

        return ['publico_canal' => 1, 'slug_publico' => $slug, 'error' => null];
    }

    public function slugPublicoEmUso(string $slug, ?int $ignoreId = null): bool
    {
        $slug = self::normalizeSlugPublico($slug);
        if ($slug === '') {
            return false;
        }
        try {
            $sql = 'SELECT id FROM lgpd_termos WHERE slug_publico = :slug';
            if ($ignoreId !== null && $ignoreId > 0) {
                $sql .= ' AND id <> :id';
            }
            $sql .= ' LIMIT 1';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':slug', $slug, PDO::PARAM_STR);
            if ($ignoreId !== null && $ignoreId > 0) {
                $stmt->bindValue(':id', $ignoreId, PDO::PARAM_INT);
            }
            $stmt->execute();

            return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Erro ao verificar slug público LGPD: ' . $e->getMessage());

            return true;
        }
    }

    /**
     * Termos ativos publicados no canal /lgpd.
     *
     * @return list<array<string, mixed>>
     */
    public function listPublicosAtivos(): array
    {
        try {
            $sql = "SELECT id, titulo, versao, slug_publico, tipo, data_inicio_vigencia
                    FROM lgpd_termos
                    WHERE publico_canal = 1
                      AND status = 'Ativo'
                      AND slug_publico IS NOT NULL
                      AND slug_publico <> ''
                      AND data_inicio_vigencia <= NOW()
                      AND (
                            data_fim_vigencia IS NULL
                            OR data_fim_vigencia = '0000-00-00 00:00:00'
                            OR data_fim_vigencia >= NOW()
                          )
                    ORDER BY titulo ASC, id DESC";
            $stmt = $this->getConnection()->query($sql);

            return $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
        } catch (Exception $e) {
            error_log('Erro ao listar termos públicos LGPD: ' . $e->getMessage());

            return [];
        }
    }

    public function getPublicoAtivoPorSlug(string $slug): ?array
    {
        $slug = self::normalizeSlugPublico($slug);
        if ($slug === '' || in_array($slug, self::reservedPublicSlugs(), true)) {
            return null;
        }
        try {
            $sql = "SELECT *
                    FROM lgpd_termos
                    WHERE publico_canal = 1
                      AND status = 'Ativo'
                      AND slug_publico = :slug
                      AND data_inicio_vigencia <= NOW()
                      AND (
                            data_fim_vigencia IS NULL
                            OR data_fim_vigencia = '0000-00-00 00:00:00'
                            OR data_fim_vigencia >= NOW()
                          )
                    ORDER BY data_inicio_vigencia DESC, id DESC
                    LIMIT 1";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':slug', $slug, PDO::PARAM_STR);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row ?: null;
        } catch (Exception $e) {
            error_log('Erro ao buscar termo público por slug: ' . $e->getMessage());

            return null;
        }
    }

    public function create(array $data): bool|int
    {
        // Gerar identificador lógico do documento se não vier do formulário
        $documentoCodigo = $data['documento_codigo'] ?? null;
        if (empty($documentoCodigo)) {
            $documentoCodigo = $this->generateDocumentoCodigo($data['tipo'] ?? 'geral', $data['titulo'] ?? '');
        }

        $publicoCanal = !empty($data['publico_canal']) ? 1 : 0;
        $slugPublico = $publicoCanal === 1 ? ($data['slug_publico'] ?? null) : null;
        if ($slugPublico === '') {
            $slugPublico = null;
        }

        $sql = "INSERT INTO lgpd_termos 
                    (versao, titulo, tipo, documento_codigo, conteudo, data_inicio_vigencia, data_fim_vigencia, status, publico_canal, slug_publico, created_at) 
                VALUES 
                    (:versao, :titulo, :tipo, :documento_codigo, :conteudo, :data_inicio_vigencia, :data_fim_vigencia, :status, :publico_canal, :slug_publico, NOW())";

        $stmt = $this->getConnection()->prepare($sql);
        $dataFimVigencia = $this->normalizeNullableDateTime($data['data_fim_vigencia'] ?? null);
        $stmt->bindValue(':versao', $data['versao'], PDO::PARAM_STR);
        $stmt->bindValue(':titulo', $data['titulo'], PDO::PARAM_STR);
        $stmt->bindValue(':tipo', $data['tipo'], PDO::PARAM_STR);
        $stmt->bindValue(':documento_codigo', $documentoCodigo, PDO::PARAM_STR);
        $stmt->bindValue(':conteudo', $data['conteudo'], PDO::PARAM_STR);
        $stmt->bindValue(':data_inicio_vigencia', $data['data_inicio_vigencia'], PDO::PARAM_STR);
        $stmt->bindValue(':data_fim_vigencia', $dataFimVigencia, $dataFimVigencia !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':status', $data['status'] ?? 'Ativo', PDO::PARAM_STR);
        $stmt->bindValue(':publico_canal', $publicoCanal, PDO::PARAM_INT);
        $stmt->bindValue(':slug_publico', $slugPublico, $slugPublico !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);

        $ok = $stmt->execute();

        if (!$ok) {
            return false;
        }

        // Retornar ID e registrar log de alterações, se possível
        try {
            $id = (int)$this->getConnection()->lastInsertId();

            // Se tivermos um usuário na sessão, registrar no Log de Modificações
            if (!empty($_SESSION['user_id'])) {
                $dadosDepois = $data;
                $dadosDepois['id'] = $id;
                $dadosDepois['documento_codigo'] = $documentoCodigo;

                LogAlteracaoService::registrarAlteracao(
                    'lgpd_termos',
                    $id,
                    (int)$_SESSION['user_id'],
                    'INSERT',
                    [],
                    $dadosDepois
                );
            }

            return $id;
        } catch (\Throwable $e) {
            // Em último caso, apenas retorna true para manter compatibilidade
            return true;
        }
    }

    /**
     * Cria uma nova versão de um termo existente.
     * - Mantém tipo e documento_codigo
     * - Atualiza data_fim_vigencia e status da versão anterior
     * - Retorna o ID da nova versão ou null em caso de erro
     */
    public function createNewVersion(int $idAnterior, array $dataNova): ?int
    {
        try {
            $conn = $this->getConnection();
            $conn->beginTransaction();

            // Buscar termo anterior
            $sqlAnterior = "SELECT * FROM lgpd_termos WHERE id = :id";
            $stmtAnt = $conn->prepare($sqlAnterior);
            $stmtAnt->bindValue(':id', $idAnterior, PDO::PARAM_INT);
            $stmtAnt->execute();
            $termoAntigo = $stmtAnt->fetch(PDO::FETCH_ASSOC);

            if (!$termoAntigo) {
                $conn->rollBack();
                return null;
            }

            // Garantir que exista um documento_codigo para o termo
            $documentoCodigo = $termoAntigo['documento_codigo'] ?? null;
            if (empty($documentoCodigo)) {
                $documentoCodigo = $this->generateDocumentoCodigo($termoAntigo['tipo'], $termoAntigo['titulo']);

                $stmtUpdCodigo = $conn->prepare(
                    "UPDATE lgpd_termos SET documento_codigo = :codigo WHERE id = :id"
                );
                $stmtUpdCodigo->bindValue(':codigo', $documentoCodigo, PDO::PARAM_STR);
                $stmtUpdCodigo->bindValue(':id', $idAnterior, PDO::PARAM_INT);
                $stmtUpdCodigo->execute();
            }

            // Fechar vigência da versão anterior (libera slug único para a nova)
            $dataFim = $dataNova['data_inicio_vigencia'] ?? date('Y-m-d H:i:s');
            $stmtClose = $conn->prepare(
                "UPDATE lgpd_termos 
                 SET data_fim_vigencia = :data_fim, status = 'Inativo',
                     publico_canal = 0, slug_publico = NULL, updated_at = NOW()
                 WHERE id = :id"
            );
            $stmtClose->bindValue(':data_fim', $dataFim, PDO::PARAM_STR);
            $stmtClose->bindValue(':id', $idAnterior, PDO::PARAM_INT);
            $stmtClose->execute();

            $publicoCanal = array_key_exists('publico_canal', $dataNova)
                ? (!empty($dataNova['publico_canal']) ? 1 : 0)
                : (int) ($termoAntigo['publico_canal'] ?? 0);
            $slugPublico = $publicoCanal === 1
                ? ($dataNova['slug_publico'] ?? $termoAntigo['slug_publico'] ?? null)
                : null;
            if ($slugPublico === '') {
                $slugPublico = null;
            }

            // Inserir nova versão
            $sqlInsert = "INSERT INTO lgpd_termos 
                            (versao, titulo, tipo, documento_codigo, conteudo, data_inicio_vigencia, data_fim_vigencia, status, publico_canal, slug_publico, created_at)
                          VALUES
                            (:versao, :titulo, :tipo, :documento_codigo, :conteudo, :data_inicio_vigencia, :data_fim_vigencia, :status, :publico_canal, :slug_publico, NOW())";

            $stmtNew = $conn->prepare($sqlInsert);
            $dataFimVigenciaNova = $this->normalizeNullableDateTime($dataNova['data_fim_vigencia'] ?? null);
            $stmtNew->bindValue(':versao', $dataNova['versao'], PDO::PARAM_STR);
            $stmtNew->bindValue(':titulo', $dataNova['titulo'], PDO::PARAM_STR);
            $stmtNew->bindValue(':tipo', $termoAntigo['tipo'], PDO::PARAM_STR);
            $stmtNew->bindValue(':documento_codigo', $documentoCodigo, PDO::PARAM_STR);
            $stmtNew->bindValue(':conteudo', $dataNova['conteudo'], PDO::PARAM_STR);
            $stmtNew->bindValue(':data_inicio_vigencia', $dataNova['data_inicio_vigencia'], PDO::PARAM_STR);
            $stmtNew->bindValue(':data_fim_vigencia', $dataFimVigenciaNova, $dataFimVigenciaNova !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmtNew->bindValue(':status', $dataNova['status'] ?? 'Ativo', PDO::PARAM_STR);
            $stmtNew->bindValue(':publico_canal', $publicoCanal, PDO::PARAM_INT);
            $stmtNew->bindValue(':slug_publico', $slugPublico, $slugPublico !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);

            if (!$stmtNew->execute()) {
                $conn->rollBack();
                return null;
            }

            $newId = (int)$conn->lastInsertId();
            $conn->commit();

            return $newId > 0 ? $newId : null;
        } catch (Exception $e) {
            error_log('Erro ao criar nova versão de termo LGPD: ' . $e->getMessage());
            try {
                $this->getConnection()->rollBack();
            } catch (Exception $ignored) {
            }
            return null;
        }
    }

    /**
     * Exclui definitivamente um termo LGPD, registrando log de alterações se possível.
     */
    public function delete(int $id): bool
    {
        $conn = $this->getConnection();

        // Buscar registro antes da exclusão para log
        $dadosAntes = $this->getById($id);

        $stmt = $conn->prepare("DELETE FROM lgpd_termos WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        $ok = $stmt->execute();

        if ($ok && $stmt->rowCount() > 0 && $dadosAntes && !empty($_SESSION['user_id'])) {
            LogAlteracaoService::registrarAlteracao(
                'lgpd_termos',
                $id,
                (int)$_SESSION['user_id'],
                'DELETE',
                $dadosAntes,
                []
            );
        }

        return $ok;
    }

    /**
     * Gera um identificador lógico de documento baseado em tipo e título.
     * Ex.: tipo=login, título="Termo de Uso" => LOGIN_TERMO_DE_USO_abc123
     */
    private function generateDocumentoCodigo(string $tipo, string $titulo): string
    {
        $base = strtoupper($tipo . '_' . preg_replace('/[^a-z0-9]+/i', '_', $titulo));
        $base = trim($base, '_');
        // Sufixo para garantir unicidade mesmo com títulos repetidos
        $sufixo = substr(sha1($base . microtime(true)), 0, 6);
        return $base . '_' . $sufixo;
    }

    public function update(int $id, array $data): bool
    {
        $publicoCanal = !empty($data['publico_canal']) ? 1 : 0;
        $slugPublico = $publicoCanal === 1 ? ($data['slug_publico'] ?? null) : null;
        if ($slugPublico === '') {
            $slugPublico = null;
        }

        $sql = "UPDATE lgpd_termos
                SET versao = :versao,
                    titulo = :titulo,
                    tipo = :tipo,
                    conteudo = :conteudo,
                    data_inicio_vigencia = :data_inicio_vigencia,
                    data_fim_vigencia = :data_fim_vigencia,
                    status = :status,
                    publico_canal = :publico_canal,
                    slug_publico = :slug_publico,
                    updated_at = NOW()
                WHERE id = :id";

        $stmt = $this->getConnection()->prepare($sql);
        $dataFimVigencia = $this->normalizeNullableDateTime($data['data_fim_vigencia'] ?? null);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':versao', $data['versao'], PDO::PARAM_STR);
        $stmt->bindValue(':titulo', $data['titulo'], PDO::PARAM_STR);
        $stmt->bindValue(':tipo', $data['tipo'], PDO::PARAM_STR);
        $stmt->bindValue(':conteudo', $data['conteudo'], PDO::PARAM_STR);
        $stmt->bindValue(':data_inicio_vigencia', $data['data_inicio_vigencia'], PDO::PARAM_STR);
        $stmt->bindValue(':data_fim_vigencia', $dataFimVigencia, $dataFimVigencia !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':status', $data['status'] ?? 'Ativo', PDO::PARAM_STR);
        $stmt->bindValue(':publico_canal', $publicoCanal, PDO::PARAM_INT);
        $stmt->bindValue(':slug_publico', $slugPublico, $slugPublico !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);

        return $stmt->execute();
    }
}


