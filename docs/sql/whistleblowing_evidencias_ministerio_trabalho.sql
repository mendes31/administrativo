-- =============================================================================
-- Canal de Denúncias — Queries de evidência (Ministério do Trabalho / auditoria)
-- =============================================================================
-- REGRAS:
-- 1. Executar preferencialmente em HOMOLOGAÇÃO ou com denúncia de TESTE.
-- 2. Não exportar resultados com relatos reais identificáveis para terceiros.
-- 3. Nunca incluir chave de criptografia (adms_whistleblowing_config) no relatório.
-- 4. Substituir 'DEN-TESTE-MTB' pelo protocolo fictício usado na demonstração.
-- =============================================================================

-- -----------------------------------------------------------------------------
-- B1) Estrutura das tabelas principais (evidência de arquitetura)
-- -----------------------------------------------------------------------------
DESCRIBE adms_whistleblowing_reports;
DESCRIBE adms_whistleblowing_access_log;
DESCRIBE adms_whistleblowing_status_log;
DESCRIBE adms_whistleblowing_messages;
DESCRIBE adms_whistleblowing_attachments;

-- -----------------------------------------------------------------------------
-- B2) Denúncia de teste — metadados SEM conteúdo sensível
-- -----------------------------------------------------------------------------
SELECT
    id,
    protocol,
    category,
    risk_level,
    status,
    is_reporter_identified,
    committee_id,
    assigned_user_id,
    created_at,
    closed_at,
    archived_at,
    retention_archive_at,
    retention_delete_at
FROM adms_whistleblowing_reports
WHERE protocol = 'DEN-TESTE-MTB'
LIMIT 1;

-- -----------------------------------------------------------------------------
-- B3) Evidência de CRIPTOGRAFIA — conteúdo ilegível em repouso
-- (mostrar apenas prefixo da coluna cifrada)
-- -----------------------------------------------------------------------------
SELECT
    id,
    protocol,
    CHAR_LENGTH(content_encrypted) AS tamanho_bytes_cifrado,
    LEFT(content_encrypted, 60) AS amostra_cifrada_ilegivel
FROM adms_whistleblowing_reports
WHERE protocol = 'DEN-TESTE-MTB'
LIMIT 1;

-- -----------------------------------------------------------------------------
-- B4) Identificação voluntária — flag sem expor contato
-- -----------------------------------------------------------------------------
SELECT
    id,
    protocol,
    is_reporter_identified,
    CASE
        WHEN reporter_contact_encrypted IS NULL THEN 'sem_contato'
        ELSE CONCAT('cifrado_', CHAR_LENGTH(reporter_contact_encrypted), '_chars')
    END AS contato_em_repouso
FROM adms_whistleblowing_reports
WHERE protocol = 'DEN-TESTE-MTB'
LIMIT 1;

-- -----------------------------------------------------------------------------
-- B5) Senha do denunciante — apenas hash (nunca texto claro)
-- -----------------------------------------------------------------------------
SELECT
    id,
    protocol,
    LEFT(password_hash, 20) AS inicio_hash_senha,
    CHAR_LENGTH(password_hash) AS tamanho_hash
FROM adms_whistleblowing_reports
WHERE protocol = 'DEN-TESTE-MTB'
LIMIT 1;

-- -----------------------------------------------------------------------------
-- B6) AUDITORIA DE ACESSO INTERNO
-- -----------------------------------------------------------------------------
SELECT
    al.id,
    al.report_id,
    r.protocol,
    u.name AS usuario_interno,
    al.action,
    al.created_at
FROM adms_whistleblowing_access_log al
INNER JOIN adms_whistleblowing_reports r ON r.id = al.report_id
INNER JOIN adms_users u ON u.id = al.user_id
WHERE r.protocol = 'DEN-TESTE-MTB'
ORDER BY al.created_at DESC;

-- -----------------------------------------------------------------------------
-- B7) Linha do tempo de STATUS
-- -----------------------------------------------------------------------------
SELECT
    sl.id,
    r.protocol,
    sl.from_status,
    sl.to_status,
    u.name AS alterado_por,
    CASE
        WHEN sl.notes_encrypted IS NULL OR sl.notes_encrypted = '' THEN 'sem_nota'
        ELSE CONCAT('nota_cifrada_', CHAR_LENGTH(sl.notes_encrypted), '_chars')
    END AS notas,
    sl.created_at
FROM adms_whistleblowing_status_log sl
INNER JOIN adms_whistleblowing_reports r ON r.id = sl.report_id
LEFT JOIN adms_users u ON u.id = sl.user_id
WHERE r.protocol = 'DEN-TESTE-MTB'
ORDER BY sl.created_at ASC;

-- -----------------------------------------------------------------------------
-- B8) Mensagens — tipo de remetente (denunciante sem user_id interno)
-- -----------------------------------------------------------------------------
SELECT
    m.id,
    r.protocol,
    m.sender_type,
    m.is_internal_note,
    m.user_id AS usuario_interno_id,
    CASE
        WHEN m.message_encrypted IS NULL THEN 'vazio'
        ELSE CONCAT('cifrada_', CHAR_LENGTH(m.message_encrypted), '_chars')
    END AS mensagem_em_repouso,
    m.created_at
FROM adms_whistleblowing_messages m
INNER JOIN adms_whistleblowing_reports r ON r.id = m.report_id
WHERE r.protocol = 'DEN-TESTE-MTB'
ORDER BY m.created_at ASC;

-- -----------------------------------------------------------------------------
-- B9) Anexos — arquivos cifrados em disco
-- -----------------------------------------------------------------------------
SELECT
    a.id,
    r.protocol,
    a.stored_name,
    a.mime_type,
    a.size_bytes,
    a.uploaded_by,
    CASE
        WHEN a.stored_name LIKE '%.enc' THEN 'cifrado_em_disco'
        ELSE 'legado'
    END AS status_arquivo,
    a.created_at
FROM adms_whistleblowing_attachments a
INNER JOIN adms_whistleblowing_reports r ON r.id = a.report_id
WHERE r.protocol = 'DEN-TESTE-MTB';

-- -----------------------------------------------------------------------------
-- B10) Comitês e segregação de acesso
-- -----------------------------------------------------------------------------
SELECT
    c.id,
    c.name AS comite,
    c.is_active,
    u.name AS membro,
    u.email AS email_membro
FROM adms_whistleblowing_committees c
INNER JOIN adms_whistleblowing_committee_members cm ON cm.committee_id = c.id
INNER JOIN adms_users u ON u.id = cm.user_id
WHERE c.is_active = 1
ORDER BY c.name, u.name;

-- -----------------------------------------------------------------------------
-- B11) Classificações por comitê
-- -----------------------------------------------------------------------------
SELECT
    c.name AS comite,
    cc.category AS classificacao
FROM adms_whistleblowing_committee_categories cc
INNER JOIN adms_whistleblowing_committees c ON c.id = cc.committee_id
WHERE c.is_active = 1
ORDER BY c.name, cc.category;

-- -----------------------------------------------------------------------------
-- B12) Política LGPD configurada (SEM chave de criptografia)
-- -----------------------------------------------------------------------------
SELECT
    retention_archive_years,
    retention_delete_years,
    cron_enabled,
    rate_limit_max_attempts,
    rate_limit_window_minutes,
    updated_at
FROM adms_whistleblowing_config
LIMIT 1;

-- -----------------------------------------------------------------------------
-- B13) Histórico de execuções de RETENÇÃO LGPD
-- -----------------------------------------------------------------------------
SELECT
    id,
    started_at,
    finished_at,
    status,
    triggered_by,
    archived_count,
    deleted_count,
    attachments_deleted,
    duration_ms,
    LEFT(COALESCE(message, ''), 80) AS mensagem
FROM adms_whistleblowing_retention_runs
ORDER BY id DESC
LIMIT 10;

-- -----------------------------------------------------------------------------
-- B14) Resumo operacional (quantidades — sem conteúdo)
-- -----------------------------------------------------------------------------
SELECT
    COUNT(*) AS total_denuncias,
    SUM(CASE WHEN status = 'Encerrada' THEN 1 ELSE 0 END) AS encerradas,
    SUM(CASE WHEN archived_at IS NOT NULL THEN 1 ELSE 0 END) AS arquivadas,
    SUM(CASE WHEN is_reporter_identified = 1 THEN 1 ELSE 0 END) AS com_identificacao_voluntaria,
    SUM(CASE WHEN is_reporter_identified = 0 OR is_reporter_identified IS NULL THEN 1 ELSE 0 END) AS anonimas
FROM adms_whistleblowing_reports;

-- -----------------------------------------------------------------------------
-- B15) Denúncias aguardando retenção (prazos a partir do encerramento)
-- -----------------------------------------------------------------------------
SELECT
    protocol,
    status,
    closed_at,
    retention_archive_at,
    retention_delete_at,
    archived_at
FROM adms_whistleblowing_reports
WHERE status = 'Encerrada'
  AND closed_at IS NOT NULL
ORDER BY closed_at DESC
LIMIT 20;
