# TI / Acessos — Visão executiva

## Objetivo

Manter um mapa auditável de quais colaboradores possuem conta ou login em
sistemas e equipamentos (incluindo embarcados fora da rede), para que no
desligamento a TI/RH saiba exatamente o que inativar.

## Situação atual

- Não existia catálogo de sistemas externos ao Portal Administrativo.
- Offboarding possui item genérico `revogar_acessos` sem lista concreta.
- Inventário LGPD cobre dados pessoais; Estoque cobre SKUs; SST cobre
  equipamentos de segurança — nenhum substitui este mapa.
- ACL de páginas do Portal não cobre contas em CLP, IHM, NVR, SAP etc.

## Benefícios esperados

- checklist de desligamento com itens concretos por sistema/equipamento;
- redução de contas órfãs em sistemas locais e embarcados;
- visão colaborador → sistemas e sistema → usuários;
- base para revisões e matriz por cargo em fases posteriores.

## Capacidades

Ver [Catálogo de capacidades](../../03_DOMINIOS/CATALOGO_CAPACIDADES.md)
(secção TI / Acessos).

## Prioridades

1. catálogo de sistemas e vínculos ativos (essencial);
2. integração com offboarding (essencial);
3. matriz cargo × sistema e alertas (recomendada);
4. discovery de rede e sync de diretórios (avançada).

## Indicadores

| Indicador | Fórmula | Fonte | Periodicidade | Responsável |
|---|---|---|---|---|
| Cobertura no desligamento | % offboardings concluídos com zero acessos TI ativos | `ti_acessos` + `rh_offboarding_*` | Mensal | TI |
| Contas órfãs detectadas | Acessos ativos de usuários inativos | `ti_acessos` × `adms_users` | Mensal | TI |

## Roadmap resumido

- Fase 1: catálogo + mapa + offboarding.
- Fase 2: matriz por cargo, solicitações, alertas de revisão.
- Fase 3: discovery e integrações automáticas.
