<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdConsentimentosRepository;
use App\adms\Models\Repository\LgpdConsentimentoArquivosRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller responsável pela visualização de Consentimentos LGPD.
 *
 * @package App\adms\Controllers\lgpd
 */
class LgpdConsentimentosView
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
     * Método para visualizar consentimento.
     *
     * @param int $id ID do consentimento
     * @return void
     */
    public function index(int $id): void
    {
        $this->data['consentimento'] = $this->consentimentosRepo->getConsentimentoById($id);
        
        if (!$this->data['consentimento']) {
            $_SESSION['error'] = "Consentimento não encontrado!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-consentimentos");
            exit;
        }

        // Carregar anexos vinculados
        $this->data['anexos'] = $this->arquivosRepo->getByConsentimentoId($id);

        // Carregar informações do termo vinculado (se houver)
        if (!empty($this->data['consentimento']['lgpd_termo_id'])) {
            $termosRepo = new \App\adms\Models\Repository\LgpdTermosRepository();
            $termo = $termosRepo->getById((int)$this->data['consentimento']['lgpd_termo_id']);
            $this->data['termo_vinculado'] = $termo;
        } else {
            $this->data['termo_vinculado'] = null;
        }

        $returnUrl = $_ENV['URL_ADM'] . 'lgpd-consentimentos-view/' . $id;
        $this->data['log_resumo'] = LogResumoService::getResumo('lgpd_consentimentos', (int) $id, $returnUrl);

        // Carregar informações do usuário vinculado (se houver)
        if (!empty($this->data['consentimento']['adms_user_id'])) {
            $usersRepo = new \App\adms\Models\Repository\UsersRepository();
            $user = $usersRepo->getUser((int)$this->data['consentimento']['adms_user_id']);
            $this->data['usuario_vinculado'] = is_array($user) ? $user : null;
        } else {
            $this->data['usuario_vinculado'] = null;
        }

        // Configurar elementos da página
        $pageElements = [
            'title_head' => 'Visualizar Consentimento',
            'menu' => 'lgpd-consentimentos',
            'buttonPermission' => ['ViewLgpdConsentimentos', 'EditLgpdConsentimentos', 'DeleteLgpdConsentimentos'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a VIEW
        $loadView = new LoadViewService("adms/Views/lgpd/consentimentos/view", $this->data);
        $loadView->loadView();
    }
}
