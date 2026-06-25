<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Tests\Unit\Language;

use GlobusStudio\ReadSight\Language\Language;
use GlobusStudio\ReadSight\Language\Script;
use PHPUnit\Framework\TestCase;

final class LanguageTest extends TestCase
{
    /**
     * @return array{
     *     code: string,
     *     name: string,
     *     nativeName: string,
     *     script: string,
     *     hyphenMins: array{left: int, right: int},
     *     letterPattern: string,
     *     wordSplitPattern: string,
     *     sentenceBoundaryPattern: string,
     *     formulas?: array<string, array<string, mixed>>
     * }
     */
    private function getValidData(): array
    {
        return [
            'code' => 'en-us',
            'name' => 'English (US)',
            'nativeName' => 'English (US)',
            'script' => 'Latin',
            'hyphenMins' => ['left' => 2, 'right' => 2],
            'letterPattern' => '[A-Za-z]',
            'wordSplitPattern' => "[^A-Za-z'’-]+",
            'sentenceBoundaryPattern' => '[.!?]+',
            'formulas' => [
                'flesch_reading_ease' => [
                    'enabled' => true,
                    'base' => 206.835,
                    'aslMult' => 1.015,
                    'aswMult' => 84.6,
                ],
                'lix' => [
                    'enabled' => true,
                    'longWordThreshold' => 6,
                ],
            ],
        ];
    }

    public function test_creates_from_valid_data(): void
    {
        $language = new Language($this->getValidData());
        $this->assertSame('en-us', $language->code);
        $this->assertSame('English (US)', $language->name);
        $this->assertSame(Script::Latin, $language->script);
        $this->assertSame(2, $language->minHyphenLeft);
        $this->assertSame(2, $language->minHyphenRight);
    }

    public function test_supports_formula_returns_true_for_enabled(): void
    {
        $language = new Language($this->getValidData());
        $this->assertTrue($language->supportsFormula('flesch_reading_ease'));
        $this->assertTrue($language->supportsFormula('lix'));
    }

    public function test_supports_formula_returns_false_for_missing(): void
    {
        $language = new Language($this->getValidData());
        $this->assertFalse($language->supportsFormula('wiener_sachtextformel'));
    }

    public function test_get_formula_config_returns_config_array(): void
    {
        $language = new Language($this->getValidData());
        $config = $language->getFormulaConfig('flesch_reading_ease');
        $this->assertIsArray($config);
        $this->assertSame(206.835, $config['base']);
    }

    public function test_get_formula_config_returns_null_for_missing(): void
    {
        $language = new Language($this->getValidData());
        $this->assertNull($language->getFormulaConfig('nonexistent'));
    }

    public function test_get_supported_formulas_returns_list(): void
    {
        $language = new Language($this->getValidData());
        $formulas = $language->getSupportedFormulas();
        $this->assertContains('flesch_reading_ease', $formulas);
        $this->assertContains('lix', $formulas);
    }

    public function test_creates_without_formulas(): void
    {
        $data = $this->getValidData();
        unset($data['formulas']);
        $language = new Language($data);
        $this->assertSame([], $language->formulaConfigs);
        $this->assertFalse($language->supportsFormula('anything'));
    }

    public function test_script_is_correct_enum_value(): void
    {
        $language = new Language($this->getValidData());
        $this->assertSame(Script::Latin, $language->script);
    }
}

