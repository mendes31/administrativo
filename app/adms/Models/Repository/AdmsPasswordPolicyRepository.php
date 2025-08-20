<?php

namespace App\adms\Models\Repository;

use App\adms\Models\AdmsPasswordPolicy;
use PDO;
use App\adms\Models\Services\DbConnection;
use Exception;

class AdmsPasswordPolicyRepository extends DbConnection
{
    protected string $table = 'adms_password_policy';

    /**
     * Buscar a política de senha mais recente.
     */
    public function getPolicy(): ?AdmsPasswordPolicy
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY id DESC LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($data) {
            $policy = new AdmsPasswordPolicy();
            foreach ($data as $key => $value) {
                $policy->$key = $value;
            }
            $policy->expirar_sessao_por_tempo = $data['expirar_sessao_por_tempo'] ?? 'Não';
            $policy->tempo_expiracao_sessao = (int)($data['tempo_expiracao_sessao'] ?? 30);
            return $policy;
        }
        return null;
    }

    /**
     * Buscar política de senha pelo ID
     */
    public function getById(int $id): ?AdmsPasswordPolicy
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($data) {
            $policy = new AdmsPasswordPolicy();
            foreach ($data as $key => $value) {
                $policy->$key = $value;
            }
            $policy->expirar_sessao_por_tempo = $data['expirar_sessao_por_tempo'] ?? 'Não';
            $policy->tempo_expiracao_sessao = (int)($data['tempo_expiracao_sessao'] ?? 30);
            return $policy;
        }
        return null;
    }

    /**
     * Atualizar política de senha
     */
    public function update(array $data): bool
    {
        try {
            $sql = "UPDATE {$this->table} SET 
                vencimento_dias = :vencimento_dias, 
                comprimento_minimo = :comprimento_minimo, 
                min_maiusculas = :min_maiusculas, 
                min_minusculas = :min_minusculas, 
                min_digitos = :min_digitos, 
                min_nao_alfanumericos = :min_nao_alfanumericos, 
                historico_senhas = :historico_senhas, 
                tentativas_bloqueio = :tentativas_bloqueio, 
                tentativas_bloqueio_temporario = :tentativas_bloqueio_temporario, 
                tempo_bloqueio_temporario = :tempo_bloqueio_temporario, 
                bloqueio_temporario = :bloqueio_temporario, 
                notificar_usuario_bloqueio = :notificar_usuario_bloqueio, 
                notificar_admins_bloqueio = :notificar_admins_bloqueio, 
                forcar_logout_troca_senha = :forcar_logout_troca_senha, 
                expirar_sessao_por_tempo = :expirar_sessao_por_tempo, 
                tempo_expiracao_sessao = :tempo_expiracao_sessao, 
                exemplo_senha = :exemplo_senha, 
                nivel_seguranca = :nivel_seguranca, 
                updated_at = :updated_at 
                WHERE id = :id";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':vencimento_dias', $data['vencimento_dias']);
            $stmt->bindValue(':comprimento_minimo', $data['comprimento_minimo']);
            $stmt->bindValue(':min_maiusculas', $data['min_maiusculas']);
            $stmt->bindValue(':min_minusculas', $data['min_minusculas']);
            $stmt->bindValue(':min_digitos', $data['min_digitos']);
            $stmt->bindValue(':min_nao_alfanumericos', $data['min_nao_alfanumericos']);
            $stmt->bindValue(':historico_senhas', $data['historico_senhas']);
            $stmt->bindValue(':tentativas_bloqueio', $data['tentativas_bloqueio']);
            $stmt->bindValue(':tentativas_bloqueio_temporario', $data['tentativas_bloqueio_temporario']);
            $stmt->bindValue(':tempo_bloqueio_temporario', $data['tempo_bloqueio_temporario']);
            $stmt->bindValue(':bloqueio_temporario', $data['bloqueio_temporario']);
            $stmt->bindValue(':notificar_usuario_bloqueio', $data['notificar_usuario_bloqueio']);
            $stmt->bindValue(':notificar_admins_bloqueio', $data['notificar_admins_bloqueio']);
            $stmt->bindValue(':forcar_logout_troca_senha', $data['forcar_logout_troca_senha']);
            $stmt->bindValue(':expirar_sessao_por_tempo', $data['expirar_sessao_por_tempo']);
            $stmt->bindValue(':tempo_expiracao_sessao', $data['tempo_expiracao_sessao']);
            $stmt->bindValue(':exemplo_senha', $data['exemplo_senha']);
            $stmt->bindValue(':nivel_seguranca', $data['nivel_seguranca']);
            $stmt->bindValue(':updated_at', date('Y-m-d H:i:s'));
            $stmt->bindValue(':id', $data['id']);
            return $stmt->execute();
        } catch (Exception $e) {
            file_put_contents(__DIR__ . '/../../../logs/password_policy_update_error.log', date('Y-m-d H:i:s') . ' - ' . $e->getMessage() . "\n" . print_r($data, true) . "\n", FILE_APPEND);
            return false;
        }
    }
} 