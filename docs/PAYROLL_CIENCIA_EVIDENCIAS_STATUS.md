# Documentos de RH no portal — ciência, assinatura e evidências

Documento único de referência para **RH, Jurídico, DPO e TI**: cruza o desenho alvo (trilha probatória, OTP, versionamento) com o **estado atual do código** e a **ordem sugerida de desenvolvimento**.

**Última revisão:** 2026-04-10 — alinhado ao código em `administrativo` (versão, assinatura, OTP, lembretes, comprovante, download com reauth).

---

## 0. Pacote 1–5 (resumo executivo)

| # | Entrega | Estado |
|---|---------|--------|
| 1 | Versão + hash no documento | **✓** — grupo lógico, `document_version`, `status_version`, `file_hash_sha256`, supersede na reimportação |
| 2 | Botão Assinar + aceite + `signature_auth` | **✓** — `SignPayrollDocument`, `recordSignature`, snapshots por tipo |
| 3 | OTP (tabelas + WhatsApp + e-mail) + eventos | **✓** — `adms_payroll_document_otp_challenges`, `adms_payroll_document_events` |
| 4 | Notificação ao publicar + régua D+X + painel | **✓** — notificação interna, régua **por tipo**, `PayrollDocumentRemindersService` (1×/24h login/dashboard), painel pendentes |
| 5 | Comprovante PDF (fase 2 básica) | **✓** — `PayrollSignatureReceipt` (mPDF); não inclui TSA / prova qualificada |

*Pré-requisito:* migrations aplicadas (`phinx migrate`), incluindo páginas/ACL registadas nas migrations de payroll.

---

## 1. Mapa rápido: já temos | parcial | falta

Legenda: **✓** implementado e operacional · **◐** existe base, incompleto ou não formalizado · **✗** não implementado

### 1.1 Níveis de ciência / assinatura

| Nível | Descrição | Situação |
|--------|-----------|----------|
| Ciência simples | Botão + registo (`none`) | **✓** `SignPayrollDocument`, `signature_status`, lista “Meus documentos” |
| Assinatura avançada | Senha / OTP / reautenticação | **✓** `signature_auth_snapshot` + `UsersRepository::getPasswordHashById`, OTP + fallback e-mail |
| Assinatura qualificada (ICP-Brasil / provedor) | Certificado digital / Clicksign etc. | **✗** (fase futura) |

### 1.2 Trilha de evidências (audit trail)

| Elemento | Situação |
|----------|----------|
| IP, user-agent, data/hora | **✓** — `adms_payroll_document_access_logs` + eventos em `adms_payroll_document_events` (ex.: `document_viewed`, `document_downloaded`) |
| Timezone UTC + local explícitos | **✗** — usar timezone da app; não há duplo carimbo no evento |
| Hash SHA-256 do PDF persistido | **✓** `file_hash_sha256` por versão |
| Hash na assinatura | **✓** `signed_document_hash_sha256` |
| Hash do conteúdo aceite (termo + documento composto) | **✗** |
| Versão documental (`active` / `superseded` / …) | **✓** `status_version`, `supersedes_document_id` |
| Sequência publicado → visualizado → baixado → confirmado | **◐** — `document_published`, viewed/downloaded, OTP/*, `document_signed`; notificação ao publicar não gera evento `notified` dedicado (só notificação interna) |
| `session_id` nos eventos | **✗** |
| `batch_id` na trilha de acesso | **◐** — no documento e em `meta_json` de `document_published`; não no log de stream |

### 1.3 Boas práticas (original intacto, comprovante, versionar)

| Prática | Situação |
|---------|----------|
| Não sobrescrever PDF sem histórico | **✓** supersede + nova versão (mesmo nome/original) |
| PDF de comprovante | **✓** básico — `PayrollSignatureReceipt` |
| Logs / eventos imutáveis | **◐** — inserts append-only em `adms_payroll_document_events`; política formal por escrito **✗** |

### 1.4 Modelo prático de compliance (login, eventos, confirmação, régua)

| Item | Situação |
|------|----------|
| Login individual e sessão | **✓** |
| Confirmar recebimento com senha/OTP | **✓** |
| Relatório / dossiê de auditoria por documento (export) | **✗** |
| Régua D+X | **✓** por tipo (`signature_reminders_*`) + job + `PayrollDocumentRemindersService` |
| Token/cron HTTP opcional | **✓** — token em BD (`payroll-cron-config`); CLI `scripts/payroll_reminders_cron.php` força execução |
| Política interna / termo na admissão | **Fora do código** |

### 1.5 Infraestrutura WhatsApp e OTP

| Item | Situação |
|------|----------|
| Envio WhatsApp | **✓** `SendWhatsAppService` |
| OTP folha: hash, expiração, tentativas, limite/hora, fallback e-mail | **✓** `PayrollDocumentOtpRepository` + `SignPayrollDocument` |
| `require_auth_download` | **✓** snapshot + `ConfirmPayrollDocumentDownload` + desbloqueio temporário na sessão (só **download**; ver sem senha) |

### 1.6 Fluxo UX (ver · baixar · assinar)

| Etapa | Situação |
|-------|----------|
| Visualizar PDF (inline) + registo | **✓** |
| Download com reauth se política | **✓** |
| Assinar com OTP + fallback e-mail | **✓** |

### 1.7 Estados do documento

| Estado | Situação |
|--------|----------|
| Máquina de estados explícita (diagrama único) | **✗** |
| Campos `signature_status`, `status_version`, `reminder_stage` | **✓** — cobrem o fluxo operacional |

### 1.8 Contingência RH e painel

| Item | Situação |
|------|----------|
| Lembretes configuráveis D+1/D+2/D+3 por tipo | **✓** |
| Painel pendentes de ciência | **✓** `ListPayrollSigningPendencies` |

### 1.9 LGPD

| Item | Situação |
|------|----------|
| Transparência em “Meus documentos” | **✓** |
| Consentimento específico trilha/OTP | **✗** — avaliar com DPO |

---

## 2. Checklist reorganizado (referência única)

### A. Níveis de assinatura

1. **Ciência simples** — **✓**  
2. **Avançada** (senha/OTP) — **✓**  
3. **Qualificada** — **✗**

### B. Trilha mínima desejável

| Requisito | Tag |
|-----------|-----|
| IP, UA, timestamp | ✓ |
| UTC + local | ✗ |
| `document_hash_sha256` | ✓ |
| Versão documental | ✓ |
| Eventos + OTP | ✓ |
| Tabela append-only + política documentada | ◐ |

### C. Integridade e versionamento

- Versões + `superseded`: **✓**  
- Comprovante PDF simples: **✓**  
- TSA / prova reforçada: **✗**

### D. Fases de produto

| Fase | Conteúdo | Estado |
|------|-----------|--------|
| **1** | Reauth, hash, versão, assinatura, OTP, notificações, régua, painel, comprovante básico | **◐/✓** — falta sobretudo formalização e export dossiê |
| **2** | Folha de evidências reforçada, TSA | **✗** |
| **3** | Provedor / ICP-Brasil | **✗** |

### E. Requisitos de prova

- Identificação individual: **✓**  
- Eventos principais: **✓** / **◐** (evento `notified` explícito opcional)  
- SHA-256 armazenado: **✓**  
- Versionar correções: **✓**

### F. Entrega e ciência

- Publicação: **✓**  
- Notificação ao publicar: **✓** (interna)  
- Texto de aceite configurável no ecrã Assinar: **✗**  
- Régua D+X: **✓**

### G. OTP

- Segurança (expiração, tentativas, rate limit, hash): **✓**  
- Eventos listados no desenho: **✓** (nomes podem variar ligeiramente; ver código)

### H. Contingência

- Régua por tipo + job + disparo em login/dashboard: **✓**  
- Escalação para gestor (e-mail separado): **✗**

---

## 3. Síntese executiva

O portal cobre o **núcleo operacional** acordado: PDF privado por utilizador, **versionamento com hash**, **ciência com regras por tipo** (incluindo OTP e WhatsApp/e-mail), **notificação e lembretes**, **painel RH**, **comprovante PDF simples** e **download condicionado** quando o tipo exige.

Para **reduzir risco jurídico residual**, os maiores buracos técnicos passam a ser: **export/dossiê** de trilha por documento, **texto de aceite configurável**, **metadados temporais explícitos (UTC)** e, se exigido, **prova qualificada / TSA / provedor** (fases 2–3).

---

## 4. Backlog sugerido (próximos passos)

1. **Dossiê / export** — CSV ou PDF com eventos + log de acesso por `employee_payroll_document_id` (RH com permissão).  
2. **Texto de aceite** — campo no tipo de documento (HTML ou texto) exibido no ecrã Assinar antes de confirmar.  
3. **Evento `notified`** ou equivalente — registo explícito quando `notifyPublished` corre (opcional, para relatórios).  
4. **`session_id` / UTC** nos eventos — se DPO ou auditoria interna exigir.  
5. **Integração ICP / Clicksign / TSA** — conforme parecer jurídico.

Itens **1–5** do pacote inicial estão **entregues no código**; a lista acima é **evolução** pós-pacote.

---

## 5. Referências no repositório (para TI)

| Tema | Onde |
|------|------|
| Documentos + versão + hash | `adms_employee_payroll_documents`, `EmployeePayrollDocumentsRepository`, migrações `20260414120000_*` (versionamento/OTP/eventos) |
| Importação / supersede | `PayrollPdfSplitService` |
| Tipos (assinatura, OTP, lembretes, download) | `adms_payroll_document_types`, CRUD em `portal/` |
| Stream PDF | `ViewPayrollDocument` |
| Download com senha | `ConfirmPayrollDocumentDownload`, `PayrollDocumentDownloadUnlock`, coluna `require_auth_download_snapshot` |
| Lista / Assinar | `MyPayrollDocuments`, `SignPayrollDocument`, views `my_payroll_documents.php`, `sign_payroll_document.php` |
| OTP | `PayrollDocumentOtpRepository`, `adms_payroll_document_otp_challenges` |
| Eventos | `PayrollDocumentEventsRepository`, `adms_payroll_document_events` |
| Notificação publicação | `PayrollDocumentPublishNotifier` |
| Lembretes | `PayrollDocumentReminderJob`, `PayrollDocumentRemindersService`, `storage/cache/system/payroll_reminders_last_run.json` |
| Token cron (BD) | `adms_payroll_cron_config`, `PayrollCronConfig`, `PayrollRemindersCron` |
| Painel pendentes | `ListPayrollSigningPendencies` |
| Comprovante PDF | `PayrollSignatureReceipt` |
| Log de acesso PDF | `adms_payroll_document_access_logs` |
| WhatsApp / e-mail | `SendWhatsAppService`, `SendEmailService` |

---

## 6. Nota jurídica (resumo)

- A obrigação trabalhista central inclui **pagamento correto**, **discriminação de parcelas** e **capacidade de demonstrar entrega/acesso** ao comprovante em litígio (ex.: contexto do art. 464 da CLT e prática de meios eletrônicos).  
- **“Só disponibilizar”** sem trilha e sem diligência com **não conformes** mantém risco na empresa.  
- **MP 2.200-2/2001** é referência para meios eletrônicos; o **nível probatório** (simples vs avançado vs qualificado) deve ser **fechado com Jurídico** por tipo de documento.  
- Este ficheiro **não substitui parecer jurídico**; descreve aderência técnica ao desenho de compliance.

---

*Atualizar este ficheiro após mudanças relevantes em migrations, fluxos de assinatura ou política de evidências.*
