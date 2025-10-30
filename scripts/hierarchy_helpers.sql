-- ========================================
-- 🔧 HELPERS SQL PARA GERENCIAR HIERARQUIA
-- ========================================

-- ========================================
-- 1️⃣ VISUALIZAR HIERARQUIA COMPLETA
-- ========================================

-- Ver toda a estrutura hierárquica
SELECT 
    u.id,
    u.name AS usuario,
    u.user_department_id AS depto_id,
    d.name AS departamento,
    u.immediate_supervisor_id AS supervisor_id,
    s.name AS supervisor,
    u.status,
    (SELECT COUNT(*) FROM adms_users WHERE immediate_supervisor_id = u.id AND status = 1) AS qtd_subordinados
FROM adms_users u
LEFT JOIN adms_users s ON u.immediate_supervisor_id = s.id
LEFT JOIN adms_departments d ON u.user_department_id = d.id
WHERE u.status = 1
ORDER BY 
    COALESCE(u.immediate_supervisor_id, 0), 
    u.name;


-- ========================================
-- 2️⃣ IDENTIFICAR GERENTES (TEM SUBORDINADOS)
-- ========================================

SELECT 
    u.id,
    u.name AS gerente,
    COUNT(sub.id) AS qtd_subordinados_diretos,
    GROUP_CONCAT(sub.name ORDER BY sub.name SEPARATOR ', ') AS subordinados
FROM adms_users u
INNER JOIN adms_users sub ON sub.immediate_supervisor_id = u.id
WHERE u.status = 1 AND sub.status = 1
GROUP BY u.id, u.name
ORDER BY qtd_subordinados_diretos DESC;


-- ========================================
-- 3️⃣ IDENTIFICAR USUÁRIOS SEM SUPERVISOR
-- ========================================

SELECT 
    id,
    name,
    user_department_id,
    user_position_id,
    status
FROM adms_users
WHERE immediate_supervisor_id IS NULL
AND status = 1
ORDER BY name;


-- ========================================
-- 4️⃣ TRANSFERIR EQUIPE (TODOS SUBORDINADOS)
-- ========================================

-- EXEMPLO: Transferir equipe de João (ID 5) para Maria (ID 8)

-- PASSO 1: Ver subordinados antes da transferência
SELECT 
    id, 
    name, 
    immediate_supervisor_id AS supervisor_atual
FROM adms_users 
WHERE immediate_supervisor_id = 5  -- ID do João
AND status = 1;

-- PASSO 2: Executar transferência
UPDATE adms_users 
SET immediate_supervisor_id = 8    -- ID da Maria (novo supervisor)
WHERE immediate_supervisor_id = 5  -- ID do João (supervisor atual)
AND status = 1;

-- PASSO 3: Confirmar transferência
SELECT 
    id, 
    name, 
    immediate_supervisor_id AS novo_supervisor
FROM adms_users 
WHERE immediate_supervisor_id = 8
AND status = 1;


-- ========================================
-- 5️⃣ PROMOVER SUBORDINADOS (SOBEM 1 NÍVEL)
-- ========================================

-- EXEMPLO: João (ID 5) será removido, subordinados vão para o supervisor de João

-- PASSO 1: Ver quem é o supervisor do João
SELECT 
    id,
    name,
    immediate_supervisor_id AS supervisor_do_joao
FROM adms_users 
WHERE id = 5;

-- PASSO 2: Promover subordinados (eles vão para o supervisor do João)
UPDATE adms_users 
SET immediate_supervisor_id = (
    SELECT immediate_supervisor_id 
    FROM (SELECT immediate_supervisor_id FROM adms_users WHERE id = 5) AS temp
)
WHERE immediate_supervisor_id = 5;


-- ========================================
-- 6️⃣ VERIFICAR ANTES DE DESATIVAR/DELETAR
-- ========================================

-- Ver quantos subordinados um usuário tem antes de desativar/deletar
SELECT 
    COUNT(*) AS total_subordinados,
    GROUP_CONCAT(name SEPARATOR ', ') AS nomes_subordinados
FROM adms_users 
WHERE immediate_supervisor_id = 5  -- ID do usuário que será removido
AND status = 1;

-- Se COUNT > 0, você DEVE redistribuir antes de desativar!


-- ========================================
-- 7️⃣ REMOVER SUPERVISOR (DEIXAR ÓRFÃOS)
-- ========================================

-- CUIDADO! Só use se realmente quiser deixar usuários sem supervisor

UPDATE adms_users 
SET immediate_supervisor_id = NULL
WHERE immediate_supervisor_id = 5;  -- ID do supervisor


-- ========================================
-- 8️⃣ DETECTAR LOOPS NA HIERARQUIA
-- ========================================

-- Encontrar possíveis loops (A → B → A)
SELECT 
    u1.id AS user_a_id,
    u1.name AS user_a,
    u1.immediate_supervisor_id AS aponta_para_b,
    u2.name AS user_b,
    u2.immediate_supervisor_id AS b_aponta_para
FROM adms_users u1
INNER JOIN adms_users u2 ON u1.immediate_supervisor_id = u2.id
WHERE u2.immediate_supervisor_id = u1.id
AND u1.status = 1 
AND u2.status = 1;

-- Se retornar algum resultado, você tem um LOOP!


-- ========================================
-- 9️⃣ REORGANIZAR DEPARTAMENTO INTEIRO
-- ========================================

-- EXEMPLO: Todos do departamento "Comercial" (ID 2) agora reportam para gerente ID 10

-- PASSO 1: Ver usuários do departamento
SELECT 
    id,
    name,
    immediate_supervisor_id,
    user_department_id
FROM adms_users
WHERE user_department_id = 2
AND status = 1;

-- PASSO 2: Definir novo gerente para todos (exceto o próprio gerente)
UPDATE adms_users 
SET immediate_supervisor_id = 10  -- ID do novo gerente
WHERE user_department_id = 2 
AND id != 10                      -- Não atualizar o próprio gerente
AND status = 1;


-- ========================================
-- 🔟 RESETAR HIERARQUIA COMPLETA
-- ========================================

-- ⚠️ CUIDADO! Isso REMOVE toda a hierarquia!
-- Use apenas em testes ou para reconfigurar do zero

UPDATE adms_users 
SET immediate_supervisor_id = NULL;


-- ========================================
-- 1️⃣1️⃣ RELATÓRIO DE TAMANHO DAS EQUIPES
-- ========================================

-- Ver quantos subordinados cada gerente tem (recursivo)
SELECT 
    supervisor.id,
    supervisor.name AS gerente,
    COUNT(DISTINCT subordinado.id) AS total_equipe
FROM adms_users supervisor
LEFT JOIN adms_users subordinado ON subordinado.immediate_supervisor_id = supervisor.id
WHERE supervisor.status = 1
GROUP BY supervisor.id, supervisor.name
HAVING total_equipe > 0
ORDER BY total_equipe DESC;


-- ========================================
-- 1️⃣2️⃣ BACKUP DA HIERARQUIA ATUAL
-- ========================================

-- Criar backup antes de fazer mudanças
CREATE TABLE hierarchy_backup_20251030 AS
SELECT 
    id,
    name,
    immediate_supervisor_id,
    user_department_id,
    NOW() AS backup_date
FROM adms_users
WHERE status = 1;

-- Restaurar backup (se necessário)
-- UPDATE adms_users u
-- INNER JOIN hierarchy_backup_20251030 b ON u.id = b.id
-- SET u.immediate_supervisor_id = b.immediate_supervisor_id;


-- ========================================
-- 📊 EXEMPLOS PRÁTICOS
-- ========================================

-- CENÁRIO 1: João foi demitido, Maria assume a equipe
-- --------------------------------------------------------
-- 1. Ver equipe do João
SELECT id, name FROM adms_users WHERE immediate_supervisor_id = 5;

-- 2. Transferir para Maria
UPDATE adms_users SET immediate_supervisor_id = 8 WHERE immediate_supervisor_id = 5;

-- 3. Desativar João
UPDATE adms_users SET status = 0, immediate_supervisor_id = NULL WHERE id = 5;


-- CENÁRIO 2: Carlos foi promovido, equipe sobe para diretor
-- --------------------------------------------------------
-- 1. Ver supervisor do Carlos
SELECT immediate_supervisor_id FROM adms_users WHERE id = 10;

-- 2. Promover equipe (vai para o diretor)
UPDATE adms_users 
SET immediate_supervisor_id = (SELECT immediate_supervisor_id FROM adms_users WHERE id = 10)
WHERE immediate_supervisor_id = 10;


-- CENÁRIO 3: Novo gerente assume departamento inteiro
-- --------------------------------------------------------
-- 1. Contratar/ativar novo gerente
INSERT INTO adms_users (name, ..., user_department_id, immediate_supervisor_id) 
VALUES ('Novo Gerente', ..., 2, NULL);

-- 2. Pegar ID do novo gerente
SET @novo_gerente_id = LAST_INSERT_ID();

-- 3. Transferir todos do departamento
UPDATE adms_users 
SET immediate_supervisor_id = @novo_gerente_id
WHERE user_department_id = 2 
AND id != @novo_gerente_id;


-- ========================================
-- ✅ VALIDAÇÕES ANTES DE SALVAR
-- ========================================

-- Validar: Não criar loop (A → B → A)
SELECT 
    CASE 
        WHEN EXISTS (
            SELECT 1 FROM adms_users u1
            INNER JOIN adms_users u2 ON u1.immediate_supervisor_id = u2.id
            WHERE u2.immediate_supervisor_id = u1.id
        ) THEN 'ERRO: Loop detectado!'
        ELSE 'OK: Sem loops'
    END AS validacao_loop;

-- Validar: Supervisor existe e está ativo
SELECT 
    CASE 
        WHEN NOT EXISTS (
            SELECT 1 FROM adms_users 
            WHERE id = 999  -- ID do supervisor a verificar
            AND status = 1
        ) THEN 'ERRO: Supervisor não existe ou está inativo'
        ELSE 'OK: Supervisor válido'
    END AS validacao_supervisor;

