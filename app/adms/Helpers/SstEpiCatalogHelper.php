<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/** Normalização e validação do cadastro genérico de EPI. */
final class SstEpiCatalogHelper
{
    /**
     * @return array<string, mixed>
     */
    public static function parseFormData(array $post): array
    {
        $nome = trim((string) ($post['nome'] ?? ''));
        $descricao = trim((string) ($post['descricao'] ?? ''));
        $categoria = trim((string) ($post['categoria'] ?? ''));
        $status = (string) ($post['status'] ?? 'Ativo');
        $estoqueMin = trim((string) ($post['estoque_minimo'] ?? ''));
        $vidaUtil = trim((string) ($post['periodicidade_troca_dias'] ?? ''));

        return [
            'nome' => $nome !== '' ? $nome : null,
            'descricao' => $descricao !== '' ? $descricao : null,
            'categoria' => $categoria !== '' ? $categoria : null,
            'estoque_minimo' => $estoqueMin !== '' ? (int) $estoqueMin : null,
            'periodicidade_troca_dias' => $vidaUtil !== '' ? (int) $vidaUtil : null,
            'status' => in_array($status, ['Ativo', 'Inativo'], true) ? $status : 'Ativo',
        ];
    }

    public static function validate(array $data): ?string
    {
        if (empty($data['nome'])) {
            return 'Informe o nome do EPI.';
        }

        if (empty($data['categoria']) || !SstEpiCategoriaHelper::isValid((string) $data['categoria'])) {
            return 'Selecione a categoria de proteção do EPI.';
        }

        return null;
    }
}
