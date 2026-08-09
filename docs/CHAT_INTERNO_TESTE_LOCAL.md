# Chat interno — teste local (homologação) sem SAP

## Respostas rápidas

| Pergunta | Resposta |
|----------|----------|
| Consigo testar na homologação local? | **Sim** (WAMP + banco `tiaraju04_homologacao` / o que estiver no `.env`). |
| Consigo IA free? | **Sim**: (1) modo `local:internal` **sem LLM** (regras + SQL do Portal); (2) **Ollama** no PC (gratuito); (3) tiers grátis de Gemini/Groq (API externa, limites). |
| Claude Pro do site? | **Não** para o chat do Portal — use API depois. Para o piloto local **não precisa**. |

## Ativar no Portal (UI)

1. Login com usuário que tenha permissão **McpChat**.
2. Administração → **Configuração da API MCP**.
3. URL: `local:internal`
4. Marque **Ativar** e salve.
5. Abra o **Tiarajuzinho** (ícone do robô no navbar) e pergunte, por exemplo:
   - `quantos colaboradores ativos?`
   - `quantos colaboradores inativos?`
   - `inativos em janeiro` (desligados no mês, não departamento)
   - `desligados 2025` / `inativos 2025` (série mensal do ano)
   - `quantos usuários bloqueados?`
   - `ativos na TI` (alias: tecnologia da informação)
   - `ativos em Produção`
   - `headcount por departamento`

O piloto **não** gera dashboard/gráfico nem SQL livre: só responde texto das tools do catálogo. Ollama (se ligado) só ajuda a escolher a intenção.

**Contexto curto:** depois de `ativos` ou `inativos`, pode digitar só o departamento (`financeiro`, `TI`), ou `por mês` / `por departamento`. A sessão guarda a última intenção. Ex.: `inativos` → `por mes` mostra desligamentos mensais do ano.

## Relatórios dinâmicos no chat (`report.run`)

1. Relatórios → editar um relatório no construtor.
2. Marque **Disponível no chat**, preencha tool / descrição / exemplos e salve.
3. Rode a migração se ainda não rodou: `php vendor/bin/phinx migrate -c database/phinx.php`
4. No Assistente MCP:
   - `quais relatórios no chat?`
   - `relatório [nome ou tool]`
   - ou uma das frases de exemplo cadastradas

Só entram relatórios com `chat_enabled` e que o utilizador possa ver (público / criador / partilha / acesso total).

## Tools do assistente (fase C)

Menu: **Administração → Tools do Assistente MCP** (`list-mcp-chat-tools`).

- Lista tools RH built-in.
- Ativa/desativa relatórios no chat e edita tool / descrição / exemplos sem abrir o construtor completo.
- Resumo analítico opcional via Ollama após `headcount por departamento` ou `report.run` (desligue com `OLLAMA_ANALYZE=0` no `.env`).

## Gráficos no chat (fase B)

O Assistente MCP renderiza:
- tabela HTML quando `data.rows` vem de `report.run`;
- gráfico Chart.js quando há `data.chart` (relatório com tipo barra/pizza/linha, ou `headcount por departamento`).
- barra **Baixar** sob o resultado: PNG do gráfico, Excel/CSV dos dados; em `report.run` também Excel/CSV/PDF completos (`export-dynamic-report-*`).

Peça no chat: `headcount por departamento` para ver o gráfico de ativos.

## Teste por linha de comando

```bash
php scripts/test_local_rh_chat.php
php scripts/test_local_rh_chat.php "quantos bloqueados?"
php scripts/test_local_rh_chat.php "quantos inativos?"
php scripts/test_local_rh_chat.php "ativos na TI"
php scripts/test_local_rh_chat.php "ativos em Produção"
```

## LLM (API ou Ollama)

Não precisa de n8n: o Portal chama a API diretamente.

Prioridade com `INTERNAL_CHAT_LLM=auto`: **OpenAI** → **Anthropic** → **Ollama**.

### OpenAI (ou compatível: Groq, Azure, etc.)

```env
INTERNAL_CHAT_LLM=auto
OPENAI_API_KEY=sk-...
OPENAI_BASE_URL=https://api.openai.com/v1
OPENAI_MODEL=gpt-4o-mini
INTERNAL_CHAT_ANALYZE=1
```

### Anthropic Claude

```env
INTERNAL_CHAT_LLM=anthropic
ANTHROPIC_API_KEY=sk-ant-...
ANTHROPIC_MODEL=claude-sonnet-4-20250514
```

### Ollama (opcional, gratuito no PC)

1. Instale: https://ollama.com  
2. `ollama pull llama3.2`  
3. No `.env`:

```env
OLLAMA_URL=http://127.0.0.1:11434
OLLAMA_MODEL=llama3.2
```

Perguntas fora das regras locais são roteadas pelo LLM; a execução continua tipada (tools PHP).

## Próximo passo (depois do piloto)

- Ligar Claude/OpenAI **API** (créditos) no servidor de agente MCP.
- Ampliar tools (treinamentos, LNT) e depois SAP B1 com ACL.
