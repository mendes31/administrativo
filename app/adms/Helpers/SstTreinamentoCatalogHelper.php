<?php

declare(strict_types=1);

namespace App\adms\Helpers;

use App\adms\Models\Repository\SstTreinamentosRepository;

/** Normalização e validação do cadastro de treinamentos SST. */
final class SstTreinamentoCatalogHelper
{
    /**
     * @return array<string, mixed>
     */
    public static function parseFormData(array $post): array
    {
        $codigo = trim((string) ($post['codigo'] ?? ''));
        $nome = trim((string) ($post['nome'] ?? ''));
        $descricao = trim((string) ($post['descricao'] ?? ''));
        $nrReferencia = trim((string) ($post['nr_referencia'] ?? ''));
        $modalidade = trim((string) ($post['modalidade'] ?? 'Presencial'));
        $status = (string) ($post['status'] ?? 'Ativo');

        $cargaHoras = str_replace(',', '.', trim((string) ($post['carga_horaria_horas'] ?? '')));
        $validadeMeses = trim((string) ($post['validade_meses'] ?? ''));
        $prazoPrimeiro = trim((string) ($post['prazo_primeiro_dias'] ?? ''));

        $aplicacaoPost = is_array($post['aplicacao'] ?? null) ? $post['aplicacao'] : [];
        $aplicacaoMomentos = SstTreinamentoAplicacaoHelper::normalizeFromPost($aplicacaoPost);

        $modalidadesValidas = ['Presencial', 'EAD', 'Hibrido'];

        $cargaMinutos = null;
        if ($cargaHoras !== '') {
            $cargaMinutos = max(1, (int) round((float) $cargaHoras * 60));
        }

        $validade = null;
        if ($validadeMeses !== '') {
            $validade = max(0, (int) $validadeMeses);
        }

        $prazo = null;
        if ($prazoPrimeiro !== '') {
            $prazo = max(0, (int) $prazoPrimeiro);
        }

        return [
            'codigo' => $codigo !== '' ? strtoupper($codigo) : null,
            'nome' => $nome !== '' ? $nome : null,
            'descricao' => $descricao !== '' ? $descricao : null,
            'nr_referencia' => $nrReferencia !== '' ? $nrReferencia : null,
            'aplicacao_momentos' => $aplicacaoMomentos,
            'tipo' => SstTreinamentoAplicacaoHelper::toLegacyTipo($aplicacaoMomentos),
            'modalidade' => in_array($modalidade, $modalidadesValidas, true) ? $modalidade : 'Presencial',
            'carga_horaria_minutos' => $cargaMinutos,
            'validade_meses' => $validade,
            'prazo_primeiro_dias' => $prazo,
            'status' => in_array($status, ['Ativo', 'Inativo'], true) ? $status : 'Ativo',
        ];
    }

    public static function validate(array $data, SstTreinamentosRepository $repo, ?int $excludeId = null): ?string
    {
        if (empty($data['nome'])) {
            return 'Informe o nome do treinamento.';
        }

        if (empty($data['aplicacao_momentos'])) {
            return 'Selecione ao menos um momento de aplicação (quando exigir).';
        }

        if (!empty($data['codigo']) && $repo->existsCodigo((string) $data['codigo'], $excludeId)) {
            return 'Já existe um treinamento com este código interno.';
        }

        if (!empty($data['nr_referencia']) && !SstTreinamentoNrHelper::isValid((string) $data['nr_referencia'])) {
            return 'NR de referência inválida.';
        }

        if (!empty($data['carga_horaria_minutos']) && (int) $data['carga_horaria_minutos'] < 1) {
            return 'Carga horária deve ser maior que zero.';
        }

        if ($data['validade_meses'] !== null && (int) $data['validade_meses'] < 0) {
            return 'Validade de reciclagem inválida.';
        }

        if ($data['prazo_primeiro_dias'] !== null && (int) $data['prazo_primeiro_dias'] < 0) {
            return 'Prazo para 1º treinamento inválido.';
        }

        return null;
    }
}
