<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use Exception;
use PDO;


class ResetPasswordRepository extends DbConnection
{


    public function getUser(string $identificador)
    {
        // Permitir buscar por e-mail OU CPF
        // Detectar se é e-mail (contém "@") ou CPF (apenas dígitos)
        $identificador = trim($identificador);

        $isEmail = str_contains($identificador, '@');

        if ($isEmail) {
            $sql = "SELECT id, name, email, cpf, celular, recover_password, validate_recover_password 
                    FROM adms_users 
                    WHERE email = :valor 
                    LIMIT 1";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindParam(':valor', $identificador, PDO::PARAM_STR);
        } else {
            // Tratar como CPF: remover máscara e, se tiver 11 dígitos, formatar como 000.000.000-00
            $digits = preg_replace('/\D/', '', $identificador);

            if (strlen($digits) === 11) {
                $cpfFormatado = substr($digits, 0, 3) . '.' .
                    substr($digits, 3, 3) . '.' .
                    substr($digits, 6, 3) . '-' .
                    substr($digits, 9, 2);
            } else {
                // Formato inválido de CPF - forçar busca impossível para retornar vazio
                $cpfFormatado = '__CPF_INVALIDO__';
            }

            $sql = "SELECT id, name, email, cpf, celular, recover_password, validate_recover_password 
                    FROM adms_users 
                    WHERE cpf = :valor 
                    LIMIT 1";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindParam(':valor', $cpfFormatado, PDO::PARAM_STR);
        }

        // Executar a QUERY
        $stmt->execute();

        // Ler o registro e retornar
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateForgotPassword(array $data): bool
    {
        // Usar try e catch para gerenciar exceção/erro

        try { // Permanece no try se não houver nenhum erro

            // QUERY para atualizar o usuário (usar ID para suportar usuários sem e-mail)
            $sql = 'UPDATE adms_users 
                    SET recover_password = :recover_password, 
                        validate_recover_password = :validate_recover_password,  
                        updated_at = :updated_at
                    WHERE id = :id 
                    LIMIT 1';

            // Preparar a QUERY
            $stmt = $this->getConnection()->prepare($sql);

            // Substituir os links da QUERY pelo valor
            $stmt->bindValue(':recover_password', $data['recover_password'], PDO::PARAM_STR);
            $stmt->bindValue(':validate_recover_password', $data['validate_recover_password']);
            $stmt->bindValue(':updated_at', date("Y-m-d H:i:s"));
            $stmt->bindValue(':id', $data['user']['id'], PDO::PARAM_INT);


            // Retornar TRUE quando conseguir executar a QUERY SQL, não considerando se alterou dados do registro
            return $stmt->execute();

        } catch (Exception $e) { // Acessa o catch quando houver erro no try

            // Chamar o método para salvar o log
            GenerateLog::generateLog("error", "Erro ao atualizar dados de recuperação de senha.", [
                'user_id' => $data['user']['id'] ?? null,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    public function updatePassword(array $data): bool
    {

        // Usar try e catch para gerenciar exceção/erro
        try {  // Permanece no try se não houver nenhum erro

            $conn = $this->getConnection();

            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

            // QUERY para atualizar usuário (usar ID para suportar usuários sem e-mail)
            $sql = 'UPDATE adms_users 
                    SET password = :password, 
                        recover_password = NULL, 
                        validate_recover_password = NULL,  
                        updated_at = :updated_at 
                    WHERE id = :id';

            // Preparar a QUERY
            $stmt = $conn->prepare($sql);

            // Substituir os links da QUERY pelo valor
            $stmt->bindValue(':password', $hashedPassword);
            $stmt->bindValue(':updated_at', date("Y-m-d H:i:s"));
            $stmt->bindValue(':id', $data['user_id'], PDO::PARAM_INT);

            $result = $stmt->execute();

            if ($result) {
                // Registrar histórico de senha para o próprio usuário (reset via fluxo público)
                try {
                    $sqlHist = 'INSERT INTO adms_password_history (user_id, password, created_at)
                                VALUES (:user_id, :password, :created_at)';
                    $stmtHist = $conn->prepare($sqlHist);
                    $stmtHist->bindValue(':user_id', $data['user_id'], PDO::PARAM_INT);
                    $stmtHist->bindValue(':password', $hashedPassword, PDO::PARAM_STR);
                    $stmtHist->bindValue(':created_at', date("Y-m-d H:i:s"));
                    $stmtHist->execute();

                    // Limitar quantidade de registros de histórico de acordo com a política
                    try {
                        $policyRepo = new \App\adms\Models\Repository\AdmsPasswordPolicyRepository();
                        $policy = $policyRepo->getPolicy();
                        $limite = $policy ? (int)$policy->historico_senhas : 0;

                        if ($limite > 0) {
                            $sqlCleanup = '
                                DELETE FROM adms_password_history
                                WHERE user_id = :user_id
                                  AND id NOT IN (
                                      SELECT id FROM (
                                          SELECT id
                                          FROM adms_password_history
                                          WHERE user_id = :user_id_inner
                                          ORDER BY created_at DESC
                                          LIMIT :limite
                                      ) AS t
                                  )';
                            $stmtCleanup = $conn->prepare($sqlCleanup);
                            $stmtCleanup->bindValue(':user_id', $data['user_id'], PDO::PARAM_INT);
                            $stmtCleanup->bindValue(':user_id_inner', $data['user_id'], PDO::PARAM_INT);
                            $stmtCleanup->bindValue(':limite', $limite, PDO::PARAM_INT);
                            $stmtCleanup->execute();
                        }
                    } catch (Exception $e) {
                        GenerateLog::generateLog("error", "Falha ao limpar histórico de senhas excedente (reset password).", [
                            'user_id' => $data['user_id'] ?? null,
                            'error' => $e->getMessage(),
                        ]);
                    }
                } catch (Exception $e) {
                    GenerateLog::generateLog("error", "Falha ao registrar histórico de senha (reset password).", [
                        'user_id' => (int)($data['user_id'] ?? 0),
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Retornar TRUE quando conseguir executar a QUERY SQL
            return $result;
        } catch (Exception $e) { // Acessa o catch quando houver erro no try

            // Chamar o método para salvar o log
            GenerateLog::generateLog("error", "Senha não editada.", [
                'user_id' => (int)($data['user_id'] ?? 0),
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }
}
