<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AlterCharsetAdmsDepartments extends AbstractMigration
{
    public function up(): void
    {
        // Garantir charset/collation corretos na tabela e coluna
        $this->execute("ALTER TABLE adms_departments CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $this->execute("ALTER TABLE adms_departments MODIFY name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL");
    }

    public function down(): void
    {
        // Sem down específico; manter utf8mb4
    }
}


