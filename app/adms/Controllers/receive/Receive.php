<?php

namespace App\adms\Controllers\receive;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationReceiveService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\AccountPlanRepository;
use App\adms\Models\Repository\BanksRepository;
use App\adms\Models\Repository\CostCentersRepository;
use App\adms\Models\Repository\FrequencyRepository;
use App\adms\Models\Repository\LogsRepository;
use App\adms\Models\Repository\PaymentMethodsRepository;
use App\adms\Models\Repository\ReceiptsRepository;
use App\adms\Models\Repository\CustomerRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para editar Conta
 *
 * Esta classe é responsável por gerenciar a edição de informações de uma Conta existente. Inclui a validação dos dados
 * do formulário, a atualização das informações do Conta no repositório e a renderização da visualização apropriada.
 * Caso haja algum problema, como um Conta não encontrado ou dados inválidos, mensagens de erro são exibidas e registradas.
 *
 * @package App\adms\Controllers\receive
 * @author Rafael Mendes
 */
class Receive
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $dataBD = null;

    /**
     * Editar o Conta.
     *
     * Este método gerencia o processo de edição de um Conta. Recebe os dados do formulário, valida o CSRF token e
     * a existência do Conta, e chama o método adequado para editar o Conta ou carregar a visualização de edição.
     *
     * @param int|string $id ID do Conta a ser editado.
     * 
     * @return void
     */
    public function index(int|string $id): void
    {
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Validar o CSRF token e a existência do ID da conta
        if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_receive', $this->data['form']['csrf_token'])) {
            // Editar o Conta
            // Recuperar o registro do Conta
            $viewReceive = new ReceiptsRepository();

            $this->dataBD = $viewReceive->getReceive((int) $id);

            $this->downReceive();
        } else {
            // Recuperar o registro do Conta
            $viewReceive = new ReceiptsRepository();
            $this->data['form'] = $viewReceive->getReceive((int) $id);

            // var_dump($this->data['form']);

            // Verificar se a Conta foi encontrado
            if (!$this->data['form']) {
                // Registrar o erro e redirecionar
                GenerateLog::generateLog("error", "Conta não encontrado", ['id' => (int) $id]);
                $_SESSION['error'] = "Conta não encontrada!";
                header("Location: {$_ENV['URL_ADM']}list-receipts");
                return;
            }

            // Atualizar o campo busy e user_temp
            $receiveRepo = new ReceiptsRepository();
            $receiveRepo->updateBusy((int) $id, $_SESSION['user_id']); // ou use o ID de usuário que tiver

            // Carregar a visualização para edição do Conta
            $this->viewReceive();
        }
    }

    /**
     * Carregar a visualização para edição do Conta.
     *
     * Este método define o título da página e carrega a visualização de edição da Conta com os dados necessários.
     * 
     * @return void
     */
    private function viewReceive(): void
    {
        // Instanciar o repositório para recuperar os fornecedores
        $listCustomers = new CustomerRepository();
        $this->data['listCustomers'] = $listCustomers->getAllCustomersSelect();

        // Instanciar o repositório para recuperar as frequencias
        $listFrequencies = new FrequencyRepository();
        $this->data['listFrequencies'] = $listFrequencies->getAllFrequencySelect();

        // Instanciar o repositório para formas de pagamento
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
            'title_head' => 'Editar Conta',
            'menu' => 'list-receipts',
            'buttonPermission' => ['ListReceipts', 'ViewReceive'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/receive/receive", $this->data);
        $loadView->loadView();

        $viewReceive = new \App\adms\Models\Repository\ReceiptsRepository();
        $receive = $viewReceive->getReceive((int)($this->data['form']['id_receive'] ?? $this->data['form']['id'] ?? 0));
        if ($receive) {
            // Buscar movimentos usando PartialValuesRepository
            $viewMovementValues = new \App\adms\Models\Repository\PartialValuesRepository();
            $idReceive = (int)($this->data['form']['id_receive'] ?? $this->data['form']['id'] ?? 0);
            $movements = $viewMovementValues->getMovementValues($idReceive);

            $totalRecebido = 0;
            $totalDesconto = 0;
            if (!empty($movements)) {
                foreach ($movements as $mov) {
                    $totalRecebido += $mov['movement_value'];
                    $totalDesconto += $mov['discount_value'] ?? 0;
                }
            }
            if ($totalDesconto > 0) {
                $valorRestante = $receive['original_value'] - ($totalRecebido + $totalDesconto);
            } else {
                $valorRestante = $receive['original_value'] - $totalRecebido;
            }
            if ($valorRestante < 0) {
                $valorRestante = 0;
            }
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($this->data['form']['value'])) {
                // Não sobrescreve o valor digitado pelo usuário
            } else {
                // Preenche com o saldo a pagar calculado
                $this->data['form']['value'] = number_format($valorRestante, 2, '.', '');
            }
        }

        // Preencher campos obrigatórios se não vierem do formulário
        $camposObrigatorios = ['partner_id', 'frequency_id', 'cost_center_id', 'account_id', 'installment_number', 'issue_date', 'doc_date', 'due_date', 'expected_date', 'num_doc', 'num_nota', 'description'];
        foreach ($camposObrigatorios as $campo) {
            if (!isset($this->data['form'][$campo]) && isset($this->dataBD[$campo])) {
                $this->data['form'][$campo] = $this->dataBD[$campo];
            }
        }

        // Garantir que os campos de multa, juros e desconto estejam presentes
        // (removido pois não existem mais no banco)
    }

    /**
     * Editar o Conta.
     *
     * Este método valida os dados do formulário, atualiza as informações do Conta no repositório e lida com o resultado
     * da operação. Se a atualização for bem-sucedida, o Conta é redirecionado para a página de visualização do Conta.
     * Caso contrário, uma mensagem de erro é exibida e a visualização de edição é recarregada.
     * 
     * @return void
     */
    private function downReceive(): void
    {
        // Validação obrigatória de issue_date e num_nota
        if (empty($this->dataBD['issue_date']) || empty($this->dataBD['num_nota'])) {
            $this->data['errors'][] = "Para realizar o recebimento, é obrigatório informar a Data de Emissão e o Número da Nota.";
            $this->viewReceive();
            return;
        }

        // Validar os dados do formulário
        $validationReceive = new ValidationReceiveService();
        $this->data['errors'] = $validationReceive->validate($this->dataBD, $this->data['form']);

        // Se houver erros de validação, recarregar a visualização
        if (!empty($this->data['errors'])) {
            $this->viewReceive();
            return;
        }

        // $resultOriginalValue = $validationPay->validateOriginalValue($this->dataBD, $this->data['form']);
        // // Atualizar a Conta
        $receiveUpdate = new ReceiptsRepository();
        // var_dump($this->dataBD);
        // var_dump($this->data['form']);
        // var_dump($resultOriginalValue);
        // exit;

        // if ($resultOriginalValue) {
            // Aqui seria edição completa, mas para pagamento/baixa 
            
                $resultMovement = $receiveUpdate->createMovement($this->dataBD, $this->data['form']);

                // Verificar o resultado da atualização
                if ($resultMovement) {

                    // gravar logs na tabela adms-logs
                    if ($_ENV['APP_LOGS'] == 'Sim') {
                        $dataLogs = [
                            'table_name' => 'adms_receive',
                            'action' => 'edição',
                            'record_id' => $this->data['form']['id_receive'],
                            'description' => $this->data['form']['num_doc'],

                        ];
                        // Instanciar a classe validar  o usuário
                        $insertLogs = new LogsRepository();
                        $insertLogs->insertLogs($dataLogs);
                    }

                    // Após editar e baixar a conta, liberar o "busy"
                    $receiveUpdate->clearBusy($this->data['form']['id_receive']);

                    $_SESSION['success'] = "Conta paga/baixada com sucesso!";
                    header("Location: {$_ENV['URL_ADM']}view-receive/{$this->data['form']['id_receive']}");
                } else {
                    $this->data['errors'][] = "Conta não editada!";
                    $this->viewReceive();
                }
            // }
        // $_SESSION['success'] = "Conta paga/baixada com sucesso!";
        // header("Location: {$_ENV['URL_ADM']}view-pay/{$this->data['form']['id_pay']}");
    }
}
