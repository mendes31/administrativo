<?php

namespace App\adms\Controllers\documents;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationDocumentService;
use App\adms\Models\Repository\DocumentsRepository;
use App\adms\Models\Repository\GroupsPagesRepository;
use App\adms\Models\Repository\PackagesRepository;
use App\adms\Helpers\CSRFHelper;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para criação de documento
 *
 * Esta classe é responsável pelo processo de criação de novas documentos. Ela lida com a recepção dos dados do
 * formulário, validação dos mesmos e criação do documento no sistema. Além disso, é responsável por carregar
 * a visualização apropriada com mensagens de sucesso ou erro.
 * 
 * @package App\adms\Controllers\documents
 * @author Rafael Mendes
 */
class CreateDocument
{
    /** @var array|string|null $data Dados que serão enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Método principal que gerencia a criação do documento.
     *
     * Este método é chamado para processar a criação de um novo documento. Ele verifica a validade do token CSRF,
     * valida os dados do formulário e, se tudo estiver correto, cria o documento. Caso contrário, carrega a
     * visualização de criação de documento com mensagens de erro.
     * 
     * @return void
     */
    public function index(): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Verificar se o token CSRF é válido
        if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_create_document', $this->data['form']['csrf_token'])) {
            // Chamar o método para adicionar o documento
            $this->addDocument();
        } else {
            // Chamar o método para carregar a view de criação de documento
            $this->viewDocument();
        }
    }

    /**
     * Carregar a visualização de criação de documento.
     * 
     * Este método configura os dados necessários e carrega a view para a criação de um novo documento.
     * 
     * @return void
     */
    private function viewDocument(): void
    {        
        // Instanciar o repositório para recuperar os pacotes
        $listPackagesPages = new PackagesRepository();
        $this->data['listPackagesPages'] = $listPackagesPages->getAllPackagesSelect();

        // Instanciar o repositório para recuperar os grupos
        $listgroupsPages = new GroupsPagesRepository();
        $this->data['listgroupsPages'] = $listgroupsPages->getAllGroupsPagesSelect();

        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Cadastrar Documento',
            'menu' => 'list-documents',
            'buttonPermission' => ['ListDocuments'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/documents/create", $this->data);
        $loadView->loadView();
    }

    /**
     * Adicionar um novo documento ao sistema.
     * 
     * Este método valida os dados do formulário usando a classe de validação `ValidationDocumentService` e,
     * se não houver erros, cria o documento no banco de dados usando o `DocumentsRepository`. Caso contrário, ele
     * recarrega a visualização de criação com mensagens de erro.
     * 
     * @return void
     */
    private function addDocument(): void
    {
        // Ajustar campos para o banco
        $this->data['form']['name_doc'] = $this->data['form']['name'] ?? '';
        $this->data['form']['active'] = $this->data['form']['page_status'] ?? 1;

        // Instanciar a classe de validação dos dados do formulário
        $validationDocument = new ValidationDocumentService();
        $this->data['errors'] = $validationDocument->validate($this->data['form']);

        // Se houver erros, recarregar a view com erros
        if (!empty($this->data['errors'])) {
            $this->viewDocument();
            return;
        }

        // Instanciar o Repository para criar o documento
        $documentCreate = new DocumentsRepository();
        $result = $documentCreate->createDocument($this->data['form']);

        // Se a criação do documento for bem-sucedida
        if ($result) {
            // Mensagem de sucesso
            $_SESSION['success'] = "Documento cadastrado com sucesso!";

            // Redirecionar para a página de visualização do documento recém-criado
            header("Location: {$_ENV['URL_ADM']}view-document/$result");
            return;
        } else {
            // Mensagem de erro
            $this->data['errors'][] = "Documento não cadastrado!";
        }
    }
}
