<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Helpers\SstCategoriaAsoHelper;
use App\adms\Helpers\UserFormHelper;
use App\adms\Models\Repository\PagesRoutesRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Services\SstExamesObrigatoriosResolver;

/**
 * Retorna JSON com exames complementares por colaborador e categoria ASO.
 */
class SstPacoteExamesAso
{
    public function index(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'message' => 'Sessão expirada. Faça login novamente.']);
            return;
        }

        $allowed = (new PagesRoutesRepository())->checkUserAnyPagePermissionForControllers([
            'SstPacoteExamesAso',
            'SstCreateAso',
            'SstUpdateAso',
            'SstListAsos',
            'SstEncaminhamentoAso',
        ]);
        if (!$allowed) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'message' => 'Sem permissão para carregar o pacote de exames.']);
            return;
        }

        $userId = (int) ($_GET['adms_user_id'] ?? 0);
        $tipo = trim((string) ($_GET['tipo'] ?? ''));
        if ($userId <= 0 || !SstCategoriaAsoHelper::isValid($tipo)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'Parâmetros inválidos.']);
            return;
        }

        $resolver = new SstExamesObrigatoriosResolver();
        $pacote = $resolver->resolvePacoteCompleto($userId, $tipo);
        $obrigatorios = $pacote['obrigatorios'];
        $recomendados = $pacote['recomendados'];

        $empresaSlug = null;
        $empresaLabel = null;
        $user = (new UsersRepository())->getUser($userId);
        if (is_array($user)) {
            $empresaSlug = UserFormHelper::resolveEmpresaContratanteSlug($user['empresa_contratante'] ?? null);
            $empresaLabel = $empresaSlug !== null
                ? UserFormHelper::empresaContratantePdfLabel($empresaSlug)
                : null;
        }

        echo json_encode([
            'ok' => true,
            'exames' => $obrigatorios,
            'obrigatorios' => $obrigatorios,
            'recomendados' => $recomendados,
            'empresa_contratante' => $empresaSlug,
            'empresa_contratante_label' => $empresaLabel,
        ], JSON_UNESCAPED_UNICODE);
    }
}
