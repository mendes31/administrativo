<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Endereço residencial e e-mail pessoal no cadastro de usuários (relação 1:1).
 */
final class AddAddressAndEmailPessoalToAdmsUsers extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_users')) {
            return;
        }

        $table = $this->table('adms_users');
        $after = $table->hasColumn('pais_residencia_iso') ? 'pais_residencia_iso' : null;

        if (!$table->hasColumn('email_pessoal')) {
            $opts = [
                'limit' => 255,
                'null' => true,
                'default' => null,
                'comment' => 'E-mail pessoal (não corporativo)',
            ];
            if ($after !== null) {
                $opts['after'] = $after;
            }
            $table->addColumn('email_pessoal', 'string', $opts);
            $after = 'email_pessoal';
        }

        if (!$table->hasColumn('endereco')) {
            $opts = [
                'limit' => 255,
                'null' => true,
                'default' => null,
                'comment' => 'Logradouro (rua, avenida, etc.)',
            ];
            if ($after !== null) {
                $opts['after'] = $after;
            }
            $table->addColumn('endereco', 'string', $opts);
            $after = 'endereco';
        }

        if (!$table->hasColumn('numero_endereco')) {
            $opts = [
                'limit' => 20,
                'null' => true,
                'default' => null,
                'comment' => 'Número do endereço',
            ];
            if ($after !== null) {
                $opts['after'] = $after;
            }
            $table->addColumn('numero_endereco', 'string', $opts);
            $after = 'numero_endereco';
        }

        if (!$table->hasColumn('complemento_endereco')) {
            $opts = [
                'limit' => 80,
                'null' => true,
                'default' => null,
                'comment' => 'Complemento (apto, bloco, etc.)',
            ];
            if ($after !== null) {
                $opts['after'] = $after;
            }
            $table->addColumn('complemento_endereco', 'string', $opts);
            $after = 'complemento_endereco';
        }

        if (!$table->hasColumn('bairro')) {
            $opts = [
                'limit' => 120,
                'null' => true,
                'default' => null,
            ];
            if ($after !== null) {
                $opts['after'] = $after;
            }
            $table->addColumn('bairro', 'string', $opts);
            $after = 'bairro';
        }

        if (!$table->hasColumn('cep')) {
            $opts = [
                'limit' => 10,
                'null' => true,
                'default' => null,
                'comment' => 'CEP (formatado ou só dígitos)',
            ];
            if ($after !== null) {
                $opts['after'] = $after;
            }
            $table->addColumn('cep', 'string', $opts);
            $after = 'cep';
        }

        if (!$table->hasColumn('municipio')) {
            $opts = [
                'limit' => 120,
                'null' => true,
                'default' => null,
            ];
            if ($after !== null) {
                $opts['after'] = $after;
            }
            $table->addColumn('municipio', 'string', $opts);
            $after = 'municipio';
        }

        if (!$table->hasColumn('uf')) {
            $opts = [
                'limit' => 2,
                'null' => true,
                'default' => null,
                'comment' => 'UF (sigla do estado)',
            ];
            if ($after !== null) {
                $opts['after'] = $after;
            }
            $table->addColumn('uf', 'char', $opts);
        }

        $table->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_users')) {
            return;
        }

        $table = $this->table('adms_users');
        foreach (['uf', 'municipio', 'cep', 'bairro', 'complemento_endereco', 'numero_endereco', 'endereco', 'email_pessoal'] as $col) {
            if ($table->hasColumn($col)) {
                $table->removeColumn($col);
            }
        }
        $table->update();
    }
}
