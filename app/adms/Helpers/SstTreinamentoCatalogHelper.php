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
        $tipo = trim((string) ($post['tipo'] ?? 'Ambos'));
        $modalidade = trim((string) ($post['modalidade'] ?? 'Presencial'));
        $status = (string) ($post['status'] ?? 'Ativo');

        $cargaHoraria = trim((string) ($post['carga_horaria_minutos'] ?? ''));
        $validadeMeses = trim((string) ($post['validade_meses'] ?? ''));
        $prazoPrimeiro = trim((string) ($post['prazo_primeiro_dias'] ?? ''));

        $tiposValidos = ['Inicial', 'Reciclagem', 'Ambos'];
        $modalidadesValidas = ['Presencial', 'EAD', 'Hibrido'];

        return [
            'codigo' => $codigo !== '' ? strtoupper($codigo) : null,
            'nome' => $nome !== '' ? $nome : null,
            'descricao' => $descricao !== '' ? $descricao : null,
            'nr_referencia' => $nrReferencia !== '' ? $nrReferencia : null,
            'tipo' => in_array($tipo, $tiposValidos, true) ? $tipo : 'Ambos',
            'modalidade' => in_array($modalidade, $modalidadesValidas, true) ? $modalidade : 'Presencial',
            'carga_horaria_minutos' => $cargaHoraria !== '' ? (int) $cargaHoraria : null,
            'validade_meses' => $validadeMeses !== '' ? (int) $validadeMeses : null,
            'prazo_primeiro_dias' => $prazoPrimeiro !== '' ? (int) $prazoPrimeiro : null,
            'status' => in_array($status, ['Ativo', 'Inativo'], true) ? $status : 'Ativo',
        ];
    }

    public static function validate(array $data, SstTreinamentosRepository $repo, ?int $excludeId = null): ?string
    {
        if (empty($data['nome'])) {
            return 'Informe o nome do treinamento.';
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

        if (!empty($data['validade_meses']) && (int) $data['validade_meses'] < 1) {
            return 'Validade em meses deve ser maior que zero.';
        }

        return null;
    }
}
