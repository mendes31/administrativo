<?php

namespace App\adms\Controllers\users;

use App\adms\Models\Repository\UsersRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Exporta lista de usuários em Excel respeitando os filtros da listagem.
 */
class ExportUsersExcel
{
    public function index(): void
    {
        // Reaproveitar mesma lógica de filtros da ListUsers (GET + sessão)
        if (!isset($_SESSION['filtros_list_users'])) {
            $_SESSION['filtros_list_users'] = [];
        }

        $filtros = [
            'nome' => $_GET['nome'] ?? $_SESSION['filtros_list_users']['nome'] ?? '',
            'email' => $_GET['email'] ?? $_SESSION['filtros_list_users']['email'] ?? '',
            'usuario' => $_GET['usuario'] ?? $_SESSION['filtros_list_users']['usuario'] ?? '',
            'departamento_id' => $_GET['departamento_id'] ?? $_SESSION['filtros_list_users']['departamento_id'] ?? '',
            'cargo_id' => $_GET['cargo_id'] ?? $_SESSION['filtros_list_users']['cargo_id'] ?? '',
            'status' => $_GET['status'] ?? $_SESSION['filtros_list_users']['status'] ?? '',
            'bloqueado' => $_GET['bloqueado'] ?? $_SESSION['filtros_list_users']['bloqueado'] ?? '',
            'desligado' => $_GET['desligado'] ?? $_SESSION['filtros_list_users']['desligado'] ?? '',
            'periodo_tipo' => $_GET['periodo_tipo'] ?? $_SESSION['filtros_list_users']['periodo_tipo'] ?? '',
            'data_de' => $_GET['data_de'] ?? $_SESSION['filtros_list_users']['data_de'] ?? '',
            'data_ate' => $_GET['data_ate'] ?? $_SESSION['filtros_list_users']['data_ate'] ?? '',
        ];

        $usersRepo = new UsersRepository();
        $users = $usersRepo->getAllUsersForExport($filtros);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Usuários');

        // Cabeçalhos
        $headers = [
            'ID', 'Nome', 'CPF', 'E-mail', 'Usuário', 'Departamento', 'Cargo',
            'Status', 'Bloqueado', 'Desligado', 'Data Admissão', 'Data Desligamento',
        ];

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF2E9263');
            $sheet->getStyle($col . '1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle($col . '1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $col++;
        }

        // Dados
        $row = 2;
        foreach ($users as $user) {
            $sheet->setCellValue('A' . $row, $user['id'] ?? '');
            $sheet->setCellValue('B' . $row, $user['name'] ?? '');
            $sheet->setCellValueExplicit('C' . $row, (string)($user['cpf'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('D' . $row, $user['email'] ?? '');
            $sheet->setCellValue('E' . $row, $user['username'] ?? '');
            $sheet->setCellValue('F' . $row, $user['name_dep'] ?? '');
            $sheet->setCellValue('G' . $row, $user['name_pos'] ?? '');
            $sheet->setCellValue('H' . $row, $user['status'] ?? '');

            $bloqueado = $user['bloqueado'] ?? 0;
            $sheet->setCellValue('I' . $row, ($bloqueado == 1 || $bloqueado === 'Sim') ? 'Sim' : 'Não');

            $dataDesligamento = $user['data_desligamento'] ?? null;
            $sheet->setCellValue('J' . $row, $dataDesligamento ? 'Sim' : 'Não');
            $sheet->setCellValue('K' . $row, !empty($user['data_admissao']) ? date('d/m/Y', strtotime($user['data_admissao'])) : '');
            $sheet->setCellValue('L' . $row, $dataDesligamento ? date('d/m/Y', strtotime($dataDesligamento)) : '');

            $row++;
        }

        // Auto-ajustar largura
        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'usuarios_' . date('Y-m-d_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }
}

