<?php

namespace App\adms\Helpers;

use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

 /**
 * Classe para gerar logs.
 *
 * Esta classe fornece métodos estáticos para criar e salvar logs em arquivos usando o Monolog, uma biblioteca de
 * logging para PHP. Os logs podem ser de diferentes níveis, como DEBUG, INFO, WARNING, ERROR, etc., e são salvos
 * em arquivos com base na data atual.
 *
 * @package App\adms\Helpers
 * @author Rafael Mendes
 */
class GenerateLog
{
    /**
     * Salvar um log em um arquivo.
     *
     * Este método cria ou abre um arquivo de log baseado na data atual e grava uma mensagem de log com um nível específico.
     * O nível do log determina a gravidade da mensagem e pode ser DEBUG, INFO, WARNING, ERROR, etc.
     *
     * @param string $level Nível de log para a mensagem. Os níveis válidos são: DEBUG, INFO, NOTICE, WARNING, ERROR, CRITICAL, ALERT, EMERGENCY.
     *  - DEBUG (100): Informação de depuração.
     *  - INFO (200): Eventos interessantes. Por exemplo: um usuário realizou o login ou logs de SQL.
     *  - NOTICE (250): Eventos normais, mas significantes.
     *  - WARNING (300): Ocorrências excepcionais, mas que não são erros. Por exemplo: Uso de APIs descontinuadas, uso inadequado de uma API. Em geral coisas que não estão erradas mas precisam de atenção.
     *  - ERROR (400): Erros de tempo de execução que não requerem ação imediata, mas que devem ser logados e monitorados.
     *  - CRITICAL (500): Condições criticas. Por exemplo: Um componente da aplicação não está disponível, uma exceção não esperada ocorreu.
     *  - ALERT (550): Uma ação imediata deve ser tomada. Exemplo: O sistema caiu, o banco de dados está indisponível , etc. Deve disparar um alerta para o responsável tomar providencia o mais rápido possível.
     *  - EMERGENCY (600): Emergência: O sistema está inutilizável.
     * @param string $message Mensagem de log que será gravada no arquivo.
     * @param array|null $content Conteúdo adicional a ser incluído no log, como dados contextuais ou informações extras.
     * @return void
     */
    public static function generateLog(string $level, string $message, array|null $content): void
    {
        // criar o logger
        $log = new Logger('name');

        // obeter a data atual no formato "ddmmyyyy"
        $nameFileLog = date('dmY') . ".log";

        // Criar o caminho dos logs
        $filePath = 'logs/' . $nameFileLog;

        // Verificar se o arquivo existe
        if(!file_exists($filePath)){

            // Abrir o arquivo para escrita
            $fileOpen = fopen($filePath, 'w');

            // Fechar o arquivo
            fclose($fileOpen);
        }

        // Utilizar StreamHandler para salvar os logs no arquivo
        $log->pushHandler(new StreamHandler($filePath, Level::Debug));

        // SAlvar o log no arquivo
        $log->$level($message, $content);


    }
}
