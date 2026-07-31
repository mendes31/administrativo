# ADR-0008 — Domínio TI / Acessos (mapa de sistemas e acessos)

- Status: Aprovado
- Data: 2026-07-30
- Responsável: Arquitetura / TI
- Módulos impactados: catálogo de sistemas, mapa de acessos, offboarding (consumo),
  cadastro de usuários (visão), ACL de páginas

## Contexto

No desligamento é necessário saber em quais sistemas e equipamentos (muitos
embarcados, fora da rede, com controle de usuário próprio) o colaborador ainda
possui conta. Não havia fonte de verdade para esse mapa. Inventário LGPD,
Estoque e ACL do Portal resolvem outros problemas e não devem absorver essa
capacidade.

## Decisão

1. Criar o domínio **TI / Acessos** com fonte de verdade em `ti_sistemas` e
   `ti_acessos`.
2. Sujeito do acesso = `adms_user_id` (conta operacional atual; alinhado ao
   ADR-0002). Evolução para Pessoa física fica condicionada ao Contract de
   identidade.
3. Offboarding (Gestão de Pessoas / Jornada) **consome** o mapa para listar e
   confirmar revogações; não é proprietário do dado.
4. Grupo ACL novo: **TI - Sistemas e Acessos**. Páginas nascem sem concessão em
   massa.
5. Limites explícitos: não fundir com inventário LGPD, Estoque (`inv_*`), SST
   equipamentos, ACL de páginas do Portal nem Patrimônio (hardware). Discovery
   de rede, se existir, será submódulo de apoio — não fonte única.

## Alternativas consideradas

- Aba/campo solto no usuário sem módulo próprio — sem auditoria nem visão por
  sistema.
- Colocar sob LGPD Inventory — semântica de dados pessoais, não de contas.
- Colocar sob Estoque ou Patrimônio — SKU/ativo físico ≠ conta em sistema.
- Só checklist textual no offboarding — já existe e não lista o quê inativar.

## Consequências

### Positivas

- mapa concreto para desligamento, inclusive sistemas offline;
- ownership e ACL claros;
- evolui para matriz/alertas sem retrabalho estrutural.

### Negativas e riscos

- valor depende do cadastro disciplinado pela TI;
- confirmação de inativação no equipamento continua manual;
- dois conceitos de “acesso” (Portal ACL × mapa TI) exigem clareza na UI.
