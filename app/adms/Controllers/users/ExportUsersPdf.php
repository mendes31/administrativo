<?php

namespace App\adms\Controllers\users;

use App\adms\Helpers\UserEducationHelper;
use App\adms\Models\Repository\UserEducationsRepository;
use App\adms\Models\Repository\UsersRepository;
use Dompdf\Dompdf;

/**
 * Exporta lista de usuários em PDF respeitando os filtros da listagem.
 * Colunas alinhadas ao pedido: cargo com texto integral da BD (pos.name).
 */
class ExportUsersPdf
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
        $userIds = array_map(static fn (array $user): int => (int) ($user['user_id'] ?? 0), $users);
        $educations = (new UserEducationsRepository())->getByUserIds($userIds);
        $userNames = [];
        foreach ($users as $user) {
            $userNames[(int) ($user['user_id'] ?? 0)] = (string) ($user['user_name'] ?? '');
        }

        $colCount = count(self::EXPORT_HEADERS_PT);

        $html = '<html><head><meta charset="UTF-8"><style>
            body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 8px; }
            h1 { font-size: 15px; margin-bottom: 5px; }
            table { width: 100%; border-collapse: collapse; margin-top: 8px; table-layout: fixed; }
            th, td { border: 1px solid #ccc; padding: 3px 4px; vertical-align: top; word-wrap: break-word; }
            th { background: #e8e8e8; font-weight: bold; font-size: 7.5px; }
            .small { font-size: 8px; color: #666; }
            .col-cargo { font-size: 7.5px; }
        </style></head><body>';

        $html .= '<h1>Lista de Usuários</h1>';
        $html .= '<div class="small">Gerado em ' . date('d/m/Y H:i') . '</div>';

        $html .= '<table><thead><tr>';
        foreach (self::EXPORT_HEADERS_PT as $label) {
            $html .= '<th>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        if (!$users) {
            $html .= '<tr><td colspan="' . $colCount . '" style="text-align:center;">Nenhum usuário encontrado com os filtros selecionados.</td></tr>';
        } else {
            foreach ($users as $user) {
                $html .= '<tr>';
                foreach (self::EXPORT_KEYS as $key) {
                    $val = (string) ($user[$key] ?? '');
                    $cell = htmlspecialchars($val, ENT_QUOTES, 'UTF-8');
                    $class = ($key === 'position_name') ? ' class="col-cargo"' : '';
                    $html .= '<td' . $class . '>' . $cell . '</td>';
                }
                $html .= '</tr>';
            }
        }

        $html .= '</tbody></table>';
        if ($educations !== []) {
            $html .= '<h1 style="margin-top:18px;">Formações acadêmicas e cursos</h1>';
            $html .= '<table><thead><tr>'
                . '<th>Usuário</th><th>Tipo</th><th>Curso/Formação</th><th>Instituição</th>'
                . '<th>Situação</th><th>Período</th><th>Carga horária</th><th>Comprovante</th>'
                . '</tr></thead><tbody>';
            foreach ($educations as $education) {
                $userId = (int) ($education['adms_user_id'] ?? 0);
                $start = !empty($education['data_inicio'])
                    ? date('m/Y', strtotime((string) $education['data_inicio']))
                    : '';
                $end = !empty($education['data_conclusao'])
                    ? date('m/Y', strtotime((string) $education['data_conclusao']))
                    : '';
                $period = trim($start . ($start !== '' && $end !== '' ? ' a ' : '') . $end);
                $values = [
                    $userNames[$userId] ?? '',
                    UserEducationHelper::typeLabel((string) ($education['tipo'] ?? '')),
                    (string) ($education['curso'] ?? ''),
                    (string) ($education['instituicao'] ?? ''),
                    UserEducationHelper::statusLabel((string) ($education['situacao'] ?? '')),
                    $period,
                    !empty($education['carga_horaria']) ? (int) $education['carga_horaria'] . ' h' : '',
                    !empty($education['comprovante_path']) ? 'Sim' : 'Não',
                ];
                $html .= '<tr>';
                foreach ($values as $value) {
                    $html .= '<td>' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
        }
        $html .= '</body></html>';

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream('usuarios_' . date('Y-m-d_His') . '.pdf', ['Attachment' => true]);
        exit;
    }
}
