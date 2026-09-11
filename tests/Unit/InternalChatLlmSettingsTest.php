<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\adms\Models\Services\InternalChat\InternalChatLlmSettings;
use PHPUnit\Framework\TestCase;

final class InternalChatLlmSettingsTest extends TestCase
{
    public function testAutoPrefersGroqThenGemini(): void
    {
        $settings = new InternalChatLlmSettings(
            [
                'llm_provider' => 'auto',
                'llm_groq_api_key' => 'gsk_test',
                'llm_gemini_api_key' => 'AIza_test',
            ],
            []
        );

        $plan = $settings->resolveCallPlan();
        self::assertSame(['groq', 'gemini'], array_column($plan, 'slot'));
        self::assertSame('https://api.groq.com/openai/v1', $plan[0]['base_url']);
        self::assertSame('https://generativelanguage.googleapis.com/v1beta/openai', $plan[1]['base_url']);
    }

    public function testForcedProviderIgnoresOtherKeys(): void
    {
        $settings = new InternalChatLlmSettings(
            [
                'llm_provider' => 'gemini',
                'llm_groq_api_key' => 'gsk_test',
                'llm_gemini_api_key' => 'AIza_test',
                'llm_gemini_model' => 'gemini-2.5-flash',
            ],
            []
        );

        $plan = $settings->resolveCallPlan();
        self::assertCount(1, $plan);
        self::assertSame('gemini', $plan[0]['slot']);
        self::assertSame('gemini-2.5-flash', $plan[0]['model']);
    }

    public function testFallsBackToEnvOpenAiWhenUiEmpty(): void
    {
        $settings = new InternalChatLlmSettings(
            ['llm_provider' => 'auto'],
            [
                'OPENAI_API_KEY' => 'sk-env',
                'OPENAI_BASE_URL' => 'https://api.openai.com/v1',
                'OPENAI_MODEL' => 'gpt-4o-mini',
            ]
        );

        $plan = $settings->resolveCallPlan();
        self::assertCount(1, $plan);
        self::assertSame('openai', $plan[0]['slot']);
        self::assertSame('sk-env', $plan[0]['api_key']);
    }

    public function testPostedKeyOverridesStoredWithoutClearingOthers(): void
    {
        $base = new InternalChatLlmSettings(
            [
                'llm_provider' => 'groq',
                'llm_groq_api_key' => 'gsk_old',
                'llm_gemini_api_key' => 'AIza_keep',
            ],
            []
        );
        $settings = $base->withPostedForm([
            'llm_provider' => 'gemini',
            'llm_gemini_api_key' => 'AIza_new',
        ]);

        $plan = $settings->resolveCallPlan();
        self::assertCount(1, $plan);
        self::assertSame('gemini', $plan[0]['slot']);
        self::assertSame('AIza_new', $plan[0]['api_key']);
    }

    public function testMaskKeyKeepsLastFour(): void
    {
        self::assertSame('••••1234', InternalChatLlmSettings::maskKey('gsk_abcd1234'));
        self::assertSame('', InternalChatLlmSettings::maskKey(''));
    }

    public function testAutoIgnoresErpUrlThatIsNotOllama(): void
    {
        $settings = new InternalChatLlmSettings(
            [
                'llm_provider' => 'auto',
                'llm_groq_api_key' => 'gsk_test',
                'llm_ollama_url' => 'http://192.168.1.118:5153/api/sales',
            ],
            ['OLLAMA_URL' => 'http://192.168.1.118:5153/api/sales']
        );

        $plan = $settings->resolveCallPlan();
        self::assertSame(['groq'], array_column($plan, 'slot'));
        self::assertFalse(InternalChatLlmSettings::isPlausibleOllamaUrl('http://192.168.1.118:5153/api/sales'));
        self::assertTrue(InternalChatLlmSettings::isPlausibleOllamaUrl('http://127.0.0.1:11434'));
    }
}
