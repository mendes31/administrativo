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
            return $_ENV['URL_ADM'] . "serve-file?path={$type}/{$defaultImage}";
        }

        // Verificar se o arquivo existe
        $fullPath = "public/adms/uploads/{$imagePath}";
        if (!file_exists($fullPath)) {
            return $_ENV['URL_ADM'] . "serve-file?path={$type}/{$defaultImage}";
        }

        return $_ENV['URL_ADM'] . "serve-file?path=" . urlencode($imagePath);
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
    public static function displayImage(?string $imagePath, array $attributes = [], string $defaultImage = 'icon_user.png', string $type = 'users'): string
    {
        $defaultAttributes = [
            'alt' => 'Imagem',
            'class' => 'img-fluid',
            'style' => 'max-width: 100%; height: auto;'
        ];

        $attributes = array_merge($defaultAttributes, $attributes);
        
        $imageUrl = self::getImageUrl($imagePath, $defaultImage, $type);
        
        $html = '<img src="' . htmlspecialchars($imageUrl) . '"';
        
        foreach ($attributes as $key => $value) {
            $html .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
        }
        
        $html .= ' onerror="this.onerror=null; this.src=\'' . $_ENV['URL_ADM'] . "serve-file?path={$type}/{$defaultImage}" . '\';">';
        
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