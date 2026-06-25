<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Tests\Unit\Formula;

use GlobusStudio\ReadSight\Formula\Crawford;
use GlobusStudio\ReadSight\Formula\FernandezHuerta;
use GlobusStudio\ReadSight\Formula\FogPL;
use GlobusStudio\ReadSight\Formula\Gulpease;
use GlobusStudio\ReadSight\Formula\GutierrezPolini;
use GlobusStudio\ReadSight\Formula\Osman;
use GlobusStudio\ReadSight\Formula\SzigrisztPazos;
use GlobusStudio\ReadSight\Formula\WienerSachtextformel;
use GlobusStudio\ReadSight\Language\Language;
use GlobusStudio\ReadSight\Text\TextStatistics;
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
        $expected = 200.0 - 2.0 * $asl - 1.5 * $avgLetters * 100.0 - 0.4 * $hardPct;
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
}
