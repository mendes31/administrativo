<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SstCategoriaAsoHelper;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Services\SstEncaminhamentoAsoPdfService;
use App\adms\Models\Services\SstExamesObrigatoriosResolver;
use Mpdf\Mpdf;

/**
 * Gera PDF do encaminhamento / autorização de ASO.
 */
class SstExportEncaminhamentoAsoPdf
{
    public function index(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ' . $_ENV['URL_ADM'] . 'sst-encaminhamento-aso');
                exit;
            }

            if (!CSRFHelper::validateCSRFToken('sst_encaminhamento_aso', $_POST['csrf_token'] ?? '')) {
                $_SESSION['msg'] = 'Token CSRF inválido.';
                $_SESSION['msg_type'] = 'danger';
                header('Location: ' . $_ENV['URL_ADM'] . 'sst-encaminhamento-aso');
                exit;
            }

            if (ob_get_length()) {
                ob_end_clean();
            }
            @set_time_limit(60);

            $userId = (int) ($_POST['adms_user_id'] ?? 0);
            $categoria = trim((string) ($_POST['categoria_aso'] ?? ''));
            $dataEnc = trim((string) ($_POST['data_encaminhamento'] ?? date('Y-m-d')));

            if ($userId <= 0 || !SstCategoriaAsoHelper::isValid($categoria)) {
                $_SESSION['msg'] = 'Selecione colaborador e categoria ASO válidos.';
                $_SESSION['msg_type'] = 'danger';
                header('Location: ' . $_ENV['URL_ADM'] . 'sst-encaminhamento-aso');
                exit;
            }

            $user = (new UsersRepository())->getUser($userId);
            if (!$user) {
                $_SESSION['msg'] = 'Colaborador não encontrado.';
                $_SESSION['msg_type'] = 'danger';
                header('Location: ' . $_ENV['URL_ADM'] . 'sst-encaminhamento-aso');
                exit;
            }

            $resolver = new SstExamesObrigatoriosResolver();
            $pacote = $resolver->resolvePacoteCompleto($userId, $categoria);
            $recomendadosIds = array_map('intval', is_array($_POST['recomendados'] ?? null) ? $_POST['recomendados'] : []);
            $recomendadosIds = array_values(array_filter($recomendadosIds, fn (int $id) => $id > 0));

            $html = (new SstEncaminhamentoAsoPdfService())->buildHtml(
                $user,
                $categoria,
                $dataEnc,
                $pacote['obrigatorios'],
                $pacote['recomendados'],
                $recomendadosIds
            );

            $nomeArquivo = preg_replace('/\W+/', '_', (string) ($user['name'] ?? 'colaborador'));
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 14,
                'margin_right' => 14,
                'margin_top' => 14,
                'margin_bottom' => 14,
            ]);
            $mpdf->SetTitle('Encaminhamento ASO - ' . ($user['name'] ?? ''));
            $mpdf->WriteHTML($html);
            $mpdf->Output('Encaminhamento_ASO_' . $nomeArquivo . '.pdf', 'I');
            exit;
        } catch (\Throwable $e) {
            $_SESSION['msg'] = 'Erro ao gerar PDF: ' . $e->getMessage();
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-encaminhamento-aso');
            exit;
        }
    }
}
