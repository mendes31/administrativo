-- Script SQL para adicionar campos CPF e Celular na tabela adms_users
-- Execute este script diretamente no banco de dados se a migration não funcionar

-- Verificar se a tabela existe
SELECT 'Verificando tabela adms_users...' as status;

-- Adicionar coluna CPF se não existir
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'adms_users' 
     AND COLUMN_NAME = 'cpf') = 0,
    'ALTER TABLE adms_users ADD COLUMN cpf VARCHAR(14) NULL COMMENT "CPF do usuário no formato 000.000.000-00" AFTER username',
    'SELECT "Coluna cpf já existe" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar coluna Celular se não existir
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'adms_users' 
     AND COLUMN_NAME = 'celular') = 0,
    'ALTER TABLE adms_users ADD COLUMN celular VARCHAR(20) NULL COMMENT "Celular do usuário, ex: (00) 00000-0000" AFTER cpf',
    'SELECT "Coluna celular já existe" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar índice único para CPF se não existir
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'adms_users' 
     AND INDEX_NAME = 'idx_adms_users_cpf_unique') = 0,
    'ALTER TABLE adms_users ADD UNIQUE INDEX idx_adms_users_cpf_unique (cpf)',
    'SELECT "Índice idx_adms_users_cpf_unique já existe" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar se as colunas foram criadas
SELECT 'Verificando colunas criadas...' as status;
SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, IS_NULLABLE, COLUMN_COMMENT 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'adms_users' 
AND COLUMN_NAME IN ('cpf', 'celular')
ORDER BY ORDINAL_POSITION;
