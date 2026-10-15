<?php

declare(strict_types=1);

namespace App\adms\Controllers\pricing;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\FormacaoPrecoRepository;
use App\adms\Models\Services\FormacaoPrecoCalculator;
use App\adms\Views\Services\LoadViewService;

final class FormacaoPrecoUpdate
{
    private array $data = [];

    public function index(int|string $id): void
    {
        $registroId = (int) $id;
        $repo = new FormacaoPrecoRepository();
        $registro = $repo->getById($registroId);
        if ($registro === null) {
            $_SESSION['msg'] = 'Precificação não encontrada.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'formacao-preco');
            exit;
        }

        $posted = $_POST ?? [];
        if (
            is_array($posted)
            && isset($posted['csrf_token'])
            && CSRFHelper::validateCSRFToken('form_formacao_preco', (string) $posted['csrf_token'])
        ) {
            $this->data['form'] = $posted;
            $this->save($registroId, $registro);
            return;
        }

        $this->data['form'] = $this->headerToForm($registro);
        $this->data['itens'] = $registro['itens'] ?? [];
        $this->viewForm($registroId, $registro);
    }

    private function viewForm(int $registroId, array $registro): void
    {
        $this->data['registro'] = $registro;
        $this->data['registro_id'] = $registroId;
        $this->data['editable'] = true;
        $pageElements = [
            'title_head' => 'Editar formação de preço',
            'menu' => 'formacao-preco',
            'buttonPermission' => ['FormacaoPreco', 'FormacaoPrecoView', 'FormacaoPrecoDelete', 'FormacaoPrecoPdf'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/pricing/update', $this->data))->loadView();
    }

    private function save(int $registroId, array $registro): void
    {
        $repo = new FormacaoPrecoRepository();
        [$errors, $payload] = $repo->validateHeader($this->data['form'] ?? []);
        $itens = $repo->parseItensFromForm($this->data['form'] ?? []);

        if ($errors !== []) {
            $this->data['errors'] = $errors;
            $this->data['itens'] = $this->recalcPosted($itens);
            $this->viewForm($registroId, $registro);
            return;
        }

        $ok = $repo->update($registroId, $payload, $itens, (int) ($_SESSION['user_id'] ?? 0));
        if ($ok) {
            $_SESSION['msg'] = 'Precificação salva.';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'formacao-preco-view/' . $registroId);
            return;
        }

        $this->data['errors'] = ['Não foi possível salvar. Tente novamente.'];
        $this->data['itens'] = $this->recalcPosted($itens);
        $this->viewForm($registroId, $registro);
    }

    /**
     * @param list<array<string, mixed>> $itens
     * @return list<array<string, mixed>>
     */
    private function recalcPosted(array $itens): array
    {
        $repo = new FormacaoPrecoRepository();
        foreach ($itens as &$item) {
            $item['calc'] = FormacaoPrecoCalculator::calcularLinha($repo->itemToCalcInput($item));
        }
        unset($item);

        return $itens;
    }

    /**
     * @param array<string, mixed> $registro
     * @return array<string, mixed>
     */
    private function headerToForm(array $registro): array
    {
        return [
            'nome' => (string) ($registro['nome'] ?? ''),
            'observacao' => (string) ($registro['observacao'] ?? ''),
            'icms_padrao' => FormacaoPrecoCalculator::formatPercent((float) ($registro['icms_padrao'] ?? 0.12)),
            'pis_padrao' => FormacaoPrecoCalculator::formatPercent((float) ($registro['pis_padrao'] ?? 0.0165)),
            'cofins_padrao' => FormacaoPrecoCalculator::formatPercent((float) ($registro['cofins_padrao'] ?? 0.076)),
        ];
    }
}
