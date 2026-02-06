<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdConsentimentosRepository;
use App\adms\Models\Repository\LgpdTermosRepository;
use App\adms\Models\Repository\LgpdConsentimentoArquivosRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller responsável pela criação de Consentimentos LGPD.
 *
 * @package App\adms\Controllers\lgpd
 */
class LgpdConsentimentosCreate
{
    /** @var array $data Recebe os dados que devem ser enviados para a VIEW */
    private array $data = [];

    /** @var LgpdConsentimentosRepository $consentimentosRepo */
    private LgpdConsentimentosRepository $consentimentosRepo;

    /** @var LgpdConsentimentoArquivosRepository */
    private LgpdConsentimentoArquivosRepository $arquivosRepo;

    public function __construct()
    {
        $this->consentimentosRepo = new LgpdConsentimentosRepository();
        $this->arquivosRepo = new LgpdConsentimentoArquivosRepository();
    }

    /**
     * Método para criar novo consentimento.
     *
     * @return void
     */
    public function index(): void
    {
        $this->data['form'] = [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->data['form'] = $_POST;
            
            // Criar consentimento e obter o ID diretamente
            $consentId = $this->consentimentosRepo->create($this->data['form']);
            
            if ($consentId !== false && $consentId > 0) {
                // Processar anexos, se houver
                if (!empty($_FILES['anexos']) && is_array($_FILES['anexos']['name'])) {
                    $this->salvarAnexos($consentId, $_FILES['anexos']);
                }

                $_SESSION['success'] = "Consentimento cadastrado com sucesso!";
                header("Location: " . $_ENV['URL_ADM'] . "lgpd-consentimentos");
                exit;
            } else {
                $this->data['errors'][] = "Consentimento não cadastrado!";
            }
        }

        // Carregar termos ativos para relacionamento opcional
        $termosRepo = new LgpdTermosRepository();
        $this->data['termos'] = $termosRepo->getAll(1, 100); // simples, pode ser filtrado depois

        // Configurar elementos da página
        $pageElements = [
            'title_head' => 'Cadastrar Consentimento',
            'menu' => 'lgpd-consentimentos',
            'buttonPermission' => ['CreateLgpdConsentimentos'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/lgpd/consentimentos/create", $this->data);
        $loadView->loadView();
    }

    /**
     * Salvar arquivos enviados para um consentimento
     */
    private function salvarAnexos(int $consentId, array $files): void
    {
        $baseRelPath = 'storage/lgpd/consentimentos/' . $consentId;
        // dirname(__DIR__, 4) = raiz do projeto (de app/adms/Controllers/lgpd para raiz)
        $baseDir = dirname(__DIR__, 4) . '/' . $baseRelPath;

        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0775, true);
        }

        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];

        $names = $files['name'];
        $tmpNames = $files['tmp_name'];
        $errors = $files['error'];
        $sizes = $files['size'];
        $types = $files['type'];

        foreach ($names as $idx => $originalName) {
            if ($errors[$idx] !== UPLOAD_ERR_OK || empty($originalName)) {
                continue;
            }

            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExtensions, true)) {
                continue;
            }

            $safeName = uniqid('cons_', true) . '.' . $ext;
            $destPath = $baseDir . '/' . $safeName;

            if (!move_uploaded_file($tmpNames[$idx], $destPath)) {
                continue;
            }

            $this->arquivosRepo->create([
                'consentimento_id' => $consentId,
                'nome_original' => $originalName,
                'arquivo_path' => $baseRelPath . '/' . $safeName,
                'mime_type' => $types[$idx] ?? null,
                'tamanho_bytes' => $sizes[$idx] ?? null,
            ]);
        }
    }
}
