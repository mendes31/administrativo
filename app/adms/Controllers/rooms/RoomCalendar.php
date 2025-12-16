<?php

namespace App\adms\Controllers\rooms;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\MeetingRoomsRepository;
use App\adms\Models\Repository\RoomBookingsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para calendário geral de reservas de salas
 */
class RoomCalendar
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data = [];

        // Tipo de visualização (month ou week)
        $view = $_GET['view'] ?? 'month';
        $this->data['view'] = $view;

        // Buscar todas as salas ativas
        $roomsRepo = new MeetingRoomsRepository();
        $rooms = $roomsRepo->getAll(['status' => 'active'], 1, 1000);
        $this->data['rooms'] = $rooms;

        if ($view === 'week') {
            // Visualização semanal
            $selectedDate = $_GET['date'] ?? date('Y-m-d');
            $dateObj = new \DateTime($selectedDate);
            $dayOfWeek = (int)$dateObj->format('N'); // 1=Monday, 7=Sunday
            
            // Ajustar para segunda-feira da semana
            $startOfWeek = (clone $dateObj)->modify('-' . ($dayOfWeek - 1) . ' days');
            $endOfWeek = (clone $startOfWeek)->modify('+6 days');
            
            $startDate = $startOfWeek->format('Y-m-d');
            $endDate = $endOfWeek->format('Y-m-d');
            
            // Navegação de semanas
            $prevWeek = (clone $startOfWeek)->modify('-7 days')->format('Y-m-d');
            $nextWeek = (clone $startOfWeek)->modify('+7 days')->format('Y-m-d');
            
            $this->data['selected_date'] = $selectedDate;
            $this->data['start_of_week'] = $startOfWeek;
            $this->data['end_of_week'] = $endOfWeek;
            $this->data['prev_week'] = $prevWeek;
            $this->data['next_week'] = $nextWeek;
        } else {
            // Visualização mensal
            $selectedMonth = $_GET['month'] ?? date('Y-m');
            $firstDay = new \DateTime($selectedMonth . '-01');
            $lastDay = (clone $firstDay)->modify('last day of this month');
            
            $startDate = $firstDay->format('Y-m-d');
            $endDate = $lastDay->format('Y-m-d');
            
            $this->data['selected_month'] = $selectedMonth;
            $this->data['first_day'] = $firstDay;
            $this->data['last_day'] = $lastDay;

            // Navegação de meses
            $prevMonth = (clone $firstDay)->modify('-1 month')->format('Y-m');
            $nextMonth = (clone $firstDay)->modify('+1 month')->format('Y-m');
            $this->data['prev_month'] = $prevMonth;
            $this->data['next_month'] = $nextMonth;
        }
        
        // Buscar todas as reservas do período
        $bookingsRepo = new RoomBookingsRepository();
        $allBookings = [];
        
        foreach ($rooms as $room) {
            $roomBookings = $bookingsRepo->getBookingsByRoomAndPeriod($room['id'], $startDate, $endDate);
            foreach ($roomBookings as $booking) {
                $booking['room_name'] = $room['name'];
                $booking['room_id'] = $room['id'];
                $allBookings[] = $booking;
            }
        }

        // Organizar reservas por data e sala
        $bookingsByDateAndRoom = [];
        foreach ($allBookings as $booking) {
            $date = date('Y-m-d', strtotime($booking['start_datetime']));
            $roomId = $booking['room_id'];
            
            if (!isset($bookingsByDateAndRoom[$date])) {
                $bookingsByDateAndRoom[$date] = [];
            }
            if (!isset($bookingsByDateAndRoom[$date][$roomId])) {
                $bookingsByDateAndRoom[$date][$roomId] = [];
            }
            
            $bookingsByDateAndRoom[$date][$roomId][] = $booking;
        }

        $this->data['bookings'] = $bookingsByDateAndRoom;

        $pageElements = [
            'title_head' => 'Calendário de Reservas de Salas',
            'menu' => 'room-calendar',
            'buttonPermission' => [
                'ListMeetingRooms',
                'BookRoom',
                'CreateBooking',
            ],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/rooms/calendar', $this->data);
        $loadView->loadView();
    }
}

