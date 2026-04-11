<?php

namespace App\adms\Controllers\users;

use App\adms\Models\Repository\UsersRepository;
use Dompdf\Dompdf;

/**
 * Exporta lista de usuários em PDF respeitando os filtros da listagem.
 */
class ExportUsersPdf
{
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

        $html = '<html><head><meta charset="UTF-8"><style>
            body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; }
            h1 { font-size: 18px; margin-bottom: 5px; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th, td { border: 1px solid #ccc; padding: 4px 6px; }
            th { background: #f2f2f2; font-weight: bold; }
            .text-center { text-align: center; }
            .small { font-size: 10px; color: #666; }
        </style></head><body>';

        $html .= '<h1>Lista de Usuários</h1>';
        $html .= '<div class="small">Gerado em ' . date('d/m/Y H:i') . '</div>';

        $html .= '<table><thead><tr>
            <th style="width:6%;">ID</th>
            <th style="width:14%;">Nome</th>
            <th style="width:10%;">CPF</th>
            <th style="width:16%;">E-mail</th>
            <th style="width:10%;">Usuário</th>
            <th style="width:12%;">Departamento</th>
            <th style="width:12%;">Cargo</th>
            <th style="width:6%;">Status</th>
            <th style="width:6%;">Bloq.</th>
            <th style="width:6%;">Desl.</th>
        </tr></thead><tbody>';

        if (!$users) {
            $html .= '<tr><td colspan="10" class="text-center">Nenhum usuário encontrado com os filtros selecionados.</td></tr>';
        } else {
            foreach ($users as $user) {
                $id = (int)($user['id'] ?? 0);
                $name = htmlspecialchars($user['name'] ?? '');
                $cpf = htmlspecialchars((string)($user['cpf'] ?? ''));
                $email = htmlspecialchars($user['email'] ?? '');
                $username = htmlspecialchars($user['username'] ?? '');
                $dep = htmlspecialchars($user['name_dep'] ?? '');
                $pos = htmlspecialchars($user['name_pos'] ?? '');
                $status = htmlspecialchars($user['status'] ?? '');
                $bloqueado = $user['bloqueado'] ?? 0;
                $bloqLabel = ($bloqueado == 1 || $bloqueado === 'Sim') ? 'Sim' : 'Não';
                $desligado = !empty($user['data_desligamento']) ? 'Sim' : 'Não';

                $html .= '<tr>';
                $html .= '<td class="text-center">' . $id . '</td>';
                $html .= '<td>' . $name . '</td>';
                $html .= '<td class="text-center">' . $cpf . '</td>';
                $html .= '<td>' . $email . '</td>';
                $html .= '<td>' . $username . '</td>';
                $html .= '<td>' . $dep . '</td>';
                $html .= '<td>' . $pos . '</td>';
                $html .= '<td class="text-center">' . $status . '</td>';
                $html .= '<td class="text-center">' . $bloqLabel . '</td>';
                $html .= '<td class="text-center">' . $desligado . '</td>';
                $html .= '</tr>';
            }
        }

        $html .= '</tbody></table>';
        $html .= '</body></html>';

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream('usuarios_' . date('Y-m-d_His') . '.pdf', ['Attachment' => true]);
        exit;
    }
}

