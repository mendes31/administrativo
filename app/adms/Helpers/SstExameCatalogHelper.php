<?php

declare(strict_types=1);

namespace App\adms\Helpers;

use App\adms\Models\Repository\SstExamesRepository;

/** Normalização e validação do cadastro de exames complementares. */
final class SstExameCatalogHelper
{
    /**
     * @return array<string, mixed>
     */
    public static function parseFormData(array $post): array
    {
        $codigo = trim((string) ($post['codigo'] ?? ''));
        $nome = trim((string) ($post['nome'] ?? ''));
        $descricao = trim((string) ($post['descricao'] ?? ''));
        $tipo = trim((string) ($post['tipo'] ?? ''));
        $status = (string) ($post['status'] ?? 'Ativo');

        $periodicidade = trim((string) ($post['periodicidade_meses'] ?? ''));
        $validadeMeses = trim((string) ($post['validade_meses'] ?? ''));

        $possuiValidade = !empty($post['possui_validade']);
        $exigeResultado = array_key_exists('exige_resultado', $post)
            ? !empty($post['exige_resultado'])
            : true;

        $resultadosRaw = $post['resultados_permitidos'] ?? [];
        $resultados = is_array($resultadosRaw)
            ? SstExameResultadoHelper::filterValid(array_map('strval', $resultadosRaw))
            : [];

        return [
            'codigo' => $codigo !== '' ? strtoupper($codigo) : null,
            'nome' => $nome !== '' ? $nome : null,
            'descricao' => $descricao !== '' ? $descricao : null,
            'tipo' => $tipo !== '' ? $tipo : null,
            'periodicidade_meses' => $periodicidade !== '' ? (int) $periodicidade : null,
            'possui_validade' => $possuiValidade,
            'validade_meses' => $possuiValidade && $validadeMeses !== '' ? (int) $validadeMeses : null,
            'exige_resultado' => $exigeResultado,
            'resultados_permitidos' => $exigeResultado
                ? SstExameResultadoHelper::encode($resultados)
                : null,
            'status' => in_array($status, ['Ativo', 'Inativo'], true) ? $status : 'Ativo',
        ];
    }

    public static function validate(array $data, SstExamesRepository $repo, ?int $excludeId = null): ?string
    {
        if (empty($data['nome'])) {
            return 'Informe o nome do exame.';
        }

        if (!empty($data['codigo']) && $repo->existsCodigo((string) $data['codigo'], $excludeId)) {
            return 'Já existe um exame com este código interno.';
        }

        if (!empty($data['tipo']) && !SstExameTipoHelper::isValid((string) $data['tipo'])) {
            return 'Tipo de exame inválido.';
        }

        if (!empty($data['possui_validade']) && empty($data['validade_meses'])) {
            return 'Informe a validade em meses quando o exame possuir validade.';
        }

        if (!empty($data['exige_resultado']) && empty($data['resultados_permitidos'])) {
            return 'Selecione ao menos um resultado esperado ou desmarque "Exige resultado".';
        }

        return null;
    }
}
