<?php


namespace App\adms\Controllers\Services\Validation;

class ValidationEmptyField
{
    private array|null $data;
    private bool $result;

    function getResult(){
        return $this->result;
    }

    public function valField(array|null $data = null)
    {
        $this->data = $data;
        $this->data = array_map('strip_tags', $this->data);
        $this->data = array_map('trim', $this->data);

        if(in_array('', $this->data)){
            $_SESSION['msg'] ="<p class='alert-danger'>Erro: Necessário preencher todos os campos</p>";
            $this->result = false;
        }else{
            $this->result = true;
        }
    }

}