<?php

namespace App\adms\Controllers\Services;

use App\adms\Helpers\SendEmailService;
use App\adms\Helpers\SendWhatsAppService;
use App\adms\Models\Repository\ResetPasswordRepository;

class RecoverPassword
{
    /** @var array|string|null $data Recebe os dados que devem ser enviados para a VIEW */
    private array|string|null $data = null;

    public function recoverPassword(array $data): bool 
    {
        // Instanciar o serviço para gerar a chave
        $valueGenerateKey = GenerateKeyService::generateKey();

        $data['key'] = $valueGenerateKey['key'];
        $data['recover_password'] = $valueGenerateKey['encryptedkey'];
        $data['validate_recover_password'] = date("Y-m-d H:i:s", strtotime('+1hour'));
        
        // Formatar a data e hora separadamente
        $formattedTime = date("H:i:s", strtotime($data['validate_recover_password']));
        $formattedDate = date("d/m/Y", strtotime($data['validate_recover_password']));

        // Instanciar Repository para resetar a senha
        $userUpdate = new ResetPasswordRepository();
        $result = $userUpdate->updateForgotPassword($data);

        // Acessa o IF se o repository retornou TRUE
        if(!$result){
            return false;

        }

        $name = explode(" ", $data['user']['name']);
        $firstName = $name[0];

        // Usar URL_ADM diretamente do .env (local ou produção)
        // Remover espaços em branco que podem vir do .env (ex: "URL_ADM= http://...")
        $baseUrl = trim($_ENV['URL_ADM'] ?? '');
        $baseUrl = rtrim($baseUrl, '/');

        // Log da URL original para diagnóstico
        error_log("RecoverPassword - URL_ADM original: " . ($_ENV['URL_ADM'] ?? 'NULL'));

        // Detectar se é ambiente local (localhost, 127.0.0.1, etc.)
        $isLocal = (
            stripos($baseUrl, 'localhost') !== false ||
            stripos($baseUrl, '127.0.0.1') !== false ||
            stripos($baseUrl, '::1') !== false ||
            preg_match('/^https?:\/\/192\.168\./', $baseUrl) ||
            preg_match('/^https?:\/\/10\./', $baseUrl)
        );

        // CORREÇÃO CRÍTICA: Forçar domínio correto APENAS em produção
        if (!$isLocal) {
            $dominioCorreto = 'www.administrativotiaraju.kinghost.net';
            
            // Extrair partes da URL usando parse_url para ser mais preciso
            $urlParts = parse_url($baseUrl);
            // USAR o protocolo do .env (não forçar HTTPS - pode causar problemas se não houver certificado válido)
            $protocolo = isset($urlParts['scheme']) ? $urlParts['scheme'] . '://' : 'http://';
            $host = $urlParts['host'] ?? '';
            $path = $urlParts['path'] ?? '/administrativo';
            
            // Se não conseguiu extrair com parse_url, tentar regex
            if (empty($host)) {
                // Extrair protocolo do .env (não forçar)
                if (preg_match('/^(https?:\/\/)/i', $baseUrl, $matches)) {
                    $protocolo = $matches[1];
                    $resto = substr($baseUrl, strlen($protocolo));
                } else {
                    $resto = $baseUrl;
                    $protocolo = 'http://'; // Padrão HTTP se não especificado
                }
                
                // Extrair host e path
                if (preg_match('/^([^\/]+)(\/.*)?$/', $resto, $matches)) {
                    $host = $matches[1];
                    $path = $matches[2] ?? '/administrativo';
                }
            }
            
            // Verificar se o domínio já está correto
            if (stripos($host, $dominioCorreto) !== false) {
                // Já está correto, apenas garantir formato
                error_log("RecoverPassword - Domínio já está correto: {$host}");
            } else {
                // Lista de domínios incorretos
                $dominiosIncorretos = [
                    'raju.kinghost.net',
                    'www.raju.kinghost.net',
                    'administrativotiaraju.kinghost.net',
                ];
                
                // Verificar se o host atual está na lista de incorretos
                $precisaCorrigir = false;
                foreach ($dominiosIncorretos as $incorreto) {
                    if (stripos($host, $incorreto) !== false) {
                        $precisaCorrigir = true;
                        error_log("RecoverPassword - Domínio incorreto detectado: {$host}");
                        break;
                    }
                }
                
                // Se contém kinghost.net mas não é o correto, também precisa corrigir
                if (!$precisaCorrigir && stripos($host, 'kinghost.net') !== false && stripos($host, $dominioCorreto) === false) {
                    $precisaCorrigir = true;
                    error_log("RecoverPassword - Domínio kinghost.net detectado mas incorreto: {$host}");
                }
                
                // Corrigir o domínio
                if ($precisaCorrigir) {
                    $host = $dominioCorreto;
                    error_log("RecoverPassword - Domínio corrigido para: {$dominioCorreto}");
                }
            }
            
            // Garantir que o path contenha /administrativo
            if (stripos($path, '/administrativo') === false) {
                $path = '/administrativo';
            }
            
            // Reconstruir URL completa (usando protocolo do .env, não forçar HTTPS)
            $baseUrl = $protocolo . $host . $path;
        }

        // Garantir que a URL tenha protocolo (http:// ou https://)
        // WhatsApp e navegadores móveis precisam do protocolo para reconhecer como link clicável
        if (!empty($baseUrl) && !preg_match('/^https?:\/\//i', $baseUrl)) {
            // Se não tem protocolo, adicionar http:// para local, http:// para produção (não forçar HTTPS)
            $protocolo = 'http://';
            $baseUrl = $protocolo . ltrim($baseUrl, '/');
            error_log("RecoverPassword - Protocolo adicionado: {$protocolo}");
        }
        // REMOVIDO: Não forçar conversão HTTP para HTTPS (pode causar problemas se não houver certificado válido)

        // Garantir que a URL termine com /administrativo/ para funcionar corretamente (apenas se não for local)
        if (!$isLocal && !empty($baseUrl) && stripos($baseUrl, '/administrativo') === false) {
            // Se não tem /administrativo, adicionar
            $baseUrl = rtrim($baseUrl, '/') . '/administrativo';
            error_log("RecoverPassword - Caminho /administrativo adicionado");
        }

        // Log da URL final para diagnóstico (com detalhes)
        error_log("RecoverPassword - URL_ADM do .env: " . ($_ENV['URL_ADM'] ?? 'NULL'));
        error_log("RecoverPassword - URL final gerada: " . $baseUrl);
        error_log("RecoverPassword - Protocolo usado: " . (preg_match('/^(https?):\/\//i', $baseUrl, $m) ? $m[1] : 'NENHUM'));

        // Incluir o identificador (e-mail ou CPF) na URL apenas para pré-preencher o formulário.
        // A validação de segurança continua baseada na chave e na validade.
        // Preferir o valor informado originalmente na tela (identifier = e-mail ou CPF);
        // se vazio, usar o e-mail atual do cadastro.
        $identifier = $data['form']['identifier'] ?? ($data['form']['email'] ?? ($data['user']['email'] ?? ''));
        $queryEmail = $identifier ? ('?email=' . urlencode($identifier)) : '';

        // Construir URL completa garantindo formato correto
        $url = $baseUrl . '/reset-password/' . $data['key'] . $queryEmail;
        
        // Log para debug (remover em produção se necessário)
        error_log("RecoverPassword - URL gerada: " . $url);

        // Método de entrega: email, whatsapp ou both
        $deliveryMethod = $data['form']['delivery_method'] ?? 'email';

        $sentEmail = false;
        $sentWhatsApp = false;

        // --- Envio por E-MAIL ---
        if ($deliveryMethod === 'email' || $deliveryMethod === 'both') {
            $subject = "Recuperar Senha.";

            $body = "<p>Prezado $firstName</p>";
            $body .= "<p>Você solicitou a alteração de sua senha.</p>";
            $body .= "<p>Para continuar, clique no link abaixo ou cole o endereço no seu navegador: </p>";
            $body .= "<p><a href='$url'>$url</a></p>";
            $body .= "<p>Por questões de segurança esse link é válido somente até as $formattedTime do dia $formattedDate. Caso esse prazo esteja expirado, será necessário solicitar outro link.</p>";
            $body .= "<p>Se você não solicitou essa alteração, nenhuma ação é necessária. Sua senha permanecerá a mesma até que você solicite um novo link.</p>";

            $altBody = "Prezado $firstName\n\n";
            $altBody .= "Você solicitou a alteração de sua senha.\n\n";
            $altBody .= "Para continuar, clique no link abaixo ou cole o endereço no seu navegador: \n\n";
            $altBody .= "$url\n\n";
            $altBody .= "Por questões de segurança esse link é válido somente até as $formattedTime do dia $formattedDate. Caso esse prazo esteja expirado, será necessário solicitar outro link.\n\n";
            $altBody .= "Se você não solicitou essa alteração, nenhuma ação é necessária. Sua senha permanecerá a mesma até que você solicite um novo link.\n\n";

            $sentEmail = SendEmailService::sendEmail(
                $data['user']['email'],
                $data['user']['name'],
                $subject,
                $body,
                $altBody
            );
        }

        // --- Envio por WHATSAPP ---
        if ($deliveryMethod === 'whatsapp' || $deliveryMethod === 'both') {
            $phone = $data['user']['celular'] ?? '';

            if (!empty($phone)) {
                // Formatar mensagem para WhatsApp com link em linha separada
                // O WhatsApp reconhece links quando estão em linha própria e começam com http:// ou https://
                // IMPORTANTE: Garantir que a URL tenha protocolo completo para funcionar em dispositivos móveis
                $mensagem = "Prezado {$firstName},\n\n";
                $mensagem .= "Você solicitou a alteração de sua senha.\n\n";
                $mensagem .= "Para continuar, acesse o link abaixo:\n\n";
                // Link em linha própria com protocolo completo para garantir que seja reconhecido como clicável
                // Adicionar espaço antes e depois para melhor reconhecimento
                $mensagem .= "👉 {$url}\n\n";
                $mensagem .= "Por questões de segurança, esse link é válido somente até as {$formattedTime} do dia {$formattedDate}.\n";
                $mensagem .= "Caso esse prazo esteja expirado, será necessário solicitar outro link.\n\n";
                $mensagem .= "Se você não solicitou essa alteração, nenhuma ação é necessária. Sua senha permanecerá a mesma até que você solicite um novo link.";

                // Log da URL que será enviada
                error_log("RecoverPassword WhatsApp - URL a ser enviada: " . $url);
                error_log("RecoverPassword WhatsApp - URL tem protocolo: " . (preg_match('/^https?:\/\//i', $url) ? 'SIM' : 'NÃO'));

                $resultWhats = SendWhatsAppService::sendMessage($phone, $mensagem);
                $sentWhatsApp = $resultWhats['success'] ?? false;
            }
        }

        // Regras de retorno:
        // - email: precisa ter enviado e-mail
        // - whatsapp: precisa ter enviado WhatsApp
        // - both: considera sucesso se pelo menos um dos dois canais funcionar
        if ($deliveryMethod === 'email') {
            return $sentEmail;
        }

        if ($deliveryMethod === 'whatsapp') {
            return $sentWhatsApp;
        }

        // both
        return ($sentEmail || $sentWhatsApp);
    }
}
