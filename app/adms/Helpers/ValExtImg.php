<?php

namespace App\adms\Helpers;

/**
 * Classe genérica para validar a extensão da imagem
 *
 * @author Rafael Mendes
 */
class ValExtImg
{
    /** @var string $mimeType Recebe o mimeType da imagem */
    private string $mimeType;

    /** @var bool $result Recebe true quando executar o processo com sucesso e false quando houver erro */
    private bool $result;

    /** @var bool $data Recebe array de erros quando houver erro */
    private array $data;

    /**
     * @return bool Retorna true quando executar o processo com sucesso e false quando houver erro
     */
    function getResult(): bool
    {
        return $this->result;
    }

    /** 
     * Validar a extensão da imagem.
     * Recebe a extensão da imagem que deve ser validada.
     * Retorna TRUE quando a extensão da imagem é válida.
     * Retorna FALSE quando a extensão da imagem é inválida.
     * 
     * @param string $mimeType Recebe o tipo da imagem que deve ser validada.
     * 
     * @return void
     */
    public function validateExtImg(string $mimeType): void
    {
        $this->mimeType = $mimeType;

        // Ampliado para suportar variações comuns enviadas por navegadores em desktops
        $allowedMimeTypes = [
            'image/jpeg',
            'image/pjpeg',
            'image/jpg',
            'image/jfif',
            'image/png',
            'image/x-png',
            'image/webp',
            'image/gif',
            'image/heic',
            'image/heif',
            'image/avif',
        ];

        if (in_array($this->mimeType, $allowedMimeTypes, true)) {
            $this->result = true;
        } else {
            // Criar a mensagem de erro
           
            $this->data['errors'][] = "Erro: Necessário selecionar uma imagem válida (PNG, JPG, JPEG, JFIF ou WEBP)!";
            
            // $_SESSION['msg'] = "<p class='alert-danger'>Erro: Necessário selecionar uma imagem JPEG ou PNG!</p>";
            $this->result = false;
        }
    }
}
