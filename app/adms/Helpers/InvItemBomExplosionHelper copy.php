<?php



declare(strict_types=1);



namespace App\adms\Helpers;



use App\adms\Models\Repository\inventory\InvItemBomRepository;



/**

 * Explosão de produtos intermediários (PI) na BOM do PA — alinhado à planilha FORMPROD.

 *

 * O PA mantém o PI na estrutura SAP, mas o custeio e a exibição usam as MPs/MAEs do PI

 * (quantidades × qty PI no PA). Sub-BOM do PI é por unidade de PI (não divide pelo MinOrdrQty SAP).

 */

final class InvItemBomExplosionHelper

{

    private const MAX_DEPTH = 8;



    /**

     * @param list<array<string, mixed>> $rows Linhas no formato de custeio (InventoryCostService)

     * @return list<array<string, mixed>>

     */

    public static function explodeIntermediateProducts(array $rows): array

    {

        if ($rows === []) {

            return [];

        }



        $repo = new InvItemBomRepository();



        return self::flattenLevel($rows, $repo, 0, null);

    }



    /**

     * Monta linhas para exibição: PI de referência + MPs/MAEs explodidas com identificação.

     *

     * @param list<array<string, mixed>> $rows Linhas de InvItemBomRepository::getByItem (com categoria)

     * @return list<array<string, mixed>>

     */

    public static function buildDisplayRows(array $rows): array

    {

        if ($rows === []) {

            return [];

        }



        $repo = new InvItemBomRepository();



        return self::displayLevel($rows, $repo, 0, null);

    }



    public static function isIntermediateCategory(?string $categoryName): bool

    {

        $upper = mb_strtoupper(trim((string)$categoryName), 'UTF-8');

        if ($upper === '') {

            return false;

        }



        return str_contains($upper, 'INTERMED') || str_contains($upper, 'PROD INTER');

    }



    /**

     * @param list<array<string, mixed>> $rows

     * @return list<array<string, mixed>>

     */

    private static function flattenLevel(

        array $rows,

        InvItemBomRepository $repo,

        int $depth,

        ?array $piContext

    ): array {

        if ($depth >= self::MAX_DEPTH) {

            return $rows;

        }



        $flat = [];

        foreach ($rows as $row) {

            $lineSource = (string)($row['line_source'] ?? 'catalog');

            $category = (string)($row['component_category'] ?? '');

            $componentId = (int)($row['component_item_id'] ?? 0);



            if (

                $lineSource !== 'catalog'

                || $componentId <= 0

                || !self::isIntermediateCategory($category)

            ) {

                $flat[] = $row;

                continue;

            }



            $scaledChildren = self::scalePiChildren($row, $repo, $depth);

            if ($scaledChildren === []) {

                $flat[] = $row;

                continue;

            }



            $flat = array_merge($flat, self::flattenLevel($scaledChildren, $repo, $depth + 1, null));

        }



        return $flat;

    }



    /**

     * @param list<array<string, mixed>> $rows

     * @return list<array<string, mixed>>

     */

    private static function displayLevel(

        array $rows,

        InvItemBomRepository $repo,

        int $depth,

        ?array $piContext

    ): array {

        if ($depth >= self::MAX_DEPTH) {

            return $rows;

        }



        $display = [];

        foreach ($rows as $row) {

            $lineSource = (string)($row['line_source'] ?? 'catalog');

            $category = (string)($row['component_category'] ?? '');

            $componentId = (int)($row['component_item_id'] ?? 0);



            if (

                $lineSource === 'catalog'

                && $componentId > 0

                && self::isIntermediateCategory($category)

            ) {

                $piContext = [

                    'item_id' => $componentId,

                    'code' => trim((string)($row['component_code'] ?? '')),

                    'description' => trim((string)($row['component_description'] ?? '')),

                ];

                $row['bom_row_kind'] = 'pi_reference';

                $row['include_in_total'] = false;

                $display[] = $row;



                $scaledChildren = self::scalePiChildren($row, $repo, $depth);

                if ($scaledChildren === []) {

                    continue;

                }



                foreach (self::displayLevel($scaledChildren, $repo, $depth + 1, $piContext) as $child) {

                    if (($child['bom_row_kind'] ?? '') === 'pi_reference') {

                        if ($piContext !== null) {

                            $child['from_pi_item_id'] = (int)($piContext['item_id'] ?? 0);

                            $child['from_pi_code'] = (string)($piContext['code'] ?? '');

                            $child['from_pi_description'] = (string)($piContext['description'] ?? '');

                        }

                        $display[] = $child;

                        continue;

                    }

                    $child['bom_row_kind'] = 'pi_exploded';

                    $child['include_in_total'] = true;

                    if (empty($child['from_pi_code'])) {

                        $child['from_pi_item_id'] = (int)($piContext['item_id'] ?? 0);

                        $child['from_pi_code'] = (string)($piContext['code'] ?? '');

                        $child['from_pi_description'] = (string)($piContext['description'] ?? '');

                    }

                    $display[] = $child;

                }

                continue;

            }



            $row['bom_row_kind'] = $lineSource === 'manual' ? 'manual' : 'catalog';

            $row['include_in_total'] = true;

            $display[] = $row;

        }



        return $display;

    }



    /**

     * @param array<string, mixed> $piRow

     * @return list<array<string, mixed>>

     */

    private static function scalePiChildren(array $piRow, InvItemBomRepository $repo, int $depth): array

    {

        if ($depth >= self::MAX_DEPTH) {

            return [];

        }



        $componentId = (int)($piRow['component_item_id'] ?? 0);

        $piQty = (float)($piRow['quantity_per_batch'] ?? 0);

        $piScrap = (float)($piRow['scrap_percent'] ?? 0);

        if ($componentId <= 0 || $piQty <= 0) {

            return [];

        }



        $piFactor = $piQty * (1 + $piScrap / 100.0);

        $childRows = $repo->getCostingRowsByItem($componentId);

        if ($childRows === []) {

            return [];

        }



        $scaledChildren = [];

        foreach ($childRows as $child) {

            $childQty = (float)($child['quantity_per_batch'] ?? 0);

            if ($childQty <= 0) {

                continue;

            }

            $child['quantity_per_batch'] = round($childQty * $piFactor, 8);

            $child['scrap_percent'] = (float)($child['scrap_percent'] ?? 0);

            $scaledChildren[] = $child;

        }



        return $scaledChildren;

    }

}

