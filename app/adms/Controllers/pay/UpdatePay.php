<?php

namespace App\adms\Controllers\pay;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationPaymentsService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\AccountPlanRepository;
use App\adms\Models\Repository\CostCentersRepository;
use App\adms\Models\Repository\FrequencyRepository;
use App\adms\Models\Repository\LogsRepository;
use App\adms\Models\Repository\PaymentsRepository;
use App\adms\Models\Repository\SupplierRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para editar Conta
 *
 * Esta classe é responsável por gerenciar a edição de informações de uma Conta existente. Inclui a validação dos dados
 * do formulário, a atualização das informações do Conta no repositório e a renderização da visualização apropriada.
 * Caso haja algum problema, como um Conta não encontrado ou dados inválidos, mensagens de erro são exibidas e registradas.
 *
 * @package App\adms\Controllers\pay
 * @author Rafael Mendes
 */
class UpdatePay
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
        // var_dump($id);
        // exit;
        // Receber os dados do formulário
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        // Validar o CSRF token e a existência do ID da conta
        if (
            isset($this->data['form']['csrf_token']) &&
            CSRFHelper::validateCSRFToken('form_update_pay', $this->data['form']['csrf_token'])
        ) {
            // Editar o Conta
            $this->editPay();
        } else {
            // Recuperar o registro do Conta
            $viewPay = new PaymentsRepository();
            $this->data['form'] = $viewPay->getPay((int) $id);
         

            // Verificar se a Conta foi encontrado
            if (!$this->data['form']) {
                // Registrar o erro e redirecionar
                GenerateLog::generateLog("error", "Conta não encontrado", ['id' => (int) $id]);
                $_SESSION['error'] = "Conta não encontrada!";
                header("Location: {$_ENV['URL_ADM']}list-payments");
                return;

                
            }

            // Atualizar o campo busy e user_temp
            $payRepo = new PaymentsRepository();
            $payRepo->updateBusy((int) $id, $_SESSION['user_id']); // ou use o ID de usuário que tiver

            // Buscar movimentos usando PartialValuesRepository
            $viewMovementValues = new \App\adms\Models\Repository\PartialValuesRepository();
            $movements = $viewMovementValues->getMovementValues((int)$id);

            $totalPago = 0;
            $totalDesconto = 0;
            if (!empty($movements)) {
                foreach ($movements as $mov) {
                    $totalPago += $mov['movement_value'];
                    $totalDesconto += $mov['discount_value'] ?? 0;
                }
            }
            if ($totalDesconto > 0) {
                $saldoPagar = $this->data['form']['original_value'] - ($totalPago + $totalDesconto);
            } else {
                $saldoPagar = $this->data['form']['original_value'] - $totalPago;
            }
            if ($saldoPagar < 0) {
                $saldoPagar = 0;
            }
            $this->data['form']['value'] = number_format($saldoPagar, 2, '.', '');

            // Carregar a visualização para edição do Conta
            $this->viewPay();

            
        }
    }

    /**
     * Carregar a visualização para edição do Conta.
     *
     * Este método define o título da página e carrega a visualização de edição da Conta com os dados necessários.
     * 
     * @return void
     */
    private function viewPay(): void
    {
        // Instanciar o repositório para recuperar os fornecedores
        $listSuppliers = new SupplierRepository();
        $this->data['listSuppliers'] = $listSuppliers->getAllSuppliersSelect();

        // Instanciar o repositório para recuperar as frequencias
        $listFrequencies = new FrequencyRepository();
        $this->data['listFrequencies'] = $listFrequencies->getAllFrequencySelect();

        // Instanciar o repositório para recuperar os planos de conta
        $listAccountsPlan = new AccountPlanRepository();
        $this->data['listAccountsPlan'] = $listAccountsPlan->getAllAccountsPlanSelect();

        // Instanciar o repositório para recuperar os centros de custo
        $listCostCenters = new CostCentersRepository();
        $this->data['listCostCenters'] = $listCostCenters->getAllCostCenterSelect();

        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Editar Conta',
            'menu' => 'list-payments',
            'buttonPermission' => ['ListPayments', 'ViewPay'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/pay/update", $this->data);
        $loadView->loadView();
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
    private function editPay(): void
    {
        // Buscar dados atuais da conta no banco
        $payRepo = new PaymentsRepository();
        $contaAtual = $payRepo->getPay((int)$this->data['form']['id']);

       
        // Garantir que partner_id seja inteiro ou null
        if (empty($this->data['form']['partner_id'])) {
            $this->data['form']['partner_id'] = null;
        } else {
            $this->data['form']['partner_id'] = (int)$this->data['form']['partner_id'];
        }


        // Garantir que installment_number nunca seja NULL
        if (empty($this->data['form']['installment_number'])) {
            // Buscar valor atual do banco
            if (isset($contaAtual['installment_number'])) {
                $this->data['form']['installment_number'] = $contaAtual['installment_number'];
            } else {
                $this->data['form']['installment_number'] = 1; // fallback seguro
            }
        }

        // Sempre que for edição, original_value recebe o novo value
        $this->data['form']['original_value'] = $this->data['form']['value'];
        
        // Atualizar o campo card_code_fornecedor conforme o partner_id
        if (!empty($this->data['form']['partner_id'])) {
            $supplierRepo = new \App\adms\Models\Repository\SupplierRepository();
            $cardCode = $supplierRepo->getSupplier($this->data['form']['partner_id'])['card_code'] ?? '';
            $this->data['form']['card_code_fornecedor'] = $cardCode;
        }




        // Validar os dados do formulário
        $validationPay = new ValidationPaymentsService();
        $this->data['errors'] = $validationPay->validate($this->data['form']);

     
    

        // Se houver erros de validação, recarregar a visualização
        if (!empty($this->data['errors'])) {
            $this->viewPay();
            return;
        }

       //var_dump($this->data['form']);
     
        
        // Atualizar a Conta
        $payUpdate = new PaymentsRepository();
        $result = $payUpdate->updatePay($this->data['form']);
        //var_dump($this->data['form']);
        //exit;


        

        // Verificar o resultado da atualização
        if ($result) {
            // gravar logs na tabela adms-logs
            if ($_ENV['APP_LOGS'] == 'Sim') {
                $dataLogs = [
                    'table_name' => 'adms_pay',
                    'action' => 'edição',
                    'record_id' => $this->data['form']['id'],
                    'description' => $this->data['form']['num_doc'],
                ];
                // Instanciar a classe validar  o usuário
                $insertLogs = new LogsRepository();
                $insertLogs->insertLogs($dataLogs);
            }
            $_SESSION['success'] = "Conta editada com sucesso!";
            header("Location: {$_ENV['URL_ADM']}view-pay/{$this->data['form']['id']}");
        } else {
            $this->data['errors'][] = "Conta não editado!";
            $this->viewPay();
        }
    }
}
