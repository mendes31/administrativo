<?php

declare(strict_types=1);

namespace App\adms\Models\Services\Imports;

use App\adms\Helpers\SstEquipamentoCodigoHelper;
use App\adms\Helpers\SstEquipamentoPeriodicidadeHelper;
use App\adms\Helpers\SstEquipamentoRecargaHelper;
use App\adms\Helpers\SstEquipamentoSiteHelper;
use App\adms\Models\Repository\SstEquipamentoRecargasRepository;
use App\adms\Models\Repository\SstEquipamentosRepository;
use App\adms\Models\Services\SstEquipamentoVistoriaGeneratorService;

final class SstEquipamentosImportProfile extends AbstractSstCatalogImportProfile
{
    public function key(): string
    {
        return 'sst_equipamentos';
    }

    public function label(): string
    {
        return 'SST — Equipamentos de segurança';
    }

    public function fields(): array
    {
        return [
            'id' => 'ID (chave)',
            'codigo' => 'Código gerado (chave para atualizar; único por site, ex. EXT00001)',
            'patrimonio' => 'Patrimônio (chave alternativa)',
            'tipo' => 'Tipo / grupo (código, nome, prefixo ou ID)',
            'localizacao' => 'Localização',
            'department' => 'Departamento (nome ou ID)',
            'empresa_contratante' => 'Empresa (site) * — Laboratório Tiaraju, Afra Pharma ou Afra Biotics',
            'responsavel' => 'Responsável (login, e-mail, CPF, nome ou ID)',
            'fabricante' => 'Fabricante',
            'modelo' => 'Tipo / agente (ex. Pó ABC)',
            'numero_serie' => 'Nº de série',
            'capacidade' => 'Capacidade (ex. 6 kg)',
            'data_fabricacao' => 'Data de fabricação',
            'data_recarga' => 'Data da última recarga',
            'data_proxima_recarga' => 'Próxima recarga',
            'periodicidade' => 'Periodicidade da vistoria (Mensal, Anual…)',
            'dia_previsto_vistoria' => 'Dia previsto da vistoria (1–28)',
            'vistoria_automatica' => 'Gerar vistorias automaticamente (Sim/Não)',
            'status' => 'Status (Ativo/Inativo/Baixado/Bloqueado)',
            'observacoes' => 'Observações',
        ];
    }

    public function keyFields(): array
    {
        return ['codigo', 'patrimonio', 'id'];
    }

    public function defaultKeyField(): string
    {
        return 'codigo';
    }

    public function sampleRow(): array
    {
        return [
            '', '', '', 'EXTINTOR', 'Almoxarifado — entrada', 'Suprimentos', 'Laboratório Tiaraju',
            '', 'ABC Equipamentos', 'Pó ABC', 'SN-001', '6 kg', '', '15/01/2026', '',
            'Mensal', '5', 'Sim', 'Ativo', '',
        ];
    }

    protected function resolveKeyRaw(array $mapped, string $keyField): string
    {
        foreach ([$keyField, 'codigo', 'patrimonio', 'numero_serie', 'id'] as $field) {
            $value = SstImportValues::v($mapped, $field);
            if ($value !== '') {
                return $value;
            }
        }
        $tipo = SstImportValues::v($mapped, 'tipo');
        $local = SstImportValues::v($mapped, 'localizacao');
        if ($tipo !== '' && $local !== '') {
            return $tipo . ' @ ' . $local;
        }

        return $tipo !== '' ? $tipo : $local;
    }

    protected function findExisting(array $mapped, string $keyField): ?array
    {
        $lookup = new SstImportLookup();
        $idRaw = SstImportValues::v($mapped, 'id');
        if ($idRaw !== '' && ctype_digit($idRaw)) {
            $byId = $lookup->byId('adms_sst_equipamentos', (int) $idRaw);
            if ($byId !== null) {
                return $byId;
            }
        }
        $codigo = SstImportValues::v($mapped, 'codigo') ?: ($keyField === 'codigo' ? SstImportValues::v($mapped, $keyField) : '');
        if ($codigo !== '') {
            $byCode = $this->findByCodigoAndSite($lookup, $codigo, $mapped);
            if ($byCode !== null) {
                return $byCode;
            }
        }
        $patrimonio = SstImportValues::v($mapped, 'patrimonio');
        if ($patrimonio !== '') {
            $row = $lookup->findLink('adms_sst_equipamentos', ['patrimonio' => $patrimonio]);
            if ($row !== null) {
                return $row;
            }
        }
        $serie = SstImportValues::v($mapped, 'numero_serie');
        if ($serie !== '') {
            return $lookup->findLink('adms_sst_equipamentos', ['numero_serie' => $serie]);
        }

        return null;
    }

    protected function buildCreatePayload(array $mapped): array
    {
        $tipo = $this->requireTipo($mapped);
        $deptId = $this->optionalDepartment($mapped);
        $responsavelId = $this->optionalResponsavel($mapped);
        $empresa = $this->requireEmpresa($mapped);
        $dataRecarga = SstImportValues::date(SstImportValues::v($mapped, 'data_recarga'));
        $dataProxima = SstImportValues::date(SstImportValues::v($mapped, 'data_proxima_recarga'));
        $controla = !empty($tipo['controla_recarga']);
        if (!$controla) {
            $dataRecarga = null;
            $dataProxima = null;
        } elseif ($dataRecarga !== null && $dataProxima === null) {
            $meses = (int) ($tipo['validade_recarga_meses'] ?? 12);
            $dataProxima = SstEquipamentoRecargaHelper::calcularProxima($dataRecarga, $meses > 0 ? $meses : 12);
        }

        return [
            'patrimonio' => SstImportValues::v($mapped, 'patrimonio') ?: null,
            'adms_sst_equipamento_tipo_id' => (int) $tipo['id'],
            'adms_department_id' => $deptId,
            'empresa_contratante' => $empresa,
            'localizacao' => SstImportValues::v($mapped, 'localizacao') ?: null,
            'fabricante' => SstImportValues::v($mapped, 'fabricante') ?: null,
            'modelo' => SstImportValues::v($mapped, 'modelo') ?: null,
            'numero_serie' => SstImportValues::v($mapped, 'numero_serie') ?: null,
            'capacidade' => SstImportValues::v($mapped, 'capacidade') ?: null,
            'data_fabricacao' => SstImportValues::date(SstImportValues::v($mapped, 'data_fabricacao')),
            'data_recarga' => $dataRecarga,
            'data_proxima_recarga' => $dataProxima,
            'periodicidade_meses' => SstImportValues::periodicidadeMeses(SstImportValues::v($mapped, 'periodicidade')) ?? 1,
            'data_referencia_inspecao' => null,
            'dia_previsto_vistoria' => $this->diaPrevisto($mapped),
            'vistoria_automatica' => SstImportValues::bool01(SstImportValues::v($mapped, 'vistoria_automatica')) ?? 1,
            'responsavel_adms_user_id' => $responsavelId,
            'status' => SstImportValues::equipamentoStatus(SstImportValues::v($mapped, 'status')) ?? 'Ativo',
            'observacoes' => SstImportValues::v($mapped, 'observacoes') ?: null,
            '_tipo' => $tipo,
        ];
    }

    protected function buildUpdatePayload(array $mapped, array $existing, string $emptyPolicy): array
    {
        $tipo = SstImportValues::v($mapped, 'tipo') !== ''
            ? $this->requireTipo($mapped)
            : ['id' => $existing['adms_sst_equipamento_tipo_id'] ?? 0, 'controla_recarga' => $existing['controla_recarga'] ?? 0, 'validade_recarga_meses' => $existing['validade_recarga_meses'] ?? 12];
        $mappedForCreate = $mapped;
        if (SstImportValues::v($mapped, 'empresa_contratante') === '' && !empty($existing['empresa_contratante'])) {
            $mappedForCreate['empresa_contratante'] = (string) $existing['empresa_contratante'];
        }
        $payload = $this->buildCreatePayload(array_merge($mappedForCreate, [
            'tipo' => SstImportValues::v($mapped, 'tipo') !== '' ? SstImportValues::v($mapped, 'tipo') : (string) ($tipo['id'] ?? ''),
        ]));
        unset($payload['_tipo']);
        if ($emptyPolicy === 'skip') {
            if (SstImportValues::v($mapped, 'department') === '') {
                $payload['adms_department_id'] = $existing['adms_department_id'] ?? null;
            }
            if (SstImportValues::v($mapped, 'responsavel') === '') {
                $payload['responsavel_adms_user_id'] = $existing['responsavel_adms_user_id'] ?? null;
            }
            if (SstImportValues::v($mapped, 'empresa_contratante') === '') {
                $payload['empresa_contratante'] = $existing['empresa_contratante'] ?? null;
            }
            if (SstImportValues::v($mapped, 'periodicidade') === '') {
                $payload['periodicidade_meses'] = $existing['periodicidade_meses'] ?? 1;
            }
            if (SstImportValues::v($mapped, 'dia_previsto_vistoria') === '') {
                $payload['dia_previsto_vistoria'] = $existing['dia_previsto_vistoria'] ?? null;
            }
            if (SstImportValues::v($mapped, 'vistoria_automatica') === '') {
                $payload['vistoria_automatica'] = $existing['vistoria_automatica'] ?? 1;
            }
        }
        $payload['codigo'] = (string) ($existing['codigo'] ?? '');

        return SstImportValues::mergeSkipEmpty($payload, $existing, $emptyPolicy, [
            'patrimonio', 'localizacao', 'fabricante', 'modelo', 'numero_serie', 'capacidade',
            'data_fabricacao', 'data_recarga', 'data_proxima_recarga', 'status', 'observacoes',
        ]);
    }

    protected function createRow(array $payload): int|false
    {
        $tipo = is_array($payload['_tipo'] ?? null) ? $payload['_tipo'] : [];
        unset($payload['_tipo']);
        $id = (new SstEquipamentosRepository())->create($payload);
        if (!$id) {
            return false;
        }
        if (!empty($tipo['controla_recarga']) && !empty($payload['data_recarga'])) {
            (new SstEquipamentoRecargasRepository())->register([
                'adms_sst_equipamento_id' => (int) $id,
                'tipo_evento' => 'Recarga',
                'data_recarga' => (string) $payload['data_recarga'],
                'data_proxima_recarga' => $payload['data_proxima_recarga'] ?? null,
                'validade_meses' => (int) ($tipo['validade_recarga_meses'] ?? 12),
                'observacao' => 'Registro inicial na importação do equipamento.',
            ]);
        }
        (new SstEquipamentoVistoriaGeneratorService())->tryGenerateOnEquipamentoCreate((int) $id);

        return $id;
    }

    protected function updateRow(int $id, array $payload): bool
    {
        unset($payload['_tipo']);

        return (new SstEquipamentosRepository())->update($id, $payload);
    }

    /** @param array<string, string> $mapped */
    private function requireTipo(array $mapped): array
    {
        $raw = SstImportValues::v($mapped, 'tipo');
        if ($raw === '') {
            throw new \RuntimeException('Para criar equipamento, informe o tipo (código, nome, prefixo ou ID).');
        }
        $lookup = new SstImportLookup();
        $tipo = $lookup->catalog('adms_sst_equipamento_tipos', $raw, true);
        if ($tipo === null) {
            $prefixo = SstEquipamentoCodigoHelper::normalizePrefixo($raw);
            if (SstEquipamentoCodigoHelper::isValidPrefixo($prefixo)) {
                $tipo = $lookup->findLink('adms_sst_equipamento_tipos', ['prefixo' => $prefixo]);
            }
        }
        if ($tipo === null) {
            throw new \RuntimeException('Tipo de equipamento não encontrado: ' . $raw);
        }
        $prefixo = SstEquipamentoCodigoHelper::normalizePrefixo((string) ($tipo['prefixo'] ?? ''));
        if (!SstEquipamentoCodigoHelper::isValidPrefixo($prefixo)) {
            throw new \RuntimeException('Tipo sem prefixo válido (3 caracteres). Cadastre o prefixo no tipo.');
        }

        return $tipo;
    }

    /** @param array<string, string> $mapped */
    private function optionalDepartment(array $mapped): ?int
    {
        $raw = SstImportValues::v($mapped, 'department');
        if ($raw === '') {
            return null;
        }
        $id = (new SstImportLookup())->department($raw);
        if ($id === null) {
            throw new \RuntimeException('Departamento não encontrado: ' . $raw);
        }

        return $id;
    }

    /** @param array<string, string> $mapped */
    private function optionalResponsavel(array $mapped): ?int
    {
        $raw = SstImportValues::v($mapped, 'responsavel');
        if ($raw === '') {
            return null;
        }
        $id = (new SstImportLookup())->user($raw);
        if ($id === null) {
            throw new \RuntimeException('Responsável não encontrado: ' . $raw);
        }

        return $id;
    }

    /**
     * @param array<string, string> $mapped
     * @return array<string, mixed>|null
     */
    private function findByCodigoAndSite(SstImportLookup $lookup, string $codigo, array $mapped): ?array
    {
        $rows = $lookup->byCodigoAll('adms_sst_equipamentos', $codigo);
        if ($rows === []) {
            return null;
        }
        if (count($rows) === 1) {
            return $rows[0];
        }
        $site = $this->optionalEmpresa($mapped);
        if ($site === null) {
            throw new \RuntimeException(
                'O código ' . strtoupper(trim($codigo)) . ' existe em mais de um site. Informe a Empresa (site).'
            );
        }
        $matched = null;
        foreach ($rows as $row) {
            if (SstEquipamentoSiteHelper::normalize($row['empresa_contratante'] ?? null) === $site) {
                if ($matched !== null) {
                    throw new \RuntimeException('Código duplicado no mesmo site: ' . strtoupper(trim($codigo)));
                }
                $matched = $row;
            }
        }

        return $matched;
    }

    /** @param array<string, string> $mapped */
    private function requireEmpresa(array $mapped): string
    {
        $slug = $this->optionalEmpresa($mapped);
        if ($slug === null) {
            throw new \RuntimeException(
                'Para criar equipamento, informe a Empresa (site): Laboratório Tiaraju, Afra Pharma ou Afra Biotics.'
            );
        }

        return $slug;
    }

    /** @param array<string, string> $mapped */
    private function optionalEmpresa(array $mapped): ?string
    {
        $raw = SstImportValues::v($mapped, 'empresa_contratante');
        if ($raw === '') {
            return null;
        }
        $slug = SstEquipamentoSiteHelper::normalize($raw);
        if ($slug === null) {
            throw new \RuntimeException('Empresa (site) inválida: ' . $raw . '. Use Laboratório Tiaraju, Afra Pharma ou Afra Biotics.');
        }

        return $slug;
    }

    /** @param array<string, string> $mapped */
    private function diaPrevisto(array $mapped): ?int
    {
        $raw = SstImportValues::v($mapped, 'dia_previsto_vistoria');
        if ($raw === '') {
            return null;
        }
        if (!ctype_digit($raw)) {
            throw new \RuntimeException('Dia previsto da vistoria deve ser um número de 1 a 28.');
        }

        return SstEquipamentoPeriodicidadeHelper::clampDay((int) $raw);
    }
}
