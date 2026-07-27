# ADR-0002 — Separar conceitualmente Pessoa e Conta

- Status: Aceito com condicionantes (não priorizar Contract físico)
- Data: 2026-07-19 (atualizado 2026-07-27)
- Responsável: Arquitetura, Administração e Gestão de Pessoas
- Módulos impactados: todos os consumidores de `adms_users`

## Contexto

É regra de negócio válida que todo colaborador ativo do Portal possua conta de
acesso, pois comunicações, documentos e solicitações exigem autenticação. Na
Tiaraju a relação operacional é 1:1 (não existe colaborador de Portal sem conta).

O problema não é a existência da conta, e sim `adms_users` representar, na mesma
linha, conta, pessoa, colaborador, vínculo, lotação e ator. Essa concentração
dificulta histórico, autorização, recontratações, privacidade e integrações —
mas **não** exige fragmentar a tabela agora.

## Decisão

1. **Conceitos** Pessoa, Conta, Vínculo e Lotação permanecem no modelo mental e
   no Expand sombra (`rh_pessoas` / `rh_vinculos` / `rh_lotacoes`), com dual-write.
2. **Operação:** `adms_users` continua a fonte de verdade do colaborador/Portal.
3. **Contract físico** (remover colunas de identidade de `adms_users`, migrar FKs)
   **somente** quando existir requisito real que a fachada atual não atenda
   (terceiros, múltiplos vínculos simultâneos, etc.).
4. **Entrada de colaboradores:** cadastro manual (`CreateUser`), importação
   (`ImportUsers`) e conversão ATS (`criar` | `vincular`) **coexistem**. Nenhum
   incremento pode tornar o processo seletivo obrigatório para criar usuário.
5. **Histórico de passagens** permanece em `adms_employment_history`.

## Alternativas consideradas

- separar `people` / `users` / `employment` imediatamente (complexidade sem ganho
  no cenário 1:1 da Tiaraju);
- substituir a tabela de uma vez;
- obrigar toda admissão a passar pelo ATS (inviável enquanto o módulo evolui).

## Consequências

### Positivas

- processo simples: pré-contratação no GP; contratação cria/vincula `adms_users`;
- cadastro manual e importação preservados durante a evolução do ATS;
- Expand de identidade disponível sem forçar Contract;
- esforço priorizado em workflow, ACL, portal, auditoria e integração.

### Negativas e riscos

- `adms_users` continua concentrando responsabilidades (mitigar com organização
  no código: serviços, abas, validações por domínio);
- dual-write sombra pode divergir se não mantido — monitorar;
- Contract futuro ainda exige inventário de FKs quando for necessário.
