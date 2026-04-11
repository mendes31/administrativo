# Implantação: consultas SQL — boas-vindas e primeiro acesso

Uso **pontual** na fase de implantação para identificar quem ainda precisa de convite ou reenvio, sem alterar a listagem do sistema.

## Campos e tabelas relevantes

| Origem | Uso |
|--------|-----|
| `adms_users.enviar_boas_vindas_email` | **Preferência gravada no cadastro** (checkbox “enviar boas-vindas por e-mail ao criar”). Valor `0`/`1` no MySQL. **Não** é atualizado quando o envio acontece noutro momento nem reflete sozinho se a mensagem já foi enviada. |
| `adms_users.enviar_boas_vindas_whatsapp` | Idem para WhatsApp no ato da **criação** do utilizador. |
| `adms_users.boas_vindas_enviado_em` | Preenchido quando o `WelcomeMessageService` conclui envio com sucesso (e-mail e/ou WhatsApp). `NULL` = o sistema **não** registou envio. **Este é o campo a usar para “já foi enviado pelo sistema”.** |
| `adms_users.boas_vindas_enviado_por` | ID do utilizador da sessão que estava associado ao envio (auditoria). |
| `adms_login_attempts` | Tentativas de login; `resultado = 'SUCCESS'` indica login com senha válida. |
| `adms_departments` | Nome do departamento via `user_department_id`. |
| `adms_users_access_levels` + `adms_access_levels` | Um utilizador pode ter **vários** níveis; na consulta abaixo os nomes vêm concatenados (`GROUP_CONCAT`), ordenados por id do nível. |

**Porque é normal ver `0` em `enviar_boas_vindas_email` / `enviar_boas_vindas_whatsapp` e ainda assim `boas_vindas_enviado_em` preenchido:** o envio pode ter sido feito com outro fluxo (ex.: redefinição de senha / tela que marca envio na hora), utilizador criado por importação sem marcar as caixas, ou registo antigo antes de existir esse par de flags. Para implantação, confia em `boas_vindas_enviado_em`, `envio_boas_vindas_registado` / `situacao_implantacao` e no login — não nos dois `enviar_*`.

### Limitações (ler antes de usar)

1. **`boas_vindas_enviado_em`** não prova que o e-mail foi aberto ou que o link foi clicado — apenas que o envio foi considerado bem-sucedido pelo serviço.
2. **`adms_login_attempts`** só contém dados **a partir da criação dessa tabela** no ambiente. Utilizadores que só entraram antes disso podem aparecer como “sem login” mesmo já tendo acedido no passado.
3. Ajuste filtros (`status`, `username`, etc.) ao teu critério de implantação.

---

## Consulta única — todos os utilizadores ativos com indicadores (recomendada)

Uma só listagem com **departamento**, **níveis de acesso** (nomes concatenados se houver mais do que um), indicadores de **boas-vindas** e **login `SUCCESS`**, e **data do primeiro login** conhecido (quando existir).

Os níveis são obtidos por **subconsulta** com `GROUP_CONCAT` para não multiplicar linhas em relação ao `LEFT JOIN` de `adms_login_attempts`.

```sql
SELECT
    u.id,
    u.name,
    u.username,
    u.status,
    u.created_at,
    MAX(dep.name) AS departamento,
    (
        SELECT GROUP_CONCAT(DISTINCT al.name ORDER BY al.id SEPARATOR ', ')
        FROM adms_users_access_levels ual
        INNER JOIN adms_access_levels al ON al.id = ual.adms_access_level_id
        WHERE ual.adms_user_id = u.id
    ) AS niveis_acesso,
    u.boas_vindas_enviado_em,
    u.boas_vindas_enviado_por,
    MIN(a.data_tentativa) AS primeiro_login_success_em,
    CASE WHEN u.boas_vindas_enviado_em IS NOT NULL THEN 'Sim' ELSE 'Não' END AS envio_boas_vindas_registado,
    CASE WHEN COUNT(a.id) > 0 THEN 'Sim' ELSE 'Não' END AS ja_teve_login_success,
    CASE
        WHEN u.boas_vindas_enviado_em IS NULL THEN '1 - Sem registo de envio de boas-vindas'
        WHEN COUNT(a.id) = 0 THEN '2 - Boas-vindas registadas; sem login SUCCESS na tabela'
        ELSE '3 - Boas-vindas e pelo menos um login SUCCESS registados'
    END AS situacao_implantacao
FROM adms_users u
LEFT JOIN adms_departments dep ON dep.id = u.user_department_id
LEFT JOIN adms_login_attempts a
    ON a.user_id = u.id
   AND a.resultado = 'SUCCESS'
WHERE u.status = 'Ativo'
  AND u.username <> 'manager'
GROUP BY
    u.id,
    u.name,
    u.username,
    u.status,
    u.created_at,
    u.boas_vindas_enviado_em,
    u.boas_vindas_enviado_por
ORDER BY u.name;
```

Opcional — voltar a incluir **e-mail** ou as flags `enviar_boas_vindas_*` no `SELECT` (são preferências do cadastro, não prova de envio); se o fizeres, acrescenta as mesmas colunas no `GROUP BY`.

Opcional — **não** excluir o utilizador técnico: remove a linha `AND u.username <> 'manager'`.

---

## Filtros úteis (equivalentes às consultas antigas)

Sobre a consulta acima, podes **acrescentar `HAVING`** no fim para isolar cada grupo (ou filtrar no Excel / relatório dinâmico pelas colunas `situacao_implantacao` / `ja_teve_login_success`).

**A) Só quem ainda não tem registo de envio de boas-vindas** (antiga query 1):

```sql
HAVING u.boas_vindas_enviado_em IS NULL
```

**B) Só quem tem envio registado mas nenhum `SUCCESS` em `adms_login_attempts`** (antiga query 2 — candidatos a reenvio):

```sql
HAVING u.boas_vindas_enviado_em IS NOT NULL AND COUNT(a.id) = 0
```

**C) Só quem já tem pelo menos um login `SUCCESS` registado** (útil para confirmar adesão):

```sql
HAVING COUNT(a.id) > 0
```

---

## Operação sugerida na implantação

1. Correr a **consulta única** e guardar/exportar o resultado (ou usar um relatório local com a mesma SQL).
2. Ordenar ou filtrar por `situacao_implantacao` ou pelos `HAVING` acima:
   - prioridade **1** → enviar/registar boas-vindas;
   - prioridade **2** → reenvio ou suporte;
   - prioridade **3** → em regra implantado neste critério.
3. Repetir **semanalmente** nas primeiras semanas ou até estabilizar a adesão.

O reenvio no sistema continua a ser feito pelos fluxos já existentes (ex.: cadastro com flags de boas-vindas, redefinição de senha / notificação conforme configurado no projeto).
