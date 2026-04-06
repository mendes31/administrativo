<?php

namespace App\adms\Controllers\rooms;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Repository\MeetingRoomsRepository;
use App\adms\Models\Repository\RoomBookingsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para reservar sala via calendário visual
 */
class BookRoom
{
    private array|string|null $data = null;

    public function index(string|int|null $roomId = null): void
    {
        $this->data = [];

        // Obter room_id do GET se não foi passado como parâmetro
        if (empty($roomId) && !empty($_GET['room_id'])) {
            $roomId = (int)$_GET['room_id'];
        } else {
            $roomId = $roomId ? (int)$roomId : 0;
        }

        if ($roomId === 0) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">ID da sala não informado!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-meeting-rooms');
            exit;
        }

        $roomsRepo = new MeetingRoomsRepository();
        $room = $roomsRepo->getById($roomId);

        if (!$room) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Sala não encontrada!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-meeting-rooms');
            exit;
        }

        if ($room['status'] !== 'active') {
            $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Esta sala não está disponível para reservas!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'view-meeting-room/' . $roomId);
            exit;
        }

        // Tipo de visualização (month ou week)
        $view = $_GET['view'] ?? 'month';
        
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
            
            $this->data['view'] = 'week';
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
            
            $prevMonth = (clone $firstDay)->modify('-1 month')->format('Y-m');
            $nextMonth = (clone $firstDay)->modify('+1 month')->format('Y-m');
            
            $this->data['view'] = 'month';
            $this->data['selected_month'] = $selectedMonth;
            $this->data['first_day'] = $firstDay;
            $this->data['last_day'] = $lastDay;
            $this->data['prev_month'] = $prevMonth;
            $this->data['next_month'] = $nextMonth;
        }

        // Buscar reservas do período
        $bookingsRepo = new RoomBookingsRepository();
        $bookings = $bookingsRepo->getBookingsByRoomAndPeriod($roomId, $startDate, $endDate);

        // Organizar reservas por data e hora
        $bookingsByDateTime = [];
        foreach ($bookings as $booking) {
            $date = date('Y-m-d', strtotime($booking['start_datetime']));
            $startTime = date('H:i', strtotime($booking['start_datetime']));
            $endTime = date('H:i', strtotime($booking['end_datetime']));
            
            if (!isset($bookingsByDateTime[$date])) {
                $bookingsByDateTime[$date] = [];
            }
            
            $bookingsByDateTime[$date][] = [
                'id' => $booking['id'],
                'title' => $booking['title'],
                'start_time' => $startTime,
                'end_time' => $endTime,
                'start_datetime' => $booking['start_datetime'],
                'end_datetime' => $booking['end_datetime'],
                'user_name' => $booking['user_name'],
                'user_id' => (int)($booking['user_id'] ?? 0),
                'status' => $booking['status'],
            ];
        }

        $this->data['room'] = $room;
        $this->data['bookings'] = $bookingsByDateTime;

        $pageElements = [
            'title_head' => 'Reservar Sala: ' . $room['name'],
            'menu' => 'book-room',
            'buttonPermission' => [
                'ListMeetingRooms',
                'ViewMeetingRoom',
                'ViewBooking',
                'UpdateBooking',
                'CancelBooking',
            ],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        $this->data['book_room_current_user_id'] = (int)($_SESSION['user_id'] ?? 0);
        $this->data['book_room_is_super_admin'] = UserAccessHelper::hasFullSystemAccess();
        
        $loadView = new LoadViewService('adms/Views/rooms/book_room', $this->data);
        $loadView->loadView();
    }
}

