<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class AddAdmsPasswordPolicy extends AbstractSeed
{
    /**
     * Popula a tabela adms_password_policy com um registro padrão de segurança Baixo, se não existir.
     *
     * @return void
     */
    public function run(): void
    {
        // Verifica se já existe registro
        $existing = $this->query('SELECT id FROM adms_password_policy LIMIT 1')->fetch();
        if (!$existing) {
            $data = [
                'vencimento_dias' => -1,
                'comprimento_minimo' => 4,
                'min_maiusculas' => 0,
                'min_minusculas' => 0,
                'min_digitos' => 0,
                'min_nao_alfanumericos' => 0,
                'historico_senhas' => 0,
                'tentativas_bloqueio' => -1,
                'nivel_seguranca' => 'Baixo',
                'exemplo_senha' => 'Abcde12@',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $this->table('adms_password_policy')->insert($data)->save();
        }
    }
} 