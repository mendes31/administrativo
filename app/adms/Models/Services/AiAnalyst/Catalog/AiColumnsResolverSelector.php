<?php

declare(strict_types=1);

namespace App\adms\Models\Services\AiAnalyst\Catalog;

use App\adms\Models\Services\AiAnalyst\Feature\AiAnalystFeatureFlags;

final class AiColumnsResolverSelector
{
    public function select(
        AiAnalystFeatureFlags $flags,
        AiTechnicalCatalogService $catalog
    ): TableColumnsResolverInterface {
        $local = new LocalCatalogColumnsResolver($catalog);
        if (!$flags->hanaMetadataEnabled()) {
            return $local;
        }

        return new HanaTableColumnsResolver($local);
    }
}
