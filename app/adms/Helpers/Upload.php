<?php

namespace App\adms\Helpers;

/**
 * Classe genérica para upload
 *
 * @author Celke
 */
class Upload
{
    /** @var string $directory Recebe o caminho do direitorio*/
    private string $directory;
    /** @var string $tmpName Recebe o nome temporario*/
    private string $tmpName;
    /** @var string $name Recebe o nome da imagem*/
    private string $name;


    /** @var bool $result Recebe true quando executar o processo com sucesso e false quando houver erro */
    private bool $result;

    /** @var array $data Recebe array com msg de erro quando houver erro */
    private array $data = [];



    /**
     * @return bool Retorna true quando executar o processo com sucesso e false quando houver erro
     */
    function getResult(): bool
    {
        return $this->result;
    }

    /**
     * Metodo recebe o caminho do diretorio, o nome temporario e o nome da imagem que será salvo
     * Chama o metodo valDirectory para validar o caminho do diretorio e na sequencia chama o metodo uploadFile para fazer o upload
     * Retorna FALSE caso tenha algum erro
     * @param string $directory
     * @param string $tmpName
     * @param string $name
     * @return void
     */
    public function upload(string $directory, string $tmpName, string $name): bool
    {
        $this->directory = $directory;
        $this->tmpName = $tmpName;
        $this->name = $name;


        if ($this->valDirectory()) {
            $this->uploadFile();
        } else {
            $this->result = false;
        }
        return true;
    }

    /**
     * Metodo verifica se o diretorio é valido e se ele existe, se não existir, o diretorio é criado
     * 
     * @return boolean
     */
    private function valDirectory(): bool
    {
        // Criar o array que deve receber as mensagens de erro 
        $errors = [];
        
        if ((!file_exists($this->directory)) and (!is_dir($this->directory))) {

            mkdir($this->directory, 0755);
            if ((!file_exists($this->directory)) and (!is_dir($this->directory))) {
                // Criar a mensagem de erro
                $this->data['errors'][] = "Erro: Upload não realizado com sucesso. Tente novamente!";
                // $_SESSION['msg'] = "<p class='alert-danger'>Erro: Upload não realizado com sucesso. Tente novamente!</p>";
                return false;
            } else {
                return true;
            }
        } else {
            return true;
        }
    }

    /**
     * Metodo faz o upload do arquivo no servidor
     * Retorna FALSE se houver algum erro
     * @return void
     */
    private function uploadFile()
    {
        if (move_uploaded_file($this->tmpName, $this->directory . $this->name)) {
            $this->result = true;
        } else {
            $_SESSION['msg'] = "<p class='alert-danger'>Erro: Upload não realizado com sucesso. Tente novamente!</p>";
            $this->result = false;
        }
    }
}
