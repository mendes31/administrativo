# Estabelecimentos (Matriz / Filial) — Expand

- Domínio: Administração (cadastro transversal; consumido por Gestão de Pessoas e outros módulos).
- Data: 20/07/2026.
- Status: CRUD Filiais + vínculo de rótulos com `empresa_contratante` do usuário.
- Menu: **Administração → Configurações → Filiais** (não exclusivo de GP).

## Objetivo

Permitir cadastrar cada CNPJ/estabelecimento com **tipo Matriz ou Filial**, **razão social**
(nome empresarial) e **nome fantasia**, alinhado ao comprovante da Receita Federal —
mesmo quando a razão social é idêntica entre estabelecimentos.

## O que o cadastro de usuário usa

| Campo no usuário | O que é | Fonte |
|------------------|---------|--------|
| `empresa_contratante` | CNPJ / empresa do vínculo (slug) | Select com rótulo = **nome fantasia** de `adms_branches` |
| `user_branch_id` | FK → `adms_branches.id` | Dual-write a partir do slug; backfill dos já cadastrados |

Slugs gravados (não mudam):

| Slug | Nome fantasia (rótulo) |
|------|------------------------|
| `tiaraju_farma` | Tiaraju Farma |
| `lab_tiaraju_matriz` | Laboratório Tiaraju |
| `lab_tiaraju_filial` | Afra Pharma |

**Obrigatoriedade:** `empresa_contratante` é **obrigatória no cadastro e na edição**
(exceto usuário técnico `manager`). Dual-write preenche `user_branch_id`.

**Listagem / filtros:** o filtro de empresa em Listar Usuários considera
`empresa_contratante` **ou** `user_branch_id` (FK). “Sem empresa” exige ambos vazios.
Exibição e ASO resolvem o slug a partir do slug ou da filial vinculada.

Homologação (jul/2026): 12 usuários com `lab_tiaraju_matriz`; ~208 ainda sem empresa —
preenchimento via importação ou edição (sem regra de backfill em massa neste incremento).

**Importação:** ao mapear `empresa_contratante`, o dual-write preenche `user_branch_id`.

## Regras

- `establishment_type`: `matriz` | `filial` (obrigatório; padrão `filial`).
- `cnpj`: 14 dígitos, único quando preenchido (vários NULL permitidos).
- `razao_social`: nome empresarial (pode repetir entre registros).
- `nome_fantasia`: título do estabelecimento; se `name` vier vazio, copia o fantasia.
- Endereço no formato Receita: `logradouro`, `numero`, `complemento`, `cep`, `bairro`, `municipio`, `uf`.
- Contato: `email` (endereço eletrônico), `phone` (telefone).
- Complementares: `data_abertura`, `porte`, `cnae_principal`, `natureza_juridica`, `situacao_cadastral`.
- `address` continua como linha-resumo montada automaticamente a partir do endereço estruturado.
- `code` das 3 empresas conhecidas = slug de `empresa_contratante` (migration de vínculo).

## Fora deste incremento

- Substituir definitivamente o slug por FK (`user_branch_id` ou coluna nova).
- Consulta automática à Receita Federal / CNPJ.
- Lista de CNAEs secundários; EFR; situação especial.

## Migration

- `database/migrations/20260720160000_expand_adms_branches_estabelecimento_fields.php`
- `database/migrations/20260720161000_expand_adms_branches_endereco_cnpj_fields.php`
- `database/migrations/20260720170000_link_branches_to_empresa_contratante_slugs.php`
- `database/migrations/20260720171000_backfill_user_branch_id_from_empresa_contratante.php`
- `database/migrations/20260720172000_fix_adms_branches_code_length_and_relink_users.php`

## UI

- Listagem / cadastro / edição / visualização de Filiais com os novos campos e filtros por tipo e CNPJ.
- Select **Empresa contratante** no usuário exibe os nomes fantasia das Filiais.
