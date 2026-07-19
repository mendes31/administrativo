# ADR-0004 — Escopo de listagem ATS via permissão ViewAll (Expand/Contract)

- Status: Aprovado
- Data: 2026-07-19
- Responsável: Arquitetura / Gestão de Pessoas (Talentos)
- Módulos impactados: vagas, entrevistas, candidatos, ACL (`adms_pages`)

## Contexto

Operadores com ACL de listagem viam todos os registros. Era necessário introduzir
escopo por relação (responsável / relacionados) sem quebrar produção no dia do
deploy.

## Decisão

1. Criar permissões técnicas `RhVagasViewAll`, `RhEntrevistasViewAll` e
   `RhCandidatosViewAll`.
2. Migrations concedem ViewAll a quem já possui a página de listagem
   correspondente (comportamento preservado).
3. Sem ViewAll, a listagem filtra por relação (`responsible` / `related`).
4. Autorização por objeto de candidato usa o mesmo critério ViewAll (Expand).
5. Contract futuro: retirar ViewAll seletivamente dos perfis restritos.

## Alternativas consideradas

- Filtrar imediatamente por responsável (quebra usuários RH no deploy).
- Escopo só na UI sem policy de objeto (falso senso de segurança).
- Papéis novos no banco sem permissão técnica reutilizável.

## Consequências

### Positivas

- deploy seguro (Expand);
- Contract é só revogar grants;
- listagem e ficha de candidato compartilham a mesma chave ViewAll.

### Negativas e riscos

- gestores CRM ainda acessam fichas sem filtro de área (lacuna conhecida);
- Contract exige decisão de negócio por perfil antes de revogar ViewAll;
- páginas ViewAll não têm controller físico (só ACL).
