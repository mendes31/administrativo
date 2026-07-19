# ADR-0001 — Manter monólito modular

- Status: Aprovado
- Data: 2026-07-19
- Responsável: Arquitetura do Sistema Administrativo
- Módulos impactados: todos

## Contexto

O sistema é uma aplicação PHP única. Roteamento, páginas, menus, ACL, banco e
deploy possuem dependências compartilhadas. Os limites de domínio ainda estão
em consolidação.

Separar aplicações agora aumentaria custos de identidade, autorização,
transações, integração e operação sem eliminar o acoplamento existente.

## Decisão

Manter o sistema como monólito modular e evoluí-lo incrementalmente.

Domínios terão responsabilidades, contratos, fontes, policies e roadmaps
próprios. Não haverá mudança ampla de namespaces sem benefício mensurável.

## Alternativas consideradas

- reescrita total;
- microserviços ou aplicações por domínio;
- continuar o crescimento sem limites formais.

## Consequências

### Positivas

- preserva funcionalidades estáveis;
- reduz risco e tempo sem entrega;
- permite transações locais;
- viabiliza migração gradual.

### Negativas e riscos

- banco e deploy continuam compartilhados;
- fronteiras dependem de disciplina e testes;
- extração futura ainda exigirá trabalho;
- acoplamentos atuais precisam ser catalogados e reduzidos.
