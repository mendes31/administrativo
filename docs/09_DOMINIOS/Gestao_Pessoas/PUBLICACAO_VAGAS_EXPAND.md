# Publicação de vagas — Expand Fase 3

- Domínio: Gestão de Pessoas / Talentos.
- Data: 19/07/2026.
- Status: flag admin + listagem pública + **candidatura com LGPD/CAPTCHA/dedupe** entregues.

## Modelo

Colunas em `rh_vagas`:

| Coluna | Default | Uso |
|--------|---------|-----|
| `publicada` | `0` | Intenção de exibir no portal |
| `publicado_em` | `NULL` | Timestamp da (re)publicação |

Regras:

- `status` continua sendo o ciclo interno (`aberta|pausada|fechada|cancelada`);
- só vagas com `status = aberta` podem ficar `publicada = 1` (caso contrário o save força `0`);
- `mostrar_salario` permanece independente;
- portal público usa `listPublicadas` / `getPublicadaById` (nunca `getById` admin).

## Admin

- checkbox em criar/editar;
- badge e filtro na listagem;
- data de publicação na visualização.

## Portal público

- URL: `{URL_ADM}vagas-abertas` e `{URL_ADM}vagas-abertas/{id}`
- Página `adms_pages` com `public_page=1` (controller `RhVagasPublicas`)
- Layout próprio (sem menu admin)
- Critério: `publicada=1` + `status=aberta` + prazo de inscrição vigente (se houver)
- Não expõe `observacoes`, responsável nem dados de pipeline
- Salário só se `mostrar_salario=1`

### Candidatura (POST)

- Formulário no detalhe da vaga; CSRF + honeypot + rate limit
- CAPTCHA **opcional e configurável** em `rh-vagas-publicas-config` (tabela `rh_vagas_publicas_config`) — independente do Canal de Denúncias
- Consentimento LGPD obrigatório (termo ativo `curriculo_candidato`)
- Sem upload de currículo neste incremento
- Dedupe: mesmo e-mail não se candidata duas vezes à mesma vaga; reutiliza candidato ativo existente
- Origem do histórico: `portal`; origem do cadastro novo: `form_trabalhe_conosco`
- Serviço: `RhCandidaturaPublicaService` / CAPTCHA: `RhVagasPublicasCaptchaService`

## Contract / próximos Expand

1. [x] Listagem pública read-only
2. [x] Candidatura pública + consentimento LGPD + CAPTCHA + deduplicação
3. [x] Oferta / pré-admissão (aceite RH + checklist; sem conversão Pessoa)
4. Conversão auditável Pessoa/Vínculo — entregue via fachada `adms_users` ([CONVERSAO_ADMISSAO_EXPAND.md](CONVERSAO_ADMISSAO_EXPAND.md)); tabelas físicas ficam na Fase 4.

## Migrations

`database/migrations/20260719235000_add_rh_vagas_publicacao_flag.php`

`database/migrations/20260719236000_register_rh_vagas_publicas_page.php`

`database/migrations/20260719237000_create_rh_vagas_publicas_config_captcha.php`
