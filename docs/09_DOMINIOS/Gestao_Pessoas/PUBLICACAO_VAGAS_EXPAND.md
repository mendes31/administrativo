# Publicação de vagas — Expand Fase 3

- Domínio: Gestão de Pessoas / Talentos.
- Data: 27/07/2026.
- Status: flag admin + listagem pública + candidatura LGPD/CAPTCHA/dedupe + **upload de currículo** + links de divulgação + visibilidade interna/externa.

## Modelo

Colunas em `rh_vagas`:

| Coluna | Default | Uso |
|--------|---------|-----|
| `publicada` | `0` | Intenção de exibir no portal público |
| `publicado_em` | `NULL` | Timestamp da (re)publicação |
| `visibilidade` | `externa` | `externa` \| `interna` \| `ambas` |

Regras:

- `status` continua sendo o ciclo interno (`aberta|pausada|fechada|cancelada`);
- só vagas com `status = aberta` **e** `visibilidade` em `externa|ambas` podem ficar `publicada = 1`;
- vaga **só interna** nunca entra em `vagas-abertas` (o save força `publicada = 0`);
- `mostrar_salario` permanece independente;
- portal público usa `listPublicadas` / `getPublicadaById` (nunca `getById` admin).

## Onde publicar?

| Público-alvo | Canal | Como |
|--------------|-------|------|
| Candidatos externos (site, LinkedIn, redes) | Portal `vagas-abertas/{id}` | Divulgação = externa ou ambas + **Publicar (portal)**; copiar links na visualização da vaga (UTM por canal) |
| Colaboradores (app autenticado) | **`vagas-internas`** + Informativos | Divulgação = interna ou ambas; colaboradores se candidatam logados; use Informativos para anunciar/notificar departamentos |

Não use o portal público para vagas confidenciais só internas.

## Admin

- select **Divulgação** + checkbox **Publicar (portal)** em criar/editar;
- badge e bloco **Divulgação** na visualização (copiar URL / abrir / criar informativo);
- serviço: `RhVagaDivulgacaoService`.

## Portal público

- URL: `{URL_ADM}vagas-abertas` e `{URL_ADM}vagas-abertas/{id}`
- Query opcional: `utm_source` / `utm_medium` / `utm_campaign` (só rastreio; não altera segurança)
- Página `adms_pages` com `public_page=1` (controller `RhVagasPublicas`)
- Critério: `publicada=1` + `status=aberta` + prazo de inscrição vigente (se houver)
- Não expõe `observacoes`, responsável nem dados de pipeline
- Salário só se `mostrar_salario=1`

### Candidatura (POST)

- Formulário no detalhe da vaga; CSRF + honeypot + rate limit
- CAPTCHA **opcional e configurável** em `rh-vagas-publicas-config`
- Consentimento LGPD obrigatório (termo ativo `curriculo_candidato`)
- **Upload obrigatório de currículo** (PDF/DOC/DOCX, máx. 10 MB) via `RhCandidatoAnexoService::storeCurriculo` → storage privado (ADR-0003) + `rh_candidatos_anexos`
- Download permanece autenticado (`RhCandidatosDownloadAnexo` + access log)
- Dedupe: mesmo e-mail não se candidata duas vezes à mesma vaga
- Serviço: `RhCandidaturaPublicaService` / CAPTCHA: `RhVagasPublicasCaptchaService`

## Próximo incremento (opcional)

- (entregue) Listagem autenticada `vagas-internas` + candidatura ligada ao `user_id`.
- (entregue) Upload de currículo no portal público `vagas-abertas`.
- Melhorias: upload de currículo em `vagas-internas`, UTM analytics no admin, QR code.

## Migrations

`database/migrations/20260719235000_add_rh_vagas_publicacao_flag.php`

`database/migrations/20260719236000_register_rh_vagas_publicas_page.php`

`database/migrations/20260719237000_create_rh_vagas_publicas_config_captcha.php`

`database/migrations/20260727170000_add_rh_vagas_visibilidade_divulgacao.php`

`database/migrations/20260727180000_register_vagas_internas_portal_page.php`
