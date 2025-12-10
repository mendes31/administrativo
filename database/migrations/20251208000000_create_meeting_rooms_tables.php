<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateMeetingRoomsTables extends AbstractMigration
{
    public function change(): void
    {
        // Tabela de Salas de Reunião
        if (!$this->hasTable('adms_meeting_rooms')) {
            $table = $this->table('adms_meeting_rooms', ['id' => 'id', 'primary_key' => ['id']]);
            
            $table
                ->addColumn('name', 'string', ['limit' => 255, 'null' => false, 'comment' => 'Nome da sala'])
                ->addColumn('description', 'text', ['null' => true, 'comment' => 'Descrição da sala'])
                ->addColumn('capacity', 'integer', ['null' => false, 'default' => 1, 'comment' => 'Capacidade (número de pessoas)'])
                ->addColumn('location', 'string', ['limit' => 255, 'null' => true, 'comment' => 'Localização (ex: Bloco A - 3º Andar)'])
                ->addColumn('floor', 'string', ['limit' => 50, 'null' => true, 'comment' => 'Andar'])
                ->addColumn('building', 'string', ['limit' => 100, 'null' => true, 'comment' => 'Bloco/Prédio'])
                ->addColumn('image', 'string', ['limit' => 500, 'null' => true, 'comment' => 'Caminho da imagem da sala'])
                ->addColumn('status', 'string', ['limit' => 20, 'default' => 'active', 'comment' => 'Status: active, inactive, maintenance'])
                ->addColumn('requires_approval', 'boolean', ['default' => false, 'comment' => 'Requer aprovação para reserva'])
                ->addColumn('min_advance_booking_hours', 'integer', ['null' => true, 'comment' => 'Tempo mínimo de antecedência (horas)'])
                ->addColumn('max_advance_booking_days', 'integer', ['null' => true, 'comment' => 'Tempo máximo de antecedência (dias)'])
                ->addColumn('booking_duration_limit_hours', 'integer', ['null' => true, 'comment' => 'Limite de duração da reserva (horas)'])
                ->addColumn('created_by', 'integer', ['signed' => false, 'null' => false, 'comment' => 'ID do usuário que criou'])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'update' => 'CURRENT_TIMESTAMP'])
                
                ->addIndex(['status'])
                ->addIndex(['building', 'floor'])
                ->addIndex(['created_by'])
                
                ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
                
                ->create();
        }
        
        // Tabela de Recursos das Salas
        if (!$this->hasTable('adms_room_resources')) {
            $table = $this->table('adms_room_resources', ['id' => 'id', 'primary_key' => ['id']]);
            
            $table
                ->addColumn('room_id', 'integer', ['signed' => false, 'null' => false, 'comment' => 'ID da sala'])
                ->addColumn('resource_type', 'string', ['limit' => 100, 'null' => false, 'comment' => 'Tipo de recurso'])
                ->addColumn('resource_name', 'string', ['limit' => 255, 'null' => false, 'comment' => 'Nome do recurso'])
                ->addColumn('description', 'text', ['null' => true, 'comment' => 'Descrição do recurso'])
                ->addColumn('is_required', 'boolean', ['default' => false, 'comment' => 'Recurso obrigatório para reserva'])
                ->addColumn('quantity', 'integer', ['default' => 1, 'comment' => 'Quantidade disponível'])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'update' => 'CURRENT_TIMESTAMP'])
                
                ->addIndex(['room_id'])
                ->addIndex(['resource_type'])
                
                ->addForeignKey('room_id', 'adms_meeting_rooms', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                
                ->create();
        }
        
        // Tabela de Reservas de Salas
        if (!$this->hasTable('adms_room_bookings')) {
            $table = $this->table('adms_room_bookings', ['id' => 'id', 'primary_key' => ['id']]);
            
            $table
                ->addColumn('room_id', 'integer', ['signed' => false, 'null' => false, 'comment' => 'ID da sala'])
                ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false, 'comment' => 'ID do solicitante'])
                ->addColumn('title', 'string', ['limit' => 255, 'null' => false, 'comment' => 'Título da reunião'])
                ->addColumn('description', 'text', ['null' => true, 'comment' => 'Descrição/Finalidade da reunião'])
                ->addColumn('start_datetime', 'datetime', ['null' => false, 'comment' => 'Data e hora de início'])
                ->addColumn('end_datetime', 'datetime', ['null' => false, 'comment' => 'Data e hora de fim'])
                ->addColumn('status', 'string', ['limit' => 20, 'default' => 'pending', 'comment' => 'Status: pending, confirmed, cancelled, completed, in_progress'])
                ->addColumn('requires_approval', 'boolean', ['default' => false, 'comment' => 'Requer aprovação'])
                ->addColumn('approved_by', 'integer', ['signed' => false, 'null' => true, 'comment' => 'ID do aprovador'])
                ->addColumn('approved_at', 'datetime', ['null' => true, 'comment' => 'Data de aprovação'])
                ->addColumn('cancelled_by', 'integer', ['signed' => false, 'null' => true, 'comment' => 'ID de quem cancelou'])
                ->addColumn('cancelled_at', 'datetime', ['null' => true, 'comment' => 'Data de cancelamento'])
                ->addColumn('cancellation_reason', 'text', ['null' => true, 'comment' => 'Motivo do cancelamento'])
                ->addColumn('reminder_sent', 'boolean', ['default' => false, 'comment' => 'Se lembrete foi enviado'])
                ->addColumn('reminder_sent_at', 'datetime', ['null' => true, 'comment' => 'Data do envio do lembrete'])
                ->addColumn('has_additional_requests', 'boolean', ['default' => false, 'comment' => 'Se possui solicitações adicionais'])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'update' => 'CURRENT_TIMESTAMP'])
                
                ->addIndex(['room_id'])
                ->addIndex(['user_id'])
                ->addIndex(['status'])
                ->addIndex(['start_datetime', 'end_datetime'])
                ->addIndex(['approved_by'])
                ->addIndex(['cancelled_by'])
                
                ->addForeignKey('room_id', 'adms_meeting_rooms', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
                ->addForeignKey('user_id', 'adms_users', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
                ->addForeignKey('approved_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
                ->addForeignKey('cancelled_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
                
                ->create();
        }
        
        // Tabela de Participantes da Reunião
        if (!$this->hasTable('adms_booking_participants')) {
            $table = $this->table('adms_booking_participants', ['id' => 'id', 'primary_key' => ['id']]);
            
            $table
                ->addColumn('booking_id', 'integer', ['signed' => false, 'null' => false, 'comment' => 'ID da reserva'])
                ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false, 'comment' => 'ID do participante'])
                ->addColumn('is_organizer', 'boolean', ['default' => false, 'comment' => 'É o organizador'])
                ->addColumn('status', 'string', ['limit' => 20, 'default' => 'pending', 'comment' => 'Status: confirmed, pending, declined'])
                ->addColumn('notified', 'boolean', ['default' => false, 'comment' => 'Se foi notificado'])
                ->addColumn('notified_at', 'datetime', ['null' => true, 'comment' => 'Data da notificação'])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                
                ->addIndex(['booking_id'])
                ->addIndex(['user_id'])
                ->addIndex(['status'])
                
                ->addForeignKey('booking_id', 'adms_room_bookings', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                ->addForeignKey('user_id', 'adms_users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                
                ->create();
        }
        
        // Tabela de Lista de Espera
        if (!$this->hasTable('adms_booking_waitlist')) {
            $table = $this->table('adms_booking_waitlist', ['id' => 'id', 'primary_key' => ['id']]);
            
            $table
                ->addColumn('room_id', 'integer', ['signed' => false, 'null' => false, 'comment' => 'ID da sala'])
                ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false, 'comment' => 'ID do usuário'])
                ->addColumn('desired_start_datetime', 'datetime', ['null' => false, 'comment' => 'Data/hora desejada de início'])
                ->addColumn('desired_end_datetime', 'datetime', ['null' => false, 'comment' => 'Data/hora desejada de fim'])
                ->addColumn('priority', 'integer', ['default' => 0, 'comment' => 'Prioridade na fila (ordem de inscrição)'])
                ->addColumn('status', 'string', ['limit' => 20, 'default' => 'waiting', 'comment' => 'Status: waiting, notified, accepted, expired, cancelled'])
                ->addColumn('notified_at', 'datetime', ['null' => true, 'comment' => 'Data da notificação'])
                ->addColumn('notification_expires_at', 'datetime', ['null' => true, 'comment' => 'Data de expiração da notificação'])
                ->addColumn('response_time_limit_minutes', 'integer', ['default' => 15, 'comment' => 'Tempo limite para resposta (minutos)'])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'update' => 'CURRENT_TIMESTAMP'])
                
                ->addIndex(['room_id'])
                ->addIndex(['user_id'])
                ->addIndex(['status'])
                ->addIndex(['priority'])
                ->addIndex(['desired_start_datetime', 'desired_end_datetime'])
                
                ->addForeignKey('room_id', 'adms_meeting_rooms', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                ->addForeignKey('user_id', 'adms_users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                
                ->create();
        }
        
        // Tabela de Recursos Utilizados na Reserva
        if (!$this->hasTable('adms_booking_resources')) {
            $table = $this->table('adms_booking_resources', ['id' => 'id', 'primary_key' => ['id']]);
            
            $table
                ->addColumn('booking_id', 'integer', ['signed' => false, 'null' => false, 'comment' => 'ID da reserva'])
                ->addColumn('resource_id', 'integer', ['signed' => false, 'null' => false, 'comment' => 'ID do recurso'])
                ->addColumn('quantity', 'integer', ['default' => 1, 'comment' => 'Quantidade utilizada'])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                
                ->addIndex(['booking_id'])
                ->addIndex(['resource_id'])
                
                ->addForeignKey('booking_id', 'adms_room_bookings', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                ->addForeignKey('resource_id', 'adms_room_resources', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
                
                ->create();
        }
        
        // Tabela de Solicitações Adicionais na Reserva
        if (!$this->hasTable('adms_booking_additional_requests')) {
            $table = $this->table('adms_booking_additional_requests', ['id' => 'id', 'primary_key' => ['id']]);
            
            $table
                ->addColumn('booking_id', 'integer', ['signed' => false, 'null' => false, 'comment' => 'ID da reserva'])
                ->addColumn('request_type', 'string', ['limit' => 50, 'null' => false, 'comment' => 'Tipo de solicitação'])
                ->addColumn('request_description', 'text', ['null' => false, 'comment' => 'Descrição detalhada da solicitação'])
                ->addColumn('quantity', 'integer', ['null' => true, 'comment' => 'Quantidade (se aplicável)'])
                ->addColumn('responsible_user_id', 'integer', ['signed' => false, 'null' => false, 'comment' => 'ID do responsável por atender'])
                ->addColumn('status', 'string', ['limit' => 20, 'default' => 'pending', 'comment' => 'Status: pending, in_preparation, attended, cancelled'])
                ->addColumn('attended_at', 'datetime', ['null' => true, 'comment' => 'Data/hora em que foi atendida'])
                ->addColumn('attended_by', 'integer', ['signed' => false, 'null' => true, 'comment' => 'ID de quem atendeu'])
                ->addColumn('notes', 'text', ['null' => true, 'comment' => 'Observações adicionais'])
                ->addColumn('notification_sent', 'boolean', ['default' => false, 'comment' => 'Se notificação foi enviada ao responsável'])
                ->addColumn('notification_sent_at', 'datetime', ['null' => true, 'comment' => 'Data do envio da notificação'])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'update' => 'CURRENT_TIMESTAMP'])
                
                ->addIndex(['booking_id'])
                ->addIndex(['responsible_user_id'])
                ->addIndex(['status'])
                ->addIndex(['request_type'])
                
                ->addForeignKey('booking_id', 'adms_room_bookings', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                ->addForeignKey('responsible_user_id', 'adms_users', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
                ->addForeignKey('attended_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
                
                ->create();
        }
        
        // Tabela de Notificações de Reservas
        if (!$this->hasTable('adms_booking_notifications')) {
            $table = $this->table('adms_booking_notifications', ['id' => 'id', 'primary_key' => ['id']]);
            
            $table
                ->addColumn('booking_id', 'integer', ['signed' => false, 'null' => true, 'comment' => 'ID da reserva'])
                ->addColumn('waitlist_id', 'integer', ['signed' => false, 'null' => true, 'comment' => 'ID da lista de espera'])
                ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false, 'comment' => 'ID do usuário'])
                ->addColumn('type', 'string', ['limit' => 50, 'null' => false, 'comment' => 'Tipo: confirmation, reminder, cancellation, vacancy_released, approval, additional_request'])
                ->addColumn('title', 'string', ['limit' => 255, 'null' => false, 'comment' => 'Título da notificação'])
                ->addColumn('message', 'text', ['null' => false, 'comment' => 'Mensagem da notificação'])
                ->addColumn('email_sent', 'boolean', ['default' => false, 'comment' => 'Se e-mail foi enviado'])
                ->addColumn('email_sent_at', 'datetime', ['null' => true, 'comment' => 'Data do envio do e-mail'])
                ->addColumn('in_app_read', 'boolean', ['default' => false, 'comment' => 'Se foi lida na aplicação'])
                ->addColumn('in_app_read_at', 'datetime', ['null' => true, 'comment' => 'Data da leitura'])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                
                ->addIndex(['booking_id'])
                ->addIndex(['waitlist_id'])
                ->addIndex(['user_id'])
                ->addIndex(['type'])
                ->addIndex(['email_sent'])
                ->addIndex(['in_app_read'])
                
                ->addForeignKey('booking_id', 'adms_room_bookings', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                ->addForeignKey('waitlist_id', 'adms_booking_waitlist', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                ->addForeignKey('user_id', 'adms_users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                
                ->create();
        }
        
        // Tabela de Configurações das Salas
        if (!$this->hasTable('adms_room_settings')) {
            $table = $this->table('adms_room_settings', ['id' => 'id', 'primary_key' => ['id']]);
            
            $table
                ->addColumn('room_id', 'integer', ['signed' => false, 'null' => false, 'comment' => 'ID da sala'])
                ->addColumn('setting_key', 'string', ['limit' => 100, 'null' => false, 'comment' => 'Chave da configuração'])
                ->addColumn('setting_value', 'text', ['null' => true, 'comment' => 'Valor da configuração'])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'update' => 'CURRENT_TIMESTAMP'])
                
                ->addIndex(['room_id', 'setting_key'], ['unique' => true])
                
                ->addForeignKey('room_id', 'adms_meeting_rooms', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                
                ->create();
        }
        
        // Tabela de Disponibilidade das Salas (Cache)
        if (!$this->hasTable('adms_room_availability')) {
            $table = $this->table('adms_room_availability', ['id' => 'id', 'primary_key' => ['id']]);
            
            $table
                ->addColumn('room_id', 'integer', ['signed' => false, 'null' => false, 'comment' => 'ID da sala'])
                ->addColumn('date', 'date', ['null' => false, 'comment' => 'Data'])
                ->addColumn('hour', 'integer', ['null' => false, 'comment' => 'Hora (0-23)'])
                ->addColumn('is_available', 'boolean', ['default' => true, 'comment' => 'Se está disponível'])
                ->addColumn('booking_id', 'integer', ['signed' => false, 'null' => true, 'comment' => 'ID da reserva (se ocupada)'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'update' => 'CURRENT_TIMESTAMP'])
                
                ->addIndex(['room_id', 'date', 'hour'], ['unique' => true])
                ->addIndex(['is_available'])
                ->addIndex(['booking_id'])
                
                ->addForeignKey('room_id', 'adms_meeting_rooms', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                ->addForeignKey('booking_id', 'adms_room_bookings', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
                
                ->create();
        }
    }
}

