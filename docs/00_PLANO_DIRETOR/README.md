# Plano Diretor do Sistema Administrativo

## Propósito

Este é o índice mestre de governança da plataforma administrativa. Ele orienta
decisões e roadmaps sem substituir código, migrations, manual de ajuda ou
especificações de cada domínio.

O objetivo é preservar o que funciona, corrigir riscos estruturais e evoluir
cada domínio de forma incremental até um padrão profissional.

## Estrutura

1. [Princípios arquiteturais](../01_PRINCIPIOS/PRINCIPIOS_ARQUITETURAIS.md)
2. [Glossário corporativo](../02_GLOSSARIO/GLOSSARIO_CORPORATIVO.md)
3. [Mapa de domínios](../03_DOMINIOS/MAPA_DOMINIOS.md)
4. [Catálogo de capacidades](../03_DOMINIOS/CATALOGO_CAPACIDADES.md)
5. [Modelo de identidade](../04_IDENTIDADE/MODELO_IDENTIDADE.md)
6. [Modelo de autorização](../05_AUTORIZACAO/MODELO_AUTORIZACAO.md)
7. [Fontes de verdade](../06_FONTES_VERDADE/FONTES_VERDADE.md)
8. [Eventos e auditoria](../07_EVENTOS/EVENTOS_AUDITORIA.md)
9. [ADRs](../08_ADR/README.md)
10. [Domínios e roadmaps](../09_DOMINIOS/README.md)
11. [Templates](../templates/README.md)

## Fluxo de governança

```text
Ideia ou solicitação
  -> análise do domínio
  -> classificação: essencial, recomendada ou avançada
  -> gate arquitetural
  -> ADR, quando necessário
  -> roadmap do domínio
  -> implementação
  -> testes
  -> manual
  -> aceite
```

## Gate arquitetural

Uma funcionalidade relevante somente entra no roadmap quando possuir:

- domínio responsável;
- problema, valor para o negócio e usuários beneficiados;
- fonte de verdade;
- ações, papéis e escopos de autorização;
- eventos, auditoria e notificações;
- classificação LGPD e controles de segurança;
- integrações e consumidores;
- estratégia de migração e compatibilidade;
- critérios de testes;
- impacto no manual e documentação.

Mudanças em limites de domínio, fontes de verdade, identidade, autorização,
contratos de integração ou padrões transversais exigem ADR.

## Fast Track

Incidentes críticos de segurança, privacidade, integridade, obrigação legal ou
indisponibilidade não aguardam o fluxo completo:

```text
Problema crítico
  -> contenção e implementação imediata
  -> teste de regressão
  -> ADR, se aplicável
  -> atualização da documentação
```

A via rápida reduz o risco imediato, mas não elimina rastreabilidade.

## Classificação de capacidades

- **Essencial:** necessária para conformidade, segurança, integridade ou
  funcionamento correto do processo;
- **Recomendada:** aumenta eficiência, controle ou experiência após a base;
- **Avançada:** depende de dados maduros, automação ou capacidade operacional
  adicional.

Recursos observados em ferramentas de mercado são referências, não backlog
automático. A classificação depende de aderência ao processo da Tiaraju.

## Fontes em caso de divergência

Para determinar o estado atual:

1. código executável e schema efetivamente aplicado;
2. migrations e seeds versionados;
3. scripts e workflows automatizados;
4. manual contextual e seus mapas;
5. especificações declaradas como vigentes;
6. documentos datados de status, diagnóstico ou proposta.

Para determinar a direção desejada:

1. decisões aprovadas em ADR;
2. este Plano Diretor;
3. documento funcional e roadmap vigente do domínio.

Em conflito entre documentos, prevalece o código em produção até que a
documentação seja corrigida — salvo decisão explícita em ADR.

## Manual de ajuda e termos

O **manual contextual (F1)** é a documentação operacional para o utilizador final.
Complementa o [Glossário corporativo](../02_GLOSSARIO/GLOSSARIO_CORPORATIVO.md)
(canónico para linguagem de domínio) sem o substituir.

Padrão obrigatório em cada tópico HTML (`docs/manual/`) — **páginas novas e
alteradas devem sair completas**; não entregar feature só com esqueleto:

1. **`<h2>Função no sistema</h2>`** — papel da tela no fluxo.
2. **`<h2>O que significam os termos</h2>`** — quando houver siglas/jargão
   (eNPS, Pulse, OKR, PDI, Nine Box, HiPo, EPI, LGPD, etc.); não criar secção vazia.
3. **Quem acessa** — permissões (`<em>Controller</em>`).
4. **`<h2>Fluxo principal</h2>`** (ou equivalente) — passo a passo.
5. **`<h2>Campos e parâmetros</h2>`** (ou Campos e telas) — filtros, formulário, ações.
6. **`<h2>Problemas comuns</h2>`** — erros frequentes e o que verificar.

Canónico operacional: [checklist do manual](../manual/README.md).
Auditoria local: `php scripts/manual_structure_audit.php`.

Manutenção em lote (respeitando o limite de **100 ficheiros/push**):

- `php scripts/manual_ensure_funcao_section.php`
- `php scripts/manual_ensure_termos_section.php`
- `php scripts/manual_ensure_structure_sections.php` (`--dry-run`, `--only=`, `--limit=`)

Novas features só entram em aceite com manual alinhado a este padrão.

## Deploy e dados de produção

O deploy **nunca** deve apagar ou sobrescrever uploads, anexos, `.env` ou
storage privado. Política operacional e lista de exclusões:

- [Deploy em produção](../DEPLOY_PRODUCAO.md)

**Commits / pushes para `dev-master` ou `main`:** no máximo **100 ficheiros
alterados por push**. Acima disso o pipeline abandona o deploy rápido (>150
ficheiros) e, se o FTP incremental falhar, o fallback **lftp** pode reenviar
grande parte do repositório e demorar cerca de **1 hora**. Preferir vários
commits/pushes pequenos (código separado de lote documental, manuais em
fatias). Detalhe operacional em [DEPLOY_PRODUCAO.md](../DEPLOY_PRODUCAO.md).

Princípios 19 e 20 em
[Princípios arquiteturais](../01_PRINCIPIOS/PRINCIPIOS_ARQUITETURAIS.md).

## Artefatos obrigatórios por domínio

Cada domínio utiliza:

1. `01_Executivo.md`
2. `02_Funcional.md`
3. `03_Tecnico.md`
4. `04_Roadmap.md`
5. `05_Testes.md`
6. `06_ADRs.md`

As regras não devem ser copiadas entre documentos. O funcional é canônico para
regras de negócio; o técnico o referencia; o executivo o resume.

## Estado

- Fase: -1 — decisões arquiteturais mínimas;
- status: em construção;
- início: 19/07/2026;
- primeira aplicação: Gestão de Pessoas.
