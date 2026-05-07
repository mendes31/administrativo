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
        if (isset($this->data['training']['is_current_version']) && (int)$this->data['training']['is_current_version'] !== 1) {
            $_SESSION['msg'] = "Somente a versão atual permite aplicação.";
            $_SESSION['msg_type'] = "warning";
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
        // LOG DE DEBUG AGRESSIVO - Capturar TUDO
        $logDebug = "=== APPLY TRAINING CHAMADO ===\n";
        $logDebug .= "Data/Hora: " . date('Y-m-d H:i:s') . "\n";
        $logDebug .= "REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD'] . "\n";
        $logDebug .= "POST count: " . count($_POST) . "\n";
        $logDebug .= "POST recebido: " . print_r($_POST, true) . "\n";
        $logDebug .= "SESSION user_id: " . ($_SESSION['user_id'] ?? 'NÃO DEFINIDO') . "\n";
        $logDebug .= "Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'NÃO DEFINIDO') . "\n";
        $logDebug .= "Content-Length: " . ($_SERVER['CONTENT_LENGTH'] ?? 'NÃO DEFINIDO') . "\n";
        $logDebug .= "URL_ADM: " . ($_ENV['URL_ADM'] ?? 'NÃO DEFINIDO') . "\n";
        
        // Escrever no arquivo de log E no error_log
        file_put_contents(__DIR__ . '/../../logs/apply_training_debug.log', $logDebug . "\n", FILE_APPEND);
        error_log($logDebug);
        
        // VERIFICAÇÃO CRÍTICA: Se POST está vazio
        if (empty($_POST)) {
            $errorMsg = "⚠️ ALERTA: POST VAZIO - Possível problema de configuração do servidor!";
            file_put_contents(__DIR__ . '/../../logs/apply_training_debug.log', $errorMsg . "\n", FILE_APPEND);
            error_log($errorMsg);
            
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
        
        // NORMALIZAR NOTA: Converter vírgula para ponto (padrão brasileiro → americano)
        $nota = $_POST['nota'] ?? null;
        if ($nota !== null && $nota !== '') {
            // Substituir vírgula por ponto
            $nota = str_replace(',', '.', $nota);
            // Remover espaços
            $nota = trim($nota);
            // Converter para float e depois string para manter precisão
            if (is_numeric($nota)) {
                $nota = (string) (float) $nota;
            }
        }
        error_log("NOTA NORMALIZADA: [" . ($_POST['nota'] ?? 'vazio') . "] → [$nota]");
        
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
        error_log("VALIDAÇÃO 1: Verificando training_id e user_id");
        if (!$training_id || !$user_id) {
            error_log("❌ FALHOU: training_id=$training_id, user_id=$user_id");
            $_SESSION['msg'] = "Dados obrigatórios não informados.";
            $_SESSION['msg_type'] = "danger";
            saveFormSession();
            header("Location: " . $redirectUrl);
            exit;
        }
        $trainingRef = $trainingsRepo->getTraining($training_id);
        if (!$trainingRef || (isset($trainingRef['is_current_version']) && (int)$trainingRef['is_current_version'] !== 1)) {
            $_SESSION['msg'] = "Somente a versão atual permite aplicação.";
            $_SESSION['msg_type'] = "warning";
            header("Location: " . $_ENV['URL_ADM'] . "list-training-status");
            exit;
        }
        error_log("✓ PASSOU: training_id=$training_id, user_id=$user_id");
        
        // Validação obrigatória da nota
        error_log("VALIDAÇÃO 2: Verificando nota (valor=$nota, is_numeric=" . (is_numeric($nota) ? 'true' : 'false') . ")");
        if ($nota === null || $nota === '' || !is_numeric($nota) || $nota < 0 || $nota > 10) {
            $detalhes = "nota_null=" . ($nota === null ? 'SIM' : 'NÃO');
            $detalhes .= ", nota_empty=" . ($nota === '' ? 'SIM' : 'NÃO');
            $detalhes .= ", is_numeric=" . (is_numeric($nota) ? 'SIM' : 'NÃO');
            $detalhes .= ", valor=" . var_export($nota, true);
            error_log("❌ FALHOU: nota inválida ou vazia - $detalhes");
            
            $_SESSION['msg'] = "O campo Nota é obrigatório e deve estar entre 0 e 10.";
            $_SESSION['msg_type'] = "danger";
            saveFormSession();
            header("Location: " . $redirectUrl);
            exit;
        }
        error_log("✓ PASSOU: nota=$nota (válida)");
        
        // Validação obrigatória da data de avaliação
        error_log("VALIDAÇÃO 3: Verificando data_avaliacao (valor=$data_avaliacao)");
        if (empty($data_avaliacao)) {
            error_log("❌ FALHOU: data_avaliacao vazia");
            $_SESSION['msg'] = "A Data de Avaliação é obrigatória.";
            $_SESSION['msg_type'] = "danger";
            saveFormSession();
            header("Location: " . $redirectUrl);
            exit;
        }
        error_log("✓ PASSOU: data_avaliacao=$data_avaliacao");

        // Validação de data de avaliação (entre realização e hoje)
        error_log("VALIDAÇÃO 4: Verificando limites da data_avaliacao");
        if ($data_avaliacao) {
            if ($data_avaliacao > date('Y-m-d')) {
                error_log("❌ FALHOU: data_avaliacao ($data_avaliacao) é futura");
                $_SESSION['msg'] = "Data de avaliação deve ser até hoje.";
                $_SESSION['msg_type'] = "danger";
                saveFormSession();
                header("Location: " . $redirectUrl);
                exit;
            }
            if ($data_realizacao && $data_avaliacao < $data_realizacao) {
                error_log("❌ FALHOU: data_avaliacao ($data_avaliacao) < data_realizacao ($data_realizacao)");
                $_SESSION['msg'] = "Data de avaliação deve ser igual ou posterior à data de realização.";
                $_SESSION['msg_type'] = "danger";
                saveFormSession();
                header("Location: " . $redirectUrl);
                exit;
            }
        }
        error_log("✓ PASSOU: data_avaliacao válida");

        // Deve ter pelo menos uma data
        error_log("VALIDAÇÃO 5: Verificando se tem data_realizacao OU data_agendada");
        if (!$data_realizacao && !$data_agendada) {
            error_log("❌ FALHOU: nenhuma data informada");
            $_SESSION['msg'] = "Informe a data de realização ou agendamento.";
            $_SESSION['msg_type'] = "danger";
            saveFormSession();
            header("Location: " . $redirectUrl);
            exit;
        }
        error_log("✓ PASSOU: data_realizacao=$data_realizacao, data_agendada=$data_agendada");

        // Validações de data
        error_log("VALIDAÇÃO 8: Verificando data_realizacao");
        if ($data_realizacao) {
            // Data de realização não pode ser superior à data atual
            error_log("VALIDAÇÃO 8A: data_realizacao vs hoje");
            if ($data_realizacao > date('Y-m-d')) {
                error_log("❌ FALHOU: data_realizacao ($data_realizacao) é futura");
                $_SESSION['msg'] = "Data de realização não pode ser superior à data atual.";
                $_SESSION['msg_type'] = "danger";
                saveFormSession();
                header("Location: " . $redirectUrl);
                exit;
            }
            error_log("✓ PASSOU: data_realizacao não é futura");
            
            // VALIDAÇÃO FLEXÍVEL: Data de realização vs data de criação do vínculo
            error_log("VALIDAÇÃO 8B: Verificando data_realizacao vs created_at do vínculo (FLEXÍVEL)");
            $trainingUsersRepo = new TrainingUsersRepository();
            $trainingUser = $trainingUsersRepo->getByUserAndTraining($user_id, $training_id);
            
            if ($trainingUser && !empty($trainingUser['created_at'])) {
                $dataCriacaoVinculo = date('Y-m-d', strtotime($trainingUser['created_at']));
                error_log("Data criação vínculo: $dataCriacaoVinculo");
                error_log("Data realização: $data_realizacao");
                
                if ($data_realizacao < $dataCriacaoVinculo) {
                    // PERMITIR data retroativa, mas registrar em log
                    error_log("⚠️ AVISO: Lançamento RETROATIVO detectado!");
                    error_log("⚠️ Data de realização ($data_realizacao) é ANTERIOR à criação do vínculo ($dataCriacaoVinculo)");
                    error_log("⚠️ Usuário: $user_id, Treinamento: $training_id, Aplicado por: " . ($_SESSION['user_id'] ?? 'N/A'));
                    error_log("⚠️ PERMITINDO salvamento mesmo assim (FLEXIBILIZADO)");
                    
                    // Salvar aviso em log específico para auditoria
                    $avisoRetroativo = date('Y-m-d H:i:s') . " | LANÇAMENTO RETROATIVO | ";
                    $avisoRetroativo .= "User: $user_id | Training: $training_id | ";
                    $avisoRetroativo .= "Data Realização: $data_realizacao | Vínculo criado em: $dataCriacaoVinculo | ";
                    $avisoRetroativo .= "Aplicado por: " . ($_SESSION['user_id'] ?? 'N/A') . " (" . ($_SESSION['user_name'] ?? 'N/A') . ")\n";
                    file_put_contents(__DIR__ . '/../../logs/lancamentos_retroativos.log', $avisoRetroativo, FILE_APPEND);
                    
                    // Mostrar aviso na tela (não bloqueia, apenas informa)
                    $_SESSION['msg_warning'] = "⚠️ Atenção: Data de realização (" . date('d/m/Y', strtotime($data_realizacao)) . 
                                               ") é anterior à criação do vínculo (" . date('d/m/Y', strtotime($dataCriacaoVinculo)) . 
                                               "). Lançamento retroativo registrado.";
                } else {
                    error_log("✓ OK: data_realizacao >= dataCriacaoVinculo");
                }
            } else {
                error_log("ℹ Vínculo não encontrado ou sem created_at - permitindo qualquer data");
            }
            error_log("✓ PASSOU: Validação 8B - Data retroativa FLEXIBILIZADA");
        }
        error_log("✓ PASSOU: Todas validações de data_realizacao");

        if ($data_agendada && $data_agendada < date('Y-m-d')) {
            $_SESSION['msg'] = "Data de agendamento não pode ser retroativa.";
            $_SESSION['msg_type'] = "danger";
            saveFormSession();
            header("Location: " . $redirectUrl);
            exit;
        }

        // Validação obrigatória do tipo de instrutor
        error_log("VALIDAÇÃO 6: Verificando instructor_type (valor=$instructor_type)");
        if (empty($instructor_type)) {
            error_log("❌ FALHOU: instructor_type vazio");
            $_SESSION['msg'] = "Selecione o tipo de instrutor.";
            $_SESSION['msg_type'] = "danger";
            saveFormSession();
            header("Location: " . $redirectUrl);
            exit;
        }
        error_log("✓ PASSOU: instructor_type=$instructor_type");
        
        // Validação obrigatória do instrutor conforme o tipo
        error_log("VALIDAÇÃO 7: Verificando dados do instrutor");
        if ($instructor_type === 'internal') {
            if (empty($instructor_user_id)) {
                error_log("❌ FALHOU: instructor_user_id vazio (interno)");
                $_SESSION['msg'] = "Selecione o instrutor interno.";
                $_SESSION['msg_type'] = "danger";
                saveFormSession();
                header("Location: " . $redirectUrl);
                exit;
            }
            error_log("✓ PASSOU: instructor_user_id=$instructor_user_id (interno)");
        } elseif ($instructor_type === 'external') {
            if (empty($instrutor_nome) || empty($instrutor_email)) {
                error_log("❌ FALHOU: instrutor_nome ou email vazio (externo)");
                $_SESSION['msg'] = "Informe o nome e e-mail do instrutor externo.";
                $_SESSION['msg_type'] = "danger";
                saveFormSession();
                header("Location: " . $redirectUrl);
                exit;
            }
            error_log("✓ PASSOU: instrutor_nome=$instrutor_nome, email=$instrutor_email (externo)");
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
        
        error_log("✅✅✅ TODAS AS VALIDAÇÕES PASSARAM - INICIANDO SALVAMENTO ✅✅✅");
        error_log("Dados validados: user=$user_id, training=$training_id, nota=$nota, data_aval=$data_avaliacao");
        
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
            
            // Verificar se há mensagem de warning (lançamento retroativo)
            if (isset($_SESSION['msg_warning'])) {
                error_log("⚠️ MENSAGEM DE AVISO ATIVA: " . $_SESSION['msg_warning']);
            } else {
                error_log("ℹ Nenhuma mensagem de aviso");
            }
            
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