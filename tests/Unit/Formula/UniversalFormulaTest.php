<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Tests\Unit\Formula;

use GlobusStudio\ReadSight\Formula\AutomatedReadabilityIndex;
use GlobusStudio\ReadSight\Formula\ColemanLiau;
use GlobusStudio\ReadSight\Formula\FleschKincaidGradeLevel;
use GlobusStudio\ReadSight\Formula\FleschReadingEase;
use GlobusStudio\ReadSight\Formula\FormulaRegistry;
use GlobusStudio\ReadSight\Formula\FormulaResult;
use GlobusStudio\ReadSight\Formula\GunningFog;
use GlobusStudio\ReadSight\Formula\Lix;
use GlobusStudio\ReadSight\Formula\SmogIndex;
use GlobusStudio\ReadSight\Language\Language;
use GlobusStudio\ReadSight\Text\TextStatistics;
use PHPUnit\Framework\TestCase;

final class UniversalFormulaTest extends TestCase
{
    private Language $language;
    private TextStatistics $stats;

    protected function setUp(): void
    {
        $this->language = new Language([
            'code' => 'en-us',
            'name' => 'English (US)',
            'nativeName' => 'English (US)',
            'script' => 'Latin',
            'hyphenMins' => ['left' => 2, 'right' => 2],
            'letterPattern' => '[A-Za-z]',
            'wordSplitPattern' => "[^A-Za-z'’-]+",
            'sentenceBoundaryPattern' => '[.!?]+',
            'formulas' => [
                'flesch_reading_ease' => ['enabled' => true, 'base' => 206.835, 'aslMult' => 1.015, 'aswMult' => 84.6],
                'lix' => ['enabled' => true, 'longWordThreshold' => 6],
            ],
        ]);

        $this->stats = new TextStatistics(
            letterCount: 45,
            wordCount: 10,
            sentenceCount: 2,
            syllableCount: 15,
            polysyllableCount: 2,
            averageSyllablesPerWord: 1.5,
            averageWordsPerSentence: 5.0,
            longWordCount: 3,
            syllableHistogram: [1 => 5, 2 => 3, 3 => 2],
        );
    }

    public function test_flesch_reading_ease_with_english_coefficients(): void
    {
        $formula = new FleschReadingEase();
        $result = $formula->calculate($this->stats, $this->language);

        $expected = 206.835 - 1.015 * 5.0 - 84.6 * 1.5;
        $this->assertEqualsWithDelta($expected, $result->score, 0.1);
        $this->assertSame('flesch_reading_ease', $result->formulaName);
        $this->assertSame('en-us', $result->languageCode);
    }

    public function test_flesch_reading_ease_with_german_coefficients(): void
    {
        $german = new Language([
            'code' => 'de-1996',
            'name' => 'German',
            'nativeName' => 'Deutsch',
            'script' => 'Latin',
            'hyphenMins' => ['left' => 2, 'right' => 2],
            'letterPattern' => '[A-Za-zÄÖÜäöüß]',
            'wordSplitPattern' => "[^A-Za-zÄÖÜäöüß'’-]+",
            'sentenceBoundaryPattern' => '[.!?]+',
            'formulas' => [
                'flesch_reading_ease' => ['enabled' => true, 'base' => 180.0, 'aslMult' => 1.0, 'aswMult' => 58.5],
            ],
        ]);

        $formula = new FleschReadingEase();
        $result = $formula->calculate($this->stats, $german);

        $expected = 180.0 - 1.0 * 5.0 - 58.5 * 1.5;
        $this->assertEqualsWithDelta($expected, $result->score, 0.1);
    }

    public function test_flesch_kincaid_grade_level(): void
    {
        $formula = new FleschKincaidGradeLevel();
        $result = $formula->calculate($this->stats, $this->language);

        $expected = 0.39 * 5.0 + 11.8 * 1.5 - 15.59;
        $this->assertEqualsWithDelta($expected, $result->score, 0.1);
        $this->assertNotNull($result->gradeLevel);
    }

    public function test_gunning_fog(): void
    {
        $formula = new GunningFog();
        $result = $formula->calculate($this->stats, $this->language);

        $polyPct = (2 / 10) * 100.0;
        $expected = 0.4 * (5.0 + $polyPct);
        $this->assertEqualsWithDelta($expected, $result->score, 0.1);
        $this->assertNotNull($result->gradeLevel);
    }

    public function test_gunning_fog_zero_words(): void
    {
        $emptyStats = new TextStatistics(0, 0, 0, 0, 0, 0.0, 0.0, 0, []);
        $formula = new GunningFog();
        $result = $formula->calculate($emptyStats, $this->language);
        $this->assertEqualsWithDelta(0.0, $result->score, 0.1);
    }

    public function test_smog_index(): void
    {
        $formula = new SmogIndex();
        $result = $formula->calculate($this->stats, $this->language);

        $expected = 1.0430 * \sqrt(2 * (30.0 / 2)) + 3.1291;
        $this->assertEqualsWithDelta($expected, $result->score, 0.1);
    }

    public function test_coleman_liau(): void
    {
        $formula = new ColemanLiau();
        $result = $formula->calculate($this->stats, $this->language);

        $L = (45.0 / 10.0) * 100.0;
        $S = (2.0 / 10.0) * 100.0;
        $expected = 0.0588 * $L - 0.296 * $S - 15.8;
        $this->assertEqualsWithDelta($expected, $result->score, 0.1);
    }

    public function test_automated_readability_index(): void
    {
        $formula = new AutomatedReadabilityIndex();
        $result = $formula->calculate($this->stats, $this->language);

        $expected = 4.71 * (45.0 / 10.0) + 0.5 * (10.0 / 2.0) - 21.43;
        $this->assertEqualsWithDelta($expected, $result->score, 0.1);
    }

    public function test_lix(): void
    {
        $formula = new Lix();
        $result = $formula->calculate($this->stats, $this->language);

        $longWordPct = (3.0 / 10.0) * 100.0;
        $expected = 5.0 + $longWordPct;
        $this->assertEqualsWithDelta($expected, $result->score, 0.01);
    }

    public function test_formula_registry_register_and_get(): void
    {
        $registry = new FormulaRegistry();
        $registry->register(new FleschReadingEase());

        $this->assertTrue($registry->has('flesch_reading_ease'));
        $this->assertNotNull($registry->get('flesch_reading_ease'));
        $this->assertFalse($registry->has('nonexistent'));
    }

    public function test_formula_registry_list_for_language(): void
    {
        $registry = new FormulaRegistry();
        $registry->register(new Lix());

        $formulas = $registry->listForLanguage($this->language);
        $this->assertContains('lix', $formulas);
    }

    public function test_formula_registry_list_names(): void
    {
        $registry = new FormulaRegistry();
        $registry->register(new GunningFog());
        $registry->register(new Lix());

        $names = $registry->listNames();
        $this->assertContains('gunning_fog', $names);
        $this->assertContains('lix', $names);
    }

    public function test_formula_result_dto(): void
    {
        $result = new FormulaResult(
            formulaName: 'test',
            languageCode: 'en-us',
            score: 82.5,
            gradeLevel: 6.0,
            interpretation: 'Easy',
            gradeLabel: '6th Grade',
            inputs: ['asl' => 5.0, 'asw' => 1.5],
        );

        $this->assertSame('test', $result->formulaName);
        $this->assertSame(82.5, $result->score);
        $this->assertSame(6.0, $result->gradeLevel);
        $this->assertSame('Easy', $result->interpretation);
    }
}

