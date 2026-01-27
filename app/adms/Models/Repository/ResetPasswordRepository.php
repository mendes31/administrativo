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

            // QUERY para atualizar o usuário
            $sql = 'UPDATE adms_users SET recover_password = :recover_password, validate_recover_password = :validate_recover_password,  updated_at = :updated_at';

            // Condição para indicar qual registro editar
            $sql .= ' WHERE email = :email LIMIT 1';

            // Preparar a QUERY
            $stmt = $this->getConnection()->prepare($sql);

            // Substituir os links da QUERY pelo valor
            $stmt->bindValue(':recover_password', $data['recover_password'], PDO::PARAM_STR);
            $stmt->bindValue(':validate_recover_password', $data['validate_recover_password']);
            $stmt->bindValue(':updated_at', date("Y-m-d H:i:s"));
            $stmt->bindValue(':email', $data['form']['email'], PDO::PARAM_STR);


            // Retornar TRUE quando conseguir executar a QUERY SQL, não considerando se alterou dados do registro
            return $stmt->execute();

        } catch (Exception $e) { // Acessa o catch quando houver erro no try

            // Chamar o método para salvar o log
            GenerateLog::generateLog("error", "Email para recuperar senha não encontrado.", ['email' => $data['email'], 'error' => $e->getMessage()]);

            return false;
        }
    }

    public function updatePassword(array $data): bool
    {

        // Usar try e catch para gerenciar exceção/erro
        try {  // Permanece no try se não houver nenhum erro

            // QUERY para atualizar usuário
            // Condição para indicar qual registro editar
            $sql = 'UPDATE adms_users SET password = :password, recover_password = NULL, validate_recover_password = NULL,  updated_at = :updated_at WHERE email = :email';

            // Preparar a QUERY
            $stmt = $this->getConnection()->prepare($sql);

            // Substituir os links da QUERY pelo valor
            $stmt->bindValue(':password', password_hash($data['password'], PASSWORD_DEFAULT));
            $stmt->bindValue(':updated_at', date("Y-m-d H:i:s"));
            $stmt->bindValue(':email', $data['email'], PDO::PARAM_STR);

            // Retornar TRUE quando conseguir executar a QUERY SQL, não considerando se alterou dados do registro
            return $stmt->execute();
        } catch (Exception $e) { // Acessa o catch quando houver erro no try

            // Chamar o método para salvar o log
            GenerateLog::generateLog("error", "Senha não editada.", ['email' => (string) $data['email'], 'error' => $e->getMessage()]);

            return false;
        }
    }
}
