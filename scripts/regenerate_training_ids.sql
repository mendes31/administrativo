-- ============================================================
-- Script SQL para regenerar IDs da tabela adms_trainings
-- de forma sequencial, preservando todos os dados.
-- 
-- IMPORTANTE: Execute isso em uma transação no phpMyAdmin
-- ou via SSH com BEGIN/COMMIT.
-- ============================================================

-- Passo 1: Criar tabela temporária com os dados atuais
CREATE TEMPORARY TABLE adms_trainings_backup AS
SELECT * FROM adms_trainings
ORDER BY id ASC;

-- Passo 2: Criar mapeamento de IDs antigos -> novos (sequenciais)
CREATE TEMPORARY TABLE id_mapping (
    old_id INT UNSIGNED,
    new_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
) AS
SELECT id AS old_id
FROM adms_trainings
ORDER BY id ASC;

-- Passo 3: Limpar a tabela original (CUIDADO: isso apaga todos os registros)
-- Descomente a linha abaixo apenas se tiver certeza:
-- TRUNCATE TABLE adms_trainings;

-- Passo 4: Reinserir dados com novos IDs sequenciais
-- (Execute isso DEPOIS do TRUNCATE)
INSERT INTO adms_trainings (
    nome, codigo, versao, prazo_treinamento, tipo, instrutor,
    carga_horaria, ativo, created_at, updated_at,
    instructor_user_id, instructor_email, instructor_name,
    reciclagem, reciclagem_periodo,
    area_responsavel_id, area_elaborador_id, tipo_obrigatoriedade
)
SELECT 
    b.nome, b.codigo, b.versao, b.prazo_treinamento, b.tipo, b.instrutor,
    b.carga_horaria, b.ativo, b.created_at, b.updated_at,
    b.instructor_user_id, b.instructor_email, b.instructor_name,
    b.reciclagem, b.reciclagem_periodo,
    b.area_responsavel_id, b.area_elaborador_id, b.tipo_obrigatoriedade
FROM adms_trainings_backup b
ORDER BY b.id ASC;

-- Passo 5: Atualizar FKs nas tabelas relacionadas
-- (Execute isso DEPOIS de reinserir os dados)

-- Atualizar adms_training_users
UPDATE adms_training_users tu
INNER JOIN id_mapping m ON tu.adms_training_id = m.old_id
SET tu.adms_training_id = m.new_id;

-- Atualizar adms_training_applications
UPDATE adms_training_applications ta
INNER JOIN id_mapping m ON ta.adms_training_id = m.old_id
SET ta.adms_training_id = m.new_id;

-- Atualizar adms_training_positions
UPDATE adms_training_positions tp
INNER JOIN id_mapping m ON tp.adms_training_id = m.old_id
SET tp.adms_training_id = m.new_id;

-- Atualizar adms_training_contents
UPDATE adms_training_contents tc
INNER JOIN id_mapping m ON tc.adms_training_id = m.old_id
SET tc.adms_training_id = m.new_id;

-- Atualizar adms_training_evaluations
UPDATE adms_training_evaluations te
INNER JOIN id_mapping m ON te.adms_training_id = m.old_id
SET te.adms_training_id = m.new_id;

-- ============================================================
-- FIM DO SCRIPT
-- ============================================================

