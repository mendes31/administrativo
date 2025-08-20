<?php

namespace App\adms\Controllers\Services;

use App\adms\Models\Repository\LoginRepository;
use App\adms\Models\Repository\AdmsPasswordPolicyRepository;
use App\adms\Helpers\SendEmailService;
use Exception;

/**
 * Serviço de Segurança
 * 
 * Gerencia todas as regras de segurança do sistema:
 * - Bloqueio temporário e permanente
 * - Notificações por e-mail
 * - Forçar logout ao trocar senha
 * - Validação de política de senha
 * - Logs de segurança
 */
class SecurityService
{
    private LoginRepository $loginRepo;
    private AdmsPasswordPolicyRepository $policyRepo;

    public function __construct()
    {
        $this->loginRepo = new LoginRepository();
        $this->policyRepo = new AdmsPasswordPolicyRepository();
    }

    /**
     * Valida login com todas as regras de segurança
     */
    public function validateLogin(array $data): array
    {
        $result = [
            'success' => false,
            'user' => null,
            'message' => '',
            'action' => 'none'
        ];

        try {
            // Buscar usuário
            $user = $this->loginRepo->getUser((string) $data['username']);
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

            if (!$user) {
                $this->registrarTentativaInvalida(null, $data['username'], $ip, $userAgent, 'USER_NOT_FOUND');
                $result['message'] = "Usuário ou senha incorreta.";
                return $result;
            }

            // NOVA VERIFICAÇÃO: Usuário inativo e/ou bloqueado
            $motivos = [];
            if (isset($user['status']) && $user['status'] === 'Inativo') {
                $motivos[] = 'Usuário Inativo';
            }
            if (isset($user['bloqueado']) && $user['bloqueado'] === 'Sim') {
                $motivos[] = 'Usuário Bloqueado';
            }
            if (!empty($motivos)) {
                $msg = implode(' e ', $motivos) . '! Contate o Administrador do sistema.';
                $this->registrarTentativaInvalida($user['id'], $data['username'], $ip, $userAgent, 'INACTIVE_OR_BLOCKED_USER');
                $result['message'] = $msg;
                return $result;
            }

            // Verificar bloqueio temporário
            if ($this->isUsuarioBloqueadoTemporariamente(($user))) {
                $this->registrarTentativaInvalida($user['id'], $data['username'], $ip, $userAgent, 'TEMPORARY_BLOCKED');
                $result['message'] = "Usuário bloqueado temporariamente. Tente novamente mais tarde.";
                return $result;
            }

            // Validar senha
            if (!password_verify($data['password'], $user['password'])) {
                $this->processarSenhaIncorreta($user, $data['username'], $ip, $userAgent);
                $result['message'] = "Usuário ou senha incorreta.";
                return $result;
            }

            // Verificar se senha expirou
            if ($this->isSenhaExpirada($user)) {
                $this->registrarTentativaInvalida($user['id'], $data['username'], $ip, $userAgent, 'EXPIRED_PASSWORD');
                $result['message'] = "Sua senha expirou. É necessário alterá-la.";
                $result['action'] = 'force_password_change';
                return $result;
            }

            // Login bem-sucedido
            $this->processarLoginSucesso($user, $data['username'], $ip, $userAgent);
            
            $result['success'] = true;
            $result['user'] = $user;
            $result['message'] = "Login realizado com sucesso!";
            
            return $result;

        } catch (Exception $e) {
            error_log("Erro no SecurityService::validateLogin: " . $e->getMessage());
            $result['message'] = "Erro interno do sistema. Tente novamente.";
            return $result;
        }
    }

    /**
     * Verifica se usuário está bloqueado
     */
    public function isUsuarioBloqueado(array $user): bool
    {
        if (!isset($user['bloqueado']) || $user['bloqueado'] === 'Não') {
            return false;
        }

        // Se está bloqueado, verificar se é temporário ou definitivo
        if ($user['bloqueado'] === 'Sim') {
            // Se tem data de bloqueio temporário, verificar se ainda é válido
            if (isset($user['data_bloqueio_temporario']) && $user['data_bloqueio_temporario']) {
                return $this->isBloqueioTemporarioValido($user);
            }
            // Se não tem data de bloqueio temporário, é bloqueio definitivo
            return true;
        }

        return false;
    }

    /**
     * Verifica se o bloqueio temporário ainda é válido (ajustado para usar timestamps - revisão 2024-07-04)
     */
    private function isBloqueioTemporarioValido(array $user): bool
    {
        if (!isset($user['data_bloqueio_temporario'])) {
            return false;
        }

        $policy = $this->policyRepo->getPolicy();
        $tempoBloqueio = $policy ? (int)$policy->tempo_bloqueio_temporario : 30; // padrão 30 minutos

        $dataBloqueio = new \DateTime($user['data_bloqueio_temporario']);
        $dataAtual = new \DateTime();
        $minutosPassados = ($dataAtual->getTimestamp() - $dataBloqueio->getTimestamp()) / 60;
        $minutosRestantes = max(0, $tempoBloqueio - $minutosPassados);

        // Se ainda está no período de bloqueio
        if ($minutosPassados < $tempoBloqueio) {
            $_SESSION['error'] = "Usuário Bloqueado temporáriamente! Tente novamente após " . ceil($minutosRestantes) . " minuto(s).";
            return true;
        }

        // Se passou o tempo, desbloquear automaticamente
        $this->desbloquearTemporariamente($user['id']);
        return false;
    }

    /**
     * Verifica se usuário está bloqueado temporariamente
     */
    private function isUsuarioBloqueadoTemporariamente(array $user): bool
    {
        return $this->isUsuarioBloqueado($user) && 
               isset($user['data_bloqueio_temporario']) && 
               $user['data_bloqueio_temporario'];
    }

    /**
     * Verifica se a senha expirou
     */
    private function isSenhaExpirada(array $user): bool
    {
        $policy = $this->policyRepo->getPolicy();
        if (!$policy || !$policy->vencimento_dias) {
            return false; // Sem política de vencimento
        }

        if (!isset($user['data_ultima_troca_senha'])) {
            return true; // Nunca trocou a senha
        }

        $dataUltimaTroca = new \DateTime($user['data_ultima_troca_senha']);
        $dataAtual = new \DateTime();
        $diferenca = $dataAtual->diff($dataUltimaTroca);
        
        return $diferenca->days >= $policy->vencimento_dias;
    }

    /**
     * Processa senha incorreta
     */
    public function processarSenhaIncorreta(array $user, string $username, string $ip, string $userAgent): void
    {
        // Buscar política de senha
        $policy = $this->policyRepo->getPolicy();
        
        if (!$policy) {
            // Se não há política, apenas registrar tentativa
            $this->registrarTentativaInvalida($user['id'], $username, $ip, $userAgent, 'WRONG_PASSWORD');
            return;
        }

        $limiteTentativasTemporario = ($policy->bloqueio_temporario === 'Sim') ? ($policy->tentativas_bloqueio_temporario ?? 3) : null;
        $limiteTentativasDefinitivo = $policy->tentativas_bloqueio ?? 5;

        // Usar o novo método que atualiza todos os campos juntos
        $resultado = $this->loginRepo->incrementarTentativasLoginCompleto(
            $user['id'], 
            $limiteTentativasTemporario, 
            $limiteTentativasDefinitivo,
            $policy->bloqueio_temporario === 'Sim'
        );

        if (!$resultado['success']) {
            // Se falhou, usar método antigo como fallback
            $this->loginRepo->incrementarTentativasLogin($user['id']);
            $this->registrarTentativaInvalida($user['id'], $username, $ip, $userAgent, 'WRONG_PASSWORD');
            return;
        }

        // Registrar tentativa inválida
        $this->registrarTentativaInvalida($user['id'], $username, $ip, $userAgent, 'WRONG_PASSWORD');

        // Aplicar bloqueios se necessário
        if ($resultado['bloqueio_definitivo']) {
            $this->aplicarBloqueioDefinitivo($user['id'], $policy);
            // Mensagem de bloqueio definitivo
            $_SESSION['error'] = "Usuário Bloqueado! Contate o Administrador do sistema.";
        } elseif ($policy->bloqueio_temporario === 'Sim' && $resultado['bloqueio_temporario']) {
            $this->aplicarBloqueioTemporario($user['id'], $policy);
            // Mensagem de bloqueio temporário
            $_SESSION['error'] = "Usuário Bloqueado temporáriamente! Tente novamente após {$policy->tempo_bloqueio_temporario} minuto(s).";
        }
    }

    /**
     * Aplica bloqueio temporário
     */
    private function aplicarBloqueioTemporario(int $userId, $policy): void
    {
        // O update já foi feito no incrementarTentativasLoginCompleto
        // Aqui apenas aplicamos as notificações
        
        // Notificações
        if ($policy && $policy->notificar_usuario_bloqueio === 'Sim') {
            $this->notificarUsuarioBloqueio($userId, 'temporário');
        }
        if ($policy && $policy->notificar_admins_bloqueio === 'Sim') {
            $this->notificarAdminsBloqueio($userId, 'temporário');
        }
    }

    /**
     * Aplica bloqueio definitivo
     */
    private function aplicarBloqueioDefinitivo(int $userId, $policy): void
    {
        // O update já foi feito no incrementarTentativasLoginCompleto
        // Aqui apenas aplicamos as ações adicionais
        
        // Invalidar todas as sessões do usuário
        $sessionRepo = new \App\adms\Models\Repository\AdmsSessionsRepository();
        $sessionRepo->invalidateAllSessionsByUserId($userId);
        
        // Notificações
        if ($policy && $policy->notificar_usuario_bloqueio === 'Sim') {
            $this->notificarUsuarioBloqueio($userId, 'definitivo');
        }
        if ($policy && $policy->notificar_admins_bloqueio === 'Sim') {
            $this->notificarAdminsBloqueio($userId, 'definitivo');
        }
    }

    /**
     * Bloqueia usuário temporariamente
     */
    private function bloquearTemporariamente(int $userId): void
    {
        $sql = 'UPDATE adms_users SET 
                bloqueado = "Sim", 
                data_bloqueio_temporario = NOW() 
                WHERE id = :id';
        
        $stmt = $this->loginRepo->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $userId, \PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Desbloqueia usuário temporariamente
     */
    private function desbloquearTemporariamente(int $userId): void
    {
        // Usar o método completo que atualiza todos os campos juntos
        $this->loginRepo->resetarTentativasLoginCompleto($userId);
    }

    /**
     * Processa login bem-sucedido
     */
    private function processarLoginSucesso(array $user, string $username, string $ip, string $userAgent): void
    {
        // Registrar tentativa de sucesso
        $this->loginRepo->registrarTentativaLogin(
            $user['id'],
            $username,
            $ip,
            $userAgent,
            'SUCCESS',
            null
        );

        // Resetar tentativas e desbloquear se necessário usando o método completo
        $this->loginRepo->resetarTentativasLoginCompleto($user['id']);

        // Configurar sessão
        $this->configurarSessao($user);
    }

    /**
     * Configura a sessão do usuário
     */
    private function configurarSessao(array $user): void
    {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_username'] = $user['username'];
        $_SESSION['user_image'] = $user['image'];
        $_SESSION['user_department'] = $user['dep_name'];
        $_SESSION['user_position'] = $user['pos_name'];
        $_SESSION['login_time'] = time();
        $_SESSION['session_id'] = session_id();
    }

    /**
     * Registra tentativa inválida
     */
    private function registrarTentativaInvalida(?int $userId, string $username, string $ip, string $userAgent, string $resultado): void
    {
        $this->loginRepo->registrarTentativaLogin(
            $userId,
            $username,
            $ip,
            $userAgent,
            $resultado,
            $this->getDetalhesResultado($resultado)
        );
    }

    /**
     * Retorna detalhes do resultado
     */
    private function getDetalhesResultado(string $resultado): string
    {
        $detalhes = [
            'USER_NOT_FOUND' => 'Usuário não encontrado',
            'WRONG_PASSWORD' => 'Senha incorreta',
            'BLOCKED_USER' => 'Usuário bloqueado permanentemente',
            'TEMPORARY_BLOCKED' => 'Usuário bloqueado temporariamente',
            'EXPIRED_PASSWORD' => 'Senha expirada',
            'INACTIVE_USER' => 'Usuário inativo'
        ];

        return $detalhes[$resultado] ?? 'Tentativa inválida';
    }

    /**
     * Notifica usuário sobre bloqueio
     */
    private function notificarUsuarioBloqueio(int $userId, string $tipo): void
    {
        $user = $this->loginRepo->getUserById($userId);
        if (!$user || !$user['email']) {
            return;
        }

        $subject = "Conta Bloqueada - " . $_ENV['APP_NAME'];
        $message = $this->getMensagemBloqueioUsuario($user['name'], $tipo);

        SendEmailService::sendEmail($user['email'], $user['name'], $subject, $message, strip_tags($message));
    }

    /**
     * Notifica administradores sobre bloqueio
     */
    private function notificarAdminsBloqueio(int $userId, string $tipo): void
    {
        $user = $this->loginRepo->getUserById($userId);
        if (!$user) {
            return;
        }

        // Buscar e-mails dos administradores
        $admins = $this->getEmailsAdmins();
        
        if (empty($admins)) {
            return;
        }

        $subject = "Alerta de Segurança - Usuário Bloqueado - " . $_ENV['APP_NAME'];
        $message = $this->getMensagemBloqueioAdmin($user, $tipo);

        foreach ($admins as $email) {
            SendEmailService::sendEmail($email, '', $subject, $message, strip_tags($message));
        }
    }

    /**
     * Busca e-mails dos administradores
     */
    private function getEmailsAdmins(): array
    {
        $sql = 'SELECT DISTINCT u.email 
                FROM adms_users u 
                INNER JOIN adms_users_access_levels ual ON u.id = ual.adms_user_id
                INNER JOIN adms_access_levels al ON ual.adms_access_level_id = al.id
                LEFT JOIN adms_access_levels_pages alp ON ual.adms_access_level_id = alp.adms_access_level_id
                LEFT JOIN adms_pages p ON alp.adms_page_id = p.id
                WHERE 
                  (ual.adms_access_level_id = 1 
                   OR p.controller IN ("CreateUser", "UpdateUser"))
                  AND u.email IS NOT NULL 
                  AND u.email != ""';
        $stmt = $this->loginRepo->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Gera mensagem de bloqueio para usuário
     */
    private function getMensagemBloqueioUsuario(string $nome, string $tipo): string
    {
        $tipoTexto = $tipo === 'temporário' ? 'temporariamente' : 'permanentemente';
        
        return "
        <h2>Conta Bloqueada</h2>
        <p>Olá {$nome},</p>
        <p>Sua conta foi bloqueada {$tipoTexto} devido a múltiplas tentativas de login inválidas.</p>
        
        " . ($tipo === 'temporário' ? "
        <p>O bloqueio será removido automaticamente após o período de segurança.</p>
        " : "
        <p>Para desbloquear sua conta, entre em contato com o administrador do sistema.</p>
        ") . "
        
        <p>Atenciosamente,<br>
        Equipe " . $_ENV['APP_NAME'] . "</p>";
    }

    /**
     * Gera mensagem de bloqueio para administradores
     */
    private function getMensagemBloqueioAdmin(array $user, string $tipo): string
    {
        $tipoTexto = $tipo === 'temporário' ? 'temporariamente' : 'permanentemente';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'N/A';
        $data = date('d/m/Y H:i:s');
        
        return "
        <h2>Alerta de Segurança - Usuário Bloqueado</h2>
        <p><strong>Usuário:</strong> {$user['name']} ({$user['username']})</p>
        <p><strong>E-mail:</strong> {$user['email']}</p>
        <p><strong>Departamento:</strong> {$user['dep_name']}</p>
        <p><strong>Cargo:</strong> {$user['pos_name']}</p>
        <p><strong>IP:</strong> {$ip}</p>
        <p><strong>Data/Hora:</strong> {$data}</p>
        <p><strong>Tipo de Bloqueio:</strong> {$tipoTexto}</p>
        
        <p>O usuário foi bloqueado devido a múltiplas tentativas de login inválidas.</p>
        
        " . ($tipo === 'temporário' ? "
        <p>O bloqueio será removido automaticamente após o período configurado na política de senha.</p>
        " : "
        <p>É necessário desbloquear manualmente o usuário no sistema.</p>
        ") . "
        
        <p>Atenciosamente,<br>
        Sistema de Segurança - " . $_ENV['APP_NAME'] . "</p>";
    }

    /**
     * Força logout de todas as sessões do usuário
     */
    public function forcarLogoutUsuario(int $userId): void
    {
        // Buscar usuário atualizado
        $user = $this->loginRepo->getUserById($userId);
        $motivos = [];
        if ($user) {
            if (isset($user['status']) && $user['status'] === 'Inativo') {
                $motivos[] = 'Usuário Inativo';
            }
            if (isset($user['bloqueado']) && $user['bloqueado'] === 'Sim') {
                if (isset($user['data_bloqueio_temporario']) && $user['data_bloqueio_temporario']) {
                    $motivos[] = 'Usuário Bloqueado Temporariamente';
                } else {
                    $motivos[] = 'Usuário Bloqueado!';
                }
            }
        }
        // Só seta a mensagem se for o próprio usuário logado
        if (!empty($motivos) && isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
            $msg = implode(' e ', $motivos) . '! Contate o Administrador do sistema.';
            $_SESSION['error'] = $msg;
        }
        // Sempre invalidar a sessão do usuário-alvo no banco
        $sessionRepo = new \App\adms\Models\Repository\AdmsSessionsRepository();
        $sessionRepo->invalidateSessionByUserId($userId);
        // Só destruir a sessão se for o próprio usuário logado
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
            session_destroy();
            header('Location: ' . $_ENV['URL_ADM'] . 'login');
            exit;
        }
        // Se não for o usuário logado, não manipular $_SESSION nem session_destroy()
    }

    /**
     * Valida política de senha
     */
    public function validarPoliticaSenha(string $senha, int|string|null $userId = null): array
    {
        $policy = $this->policyRepo->getPolicy();
        if (!$policy) {
            return ['valid' => true, 'errors' => []];
        }

        $errors = [];

        // Comprimento mínimo
        if (strlen($senha) < $policy->comprimento_minimo) {
            $errors[] = "A senha deve ter pelo menos {$policy->comprimento_minimo} caracteres.";
        }

        // Maiúsculas
        if ($policy->min_maiusculas > 0) {
            $maiusculas = preg_match_all('/[A-Z]/', $senha);
            if ($maiusculas < $policy->min_maiusculas) {
                $errors[] = "A senha deve conter pelo menos {$policy->min_maiusculas} letra(s) maiúscula(s).";
            }
        }

        // Minúsculas
        if ($policy->min_minusculas > 0) {
            $minusculas = preg_match_all('/[a-z]/', $senha);
            if ($minusculas < $policy->min_minusculas) {
                $errors[] = "A senha deve conter pelo menos {$policy->min_minusculas} letra(s) minúscula(s).";
            }
        }

        // Dígitos
        if ($policy->min_digitos > 0) {
            $digitos = preg_match_all('/[0-9]/', $senha);
            if ($digitos < $policy->min_digitos) {
                $errors[] = "A senha deve conter pelo menos {$policy->min_digitos} dígito(s).";
            }
        }

        // Caracteres especiais
        if ($policy->min_nao_alfanumericos > 0) {
            $especiais = preg_match_all('/[^A-Za-z0-9]/', $senha);
            if ($especiais < $policy->min_nao_alfanumericos) {
                $errors[] = "A senha deve conter pelo menos {$policy->min_nao_alfanumericos} caractere(s) especial(is).";
            }
        }

        // Histórico de senhas
        if ($userId && $policy->historico_senhas > 0) {
            if ($this->senhaNoHistorico($userId, $senha, $policy->historico_senhas)) {
                $errors[] = "A senha não pode ser igual às últimas {$policy->historico_senhas} senhas utilizadas.";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Verifica se senha está no histórico
     */
    private function senhaNoHistorico(int $userId, string $senha, int $limite): bool
    {
        $sql = 'SELECT password FROM adms_password_history 
                WHERE user_id = :user_id 
                ORDER BY created_at DESC 
                LIMIT :limite';
        
        $stmt = $this->loginRepo->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, \PDO::PARAM_INT);
        $stmt->execute();
        
        $historico = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        
        foreach ($historico as $senhaHash) {
            if (password_verify($senha, $senhaHash)) {
                return true;
            }
        }
        
        return false;
    }
} 