<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/**
 * Campos de estabelecimento (matriz/filial) em adms_branches.
 */
final class BranchFormHelper
{
    public const TYPE_MATRIZ = 'matriz';
    public const TYPE_FILIAL = 'filial';

    /** @return list<string> */
    public static function typeSlugs(): array
    {
        return [self::TYPE_MATRIZ, self::TYPE_FILIAL];
    }

    public static function typeLabel(?string $type): string
    {
        return match ($type) {
            self::TYPE_MATRIZ => 'Matriz',
            self::TYPE_FILIAL => 'Filial',
            default => '—',
        };
    }

    /** @return array<string, string> */
    public static function typeOptions(): array
    {
        return [
            self::TYPE_MATRIZ => 'Matriz',
            self::TYPE_FILIAL => 'Filial',
        ];
    }

    /** @return array<string, string> UF => nome */
    public static function ufOptions(): array
    {
        return [
            'AC' => 'AC', 'AL' => 'AL', 'AP' => 'AP', 'AM' => 'AM', 'BA' => 'BA',
            'CE' => 'CE', 'DF' => 'DF', 'ES' => 'ES', 'GO' => 'GO', 'MA' => 'MA',
            'MT' => 'MT', 'MS' => 'MS', 'MG' => 'MG', 'PA' => 'PA', 'PB' => 'PB',
            'PR' => 'PR', 'PE' => 'PE', 'PI' => 'PI', 'RJ' => 'RJ', 'RN' => 'RN',
            'RS' => 'RS', 'RO' => 'RO', 'RR' => 'RR', 'SC' => 'SC', 'SP' => 'SP',
            'SE' => 'SE', 'TO' => 'TO',
        ];
    }

    public static function normalizeType(mixed $value): ?string
    {
        $v = strtolower(trim((string) $value));

        return in_array($v, self::typeSlugs(), true) ? $v : null;
    }

    /** Apenas dígitos do CNPJ (0–14). */
    public static function cnpjDigits(mixed $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }

    public static function normalizeCnpj(mixed $value): ?string
    {
        $digits = self::cnpjDigits($value);
        if ($digits === '') {
            return null;
        }
        if (strlen($digits) !== 14) {
            return null;
        }

        return $digits;
    }

    public static function formatCnpj(?string $digits): string
    {
        $d = self::cnpjDigits($digits);
        if (strlen($d) !== 14) {
            return (string) $digits;
        }

        return substr($d, 0, 2) . '.' . substr($d, 2, 3) . '.' . substr($d, 5, 3)
            . '/' . substr($d, 8, 4) . '-' . substr($d, 12, 2);
    }

    public static function cepDigits(mixed $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }

    public static function formatCep(?string $digits): string
    {
        $d = self::cepDigits($digits);
        if (strlen($d) !== 8) {
            return (string) $digits;
        }

        return substr($d, 0, 5) . '-' . substr($d, 5, 3);
    }

    /**
     * Monta linha única de endereço (legado / listagens).
     *
     * @param array<string, mixed> $data
     */
    public static function composeAddressLine(array $data): string
    {
        $parts = [];
        $log = trim((string) ($data['logradouro'] ?? ''));
        $num = trim((string) ($data['numero'] ?? ''));
        $comp = trim((string) ($data['complemento'] ?? ''));
        $bairro = trim((string) ($data['bairro'] ?? ''));
        $muni = trim((string) ($data['municipio'] ?? ''));
        $uf = strtoupper(trim((string) ($data['uf'] ?? '')));
        $cep = self::cepDigits($data['cep'] ?? '');

        if ($log !== '') {
            $line = $log;
            if ($num !== '') {
                $line .= ', ' . $num;
            }
            if ($comp !== '') {
                $line .= ' — ' . $comp;
            }
            $parts[] = $line;
        }
        if ($bairro !== '') {
            $parts[] = $bairro;
        }
        $cidade = trim($muni . ($uf !== '' ? '/' . $uf : ''));
        if ($cidade !== '' && $cidade !== '/') {
            $parts[] = $cidade;
        }
        if ($cep !== '') {
            $parts[] = 'CEP ' . self::formatCep($cep);
        }

        $composed = implode(' · ', $parts);
        if ($composed !== '') {
            return $composed;
        }

        return trim((string) ($data['address'] ?? ''));
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function normalizeFormPayload(array $data): array
    {
        $fantasia = trim((string) ($data['nome_fantasia'] ?? ''));
        $razao = trim((string) ($data['razao_social'] ?? ''));
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '' && $fantasia !== '') {
            $name = $fantasia;
        }
        if ($fantasia === '' && $name !== '') {
            $fantasia = $name;
        }

        $type = self::normalizeType($data['establishment_type'] ?? null) ?? self::TYPE_FILIAL;
        $cnpjRaw = trim((string) ($data['cnpj'] ?? ''));
        $cnpj = $cnpjRaw === '' ? null : self::cnpjDigits($cnpjRaw);

        $cepRaw = trim((string) ($data['cep'] ?? ''));
        $cep = $cepRaw === '' ? null : self::cepDigits($cepRaw);

        $uf = strtoupper(trim((string) ($data['uf'] ?? '')));
        if ($uf === '' || !isset(self::ufOptions()[$uf])) {
            $uf = '';
        }

        $nullable = static function (mixed $v): ?string {
            $t = trim((string) $v);

            return $t === '' ? null : $t;
        };

        $dataAbertura = trim((string) ($data['data_abertura'] ?? ''));
        if ($dataAbertura !== '') {
            // Aceita dd/mm/yyyy ou yyyy-mm-dd
            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $dataAbertura, $m)) {
                $dataAbertura = $m[3] . '-' . $m[2] . '-' . $m[1];
            }
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataAbertura)) {
                $dataAbertura = '';
            }
        }

        $data['name'] = $name;
        $data['nome_fantasia'] = $fantasia !== '' ? $fantasia : null;
        $data['razao_social'] = $razao !== '' ? $razao : null;
        $data['establishment_type'] = $type;
        $data['cnpj'] = ($cnpj !== null && $cnpj !== '') ? $cnpj : null;
        $data['data_abertura'] = $dataAbertura !== '' ? $dataAbertura : null;
        $data['porte'] = $nullable($data['porte'] ?? null);
        $data['cnae_principal'] = $nullable($data['cnae_principal'] ?? null);
        $data['natureza_juridica'] = $nullable($data['natureza_juridica'] ?? null);
        $data['logradouro'] = $nullable($data['logradouro'] ?? null);
        $data['numero'] = $nullable($data['numero'] ?? null);
        $data['complemento'] = $nullable($data['complemento'] ?? null);
        $data['cep'] = ($cep !== null && $cep !== '') ? $cep : null;
        $data['bairro'] = $nullable($data['bairro'] ?? null);
        $data['municipio'] = $nullable($data['municipio'] ?? null);
        $data['uf'] = $uf !== '' ? $uf : null;
        $data['situacao_cadastral'] = $nullable($data['situacao_cadastral'] ?? null);
        $data['phone'] = trim((string) ($data['phone'] ?? ''));
        $data['email'] = trim((string) ($data['email'] ?? ''));
        $data['address'] = self::composeAddressLine($data);

        return $data;
    }
}
