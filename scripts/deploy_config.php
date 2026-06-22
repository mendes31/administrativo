<?php

declare(strict_types=1);

/**
 * Configuração central do caminho remoto FTP (Kinghost).
 *
 * Na Kinghost, o login FTP/WebFTP do site já abre DENTRO de ~/www/administrativo/
 * (app, bin, config, index.php na raiz da sessão). NÃO existe subpasta administrativo/
 * dentro desse diretório — usar server-dir: administrativo/ cria lixo
 * (Administrativo/App/Adms/...) e desalinha deploy + verificação SHA-256.
 *
 * Se numa conta antiga o login abrir em ~/www/ (com administrativo/ como subpasta),
 * defina FTP_REMOTE_BASE=administrativo no workflow ou .env de deploy.
 */

function deployFtpRemoteBase(): string
{
    $base = getenv('FTP_REMOTE_BASE');
    if ($base === false || $base === '') {
        return '';
    }

    return trim(str_replace('\\', '/', $base), '/');
}

/** Caminho para SamKirkland/FTP-Deploy-Action (server-dir). */
function deployFtpServerDir(): string
{
    $base = deployFtpRemoteBase();

    return $base === '' ? './' : $base . '/';
}

/** Monta caminho remoto relativo à raiz da sessão FTP. */
function deployFtpRemotePath(string $relativePath): string
{
    $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
    $base = deployFtpRemoteBase();

    return $base === '' ? $relativePath : $base . '/' . $relativePath;
}
