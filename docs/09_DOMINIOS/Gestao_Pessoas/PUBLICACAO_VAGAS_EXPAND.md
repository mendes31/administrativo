# Publicação de vagas — Expand Fase 3

- Domínio: Gestão de Pessoas / Talentos.
- Data: 19/07/2026.
- Status: flag administrativa entregue; **portal público ainda não**.

## Modelo

Colunas em `rh_vagas`:

| Coluna | Default | Uso |
|--------|---------|-----|
| `publicada` | `0` | Intenção de exibir no portal futuro |
| `publicado_em` | `NULL` | Timestamp da (re)publicação |

Regras deste Expand:

- `status` continua sendo o ciclo interno (`aberta|pausada|fechada|cancelada`);
- só vagas com `status = aberta` podem ficar `publicada = 1` (caso contrário o save força `0`);
- `mostrar_salario` permanece independente (preparação para página pública);
- **nenhuma rota pública** é criada neste incremento.

## Admin

- checkbox em criar/editar;
- badge e filtro na listagem;
- data de publicação na visualização.

## Contract / próximos Expand

1. Listagem pública read-only (`publicada=1` + `status=aberta` + limite de inscrição).
2. Candidatura pública + consentimento LGPD + CAPTCHA + deduplicação.
3. Oferta / pré-admissão.

## Migration

`database/migrations/20260719235000_add_rh_vagas_publicacao_flag.php`
