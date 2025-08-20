<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;


class LoginRepository extends DbConnection
{

    public function getUser(string $username): array|bool
    {
        // QUERY para recuperar o registro selecionado do banco de dados
        // $sql = 'SELECT id, name, email, username, password
        //         FROM adms_users
        //         WHERE username = :username
        //         LIMIT 1';

        $sql = 'SELECT t0.id, t0.name, t0.email, t0.username, t0.image, t0.password, t0.user_department_id, t0.user_position_id, t0.created_at, 
                t0.updated_at, t0.bloqueado, t0.bloqueado_temporario, t0.data_bloqueio_temporario, t0.tentativas_login, t0.status,
                t0.modificar_senha_proximo_logon,
                t1.name dep_name, t2.name pos_name
                FROM adms_users t0
                INNER JOIN adms_departments t1 ON t0.user_department_id = t1.id
                INNER JOIN adms_positions t2 ON t0.user_position_id = t2.id
                WHERE t0.username = :username
                LIMIT 1';

        // Preparar a QUERY
        $stmt = $this->getConnection()->prepare($sql);

        // Substituir o link da QUERY pelo valor / Evita SQL INJECTION
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);

        // Executar a QUERY
        $stmt->execute();

        // Ler o registro e retornar
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Incrementa tentativas de login do usuário
     * ATENÇÃO: Este método atualiza apenas tentativas_login
     * Para atualizações completas, use os métodos do SecurityService
     */
    public function incrementarTentativasLogin(int $userId): void
    {
        $sql = 'UPDATE adms_users SET tentativas_login = tentativas_login + 1 WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Reseta tentativas de login do usuário
     * ATENÇÃO: Este método atualiza apenas tentativas_login
     * Para atualizações completas, use os métodos do SecurityService
     */
    public function resetarTentativasLogin(int $userId): void
    {
        $sql = 'UPDATE adms_users SET tentativas_login = 0 WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Incrementa tentativas de login e aplica bloqueio se necessário
     * Este método garante que todos os campos relacionados sejam atualizados juntos
     */
    public function incrementarTentativasLoginCompleto(int $userId, ?int $limiteTemporario, int $limiteDefinitivo, bool $aplicaBloqueioTemporario = true): array
    {
        // Primeiro, buscar dados atuais do usuário
        $user = $this->getUserById($userId);
        if (!$user) {
            return ['success' => false, 'message' => 'Usuário não encontrado'];
        }

        $tentativasAtuais = $user['tentativas_login'] ?? 0;
        $novaTentativa = $tentativasAtuais + 1;
        
        // Determinar se deve aplicar bloqueio
        $aplicarBloqueioTemporario = false;
        if ($aplicaBloqueioTemporario && $limiteTemporario !== null) {
            $aplicarBloqueioTemporario = ($novaTentativa == $limiteTemporario);
        }
        $aplicarBloqueioDefinitivo = ($novaTentativa >= $limiteDefinitivo);
        
        // Preparar SQL baseado na situação
        if ($aplicarBloqueioDefinitivo) {
            // Bloqueio definitivo - remove data de bloqueio temporário
            $sql = 'UPDATE adms_users SET 
                    tentativas_login = :tentativas,
                    bloqueado = "Sim",
                    data_bloqueio_temporario = NULL
                    WHERE id = :id';
        } elseif ($aplicarBloqueioTemporario) {
            // Bloqueio temporário
            $sql = 'UPDATE adms_users SET 
                    tentativas_login = :tentativas,
                    bloqueado = "Sim",
                    data_bloqueio_temporario = NOW()
                    WHERE id = :id';
        } else {
            // Apenas incrementar tentativas
            $sql = 'UPDATE adms_users SET tentativas_login = :tentativas WHERE id = :id';
        }
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':tentativas', $novaTentativa, PDO::PARAM_INT);
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        return [
            'success' => true,
            'tentativas' => $novaTentativa,
            'bloqueio_temporario' => $aplicarBloqueioTemporario,
            'bloqueio_definitivo' => $aplicarBloqueioDefinitivo
        ];
    }

    /**
     * Reseta tentativas de login e desbloqueia usuário
     * Este método garante que todos os campos relacionados sejam atualizados juntos
     */
    public function resetarTentativasLoginCompleto(int $userId): bool
    {
        $sql = 'UPDATE adms_users SET 
                tentativas_login = 0,
                bloqueado = "Não",
                data_bloqueio_temporario = NULL
                WHERE id = :id';
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Bloqueia o usuário
     */
    public function bloquearUsuario(int $userId): void
    {
        $sql = 'UPDATE adms_users SET bloqueado = "Sim" WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Registra tentativa de login na tabela adms_login_attempts
     */
    public function registrarTentativaLogin(?int $userId, string $usernameTentado, string $ip, ?string $userAgent, string $resultado, ?string $detalhes = null): void
    {
        $sql = 'INSERT INTO adms_login_attempts (user_id, username_tentado, ip, user_agent, data_tentativa, resultado, detalhes, created_at) VALUES (:user_id, :username_tentado, :ip, :user_agent, NOW(), :resultado, :detalhes, NOW())';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, $userId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':username_tentado', $usernameTentado, PDO::PARAM_STR);
        $stmt->bindValue(':ip', $ip, PDO::PARAM_STR);
        $stmt->bindValue(':user_agent', $userAgent, PDO::PARAM_STR);
        $stmt->bindValue(':resultado', $resultado, PDO::PARAM_STR);
        $stmt->bindValue(':detalhes', $detalhes, PDO::PARAM_STR);
        $stmt->execute();
    }

    /**
     * Busca usuário pelo ID
     */
    public function getUserById(int $userId): array|bool
    {
        $sql = 'SELECT t0.*, t1.name AS dep_name, t2.name AS pos_name
                FROM adms_users t0
                INNER JOIN adms_departments t1 ON t0.user_department_id = t1.id
                INNER JOIN adms_positions t2 ON t0.user_position_id = t2.id
                WHERE t0.id = :id
                LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

}
