<?php

namespace App\adms\Controllers\users;

use App\adms\Helpers\UserEducationHelper;
use App\adms\Helpers\UserFormHelper;
use App\adms\Models\Repository\UserEducationsRepository;
use App\adms\Models\Repository\UsersRepository;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Exporta lista de usuários em Excel respeitando os filtros da listagem.
 * As colunas seguem a mesma ordem das abas do cadastro de usuário.
 */
class ExportUsersExcel
{
    /** @var list<string> */
    private const EXPORT_KEYS = [
        // Aba Usuário
        'user_id',
        'user_name',
        'email',
        'username',
        'cpf',
        'celular',
        'department_name',
        'position_name',
        'supervisor_name',
        'work_shift_description',
        'tentativas_login',
        'image',
        'status',
        'bloqueado',
        'senha_nunca_expira',
        'modificar_senha_proximo_logon',
        'super_usuario',
        'enviar_boas_vindas_email',
        'enviar_boas_vindas_whatsapp',
        // Aba Dados Pessoais
        'email_pessoal',
        'data_nascimento',
        'sexo',
        'estado_civil',
        'escolaridade',
        'raca',
        'filhos',
        // Aba Endereço
        'cep',
        'pais_residencia_iso',
        'uf',
        'municipio',
        'bairro',
        'endereco',
        'numero_endereco',
        'complemento_endereco',
        // Aba Dados Contratuais
        'empresa_contratante',
        'matricula',
        'data_admissao',
        'data_desligamento',
        'motivo_desligamento',
        'tipo_impacto_desligamento',
        // Aba Formações (resumo; detalhes permanecem na planilha própria)
        'formacoes',
    ];

    /** @var list<string> */
    private const EXPORT_HEADERS_PT = [
        // Aba Usuário
        'ID',
        'Nome',
        'E-mail corporativo',
        'Usuário',
        'CPF',
        'Celular',
        'Departamento',
        'Cargo',
        'Superior imediato',
        'Turno',
        'Tentativas de login',
        'Imagem',
        'Status',
        'Bloqueado',
        'Senha nunca expira',
        'Modificar senha no próximo logon',
        'Super usuário',
        'Boas-vindas por e-mail',
        'Boas-vindas por WhatsApp',
        // Aba Dados Pessoais
        'E-mail pessoal',
        'Data de nascimento',
        'Sexo',
        'Estado civil',
        'Escolaridade',
        'Raça/cor',
        'Filhos',
        // Aba Endereço
        'CEP',
        'País',
        'UF',
        'Município',
        'Bairro',
        'Logradouro',
        'Número',
        'Complemento',
        // Aba Dados Contratuais
        'Empresa contratante',
        'Matrícula',
        'Data de admissão',
        'Data de desligamento',
        'Motivo do desligamento',
        'Impacto do desligamento',
        // Aba Formações
        'Formações',
    ];

    /** @var list<string> */
    private const TEXT_KEYS = [
        'username', 'cpf', 'celular', 'cep', 'numero_endereco', 'matricula',
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
        $userIds = array_map(static fn (array $user): int => (int) ($user['user_id'] ?? 0), $users);
        $educations = (new UserEducationsRepository())->getByUserIds($userIds);
        $educationsByUser = [];
        foreach ($educations as $education) {
            $userId = (int) ($education['adms_user_id'] ?? 0);
            $educationsByUser[$userId][] = $education;
        }
        foreach ($users as &$user) {
            $items = [];
            foreach ($educationsByUser[(int) ($user['user_id'] ?? 0)] ?? [] as $education) {
                $items[] = UserEducationHelper::typeLabel((string) ($education['tipo'] ?? ''))
                    . ': ' . (string) ($education['curso'] ?? '')
                    . (!empty($education['instituicao']) ? ' — ' . $education['instituicao'] : '')
                    . ' (' . UserEducationHelper::statusLabel((string) ($education['situacao'] ?? '')) . ')';
            }
            $user['formacoes'] = implode("\n", $items);
        }
        unset($user);

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
                $raw = $this->formatExportValue($key, $user[$key] ?? '');

                if (in_array($key, self::TEXT_KEYS, true)) {
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

        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:' . Coordinate::stringFromColumnIndex($headerCount) . '1');

        // Cargo: texto completo da BD — largura mínima + quebra de linha.
        $cargoIndex = array_search('position_name', self::EXPORT_KEYS, true);
        $cargoCol = Coordinate::stringFromColumnIndex(($cargoIndex === false ? 0 : $cargoIndex) + 1);
        $dim = $sheet->getColumnDimension($cargoCol);
        if ((float) $dim->getWidth() < 42) {
            $dim->setWidth(42);
        }
        if ($lastDataRow >= 2) {
            $sheet->getStyle($cargoCol . '2:' . $cargoCol . $lastDataRow)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
            $educationCol = Coordinate::stringFromColumnIndex($headerCount);
            $sheet->getStyle($educationCol . '2:' . $educationCol . $lastDataRow)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
            $sheet->getColumnDimension($educationCol)->setWidth(55);
        }

        $detail = $spreadsheet->createSheet();
        $detail->setTitle('Formações');
        $detailHeaders = [
            'ID usuário', 'Nome', 'ID formação', 'Tipo', 'Curso/Formação', 'Instituição',
            'Situação', 'Data início', 'Data conclusão', 'Carga horária', 'Observações', 'Comprovante',
        ];
        foreach ($detailHeaders as $index => $label) {
            $column = Coordinate::stringFromColumnIndex($index + 1);
            $detail->setCellValue($column . '1', $label);
            $detail->getStyle($column . '1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF2E9263');
            $detail->getStyle($column . '1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        }
        $userNames = [];
        foreach ($users as $user) {
            $userNames[(int) ($user['user_id'] ?? 0)] = (string) ($user['user_name'] ?? '');
        }
        $detailRow = 2;
        foreach ($educations as $education) {
            $userId = (int) ($education['adms_user_id'] ?? 0);
            $values = [
                $userId,
                $userNames[$userId] ?? '',
                (int) ($education['id'] ?? 0),
                UserEducationHelper::typeLabel((string) ($education['tipo'] ?? '')),
                (string) ($education['curso'] ?? ''),
                (string) ($education['instituicao'] ?? ''),
                UserEducationHelper::statusLabel((string) ($education['situacao'] ?? '')),
                !empty($education['data_inicio']) ? date('d/m/Y', strtotime((string) $education['data_inicio'])) : '',
                !empty($education['data_conclusao']) ? date('d/m/Y', strtotime((string) $education['data_conclusao'])) : '',
                $education['carga_horaria'] ?? '',
                (string) ($education['observacoes'] ?? ''),
                !empty($education['comprovante_path']) ? 'Sim' : 'Não',
            ];
            foreach ($values as $index => $value) {
                $detail->setCellValue(Coordinate::stringFromColumnIndex($index + 1) . $detailRow, $value);
            }
            $detailRow++;
        }
        foreach (range(1, count($detailHeaders)) as $index) {
            $detail->getColumnDimension(Coordinate::stringFromColumnIndex($index))->setAutoSize(true);
        }
        if ($detailRow > 2) {
            $detail->getStyle('E2:L' . ($detailRow - 1))->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        }
        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);
        $filename = 'usuarios_' . date('Y-m-d_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    private function formatExportValue(string $key, mixed $value): mixed
    {
        $text = is_scalar($value) ? (string) $value : '';

        return match ($key) {
            'data_nascimento', 'data_admissao', 'data_desligamento' => $this->formatDate($text),
            'sexo' => UserFormHelper::sexoLabel($text),
            'estado_civil' => UserFormHelper::estadoCivilLabel($text),
            'escolaridade' => UserFormHelper::escolaridadeLabel($text),
            'raca' => UserFormHelper::racaLabel($text),
            'filhos' => UserFormHelper::filhosLabel($text),
            'pais_residencia_iso' => UserFormHelper::paisResidenciaLabel($text),
            'empresa_contratante' => UserFormHelper::empresaContratanteLabel($text),
            'tipo_impacto_desligamento' => UserFormHelper::tipoImpactoDesligamentoLabel($text),
            'super_usuario', 'enviar_boas_vindas_email', 'enviar_boas_vindas_whatsapp'
                => $this->formatBoolean($value),
            default => $value ?? '',
        };
    }

    private function formatDate(string $value): string
    {
        $value = trim($value);
        if ($value === '' || $value === '0000-00-00') {
            return '';
        }
        $timestamp = strtotime($value);

        return $timestamp === false ? $value : date('d/m/Y', $timestamp);
    }

    private function formatBoolean(mixed $value): string
    {
        return in_array(strtolower(trim((string) $value)), ['1', 'sim', 'yes', 'true'], true)
            ? 'Sim'
            : 'Não';
    }
}
