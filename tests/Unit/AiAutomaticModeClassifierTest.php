<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\AiAnalyst\Adapters\FakeReportRunner;
use App\adms\Models\Services\AiAnalyst\Auth\AiActorContext;
use App\adms\Models\Services\AiAnalyst\Auth\AiConversationContext;
use App\adms\Models\Services\AiAnalyst\Feature\AiAnalystFeatureFlags;
use App\adms\Models\Services\AiAnalyst\Routing\AiAutomaticModeClassifier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AiAutomaticModeClassifierTest extends TestCase
{
    #[DataProvider('legacyMessages')]
    public function testCurrentChatCapabilitiesRemainOnLegacy(string $message): void
    {
        self::assertSame('legacy', $this->classify($message, true));
    }

    /** @return iterable<string, array{string}> */
    public static function legacyMessages(): iterable
    {
        yield 'saudacao' => ['Bom dia'];
        yield 'salas' => ['Quero reservar uma sala de reunião'];
        yield 'rh' => ['Quantos funcionários ativos temos?'];
        yield 'ambigua' => ['Pode me ajudar com isso?'];
    }

    public function testBusinessQuestionIsRoutedToBusinessProfile(): void
    {
        self::assertSame(
            'business',
            $this->classify('Compare o faturamento e a margem deste mês', false)
        );
        self::assertSame(
            'business',
            $this->classify('compare venda de janeiro e jun de 2026', false)
        );
        self::assertSame(
            'business',
            $this->classify('compare vendas janeiro e junho', false)
        );
        self::assertSame(
            'business',
            $this->classify('compare os 12 meses de 2025', false)
        );
        self::assertSame(
            'business',
            $this->classify('analise vendas dos 12 meses de 2025', false)
        );
    }

    public function testPeriodFollowUpUsesSalesSessionContext(): void
    {
        self::assertSame('legacy', $this->classify('compare jan/25, mar/26', false));

        $conversation = new AiConversationContext();
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION['ai_analyst_conversation'] = [
            'domain' => 'sales',
            'binding' => FakeReportRunner::BINDING,
            'updated_at' => time(),
        ];

        self::assertSame(
            'business',
            $this->classify('compare jan/25, mar/26', false, $conversation)
        );
        $conversation->clear();
    }

    public function testLooseTableKeywordsDoNotStealBusinessOrElevateCommonUsers(): void
    {
        self::assertSame('legacy', $this->classify('tabela de itens', true));
        self::assertSame('legacy', $this->classify('draft', true));
        self::assertSame('legacy', $this->classify('tabela de rascunhos', true));
        self::assertSame('legacy', $this->classify('OITM', true));
        self::assertSame('legacy', $this->classify('BEAS_PERS', true));
    }

    public function testSimpleReportRequestStaysOnLegacy(): void
    {
        self::assertSame('legacy', $this->classify('vendas por mês', false));
        self::assertSame('legacy', $this->classify('relatório faturamento', false));
    }

    public function testExplicitSchemaQuestionRequiresFullSystemAccess(): void
    {
        self::assertSame(
            'legacy',
            $this->classify('quais campos da OITM?', false)
        );
        self::assertSame(
            'technical',
            $this->classify('quais campos da OITM?', true)
        );
        self::assertSame(
            'technical',
            $this->classify('explique a tabela OITM', true)
        );
        self::assertSame(
            'technical',
            $this->classify('campos da BEAS_APL_HEADER', true)
        );
        self::assertSame(
            'technical',
            $this->classify('Explique esta consulta SQL HANA', true)
        );
        self::assertSame(
            'legacy',
            $this->classify('Explique esta consulta SQL HANA', false)
        );
    }

    private function classify(
        string $message,
        bool $fullSystemAccess,
        ?AiConversationContext $conversation = null
    ): string {
        $flags = new AiAnalystFeatureFlags([
            'AI_ANALYST_ENABLED' => true,
            'AI_ANALYST_BUSINESS_ENABLED' => true,
            'AI_ANALYST_TECHNICAL_ENABLED' => true,
        ]);
        $actor = new AiActorContext(10, $fullSystemAccess, ['report.list', 'report.run']);

        return (new AiAutomaticModeClassifier())->classify($message, $actor, $flags, $conversation);
    }
}
