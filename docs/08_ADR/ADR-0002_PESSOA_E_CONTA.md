# ADR-0002 — Separar conceitualmente Pessoa e Conta

- Status: Proposto
- Data: 2026-07-19
- Responsável: Arquitetura, Administração e Gestão de Pessoas
- Módulos impactados: todos os consumidores de `adms_users`

## Contexto

É regra de negócio válida que todo colaborador ativo do Portal possua conta de
acesso, pois comunicações, documentos e solicitações exigem autenticação. Essa
relação será preservada.

O problema não é a existência da conta, e sim `adms_users` representar, na mesma
linha, conta, pessoa, colaborador, vínculo, lotação e ator. Essa concentração
dificulta histórico, autorização, recontratações, privacidade e integrações.

A quantidade de referências atuais impede substituição direta segura.

## Decisão

Adotar os conceitos separados de Pessoa, Conta, Vínculo e Lotação.

A decisão sobre implementação permanece condicionada ao inventário de FKs,
modelo organizacional, testes e plano Expand/Contract. `adms_users` será
preservada como fachada durante eventual migração.

## Alternativas consideradas

- continuar ampliando `adms_users`;
- substituir a tabela de uma vez;
- separar somente credenciais.

## Consequências

### Positivas

- linguagem e ownership claros;
- histórico organizacional confiável;
- pessoa sem conta e conta técnica sem colaborador fictício;
- autorização e analytics baseados em relações vigentes.

### Negativas e riscos

- coexistência temporária;
- backfill e reconciliação;
- adapters e possíveis dual writes;
- necessidade de catalogar todas as referências;
- risco de divergência durante a transição.
