<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Tests\Integration;

use GlobusStudio\ReadSight\Engine;
use GlobusStudio\ReadSight\Exception\UnsupportedLanguageException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MultilingualIntegrationTest extends TestCase
{
    private const string DATA_DIR = __DIR__ . '/../../data';

    /** @return list<list{string, string, int|null}> */
    public static function syllableLanguagesProvider(): array
    {
        return [
            ['en-us', 'banana', 3],
            ['ru', 'молоко', 3],
            ['de-1996', 'Verständlichkeit', null],
            ['fr', 'bonjour', null],
            ['es', 'hola', null],
            ['it', 'ciao', null],
            ['pl', 'dzień', null],
            ['pt', 'obrigado', null],
            ['nl', 'goedemorgen', null],
            ['fi', 'hyvää', null],
            ['tr', 'merhaba', null],
            ['uk', 'привіт', null],
            ['cs', 'ahoj', null],
            ['el-monoton', 'καλημέρα', null],
            ['ca', 'hola', null],
        ];
    }

    #[DataProvider('syllableLanguagesProvider')]
    public function test_syllable_count_positive(string $langCode, string $word, ?int $expectedCount): void
    {
        $engine = new Engine($langCode, self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages');

        $count = $engine->syllableCount($word);
        $this->assertGreaterThan(0, $count, "Lang: {$langCode}, word: {$word}");

        if ($expectedCount !== null) {
            $this->assertSame($expectedCount, $count, "Lang: {$langCode}, word: {$word}");
        }
    }

    public function test_all_supported_languages_load(): void
    {
        $langs = Engine::getSupportedLanguages();
        $this->assertGreaterThanOrEqual(75, \count($langs));

        $loaded = 0;
        foreach ($langs as $code) {
            $engine = new Engine($code, self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages');
            $this->assertSame($code, $engine->getLanguage()->code);
            $loaded++;
        }

        $this->assertGreaterThan(0, $loaded);
    }

    public function test_universal_formulas_across_scripts(): void
    {
        $languages = ['en-us', 'ru', 'el-monoton', 'hy', 'ka'];

        foreach ($languages as $code) {
            $engine = new Engine($code, self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages');

            $this->assertGreaterThan(-50, $engine->gunningFog('test')->score);
            $this->assertGreaterThan(-50, $engine->automatedReadabilityIndex('test')->score);
            $this->assertGreaterThan(0, $engine->lix('test')->score);
            $engine->colemanLiau('test'); // just verify no exception
        }
    }

    public function test_fre_all_languages(): void
    {
        $freLangs = ['en-us', 'ru', 'de-1996', 'es', 'fr', 'it', 'nl', 'pt', 'tr'];

        foreach ($freLangs as $code) {
            $engine = new Engine($code, self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages');
            $result = $engine->fleschReadingEase('Test text for readability. It works.');
            $this->assertGreaterThan(0, $result->score, "FRE should return positive for {$code}");
        }
    }

    public function test_language_specific_formulas(): void
    {
        $tests = [
            'de-1996' => 'wiener_sachtextformel',
            'it' => 'gulpease',
            'es' => 'fernandez_huerta',
            'pl' => 'fog_pl',
        ];

        foreach ($tests as $code => $formula) {
            $engine = new Engine($code, self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages');
            $result = $engine->score($formula, 'Test text. Another sentence here.');
            $this->assertNotEmpty($result->formulaName);
        }
    }

    public function test_throws_unknown_language(): void
    {
        $this->expectException(UnsupportedLanguageException::class);
        new Engine('zz-nonexistent', self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages');
    }

    public function test_formula_lists_per_language(): void
    {
        $cases = [
            'en-us' => ['flesch_reading_ease', 'gunning_fog', 'lix', 'dale_chall'],
            'ru' => ['flesch_reading_ease', 'gunning_fog', 'lix'],
            'de-1996' => ['flesch_reading_ease', 'wiener_sachtextformel', 'lix'],
            'th' => ['gunning_fog', 'smog', 'coleman_liau', 'ari', 'lix'],
        ];

        foreach ($cases as $code => $expected) {
            $engine = new Engine($code, self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages');
            $formulas = $engine->getSupportedFormulas();
            foreach ($expected as $f) {
                $this->assertContains($f, $formulas, "{$code} should have {$f}");
            }
        }
    }
}

