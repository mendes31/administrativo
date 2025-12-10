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

        // Período do calendário (mês atual)
        $selectedMonth = $_GET['month'] ?? date('Y-m');
        $firstDay = new \DateTime($selectedMonth . '-01');
        $lastDay = (clone $firstDay)->modify('last day of this month');
        
        $startDate = $firstDay->format('Y-m-d');
        $endDate = $lastDay->format('Y-m-d');

        // Buscar todas as salas ativas
        $roomsRepo = new MeetingRoomsRepository();
        $rooms = $roomsRepo->getAll(['status' => 'active'], 1, 1000);
        
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

        $this->data['rooms'] = $rooms;
        $this->data['selected_month'] = $selectedMonth;
        $this->data['bookings'] = $bookingsByDateAndRoom;
        $this->data['first_day'] = $firstDay;
        $this->data['last_day'] = $lastDay;

        // Navegação de meses
        $prevMonth = (clone $firstDay)->modify('-1 month')->format('Y-m');
        $nextMonth = (clone $firstDay)->modify('+1 month')->format('Y-m');
        $this->data['prev_month'] = $prevMonth;
        $this->data['next_month'] = $nextMonth;

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

