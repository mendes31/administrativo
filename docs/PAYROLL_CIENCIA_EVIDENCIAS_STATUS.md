# Documentos de RH no portal — ciência, assinatura e evidências

Documento único de referência para **RH, Jurídico, DPO e TI**: cruza o desenho alvo (trilha probatória, OTP, versionamento) com o **estado atual do código** e a **ordem sugerida de desenvolvimento**.

**Última revisão:** consolidado a partir da análise funcional-jurídica e do repositório `administrativo`.

---

## 1. Mapa rápido: já temos | parcial | falta

Legenda: **✓** implementado e operacional · **◐** existe base, incompleto ou não aplicado em runtime · **✗** não implementado

### 1.1 Níveis de ciência / assinatura

| Nível | Descrição | Situação |
|--------|-----------|----------|
| Ciência simples | Botão “li e estou ciente” + registo | **✗** fluxo e persistência de aceite; **◐** toggles no tipo de documento |
| Assinatura avançada | Senha / OTP / reautenticação | **◐** `signature_auth` e campos em `adms_payroll_document_types` + CRUD; **✗** aplicação no fluxo Assinar |
| Assinatura qualificada (ICP-Brasil / provedor) | Certificado digital / Clicksign etc. | **✗** (fase futura) |

### 1.2 Trilha de evidências (audit trail)

| Elemento | Situação |
|----------|----------|
| IP, user-agent, data/hora | **◐** — ao servir o PDF (`ViewPayrollDocument`), registo em `adms_payroll_document_access_logs` (modo inline vs attachment) |
| Timezone UTC + local explícitos | **✗** |
| Hash SHA-256 do PDF persistido | **✗** |
| Hash do conteúdo aceite (termo + documento) | **✗** |
| Versão documental (`active` / `superseded` / …) | **✗** |
| Sequência: disponibilizado → notificado → visualizado → baixado → confirmado | **◐** / **✗** — só ramo de acesso ao stream |
| `session_id` nos eventos | **✗** |
| `batch_id` na trilha de acesso | **✗** (o documento tem `import_batch_id`; o log não duplica) |

### 1.3 Boas práticas (original intacto, comprovante, versionar)

| Prática | Situação |
|---------|----------|
| Não sobrescrever PDF sem histórico comprovável | **◐** — lógica de substituição na importação; **✗** modelo formal de versão + hash |
| PDF de comprovante / evidência anexa | **✗** |
| Logs imutáveis (append-only) ou política explícita | **✗** — inserts atuais não substituem um modelo de eventos completo |

### 1.4 Modelo prático de compliance (login, eventos, confirmação, régua)

| Item | Situação |
|------|----------|
| Login individual e sessão | **✓** |
| Eventos: disponibilizado, notificado, visualizado, baixado, confirmado | **◐** / **✗** |
| Metadados por evento (IP, UA, utilizador) | **◐** |
| “Confirmar recebimento” com reautenticação (senha/OTP) | **✗** |
| Relatório / dossiê de auditoria por documento | **✗** |
| Régua D+3 / D+7 e contingência operacional | **✗** |
| Política interna / termo na admissão | **Fora do código** — processo; o sistema pode suportar textos configuráveis depois |

### 1.5 Infraestrutura WhatsApp e OTP

| Item | Situação |
|------|----------|
| Envio WhatsApp (Evolution / Twilio / Meta), config, teste | **✓** — ex.: `SendWhatsAppService`, `WhatsAppConfig`, `adms_whatsapp_config` |
| Uso real (ex.: recuperação de senha) | **✓** |
| OTP: gerar, armazenar hash, expirar, tentativas, cooldown, fallback e-mail, auditoria | **✗** |
| `require_auth_download` no tipo de documento | **◐** campo existe; **✗** não aplicado no download |

### 1.6 Fluxo UX recomendado (ver sem OTP · baixar com log · assinar com OTP)

| Etapa | Situação |
|-------|----------|
| Visualizar sem OTP + registo | **◐** — log ao servir PDF; **✗** hash no evento e tipo de evento explícito `viewed` |
| Download sem OTP + log (ou com reauth se política exigir) | **◐** — `inline=0` força download; **✗** política por tipo e evento `downloaded` explícito |
| Assinar ciência com OTP WhatsApp + fallback e-mail | **✗** |

### 1.7 Estados do documento (máquina de estados)

Exemplos alvo: `available` → `viewed` → `pending_signature` → `signed` / `expired_pending`.

| Estado | Situação |
|--------|----------|
| Máquina de estados por documento ou por **versão** | **✗** |

### 1.8 Contingência RH e painel

| Item | Situação |
|------|----------|
| Lembretes D+3 / D+5 / D+7, escalonamento, painel de pendentes | **✗** |

### 1.9 LGPD

| Item | Situação |
|------|----------|
| Transparência na área “Meus documentos” | **✓** — bloco informativo na view do portal |
| Consentimento ou registo explícito para trilha/OTP (se DPO exigir) | **✗** — avaliar com DPO (base legal contratual/obrigação legal muitas vezes basta) |

---

## 2. Checklist reorganizado (referência única)

### A. Níveis de assinatura

1. **Ciência simples** — [◐] config no tipo; [✗] botão Assinar e registo de aceite.  
2. **Avançada** (senha/OTP) — [◐] `signature_auth`; [✗] implementação; [✓] WhatsApp como canal.  
3. **Qualificada** — [✗].

### B. Trilha mínima desejável

| Requisito | Tag |
|-----------|-----|
| IP, UA, timestamp | ◐ |
| UTC + local | ✗ |
| `document_hash_sha256` | ✗ |
| Versão documental | ✗ |
| Eventos completos + OTP | ✗ / ◐ |
| Tabela append-only ou política clara | ✗ |

### C. Integridade e versionamento

- Substituir sem histórico formal: **◐** → alvo **✓** com versões e `superseded`.  
- Original + comprovante: **✗**.  
- Hash por versão: **✗**.

### D. Fases de produto (alinhamento técnico)

| Fase | Conteúdo (resumo) | Apoio atual | Falta |
|------|-------------------|-------------|--------|
| **1** | Estados, reauth, evidência completa, comprovante PDF simples | Login, PDF privado, log parcial, tipos configuráveis | Estados, assinatura, hash, OTP, comprovante |
| **2** | PDF com folha de evidências, hashes, ID assinatura; TSA se possível | — | Tudo |
| **3** | Provedor externo / ICP-Brasil | — | Tudo |

### E. Requisitos de prova (evidência)

- Identificação individual: **✓**  
- Eventos mínimos listados no desenho: **◐** / **✗**  
- Metadados completos por evento: **◐**  
- SHA-256 armazenado: **✗**  
- Versionar correções: **✗**

### F. Entrega e ciência

- Publicação (importação RH): **✓**  
- Notificação ao publicar com registo `notified`: **✗**  
- Texto de aceite configurável no ato de assinar: **✗**  
- Reauth no aceite: **✗**  
- Régua D+X + contingência: **✗**

### G. OTP — segurança e eventos

- Expiração curta, tentativas máximas, limite de OTP/hora, cooldown, hash, one-time, invalidar anterior: **✗**  
- Eventos: `otp_requested`, `otp_sent_whatsapp`, `otp_sent_email_fallback`, `otp_validate_success`, `otp_validate_fail`, `document_signed`: **✗**

### H. Contingência

- D+3 / D+5 / D+7, alertas a gestor/RH, painel filtrado: **✗**

---

## 3. Síntese executiva

O estudo jurídico-operacional e este mapa são **coerentes**: o sistema já oferece **canal autenticado**, **armazenamento privado de PDFs**, **importação em lote**, **tipos de documento configuráveis** (incluindo campos para assinatura/OTP futuros) e **primeiro tijolo de trilha** ao abrir/baixar o PDF.

O **miolo probatório** que ainda falta — e que reduz risco quando o colaborador não assina ou nega ciência — é: **versionamento + hash**, **máquina de estados**, **eventos completos** (incluindo notificação e assinatura), **ação “Assinar” com regras por tipo**, **OTP** reutilizando `SendWhatsAppService`, **régua de lembretes e painel de pendentes**, e **comprovantes PDF** (fases 2–3).

---

## 4. Ordem sugerida de desenvolvimento (backlog técnico)

1. **Modelo de versão** do documento lógico (competência + tipo + titular): `version`, `status_version`, `file_hash_sha256`, `supersedes_*`; ajustar importação para não “sumir” histórico.  
2. **Botão Assinar / Confirmar recebimento** + persistência de aceite + aplicar `requires_signature` e `signature_auth` por `document_type`.  
3. **Camada OTP** (tabelas, hash do código, limites, fallback e-mail) + eventos de auditoria específicos.  
4. **Notificação** na publicação + **régua D+X** + **painel de pendentes** para RH.  
5. **Comprovante PDF** de evidência (sem alterar o original).  
6. **Integração** com provedor / ICP (se Jurídico exigir).

---

## 5. Referências no repositório (para TI)

| Tema | Onde |
|------|------|
| Documentos por colaborador | `adms_employee_payroll_documents`, `EmployeePayrollDocumentsRepository` |
| Lotes de importação | `adms_payroll_import_batches` |
| Tipos e regras (UI) | `adms_payroll_document_types`, `ListPayrollDocumentTypes`, `CreatePayrollDocumentType`, `UpdatePayrollDocumentType` |
| Stream do PDF | `ViewPayrollDocument` |
| Lista colaborador | `MyPayrollDocuments`, `my_payroll_documents.php` |
| Log de acesso ao PDF | `adms_payroll_document_access_logs`, migration `20260413120000_create_payroll_document_access_logs.php` |
| WhatsApp | `SendWhatsAppService`, `AdmsWhatsAppConfigRepository`, `WhatsAppConfig` |

---

## 6. Nota jurídica (resumo)

- A obrigação trabalhista central inclui **pagamento correto**, **discriminação de parcelas** e **capacidade de demonstrar entrega/acesso** ao comprovante em litígio (ex.: contexto do art. 464 da CLT e prática de meios eletrônicos).  
- **“Só disponibilizar”** sem trilha e sem diligência com **não conformes** mantém risco na empresa.  
- **MP 2.200-2/2001** é referência para meios eletrônicos; o **nível probatório** (simples vs avançado vs qualificado) deve ser **fechado com Jurídico** por tipo de documento.  
- Este ficheiro **não substitui parecer jurídico**; descreve aderência técnica ao desenho de compliance.

---

*Documento gerado para acompanhamento de projeto. Atualizar após cada entrega relevante (migrations, fluxo Assinar, OTP, notificações).*
