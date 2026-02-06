# 🆕 Começar do Zero Após Reorganização das Migrations

## ✅ Sim, você pode deletar tudo e começar do zero!

Agora que as sequências das migrations foram corrigidas, você pode limpar o banco completamente e executar todas as migrations na ordem correta.

## 🗑️ Opção 1: Limpar via Script PHP (Recomendado)

Execute no servidor:

```bash
php scripts/limpar_todas_tabelas.php
```

Este script:
- Limpa o `phinxlog`
- Deleta todas as tabelas automaticamente
- Desabilita/reativa foreign keys durante o processo

## 🗑️ Opção 2: Limpar via phpMyAdmin

### Passo 1: Limpar phinxlog
```sql
TRUNCATE TABLE phinxlog;
```

### Passo 2: Deletar todas as tabelas

No phpMyAdmin:
1. Vá em "Estrutura" do banco de dados
2. Marque todas as tabelas
3. No dropdown "Com marcados:", escolha "Remover"
4. Confirme a exclusão

**OU** execute este SQL (ajuste conforme suas tabelas):

```sql
SET FOREIGN_KEY_CHECKS = 0;

-- Lista de tabelas principais (adicione outras se necessário)
DROP TABLE IF EXISTS `lgpd_consentimento_arquivos`;
DROP TABLE IF EXISTS `lgpd_consentimentos`;
DROP TABLE IF EXISTS `lgpd_termos`;
DROP TABLE IF EXISTS `adms_strategic_plan_observations`;
DROP TABLE IF EXISTS `adms_strategic_plans`;
DROP TABLE IF EXISTS `adms_strategic_indicators`;
DROP TABLE IF EXISTS `adms_training_users`;
DROP TABLE IF EXISTS `adms_training_applications`;
DROP TABLE IF EXISTS `adms_trainings`;
DROP TABLE IF EXISTS `adms_users`;
DROP TABLE IF EXISTS `adms_departments`;
DROP TABLE IF EXISTS `adms_positions`;
DROP TABLE IF EXISTS `adms_access_levels`;
DROP TABLE IF EXISTS `adms_pages`;
-- ... (adicione outras conforme necessário)

SET FOREIGN_KEY_CHECKS = 1;
```

## 🚀 Passo 3: Executar Migrations do Zero

Após limpar o banco:

```bash
php vendor/bin/phinx migrate -c database/phinx.php -e production
```

Agora todas as migrations devem executar na ordem correta, sem erros de dependência!

## 🌱 Passo 4: Executar Seeds (Opcional)

Após as migrations, execute as seeds para popular dados iniciais:

```bash
php vendor/bin/phinx seed:run -c database/phinx.php -e production
```

## 📋 Passo 5: Importar Backup (Se Tiver)

Se você tem um backup de dados que quer importar:

```bash
mysql -h mysql.tiaraju.com.br -u tiaraju004_add1 -p tiaraju04 < backup.sql
```

**⚠️ IMPORTANTE:** 
- Importe o backup APÓS executar as migrations
- O backup deve conter apenas dados (INSERT), não estrutura (CREATE TABLE)

## ✅ Verificação Final

Verifique se tudo está correto:

```bash
# Ver status das migrations
php vendor/bin/phinx status -c database/phinx.php -e production

# Verificar tabelas criadas
mysql -h mysql.tiaraju.com.br -u tiaraju004_add1 -p tiaraju04 -e "SHOW TABLES;"
```

## 🎯 Vantagens de Começar do Zero

1. ✅ Garante que todas as migrations executem na ordem correta
2. ✅ Evita problemas de dependências
3. ✅ Testa a reorganização completa
4. ✅ Limpa qualquer inconsistência anterior
5. ✅ Cria todas as tabelas com a estrutura final correta

## ⚠️ Avisos Importantes

- **FAÇA BACKUP** antes de deletar tudo (se tiver dados importantes)
- Se você já tem dados em produção, considere importá-los depois
- As seeds são idempotentes (não duplicam dados), então pode executá-las mesmo após importar backup

---

## 📝 Checklist Completo

- [ ] Fazer backup dos dados (se necessário)
- [ ] Limpar phinxlog: `TRUNCATE TABLE phinxlog;`
- [ ] Deletar todas as tabelas
- [ ] Executar migrations: `php vendor/bin/phinx migrate -c database/phinx.php -e production`
- [ ] Executar seeds: `php vendor/bin/phinx seed:run -c database/phinx.php -e production`
- [ ] Importar backup (se tiver)
- [ ] Verificar status: `php vendor/bin/phinx status -c database/phinx.php -e production`

