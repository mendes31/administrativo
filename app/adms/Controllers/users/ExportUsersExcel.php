<?php

namespace App\adms\Controllers\users;

use App\adms\Models\Repository\UsersRepository;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Exporta lista de usuários em Excel respeitando os filtros da listagem.
 * Colunas alinhadas ao pedido: cargo com texto integral da BD (pos.name).
 */
class ExportUsersExcel
{
    /** @var list<string> */
    private const EXPORT_KEYS = [
        'user_name',
        'department_name',
        'data_admissao_br',
        'position_name',
        'supervisor_name',
        'data_nascimento_br',
        'cpf',
        'email',
        'sexo',
        'celular',
        'escolaridade',
    ];

    /** @var list<string> */
    private const EXPORT_HEADERS_PT = [
        'Nome',
        'Departamento',
        'Data admissão',
        'Cargo',
        'Superior imediato',
        'Data nascimento',
        'CPF',
        'E-mail',
        'Sexo',
        'Celular',
        'Escolaridade',
    ];

    public function index(): void
    {
        if (!isset($_SESSION['filtros_list_users'])) {
            $_SESSION['filtros_list_users'] = [];
        }

        $filtros = [
            'nome' => $_GET['nome'] ?? $_SESSION['filtros_list_users']['nome'] ?? '',
            'usuario' => $_GET['usuario'] ?? $_SESSION['filtros_list_users']['usuario'] ?? '',
            'departamento_id' => $_GET['departamento_id'] ?? $_SESSION['filtros_list_users']['departamento_id'] ?? '',
            'cargo_id' => $_GET['cargo_id'] ?? $_SESSION['filtros_list_users']['cargo_id'] ?? '',
            'turno_id' => $_GET['turno_id'] ?? $_SESSION['filtros_list_users']['turno_id'] ?? '',
            'status' => $_GET['status'] ?? $_SESSION['filtros_list_users']['status'] ?? '',
            'bloqueado' => $_GET['bloqueado'] ?? $_SESSION['filtros_list_users']['bloqueado'] ?? '',
            'desligado' => $_GET['desligado'] ?? $_SESSION['filtros_list_users']['desligado'] ?? '',
            'sexo' => $_GET['sexo'] ?? $_SESSION['filtros_list_users']['sexo'] ?? '',
            'filhos' => $_GET['filhos'] ?? $_SESSION['filtros_list_users']['filhos'] ?? '',
            'periodo_tipo' => $_GET['periodo_tipo'] ?? $_SESSION['filtros_list_users']['periodo_tipo'] ?? '',
            'data_de' => $_GET['data_de'] ?? $_SESSION['filtros_list_users']['data_de'] ?? '',
            'data_ate' => $_GET['data_ate'] ?? $_SESSION['filtros_list_users']['data_ate'] ?? '',
        ];

        $usersRepo = new UsersRepository();
        $users = $usersRepo->getAllUsersForExport($filtros);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Usuários');

        $headerCount = count(self::EXPORT_HEADERS_PT);
        for ($c = 1; $c <= $headerCount; $c++) {
            $colLetter = Coordinate::stringFromColumnIndex($c);
            $sheet->setCellValue($colLetter . '1', self::EXPORT_HEADERS_PT[$c - 1]);
            $sheet->getStyle($colLetter . '1')->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF2E9263');
            $sheet->getStyle($colLetter . '1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle($colLetter . '1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $row = 2;
        foreach ($users as $user) {
            for ($c = 1; $c <= $headerCount; $c++) {
                $colLetter = Coordinate::stringFromColumnIndex($c);
                $key = self::EXPORT_KEYS[$c - 1];
                $cell = $colLetter . $row;
                $raw = $user[$key] ?? '';

                if ($key === 'cpf') {
                    $sheet->setCellValueExplicit($cell, (string) $raw, DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValue($cell, $raw);
                }
            }
            $row++;
        }

        $lastDataRow = max(1, $row - 1);
        for ($c = 1; $c <= $headerCount; $c++) {
            $colLetter = Coordinate::stringFromColumnIndex($c);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // Cargo: texto completo da BD — largura mínima + quebra de linha (evita “cortar” visualmente no Excel)
        $cargoCol = Coordinate::stringFromColumnIndex(4);
        $dim = $sheet->getColumnDimension($cargoCol);
        if ((float) $dim->getWidth() < 42) {
            $dim->setWidth(42);
        }
        if ($lastDataRow >= 2) {
            $sheet->getStyle($cargoCol . '2:' . $cargoCol . $lastDataRow)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
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
