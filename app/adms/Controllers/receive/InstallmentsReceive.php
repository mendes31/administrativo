<?php

namespace App\adms\Controllers\receive;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\Validation\ValidationReceiveInstallmentsService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\CustomerRepository;
use App\adms\Models\Repository\FrequencyRepository;
use App\adms\Models\Repository\InstallmentsReceiveRepository;
use App\adms\Models\Repository\LogsRepository;
use App\adms\Views\Services\LoadViewService;
use App\adms\Models\Repository\PartialValuesRepository;
use App\adms\Models\Repository\ReceiptsRepository;

/**
 * Controller para Parcelar Conta
 *
 * Esta classe é responsável por gerenciar a edição de informações de uma Conta existente. Inclui a validação dos dados
 * do formulário, a atualização das informações do Conta no repositório e a renderização da visualização apropriada.
 * Caso haja algum problema, como um Conta não encontrado ou dados inválidos, mensagens de erro são exibidas e registradas.
 *
 * @package App\adms\Controllers\receive
 * @author Rafael Mendes
 */
class InstallmentsReceive
{
    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    /** @var array|string|null $data Dados que devem ser enviados para a VIEW */
    private array|string|null $dataBD = null;

    /**
     * Parcelar o Conta.
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
        if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_installments_receive', $this->data['form']['csrf_token'])) {
            // Editar o Conta
            // Recuperar o registro do Conta
            $viewReceiveInstallments = new InstallmentsReceiveRepository();
            $this->dataBD = $viewReceiveInstallments->getReceiveInstallments((int) $id);
            // var_dump($this->dataBD);
            $this->editReceiveInstallments();
            
        } else {
            // Recuperar o registro do Conta
            $viewReceiveInstallments = new InstallmentsReceiveRepository();
            $this->data['form'] = $viewReceiveInstallments->getReceiveInstallments((int) $id);
            // var_dump($this->data['form']);
            
            // // Preencher o campo 'value' com o valor atual da conta
            // if (!isset($this->data['form']['value']) && isset($this->data['form']['original_value'])) {
            //     $this->data['form']['value'] = $this->data['form']['original_value'];
            // }

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
        // Instanciar o repositório para recuperar os clientes
        $listCustomers = new CustomerRepository();
        $this->data['listCustomers'] = $listCustomers->getAllCustomersSelect();

        // Instanciar o repositório para recuperar as frequencias
        $listFrequencies = new FrequencyRepository();
        $this->data['listFrequencies'] = $listFrequencies->getAllFrequencySelect();


        // Definir o título da página
        // Ativar o item de menu
        // Apresentar ou ocultar botão 
        $pageElements = [
            'title_head' => 'Parcelar Conta a Receber',
            'menu' => 'list-receipts',
            'buttonPermission' => ['ListReceipts', 'ViewReceive'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/receive/installments", $this->data);
        $loadView->loadView();
    }

    /**
     * Parcelar o Conta.
     *
     * Este método valida os dados do formulário, atualiza as informações da Conta no repositório e lida com o resultado
     * da operação. Se a atualização for bem-sucedida, o Conta é redirecionado para a página de visualização do Conta.
     * Caso contrário, uma mensagem de erro é exibida e a visualização de edição é recarregada.
     * 
     * @return void
     */
    private function editReceiveInstallments(): void
    {
        // var_dump($this->data['form']);
        // Garantir que o campo 'original_value' esteja preenchido
        if (!isset($this->data['form']['original_value'])) {
            $receiveRepo = new \App\adms\Models\Repository\ReceiveRepository();
            $conta = $receiveRepo->getReceive((int)$this->data['form']['id_receive']);

            // var_dump($conta);
            
            if ($conta && isset($conta['original_value'])) {
                $this->data['form']['original_value'] = $conta['original_value'];
            } else {
                $this->data['form']['original_value'] = $this->data['form']['value'] ?? 0;
            }
        }
        // var_dump($this->data['form']);
        // var_dump($this->data['form']['value']);
        
        // Atualizar a Conta
        $receiveInstallmentsUpdate = new InstallmentsReceiveRepository();
        $resultReceiveIds = $receiveInstallmentsUpdate->getReceiveIds((int) $this->data['form']['id_receive']);
        // var_dump($this->data['form']);
        // var_dump($resultReceiveIds);

        // Instanciar o repositório para recuperar as frequencias
        $listFrequencies = new FrequencyRepository();
        $this->data['listFrequencies'] = $listFrequencies->getAllFrequencySelect();
        // var_dump($this->data['form']);
        // Validar os dados do formulário
        $validationReceiveInstallments = new ValidationReceiveInstallmentsService();
        $this->data['errors'] = $validationReceiveInstallments->validate($this->data['form']);

        // Se houver erros de validação, recarregar a visualização
        if (!empty($this->data['errors'])) {
            $this->viewReceive();
            return;
        }

        // Buscar movimentos usando PartialValuesRepository
        $viewMovementValues = new PartialValuesRepository();
        $idConta = (int)($this->data['form']['id'] ?? $id ?? $this->data['form']['id_receive'] ?? 0);
        $movements = $viewMovementValues->getMovementValues($idConta);

        $totalRecebido = 0;
        $totalDesconto = 0;
        if (!empty($movements)) {
            foreach ($movements as $mov) {
                $totalRecebido += $mov['movement_value'];
                $totalDesconto += $mov['discount_value'] ?? 0;
            }
        }
        if ($totalDesconto > 0) {
            $saldoReceber = $this->data['form']['original_value'] - ($totalRecebido + $totalDesconto);
        } else {
            $saldoReceber = $this->data['form']['original_value'] - $totalRecebido;
        }
        if ($saldoReceber < 0) {
            $saldoReceber = 0;
        }
        $this->data['form']['value'] = number_format($this->data['form']['original_value'], 2, '.', '');

        for ($i = 1; $i <= (int) $this->data['form']['installments']; $i++) {
            // Validar os dados do formulário
            $this->data['errors'] = $validationReceiveInstallments->validate($this->data['form']);

            $nova_num_doc = $resultReceiveIds['0']['num_doc'];
            $nova_descricao = (!empty($resultReceiveIds['0']['description']) ? $resultReceiveIds['0']['description'] : '') . ' - Parcela ' . $i;
            $novo_valor = $resultReceiveIds['0']['original_value'] / $this->data['form']['installments'];
            $dias_parcela = $i - 1;
            $dias_parcela_2 = ($i - 1) * $this->data['listFrequencies']['0']['days'];
            $novo_vencimento = $resultReceiveIds['0']['due_date'];

            if ($this->data['form']['frequency_id'] == 2) {
                $dias_parcela_2 = ($i - 1) * 1;
                $novo_vencimento = date('Y/m/d', strtotime("+$dias_parcela_2 days", strtotime($resultReceiveIds['0']['due_date'])));
            } else if ($this->data['form']['frequency_id'] == 3) {
                $dias_parcela = ($i - 1) * 7;
                $novo_vencimento = date('Y/m/d', strtotime("+$dias_parcela_2 days", strtotime($resultReceiveIds['0']['due_date'])));
            } else if ($this->data['form']['frequency_id'] == 4) {
                $dias_parcela = ($i - 1);
                $novo_vencimento = date('Y/m/d', strtotime("+$dias_parcela month", strtotime($resultReceiveIds['0']['due_date'])));
            }

            $novo_valor = number_format($novo_valor, 2, ',', '.');
            $novo_valor = str_replace('.', '', $novo_valor);
            $novo_valor = str_replace(',', '.', $novo_valor);
            $resto_conta = $resultReceiveIds['0']['original_value'] - $novo_valor * $this->data['form']['installments'];
            $resto_conta = number_format($resto_conta, 2);

            if ($i == $this->data['form']['installments']) {
                $novo_valor = $novo_valor + $resto_conta;
            }

            // Passar a nova descrição para o campo description
            $this->data['form']['description'] = $nova_descricao;
            $result = $receiveInstallmentsUpdate->createReceive($this->data, $resultReceiveIds, $nova_num_doc, $novo_vencimento, $novo_valor, $i);

            // var_dump([
            //     'this->data' => $this->data,
            //     'resultReceiveIds' => $resultReceiveIds,
            //     'nova_num_doc' => $nova_num_doc,
            //     'novo_vencimento' => $novo_vencimento,
            //     'novo_valor' => $novo_valor,
            // ]);
            // Verificar o resultado da atualização
            if ($result) {

                // gravar logs na tabela adms-logs
                if ($_ENV['APP_LOGS'] == 'Sim') {
                    $dataLogs = [
                        'table_name' => 'adms_receive',
                        'action' => 'inserção',
                        'record_id' => $this->data['form']['id_receive'],
                        'description' => "Doc: ". "{$this->data['form']['num_doc']} " ." - " . "{$this->dataBD['card_name']}" . " (Parcelamento)",

                    ];
                    // Instanciar a classe validar  o usuário
                    $insertLogs = new LogsRepository();
                    $insertLogs->insertLogs($dataLogs);
                }

                $receiveInstallmentsUpdate->deleteReceive($this->data['form']['id_receive']);

                $_SESSION['success'] = "Conta editada com sucesso!";
                header("Location: {$_ENV['URL_ADM']}list-receipts");
                // header("Location: {$_ENV['URL_ADM']}view-pay/{$this->data['form']['id']}");
            } else {
                $this->data['errors'][] = "Conta não editado!";
                $this->viewReceive();
            }
        }
    }
}
