<?php

namespace App\adms\Controllers\trainings;

use App\adms\Models\Repository\TrainingsRepository;
use App\adms\Models\Repository\TrainingUsersRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;
use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\TrainingApplicationsRepository;
use App\adms\Helpers\LogHelper;

class ApplyTraining
{
    private array $data = [];

    public function index($param = null): void
    {
        // Se houver dados do formulário em sessão, repopular
        if (!empty($_SESSION['form_apply_training'])) {
            foreach ($_SESSION['form_apply_training'] as $key => $value) {
                $this->data[$key] = $value;
            }
            unset($_SESSION['form_apply_training']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->apply();
            return;
        }

        // Padrão: /controller/1/1 => $param = '1/1'
        $user_id = null;
        $training_id = null;
        if ($param && preg_match('/^([0-9]+)\/([0-9]+)$/', $param, $matches)) {
            $user_id = $matches[1];
            $training_id = $matches[2];
        } else {
            $user_id = $param ?? ($_GET['user_id'] ?? null);
            $training_id = $_GET['training_id'] ?? null;
        }
        $this->data['user_id'] = $user_id;
        $this->data['training_id'] = $training_id;
        $this->data['edit_id'] = $_GET['edit_id'] ?? null;

        if (!$this->data['training_id'] || !$this->data['user_id']) {
            header("Location: " . $_ENV['URL_ADM'] . "list-training-status");
            exit;
        }

        $trainingsRepo = new TrainingsRepository();
        $usersRepo = new UsersRepository();
        $applicationsRepo = new TrainingApplicationsRepository();

        // Buscar dados básicos
        $this->data['training'] = $trainingsRepo->getTraining($this->data['training_id']);
        $this->data['user'] = $usersRepo->getUser($this->data['user_id']);

        if (!$this->data['training'] || !$this->data['user']) {
            header("Location: " . $_ENV['URL_ADM'] . "list-training-status");
            exit;
        }

        // Buscar vínculo do usuário com o treinamento para obter a data de criação
        $trainingUsersRepo = new TrainingUsersRepository();
        $this->data['trainingUser'] = $trainingUsersRepo->getByUserAndTraining($this->data['user_id'], $this->data['training_id']);

        // Buscar lista de usuários para select de instrutor interno
        $this->data['listUsers'] = $usersRepo->getAllUsersSelect();

        // Se for edição, buscar dados da aplicação
        if ($this->data['edit_id']) {
            $this->data['application'] = $applicationsRepo->getById($this->data['edit_id']);
            if (!$this->data['application']) {
                header("Location: " . $_ENV['URL_ADM'] . "list-training-status");
                exit;
            }
        }

        // Preencher formulário
        $this->data['form_data_realizacao'] = $this->data['application']['data_realizacao'] ?? date('Y-m-d');
        $this->data['form_data_agendada'] = $this->data['application']['data_agendada'] ?? '';
        $this->data['form_nota'] = $this->data['application']['nota'] ?? '';
        $this->data['form_observacoes'] = $this->data['application']['observacoes'] ?? '';
        $this->data['form_instrutor_nome'] = $this->data['application']['instrutor_nome'] ?? '';
        $this->data['form_instrutor_email'] = $this->data['application']['instrutor_email'] ?? '';
        
        // Determinar tipo de instrutor para preencher o select corretamente
        $this->data['form_instructor_type'] = '';
        $this->data['form_instructor_user_id'] = '';
        
        if (!empty($this->data['application']['instrutor_nome']) && !empty($this->data['application']['instrutor_email'])) {
            // Verificar se é um usuário interno
            foreach ($this->data['listUsers'] as $user) {
                if ($user['name'] === $this->data['application']['instrutor_nome'] && 
                    $user['email'] === $this->data['application']['instrutor_email']) {
                    $this->data['form_instructor_type'] = 'internal';
                    $this->data['form_instructor_user_id'] = $user['id'];
                    break;
                }
            }
            // Se não encontrou como interno, é externo
            if (empty($this->data['form_instructor_type'])) {
                $this->data['form_instructor_type'] = 'external';
            }
        } else {
            // Se não é edição, preencher automaticamente com o instrutor do cadastro do treinamento
            if (!$this->data['edit_id']) {
                if (!empty($this->data['training']['instructor_user_id'])) {
                    // Instrutor interno - buscar dados do usuário
                    $instructorUser = $usersRepo->getUser($this->data['training']['instructor_user_id']);
                    if ($instructorUser) {
                        $this->data['form_instructor_type'] = 'internal';
                        $this->data['form_instructor_user_id'] = $instructorUser['id'];
                        $this->data['form_instrutor_nome'] = $instructorUser['name'];
                        $this->data['form_instrutor_email'] = $instructorUser['email'];
                    }
                } elseif (!empty($this->data['training']['instructor_name'])) {
                    // Instrutor externo
                    $this->data['form_instructor_type'] = 'external';
                    $this->data['form_instrutor_nome'] = $this->data['training']['instructor_name'];
                    $this->data['form_instrutor_email'] = $this->data['training']['instructor_email'] ?? '';
                }
            }
        }

        // Elementos de página
        $pageElements = [
            'title_head' => 'Registrar Aplicação de Treinamento',
            'menu' => 'apply-training',
            'buttonPermission' => ['ApplyTraining'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/trainings/applyTraining', $this->data);
        $loadView->loadView();
    }

    public function apply(): void
    {
        // LOG DE DEBUG - Verificar se o método está sendo chamado
        error_log("=== APPLY TRAINING CHAMADO ===");
        error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
        error_log("POST count: " . count($_POST));
        error_log("POST recebido: " . print_r($_POST, true));
        error_log("SESSION user_id: " . ($_SESSION['user_id'] ?? 'NÃO DEFINIDO'));
        error_log("Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'NÃO DEFINIDO'));
        error_log("Content-Length: " . ($_SERVER['CONTENT_LENGTH'] ?? 'NÃO DEFINIDO'));
        
        // VERIFICAÇÃO CRÍTICA: Se POST está vazio, pode ser problema de configuração
        if (empty($_POST)) {
            error_log("⚠️ ALERTA: POST VAZIO - Possível problema de configuração do servidor!");
            error_log("php://input: " . file_get_contents('php://input'));
            $_SESSION['msg'] = "Erro: Dados do formulário não foram recebidos. Verifique a configuração do servidor.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "list-training-status");
            exit;
        }
        
        // Antes de cada redirecionamento de erro, salvar os dados do formulário em sessão
        function saveFormSession() {
            $_SESSION['form_apply_training'] = [
                'form_data_realizacao' => $_POST['data_realizacao'] ?? '',
                'form_data_avaliacao' => $_POST['data_avaliacao'] ?? '',
                'form_data_agendada' => $_POST['data_agendada'] ?? '',
                'form_nota' => $_POST['nota'] ?? '',
                'form_observacoes' => $_POST['observacoes'] ?? '',
                'form_instrutor_nome' => $_POST['instrutor_nome'] ?? '',
                'form_instrutor_email' => $_POST['instrutor_email'] ?? '',
                'form_instructor_type' => $_POST['instructor_type'] ?? '',
                'form_instructor_user_id' => $_POST['instructor_user_id'] ?? '',
            ];
        }

        $training_id = (int) ($_POST['training_id'] ?? 0);
        $user_id = (int) ($_POST['user_id'] ?? 0);
        
        error_log("Training ID: $training_id, User ID: $user_id");
        $edit_id = (int) ($_POST['edit_id'] ?? 0);
        
        $data_realizacao = $_POST['data_realizacao'] ?? null;
        $data_avaliacao = $_POST['data_avaliacao'] ?? null;
        $data_agendada = $_POST['data_agendada'] ?? null;
        $nota = $_POST['nota'] ?? null;
        $observacoes = $_POST['observacoes'] ?? null;
        $instructor_type = $_POST['instructor_type'] ?? null;
        $instructor_user_id = (int) ($_POST['instructor_user_id'] ?? 0);
        $instrutor_nome = $_POST['instrutor_nome'] ?? null;
        $instrutor_email = $_POST['instrutor_email'] ?? null;
        $aplicado_por = $_SESSION['user_id'] ?? null;

        $redirectUrl = $_ENV['URL_ADM'] . "apply-training?training_id={$training_id}&user_id={$user_id}";
        if (!empty($edit_id)) {
            $redirectUrl .= "&edit_id={$edit_id}";
        }

        // Instanciar repositories
        $trainingsRepo = new TrainingsRepository();

        // Validações básicas
        if (!$training_id || !$user_id) {
            $_SESSION['msg'] = "Dados obrigatórios não informados.";
            $_SESSION['msg_type'] = "danger";
            saveFormSession();
            header("Location: " . $redirectUrl);
            exit;
        }
        // Validação da nota (OPCIONAL, mas se informada deve estar entre 0 e 10)
        if ($nota !== null && $nota !== '' && (!is_numeric($nota) || $nota < 0 || $nota > 10)) {
            $_SESSION['msg'] = "A nota deve estar entre 0 e 10.";
            $_SESSION['msg_type'] = "danger";
            saveFormSession();
            header("Location: " . $redirectUrl);
            exit;
        }
        // Data de avaliação é obrigatória quando houver NOTA
        if (!empty($nota) && $nota !== '' && empty($data_avaliacao)) {
            $_SESSION['msg'] = "Data de avaliação é obrigatória quando há nota informada.";
            $_SESSION['msg_type'] = "danger";
            saveFormSession();
            header("Location: " . $redirectUrl);
            exit;
        }

        // Validação de data de avaliação (entre realização e hoje)
        if ($data_avaliacao) {
            if ($data_avaliacao > date('Y-m-d')) {
                $_SESSION['msg'] = "Data de avaliação deve ser até hoje.";
                $_SESSION['msg_type'] = "danger";
                saveFormSession();
                header("Location: " . $redirectUrl);
                exit;
            }
            if ($data_realizacao && $data_avaliacao < $data_realizacao) {
                $_SESSION['msg'] = "Data de avaliação deve ser igual ou posterior à data de realização.";
                $_SESSION['msg_type'] = "danger";
                saveFormSession();
                header("Location: " . $redirectUrl);
                exit;
            }
        }

        // Deve ter pelo menos uma data
        if (!$data_realizacao && !$data_agendada) {
            $_SESSION['msg'] = "Informe a data de realização ou agendamento.";
            $_SESSION['msg_type'] = "danger";
            saveFormSession();
            header("Location: " . $redirectUrl);
            exit;
        }

        // Validações de data
        if ($data_realizacao) {
            // Data de realização não pode ser superior à data atual
            if ($data_realizacao > date('Y-m-d')) {
                $_SESSION['msg'] = "Data de realização não pode ser superior à data atual.";
                $_SESSION['msg_type'] = "danger";
                saveFormSession();
                header("Location: " . $redirectUrl);
                exit;
            }
            
            // Data de realização não pode ser anterior à data de criação do vínculo
            $trainingUsersRepo = new TrainingUsersRepository();
            $trainingUser = $trainingUsersRepo->getByUserAndTraining($user_id, $training_id);
            if ($trainingUser && !empty($trainingUser['created_at'])) {
                $dataCriacaoVinculo = date('Y-m-d', strtotime($trainingUser['created_at']));
                if ($data_realizacao < $dataCriacaoVinculo) {
                    $_SESSION['msg'] = "Data de realização não pode ser anterior à data de criação do vínculo (" . date('d/m/Y', strtotime($dataCriacaoVinculo)) . ").";
                    $_SESSION['msg_type'] = "danger";
                    saveFormSession();
                    header("Location: " . $redirectUrl);
                    exit;
                }
            }
        }

        if ($data_agendada && $data_agendada < date('Y-m-d')) {
            $_SESSION['msg'] = "Data de agendamento não pode ser retroativa.";
            $_SESSION['msg_type'] = "danger";
            saveFormSession();
            header("Location: " . $redirectUrl);
            exit;
        }

        // Validação obrigatória do tipo de instrutor
        if (empty($instructor_type)) {
            $_SESSION['msg'] = "Selecione o tipo de instrutor.";
            $_SESSION['msg_type'] = "danger";
            saveFormSession();
            header("Location: " . $redirectUrl);
            exit;
        }
        // Validação obrigatória do instrutor conforme o tipo
        if ($instructor_type === 'internal') {
            if (empty($instructor_user_id)) {
                $_SESSION['msg'] = "Selecione o instrutor interno.";
                $_SESSION['msg_type'] = "danger";
                saveFormSession();
                header("Location: " . $redirectUrl);
                exit;
            }
        } elseif ($instructor_type === 'external') {
            if (empty($instrutor_nome) || empty($instrutor_email)) {
                $_SESSION['msg'] = "Informe o nome e e-mail do instrutor externo.";
                $_SESSION['msg_type'] = "danger";
                saveFormSession();
                header("Location: " . $redirectUrl);
                exit;
            }
        }

        // Processar dados do instrutor
        $real_instructor_nome = null;
        $real_instructor_email = null;
        $instructor_user_id_to_save = null;
        if (!empty($instructor_user_id)) {
            // Instrutor interno - buscar nome e e-mail do usuário
            $usersRepo = new UsersRepository();
            $user = $usersRepo->getUser((int)$instructor_user_id);
            if ($user) {
                $real_instructor_nome = $user['name'];
                $real_instructor_email = $user['email'];
                $instructor_user_id_to_save = $user['id'];
            }
        } elseif (!empty($instrutor_nome)) {
            // Instrutor externo
            $real_instructor_nome = $instrutor_nome;
            $real_instructor_email = $instrutor_email;
            $instructor_user_id_to_save = null;
        }

        $applicationsRepo = new TrainingApplicationsRepository();
        $trainingUsersRepo = new TrainingUsersRepository();
        $trainingsRepo = new TrainingsRepository();
        
        try {
            error_log("=== PREPARANDO DADOS PARA SALVAR ===");
            
            $dados = [
                'adms_user_id' => $user_id,
                'adms_training_id' => $training_id,
                'data_realizacao' => $data_realizacao,
                'data_avaliacao' => $data_avaliacao,
                'data_agendada' => $data_agendada,
                'nota' => $nota,
                'observacoes' => $observacoes,
                'instrutor_nome' => $real_instructor_nome,        // CORRIGIDO: usar o valor processado
                'instrutor_email' => $real_instructor_email,      // CORRIGIDO: usar o valor processado
                'instructor_user_id' => $instructor_user_id_to_save,
                'real_instructor_nome' => $real_instructor_nome,
                'real_instructor_email' => $real_instructor_email,
                'aplicado_por' => $aplicado_por,
                'status' => $data_realizacao ? 'concluido' : 'agendado'
            ];
            
            error_log("Dados a salvar: " . print_r($dados, true));

            // Atualizar vínculo principal
            error_log("=== ATUALIZANDO VÍNCULO PRINCIPAL ===");
            $resultVinculo = $trainingUsersRepo->applyTraining($user_id, $training_id, [
                'data_realizacao' => $data_realizacao,
                'data_agendada' => $data_agendada,
                'nota' => $nota,
                'observacoes' => $observacoes,
                'status' => $data_realizacao ? 'concluido' : 'agendado'
            ]);
            error_log("Resultado vínculo: " . ($resultVinculo ? 'SUCESSO' : 'FALHA'));

            if ($edit_id) {
                // Atualização
                error_log("=== MODO EDIÇÃO - ID: $edit_id ===");
                $oldData = $applicationsRepo->getById($edit_id);
                $resultUpdate = $applicationsRepo->update($edit_id, $dados);
                error_log("Resultado update: " . ($resultUpdate ? 'SUCESSO' : 'FALHA'));
                LogHelper::logUpdate('adms_training_applications', $edit_id, $oldData, $dados, $aplicado_por);
                $msg = "Aplicação atualizada com sucesso!";
            } else {
                // Inserção
                error_log("=== MODO INSERÇÃO ===");
                $newId = $applicationsRepo->insert($dados);
                error_log("Novo ID retornado: " . ($newId ?: 'FALHA'));
                
                if ($newId) {
                    LogHelper::log('adms_training_applications', 'inserção', $newId, 'Nova aplicação de treinamento', $aplicado_por);
                    $msg = $data_realizacao ? "Treinamento registrado como realizado!" : "Treinamento agendado com sucesso!";
                } else {
                    throw new \Exception("Falha ao inserir aplicação - ID retornado: " . var_export($newId, true));
                }
            }

            // Se foi realizado, analisar aprovação/reprovação e reciclagem
            if ($data_realizacao) {
                $training = $trainingsRepo->getTraining($training_id);
                $reprovado = false;
                if (isset($nota) && is_numeric($nota) && $nota < 7) {
                    $reprovado = true;
                }

                // Se reprovado, sempre criar novo ciclo (mesmo sem reciclagem)
                if ($reprovado) {
                    $trainingUsersRepo->markAsCompleted($user_id, $training_id, true, true);
                } elseif ($training['reciclagem'] && $training['reciclagem_periodo']) {
                    // Se aprovado e exige reciclagem, criar novo ciclo normalmente
                    $trainingUsersRepo->markAsCompleted($user_id, $training_id, true, false);
                }
            }

            error_log("=== SUCESSO - Salvando mensagem e redirecionando ===");
            error_log("Mensagem: $msg");
            
            $_SESSION['msg'] = $msg;
            $_SESSION['msg_type'] = "success";
            
            error_log("Redirecionando para: " . $_ENV['URL_ADM'] . "list-training-status");
            header("Location: " . $_ENV['URL_ADM'] . "list-training-status");
            
        } catch (\Exception $e) {
            error_log("=== ERRO CAPTURADO ===");
            error_log("Mensagem de erro: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            error_log("Arquivo: " . $e->getFile() . " - Linha: " . $e->getLine());
            
            $_SESSION['msg'] = "Erro ao salvar aplicação: " . $e->getMessage();
            $_SESSION['msg_type'] = "danger";
            saveFormSession();
            
            error_log("Redirecionando para: $redirectUrl");
            header("Location: " . $redirectUrl);
        }
        exit;
    }
} 