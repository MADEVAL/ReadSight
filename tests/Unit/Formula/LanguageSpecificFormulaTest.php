<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Tests\Unit\Formula;

use GlobusStudio\ReadSight\Formula\Crawford;
use GlobusStudio\ReadSight\Formula\DaleChall;
use GlobusStudio\ReadSight\Formula\FernandezHuerta;
use GlobusStudio\ReadSight\Formula\FogPL;
use GlobusStudio\ReadSight\Formula\Formula;
use GlobusStudio\ReadSight\Formula\Gulpease;
use GlobusStudio\ReadSight\Formula\GutierrezPolini;
use GlobusStudio\ReadSight\Formula\Osman;
use GlobusStudio\ReadSight\Formula\Spache;
use GlobusStudio\ReadSight\Formula\SzigrisztPazos;
use GlobusStudio\ReadSight\Formula\WienerSachtextformel;
use GlobusStudio\ReadSight\Language\Language;
use GlobusStudio\ReadSight\Text\TextStatistics;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LanguageSpecificFormulaTest extends TestCase
{
    private TextStatistics $stats;

    protected function setUp(): void
    {
        $this->stats = new TextStatistics(
            letterCount: 60,
            wordCount: 12,
            sentenceCount: 3,
            syllableCount: 18,
            polysyllableCount: 3,
            averageSyllablesPerWord: 1.5,
            averageWordsPerSentence: 4.0,
            longWordCount: 4,
            syllableHistogram: [1 => 6, 2 => 3, 3 => 2, 4 => 1],
        );
    }

    private function makeLanguage(string $code, string $script = 'Latin'): Language
    {
        return new Language([
            'code' => $code,
            'name' => $code,
            'nativeName' => $code,
            'script' => $script,
            'hyphenMins' => ['left' => 2, 'right' => 2],
            'letterPattern' => '[A-Za-z]',
            'wordSplitPattern' => "[^A-Za-z'’-]+",
            'sentenceBoundaryPattern' => '[.!?]+',
        ]);
    }

    public function test_wiener_sachtextformel_variant_1(): void
    {
        $formula = new WienerSachtextformel();
        $lang = $this->makeLanguage('de-1996');
        $result = $formula->calculateVariant($this->stats, $lang, 1);

        $this->assertStringContainsString('wiener_sachtextformel', $result->formulaName);
        $this->assertGreaterThan(0, $result->score);
    }

    public function test_wiener_sachtextformel_variant_4(): void
    {
        $formula = new WienerSachtextformel();
        $lang = $this->makeLanguage('de-1996');
        $result = $formula->calculateVariant($this->stats, $lang, 4);

        $expected = 0.2744 * (3.0 / 12.0 * 100.0) + 0.2656 * 4.0 - 1.693;
        $this->assertEqualsWithDelta($expected, $result->score, 0.1);
    }

    public function test_gulpease(): void
    {
        $formula = new Gulpease();
        $lang = $this->makeLanguage('it');

        $result = $formula->calculate($this->stats, $lang);

        $expected = 89.0 + (300.0 * 3 - 10.0 * 60.0) / 12.0;
        $this->assertEqualsWithDelta($expected, $result->score, 0.1);
        $this->assertNotEmpty($result->interpretation);
    }

    public function test_fernandez_huerta(): void
    {
        $formula = new FernandezHuerta();
        $lang = $this->makeLanguage('es');

        $result = $formula->calculate($this->stats, $lang);

        $expected = 206.84 - 1.02 * 4.0 - 60.0 * 1.5;
        $this->assertEqualsWithDelta($expected, $result->score, 0.1);
    }

    public function test_szigriszt_pazos(): void
    {
        $formula = new SzigrisztPazos();
        $lang = $this->makeLanguage('es');

        $result = $formula->calculate($this->stats, $lang);

        $S = (18.0 / 12.0) * 100.0;
        $expected = 206.835 - 62.3 * $S / 100.0 - 4.0;
        $this->assertEqualsWithDelta($expected, $result->score, 0.1);
    }

    public function test_gutierrez_polini(): void
    {
        $formula = new GutierrezPolini();
        $lang = $this->makeLanguage('es');

        $result = $formula->calculate($this->stats, $lang);

        $expected = 95.2 - 9.7 * (60.0 / 12.0) - 0.35 * 4.0;
        $this->assertEqualsWithDelta($expected, $result->score, 0.1);
    }

    public function test_crawford(): void
    {
        $formula = new Crawford();
        $lang = $this->makeLanguage('es');

        $result = $formula->calculate($this->stats, $lang);

        $avgLetters = 60.0 / 12.0;
        $sPer100 = (3.0 / 12.0) * 100.0;
        $expected = -0.205 * $avgLetters + 0.049 * $sPer100 - 3.407;
        $this->assertEqualsWithDelta($expected, $result->score, 0.1);
    }

    public function test_fog_pl(): void
    {
        $formula = new FogPL();
        $lang = $this->makeLanguage('pl');

        $result = $formula->calculate($this->stats, $lang);

        $hardPct = (3.0 / 12.0) * 100.0;
        $expected = 0.4 * ((12.0 / 3.0) + $hardPct);
        $this->assertEqualsWithDelta($expected, $result->score, 0.1);
    }

    public function test_osman(): void
    {
        $formula = new Osman();
        $lang = $this->makeLanguage('ar');

        $result = $formula->calculate($this->stats, $lang);

        $asl = 12.0 / 3.0;
        $avgLetters = 60.0 / 12.0;
        $hardPct = (3.0 / 12.0) * 100.0;
        $expected = 200.0 - 2.0 * $asl - 1.5 * $avgLetters - 0.4 * $hardPct;
        $this->assertEqualsWithDelta($expected, $result->score, 0.1);
    }

    public function test_wiener_sachtextformel_supported_languages(): void
    {
        $formula = new WienerSachtextformel();
        $langs = $formula->supportedLanguages();
        $this->assertContains('de-1996', $langs);
        $this->assertNotContains('en-us', $langs);
    }

    public function test_gulpease_supported_languages(): void
    {
        $formula = new Gulpease();
        $langs = $formula->supportedLanguages();
        $this->assertContains('it', $langs);
        $this->assertNotContains('en-us', $langs);
    }

    public function test_wiener_sachtextformel_variant_range(): void
    {
        $formula = new WienerSachtextformel();
        $lang = $this->makeLanguage('de-1996');

        for ($v = 1; $v <= 4; $v++) {
            $result = $formula->calculateVariant($this->stats, $lang, $v);
            $this->assertGreaterThan(-5.0, $result->score);
            $this->assertLessThan(20.0, $result->score);
        }
    }

    public function test_dale_chall_basic(): void
    {
        $formula = new DaleChall();
        $lang = $this->makeLanguage('en-us');
        $result = $formula->calculate($this->stats, $lang);

        $difficultPct = (($this->stats->wordCount - ($this->stats->syllableHistogram[1] ?? 0)) / $this->stats->wordCount) * 100.0;
        $raw = 0.1579 * $difficultPct + 0.0496 * $this->stats->averageWordsPerSentence;
        $expected = $raw > 0.05 ? $raw + 3.6365 : $raw;

        $this->assertEqualsWithDelta($expected, $result->score, 0.1);
        $this->assertSame('dale_chall', $result->formulaName);
        $this->assertSame('en-us', $result->languageCode);
        $this->assertArrayHasKey('difficultWordPct', $result->inputs);
    }

    public function test_dale_chall_interpretation_ranges(): void
    {
        $formula = new DaleChall();
        $lang = $this->makeLanguage('en-us');

        // To produce a Dale-Chall score of ~1.0: easy words, short sentences
        // rawScore = 0.1579 * 0 + 0.0496 * 5.0 = 0.248; adjusted = 0.248 (raw <= 0.05? No: 0.248+3.6365=3.8845)
        // With all 1-syllable words, difficultPct = 0
        $easyStats = new TextStatistics(
            letterCount: 25,
            wordCount: 5,
            sentenceCount: 1,
            syllableCount: 5,
            polysyllableCount: 0,
            averageSyllablesPerWord: 1.0,
            averageWordsPerSentence: 5.0,
            longWordCount: 0,
            syllableHistogram: [1 => 5],
        );
        $result = $formula->calculate($easyStats, $lang);
        $this->assertSame('4th grade or below', $result->interpretation);

        // All difficult words → difficultPct = 100, score ~ 0.1579*100 + 0.0496*5 = 15.79 + 0.248 = 16.038 + 3.6365 = 19.67
        $hardStats = new TextStatistics(
            letterCount: 25,
            wordCount: 5,
            sentenceCount: 1,
            syllableCount: 15,
            polysyllableCount: 5,
            averageSyllablesPerWord: 3.0,
            averageWordsPerSentence: 5.0,
            longWordCount: 4,
            syllableHistogram: [],
        );
        $result = $formula->calculate($hardStats, $lang);
        $this->assertSame('Graduate', $result->interpretation);
    }

    public function test_dale_chall_zero_words(): void
    {
        $formula = new DaleChall();
        $lang = $this->makeLanguage('en-us');
        $emptyStats = new TextStatistics(0, 0, 0, 0, 0, 0.0, 0.0, 0, []);
        $result = $formula->calculate($emptyStats, $lang);
        $this->assertEqualsWithDelta(0.0, $result->score, 0.1);
    }

    public function test_dale_chall_supported_languages(): void
    {
        $formula = new DaleChall();
        $langs = $formula->supportedLanguages();
        $this->assertContains('en-us', $langs);
        $this->assertContains('en-gb', $langs);
    }

    public function test_spache_basic(): void
    {
        $formula = new Spache();
        $lang = $this->makeLanguage('en-us');
        $result = $formula->calculate($this->stats, $lang);

        $difficultPct = (($this->stats->wordCount - ($this->stats->syllableHistogram[1] ?? 0)) / $this->stats->wordCount) * 100.0;
        $expected = 0.121 * $this->stats->averageWordsPerSentence + 0.082 * $difficultPct + 0.659;

        $this->assertEqualsWithDelta($expected, $result->score, 0.1);
        $this->assertSame('spache', $result->formulaName);
        $this->assertNotNull($result->gradeLevel);
    }

    public function test_spache_grade_level_clamping(): void
    {
        $formula = new Spache();
        $lang = $this->makeLanguage('en-us');

        // Very hard text → high score → clamped to 5.0
        $hardStats = new TextStatistics(
            letterCount: 100,
            wordCount: 10,
            sentenceCount: 1,
            syllableCount: 50,
            polysyllableCount: 8,
            averageSyllablesPerWord: 5.0,
            averageWordsPerSentence: 10.0,
            longWordCount: 5,
            syllableHistogram: [2 => 4, 3 => 3, 4 => 2, 5 => 1],
        );
        $result = $formula->calculate($hardStats, $lang);
        $this->assertLessThanOrEqual(5.0, $result->gradeLevel);

        // Very easy text → low score → clamped to 0.0
        $easyStats = new TextStatistics(
            letterCount: 20,
            wordCount: 10,
            sentenceCount: 5,
            syllableCount: 10,
            polysyllableCount: 0,
            averageSyllablesPerWord: 1.0,
            averageWordsPerSentence: 2.0,
            longWordCount: 0,
            syllableHistogram: [1 => 10],
        );
        $result = $formula->calculate($easyStats, $lang);
        $this->assertGreaterThanOrEqual(0.0, $result->gradeLevel);
    }

    public function test_spache_interpretation_ranges(): void
    {
        $formula = new Spache();
        $lang = $this->makeLanguage('en-us');

        $testCases = [
            [5,  '1st Grade',        [1 => 5]],
            [15, '2nd Grade',        [1 => 15]],
            [18, '3rd Grade',        [1 => 18]],
            [23, '4th Grade',        [1 => 23]],
            [30, 'Above 4th Grade',  [1 => 30]],
        ];

        foreach ($testCases as [$wordCount, $expectedLabel, $histogram]) {
            $asw = 1.0;
            $stats = new TextStatistics(
                letterCount: $wordCount * 5,
                wordCount: $wordCount,
                sentenceCount: 1,
                syllableCount: $wordCount,
                polysyllableCount: $wordCount - ($histogram[1] ?? 0),
                averageSyllablesPerWord: $asw,
                averageWordsPerSentence: (float) $wordCount,
                longWordCount: 0,
                syllableHistogram: $histogram,
            );
            $result = $formula->calculate($stats, $lang);
            $this->assertSame($expectedLabel, $result->interpretation, "wordCount={$wordCount}");
        }
    }

    public function test_spache_supported_languages(): void
    {
        $formula = new Spache();
        $langs = $formula->supportedLanguages();
        $this->assertContains('en-us', $langs);
        $this->assertContains('en-gb', $langs);
    }

    /** @return array<string, array{0: Formula}> */
    public static function formulaMetadataProvider(): array
    {
        return [
            'wiener_sachtextformel' => [new WienerSachtextformel()],
            'gulpease' => [new Gulpease()],
            'fernandez_huerta' => [new FernandezHuerta()],
            'szigriszt_pazos' => [new SzigrisztPazos()],
            'gutierrez_polini' => [new GutierrezPolini()],
            'crawford' => [new Crawford()],
            'fog_pl' => [new FogPL()],
            'osman' => [new Osman()],
            'dale_chall' => [new DaleChall()],
            'spache' => [new Spache()],
        ];
    }

    #[DataProvider('formulaMetadataProvider')]
    public function test_formula_metadata_is_non_empty(Formula $formula): void
    {
        $this->assertNotEmpty($formula->name());
        $this->assertNotEmpty($formula->description());
        $this->assertNotEmpty($formula->supportedLanguages());
    }
}
