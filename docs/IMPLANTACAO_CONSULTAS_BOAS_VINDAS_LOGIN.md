# Implantação: consultas SQL — boas-vindas e primeiro acesso

Uso **pontual** na fase de implantação para identificar quem ainda precisa de convite ou reenvio, sem alterar a listagem do sistema.

## Campos e tabelas relevantes

| Origem | Uso |
|--------|-----|
| `adms_users.boas_vindas_enviado_em` | Preenchido quando o `WelcomeMessageService` conclui envio com sucesso (e-mail e/ou WhatsApp). `NULL` = o sistema **não** registou envio. |
| `adms_users.boas_vindas_enviado_por` | ID do utilizador da sessão que estava associado ao envio (auditoria). |
| `adms_login_attempts` | Tentativas de login; `resultado = 'SUCCESS'` indica login com senha válida. |

### Limitações (ler antes de usar)

1. **`boas_vindas_enviado_em`** não prova que o e-mail foi aberto ou que o link foi clicado — apenas que o envio foi considerado bem-sucedido pelo serviço.
2. **`adms_login_attempts`** só contém dados **a partir da criação dessa tabela** no ambiente. Utilizadores que só entraram antes disso podem aparecer como “sem login” mesmo já tendo acedido no passado.
3. Ajuste filtros (`status`, `username`, etc.) ao teu critério de implantação.

---

## 1. Utilizadores ativos sem registo de envio de boas-vindas

Útil para: **ainda não disparámos** mensagem de boas-vindas pelo fluxo do sistema (ou falhou antes de gravar).

```sql
SELECT
    u.id,
    u.name,
    u.email,
    u.username,
    u.status,
    u.created_at,
    u.enviar_boas_vindas_email,
    u.enviar_boas_vindas_whatsapp
FROM adms_users u
WHERE u.status = 'Ativo'
  AND u.boas_vindas_enviado_em IS NULL
ORDER BY u.name;
```

Opcional — excluir conta técnica (ex.: `manager`), se existir:

```sql
  AND u.username <> 'manager'
```

---

## 2. Boas-vindas registadas como enviadas, mas sem nenhum login `SUCCESS`

Útil para: **já enviámos** (segundo o sistema) e **ainda não há** login bem-sucedido na tabela de tentativas — candidatos a **reenvio** ou contacto manual.

```sql
SELECT
    u.id,
    u.name,
    u.email,
    u.username,
    u.status,
    u.boas_vindas_enviado_em,
    u.boas_vindas_enviado_por
FROM adms_users u
WHERE u.status = 'Ativo'
  AND u.boas_vindas_enviado_em IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM adms_login_attempts a
      WHERE a.user_id = u.id
        AND a.resultado = 'SUCCESS'
  )
ORDER BY u.boas_vindas_enviado_em DESC;
```

---

## 3. Primeira data de login com sucesso conhecida (referência)

Útil para cruzar informação ou relatórios; a “primeira” é a menor `data_tentativa` com `SUCCESS` por utilizador.

```sql
SELECT
    u.id,
    u.name,
    u.username,
    MIN(a.data_tentativa) AS primeiro_login_success_em
FROM adms_users u
INNER JOIN adms_login_attempts a ON a.user_id = u.id AND a.resultado = 'SUCCESS'
GROUP BY u.id, u.name, u.username
ORDER BY u.name;
```

Utilizadores **sem** linha neste resultado ou na query acima podem nunca ter logado **desde** existir histórico em `adms_login_attempts`, ou nunca ter logado de todo.

---

## Operação sugerida na implantação

1. Correr a **query 1** — priorizar envio/reenvio para quem está sem `boas_vindas_enviado_em`.
2. Correr a **query 2** — priorizar reenvio ou suporte para quem já tem envio registado mas não aparece login.
3. Repetir **semanalmente** nas primeiras semanas ou até estabilizar a adesão.

O reenvio no sistema continua a ser feito pelos fluxos já existentes (ex.: cadastro com flags de boas-vindas, redefinição de senha / notificação conforme configurado no projeto).
