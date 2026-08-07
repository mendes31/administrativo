<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/**
 * Normalização e rótulos para campos demográficos do cadastro de utilizador.
 */
final class UserFormHelper
{
    /** @var list<string> */
    public const ESTADO_CIVIL_SLUGS = [
        'solteiro',
        'casado',
        'uniao_estavel',
        'divorciado',
        'viuvo',
        'separado',
        'outro',
    ];

    /** @var list<string> */
    public const ESCOLARIDADE_SLUGS = [
        'fundamental_incompleto',
        'fundamental_completo',
        'medio_incompleto',
        'medio_completo',
        'tecnico',
        'superior_incompleto',
        'superior_completo',
        'pos_graduacao',
        'mestrado',
        'doutorado',
    ];

    /** Categorias de cor/raça (padrão IBGE / eSocial). @var list<string> */
    public const RACA_SLUGS = [
        'branca',
        'preta',
        'parda',
        'amarela',
        'indigena',
        'nao_informado',
    ];

    /** @var list<string> */
    public const EMPRESA_CONTRATANTE_SLUGS = [
        'tiaraju_farma',
        'lab_tiaraju_matriz',
        'lab_tiaraju_filial',
    ];

    public static function normalizeSexo(mixed $value): ?string
    {
        $v = strtoupper(trim((string) $value));

        return in_array($v, ['M', 'F', 'O'], true) ? $v : null;
    }

    public static function normalizeFilhos(mixed $value): ?string
    {
        $v = strtoupper(trim((string) $value));

        return in_array($v, ['S', 'N'], true) ? $v : null;
    }

    public static function sexoLabel(?string $code): string
    {
        return match ($code) {
            'M' => 'Masculino',
            'F' => 'Feminino',
            'O' => 'Outros',
            default => 'Não informado',
        };
    }

    public static function filhosLabel(?string $code): string
    {
        return match ($code) {
            'S' => 'Sim',
            'N' => 'Não',
            default => 'Não informado',
        };
    }

    /** @return 'regrettable'|'non_regrettable'|'nao_classificado'|null */
    public static function normalizeTipoImpactoDesligamento(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $v = strtolower(trim((string) $value));

        return in_array($v, ['regrettable', 'non_regrettable', 'nao_classificado'], true) ? $v : null;
    }

    public static function tipoImpactoDesligamentoLabel(?string $code): string
    {
        return match ($code) {
            'regrettable' => 'Regrettable (desejável reter)',
            'non_regrettable' => 'Non-regrettable',
            'nao_classificado' => 'Não classificado',
            default => 'Não informado',
        };
    }

    public static function normalizeEstadoCivil(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $v = strtolower(trim((string) $value));

        return in_array($v, self::ESTADO_CIVIL_SLUGS, true) ? $v : null;
    }

    public static function estadoCivilLabel(?string $code): string
    {
        return match ($code) {
            'solteiro' => 'Solteiro(a)',
            'casado' => 'Casado(a)',
            'uniao_estavel' => 'União estável',
            'divorciado' => 'Divorciado(a)',
            'viuvo' => 'Viúvo(a)',
            'separado' => 'Separado(a)',
            'outro' => 'Outro',
            default => 'Estado civil não informado',
        };
    }

    /** @return array<string, string> slug => rótulo para selects e gráficos */
    public static function estadoCivilOptions(): array
    {
        $out = [];
        foreach (self::ESTADO_CIVIL_SLUGS as $slug) {
            $out[$slug] = self::estadoCivilLabel($slug);
        }

        return $out;
    }

    public static function normalizeEscolaridade(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $v = strtolower(trim((string) $value));

        return in_array($v, self::ESCOLARIDADE_SLUGS, true) ? $v : null;
    }

    /** Aceita slug ou rótulo exibido no formulário (útil em CSV). */
    public static function resolveEscolaridadeSlug(mixed $value): ?string
    {
        $fromSlug = self::normalizeEscolaridade($value);
        if ($fromSlug !== null) {
            return $fromSlug;
        }
        if ($value === null || trim((string) $value) === '') {
            return null;
        }
        $needle = mb_strtolower(trim((string) $value));
        foreach (self::ESCOLARIDADE_SLUGS as $slug) {
            if ($needle === mb_strtolower(self::escolaridadeLabel($slug))) {
                return $slug;
            }
        }

        return null;
    }

    public static function escolaridadeLabel(?string $code): string
    {
        return match ($code) {
            'fundamental_incompleto' => 'Ensino fundamental incompleto',
            'fundamental_completo' => 'Ensino fundamental completo',
            'medio_incompleto' => 'Ensino médio incompleto',
            'medio_completo' => 'Ensino médio completo',
            'tecnico' => 'Curso técnico',
            'superior_incompleto' => 'Ensino superior incompleto',
            'superior_completo' => 'Ensino superior completo',
            'pos_graduacao' => 'Pós-graduação',
            'mestrado' => 'Mestrado',
            'doutorado' => 'Doutorado',
            default => 'Escolaridade não informada',
        };
    }

    /** @return array<string, string> slug => rótulo */
    public static function escolaridadeOptions(): array
    {
        $out = [];
        foreach (self::ESCOLARIDADE_SLUGS as $slug) {
            $out[$slug] = self::escolaridadeLabel($slug);
        }

        return $out;
    }

    public static function normalizePaisResidenciaIso(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $v = strtoupper(trim((string) $value));
        if (strlen($v) !== 2 || !ctype_alpha($v)) {
            return null;
        }
        $countries = CountryHelper::getCountries();

        return isset($countries[$v]) ? $v : null;
    }

    public static function paisResidenciaLabel(?string $iso): string
    {
        if ($iso === null || $iso === '') {
            return 'País não informado';
        }
        $countries = CountryHelper::getCountries();
        if (isset($countries[$iso]['name'])) {
            return (string) $countries[$iso]['name'];
        }

        return 'País (' . $iso . ')';
    }

    public static function normalizeRaca(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $v = strtolower(trim((string) $value));

        return in_array($v, self::RACA_SLUGS, true) ? $v : null;
    }

    /** Aceita slug ou rótulo (ex.: Branca, Indígena) — útil em CSV. */
    public static function resolveRacaSlug(mixed $value): ?string
    {
        $fromSlug = self::normalizeRaca($value);
        if ($fromSlug !== null) {
            return $fromSlug;
        }
        if ($value === null || trim((string) $value) === '') {
            return null;
        }
        $needle = mb_strtolower(trim((string) $value));
        $aliases = [
            'indígena' => 'indigena',
            'indigena' => 'indigena',
            'não informado' => 'nao_informado',
            'nao informado' => 'nao_informado',
        ];
        if (isset($aliases[$needle])) {
            return $aliases[$needle];
        }
        foreach (self::RACA_SLUGS as $slug) {
            if ($needle === mb_strtolower(self::racaLabel($slug))) {
                return $slug;
            }
        }

        return null;
    }

    public static function racaLabel(?string $code): string
    {
        return match ($code) {
            'branca' => 'Branca',
            'preta' => 'Preta',
            'parda' => 'Parda',
            'amarela' => 'Amarela',
            'indigena' => 'Indígena',
            'nao_informado' => 'Não informado',
            default => 'Raça/cor não informada',
        };
    }

    /** @return array<string, string> */
    public static function racaOptions(): array
    {
        $out = [];
        foreach (self::RACA_SLUGS as $slug) {
            $out[$slug] = self::racaLabel($slug);
        }

        return $out;
    }

    public static function normalizeEmpresaContratante(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $v = strtolower(trim((string) $value));

        return in_array($v, self::EMPRESA_CONTRATANTE_SLUGS, true) ? $v : null;
    }

    /** @return array<string, string> */
    public static function empresaContratanteOptions(): array
    {
        $fromBranches = self::empresaContratanteOptionsFromBranches();
        $out = [];
        foreach (self::EMPRESA_CONTRATANTE_SLUGS as $slug) {
            $out[$slug] = $fromBranches[$slug] ?? self::empresaContratanteLabel($slug);
        }

        return $out;
    }

    /**
     * Lê rótulos (nome fantasia) de adms_branches.code = slug.
     *
     * @return array<string, string>
     */
    public static function empresaContratanteOptionsFromBranches(): array
    {
        try {
            $repo = new \App\adms\Models\Repository\BranchesRepository();

            return $repo->getEmpresaContratanteOptionsFromBranches();
        } catch (\Throwable) {
            return [];
        }
    }

    /** Resolve FK adms_branches.id a partir do slug de empresa_contratante. */
    public static function resolveUserBranchIdFromEmpresaSlug(?string $slug): ?int
    {
        $slug = self::normalizeEmpresaContratante($slug);
        if ($slug === null) {
            return null;
        }
        try {
            $repo = new \App\adms\Models\Repository\BranchesRepository();

            return $repo->getBranchIdByCode($slug);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Dual-write: garante slug + user_branch_id coerentes no payload do formulário.
     *
     * @param array<string, mixed> $form
     * @return array<string, mixed>
     */
    public static function applyEmpresaContratanteToForm(array $form, mixed $postedEmpresa): array
    {
        $slug = self::normalizeEmpresaContratante($postedEmpresa);
        $form['empresa_contratante'] = $slug;
        $form['user_branch_id'] = $slug !== null ? self::resolveUserBranchIdFromEmpresaSlug($slug) : null;

        return $form;
    }

    /**
     * Empresa efetiva do usuário: slug, ou code da filial via user_branch_id.
     *
     * @param array<string, mixed> $user
     */
    public static function resolveEmpresaSlugFromUser(array $user): ?string
    {
        $fromSlug = self::resolveEmpresaContratanteSlug($user['empresa_contratante'] ?? null);
        if ($fromSlug !== null) {
            return $fromSlug;
        }
        $branchId = isset($user['user_branch_id']) && is_numeric($user['user_branch_id'])
            ? (int) $user['user_branch_id']
            : 0;
        if ($branchId <= 0) {
            return null;
        }
        try {
            $repo = new \App\adms\Models\Repository\BranchesRepository();
            $code = $repo->getCodeByBranchId($branchId);

            return self::normalizeEmpresaContratante($code);
        } catch (\Throwable) {
            return null;
        }
    }

    /** Rótulo de UI a partir do registro do usuário (slug ou FK). */
    public static function empresaContratanteLabelFromUser(array $user): string
    {
        $slug = self::resolveEmpresaSlugFromUser($user);

        return self::empresaContratanteLabel($slug);
    }

    /** @return 'usuario'|'pessoais'|'endereco'|'contratuais'|'formacoes'|'acessos'|'permissoes' */
    public static function normalizeUserFormActiveTab(mixed $value): string
    {
        $v = strtolower(trim((string) $value));
        $v = preg_replace('/^#?tab-/', '', $v) ?? $v;
        $allowed = ['usuario', 'pessoais', 'endereco', 'contratuais', 'formacoes', 'acessos', 'permissoes'];

        return in_array($v, $allowed, true) ? $v : 'usuario';
    }

    /** Resolve slug a partir do cadastro (slug, rótulo curto ou razão social do PDF). */
    public static function resolveEmpresaContratanteSlug(mixed $value): ?string
    {
        $fromSlug = self::normalizeEmpresaContratante($value);
        if ($fromSlug !== null) {
            return $fromSlug;
        }
        if ($value === null || trim((string) $value) === '') {
            return null;
        }
        $needle = strtolower(trim((string) $value));
        $options = self::empresaContratanteOptions();
        foreach (self::EMPRESA_CONTRATANTE_SLUGS as $slug) {
            if ($needle === strtolower($slug)) {
                return $slug;
            }
            if ($needle === strtolower(self::empresaContratanteLabel($slug))) {
                return $slug;
            }
            if ($needle === strtolower($options[$slug] ?? '')) {
                return $slug;
            }
            if ($needle === strtolower(self::empresaContratantePdfLabel($slug))) {
                return $slug;
            }
            if ($needle === strtolower(self::empresaContratanteRazaoSocialLabel($slug))) {
                return $slug;
            }
            // Rótulos antigos (pré-migração de nomes fantasia)
            if ($slug === 'lab_tiaraju_matriz' && in_array($needle, ['lab. tiaraju matriz', 'lab tiaraju matriz'], true)) {
                return $slug;
            }
            if ($slug === 'lab_tiaraju_filial' && in_array($needle, ['lab. tiaraju filial', 'lab tiaraju filial'], true)) {
                return $slug;
            }
        }

        return null;
    }

    public static function empresaContratanteLabel(?string $code): string
    {
        return match ($code) {
            'tiaraju_farma' => 'Tiaraju Farma',
            'lab_tiaraju_matriz' => 'Laboratório Tiaraju',
            'lab_tiaraju_filial' => 'Afra Pharma',
            default => 'Empresa contratante não informada',
        };
    }

    /**
     * Rótulo em documentos PDF que diferenciam estabelecimento (ASO etc.).
     * Usa nome fantasia — razão social é idêntica entre matriz e filial.
     */
    public static function empresaContratantePdfLabel(string $slug): string
    {
        $fromBranches = self::empresaContratanteOptionsFromBranches();
        if (!empty($fromBranches[$slug])) {
            return $fromBranches[$slug];
        }
        $ui = self::empresaContratanteLabel($slug);

        return $ui !== 'Empresa contratante não informada' ? $ui : '';
    }

    /** Razão social (nome empresarial) — pode repetir entre Matriz e Filial. */
    public static function empresaContratanteRazaoSocialLabel(string $slug): string
    {
        return match ($slug) {
            'tiaraju_farma' => 'Tiaraju Farma, Alimentos e Cosméticos Ltda',
            'lab_tiaraju_matriz' => 'Laboratorio Tiaraju Alimentos e Cosmeticos S/A',
            'lab_tiaraju_filial' => 'Laboratorio Tiaraju Alimentos e Cosmeticos S/A',
            default => '',
        };
    }

    /** @return array<string, string> slug => nome fantasia (PDF / diferenciação de unidade) */
    public static function empresaContratantePdfOptions(): array
    {
        $out = [];
        foreach (self::EMPRESA_CONTRATANTE_SLUGS as $slug) {
            $label = self::empresaContratantePdfLabel($slug);
            if ($label !== '') {
                $out[$slug] = $label;
            }
        }

        return $out;
    }

    /** @return list<string> */
    public static function ufList(): array
    {
        return [
            'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA',
            'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN',
            'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO',
        ];
    }

    /** @return array<string, string> UF => UF */
    public static function ufOptions(): array
    {
        $out = [];
        foreach (self::ufList() as $uf) {
            $out[$uf] = $uf;
        }

        return $out;
    }

    public static function normalizeUf(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $v = strtoupper(trim((string) $value));

        return in_array($v, self::ufList(), true) ? $v : null;
    }

    public static function normalizeEmailPessoal(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }
        $email = strtolower(trim((string) $value));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $email;
    }

    public static function normalizeCep(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';
        if ($digits === '') {
            return null;
        }
        if (strlen($digits) === 8) {
            return substr($digits, 0, 5) . '-' . substr($digits, 5);
        }

        return substr($digits, 0, 10);
    }

    public static function normalizeOptionalText(mixed $value, int $maxLength = 255): ?string
    {
        if ($value === null) {
            return null;
        }
        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }
        if (mb_strlen($text) > $maxLength) {
            $text = mb_substr($text, 0, $maxLength);
        }

        return $text;
    }

    /**
     * Valores padrão de flags alinhados ao ENUM/default do banco (adms_users).
     *
     * @return array<string, mixed>
     */
    public static function defaultUserFormValues(): array
    {
        return [
            'status' => 'Ativo',
            'bloqueado' => 'Não',
            'senha_nunca_expira' => 'Não',
            'modificar_senha_proximo_logon' => 'Não',
            'tentativas_login' => 0,
            'super_usuario' => 0,
            'pais_residencia_iso' => 'BR',
        ];
    }

    /** Interpreta Sim/Não e legado 0/1 como no banco e na visualização. */
    public static function isSimFlag(mixed $value): bool
    {
        if ($value === true || $value === 1 || $value === '1') {
            return true;
        }

        return strcasecmp(trim((string) $value), 'Sim') === 0;
    }

    public static function isUserBlocked(mixed $bloqueado): bool
    {
        return self::isSimFlag($bloqueado);
    }

    /** Rótulo igual ao da visualização (badge). */
    public static function bloqueadoDisplayLabel(mixed $bloqueado): string
    {
        return self::isUserBlocked($bloqueado) ? 'Bloqueado' : 'Não bloqueado';
    }

    public static function simNaoLabel(mixed $value): string
    {
        return self::isSimFlag($value) ? 'Sim' : 'Não';
    }

    /**
     * Normaliza registro do banco para o formulário (create/update) com os mesmos
     * significados usados na visualização — em todas as abas.
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public static function hydrateUserFormFromRow(array $row): array
    {
        $status = trim((string) ($row['status'] ?? ''));
        $row['status'] = strcasecmp($status, 'Ativo') === 0 ? 'Ativo' : 'Inativo';
        $row['bloqueado'] = self::isUserBlocked($row['bloqueado'] ?? null) ? 'Sim' : 'Não';
        $row['senha_nunca_expira'] = self::isSimFlag($row['senha_nunca_expira'] ?? null) ? 'Sim' : 'Não';
        $row['modificar_senha_proximo_logon'] = self::isSimFlag($row['modificar_senha_proximo_logon'] ?? null) ? 'Sim' : 'Não';
        $row['tentativas_login'] = (int) ($row['tentativas_login'] ?? 0);
        $row['super_usuario'] = !empty($row['super_usuario']) ? 1 : 0;

        $emp = self::resolveEmpresaSlugFromUser($row);
        $row['empresa_contratante'] = $emp ?? '';

        $row['sexo'] = self::normalizeSexo($row['sexo'] ?? '') ?? '';
        $row['filhos'] = self::normalizeFilhos($row['filhos'] ?? '') ?? '';
        $row['estado_civil'] = self::normalizeEstadoCivil($row['estado_civil'] ?? null) ?? '';
        $row['escolaridade'] = self::normalizeEscolaridade($row['escolaridade'] ?? null) ?? '';
        $row['raca'] = self::normalizeRaca($row['raca'] ?? null) ?? '';
        $row['pais_residencia_iso'] = self::normalizePaisResidenciaIso($row['pais_residencia_iso'] ?? null) ?? 'BR';
        $row['uf'] = self::normalizeUf($row['uf'] ?? null) ?? '';
        $row['cep'] = self::normalizeCep($row['cep'] ?? null) ?? '';
        $row['email_pessoal'] = self::normalizeEmailPessoal($row['email_pessoal'] ?? null) ?? '';
        $row['matricula'] = self::normalizeOptionalText($row['matricula'] ?? null, 40) ?? '';
        $row['endereco'] = self::normalizeOptionalText($row['endereco'] ?? null, 255) ?? '';
        $row['numero_endereco'] = self::normalizeOptionalText($row['numero_endereco'] ?? null, 20) ?? '';
        $row['complemento_endereco'] = self::normalizeOptionalText($row['complemento_endereco'] ?? null, 80) ?? '';
        $row['bairro'] = self::normalizeOptionalText($row['bairro'] ?? null, 120) ?? '';
        $row['municipio'] = self::normalizeOptionalText($row['municipio'] ?? null, 120) ?? '';

        $row['data_nascimento'] = self::normalizeDateInput($row['data_nascimento'] ?? null);
        $row['data_admissao'] = self::normalizeDateInput($row['data_admissao'] ?? null);
        $row['data_desligamento'] = self::normalizeDateInput($row['data_desligamento'] ?? null);
        $row['tipo_impacto_desligamento'] = self::normalizeTipoImpactoDesligamento($row['tipo_impacto_desligamento'] ?? null) ?? '';
        $row['motivo_desligamento'] = self::normalizeOptionalText($row['motivo_desligamento'] ?? null, 255) ?? '';

        return $row;
    }

    /** Data para input type=date (Y-m-d) a partir do valor do banco. */
    public static function normalizeDateInput(mixed $value): string
    {
        if ($value === null || trim((string) $value) === '') {
            return '';
        }
        $raw = trim((string) $value);
        $ts = strtotime($raw);
        if ($ts === false) {
            return '';
        }

        return date('Y-m-d', $ts);
    }

    /**
     * Após POST inválido: mantém o que o usuário enviou e completa com o banco
     * (para todas as abas continuarem coerentes com o cadastro).
     *
     * @param array<string, mixed> $hydrated
     * @param array<string, mixed> $posted
     * @return array<string, mixed>
     */
    public static function overlayPostedUserFormOnHydrated(array $hydrated, array $posted): array
    {
        $out = array_merge($hydrated, $posted);

        // Switches: ausência no POST = desligado (comportamento HTML de checkbox)
        $out['status'] = (isset($posted['status']) && $posted['status'] === 'Ativo') ? 'Ativo' : 'Inativo';
        $out['bloqueado'] = (isset($posted['bloqueado']) && $posted['bloqueado'] === 'Sim') ? 'Sim' : 'Não';
        $out['senha_nunca_expira'] = (isset($posted['senha_nunca_expira']) && $posted['senha_nunca_expira'] === 'Sim') ? 'Sim' : 'Não';
        $out['modificar_senha_proximo_logon'] = (isset($posted['modificar_senha_proximo_logon']) && $posted['modificar_senha_proximo_logon'] === 'Sim')
            ? 'Sim'
            : 'Não';

        if (array_key_exists('tentativas_login', $posted) && $posted['tentativas_login'] !== null && $posted['tentativas_login'] !== '') {
            $out['tentativas_login'] = (int) $posted['tentativas_login'];
        } else {
            $out['tentativas_login'] = (int) ($hydrated['tentativas_login'] ?? 0);
        }

        return $out;
    }

    /**
     * Aba a abrir quando houver erros de validação (mesmo comportamento em create/update).
     *
     * @param array<string, mixed> $errors
     */
    public static function resolveTabForFormErrors(array $errors): string
    {
        if ($errors === []) {
            return 'usuario';
        }

        foreach (array_keys($errors) as $key) {
            $k = strtolower((string) $key);
            if (str_contains($k, 'education') || str_contains($k, 'formacao') || str_contains($k, 'formação')) {
                return 'formacoes';
            }
        }

        $map = [
            'contratuais' => ['empresa_contratante', 'matricula', 'data_admissao', 'data_desligamento', 'motivo_desligamento', 'tipo_impacto_desligamento'],
            'pessoais' => ['data_nascimento', 'sexo', 'filhos', 'estado_civil', 'escolaridade', 'raca', 'email_pessoal'],
            'endereco' => ['cep', 'endereco', 'numero_endereco', 'complemento_endereco', 'bairro', 'municipio', 'uf', 'pais_residencia_iso'],
            'usuario' => ['name', 'email', 'username', 'cpf', 'celular', 'user_department_id', 'user_position_id', 'immediate_supervisor_id', 'adms_work_shift_id', 'password', 'confirm_password', 'image', 'status', 'bloqueado'],
            'formacoes' => ['educations'],
        ];

        foreach ($map as $tab => $fields) {
            foreach ($fields as $field) {
                if (array_key_exists($field, $errors)) {
                    return $tab;
                }
            }
        }

        return 'usuario';
    }
}
