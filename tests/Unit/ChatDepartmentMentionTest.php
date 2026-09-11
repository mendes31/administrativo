<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\InternalChat\ChatDepartmentMention;
use PHPUnit\Framework\TestCase;

final class ChatDepartmentMentionTest extends TestCase
{
    /** @var list<string> */
    private const NAMES = [
        'Controle de Qualidade',
        'Garantia da Qualidade',
        'Recursos Humanos',
        'TI',
        'Produção',
    ];

    /** @var array<string, string> */
    private const ALIASES = [
        'ti' => 'TI',
        'informatica' => 'TI',
        'gq' => 'Garantia da Qualidade',
        'rh' => 'Recursos Humanos',
    ];

    public function testMatchesFullDepartmentAndAgeInSameSentence(): void
    {
        $got = ChatDepartmentMention::match(
            'garantia da qualidade maiores de 30 anos',
            self::NAMES,
            self::ALIASES
        );
        self::assertSame('Garantia da Qualidade', $got);
    }

    public function testPrefersLongerNameOverQualidadeSubstring(): void
    {
        $got = ChatDepartmentMention::match(
            'colaboradores da garantia da qualidade com mais de 30',
            self::NAMES,
            self::ALIASES
        );
        self::assertSame('Garantia da Qualidade', $got);
    }

    public function testShortAliasTi(): void
    {
        $got = ChatDepartmentMention::match('users 30 anos TI', self::NAMES, self::ALIASES);
        self::assertSame('TI', $got);
    }

    public function testNoDepartment(): void
    {
        self::assertNull(ChatDepartmentMention::match('maiores de 30 anos', self::NAMES, self::ALIASES));
    }
}
