# Solução: Página Training KPI Dashboard em Branco na Produção

## Problema Identificado

A página `training-kpi-dashboard` funciona localmente mas fica em branco na produção devido a um problema de **case-sensitivity** (diferença entre maiúsculas e minúsculas) no nome do arquivo.

### Causa Raiz

1. **URL no sistema**: `training-kpi-dashboard`
2. **Conversão do slug**: O sistema converte para `TrainingKpiDashboard` (via `SlugController`)
3. **Nome do arquivo atual**: `TrainingKPIDashboard.php` (com "KPI" em maiúsculas)
4. **Nome esperado pelo sistema**: `TrainingKpiDashboard.php` (com "Kpi" em camelCase)

**No Windows (local)**: O sistema de arquivos não é case-sensitive, então `TrainingKPIDashboard.php` é encontrado mesmo quando o sistema procura por `TrainingKpiDashboard.php`.

**No Linux (produção)**: O sistema de arquivos é case-sensitive, então quando o autoloader procura por `TrainingKpiDashboard.php`, ele não encontra `TrainingKPIDashboard.php`, resultando em uma página em branco.

## Solução Aplicada Localmente

O arquivo foi renomeado de:
- `app/adms/Controllers/trainings/TrainingKPIDashboard.php`

Para:
- `app/adms/Controllers/trainings/TrainingKpiDashboard.php`

E o autoloader do Composer foi regenerado.

## O Que Fazer na Produção

Execute os seguintes comandos no servidor de produção:

```bash
# 1. Acessar o diretório do projeto
cd /caminho/para/administrativo

# 2. Renomear o arquivo
mv app/adms/Controllers/trainings/TrainingKPIDashboard.php app/adms/Controllers/trainings/TrainingKpiDashboard.php

# 3. Regenerar o autoloader do Composer
composer dump-autoload
```

## Verificação

Após aplicar a correção, verifique se a página está funcionando:

1. Acesse: `https://seudominio.com.br/administrativo/training-kpi-dashboard`
2. A página deve carregar normalmente com os gráficos e KPIs

## Observações Importantes

- O nome da classe já estava correto (`TrainingKpiDashboard`), apenas o nome do arquivo precisava ser ajustado
- Todas as referências no código já usam o nome correto (`TrainingKpiDashboard`)
- O padrão de URL (`training-kpi-dashboard`) é mantido conforme solicitado
- O sistema converte automaticamente a URL para o nome da classe usando o slug

## Prevenção Futura

Para evitar problemas similares no futuro:

1. **Sempre use camelCase** para nomes de arquivos de controllers quando a URL usa hífens
2. **Mantenha consistência** entre o nome do arquivo e o nome da classe
3. **Teste em ambiente Linux** antes de fazer deploy para produção

## Exemplo de Conversão de URL para Nome de Classe

- URL: `training-kpi-dashboard` → Classe: `TrainingKpiDashboard`
- URL: `user-profile` → Classe: `UserProfile`
- URL: `sobre-empresa` → Classe: `SobreEmpresa`

O sistema usa `SlugController::slugController()` para fazer essa conversão automaticamente.

