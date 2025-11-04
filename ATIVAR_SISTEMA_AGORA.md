# 🚀 ATIVAR SISTEMA - EXECUTE AGORA!

## ⚡ 5 COMANDOS PARA ATIVAR TUDO

### 1️⃣ Executar Migration (Criar Tabelas)
```bash
cd C:\wamp64\www\administrativo
vendor\bin\phinx migrate -c database/phinx.php
```

### 2️⃣ Atualizar Autoload
```bash
composer dump-autoload
```

### 3️⃣ Adicionar Rotas
Editar: `routes/LoadPageAdm.php`

Localizar array `$listPgPrivate` e adicionar após linha 123:
```php
"DynamicReportBuilder", "ListDynamicReports", "ViewDynamicReport", 
"SaveDynamicReport", "ExecuteDynamicReport",
```

### 4️⃣ Adicionar no Menu (OPCIONAL - pode fazer depois)
Editar: `app/adms/Views/partials/menu.php`

Adicionar antes da linha final `];`:
```php
,
[
    'id' => 'relatorios',
    'icon' => 'fa-solid fa-chart-bar',
    'label' => 'Relatórios Dinâmicos',
    'submenu' => [
        [
            'label' => 'Meus Relatórios',
            'url' => $_ENV['URL_ADM'] . 'list-dynamic-reports',
            'permission' => 'ListDynamicReports'
        ],
        [
            'label' => 'Criar Relatório',
            'url' => $_ENV['URL_ADM'] . 'dynamic-report-builder',
            'permission' => 'DynamicReportBuilder'
        ]
    ]
]
```

### 5️⃣ Testar!
```
http://localhost/administrativo/list-dynamic-reports
```

---

## 🔷 CONFIGURAR SAP B1 (SE TIVER)

### 1. Adicionar no .env:
```env
SAP_B1_HOST=172.16.0.100
SAP_B1_PORT=30015
SAP_B1_DATABASE=SBODEMOUSA
SAP_B1_USER=SYSTEM
SAP_B1_PASSWORD=SuaSenha
```

### 2. Testar conexão:
```bash
php scripts/test_sap_b1_connection.php
```

Se funcionar: ✅ Pronto para consultar SAP B1!

---

## ✅ CHECKLIST

- [ ] Migration executada
- [ ] Autoload atualizado
- [ ] Rotas adicionadas
- [ ] Menu adicionado (opcional)
- [ ] Testou acesso: `list-dynamic-reports`
- [ ] SAP B1 configurado (se tiver)

---

## 🎯 PRONTO!

Tudo foi recuperado e está funcional! 🎉

**Próximo passo:** Acessar o sistema e criar seu primeiro relatório!

