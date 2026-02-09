# Atualização Automática de Status de Treinamentos

## Visão Geral

O sistema possui um serviço automático que atualiza os status dinâmicos de treinamentos (`tu.status` no banco de dados) quando o primeiro usuário acessa páginas relevantes do módulo de treinamentos.

## Como Funciona

### Serviço: `TrainingStatusUpdaterService`

O serviço `TrainingStatusUpdaterService` garante que os status dinâmicos estejam sempre atualizados, mas sem sobrecarregar o servidor:

1. **Verificação de Intervalo**: Verifica se já foi executado recentemente (intervalo padrão: 15 minutos)
2. **Execução Condicional**: 
   - Se nunca foi executado → executa imediatamente
   - Se passou mais de 15 minutos desde a última execução → executa novamente
   - Se ainda está dentro do intervalo → não executa (evita processamento desnecessário)
3. **Persistência**: Salva a última execução em `storage/cache/system/training_status_last_run.json`

### Onde é Executado Automaticamente

O serviço é chamado automaticamente nas seguintes páginas:

1. **Dashboard de KPIs** (`TrainingKpiDashboard::index()`)
   - Quando qualquer usuário acessa o dashboard de KPIs de treinamentos
   - Garante que os indicadores estejam sempre atualizados

2. **Status de Treinamentos por Colaborador** (`ListTrainingStatus::index()`)
   - Quando qualquer usuário acessa a listagem de status de treinamentos
   - Garante que a listagem mostre status corretos

### Arquivo de Cache

O serviço cria e mantém um arquivo de cache em:
```
storage/cache/system/training_status_last_run.json
```

Conteúdo do arquivo:
```json
{
    "last_run": 1707234567,
    "updated": 1234,
    "datetime": "2024-02-06 10:30:00"
}
```

- `last_run`: Timestamp Unix da última execução
- `updated`: Quantidade de registros atualizados
- `datetime`: Data/hora legível da última execução

## Execução Manual

### Via Script CLI

Para forçar uma atualização manual (útil para testes ou manutenção):

```bash
php scripts/update_training_statuses.php
```

Este script força a atualização ignorando o intervalo mínimo.

### Via Código PHP

```php
use App\adms\Models\Services\TrainingStatusUpdaterService;

// Executar com intervalo padrão (15 minutos)
TrainingStatusUpdaterService::ensureUpdated();

// Forçar execução imediata (ignorar intervalo)
TrainingStatusUpdaterService::ensureUpdated(true);

// Executar com intervalo customizado (ex: 5 minutos = 300 segundos)
TrainingStatusUpdaterService::ensureUpdated(false, 300);
```

## Agendamento via Cron (Opcional)

Para garantir atualizações regulares mesmo sem acesso de usuários, você pode configurar um cron job:

```bash
# Executar a cada hora
0 * * * * php /caminho/para/administrativo/scripts/update_training_statuses.php >> /caminho/para/logs/update_training_statuses.log 2>&1

# Executar diariamente às 3h da manhã
0 3 * * * php /caminho/para/administrativo/scripts/update_training_statuses.php >> /caminho/para/logs/update_training_statuses.log 2>&1
```

## Benefícios

1. **Performance**: Evita recalcular status em toda requisição
2. **Atualização Automática**: Status sempre atualizados quando usuários acessam
3. **Eficiência**: Intervalo de 15 minutos evita processamento desnecessário
4. **Transparente**: Funciona automaticamente, sem necessidade de configuração manual
5. **Resiliente**: Se houver erro, não quebra a requisição do usuário (apenas loga o erro)

## Monitoramento

Para verificar quando foi a última atualização:

```bash
# Ver conteúdo do arquivo de cache
cat storage/cache/system/training_status_last_run.json

# Ver logs de erro (se houver)
tail -f app/logs/error.log | grep TrainingStatusUpdaterService
```

## Configuração do Intervalo

O intervalo padrão é de **15 minutos** (900 segundos). Para alterar:

1. **Globalmente**: Edite a constante `DEFAULT_INTERVAL_SECONDS` em `TrainingStatusUpdaterService.php`
2. **Por chamada**: Passe o parâmetro `$minIntervalSeconds` ao chamar `ensureUpdated()`

## Troubleshooting

### Status não estão atualizando

1. Verifique se o arquivo de cache existe e tem permissões de escrita
2. Verifique os logs de erro: `app/logs/error.log`
3. Execute manualmente: `php scripts/update_training_statuses.php`
4. Verifique se o diretório `storage/cache/system/` existe e tem permissões

### Performance lenta

Se a atualização estiver demorando muito:
- Considere aumentar o intervalo padrão (ex: 30 minutos)
- Verifique se há índices adequados no banco de dados
- Monitore o tempo de execução do método `updateDynamicStatuses()`

