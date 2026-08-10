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
   - `agendar` / `reservar` — fluxo guiado (sala numerada com foto → data → todos os horários do dia; ocupados com nome e departamento → título)
   - `salas` / `agenda da sala [nome] hoje` / `minhas reservas`
   - `cancelar reserva #12`
   - No fluxo, vários horários: `3,4,5` ou `3-5` (blocos seguidos = 1 reserva)
   - `quantos usuários bloqueados?`
   - `bloqueados sem desligamento` / `bloqueados mas não desligados`
   - `quantos desligados?`
   - `lista de desligados` / `lista de desligados em janeiro` / `lista desligados Produção`
   - `itens` / nome do relatório marcado para o chat
   - depois do relatório, digite só o código (`1000001`, CardCode, DocNum…) para filtrar item/parceiro/documento
   - ou troque de domínio na mesma frase: `item 43000001`, `parceiro C00001` (não fica preso no relatório anterior)
   - o filtro consulta o SAP/SQL do relatório (não só as 15 linhas exibidas no chat)
   - `limpar` / `nova consulta` — zera contexto (lista de pessoas e último relatório)

   - `desligados por departamento` / `desligados por departamento 2025`
   - `departamento do Rafael` / `Wladimir está bloqueado?` / `quantos anos de empresa o Wladimir possui?`
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

Menu: **Administração → Configurações → Assistente MCP** (`mcp-api-config`), aba **Tools** (URL legada `list-mcp-chat-tools` redireciona).

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

## API ERP externa (ex.: vendas `/api/sales`)

O Portal, no modo URL externa, envia:

```json
{ "message": "texto da pergunta" }
```

Algumas APIs de ERP/MCP usam outro contrato, por exemplo:

- URL completa: `http://192.168.1.118:5153/api/sales`
- Corpo: `{ "Message": "Me traga as vendas entre …" }` (chave **Message** com M maiúsculo)

Isso **não** é drop-in na tela Assistente MCP. Para usar:

1. Rede: o PHP/WAMP precisa alcançar o host/porta do ERP.
2. Adaptador no Portal (ou proxy na frente do ERP) que:
   - converta `message` → `Message` (ou aceite as duas);
   - use o path correto (`…/api/sales` se a base sozinha não bastar);
   - normalize a resposta para o formato que o chat espera (`resposta` / JSON com reply).
3. Decidir o modelo de uso: **substituir** o MCP atual (só vendas) ou **rotear** por domínio (RH/salas em `local:internal`, vendas no ERP).

Sem esse adaptador, colar só a URL na config tende a falhar (404/400 por path ou por chave do JSON).

## Próximo passo (depois do piloto)

- Ligar Claude/OpenAI **API** (créditos) no servidor de agente MCP.
- Ampliar tools (treinamentos, LNT) e depois SAP B1 com ACL.
