<?php

namespace App\adms\Models\Services;

use App\adms\Helpers\SendWhatsAppService;
use App\adms\Models\Repository\InformativosRepository;
use App\adms\Models\Repository\UsersRepository;

/**
 * Serviço centralizado para envio de notificações via WhatsApp.
 *
 * Primeira implementação: notificar usuários sobre Informativos.
 */
class WhatsappNotificationService
{
    /**
     * Notifica usuários ativos que aceitaram receber notificações
     * sobre um Informativo marcado para notificação.
     *
     * Se houver departamentos configurados em adms_informativos_notify_departments,
     * apenas usuários desses departamentos serão notificados. Caso contrário,
     * todos os departamentos são considerados.
     *
     * @param int $informativoId
     * @return void
     */
    public static function notificarInformativoUrgente(int $informativoId): void
    {
        try {
            $informativosRepo = new InformativosRepository();
            $informativo = $informativosRepo->getInformativoById($informativoId);

            if (!$informativo) {
                return;
            }

            // Segurança extra: só notificar se estiver ativo e marcado para notificação
            if (empty($informativo['ativo']) || empty($informativo['notificar'])) {
                return;
            }

            // Departamentos alvo de notificação (se vazio, considera todos)
            $departmentsTarget = $informativosRepo->getNotifyDepartmentsIds($informativoId);

            $usersRepo = new UsersRepository();

            // Buscar usuários ativos e aptos a receber WhatsApp
            $sql = 'SELECT id, name, celular, user_department_id 
                    FROM adms_users 
                    WHERE status = :status 
                      AND (receber_notificacoes_whatsapp = 1 OR receber_notificacoes_whatsapp IS NULL)';

            // Se houver departamentos específicos, filtrar por eles
            $params = [];
            if (!empty($departmentsTarget)) {
                $placeholders = [];
                foreach ($departmentsTarget as $idx => $depId) {
                    $ph = ':dep' . $idx;
                    $placeholders[] = $ph;
                    $params[$ph] = (int)$depId;
                }
                $sql .= ' AND user_department_id IN (' . implode(',', $placeholders) . ')';
            }

            $stmt = $usersRepo->getConnection()->prepare($sql);
            $stmt->bindValue(':status', 'Ativo', \PDO::PARAM_STR);
            foreach ($params as $ph => $value) {
                $stmt->bindValue($ph, $value, \PDO::PARAM_INT);
            }

            $stmt->execute();
            $usuarios = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            if (empty($usuarios)) {
                return;
            }

            $titulo = $informativo['titulo'] ?? 'Novo informativo';
            $resumo = $informativo['resumo'] ?? strip_tags($informativo['conteudo'] ?? '');
            $urlBase = rtrim($_ENV['URL_ADM'] ?? '', '/');
            $link = $urlBase . '/view-informativo/' . $informativoId;

            foreach ($usuarios as $usuario) {
                $celular = $usuario['celular'] ?? '';
                if (empty($celular)) {
                    continue;
                }

                $nome = trim($usuario['name'] ?? '');
                $saudacaoNome = $nome !== '' ? $nome : 'Olá';

                $mensagem = $saudacaoNome . "!\n\n";
                $mensagem .= "Você recebeu um novo informativo no Sistema Administrativo Tiaraju";
                if (!empty($informativo['urgente'])) {
                    $mensagem .= " *[URGENTE]*";
                }
                $mensagem .= ":\n\n";
                $mensagem .= "*" . $titulo . "*\n";

                if (!empty($resumo)) {
                    // Limitar tamanho para mensagem de WhatsApp, se necessário
                    $mensagem .= $resumo . "\n\n";
                } else {
                    $mensagem .= "\n";
                }

                $mensagem .= "Acesse para ver os detalhes:\n" . $link;

                SendWhatsAppService::sendMessage($celular, $mensagem);
            }
        } catch (\Throwable $e) {
            // Não quebrar fluxo principal por falha de notificação
            error_log('WhatsappNotificationService::notificarInformativoUrgente error: ' . $e->getMessage());
        }
    }

    /**
     * Notifica um usuário por WhatsApp de que foi atribuído a uma etapa de projeto.
     *
     * @param int    $projectId   ID do projeto
     * @param string $projectName Nome do projeto
     * @param string $stageName   Nome da etapa
     * @param int    $userId      ID do usuário responsável (a ser notificado)
     */
    public static function notificarEtapaProjetoAtribuida(int $projectId, string $projectName, string $stageName, int $userId): void
    {
        if ($userId <= 0) {
            return;
        }
        try {
            $usersRepo = new UsersRepository();
            $user = $usersRepo->getUser($userId);
            if (!$user || ($user['status'] ?? '') !== 'Ativo') {
                return;
            }
            if (empty($user['celular'])) {
                return;
            }
            if (isset($user['receber_notificacoes_whatsapp']) && (int)$user['receber_notificacoes_whatsapp'] === 0) {
                return;
            }

            $nome = trim($user['name'] ?? '');
            $saudacao = $nome !== '' ? $nome : 'Olá';
            $urlBase = rtrim($_ENV['URL_ADM'] ?? '', '/');
            $link = $urlBase . '/update-project/' . $projectId;

            $mensagem = $saudacao . "!\n\n";
            $mensagem .= "Você foi atribuído(a) a uma etapa no *Sistema Administrativo Tiaraju*:\n\n";
            $mensagem .= "*Projeto:* " . $projectName . "\n";
            $mensagem .= "*Etapa:* " . $stageName . "\n\n";
            $mensagem .= "Acesse o projeto:\n" . $link;

            SendWhatsAppService::sendMessage($user['celular'], $mensagem);
        } catch (\Throwable $e) {
            error_log('WhatsappNotificationService::notificarEtapaProjetoAtribuida error: ' . $e->getMessage());
        }
    }
}

