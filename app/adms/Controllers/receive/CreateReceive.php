<?php

namespace App\adms\Controllers\receive;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationReceiptsService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\AccountPlanRepository;
use App\adms\Models\Repository\BanksRepository;
use App\adms\Models\Repository\CostCentersRepository;
use App\adms\Models\Repository\CustomerRepository;
use App\adms\Models\Repository\FrequencyRepository;
use App\adms\Models\Repository\LogsRepository;
use App\adms\Models\Repository\PaymentMethodsRepository;
use App\adms\Models\Repository\ReceiptsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para criação de Conta à Receber
 *
 * Esta classe é responsável pelo processo de criação de novos Conta à Receber. Ela lida com a recepção dos dados do
 * formulário, validação dos mesmos, e criação do Conta à Receber no sistema. Além disso, é responsável por carregar
 * a visualização apropriada com mensagens de sucesso ou erro.
 * 
 * @package App\adms\Controllers\receive;
 * @author Rafael Mendes
 */
class CreateReceive
{
    /** @var array|string|null $data Dados que serão enviados para a VIEW */
    private array|string|null $data = null;

    /**
     * Método principal que gerencia a criação do Conta à Receber.
     *
     * Este método é chamado para processar a criação de uma nova conta a receber. Ele verifica a validade do token CSRF,
     * valida os dados do formulário e, se tudo estiver correto, cria o Conta à Receber. Caso contrário, carrega a
     * visualização de criação do Conta à Receber com mensagens de erro.
     * 
     * @return void
     */
    public function index(): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // var_dump($this->data['form']);
        // exit;

        // Verificar se o token CSRF é válido
        if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_create_receive', $this->data['form']['csrf_token'])) {
            // Chamar o método para adicionar Conta a Receber
            $this->addReceive();
        } else {
            // Chamar o método para carregar a view de criação de Conta a Receber
            $this->viewReceive();
        }
    }

    /**
     * Carregar a visualização de criação do Conta à Receber.
     * 
     * Este método configura os dados necessários e carrega a view para a criação de um novo Conta à Receber.
     * 
     * @return void
     */
    private function viewReceive(): void
    {
        // Instanciar o repositório para recuperar os clientes
        $listCustomers = new CustomerRepository();
        $this->data['listCustomers'] = $listCustomers->getAllCustomersSelect();

        // Instanciar o repositório para recuperar as frequencias
        $listFrequencies = new FrequencyRepository();
        $this->data['listFrequencies'] = $listFrequencies->getAllFrequencySelect();

        // Instanciar o repositório para formas de pagamento/Recebimento
        $listPaymentMethods = new PaymentMethodsRepository();
        $this->data['listPaymentMethods'] = $listPaymentMethods->getAllPaymentMethodsSelect();

        // Instanciar o repositório para recuperar os planos de conta
        $listAccountsPlan = new AccountPlanRepository();
        $this->data['listAccountsPlan'] = $listAccountsPlan->getAllAccountsPlanSelect();

        // Instanciar o repositório para recuperar os centros de custo
        $listCostCenters = new CostCentersRepository();
        $this->data['listCostCenters'] = $listCostCenters->getAllCostCenterSelect();

        // Instanciar o repositório para recuperar os bancos
        $listBanks = new BanksRepository();
        $this->data['listBanks'] = $listBanks->getAllBanksSelect();


        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Cadastrar Conta à Receber',
            'menu' => 'list-receipts',
            'buttonPermission' => ['ListReceipts'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/receive/create", $this->data);
        $loadView->loadView();
    }

    /**
     * Adicionar um novo Conta à Receber ao sistema.
     * 
     * Este método valida os dados do formulário usando a classe de validação `ValidationPaymentsService` e,
     * se não houver erros, cria o Conta à Receber no Conta à Receber de dados usando o `PaymentsRepository`. Caso contrário, ele
     * recarrega a visualização de criação com mensagens de erro.
     * 
     * @return void
     */
    private function addReceive(): void
    {
        // Instanciar a classe de validação dos dados do formulário
        $validationReceipts = new ValidationReceiptsService();
        $this->data['errors'] = $validationReceipts->validate($this->data['form']);

        // // SCRIPT PARA SUBIR FOTO NO SERVIDOR

        // // Garante que a URL do ADM termina com '/'
        // $url_base = rtrim($_ENV['URL_ADM'] ?? '', '/') . '/';

        // // Define o nome do arquivo sem caracteres problemáticos
        // $nome_img = time() . '-' . preg_replace('/[^a-zA-Z0-9\._-]/', '_', $_FILES['file']['name']);

        // // Caminho para exibição da imagem na web
        // $caminho_url = $url_base . 'public/adms/image/contas/' . $nome_img;

        // // Caminho absoluto para salvar o arquivo no servidor
        // $pasta_destino = __DIR__ . '/../public/adms/image/contas/';

        // // Garante que a pasta de destino existe
        // if (!is_dir($pasta_destino)) {
        //     mkdir($pasta_destino, 0777, true);
        // }

        // // Caminho completo no servidor onde o arquivo será salvo
        // $caminho_upload = $pasta_destino . $nome_img;

        // $imagem_temp = $_FILES['arquivo']['tmp_name'];

        // // Verifica se um arquivo foi enviado
        // if (!empty($_FILES['arquivo']['name'])) {
        //     // Lista de extensões permitidas
        //     $extensoes_permitidas = ['png', 'jpg', 'jpeg', 'gif', 'pdf', 'rar', 'zip', 'doc', 'docx'];
        //     $ext = strtolower(pathinfo($nome_img, PATHINFO_EXTENSION));

        //     // Verifica se a extensão é válida
        //     if (in_array($ext, $extensoes_permitidas)) {
        //         // Exclui a foto anterior, se necessário
        //         if (!empty($foto) && $foto != "sem-foto.png" && file_exists($pasta_destino . $foto)) {
        //             unlink($pasta_destino . $foto);
        //         }

        //         // Move o arquivo para o destino
        //         if (move_uploaded_file($imagem_temp, $caminho_upload)) {
        //             $foto = $nome_img; // Atualiza a variável com o novo nome da foto
        //         } else {
        //             echo 'Erro ao mover o arquivo!';
        //             exit();
        //         }
        //     } else {
        //         echo 'Extensão de arquivo não permitida!';
        //         exit();
        //     }
        // } else {
        //     echo 'Nenhum arquivo foi enviado!';
        //     exit();
        // }



        // Se houver erros, recarregar a view com erros
        if (!empty($this->data['errors'])) {
            $this->viewReceive();
            return;
        }



        // Instanciar o Repository para criar o Conta à Receber
        $receiveCreate = new ReceiptsRepository();
        $result = $receiveCreate->createReceive($this->data['form']);

        // var_dump($result);

        // var_dump($this->data['form']);
        // exit;

        // Se a criação do Conta à Receber for bem-sucedida
        if ($result) {

            // gravar logs na tabela adms-logs
            if ($_ENV['APP_LOGS'] == 'Sim') {
                $dataLogs = [
                    'table_name' => 'adms_receive',
                    'action' => 'inserção',
                    'record_id' => $result,
                    'description' => $this->data['form']['num_doc'],

                ];
                // Instanciar a classe validar  o usuário
                $insertLogs = new LogsRepository();
                $insertLogs->insertLogs($dataLogs);
            }

            // Mensagem de sucesso
            $_SESSION['success'] = "Conta à Receber cadastrada com sucesso!";

            // Redirecionar para a página de visualização do Conta à Receber recém-criado
            // header("Location: {$_ENV['URL_ADM']}view-pay/$result");
            header("Location: {$_ENV['URL_ADM']}list-receipts");
            return;
        } else {
            // Mensagem de erro
            $this->data['errors'][] = "Conta à Receber não cadastrada!";

            // Recarregar a view com erro
            $this->viewReceive();
        }
    }
}
