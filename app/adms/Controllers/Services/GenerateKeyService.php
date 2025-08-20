<?php

namespace App\adms\Controllers\Services;

class GenerateKeyService
{
    public static function generateKey(): array 
    {
        // Definindo os caracteres possíveis (letras e numeros)
        $chars = 'abcdefghijklmnopqrstuvwxz0123456789';

        // Embaralhando os carateres
        $shuffle = str_shuffle($chars);

        // Extraindo a chave de 12 caracteres
        $key = substr($shuffle, 0, 12);

        // Criptografando a chave
        $encryptedkey = password_hash($key, PASSWORD_DEFAULT);

        // Retornar a chave em texto claro e criptografado
        return ['key' => $key, 'encryptedkey' => $encryptedkey];
    }
}