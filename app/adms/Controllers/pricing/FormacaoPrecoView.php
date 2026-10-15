<?php

declare(strict_types=1);

namespace App\adms\Controllers\pricing;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\FormacaoPrecoRepository;
use App\adms\Models\Services\FormacaoPrecoCalculator;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

final class FormacaoPrecoView
{
    private array $data = [];

    public function index(int|string $id): void
    {
        $registroId = (int) $id;
        $registro = (new FormacaoPrecoRepository())->getById($registroId);
        if ($registro === null) {
            $_SESSION['msg'] = 'Precificação não encontrada.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'formacao-preco');
            exit;
        }

        $this->data['registro'] = $registro;
        $this->data['registro_id'] = $registroId;
        $this->data['itens'] = $registro['itens'] ?? [];
        $this->data['form'] = [
            'nome' => (string) ($registro['nome'] ?? ''),
            'observacao' => (string) ($registro['observacao'] ?? ''),
            'icms_padrao' => FormacaoPrecoCalculator::formatPercent((float) ($registro['icms_padrao'] ?? 0)),
            'pis_padrao' => FormacaoPrecoCalculator::formatPercent((float) ($registro['pis_padrao'] ?? 0)),
            'cofins_padrao' => FormacaoPrecoCalculator::formatPercent((float) ($registro['cofins_padrao'] ?? 0)),
        ];
        $this->data['editable'] = false;
        $this->data['csrf_delete'] = CSRFHelper::generateCSRFToken('form_formacao_preco_delete');
        $returnUrl = ($_ENV['URL_ADM'] ?? '') . 'formacao-preco-view/' . $registroId;
        $this->data['log_resumo'] = LogResumoService::getResumo('formacao_precos', $registroId, $returnUrl);

        $pageElements = [
            'title_head' => 'Formação de preço',
            'menu' => 'formacao-preco',
            'buttonPermission' => ['FormacaoPreco', 'FormacaoPrecoUpdate', 'FormacaoPrecoDelete', 'FormacaoPrecoPdf'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/pricing/view', $this->data))->loadView();
    }
}
