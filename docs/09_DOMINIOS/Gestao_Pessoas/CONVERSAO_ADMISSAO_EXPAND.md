# Conversão oferta → colaborador — Expand Fase 3

- Domínio: Gestão de Pessoas / Talentos.
- Data: 19/07/2026.
- Status: 1º incremento — conversão auditável para Conta/Pessoa via fachada `adms_users`.

## Objetivo

Converter oferta **aceita** em colaborador do Portal de forma explícita e
auditável, sem unificação automática por e-mail/CPF e sem inventar tabelas
Pessoa/Vínculo físicas antes do Expand de identidade (ADR-0002).

## Decisão deste incremento

- Destino da conversão: `adms_users` (fachada atual de Pessoa+Conta+Vínculo).
- Registro auditável em `rh_conversoes_admissao` (quem, quando, oferta, candidato, usuário).
- Candidato recebe `adms_user_id` e `status_processo = contratado`.
- Não cria tabelas `pessoas` / `vinculos` neste Expand.
- Reversão completa (desfazer conversão) fica para incremento futuro.

## Pré-requisitos

1. Oferta com status `aceita`.
2. Todos os documentos **obrigatórios** da pré-admissão com status `aprovado`.
3. Permissão de gerenciar pipeline da vaga + ACL `RhOfertasConvert`.

## Modos

| Modo | Comportamento |
|------|----------------|
| `criar` | Cria `adms_users` a partir dos dados do candidato + lotação informada |
| `vincular` | Associa a um usuário existente (por ID), sem criar conta |

## Modelo

### `rh_conversoes_admissao`

| Campo | Uso |
|-------|-----|
| `rh_oferta_id` | Oferta convertida (única conversão ativa) |
| `rh_candidato_id` / `rh_vaga_id` / `rh_candidatura_id` | contexto |
| `adms_user_id` | Conta/colaborador resultante |
| `modo` | `criar` \| `vincular` |
| `converted_by_user_id` | ator |
| `observacoes` | motivo / notas |
| `status` | `concluida` (futuro: `revertida`) |

### Colunas adicionadas

- `rh_candidatos.adms_user_id` (nullable)
- `rh_ofertas.rh_conversao_id` (nullable)

## Histórico

`tipo_evento = oferta`, `status_novo = conversao_concluida`.

## Fora deste incremento

- Tabelas físicas Pessoa/Vínculo/Lotação;
- envio automático de boas-vindas (flags podem ser ligadas manualmente);
- upload de documentos da pré-admissão;
- reverter conversão.

## Integração

A conversão bem-sucedida dispara automaticamente o plano de onboarding
([ONBOARDING_EXPAND.md](ONBOARDING_EXPAND.md)).

## Migration

`database/migrations/20260719239000_create_rh_conversoes_admissao.php`
