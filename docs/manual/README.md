# Manual de ajuda contextual (F1)

Documentação exibida pelo sistema de ajuda nas telas do administrativo.

## Estrutura

| Caminho | Função |
|---------|--------|
| `content/**/*.html` | Tópicos de ajuda (um arquivo por tela ou conceito) |
| `manifest.json` | Índice de tópicos (id, título, arquivo) |
| `help-menu.json` | Menu lateral da central de ajuda |
| `page-topic-map.json` | Mapeia `controller_url` → `topic_id` (atalho F1 na tela) |

## Checklist obrigatório ao entregar funcionalidade

Sempre que criar ou alterar comportamento visível ao usuário (nova tela, novo botão, novo fluxo, nova permissão):

1. **Tópico da tela** — Atualizar ou criar `content/<modulo>/<topic-id>.html` em português, nesta ordem:
   - **`<h2>Função no sistema</h2>`** (obrigatório, logo após o `<h1>`) — resumo do papel da tela/função no fluxo do sistema, não só lista de campos;
   - **`<h2>O que significam os termos</h2>`** — obrigatório quando houver siglas/jargão; padrão de governança no [Plano Diretor](../00_PLANO_DIRETOR/README.md#manual-de-ajuda-e-termos) e catálogo em [Glossário corporativo](../02_GLOSSARIO/GLOSSARIO_CORPORATIVO.md);
   - permissões (`Quem acessa`);
   - fluxo passo a passo;
   - campos/ações relevantes;
   - problemas comuns relacionados à mudança.

   Scripts de manutenção (respeitar ≤100 ficheiros/push):
   `php scripts/manual_ensure_funcao_section.php` · `php scripts/manual_ensure_termos_section.php` (`--dry-run`, `--only=pasta`, `--limit=90`).

2. **Tópico conceitual** — Se a mudança afeta um fluxo maior (ex.: matrizes, versionamento), atualizar o HTML de visão geral do módulo (ex.: `rh-trein-matrizes.html`).

3. **`manifest.json`** — Conferir `id`, `title` e `file` do tópico (título em português claro).

4. **`help-menu.json`** — Incluir ou ajustar entrada no menu da central de ajuda, se a tela for acessível pelo menu de ajuda.

5. **`page-topic-map.json`** — Adicionar mapeamento `controller_url` → `topic_id` para telas novas (necessário para F1 contextual).

6. **Permissões novas** — Documentar o nome do controller da página (ex.: `DeleteCompletedTraining`) no tópico da tela que usa a permissão; páginas só de permissão podem não ter rota própria no mapa.

7. **Seeds/migrations de páginas** — Manter `database/seeds/AddAdmsPages.php` alinhado (nome e observação da página).

Não encerrar tarefa de feature sem revisar este checklist quando houver impacto em UX.

## Auditoria automática

Script que compara páginas do sistema (`AddAdmsPages.php` + migrations) com o manual:

```bash
php scripts/audit_manual_coverage.php
```

| Flag | Uso |
|------|-----|
| `--fix` | Gera HTML esqueleto onde possível e regenera `page-topic-map`, `manifest` e `help-menu` |
| `--strict` | Exit code 1 se houver pendências (CI/pre-commit) |
| `--json` | Saída estruturada para integração |
| `--changed` | Audita só slugs/controllers tocados no git (ignora backlog histórico) |
| `--base=origin/dev-master` | Com `--changed`, compara o branch com o ref informado (`--base=auto` detecta a branch padrão do remote) |

O que o auditor verifica:

- Slug da página ausente em `page-topic-map.json`
- Slug novo fora de `manual_aggregate_topic_map.php`
- Tópico sem arquivo HTML ou fora do `manifest.json`
- HTML ainda em modo esqueleto (texto genérico)
- Permissões (`EditCompletedTraining`, etc.) não citadas no tópico da tela pai

Slugs novos sem mapa agregado são listados em `docs/manual/pending-aggregate-slugs.json`.

**Recomendado:** rodar após cada feature e antes de deploy:

```bash
php scripts/manual_changed_report.php
php scripts/audit_manual_coverage.php --changed --strict
```

### Cursor (identificar o que atualizar)

O relatório `manual_changed_report.php` traduz o diff git em tópicos do manual:

```bash
php scripts/manual_changed_report.php              # working tree
php scripts/manual_changed_report.php --staged     # só git add
php scripts/manual_changed_report.php --base=origin/dev-master
php scripts/manual_changed_report.php --base=auto
php scripts/manual_changed_report.php --json       # saída para o agente
```

Regras em `.cursor/rules/` (`atualizar-manual-ajuda.mdc`, `manual-detectar-alteracoes.mdc`) orientam o agente a rodar esse relatório ao alterar Controllers/Views.

Para ver o backlog completo do sistema (centenas de telas legadas):

```bash
php scripts/audit_manual_coverage.php
```

### Hook git (opcional)

Bloqueia commit quando controller/view/migration de página mudou sem manual alinhado:

```bash
git config core.hooksPath scripts/git-hooks
```

O hook roda `audit_manual_coverage.php --changed --strict` apenas se houver arquivos relevantes no commit.

### CI (pull request)

```yaml
- run: php scripts/audit_manual_coverage.php --changed --strict --base=origin/dev-master
```

Assim o pipeline só falha por documentação faltando **no que o PR alterou**, não pelo histórico inteiro.

## Geração automática de esqueletos

`scripts/generate_manual_module_docs.php` gera HTML inicial para alguns módulos. Conteúdo gerado deve ser **revisado e expandido** em português antes de considerar a documentação concluída.

## Convenções de redação

- Títulos e textos em **português** (nomes de permissão/controller podem permanecer em inglês, como no código).
- Usar `<em>NomeDoController</em>` para permissões.
- Usar `<code>topic-id</code>` para referência cruzada entre tópicos.
- Incluir seção **Problemas comuns** com sintomas reais reportados pelos usuários.
