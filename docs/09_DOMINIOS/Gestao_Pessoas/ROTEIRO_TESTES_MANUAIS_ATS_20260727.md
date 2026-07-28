# Roteiro de testes manuais — ATS (convite/aceite e autorizações recentes)

- Domínio: Gestão de Pessoas / Talentos.
- Data: 27/07/2026.
- Escopo: commits recentes (ViewAll, gestor por área, log de currículo, **convite/aceite de avaliador**).
- Ambiente: homologação (prefixo `[TESTE]` em e-mail/notificação é esperado).

Marque cada caso com **OK** / **Falha** / **N/A**. Anote usuário, entrevista/vaga e evidência (print ou ID).

---

## 0. Pré-requisitos

### Contas (mínimo)

| Papel no teste | O que precisa |
|----------------|---------------|
| **RH** | ACL `RhEntrevistas`, `RhEntrevistasEdit`, `RhEntrevistasCreate`, preferencialmente `RhEntrevistasViewAll` |
| **Gestor de área** | Usuário com `user_department_id` = área da vaga **ou** responsável na árvore; **sem** ViewAll |
| **Entrevistador principal** | Usuário ativo com e-mail válido |
| **Avaliador A** | Usuário ativo com e-mail válido (convidado) |
| **Avaliador B** | Outro usuário (recusa / reenvio) |
| **Colaborador fora do escopo** | Sem ViewAll, sem vínculo com a vaga/entrevista |

### Dados

1. Confirme migration aplicada: `20260727160000` (status `convidado`/`recusado` + páginas ACL de aceite/recusa/reenvio).
2. Tenha **1 vaga** com `area_id` conhecida e um **candidato** vinculado.
3. SMTP / caixa de e-mail dos avaliadores acessível (ou pelo menos o **sino** in-app).

### Checklist pré-voo

- [ ] Consigo abrir `rh-entrevistas` como RH
- [ ] Consigo abrir a vaga e o candidato usados no teste
- [ ] Avaliadores A e B têm e-mail no cadastro

---

## 1. Convite e aceite de avaliador (prioridade)

Referência: [AVALIADORES_ENTREVISTA_EXPAND.md](AVALIADORES_ENTREVISTA_EXPAND.md).

### 1.1 Criar entrevista com adicional → convite

1. Login como **RH**.
2. `RH → Entrevistas → Cadastrar` (ou a partir do candidato).
3. Preencha candidato, vaga, data/hora, **entrevistador principal** = Principal.
4. Em **Avaliadores adicionais**, selecione **Avaliador A**.
5. Salve e abra a visualização.

| # | Esperado | Resultado |
|---|----------|-----------|
| 1.1.1 | Principal no painel com badge **ativo** | |
| 1.1.2 | Avaliador A com badge **convidado** | |
| 1.1.3 | Sino in-app do Avaliador A com link da entrevista | |
| 1.1.4 | E-mail de convite (assunto pode ter `[TESTE/HOMOLOGAÇÃO]`) | |

### 1.2 Convidado abre e aceita

1. Logout → login como **Avaliador A**.
2. Abra pelo sino **ou** `rh-entrevistas` (deve aparecer no modo related).
3. Na visualização, no painel, use **Aceitar**.

| # | Esperado | Resultado |
|---|----------|-----------|
| 1.2.1 | Convidado **consegue** abrir a entrevista | |
| 1.2.2 | Botões Aceitar/Recusar visíveis só na linha dele | |
| 1.2.3 | Após Aceitar, status vira **ativo** | |
| 1.2.4 | Mensagem de sucesso; entrevista continua acessível | |

### 1.3 Recusar convite

1. Como RH, edite a mesma entrevista (ou crie outra) e adicione **Avaliador B**.
2. Login como **Avaliador B** → abra a entrevista → **Recusar**.

| # | Esperado | Resultado |
|---|----------|-----------|
| 1.3.1 | Status **recusado** (visto pelo RH no painel) | |
| 1.3.2 | Avaliador B é redirecionado à listagem | |
| 1.3.3 | Avaliador B **não** abre mais essa entrevista (sem ViewAll/gestão) | |

### 1.4 Reenviar convite

1. Login como **RH** (quem gerencia a vaga).
2. Visualização da entrevista → linha do Avaliador B (**recusado** ou **convidado**).
3. Clique **Reenviar** e confirme.

| # | Esperado | Resultado |
|---|----------|-----------|
| 1.4.1 | Status volta para **convidado** | |
| 1.4.2 | Novo sino / e-mail para o Avaliador B | |
| 1.4.3 | Avaliador B volta a conseguir abrir e Aceitar | |

### 1.5 Principal sem convite; edição sem re-spam

1. Edite a entrevista **sem** mudar os avaliadores e salve de novo.

| # | Esperado | Resultado |
|---|----------|-----------|
| 1.5.1 | Principal permanece **ativo** (sem e-mail de “convite”) | |
| 1.5.2 | Adicional já **ativo** **não** recebe novo convite só por salvar | |
| 1.5.3 | Adicional ainda **convidado** **não** recebe spam a cada save (só no 1º sync / Reenviar) | |

### 1.6 Remover adicional do multi-select

1. Edite, remova Avaliador A da lista de adicionais, salve.

| # | Esperado | Resultado |
|---|----------|-----------|
| 1.6.1 | Linha fica **removido** (ou some do painel ativo) | |
| 1.6.2 | Avaliador A deixa de ver a entrevista no modo related | |

### 1.7 Negativas de segurança (convite)

| # | Ação | Esperado | Resultado |
|---|------|----------|-----------|
| 1.7.1 | Avaliador A tenta Aceitar na linha de outro (se UI permitir) / POST forjado | Só o próprio usuário convidado aceita | |
| 1.7.2 | Colaborador fora do escopo abre `rh-entrevistas-view/{id}` | Negado | |
| 1.7.3 | Avaliador **convidado** tenta **Editar** a entrevista | Sem botão Editar / sem `canManage` | |
| 1.7.4 | Usuário sem `RhEntrevistasReenviarConviteAvaliador` | Sem botão Reenviar no painel | |

---

## 2. Escopo ViewAll e visualização (regressão)

### 2.1 Com ViewAll (RH)

| # | Ação | Esperado | Resultado |
|---|------|----------|-----------|
| 2.1.1 | Listar entrevistas / candidatos / vagas | Vê o conjunto amplo (comportamento RH) | |
| 2.1.2 | Abrir entrevista de outra área | Consegue visualizar | |

### 2.2 Sem ViewAll (gestor / entrevistador)

| # | Ação | Esperado | Resultado |
|---|------|----------|-----------|
| 2.2.1 | Listar entrevistas | Só onde é principal, avaliador ativo/convidado ou responsável da vaga | |
| 2.2.2 | Abrir entrevista alheia | Negado | |

---

## 3. Gestor por área (`isManagerOfVaga`)

Use um **gestor** cuja área = `area_id` da vaga, **sem** ser `responsavel_id` da vaga (se possível).

| # | Ação | Esperado | Resultado |
|---|------|----------|-----------|
| 3.1 | Abrir/editar a vaga da sua área | Permitido (pipeline/edição conforme ACL) | |
| 3.2 | Abrir vaga de **outra** área (sem subordinados) | Negado | |
| 3.3 | Gestor que é chefe do **responsável** da vaga (árvore) | Continua com acesso como antes | |

---

## 4. Log de download de currículo (UI + PDF)

1. Login como usuário autorizado a ver currículos.
2. Baixe um anexo do candidato (gera log).
3. Abra a listagem/exportação do log (Excel e **PDF**, se disponível na tela).

| # | Esperado | Resultado |
|---|----------|-----------|
| 4.1 | Registro do download aparece na listagem | |
| 4.2 | Export Excel gera arquivo | |
| 4.3 | Export PDF gera arquivo legível | |
| 4.4 | Usuário sem permissão não exporta / não lista | |

---

## 5. Comunicação da entrevista com o candidato (regressão rápida)

Não confundir com o convite do **avaliador** (sino/e-mail síncrono).

| # | Ação | Esperado | Resultado |
|---|------|----------|-----------|
| 5.1 | Agendar/reagendar entrevista | Intenção no histórico (`recorded` → …) | |
| 5.2 | Comunicação `failed`/`blocked` | Botão **Reenviar** (ACL `RhEntrevistasResendComunicacao`) | |

---

## 6. Ordem sugerida (≈ 45–60 min)

1. Pré-requisitos (5 min)  
2. Casos **1.1 → 1.4** (núcleo do Expand)  
3. **1.5 → 1.7** (anti-spam e segurança)  
4. **2** e **3** (autorização)  
5. **4** e **5** se der tempo  

---

## 7. Registro de evidências

Para cada **Falha**, anote:

- URL e horário  
- Usuário logado (ID/nome)  
- ID da entrevista / vaga / candidato  
- Status no painel antes/depois  
- Mensagem de erro ou print  
- Se o e-mail chegou (ou só o sino)

---

## 8. Critério de aceite deste lote

Considere o Expand de convite **aprovado** se:

- [ ] Adicional nasce **convidado** com notificação (e e-mail se SMTP ok)
- [ ] Aceite → **ativo** e mantém acesso
- [ ] Recusa → perde acesso related
- [ ] Reenviar reabre o fluxo
- [ ] Principal não passa por convite
- [ ] Fora de escopo continua bloqueado

Demais seções (2–5) são regressão; falhas ali devem ser abertas como bugs separados.
