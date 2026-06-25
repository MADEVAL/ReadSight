<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Tests\Integration;

use GlobusStudio\ReadSight\Engine;
use GlobusStudio\ReadSight\Exception\UnsupportedFormulaException;
use PHPUnit\Framework\TestCase;

final class FormulaIntegrationTest extends TestCase
{
    private const string DATA_DIR = __DIR__ . '/../../data';

    public function test_english_flesch_reading_ease(): void
    {
        $engine = new Engine('en-us', self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages');
        $result = $engine->fleschReadingEase('The quick brown fox jumps over the lazy dog.');

        $this->assertSame('flesch_reading_ease', $result->formulaName);
        $this->assertGreaterThan(0, $result->score);
        $this->assertLessThanOrEqual(120, $result->score);
        $this->assertNotEmpty($result->interpretation);
    }

    public function test_english_flesch_kincaid_grade_level(): void
    {
        $engine = new Engine('en-us', self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages');
        $result = $engine->fleschKincaidGradeLevel('The quick brown fox jumps over the lazy dog.');

        $this->assertNotNull($result->gradeLevel);
        $this->assertNotEmpty($result->interpretation);
    }

    public function test_english_gunning_fog(): void
    {
        $engine = new Engine('en-us', self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages');
        $result = $engine->gunningFog('The quick brown fox jumps over the lazy dog.');

        $this->assertGreaterThan(0, $result->score);
        $this->assertNotNull($result->gradeLevel);
    }

    public function test_english_smog(): void
    {
        $engine = new Engine('en-us', self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages');
        $result = $engine->smogIndex('The quick brown fox jumps over the lazy dog.');

        $this->assertGreaterThan(0, $result->score);
    }

    public function test_english_coleman_liau(): void
    {
        $engine = new Engine('en-us', self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages');
        $result = $engine->colemanLiau('The quick brown fox jumps over the lazy dog.');

        $this->assertGreaterThan(0, $result->score);
    }

    public function test_english_ari(): void
    {
        $engine = new Engine('en-us', self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages');
        $result = $engine->automatedReadabilityIndex('The quick brown fox jumps over the lazy dog.');

        $this->assertGreaterThan(0, $result->score);
    }

    public function test_english_lix(): void
    {
        $engine = new Engine('en-us', self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages');
        $result = $engine->lix('The quick brown fox jumps over the lazy dog.');

        $this->assertGreaterThan(0, $result->score);
        $this->assertNotEmpty($result->interpretation);
    }

    public function test_english_generic_score(): void
    {
        $engine = new Engine('en-us', self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages');
        $result = $engine->score('gunning_fog', 'The quick brown fox jumps over the lazy dog.');

        $this->assertSame('gunning_fog', $result->formulaName);
    }

    public function test_unsupported_formula_throws(): void
    {
        $engine = new Engine('en-us', self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages');
        $this->expectException(UnsupportedFormulaException::class);
        $engine->gulpease('Some text');
    }

    public function test_russian_flesch_reading_ease(): void
    {
        $engine = new Engine('ru', self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages');
        $result = $engine->fleschReadingEase('Текст на русском языке. Его нужно оценить.');

        $this->assertGreaterThan(0, $result->score);
        $this->assertSame('ru', $result->languageCode);
    }

    public function test_russian_generic_formulas(): void
    {
        $engine = new Engine('ru', self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages');

        $fog = $engine->gunningFog('Текст на русском языке. Это тест.');
        $this->assertGreaterThan(0, $fog->score);

        $smog = $engine->smogIndex('Текст на русском языке. Это тест.');
        $this->assertGreaterThan(0, $smog->score);

        $ari = $engine->automatedReadabilityIndex('Текст на русском языке. Это тест.');
        $this->assertGreaterThan(0, $ari->score);
    }

    public function test_german_flesch_reading_ease(): void
    {
        $engine = new Engine('de-1996', self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages');
        $result = $engine->fleschReadingEase('Dies ist ein deutscher Text. Er enthält Sätze.');

        $this->assertGreaterThan(0, $result->score);
        $this->assertSame('de-1996', $result->languageCode);
    }

    public function test_get_supported_formulas(): void
    {
        $engine = new Engine('en-us', self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages');
        $formulas = $engine->getSupportedFormulas();

        $this->assertContains('flesch_reading_ease', $formulas);
        $this->assertContains('gunning_fog', $formulas);
        $this->assertContains('smog', $formulas);
        $this->assertContains('coleman_liau', $formulas);
        $this->assertContains('ari', $formulas);
        $this->assertContains('lix', $formulas);
    }

    public function test_formula_inputs_contain_relevant_data(): void
    {
        $engine = new Engine('en-us', self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages');
        $result = $engine->fleschReadingEase('The quick brown fox jumps over the lazy dog.');

        $this->assertArrayHasKey('asl', $result->inputs);
        $this->assertArrayHasKey('asw', $result->inputs);
        $this->assertGreaterThan(0, $result->inputs['asl']);
        $this->assertGreaterThan(0, $result->inputs['asw']);
    }

    public function test_all_english_formulas_run_without_error(): void
    {
        $engine = new Engine('en-us', self::DATA_DIR . '/patterns', self::DATA_DIR . '/languages');
        $text = 'The quick brown fox jumps over the lazy dog. This is a simple test.';

        $formulas = [
            'fleschReadingEase',
            'fleschKincaidGradeLevel',
            'gunningFog',
            'smogIndex',
            'colemanLiau',
            'automatedReadabilityIndex',
            'lix',
        ];

        foreach ($formulas as $method) {
            $result = $engine->{$method}($text);
            $this->assertInstanceOf(\GlobusStudio\ReadSight\Formula\FormulaResult::class, $result, "Method: {$method}");
        }
    }
}
