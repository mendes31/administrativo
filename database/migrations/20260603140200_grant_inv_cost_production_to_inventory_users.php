<?php

declare(strict_types=1);

use App\adms\Models\Repository\MenuPermissionUserRepository;
use Phinx\Migration\AbstractMigration;

/**
 * Antes copiava permissão de ListInventoryItems para todos os níveis que viam estoque.
 * Mantido como no-op seguro: a liberação do custeio deve ser manual na matriz
 * (exceto Super Admin, tratado nas grants específicas / acesso total).
 */
final class GrantInvCostProductionToInventoryUsers extends AbstractMigration
{
    public function up(): void
    {
        // Intencionalmente vazio: não propagar custeio para todos os perfis de estoque.
        MenuPermissionUserRepository::bumpGlobalPermissionCacheVersion();
    }

    public function down(): void
    {
    }
}
