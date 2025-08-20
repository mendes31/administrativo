<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class AddAdmsPackagesPages extends AbstractSeed
{
    /**
     * Cadastra pacote na tabela `adms_packages_pages` se ainda não existirem.
     *
     * Este método é executado para popular a tabela `adms_packages_pages` com registros iniciais dos pacotes.
     * Primeiro, verifica se já existe pacote na tabela com base no name. 
     * Se o pacote não existir, os dados são inseridos na tabela.
     * 
     * @return void
     */
    public function run(): void
    {

        // Variável para receber os dados a serem inseridos
        $data = [];

        // Verifica se o nível de acesso com o name especificado já existe
        $existingRecord = $this->query('SELECT id FROM adms_packages_pages WHERE name=:name', ['name' => 'adms'])->fetch();

        // Se o usuário não existir, adiciona seus dados ao array $data
        if (!$existingRecord) {
            $data[] = [
                'name' => 'adms',
                'obs' => 'Pacote base do sistema administrativo.',
                'created_at' => date("Y-m-d H:i:s"),
            ];
        }

        // Obtém a tabela 'adms_packages_pages' para inserir os registros
        $adms_packages_pages = $this->table('adms_packages_pages');

        // Insere os registros na tabela
        $adms_packages_pages->insert($data)->save();

    }
}
