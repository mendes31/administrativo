<?php

declare(strict_types=1);

namespace App\adms\Helpers;

final class DashboardBirthdayCardsHelper
{
    /**
     * @param list<array<string, mixed>> $items
     */
    public static function renderBirthdayMonthCards(array $items): string
    {
        $urlAdm = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/';
        $birthdayMonthItems = $items;

        ob_start();
        include dirname(__DIR__) . '/Views/dashboard/partials/birthday_month_cards.php';

        return (string) ob_get_clean();
    }

    /**
     * @param list<array<string, mixed>> $items
     */
    public static function renderCompanyTenureMonthCards(array $items): string
    {
        $urlAdm = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/';
        $companyTenureMonthItems = $items;

        ob_start();
        include dirname(__DIR__) . '/Views/dashboard/partials/company_tenure_month_cards.php';

        return (string) ob_get_clean();
    }
}
