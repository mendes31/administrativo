# Guia de utilização — Canal de Denúncias

Documento de referência para Compliance, Comitê de Ética, TI e usuários operadores do módulo **Canal de Denúncias** no Tiaraju.

**Evidências para fiscalização (Ministério do Trabalho):** ver `docs/CANAL_DENUNCIAS_EVIDENCIAS_MINISTERIO_TRABALHO.md` e queries em `docs/sql/whistleblowing_evidencias_ministerio_trabalho.sql`.

---

## 1. O que é o módulo

Canal **anônimo** para relatos de condutas inadequadas (assédio, fraude, corrupção, discriminação, segurança, meio ambiente etc.), com:

- **Canal público** (`/canaldenuncia`) — registro e acompanhamento sem login, por protocolo + senha.
- **Gestão interna** (`/administrativo/denuncias`) — triagem, investigação, resposta e auditoria.

Conteúdo cifrado em repouso (AES-256-GCM). Anexos em disco com extensão `.enc` (download somente pelo módulo autenticado).

---

## 2. Papéis e permissões

| Papel | Como obter | O que pode fazer |
|-------|-----------|------------------|
| **Denunciante (público)** | Não precisa de conta | Registrar denúncia, acompanhar por protocolo + senha |
| **Operador** | Membro de um comitê (nível secundário automático) | Dashboard, listar, ver, responder e alterar status **das denúncias dos seus comitês** |
| **Administrador** | Nível secundário manual no cadastro do usuário | Tudo do operador + **todas** as denúncias, comitês, configuração e governança LGPD |

Permissões técnicas (controllers): `WhistleblowingListReports`, `WhistleblowingViewReport`, `WhistleblowingReplyReport`, `WhistleblowingUpdateStatus`, `WhistleblowingListCommittees`, `WhistleblowingConfig`, `WhistleblowingGovernanceLgpd`, entre outras.

---

## 3. Implantação inicial (TI / Compliance)

### 3.1 Migrations

```bash
php vendor/bin/phinx migrate -c database/phinx.php
```

### 3.2 Gateway público

- Pasta `deploy/canaldenuncia/` na raiz do site (ex.: `www/canaldenuncia/`).
- Variável `URL_CANAL_DENUNCIA` no `.env` (ex.: `http://localhost/canaldenuncia/`).

### 3.3 Primeira configuração (obrigatória)

1. Acesse **Canal de Denúncias → Configuração** (`whistleblowing-config`).
2. Gere uma chave forte (mín. 32 caracteres):

   ```bash
   php -r "echo bin2hex(random_bytes(32));"
   ```

3. Salve a chave — sem ela o canal público fica **bloqueado**.
4. Cadastre **comitês** com membros e classificações.
5. Atribua nível **Administrador** aos responsáveis (Compliance/DPO).

### 3.4 Link no portal

O link para o canal público pode constar na tela de login (`login.php`), **sem** card no portal logado (preserva percepção de anonimato).

---

## 4. Uso — canal público

### Registrar denúncia

1. Acesse `/canaldenuncia`.
2. **Registrar nova denúncia** → classificação, risco, relato, envolvidos, anexos (opcional).
3. **Anote protocolo e senha** — exibidos uma única vez.

### Acompanhar

1. **Acompanhar denúncia existente** → protocolo + senha.
2. Veja status, mensagens do comitê e envie complementos.

### Limites de segurança

- **Rate limit:** após tentativas incorretas no acompanhamento, bloqueio temporário (configurável, padrão 15 min).
- **Canal indisponível:** sem chave forte configurada, páginas públicas exibem aviso.

---

## 5. Uso — gestão interna

### 5.1 Dashboard (`denuncias-dashboard`)

KPIs: totais, pendentes, críticas, distribuição por status/classificação/risco.

### 5.2 Listagem (`denuncias`)

Filtros por protocolo, status, classificação, risco, responsável, datas. Operadores veem apenas denúncias dos **seus comitês**.

### 5.3 Visualizar denúncia (`view-denuncia/{id}`)

| Área | Função |
|------|--------|
| **Relato** | Texto descriptografado do denunciante |
| **Mensagens** | Troca com denunciante + notas internas |
| **Responder** | Nova mensagem (marcar *Nota interna* se não for para o denunciante) |
| **Gestão** | Status, risco, responsável |
| **Anexos** | Download pelo ícone — descriptografa automaticamente; registrado na auditoria |
| **Linha do tempo** | Histórico de status |
| **Auditoria de acesso** | Quem visualizou/baixou internamente |

**Fluxo de status sugerido:** Recebida → Em triagem → Em análise → Comitê → Investigação → Providências → Encerrada.

### 5.4 Comitês (`list-whistleblowing-committees`)

- Nome, membros, classificações atendidas.
- Ao salvar membros → nível **Operador** automático.
- Primeiro membro pode ser responsável automático na denúncia.

---

## 6. Configuração e segurança

### Políticas LGPD (Configuração)

| Campo | Padrão | Descrição |
|-------|--------|-----------|
| Arquivar após | 5 anos | Após encerramento (`closed_at` + N anos) |
| Excluir após | 10 anos | Exclusão definitiva após encerramento + M anos |
| Retenção automática | Ativa | Rotina no **primeiro login do dia** (máx. 1×/24 h) |
| Rate limit | 5 tentativas / 15 min | Proteção do acompanhamento público |

Não é necessário Agendador de Tarefas no Windows — a retenção dispara como treinamentos e currículos.

### Chave de criptografia

- **Primeira vez:** formulário «Chave secreta» em Configuração.
- **Troca:** use **Rotação de chave** (nunca salve chave nova direto sem recriptografar).

### Rotação de chave

1. **Chave atual:** deixe em branco se houver denúncias antigas com chave legada do sistema.
2. Marque *«Tentar também chave legada do sistema»*.
3. Informe **nova chave** (mín. 32 caracteres) e confirme.
4. **Rotacionar e recriptografar** — migra relatos, mensagens, notas, nomes de anexos e arquivos `.enc`.

O sistema detecta chaves mistas (legada + forte) por registro.

### Download de anexos

- Somente via módulo interno (`view-denuncia/download-attachment/{id}`).
- Arquivo baixa com extensão correta (PDF, PNG etc.).
- Selos: **Cifrado em disco** (`.enc`) ou **Legado (sem cifra)**.

---

## 7. Governança LGPD (`whistleblowing-governance`)

- Status operacional (chave, retenção, rate limit).
- Contadores de anexos cifrados vs. legado.
- Política de retenção vigente.
- **Executar agora** — retenção manual.
- Histórico das **10 últimas execuções** + «Ver mais execuções».

---

## 8. O que ainda falta (roadmap)

| Prioridade | Item | Situação |
|------------|------|----------|
| Alta | **Deploy produção** | Migrations + arquivos + chave + comitês em `tiaraju.com.br` |
| Alta | **Notificações ao comitê** | E-mail ao receber nova denúncia (não implementado) |
| Média | **Exportação de auditoria** | Excel/PDF de acessos e alterações (não implementado) |
| Média | **Download de anexos no canal público** | Denunciante vê lista, mas download só interno hoje |
| Baixa | **Relatórios avançados** | SLA por comitê, aging, exportação do dashboard |
| Baixa | **Testes automatizados** | Cobertura E2E do fluxo público + interno |

---

## 9. Problemas comuns

| Sintoma | Causa provável | Ação |
|---------|----------------|------|
| Canal público indisponível | Chave não configurada | Configuração → salvar chave ≥ 32 chars |
| Muitas tentativas (acompanhar) | Rate limit | Aguardar janela ou ajustar em Configuração |
| Anexo não abre / sem extensão | Nome cifrado ilegível | Corrigido por inferência de extensão; atualize a página |
| Download cifrado falha | Chave trocada sem rotação | Rotação de chave com chave legada marcada |
| Rotação falha na denúncia #N | Chave atual errada | Deixe chave atual em branco + chave legada |
| Operador não vê denúncia | Outro comitê | Verificar classificação e membros do comitê |
| Lista vazia para operador | Sem comitê | Cadastrar membro em comitê ativo |

---

## 10. Referências técnicas

| Recurso | Caminho |
|---------|---------|
| Manual de ajuda (F1) | `docs/manual/content/whistleblowing/` |
| Gateway público | `deploy/canaldenuncia/index.php` |
| Uploads cifrados | `app/public/adms/uploads/whistleblowing/` |
| Retenção (login) | `WhistleblowingRetentionService::ensureUpdated()` |
| Rotação de chave | `WhistleblowingKeyRotationService` |

---

*Última atualização: julho/2026 — alinhado ao módulo whistleblowing fase 2 + governança LGPD configurável.*
