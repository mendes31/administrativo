<?php

namespace App\adms\Helpers;

/**
 * Classe helper para gerenciar imagens
 * 
 * @author Rafael Mendes
 */
class ImageHelper
{
    /**
     * Verifica se a imagem personalizada de usuário existe fisicamente.
     */
    public static function userImageExists(int $userId, ?string $imageName): bool
    {
        $name = trim((string) $imageName);
        if ($userId <= 0 || $name === '' || strcasecmp($name, 'icon_user.png') === 0) {
            return false;
        }

        $relative = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, 'public/adms/uploads/users/' . $userId . '/' . $name);

        return is_file($relative);
    }

    /**
     * Renderiza avatar textual com iniciais.
     */
    public static function renderInitialsAvatar(string $name, int $sizePx, array $attributes = []): string
    {
        $safeName = trim($name);
        $initials = '';
        if ($safeName !== '') {
            $parts = preg_split('/\s+/u', $safeName) ?: [];
            foreach ($parts as $part) {
                if ($part === '') {
                    continue;
                }
                $initials .= mb_substr($part, 0, 1, 'UTF-8');
                if (mb_strlen($initials, 'UTF-8') >= 2) {
                    break;
                }
            }
        }
        if ($initials === '') {
            $initials = 'U';
        }

        $fontSize = max(11, (int) floor($sizePx * 0.38));
        $defaultAttributes = [
            'class' => '',
            'style' => '',
            'aria-label' => 'Avatar de ' . ($safeName !== '' ? $safeName : 'Usuário'),
            'title' => $safeName !== '' ? $safeName : 'Usuário',
        ];
        $attributes = array_merge($defaultAttributes, $attributes);
        $classAttr = trim('rounded-circle d-inline-flex align-items-center justify-content-center fw-bold text-secondary ' . (string) $attributes['class']);
        $styleAttr = trim('width:' . $sizePx . 'px;height:' . $sizePx . 'px;background:#ececec;font-size:' . $fontSize . 'px;line-height:1;' . (string) $attributes['style']);

        $extraAttrs = '';
        foreach ($attributes as $key => $value) {
            if (in_array($key, ['class', 'style', 'aria-label', 'title'], true)) {
                continue;
            }
            $extraAttrs .= ' ' . htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8') . '="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '"';
        }

        return '<div class="' . htmlspecialchars($classAttr, ENT_QUOTES, 'UTF-8') . '"'
            . ' style="' . htmlspecialchars($styleAttr, ENT_QUOTES, 'UTF-8') . '"'
            . ' aria-label="' . htmlspecialchars((string) $attributes['aria-label'], ENT_QUOTES, 'UTF-8') . '"'
            . ' title="' . htmlspecialchars((string) $attributes['title'], ENT_QUOTES, 'UTF-8') . '"'
            . $extraAttrs . '>'
            . htmlspecialchars(mb_strtoupper($initials, 'UTF-8'), ENT_QUOTES, 'UTF-8')
            . '</div>';
    }

    /**
     * Codifica o valor de ?path= para serve-file sem transformar "/" em "%2F"
     * (evita falhas em Apache/proxy; o FileServer recebe "users/123/foto.png" corretamente).
     */
    public static function encodePathForServeFile(?string $path): string
    {
        if ($path === null || $path === '') {
            return '';
        }

        $path = str_replace('\\', '/', $path);
        $segments = array_values(array_filter(explode('/', $path), static fn ($s) => $s !== ''));

        return implode('/', array_map(static fn (string $s): string => rawurlencode($s), $segments));
    }

    /**
     * Obtém a URL da imagem com fallback para imagem padrão
     * 
     * @param string|null $imagePath Caminho da imagem
     * @param string $defaultImage Imagem padrão a ser usada
     * @param string $type Tipo de imagem (users, informativos, etc.)
     * @return string URL da imagem
     */
    public static function getImageUrl(?string $imagePath, string $defaultImage = 'icon_user.png', string $type = 'users'): string
    {
        if (empty($imagePath)) {
            $logical = "{$type}/{$defaultImage}";

            return $_ENV['URL_ADM'] . 'serve-file?path=' . self::encodePathForServeFile($logical);
        }

        $url = $_ENV['URL_ADM'] . 'serve-file?path=' . self::encodePathForServeFile($imagePath);
        $normalized = str_replace('\\', '/', (string) $imagePath);
        if (str_starts_with($normalized, 'users/')) {
            $fsPath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, 'public/adms/uploads/' . $normalized);
            if (is_file($fsPath)) {
                $url .= '&v=' . (int) filemtime($fsPath);
            }
        }

        return $url;
    }

    /**
     * Exibe uma imagem com tratamento de erro
     * 
     * @param string|null $imagePath Caminho da imagem
     * @param array $attributes Atributos HTML para a tag img
     * @param string $defaultImage Imagem padrão
     * @param string $type Tipo de imagem
     * @return string HTML da tag img
     */
    /**
     * Avatar no topo (navbar): carregamento imediato, sem lazy, para não piscar entre páginas.
     */
    public static function displayNavbarUserAvatar(?string $imagePath, array $attributes = []): string
    {
        $defaults = [
            'alt' => 'Foto do usuário',
            'class' => 'rounded-circle me-2 navbar-user-avatar',
            'style' => 'width: 32px; height: 32px; object-fit: cover;',
            'loading' => 'eager',
            'decoding' => 'sync',
            'fetchpriority' => 'high',
            'onload' => "this.classList.add('is-ready')",
        ];

        return self::displayImage($imagePath, array_merge($defaults, $attributes), 'icon_user.png', 'users');
    }

    public static function displayImage(?string $imagePath, array $attributes = [], string $defaultImage = 'icon_user.png', string $type = 'users'): string
    {
        $defaultAttributes = [
            'alt' => 'Imagem',
            'class' => 'img-fluid',
            'style' => 'max-width: 100%; height: auto;',
            'loading' => 'lazy',
            'decoding' => 'async',
            'fetchpriority' => 'low'
        ];

        $attributes = array_merge($defaultAttributes, $attributes);
        
        $imageUrl = self::getImageUrl($imagePath, $defaultImage, $type);
        
        $html = '<img src="' . htmlspecialchars($imageUrl) . '"';
        
        foreach ($attributes as $key => $value) {
            $html .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
        }
        
        $fallbackQs = self::encodePathForServeFile("{$type}/{$defaultImage}");
        $fallbackUrl = $_ENV['URL_ADM'] . 'serve-file?path=' . $fallbackQs;
        $html .= ' onerror="this.onerror=null; this.src=\'' . addslashes($fallbackUrl) . '\';">';
        
        return $html;
    }

    /**
     * Valida se um arquivo é uma imagem válida
     * 
     * @param array $file Array do arquivo ($_FILES['field'])
     * @return bool
     */
    public static function isValidImage(array $file): bool
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return false;
        }

        // Verificar extensão
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array($extension, $allowedExtensions)) {
            return false;
        }

        // Verificar MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimeTypes = [
            'image/jpeg',
            'image/pjpeg',
            'image/png',
            'image/x-png',
            'image/gif',
            'image/webp'
        ];

        return in_array($mimeType, $allowedMimeTypes);
    }

    /**
     * Faz upload de uma imagem com validação
     * 
     * @param array $file Array do arquivo
     * @param string $directory Diretório de destino
     * @param int $maxSize Tamanho máximo em bytes
     * @return string|null Caminho do arquivo ou null se falhar
     */
    public static function uploadImage(array $file, string $directory, int $maxSize = 5242880): ?string
    {
        // Validar arquivo
        if (!self::isValidImage($file)) {
            return null;
        }

        // Verificar tamanho
        if ($file['size'] > $maxSize) {
            return null;
        }

        // Criar diretório se não existir
        $uploadDir = "public/adms/uploads/{$directory}/";
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                return null;
            }
        }

        // Gerar nome único
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $filepath = $uploadDir . $filename;

        // Mover arquivo
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            return null;
        }

        return $directory . '/' . $filename;
    }

    /**
     * Remove uma imagem do servidor
     * 
     * @param string $imagePath Caminho da imagem
     * @return bool
     */
    public static function deleteImage(string $imagePath): bool
    {
        $fullPath = "public/adms/uploads/{$imagePath}";
        
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        
        return true; // Arquivo não existe, consideramos sucesso
    }

    /**
     * Redimensiona uma imagem
     * 
     * @param string $sourcePath Caminho da imagem original
     * @param string $destinationPath Caminho da imagem redimensionada
     * @param int $width Largura desejada
     * @param int $height Altura desejada
     * @param bool $maintainAspectRatio Manter proporção
     * @return bool
     */
    public static function resizeImage(string $sourcePath, string $destinationPath, int $width, int $height, bool $maintainAspectRatio = true): bool
    {
        if (!file_exists($sourcePath)) {
            return false;
        }

        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            return false;
        }

        $originalWidth = $imageInfo[0];
        $originalHeight = $imageInfo[1];
        $mimeType = $imageInfo['mime'];

        // Calcular novas dimensões
        if ($maintainAspectRatio) {
            $ratio = min($width / $originalWidth, $height / $originalHeight);
            $newWidth = round($originalWidth * $ratio);
            $newHeight = round($originalHeight * $ratio);
        } else {
            $newWidth = $width;
            $newHeight = $height;
        }

        // Criar imagem
        $sourceImage = self::createImageFromFile($sourcePath, $mimeType);
        if (!$sourceImage) {
            return false;
        }

        $destinationImage = imagecreatetruecolor($newWidth, $newHeight);

        // Preservar transparência para PNG
        if ($mimeType === 'image/png') {
            imagealphablending($destinationImage, false);
            imagesavealpha($destinationImage, true);
        }

        // Redimensionar
        imagecopyresampled(
            $destinationImage, $sourceImage,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $originalWidth, $originalHeight
        );

        // Salvar
        $result = self::saveImage($destinationImage, $destinationPath, $mimeType);

        // Limpar memória
        imagedestroy($sourceImage);
        imagedestroy($destinationImage);

        return $result;
    }

    /**
     * Cria uma imagem a partir de um arquivo
     */
    private static function createImageFromFile(string $path, string $mimeType)
    {
        switch ($mimeType) {
            case 'image/jpeg':
            case 'image/pjpeg':
                return imagecreatefromjpeg($path);
            case 'image/png':
            case 'image/x-png':
                return imagecreatefrompng($path);
            case 'image/gif':
                return imagecreatefromgif($path);
            case 'image/webp':
                return imagecreatefromwebp($path);
            default:
                return false;
        }
    }

    /**
     * Salva uma imagem em arquivo
     */
    private static function saveImage($image, string $path, string $mimeType): bool
    {
        // Criar diretório se não existir
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        switch ($mimeType) {
            case 'image/jpeg':
            case 'image/pjpeg':
                return imagejpeg($image, $path, 90);
            case 'image/png':
            case 'image/x-png':
                return imagepng($image, $path, 9);
            case 'image/gif':
                return imagegif($image, $path);
            case 'image/webp':
                return imagewebp($image, $path, 90);
            default:
                return false;
        }
    }
} 