-- Hardening do módulo Gestão de Treinamentos
-- STATUS: OPCIONAL (admin only)
-- Uso recomendado:
-- - Fluxo oficial do projeto: migrations + validações no PHP.
-- - Este script é apenas alternativa operacional para DBA/admin em ambiente com permissões adequadas.
--
-- IMPORTANTE SOBRE TRANSAÇÃO:
-- - Ajustes de DADOS (INSERT/UPDATE/DELETE) devem ser executados em START TRANSACTION/COMMIT.
-- - Este script é de ESTRUTURA (ALTER TABLE / CREATE TRIGGER), que no MySQL faz COMMIT implícito.
--   Ou seja: não há rollback transacional completo para esses comandos.
-- - Execute bloco a bloco no phpMyAdmin, com backup/snapshot antes.

-- 1) Pré-check: combinação código+versão duplicada em adms_trainings
SELECT
    TRIM(codigo) AS codigo_norm,
    COALESCE(TRIM(versao), '') AS versao_norm,
    COUNT(*) AS qtd
FROM adms_trainings
GROUP BY TRIM(codigo), COALESCE(TRIM(versao), '')
HAVING COUNT(*) > 1;

-- 2) Constraint de unicidade para impedir repetição de código+versão
-- OBS: só execute após o pré-check retornar zero linhas.
-- OBS2: DDL (ALTER TABLE) -> commit implícito no MySQL.
ALTER TABLE adms_trainings
  ADD CONSTRAINT uk_adms_trainings_codigo_versao UNIQUE (codigo, versao);

-- 3) Índice de suporte para validações de vínculo ativo
-- OBS: DDL (ALTER TABLE) -> commit implícito no MySQL.
ALTER TABLE adms_training_users
  ADD INDEX idx_tu_user_training_tipo_status (adms_user_id, adms_training_id, tipo_vinculo, status, id);

-- 4) Trigger: bloquear novo vínculo ativo duplicado por usuário+treinamento+tipo_vinculo
-- Requer permissão para TRIGGER.
-- OBS: DDL (CREATE/DROP TRIGGER) -> commit implícito no MySQL.
-- OPCIONAL: use apenas se o usuário do banco possuir privilégio TRIGGER.
DROP TRIGGER IF EXISTS trg_tu_prevent_duplicate_active_insert;
DELIMITER $$
CREATE TRIGGER trg_tu_prevent_duplicate_active_insert
BEFORE INSERT ON adms_training_users
FOR EACH ROW
BEGIN
    IF NEW.status <> 'concluido' AND EXISTS (
        SELECT 1
        FROM adms_training_users tu
        WHERE tu.adms_user_id = NEW.adms_user_id
          AND tu.adms_training_id = NEW.adms_training_id
          AND tu.tipo_vinculo = NEW.tipo_vinculo
          AND tu.status <> 'concluido'
        LIMIT 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Vínculo ativo duplicado para usuário+treinamento+tipo.';
    END IF;
END$$
DELIMITER ;

-- 5) Trigger: se entrar vínculo por cargo ativo, remover vínculo individual ativo do mesmo usuário+treinamento
-- OBS: DDL (CREATE/DROP TRIGGER) -> commit implícito no MySQL.
-- OPCIONAL: use apenas se o usuário do banco possuir privilégio TRIGGER.
DROP TRIGGER IF EXISTS trg_tu_cargo_overrides_individual_insert;
DELIMITER $$
CREATE TRIGGER trg_tu_cargo_overrides_individual_insert
AFTER INSERT ON adms_training_users
FOR EACH ROW
BEGIN
    IF NEW.tipo_vinculo = 'cargo' AND NEW.status <> 'concluido' THEN
        DELETE FROM adms_training_users
        WHERE adms_user_id = NEW.adms_user_id
          AND adms_training_id = NEW.adms_training_id
          AND tipo_vinculo = 'individual'
          AND status <> 'concluido'
          AND id <> NEW.id;
    END IF;
END$$
DELIMITER ;

-- 6) Pós-validação (execute após criar estrutura)
-- 6.1 Verificar se a unique foi criada
SELECT
    INDEX_NAME,
    NON_UNIQUE,
    GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS colunas
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'adms_trainings'
  AND INDEX_NAME = 'uk_adms_trainings_codigo_versao'
GROUP BY INDEX_NAME, NON_UNIQUE;

-- 6.2 Verificar triggers criadas
SELECT TRIGGER_NAME, ACTION_TIMING, EVENT_MANIPULATION
FROM INFORMATION_SCHEMA.TRIGGERS
WHERE TRIGGER_SCHEMA = DATABASE()
  AND TRIGGER_NAME IN (
      'trg_tu_prevent_duplicate_active_insert',
      'trg_tu_cargo_overrides_individual_insert'
  )
ORDER BY TRIGGER_NAME;
