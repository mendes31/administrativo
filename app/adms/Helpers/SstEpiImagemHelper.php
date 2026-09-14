<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/** Upload, URL e exclusão da foto do cadastro de EPI. */
final class SstEpiImagemHelper
{
    public const FOLDER = 'sst/epis';

    public const MAX_BYTES = 5 * 1024 * 1024;

    /**
     * @param array<string, mixed>|null $file Campo $_FILES['imagem']
     * @return array{ok: bool, path: ?string, changed: bool, error: ?string}
     */
    public static function fromRequest(?array $file, ?string $current, bool $remove): array
    {
        $current = self::sanitizeStored($current);
        $errorCode = is_array($file) ? (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) : UPLOAD_ERR_NO_FILE;

        if ($errorCode !== UPLOAD_ERR_NO_FILE) {
            if ($errorCode !== UPLOAD_ERR_OK || !is_array($file)) {
                return [
                    'ok' => false,
                    'path' => $current,
                    'changed' => false,
                    'error' => 'Erro no envio da imagem. Tente novamente.',
                ];
            }
            if (!ImageHelper::isValidImage($file) || (int) ($file['size'] ?? 0) > self::MAX_BYTES) {
                return [
                    'ok' => false,
                    'path' => $current,
                    'changed' => false,
                    'error' => 'Envie JPG, PNG, GIF ou WEBP de até 5 MB.',
                ];
            }
            $uploaded = self::store($file);
            if ($uploaded === null) {
                return [
                    'ok' => false,
                    'path' => $current,
                    'changed' => false,
                    'error' => 'Não foi possível salvar a imagem.',
                ];
            }
            self::deleteStored($current);

            return ['ok' => true, 'path' => $uploaded, 'changed' => true, 'error' => null];
        }

        if ($remove && $current !== null) {
            self::deleteStored($current);

            return ['ok' => true, 'path' => null, 'changed' => true, 'error' => null];
        }

        return ['ok' => true, 'path' => $current, 'changed' => false, 'error' => null];
    }

    public static function sanitizeStored(?string $path): ?string
    {
        $path = str_replace('\\', '/', trim((string) $path));
        if ($path === '' || str_contains($path, '..')) {
            return null;
        }
        if (!str_starts_with($path, self::FOLDER . '/')) {
            return null;
        }

        return $path;
    }

    public static function url(?string $path): string
    {
        $path = self::sanitizeStored($path);
        if ($path === null) {
            return '';
        }

        return ImageHelper::getImageUrl($path, '', self::FOLDER);
    }

    public static function deleteStored(?string $path): void
    {
        $path = self::sanitizeStored($path);
        if ($path === null) {
            return;
        }
        $full = self::uploadsRoot() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
        if (is_file($full)) {
            @unlink($full);
        }
    }

    /**
     * Valida URL http(s) ou arquivo no ZIP com o mesmo nome da chave (nome do EPI).
     * @return string|null Mensagem de erro, ou null se ok
     */
    public static function validateImportSource(string $raw, string $assetsDir, string $epiNome = ''): ?string
    {
        $raw = trim($raw);
        $epiNome = trim($epiNome);
        if ($raw === '') {
            return null;
        }
        if (self::isHttpUrl($raw)) {
            return self::validateHttpUrl($raw);
        }
        if (str_contains($raw, '..') || str_contains($raw, "\0")) {
            return 'Nome de arquivo de foto inválido.';
        }
        if ($assetsDir === '' || !is_dir($assetsDir)) {
            return 'Para importar a foto pelo nome do EPI, envie também o ZIP com as fotos.';
        }
        if (self::resolveZipAsset($assetsDir, $raw, $epiNome) === null) {
            $hint = $epiNome !== '' ? $epiNome . '.png' : $raw;

            return 'Foto não encontrada no ZIP. O arquivo deve ter o mesmo nome da chave (nome do EPI), por exemplo "' . $hint . '".';
        }

        return null;
    }

    /**
     * Copia URL ou arquivo do ZIP para o cadastro. Substitui a foto atual.
     */
    public static function importFromSource(string $raw, ?string $current, string $assetsDir, string $epiNome = ''): string
    {
        $err = self::validateImportSource($raw, $assetsDir, $epiNome);
        if ($err !== null) {
            throw new \RuntimeException($err);
        }
        $raw = trim($raw);
        $epiNome = trim($epiNome);
        if (self::isHttpUrl($raw)) {
            $tmp = self::downloadToTemp($raw);
            try {
                $stored = self::storeFromFile($tmp, self::extensionFromName($raw) ?: 'jpg');
            } finally {
                if (is_file($tmp)) {
                    @unlink($tmp);
                }
            }
        } else {
            $src = self::resolveZipAsset($assetsDir, $raw, $epiNome);
            if ($src === null) {
                throw new \RuntimeException('Foto não encontrada no ZIP. O arquivo deve ter o mesmo nome da chave (nome do EPI).');
            }
            $stored = self::storeFromFile($src, strtolower((string) pathinfo($src, PATHINFO_EXTENSION)));
        }
        if ($stored === null) {
            throw new \RuntimeException('Não foi possível gravar a foto importada (use JPG, PNG, GIF ou WEBP de até 5 MB).');
        }
        self::deleteStored($current);

        return $stored;
    }

    /**
     * Localiza a foto no ZIP: coluna imagem (se for arquivo) ou o mesmo nome da chave do EPI.
     */
    public static function resolveZipAsset(string $assetsDir, string $imagem, string $epiNome): ?string
    {
        $imagem = trim($imagem);
        $epiNome = trim($epiNome);
        if ($imagem !== '' && !self::isHttpUrl($imagem)) {
            $found = self::findAsset($assetsDir, $imagem);
            if ($found !== null) {
                return $found;
            }
        }
        if ($epiNome !== '') {
            return self::findAssetByEpiName($assetsDir, $epiNome);
        }

        return null;
    }

    /** Foto cujo nome (sem extensão) é o mesmo da chave do EPI. */
    public static function findAssetByEpiName(string $assetsDir, string $epiNome): ?string
    {
        $want = self::assetStemKey($epiNome);
        if ($want === '' || $assetsDir === '' || !is_dir($assetsDir)) {
            return null;
        }
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($assetsDir, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($it as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile()) {
                continue;
            }
            if (self::assetStemKey($file->getFilename()) === $want && self::isAllowedImageFile($file->getPathname())) {
                return $file->getPathname();
            }
        }

        return null;
    }

    /**
     * Chave de comparação: ignora pasta, maiúsculas, acentos e espaços/underscores.
     * "Avental Raspa de Couro.png" = "Avental_Raspa_de_Couro.png".
     */
    public static function assetMatchKey(string $filename): string
    {
        $base = basename(str_replace('\\', '/', trim($filename)));
        $ext = strtolower((string) pathinfo($base, PATHINFO_EXTENSION));

        return self::foldName((string) pathinfo($base, PATHINFO_FILENAME)) . '.' . $ext;
    }

    /** Nome do EPI ou do arquivo, sem extensão, para casar com a chave. */
    public static function assetStemKey(string $name): string
    {
        $base = basename(str_replace('\\', '/', trim($name)));
        $ext = strtolower((string) pathinfo($base, PATHINFO_EXTENSION));
        $stem = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)
            ? (string) pathinfo($base, PATHINFO_FILENAME)
            : $base;

        return self::foldName($stem);
    }

    private static function foldName(string $stem): string
    {
        $stem = mb_strtolower($stem, 'UTF-8');
        $stem = strtr($stem, [
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'ê' => 'e', 'è' => 'e',
            'í' => 'i', 'ì' => 'i',
            'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ü' => 'u',
            'ç' => 'c',
        ]);

        return preg_replace('/[^a-z0-9]+/', '', $stem) ?? '';
    }

    public static function findAsset(string $assetsDir, string $name): ?string
    {
        $name = str_replace('\\', '/', trim($name));
        $name = ltrim($name, '/');
        if ($name === '' || str_contains($name, '..')) {
            return null;
        }
        $direct = $assetsDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $name);
        if (is_file($direct) && self::isAllowedImageFile($direct)) {
            return $direct;
        }
        $want = self::assetMatchKey($name);
        if ($want === '.' || $want === '') {
            return null;
        }
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($assetsDir, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($it as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile()) {
                continue;
            }
            if (self::assetMatchKey($file->getFilename()) === $want && self::isAllowedImageFile($file->getPathname())) {
                return $file->getPathname();
            }
        }

        return null;
    }

    private static function store(array $file): ?string
    {
        $ext = strtolower((string) pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return null;
        }
        $dir = self::ensureUploadDir();
        if ($dir === null) {
            return null;
        }
        $filename = str_replace('.', '', uniqid('', true)) . '_' . time() . '.' . $ext;
        $dest = $dir . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file($tmp, $dest)) {
            return null;
        }

        return self::FOLDER . '/' . $filename;
    }

    private static function storeFromFile(string $src, string $ext): ?string
    {
        $ext = strtolower($ext);
        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }
        if (!self::isAllowedImageFile($src)) {
            return null;
        }
        $dir = self::ensureUploadDir();
        if ($dir === null) {
            return null;
        }
        $filename = str_replace('.', '', uniqid('', true)) . '_' . time() . '.' . $ext;
        $dest = $dir . DIRECTORY_SEPARATOR . $filename;
        if (!copy($src, $dest)) {
            return null;
        }

        return self::FOLDER . '/' . $filename;
    }

    private static function ensureUploadDir(): ?string
    {
        $dir = self::uploadsRoot() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, self::FOLDER);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return null;
        }

        return $dir;
    }

    private static function isAllowedImageFile(string $path): bool
    {
        if (!is_file($path)) {
            return false;
        }
        $size = (int) filesize($path);
        if ($size <= 0 || $size > self::MAX_BYTES) {
            return false;
        }
        $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            return false;
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? (string) finfo_file($finfo, $path) : '';
        if ($finfo) {
            finfo_close($finfo);
        }
        $allowedMime = ['image/jpeg', 'image/pjpeg', 'image/png', 'image/x-png', 'image/gif', 'image/webp'];

        return in_array($mime, $allowedMime, true);
    }

    private static function isHttpUrl(string $raw): bool
    {
        return (bool) preg_match('#^https?://#i', $raw);
    }

    private static function validateHttpUrl(string $url): ?string
    {
        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['host']) || !in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true)) {
            return 'URL da foto inválida. Use http ou https.';
        }
        $host = strtolower((string) $parts['host']);
        if (in_array($host, ['localhost', '127.0.0.1', '::1', '0.0.0.0'], true) || str_ends_with($host, '.local')) {
            return 'URL da foto não pode apontar para o próprio servidor.';
        }
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
            if (filter_var($host, FILTER_VALIDATE_IP, $flags) === false) {
                return 'URL da foto não pode usar endereço interno.';
            }
        }

        return null;
    }

    private static function downloadToTemp(string $url): string
    {
        $block = self::validateHttpUrl($url);
        if ($block !== null) {
            throw new \RuntimeException($block);
        }
        $host = strtolower((string) (parse_url($url, PHP_URL_HOST) ?? ''));
        $ip = $host !== '' ? gethostbyname($host) : '';
        if ($ip !== '' && $ip !== $host && filter_var($ip, FILTER_VALIDATE_IP)) {
            $flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
            if (filter_var($ip, FILTER_VALIDATE_IP, $flags) === false) {
                throw new \RuntimeException('URL da foto não pode usar endereço interno.');
            }
        }
        $tmp = tempnam(sys_get_temp_dir(), 'epiimg');
        if ($tmp === false) {
            throw new \RuntimeException('Não foi possível baixar a foto.');
        }
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            $fp = fopen($tmp, 'wb');
            if ($ch === false || $fp === false) {
                throw new \RuntimeException('Não foi possível baixar a foto.');
            }
            curl_setopt_array($ch, [
                CURLOPT_FILE => $fp,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_USERAGENT => 'Administrativo-SST-EPI',
                CURLOPT_MAXFILESIZE => self::MAX_BYTES,
            ]);
            $ok = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            fclose($fp);
            if ($ok !== true || $code >= 400 || !is_file($tmp) || filesize($tmp) <= 0) {
                @unlink($tmp);
                throw new \RuntimeException('Não foi possível baixar a foto da URL (HTTP ' . $code . ').');
            }
            if ((int) filesize($tmp) > self::MAX_BYTES) {
                @unlink($tmp);
                throw new \RuntimeException('A foto da URL excede 5 MB.');
            }

            return $tmp;
        }
        $ctx = stream_context_create([
            'http' => ['timeout' => 20, 'follow_location' => 1, 'max_redirects' => 3],
            'https' => ['timeout' => 20],
        ]);
        $data = @file_get_contents($url, false, $ctx, 0, self::MAX_BYTES + 1);
        if (!is_string($data) || $data === '' || strlen($data) > self::MAX_BYTES) {
            @unlink($tmp);
            throw new \RuntimeException('Não foi possível baixar a foto da URL.');
        }
        file_put_contents($tmp, $data);

        return $tmp;
    }

    private static function extensionFromName(string $name): string
    {
        $path = (string) (parse_url($name, PHP_URL_PATH) ?: $name);
        $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        if ($ext === 'jpeg') {
            return 'jpg';
        }

        return in_array($ext, ['jpg', 'png', 'gif', 'webp'], true) ? $ext : '';
    }

    private static function uploadsRoot(): string
    {
        $root = (defined('APP_ROOT') && APP_ROOT !== '') ? APP_ROOT : dirname(__DIR__, 3);

        return $root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'adms' . DIRECTORY_SEPARATOR . 'uploads';
    }
}
