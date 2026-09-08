<?php

declare(strict_types=1);

namespace App\adms\Models\Services\Imports;

final class ImportProfileCatalog
{
    /** @var array<string, ImportProfileInterface>|null */
    private static ?array $profiles = null;

    /** @return array<string, ImportProfileInterface> */
    public static function all(): array
    {
        if (self::$profiles === null) {
            $list = [
                new UsersImportProfile(),
                new DepartmentsImportProfile(),
                new PositionsImportProfile(),
                new SstRiscosImportProfile(),
                new SstEpisImportProfile(),
                new SstExamesImportProfile(),
                new SstTreinamentosImportProfile(),
                new SstCidsImportProfile(),
                new SstMedicosImportProfile(),
                new SstGheImportProfile(),
                new SstRiscosCargoImportProfile(),
                new SstRiscoEpiImportProfile(),
                new SstRiscoExameImportProfile(),
                new SstRiscoTreinamentoImportProfile(),
                new SstEpiNecessidadeImportProfile(),
                new SstExameNecessidadeImportProfile(),
                new SstTreinamentoNecessidadeImportProfile(),
            ];
            self::$profiles = [];
            foreach ($list as $profile) {
                self::$profiles[$profile->key()] = $profile;
            }
        }

        return self::$profiles;
    }

    public static function get(string $key): ?ImportProfileInterface
    {
        return self::all()[$key] ?? null;
    }
}
