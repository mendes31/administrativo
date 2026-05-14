<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

/**
 * Injeta no payload da Service Layer os campos de titular (dono do documento) e vendedor
 * em documentos de marketing (Orders, Quotations, Invoices, etc.).
 *
 * Convenção SAP B1 Service Layer (OData):
 * - {@see https://help.sap.com/docs/SAP_BUSINESS_ONE} entidades Orders / Invoices / …
 * - **DocumentsOwner**: código do empregado dono do documento (equivalente a OINV/ORDR.OwnerCode no DI).
 * - **SalesPersonCode**: código do vendedor (OSLP / tabela de vendedores).
 *
 * O login da API pode ser um utilizador técnico; estes campos definem quem figura no documento.
 */
final class SapB1MarketingDocumentAttribution
{
    /**
     * @param array<string, mixed> $document Corpo JSON já montado (CardCode, DocumentLines, …)
     * @param int|null $documentsOwner Código empregado titular/dono (DocumentsOwner). null = não altera.
     * @param int|null $salesPersonCode Código vendedor (SalesPersonCode). null = não altera.
     * @return array<string, mixed>
     */
    public static function merge(array $document, ?int $documentsOwner, ?int $salesPersonCode): array
    {
        if ($documentsOwner !== null && $documentsOwner > 0) {
            $document['DocumentsOwner'] = $documentsOwner;
        }
        if ($salesPersonCode !== null && $salesPersonCode > 0) {
            $document['SalesPersonCode'] = $salesPersonCode;
        }

        return $document;
    }

    /**
     * Preenche apenas chaves ainda ausentes, a partir de variáveis de ambiente opcionais:
     * `SAP_SL_DEFAULT_DOCUMENTS_OWNER`, `SAP_SL_DEFAULT_SALES_PERSON_CODE`.
     *
     * @param array<string, mixed> $document
     * @return array<string, mixed>
     */
    public static function mergeDefaultsFromEnv(array $document): array
    {
        $owner = self::intFromEnv('SAP_SL_DEFAULT_DOCUMENTS_OWNER');
        $slp = self::intFromEnv('SAP_SL_DEFAULT_SALES_PERSON_CODE');

        if ($owner !== null && $owner > 0 && !array_key_exists('DocumentsOwner', $document)) {
            $document['DocumentsOwner'] = $owner;
        }
        if ($slp !== null && $slp > 0 && !array_key_exists('SalesPersonCode', $document)) {
            $document['SalesPersonCode'] = $slp;
        }

        return $document;
    }

    private static function intFromEnv(string $key): ?int
    {
        if (!isset($_ENV[$key])) {
            return null;
        }
        $raw = trim((string) $_ENV[$key]);
        if ($raw === '') {
            return null;
        }
        $v = filter_var($raw, FILTER_VALIDATE_INT);

        return $v === false ? null : $v;
    }
}
