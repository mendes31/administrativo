<?php

namespace App\adms\Controllers\documents;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\DocumentPositionsRepository;
use App\adms\Models\Repository\DocumentsRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para listar treinamentos de documentos
 *
 * Esta classe é responsável por recuperar e exibir uma lista de treinamentos de documentos no sistema. Utiliza um repositório
 * para obter dados dos treinamentos de documentos e um serviço de paginação para gerenciar a navegação entre páginas de resultados.
 * Em seguida, carrega a visualização correspondente com os dados recuperados.
 * 
 * @package App\adms\Controllers\documents
 * @author Rafael Mendes
 */
class ListDocumentPositions
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /** @var int|string|null $documentId ID do documento selecionado */
    private int|string|null $documentId;

    /**
     * Recuperar e listar treinamentos de documentos com paginação.
     * 
     * Este método recupera os treinamentos de documentos a partir do repositório de treinamentos de documentos com base na página atual e no limite
     * de registros por página. Gera os dados de paginação e carrega a visualização para exibir a lista de treinamentos de documentos.
     * 
     * @param int|string $documentId ID do documento para processar o POST. Se não for fornecido, usa o ID do primeiro documento.
     * 
     * @return void
     */
    public function index(int|string $documentId): void
    {
        $this->documentId = $documentId;
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        if (
            isset($this->data['form']['csrf_token']) &&
            CSRFHelper::validateCSRFToken('form_update_document_positions', $this->data['form']['csrf_token'])
        ) {
            $this->editDocumentPositions();
        } else {
            $this->viewDocumentPositions();
        }
    }

    private function viewDocumentPositions(): void
    {
        // Buscar documento selecionado
        $documentsRepo = new DocumentsRepository();
        $documents = $documentsRepo->getAllDocumentsFull();
        $document_id = $this->documentId ?? ($documents[0]['id'] ?? null);
        $documentos = [];
        foreach ($documents as $doc) {
            $documentos[$doc['id']] = $doc['cod_doc'] . ' - ' . $doc['name_doc'] . ' - ' . $doc['version'];
        }

        // Buscar cargos e obrigatoriedade para o documento selecionado
        $positionsRepo = new PositionsRepository();
        $documentPositionsRepo = new DocumentPositionsRepository();
        $positions = $positionsRepo->getAllPositions(1, 1000);
        $docPositions = $documentPositionsRepo->getAllDocumentPositionsSelect();

        $cargos = [];
        foreach ($positions as $cargo) {
            $obrigatorio = 0;
            foreach ($docPositions as $docPos) {
                if ($docPos['adms_position_id'] == $cargo['id'] && $docPos['adms_document_id'] == $document_id && $docPos['mandatory']) {
                    $obrigatorio = 1;
                    break;
                }
            }
            $cargos[] = [
                'id' => $cargo['id'],
                'name' => $cargo['name'],
                'mandatory' => $obrigatorio
            ];
        }
        $this->data['cargos'] = $cargos;
        $this->data['documentos'] = $documentos;
        $this->data['document_id'] = $document_id;

        // Definir o título da página
        $pageElements = [
            'title_head' => 'Documentos Obrigatórios por Cargo',
            'menu' => 'list-document-positions',
            'buttonPermission' => ['CreateDocumentPosition', 'ViewDocumentPosition', 'UpdateDocumentPosition', 'DeleteDocumentPosition', 'ListDocumentPositions'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW com os dados
        $loadView = new LoadViewService("adms/Views/documents/listDoumentPositions", $this->data);
        $loadView->loadView();
    }

    private function editDocumentPositions(): void
    {
        $mandatory = $this->data['form']['mandatory'] ?? [];
        $document_id = $this->data['form']['document_id'] ?? $this->documentId;
        $documentPositionsRepo = new DocumentPositionsRepository();
        $positionsRepo = new PositionsRepository();
        $positions = $positionsRepo->getAllPositions(1, 1000);
        $docPositions = $documentPositionsRepo->getAllDocumentPositionsSelect();

        foreach ($positions as $cargo) {
            $cargoId = $cargo['id'];
            $isMandatory = isset($mandatory[$cargoId]) ? 1 : 0;
            $exists = false;
            foreach ($docPositions as $docPos) {
                if ($docPos['adms_position_id'] == $cargoId && $docPos['adms_document_id'] == $document_id) {
                    $exists = $docPos['id'];
                    break;
                }
            }
            if ($exists) {
                $documentPositionsRepo->updateDocumentPosition([
                    'id' => $exists,
                    'mandatory' => $isMandatory,
                    'adms_document_id' => $document_id,
                    'adms_position_id' => $cargoId
                ]);
            } else {
                $documentPositionsRepo->createDocumentPosition([
                    'mandatory' => $isMandatory,
                    'adms_document_id' => $document_id,
                    'adms_position_id' => $cargoId
                ]);
            }
        }
        $_SESSION['success'] = 'Treinamentos obrigatórios atualizados com sucesso!';
        header('Location: ' . $_ENV['URL_ADM'] . 'list-document-positions/' . $document_id);
        exit;
    }
}
