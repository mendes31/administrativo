<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdConsentimentosRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller responsável pela edição de Consentimentos LGPD.
 *
 * @package App\adms\Controllers\lgpd
 */
class LgpdConsentimentosEdit
{
    /** @var array $data Recebe os dados que devem ser enviados para a VIEW */
    private array $data = [];

    /** @var LgpdConsentimentosRepository $consentimentosRepo */
    private LgpdConsentimentosRepository $consentimentosRepo;

    public function __construct()
    {
        $this->consentimentosRepo = new LgpdConsentimentosRepository();
    }

    /**
     * Método para editar consentimento.
     *
     * @param int $id ID do consentimento
     * @return void
     */
    public function index(int $id): void
    {
        error_log("LGPD Edit: Iniciando edição do consentimento ID: {$id}");
        try {
            $this->data['consentimento'] = $this->consentimentosRepo->getConsentimentoById($id);
            error_log("LGPD Edit: Consentimento carregado: " . ($this->data['consentimento'] ? 'SIM' : 'NÃO'));
        } catch (\Exception $e) {
            error_log("Erro ao buscar consentimento ID {$id}: " . $e->getMessage());
            $_SESSION['error'] = "Erro ao carregar consentimento: " . $e->getMessage();
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-consentimentos");
            exit;
        }
        
        if (!$this->data['consentimento'] || !is_array($this->data['consentimento'])) {
            $_SESSION['error'] = "Consentimento não encontrado!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-consentimentos");
            exit;
        }
        
        // Garantir que o ID existe
        if (empty($this->data['consentimento']['id'])) {
            $_SESSION['error'] = "ID do consentimento inválido!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-consentimentos");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            error_log("LGPD Edit: Recebido POST para consentimento ID: {$id}");
            error_log("LGPD Edit: POST data: " . json_encode($_POST));
            error_log("LGPD Edit: FILES data: " . json_encode(array_keys($_FILES)));
            $this->data['form'] = $_POST;
            
            // Validar campos obrigatórios
            if (empty($this->data['form']['titular_nome']) || 
                empty($this->data['form']['finalidade']) || 
                empty($this->data['form']['data_consentimento']) || 
                empty($this->data['form']['status'])) {
                $this->data['errors'][] = "Por favor, preencha todos os campos obrigatórios.";
            } else {
                // Processar remoção de anexos (se houver) - ANTES de atualizar
                if (!empty($_POST['remover_anexos']) && is_array($_POST['remover_anexos'])) {
                    $arquivosRepo = new \App\adms\Models\Repository\LgpdConsentimentoArquivosRepository();
                    foreach ($_POST['remover_anexos'] as $anexoId) {
                        $arquivosRepo->delete((int)$anexoId);
                    }
                }
                
                // Atualizar consentimento
                try {
                    $result = $this->consentimentosRepo->update($id, $this->data['form']);
                } catch (\Exception $e) {
                    error_log("Erro ao atualizar consentimento ID {$id}: " . $e->getMessage());
                    $this->data['errors'][] = "Erro ao atualizar consentimento: " . $e->getMessage();
                    $result = false;
                }
                
                // Processar novos anexos (se houver) - Processar independente do resultado do update
                // Verificar se há arquivos enviados
                $temArquivos = false;
                if (isset($_FILES['anexos']) && is_array($_FILES['anexos'])) {
                    // Verificar se pelo menos um arquivo foi enviado (não está vazio)
                    if (isset($_FILES['anexos']['name']) && is_array($_FILES['anexos']['name'])) {
                        foreach ($_FILES['anexos']['name'] as $nome) {
                            if (!empty($nome) && $nome !== '') {
                                $temArquivos = true;
                                break;
                            }
                        }
                    }
                    
                    if ($temArquivos) {
                        $anexosSalvos = $this->salvarAnexos($id, $_FILES['anexos']);
                        if ($anexosSalvos > 0) {
                            $_SESSION['success'] = ($_SESSION['success'] ?? '') . ($_SESSION['success'] ? ' ' : '') . "{$anexosSalvos} anexo(s) adicionado(s) com sucesso!";
                        }
                    }
                }
                
                if ($result) {
                    if (empty($_SESSION['success'])) {
                        $_SESSION['success'] = "Consentimento atualizado com sucesso!";
                    }
                    header("Location: " . $_ENV['URL_ADM'] . "lgpd-consentimentos");
                    exit;
                } else {
                    $this->data['errors'][] = "Consentimento não atualizado!";
                }
            }
        } else {
            $this->data['form'] = $this->data['consentimento'];
            // Garantir que a data está no formato correto para o input date
            if (!empty($this->data['form']['data_consentimento'])) {
                try {
                    $dataObj = new \DateTime($this->data['form']['data_consentimento']);
                    $this->data['form']['data_consentimento'] = $dataObj->format('Y-m-d');
                } catch (\Exception $e) {
                    // Manter o valor original se não conseguir converter
                    error_log("Erro ao converter data do consentimento: " . $e->getMessage());
                }
            }
        }

        // Carregar termos ativos para o select
        try {
            $termosRepo = new \App\adms\Models\Repository\LgpdTermosRepository();
            $this->data['termos_ativos'] = $termosRepo->getAllActiveTerms();
        } catch (\Exception $e) {
            error_log("Erro ao carregar termos ativos: " . $e->getMessage());
            $this->data['termos_ativos'] = [];
        }

        // Carregar anexos do consentimento
        try {
            $arquivosRepo = new \App\adms\Models\Repository\LgpdConsentimentoArquivosRepository();
            $this->data['anexos'] = $arquivosRepo->getByConsentimentoId($id);
        } catch (\Exception $e) {
            error_log("Erro ao carregar anexos do consentimento ID {$id}: " . $e->getMessage());
            $this->data['anexos'] = [];
        }

        // Carregar informações do usuário vinculado (se houver)
        // IMPORTANTE: Se houver erro ao carregar usuário, não deve impedir a edição
        $this->data['usuario_vinculado'] = null;
        if (!empty($this->data['consentimento']['adms_user_id'])) {
            $userId = (int)($this->data['consentimento']['adms_user_id'] ?? 0);
            if ($userId > 0) {
                try {
                    $usersRepo = new \App\adms\Models\Repository\UsersRepository();
                    $user = $usersRepo->getUser($userId);
                    // getUser retorna array|bool, então verificamos se é array e não vazio
                    if (is_array($user) && !empty($user) && isset($user['id'])) {
                        $this->data['usuario_vinculado'] = $user;
                    }
                } catch (\PDOException $e) {
                    // Erro de banco de dados (ex: JOIN falhou)
                    error_log("Erro PDO ao carregar usuário vinculado (ID: {$userId}): " . $e->getMessage());
                    $this->data['usuario_vinculado'] = null;
                } catch (\Exception $e) {
                    error_log("Erro ao carregar usuário vinculado (ID: {$userId}): " . $e->getMessage());
                    $this->data['usuario_vinculado'] = null;
                } catch (\Throwable $e) {
                    error_log("Erro fatal ao carregar usuário vinculado (ID: {$userId}): " . $e->getMessage());
                    $this->data['usuario_vinculado'] = null;
                }
            }
        }

        // Configurar elementos da página
        try {
            $pageElements = [
                'title_head' => 'Editar Consentimento',
                'menu' => 'lgpd-consentimentos',
                'buttonPermission' => ['EditLgpdConsentimentos'],
            ];
            
            $pageLayoutService = new PageLayoutService();
            $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

            // Carregar a VIEW
            $loadView = new LoadViewService("adms/Views/lgpd/consentimentos/edit", $this->data);
            $loadView->loadView();
        } catch (\Exception $e) {
            error_log("Erro ao carregar view de edição: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $_SESSION['error'] = "Erro ao carregar página de edição. Por favor, tente novamente.";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-consentimentos");
            exit;
        } catch (\Throwable $e) {
            error_log("Erro fatal ao carregar view de edição: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $_SESSION['error'] = "Erro fatal ao carregar página de edição. Por favor, tente novamente.";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-consentimentos");
            exit;
        }
    }

    /**
     * Salvar arquivos enviados para um consentimento
     * 
     * @param int $consentId ID do consentimento
     * @param array $files Array $_FILES['anexos']
     * @return int Número de arquivos salvos com sucesso
     */
    private function salvarAnexos(int $consentId, array $files): int
    {
        $baseRelPath = 'storage/lgpd/consentimentos/' . $consentId;
        // dirname(__DIR__, 4) = raiz do projeto (de app/adms/Controllers/lgpd para raiz)
        $baseDir = dirname(__DIR__, 4) . '/' . $baseRelPath;

        if (!is_dir($baseDir)) {
            if (!mkdir($baseDir, 0775, true)) {
                error_log("LGPD Edit: Erro ao criar diretório: {$baseDir}");
                return 0;
            }
        }

        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];

        $names = $files['name'] ?? [];
        $tmpNames = $files['tmp_name'] ?? [];
        $errors = $files['error'] ?? [];
        $sizes = $files['size'] ?? [];
        $types = $files['type'] ?? [];

        if (empty($names) || !is_array($names)) {
            error_log("LGPD Edit: Nenhum arquivo enviado ou formato inválido");
            return 0;
        }

        $arquivosRepo = new \App\adms\Models\Repository\LgpdConsentimentoArquivosRepository();
        $arquivosSalvos = 0;

        foreach ($names as $idx => $originalName) {
            // Verificar se o arquivo foi enviado corretamente
            if (!isset($errors[$idx]) || $errors[$idx] !== UPLOAD_ERR_OK) {
                if (isset($errors[$idx]) && $errors[$idx] !== UPLOAD_ERR_NO_FILE) {
                    error_log("LGPD Edit: Erro no upload do arquivo {$originalName}: código {$errors[$idx]}");
                }
                continue;
            }

            if (empty($originalName) || empty($tmpNames[$idx]) || !is_uploaded_file($tmpNames[$idx])) {
                continue;
            }

            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExtensions, true)) {
                error_log("LGPD Edit: Extensão não permitida: {$ext} (arquivo: {$originalName})");
                continue;
            }

            $safeName = uniqid('cons_', true) . '.' . $ext;
            $destPath = $baseDir . '/' . $safeName;

            if (!move_uploaded_file($tmpNames[$idx], $destPath)) {
                error_log("LGPD Edit: Erro ao mover arquivo de {$tmpNames[$idx]} para {$destPath}");
                continue;
            }

            try {
                $result = $arquivosRepo->create([
                    'consentimento_id' => $consentId,
                    'nome_original' => $originalName,
                    'arquivo_path' => $baseRelPath . '/' . $safeName,
                    'mime_type' => $types[$idx] ?? null,
                    'tamanho_bytes' => $sizes[$idx] ?? null,
                ]);

                if ($result) {
                    $arquivosSalvos++;
                } else {
                    error_log("LGPD Edit: Erro ao salvar registro do arquivo {$originalName} no banco (retornou false)");
                    // Remover arquivo físico se não salvou no banco
                    if (file_exists($destPath)) {
                        @unlink($destPath);
                    }
                }
            } catch (\Exception $e) {
                error_log("LGPD Edit: Exceção ao salvar registro do arquivo {$originalName}: " . $e->getMessage());
                error_log("LGPD Edit: Stack trace: " . $e->getTraceAsString());
                // Remover arquivo físico se não salvou no banco
                if (file_exists($destPath)) {
                    @unlink($destPath);
                }
            } catch (\Throwable $e) {
                error_log("LGPD Edit: Erro fatal ao salvar registro do arquivo {$originalName}: " . $e->getMessage());
                error_log("LGPD Edit: Stack trace: " . $e->getTraceAsString());
                // Remover arquivo físico se não salvou no banco
                if (file_exists($destPath)) {
                    @unlink($destPath);
                }
            }
        }

        if ($arquivosSalvos > 0) {
            error_log("LGPD Edit: {$arquivosSalvos} arquivo(s) salvo(s) com sucesso para consentimento {$consentId}");
        }

        return $arquivosSalvos;
    }
}
