# 🚀 Executar Migrations Pendentes

## ✅ Situação Atual

- ✅ Tabela `adms_strategic_plans` existe
- ✅ Migrations corrigidas
- ⏳ 14 migrations pendentes

## 🎯 Comando para Executar

Execute todas as migrations pendentes:

```bash
php vendor/bin/phinx migrate -c database/phinx.php -e production
```

## 📋 Migrations Pendentes (em ordem)

1. `20250725181010` - AddAuditFieldsToLgpdConsentimentos
2. `20250725181020` - AddAdmsUserIdToLgpdConsentimentos
3. `20250725181030` - AddLgpdTermoIdToLgpdConsentimentos
4. `20250725181040` - CreateLgpdConsentimentoArquivos
5. `20251204010000` - CreateEmploymentHistoryTable
6. `20251205000000` - AddPotentialScoreToPerformanceReviews
7. `20251206000000` - AddTwoStageApprovalToEmployeeRequests (corrigida)
8. `20251206010000` - CreateRequestTypesTable
9. `20251208000000` - CreateMeetingRoomsTables
10. `20251208000001` - AddBookingRequestFieldsToRequestTypes
11. `20260128120000` - AddLgpdConsentToAdmsUsers
12. `20260128121500` - CreateLgpdTermos
13. `20260128122000` - AddVersaoTermoToLgpdConsentimentos
14. `20260128130000` - AddPerformanceIndexesTraining

## ⚠️ Se Alguma Migration Falhar

Se alguma migration falhar, você pode:

1. **Ver o erro específico** e me informar
2. **Pular temporariamente** a migration problemática:
   ```bash
   # Marcar como executada manualmente (se necessário)
   # No phpMyAdmin, execute:
   INSERT INTO phinxlog (version, migration_name, start_time, end_time, breakpoint)
   VALUES ('VERSION_AQUI', 'NomeDaMigration', NOW(), NOW(), 0)
   ON DUPLICATE KEY UPDATE end_time = NOW();
   ```

## ✅ Verificar Status Após Executar

```bash
php vendor/bin/phinx status -c database/phinx.php -e production
```

Todas devem estar `up` após a execução bem-sucedida.

