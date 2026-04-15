<?php

namespace App\adms\Controllers\rooms;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\MeetingRoomsRepository;
use App\adms\Models\Repository\RoomBookingsRepository;
use App\adms\Models\Services\RoomExternalCalendarConfig;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para dashboard administrativo de reservas
 */
class AdminBookingDashboard
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data = [];

        $roomsRepo = new MeetingRoomsRepository();
        $bookingsRepo = new RoomBookingsRepository();

        // Estatísticas gerais
        $totalRooms = $roomsRepo->count(['status' => 'active']);
        $totalBookings = $bookingsRepo->count([]);
        
        // Reservas do mês atual
        $firstDayOfMonth = new \DateTime('first day of this month');
        $lastDayOfMonth = new \DateTime('last day of this month');
        $monthStart = $firstDayOfMonth->format('Y-m-d');
        $monthEnd = $lastDayOfMonth->format('Y-m-d');
        
        $monthBookings = $bookingsRepo->getAll([
            'start_date' => $monthStart,
            'end_date' => $monthEnd
        ], 1, 1000);
        
        $confirmedBookings = $bookingsRepo->getAll(['status' => 'confirmed'], 1, 1000);
        $pendingBookings = $bookingsRepo->getAll(['status' => 'pending'], 1, 1000);
        $cancelledBookings = $bookingsRepo->getAll(['status' => 'cancelled'], 1, 1000);
        
        // Reservas por sala
        $bookingsByRoom = [];
        $allRooms = $roomsRepo->getAll(['status' => 'active'], 1, 1000);
        
        foreach ($allRooms as $room) {
            $roomBookings = $bookingsRepo->getAll(['room_id' => $room['id']], 1, 1000);
            $bookingsByRoom[$room['id']] = [
                'room' => $room,
                'count' => count($roomBookings),
                'month_count' => 0
            ];
            
            // Contar reservas do mês
            foreach ($roomBookings as $booking) {
                $bookingDate = date('Y-m-d', strtotime($booking['start_datetime']));
                if ($bookingDate >= $monthStart && $bookingDate <= $monthEnd) {
                    $bookingsByRoom[$room['id']]['month_count']++;
                }
            }
        }
        
        // Reservas recentes (últimas 10)
        $recentBookings = $bookingsRepo->getAll([], 1, 10);
        
        // Taxa de ocupação (últimos 30 dias)
        $thirtyDaysAgo = (new \DateTime('-30 days'))->format('Y-m-d');
        $today = date('Y-m-d');
        $recentBookings30Days = $bookingsRepo->getAll([
            'start_date' => $thirtyDaysAgo,
            'end_date' => $today
        ], 1, 1000);
        
        // Calcular horas totais de reserva
        $totalHours = 0;
        foreach ($recentBookings30Days as $booking) {
            $start = strtotime($booking['start_datetime']);
            $end = strtotime($booking['end_datetime']);
            $hours = ($end - $start) / 3600;
            $totalHours += $hours;
        }
        
        // Horas disponíveis (30 dias * 10 horas por dia * número de salas)
        $availableHours = 30 * 10 * $totalRooms;
        $occupancyRate = $availableHours > 0 ? ($totalHours / $availableHours) * 100 : 0;

        $this->data['total_rooms'] = $totalRooms;
        $this->data['total_bookings'] = $totalBookings;
        $this->data['month_bookings'] = count($monthBookings);
        $this->data['confirmed_bookings'] = count($confirmedBookings);
        $this->data['pending_bookings'] = count($pendingBookings);
        $this->data['cancelled_bookings'] = count($cancelledBookings);
        $this->data['bookings_by_room'] = $bookingsByRoom;
        $this->data['recent_bookings'] = $recentBookings;
        $this->data['occupancy_rate'] = round($occupancyRate, 2);
        $this->data['total_hours'] = round($totalHours, 2);

        $syncParts = [];
        if (RoomExternalCalendarConfig::isOutlookSyncEnabled()) {
            $syncParts[] = 'Outlook/Microsoft 365';
        }
        if (RoomExternalCalendarConfig::isGoogleSyncEnabled()) {
            $syncParts[] = 'Google Calendar';
        }
        $this->data['room_external_calendar_status'] = $syncParts === []
            ? 'Desativada. Configure em Salas → Administrativo → Integração calendário (Outlook / Google). A sincronização automática será ligada nessa fase.'
            : 'Ativa (definição na aplicação): ' . implode(' e ', $syncParts) . '. A sincronização automática ainda será implementada no código.';

        $pageElements = [
            'title_head' => 'Dashboard de Reservas',
            'menu' => 'admin-booking-dashboard',
            'buttonPermission' => [
                'ListMeetingRooms',
                'RoomCalendar',
                'ListBookings',
            ],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/rooms/admin_dashboard', $this->data);
        $loadView->loadView();
    }
}

