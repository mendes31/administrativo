<?php

namespace App\adms\Models\Services;

use App\adms\Models\Repository\LoginRepository;

/**
 * Serviço para validação de ações sensíveis (edições/exclusões),
 * exigindo senha e, opcionalmente, justificativa.
 *
 * Pode ser reutilizado em qualquer módulo.
 */
class SensitiveActionService
{
    /**
     * Valida senha do usuário logado e justificativa.
     *
     * @param string $password Senha informada no formulário
     * @param string|null $justificativa Texto de justificativa/motivo
     * @param bool $justificativaObrigatoria Se true, exige justificativa não vazia
     * @return array ['success' => bool, 'message' => string]
     */
    public static function validarConfirmacao(string $password, ?string $justificativa, bool $justificativaObrigatoria = true): array
    {
        if (empty($_SESSION['user_id']) || empty($_SESSION['user_username'])) {
            return [
                'success' => false,
                'message' => 'Usuário não autenticado.',
            ];
        }

        if ($justificativaObrigatoria && (trim((string)$justificativa) === '')) {
            return [
                'success' => false,
                'message' => 'Justificativa é obrigatória.',
            ];
        }

        if ($password === '') {
            return [
                'success' => false,
                'message' => 'Senha de confirmação é obrigatória.',
            ];
        }

        $loginRepo = new LoginRepository();
        $user = $loginRepo->getUser($_SESSION['user_username']);

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Usuário não encontrado.',
            ];
        }

        if (!password_verify($password, $user['password'])) {
            return [
                'success' => false,
                'message' => 'Senha incorreta.',
            ];
        }

        return [
            'success' => true,
            'message' => 'OK',
        ];
    }
}


