# Módulo de Agendamento e Reserva de Salas de Reuniões - Proposta

## 📋 Análise de Ferramentas de Mercado

### Ferramentas Analisadas:

1. **DeskFlex** - Software de agendamento de salas de conferência
   - Visualização de calendário com disponibilidade em tempo real
   - Integração com Outlook e Google Calendar
   - Notificações automáticas
   - Controle de permissões por tipo de usuário

2. **WiseOffices** - Solução para gestão de espaços de trabalho híbrido
   - Interface amigável para reserva
   - Integração com calendários pessoais
   - Destaque de vagas liberadas recentemente
   - Modal para confirmação de vaga liberada

3. **Neptune Work** - Sistema de agendamento de salas
   - Verificação de disponibilidade em tempo real
   - Solicitação de equipamentos e serviços adicionais
   - Relatórios detalhados sobre uso e ocupação
   - Controle de conflitos de agendamento

4. **CITSmart Booking** - Plataforma digital inteligente
   - Reserva automatizada de recursos corporativos
   - Transparência no uso dos espaços
   - Notificações automáticas
   - Gestão de reservas simplificada

5. **ReservarAgora.net** - Sistema de agendamento online
   - Cadastro de equipamentos, salas, veículos
   - Personalizações (horários fixos ou intervalos)
   - Suporte a múltiplas empresas
   - Disponibilidade de equipamentos diferentes

### Funcionalidades Principais Identificadas no Mercado:

- ✅ Visualização de calendário (dia, semana, mês)
- ✅ Reserva com validação de conflitos
- ✅ Lista de espera automática
- ✅ Notificações por e-mail e in-app
- ✅ Integração com calendários (Google, Outlook)
- ✅ Controle de recursos e equipamentos
- ✅ Relatórios de uso e ocupação
- ✅ Permissões por tipo de usuário
- ✅ Destaque de vagas liberadas
- ✅ Modal de confirmação rápida

---

## 🏗️ Estrutura do Módulo Proposto

### 1. **Tabelas do Banco de Dados**

#### `adms_meeting_rooms` (Salas de Reunião)
- `id` - ID único
- `name` - Nome da sala (ex: "Sala Executiva")
- `description` - Descrição da sala
- `capacity` - Capacidade (número de pessoas)
- `location` - Localização (ex: "Bloco A - 3º Andar")
- `floor` - Andar
- `building` - Bloco/Prédio
- `image` - Imagem da sala (opcional)
- `status` - Status (ativa, inativa, manutenção)
- `requires_approval` - Requer aprovação para reserva
- `min_advance_booking_hours` - Tempo mínimo de antecedência (horas)
- `max_advance_booking_days` - Tempo máximo de antecedência (dias)
- `booking_duration_limit_hours` - Limite de duração da reserva (horas)
- `created_by` - Usuário que criou
- `created_at` - Data de criação
- `updated_at` - Data de atualização

#### `adms_room_resources` (Recursos das Salas)
- `id` - ID único
- `room_id` - FK para `adms_meeting_rooms`
- `resource_type` - Tipo de recurso (TV, Projetor, Videoconferência, etc.)
- `resource_name` - Nome do recurso (ex: "TV 65"", "Projetor 4K")
- `description` - Descrição do recurso
- `is_required` - Recurso obrigatório para reserva
- `quantity` - Quantidade disponível
- `created_at` - Data de criação
- `updated_at` - Data de atualização

#### `adms_room_bookings` (Reservas de Salas)
- `id` - ID único
- `room_id` - FK para `adms_meeting_rooms`
- `user_id` - FK para `adms_users` (solicitante)
- `title` - Título da reunião
- `description` - Descrição/Finalidade da reunião
- `start_datetime` - Data e hora de início
- `end_datetime` - Data e hora de fim
- `status` - Status (pendente, confirmada, cancelada, concluída, em_andamento)
- `requires_approval` - Requer aprovação
- `approved_by` - FK para `adms_users` (aprovador)
- `approved_at` - Data de aprovação
- `cancelled_by` - FK para `adms_users` (quem cancelou)
- `cancelled_at` - Data de cancelamento
- `cancellation_reason` - Motivo do cancelamento
- `reminder_sent` - Se lembrete foi enviado
- `reminder_sent_at` - Data do envio do lembrete
- `has_additional_requests` - Se possui solicitações adicionais (lanches, etc.)
- `created_at` - Data de criação
- `updated_at` - Data de atualização

#### `adms_booking_participants` (Participantes da Reunião)
- `id` - ID único
- `booking_id` - FK para `adms_room_bookings`
- `user_id` - FK para `adms_users`
- `is_organizer` - É o organizador
- `status` - Status (confirmado, pendente, recusado)
- `notified` - Se foi notificado
- `notified_at` - Data da notificação
- `created_at` - Data de criação

#### `adms_booking_waitlist` (Lista de Espera)
- `id` - ID único
- `room_id` - FK para `adms_meeting_rooms`
- `user_id` - FK para `adms_users`
- `desired_start_datetime` - Data/hora desejada de início
- `desired_end_datetime` - Data/hora desejada de fim
- `priority` - Prioridade na fila (ordem de inscrição)
- `status` - Status (aguardando, notificado, aceito, expirado, cancelado)
- `notified_at` - Data da notificação
- `notification_expires_at` - Data de expiração da notificação
- `response_time_limit_minutes` - Tempo limite para resposta (minutos)
- `created_at` - Data de criação
- `updated_at` - Data de atualização

#### `adms_booking_resources` (Recursos Utilizados na Reserva)
- `id` - ID único
- `booking_id` - FK para `adms_room_bookings`
- `resource_id` - FK para `adms_room_resources`
- `quantity` - Quantidade utilizada
- `created_at` - Data de criação

#### `adms_booking_additional_requests` (Solicitações Adicionais na Reserva)
- `id` - ID único
- `booking_id` - FK para `adms_room_bookings`
- `request_type` - Tipo de solicitação (lanche, equipamento_extra, limpeza, outros)
- `request_description` - Descrição detalhada da solicitação
- `quantity` - Quantidade (se aplicável, ex: número de pessoas para lanche)
- `responsible_user_id` - FK para `adms_users` (responsável por atender a solicitação)
- `status` - Status (pendente, em_preparacao, atendida, cancelada)
- `attended_at` - Data/hora em que foi atendida
- `attended_by` - FK para `adms_users` (quem atendeu)
- `notes` - Observações adicionais
- `notification_sent` - Se notificação foi enviada ao responsável
- `notification_sent_at` - Data do envio da notificação
- `created_at` - Data de criação
- `updated_at` - Data de atualização

#### `adms_request_types` (Tipos de Solicitações Adicionais)
- `id` - ID único
- `code` - Código único (ex: "lanche", "equipamento_extra")
- `name` - Nome do tipo (ex: "Lanche/Refeição", "Equipamento Extra")
- `description` - Descrição do tipo de solicitação
- `requires_responsible` - Se requer seleção de responsável
- `default_responsible_user_id` - FK para `adms_users` (responsável padrão, opcional)
- `requires_quantity` - Se requer quantidade
- `is_active` - Se está ativo
- `created_at` - Data de criação
- `updated_at` - Data de atualização

#### `adms_room_settings` (Configurações das Salas)
- `id` - ID único
- `room_id` - FK para `adms_meeting_rooms`
- `setting_key` - Chave da configuração
- `setting_value` - Valor da configuração
- `created_at` - Data de criação
- `updated_at` - Data de atualização

#### `adms_booking_notifications` (Notificações de Reservas)
- `id` - ID único
- `booking_id` - FK para `adms_room_bookings` (nullable)
- `waitlist_id` - FK para `adms_booking_waitlist` (nullable)
- `user_id` - FK para `adms_users`
- `type` - Tipo (confirmação, lembrete, cancelamento, vaga_liberada, aprovação)
- `title` - Título da notificação
- `message` - Mensagem da notificação
- `email_sent` - Se e-mail foi enviado
- `email_sent_at` - Data do envio do e-mail
- `in_app_read` - Se foi lida na aplicação
- `in_app_read_at` - Data da leitura
- `created_at` - Data de criação

#### `adms_room_availability` (Disponibilidade das Salas - Cache)
- `id` - ID único
- `room_id` - FK para `adms_meeting_rooms`
- `date` - Data
- `hour` - Hora (0-23)
- `is_available` - Se está disponível
- `booking_id` - FK para `adms_room_bookings` (se ocupada)
- `updated_at` - Data de atualização

---

## 🎯 Funcionalidades Principais

### 1. **Cadastro de Salas**

**Controller:** `app/adms/Controllers/rooms/ListMeetingRooms.php`
**Controller:** `app/adms/Controllers/rooms/CreateMeetingRoom.php`
**Controller:** `app/adms/Controllers/rooms/UpdateMeetingRoom.php`
**Controller:** `app/adms/Controllers/rooms/DeleteMeetingRoom.php`
**Controller:** `app/adms/Controllers/rooms/ViewMeetingRoom.php`

### 1.1. **Cadastro de Tipos de Solicitações**

**Controller:** `app/adms/Controllers/rooms/ListRequestTypes.php`
**Controller:** `app/adms/Controllers/rooms/CreateRequestType.php`
**Controller:** `app/adms/Controllers/rooms/UpdateRequestType.php`
**Controller:** `app/adms/Controllers/rooms/DeleteRequestType.php`

**Funcionalidades:**
- Gerenciar tipos de solicitações (Lanche/Refeição, Equipamento Extra, Limpeza, etc.)
- Definir responsável padrão para cada tipo
- Configurar se requer quantidade
- Ativar/desativar tipos

**View:** `app/adms/Views/rooms/list_request_types.php`
**View:** `app/adms/Views/rooms/create_request_type.php`
**View:** `app/adms/Views/rooms/update_request_type.php`

**Campos:**
- Nome da sala
- Descrição
- Capacidade (número de pessoas)
- Localização (Bloco/Prédio, Andar)
- Imagem da sala (upload)
- Status (Ativa, Inativa, Em Manutenção)
- Recursos disponíveis (TV, projetor, videoconferência, etc.)
- Regras de reserva (tempo mínimo/máximo de antecedência, duração máxima)

**View:** `app/adms/Views/rooms/list.php`
**View:** `app/adms/Views/rooms/create.php`
**View:** `app/adms/Views/rooms/update.php`
**View:** `app/adms/Views/rooms/view.php`

### 2. **Interface de Calendário**

**Controller:** `app/adms/Controllers/rooms/RoomCalendar.php`

**Funcionalidades:**
- Visualização por dia, semana e mês
- Exibição de horários ocupados e livres
- Cores diferentes para:
  - Reservas próprias
  - Reservas de outros usuários
  - Salas inativas
  - Vagas liberadas recentemente
- Filtros por:
  - Capacidade
  - Localização (Bloco/Andar)
  - Recursos disponíveis
  - Status da sala

**View:** `app/adms/Views/rooms/calendar.php`

**JavaScript:**
- Biblioteca de calendário (FullCalendar.js ou similar)
- Atualização em tempo real via AJAX
- Drag and drop para criar reservas (opcional)

### 3. **Criação de Reservas**

**Controller:** `app/adms/Controllers/rooms/CreateBooking.php`
**Controller:** `app/adms/Controllers/rooms/UpdateBooking.php`
**Controller:** `app/adms/Controllers/rooms/CancelBooking.php`

**Campos:**
- Seleção da sala (dropdown com filtros)
- Data e horário de início/fim
- Título da reunião
- Descrição/Finalidade
- Lista de participantes (multiselect de usuários)
- Recursos adicionais necessários
- Validação automática de conflitos

**Validações:**
- Verificar se a sala está disponível no horário
- Verificar se o usuário tem permissão para reservar
- Verificar regras de antecedência mínima/máxima
- Verificar duração máxima permitida
- Verificar se recursos solicitados estão disponíveis

**View:** `app/adms/Views/rooms/create_booking.php`
**View:** `app/adms/Views/rooms/update_booking.php`

### 4. **Política de Desistência**

**Controller:** `app/adms/Controllers/rooms/CancelBooking.php`

**Funcionalidades:**
- Usuário pode cancelar sua própria reserva
- Sistema verifica automaticamente se há lista de espera
- Se existir lista de espera:
  - Notificar automaticamente os interessados (ordem de inscrição)
  - Primeiro a confirmar fica com a reserva
  - Tempo limite configurável para resposta

**Regras:**
- Cancelamento pode ser feito até X horas antes (configurável)
- Após cancelamento, vaga fica disponível imediatamente
- Notificação automática para lista de espera

### 5. **Lista de Espera**

**Controller:** `app/adms/Controllers/rooms/JoinWaitlist.php`
**Controller:** `app/adms/Controllers/rooms/LeaveWaitlist.php`
**Controller:** `app/adms/Controllers/rooms/ConfirmWaitlistBooking.php`

**Funcionalidades:**
- Ao tentar reservar horário ocupado, opção de entrar na fila
- Notificações enviadas via:
  - E-mail (usando `SendEmailService`)
  - Notificação in-app (toast/modal)
- Tempo limite configurável para resposta após liberação
- Ordem de prioridade (primeiro a entrar, primeiro a ser notificado)

**Fluxo:**
1. Usuário tenta reservar horário ocupado
2. Sistema oferece opção "Entrar na Lista de Espera"
3. Usuário confirma entrada na lista
4. Quando vaga é liberada:
   - Sistema notifica primeiro da fila
   - Usuário tem X minutos para confirmar
   - Se não confirmar, notifica próximo da fila
   - Se confirmar, cria reserva automaticamente

**View:** `app/adms/Views/rooms/waitlist.php`

### 6. **Painel Administrativo**

**Controller:** `app/adms/Controllers/rooms/AdminDashboard.php`
**Controller:** `app/adms/Controllers/rooms/AdminBookings.php`

**Funcionalidades:**
- Gerenciamento completo de salas
- Visualização de todas as reservas (filtros avançados)
- Cancelamento forçado (administrador)
- Configuração de regras globais:
  - Tempo mínimo de antecedência para reservar
  - Tempo mínimo para cancelar
  - Tempo limite para aceitar vaga liberada
  - Duração máxima de reserva
- Relatórios:
  - Taxa de ocupação por sala
  - Horários mais utilizados
  - Recursos mais solicitados
  - Usuários que mais reservam
  - Histórico de cancelamentos

**View:** `app/adms/Views/rooms/admin_dashboard.php`
**View:** `app/adms/Views/rooms/admin_bookings.php`

### 7. **Alertas e Notificações**

**Service:** `app/adms/Models/Services/BookingNotificationService.php`

**Tipos de Notificação:**
1. **Confirmação de Reserva**
   - Enviada imediatamente após criação
   - Inclui detalhes da reserva (sala, horário, participantes)

2. **Lembrete Antes da Reunião**
   - Configurável (ex: 15 minutos, 1 hora, 1 dia antes)
   - Enviada automaticamente via cron job ou evento agendado

3. **Notificação de Desistência**
   - Enviada quando organizador cancela
   - Notifica participantes da reunião

4. **Notificação para Lista de Espera**
   - Enviada quando vaga é liberada
   - Inclui link para confirmação rápida
   - Tempo limite para resposta

5. **Notificação de Aprovação**
   - Enviada quando reserva requer aprovação
   - Notifica aprovador e solicitante

6. **Notificação de Solicitação Adicional**
   - **Enviada ao responsável selecionado** quando há solicitações (lanches, equipamentos, etc.)
   - Inclui:
     - Detalhes da reserva (sala, data, horário)
     - Tipo de solicitação
     - Descrição detalhada
     - Quantidade (se aplicável)
     - Informações do solicitante
     - Link para marcar como atendida
   - Enviada imediatamente após criação da reserva
   - Cópia enviada ao solicitante confirmando que a solicitação foi encaminhada

**Implementação:**
- Usar `SendEmailService` existente para e-mails
- Criar tabela `adms_booking_notifications` para notificações in-app
- Criar componente JavaScript para exibir notificações (toast/notification bell)

---

## 🔧 Requisitos Técnicos

### Backend
- **PHP 8.3+** (já utilizado no projeto)
- **MySQL** (já utilizado no projeto)
- **Phinx** para migrações (já utilizado no projeto)
- **MVC Pattern** (já utilizado no projeto)

### Frontend
- **Bootstrap 5** (já utilizado no projeto)
- **JavaScript (Vanilla ou jQuery)** (já utilizado no projeto)
- **FullCalendar.js** para visualização de calendário
- **AJAX** para atualizações em tempo real

### Integrações
- **Sistema de E-mail** (usar `SendEmailService` existente)
- **Sistema de Permissões** (usar `PageLayoutService` existente)
- **Sistema de Usuários** (usar `UsersRepository` existente)

### Notificações
- **E-mail:** Usar `SendEmailService` existente
- **In-App:** Criar sistema de notificações usando tabela `adms_booking_notifications`
- **Tempo Real:** Usar polling AJAX ou WebSockets (futuro)

---

## 📊 Fluxo de Lógica

### Fluxo 1: Criação de Reserva

```
Usuário acessa calendário
  ↓
Seleciona sala e horário
  ↓
Sistema valida disponibilidade
  ↓
Se disponível:
  → Preenche formulário (título, descrição, participantes)
  → Seleciona recursos necessários
  → Adiciona solicitações adicionais (se necessário):
    → Tipo de solicitação (ex: Lanche/Refeição)
    → Descrição detalhada
    → Quantidade (ex: 12 pessoas)
    → Seleciona responsável (dropdown de usuários)
  → Submete reserva
  ↓
Sistema cria reserva
  ↓
Se possui solicitações adicionais:
  → Cria registros em `adms_booking_additional_requests`
  → Envia e-mail ao responsável selecionado com:
    - Detalhes da reserva
    - Tipo e descrição da solicitação
    - Quantidade
    - Link para marcar como atendida
  → Envia cópia ao solicitante confirmando encaminhamento
  ↓
Se requer aprovação:
  → Notifica aprovador
  → Status: "Pendente"
  ↓
Se não requer aprovação:
  → Status: "Confirmada"
  → Notifica participantes
  → Envia e-mail de confirmação
```

### Fluxo 2: Tentativa de Reserva em Horário Ocupado

```
Usuário tenta reservar horário ocupado
  ↓
Sistema detecta conflito
  ↓
Oferece opção "Entrar na Lista de Espera"
  ↓
Se usuário aceita:
  → Adiciona à lista de espera
  → Define prioridade (ordem de inscrição)
  → Notifica usuário que está na lista
```

### Fluxo 3: Cancelamento e Liberação de Vaga

```
Organizador cancela reserva
  ↓
Sistema atualiza status para "Cancelada"
  ↓
Sistema verifica lista de espera para aquele horário
  ↓
Se existe lista de espera:
  → Notifica primeiro da fila
  → Define tempo limite para resposta
  ↓
Se usuário confirma dentro do prazo:
  → Cria reserva automaticamente
  → Remove da lista de espera
  → Notifica participantes
  ↓
Se usuário não confirma:
  → Notifica próximo da fila
  → Repete processo
```

### Fluxo 4: Lembrete Antes da Reunião

```
Cron job executa a cada X minutos
  ↓
Busca reservas confirmadas com início em Y minutos
  ↓
Para cada reserva encontrada:
  → Verifica se lembrete já foi enviado
  → Se não foi enviado:
    → Envia e-mail de lembrete
    → Envia notificação in-app
    → Marca como enviado
```

---

## 🎨 Wireframes e Interface

### Tela de Calendário Principal

```
┌─────────────────────────────────────────────────────────┐
│  [MeetingHub] Sistema de Reservas    [🔔 2] [⚙️] [👤]   │
├─────────────────────────────────────────────────────────┤
│  Reserva de Salas                                       │
│  Encontre e reserve salas de reunião para sua equipe   │
│                                                          │
│  [Salas] [Calendário]                                   │
│                                                          │
│  [🔍 Buscar salas...] [Todas ▼] [Todas localizações ▼] │
│  [Recursos ▼]                                            │
│                                                          │
│  ┌──────────────────────────────────────────────────┐   │
│  │  CALENDÁRIO (Visualização Semanal)              │   │
│  │  [◀] Dezembro 2024 [▶]                          │   │
│  │                                                  │   │
│  │  Seg  Ter  Qua  Qui  Sex                        │   │
│  │  ┌──┐ ┌──┐ ┌──┐ ┌──┐ ┌──┐                      │   │
│  │  │09│ │10│ │11│ │12│ │13│                      │   │
│  │  └──┘ └──┘ └──┘ └──┘ └──┘                      │   │
│  │                                                  │   │
│  │  08:00 [Sala Executiva - João]                  │   │
│  │  09:00 [Sala Executiva - João]                  │   │
│  │  10:00 [Sala Criativa - Maria]                  │   │
│  │  11:00 [Disponível] [Reservar]                  │   │
│  │  12:00 [Disponível] [Reservar]                  │   │
│  │  ...                                             │   │
│  └──────────────────────────────────────────────────┘   │
│                                                          │
│  ┌──────────────────────────────────────────────────┐   │
│  │  💡 Dica rápida                                  │   │
│  │  Ao tentar reservar um horário ocupado, você    │   │
│  │  pode entrar na lista de espera e será notificado│   │
│  │  automaticamente quando uma vaga for liberada.  │   │
│  └──────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────┘
```

### Tela de Detalhes da Sala

```
┌─────────────────────────────────────────────────────────┐
│  [← Voltar]  Sala Executiva                            │
├─────────────────────────────────────────────────────────┤
│  [Imagem da Sala]                                       │
│                                                          │
│  📍 Bloco A - 3º Andar                                  │
│  👥 Capacidade: 12 pessoas                              │
│  ✅ Status: Disponível                                   │
│                                                          │
│  Recursos Disponíveis:                                  │
│  • TV 65"                                               │
│  • Videoconferência                                     │
│  • Lousa Digital                                        │
│  • Sistema de Som                                       │
│                                                          │
│  Horários Disponíveis Hoje:                             │
│  ┌──────────────────────────────────────────────────┐  │
│  │  08:00 - 09:00  [Disponível] [Reservar]         │  │
│  │  09:00 - 10:00  [Ocupado - João Silva]            │  │
│  │  10:00 - 11:00  [Disponível] [Reservar]          │  │
│  │  11:00 - 12:00  [Disponível] [Reservar]          │  │
│  │  12:00 - 13:00  [Disponível] [Reservar]          │  │
│  └──────────────────────────────────────────────────┘  │
│                                                          │
│  [Reservar Esta Sala]                                   │
└─────────────────────────────────────────────────────────┘
```

### Modal de Criação de Reserva

```
┌─────────────────────────────────────────────────────────┐
│  Nova Reserva                                    [X]     │
├─────────────────────────────────────────────────────────┤
│  Sala: Sala Executiva                                   │
│  Data: 13/12/2024                                       │
│                                                          │
│  Horário de Início: [14:00 ▼]                          │
│  Horário de Término: [15:00 ▼]                         │
│                                                          │
│  Título da Reunião:                                     │
│  [Reunião de Planejamento 2025        ]                │
│                                                          │
│  Descrição:                                             │
│  [Discussão sobre planejamento estratégico...]          │
│                                                          │
│  Participantes:                                         │
│  [Selecione os participantes... ▼]                      │
│  • João Silva                                           │
│  • Maria Santos                                         │
│                                                          │
│  Recursos Necessários:                                  │
│  ☑ TV 65"                                               │
│  ☑ Videoconferência                                     │
│  ☐ Lousa Digital                                        │
│                                                          │
│  ─────────────────────────────────────────────────────  │
│  Solicitações Adicionais                                │
│  [+ Adicionar Solicitação]                              │
│                                                          │
│  Solicitação 1:                                         │
│  Tipo: [Lanche/Refeição ▼]                             │
│  Descrição: [Café da manhã para 12 pessoas...]        │
│  Quantidade: [12] pessoas                               │
│  Responsável: [Selecione o responsável... ▼]            │
│  [Remover]                                              │
│                                                          │
│  [Cancelar]  [Reservar]                                 │
└─────────────────────────────────────────────────────────┘
```

### Modal de Lista de Espera

```
┌─────────────────────────────────────────────────────────┐
│  Horário Ocupado                                 [X]     │
├─────────────────────────────────────────────────────────┤
│  ⚠️ Este horário já está reservado.                     │
│                                                          │
│  Sala: Sala Executiva                                   │
│  Data: 13/12/2024                                       │
│  Horário: 14:00 - 15:00                                 │
│                                                          │
│  Reservado por: João Silva                              │
│                                                          │
│  Deseja entrar na lista de espera?                      │
│  Você será notificado automaticamente caso a vaga seja  │
│  liberada.                                              │
│                                                          │
│  [Cancelar]  [Entrar na Lista de Espera]                │
└─────────────────────────────────────────────────────────┘
```

### Modal de Notificação de Vaga Liberada

```
┌─────────────────────────────────────────────────────────┐
│  🎉 Vaga Liberada!                               [X]     │
├─────────────────────────────────────────────────────────┤
│  Uma vaga na lista de espera foi liberada!               │
│                                                          │
│  Sala: Sala Executiva                                   │
│  Data: 13/12/2024                                       │
│  Horário: 14:00 - 15:00                                 │
│                                                          │
│  Você tem 15 minutos para confirmar esta reserva.       │
│                                                          │
│  [Recusar]  [Confirmar Reserva]                         │
└─────────────────────────────────────────────────────────┘
```

---

## 🔐 Considerações de Segurança e Permissões

### Níveis de Acesso

1. **Super Administrador**
   - Acesso total ao módulo
   - Pode criar, editar e excluir salas
   - Pode cancelar qualquer reserva
   - Acesso a relatórios administrativos

2. **Administrador de Salas**
   - Pode gerenciar salas (criar, editar, excluir)
   - Pode visualizar todas as reservas
   - Pode cancelar reservas de outros usuários
   - Acesso a relatórios

3. **Usuário Comum**
   - Pode visualizar calendário e salas
   - Pode criar reservas
   - Pode cancelar apenas suas próprias reservas
   - Pode entrar na lista de espera

### Validações de Segurança

- Verificar permissões antes de criar/editar/cancelar reservas
- Validar que usuário só pode cancelar suas próprias reservas (exceto admin)
- Validar CSRF token em todas as ações
- Sanitizar inputs para prevenir SQL injection e XSS
- Validar datas e horários no servidor (não confiar apenas no frontend)

---

## 📈 Relatórios e Analytics

### Relatórios Disponíveis

1. **Taxa de Ocupação por Sala**
   - Percentual de ocupação por período
   - Gráficos de linha e barras
   - Comparação entre salas

2. **Horários Mais Utilizados**
   - Distribuição de reservas por horário
   - Identificar picos de uso

3. **Recursos Mais Solicitados**
   - Ranking de recursos mais utilizados
   - Identificar necessidade de novos recursos

4. **Usuários que Mais Reservam**
   - Ranking de usuários por número de reservas
   - Identificar padrões de uso

5. **Histórico de Cancelamentos**
   - Taxa de cancelamento
   - Motivos mais comuns
   - Horários com maior taxa de cancelamento

**Controller:** `app/adms/Controllers/rooms/BookingReports.php`
**View:** `app/adms/Views/rooms/reports.php`

---

## 🚀 Fases de Implementação

### Fase 1: Base (2-3 semanas)
- ✅ Criação de tabelas (migrations)
- ✅ CRUD de Salas
- ✅ CRUD de Recursos
- ✅ Interface básica de calendário
- ✅ Criação de reservas simples

### Fase 2: Funcionalidades Core (2-3 semanas)
- ✅ Validação de conflitos
- ✅ Lista de espera básica
- ✅ Notificações por e-mail
- ✅ Cancelamento de reservas
- ✅ Visualização de reservas próprias

### Fase 3: Melhorias e Polimento (1-2 semanas)
- ✅ Notificações in-app
- ✅ Lembretes automáticos
- ✅ Relatórios básicos
- ✅ Filtros avançados
- ✅ Melhorias de UX

### Fase 4: Recursos Avançados (2-3 semanas)
- ✅ Integração com calendários externos (Google, Outlook)
- ✅ Aprovação de reservas
- ✅ Relatórios avançados
- ✅ Dashboard administrativo
- ✅ Exportação de dados

**Tempo Total Estimado: 7-11 semanas**

---

## 📝 Próximos Passos

1. **Aprovação da Proposta**
   - Revisar e aprovar estrutura proposta
   - Definir prioridades de funcionalidades

2. **Criação das Migrations**
   - Criar todas as tabelas necessárias
   - Definir relacionamentos e índices

3. **Desenvolvimento Incremental**
   - Seguir fases de implementação
   - Testes contínuos
   - Feedback do usuário

4. **Documentação**
   - Documentar APIs e endpoints
   - Criar guia do usuário
   - Documentar regras de negócio

---

## ✅ Checklist de Validação com Mercado

- [x] Visualização de calendário (dia, semana, mês)
- [x] Reserva com validação de conflitos
- [x] Lista de espera automática
- [x] Notificações por e-mail e in-app
- [ ] Integração com calendários (Google, Outlook) - Fase 4
- [x] Controle de recursos e equipamentos
- [x] Relatórios de uso e ocupação
- [x] Permissões por tipo de usuário
- [x] Destaque de vagas liberadas
- [x] Modal de confirmação rápida
- [x] Interface intuitiva e moderna
- [x] Suporte a múltiplas salas e recursos

---

**Documento criado em:** 08/12/2024  
**Versão:** 1.0  
**Autor:** Sistema Administrativo

