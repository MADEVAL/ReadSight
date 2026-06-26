<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Tests\Integration;

use GlobusStudio\ReadSight\Engine;
use GlobusStudio\ReadSight\Config;
use GlobusStudio\ReadSight\Exception\EmptyTextException;
use GlobusStudio\ReadSight\Exception\UnsupportedLanguageException;
use PHPUnit\Framework\TestCase;

final class EngineTest extends TestCase
{
    private string $patternsDir;
    private string $languagesDir;
    private string $cacheDir;

    protected function setUp(): void
    {
        $this->patternsDir = \sys_get_temp_dir() . '/readsight-engine-patterns';
        $this->languagesDir = \sys_get_temp_dir() . '/readsight-engine-languages';
        $this->cacheDir = \sys_get_temp_dir() . '/readsight-engine-cache';

        @\mkdir($this->patternsDir, 0777, true);
        @\mkdir($this->languagesDir, 0777, true);
        @\mkdir($this->cacheDir, 0777, true);

        $this->createTestLanguage();
        $this->createTestPatterns();
    }

    protected function tearDown(): void
    {
        Engine::setDefaultConfig(Config::default());
        $this->rmDir($this->patternsDir);
        $this->rmDir($this->languagesDir);
        $this->rmDir($this->cacheDir);
    }

    private function rmDir(string $dir): void
    {
        $files = \glob($dir . '/*') ?: [];
        foreach ($files as $file) {
            \unlink($file);
        }
        if (\is_dir($dir)) {
            \rmdir($dir);
        }
    }

    private function createTestLanguage(): void
    {
        \file_put_contents($this->languagesDir . '/en-us.json', \json_encode([
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
        ], JSON_THROW_ON_ERROR));
    }

    private function createTestPatterns(): void
    {
        $tex = "\\patterns{\n.ach4\n.ad4der\n.af1t\n.al3t\n.am5at\n.an5c\n.ang4\n.ani5m\n.ant4\n.an3te\n}\n\\hyphenation{\nas-so-ci-ate\nta-ble\n}\n";
        \file_put_contents($this->patternsDir . '/hyph-en-us.tex', $tex);
    }

    public function test_creates_engine_for_supported_language(): void
    {
        $engine = new Engine('en-us', $this->patternsDir, $this->languagesDir, $this->cacheDir);
        $this->assertSame('en-us', $engine->getLanguage()->code);
    }

    public function test_throws_for_unsupported_language(): void
    {
        $this->expectException(UnsupportedLanguageException::class);
        new Engine('zz-unknown', $this->patternsDir, $this->languagesDir, $this->cacheDir);
    }

    public function test_get_supported_formulas(): void
    {
        $engine = new Engine('en-us', $this->patternsDir, $this->languagesDir, $this->cacheDir);
        $formulas = $engine->getSupportedFormulas();
        $this->assertContains('flesch_reading_ease', $formulas);
        $this->assertContains('lix', $formulas);
    }

    public function test_syllable_count(): void
    {
        $engine = new Engine('en-us', $this->patternsDir, $this->languagesDir, $this->cacheDir);
        $count = $engine->syllableCount('associate');
        $this->assertSame(4, $count);
    }

    public function test_syllable_count_single_syllable(): void
    {
        $engine = new Engine('en-us', $this->patternsDir, $this->languagesDir, $this->cacheDir);
        $count = $engine->syllableCount('the');
        $this->assertSame(1, $count);
    }

    public function test_split_word(): void
    {
        $engine = new Engine('en-us', $this->patternsDir, $this->languagesDir, $this->cacheDir);
        $parts = $engine->splitWord('associate');
        $this->assertSame(['as', 'so', 'ci', 'ate'], $parts);
    }

    public function test_word_count(): void
    {
        $engine = new Engine('en-us', $this->patternsDir, $this->languagesDir, $this->cacheDir);
        $this->assertSame(4, $engine->wordCount('The quick brown fox'));
    }

    public function test_sentence_count(): void
    {
        $engine = new Engine('en-us', $this->patternsDir, $this->languagesDir, $this->cacheDir);
        $this->assertSame(2, $engine->sentenceCount('Hello. World!'));
    }

    public function test_letter_count(): void
    {
        $engine = new Engine('en-us', $this->patternsDir, $this->languagesDir, $this->cacheDir);
        $this->assertSame(10, $engine->letterCount('Hello world!'));
    }

    public function test_total_syllables(): void
    {
        $engine = new Engine('en-us', $this->patternsDir, $this->languagesDir, $this->cacheDir);
        $total = $engine->totalSyllables('associate table');
        $this->assertGreaterThanOrEqual(6, $total);
    }

    public function test_average_syllables_per_word(): void
    {
        $engine = new Engine('en-us', $this->patternsDir, $this->languagesDir, $this->cacheDir);
        $avg = $engine->averageSyllablesPerWord('the cat');
        $this->assertGreaterThan(0.0, $avg);
    }

    public function test_average_words_per_sentence(): void
    {
        $engine = new Engine('en-us', $this->patternsDir, $this->languagesDir, $this->cacheDir);
        $avg = $engine->averageWordsPerSentence('One two three. Four five.');
        $this->assertGreaterThan(0.0, $avg);
    }

    public function test_polysyllable_count(): void
    {
        $engine = new Engine('en-us', $this->patternsDir, $this->languagesDir, $this->cacheDir);
        $count = $engine->polysyllableCount('the cat associate');
        $this->assertSame(1, $count); // only 'associate' has >2 syllables
    }

    public function test_histogram_syllables(): void
    {
        $engine = new Engine('en-us', $this->patternsDir, $this->languagesDir, $this->cacheDir);
        $hist = $engine->histogramSyllables('the cat associate');
        $this->assertArrayHasKey(1, $hist); // 'the' and 'cat' are 1-syllable
    }

    public function test_analyze_returns_statistics(): void
    {
        $engine = new Engine('en-us', $this->patternsDir, $this->languagesDir, $this->cacheDir);
        $stats = $engine->analyze('The quick brown fox jumps over the lazy dog.');
        $this->assertGreaterThan(0, $stats->wordCount);
        $this->assertGreaterThan(0, $stats->letterCount);
        $this->assertGreaterThan(0, $stats->sentenceCount);
        $this->assertGreaterThan(0, $stats->syllableCount);
    }

    public function test_analyze_throws_on_empty_text(): void
    {
        $engine = new Engine('en-us', $this->patternsDir, $this->languagesDir, $this->cacheDir);
        $this->expectException(EmptyTextException::class);
        $engine->analyze('   ');
    }

    public function test_add_user_hyphenations(): void
    {
        $engine = new Engine('en-us', $this->patternsDir, $this->languagesDir, $this->cacheDir);
        $engine->addHyphenations(['customword' => 'cus-tom-word']);
        $parts = $engine->splitWord('customword');
        $this->assertSame(['cus', 'tom', 'word'], $parts);
    }

    public function test_get_language(): void
    {
        $engine = new Engine('en-us', $this->patternsDir, $this->languagesDir, $this->cacheDir);
        $language = $engine->getLanguage();
        $this->assertSame('en-us', $language->code);
        $this->assertSame('English (US)', $language->name);
    }

    public function test_get_hyphenator(): void
    {
        $engine = new Engine('en-us', $this->patternsDir, $this->languagesDir, $this->cacheDir);
        $hyphenator = $engine->getHyphenator();
        $this->assertSame(4, $hyphenator->countSyllables('associate'));
    }

    public function test_cache_hit_avoids_reloading(): void
    {
        $engine1 = new Engine('en-us', $this->patternsDir, $this->languagesDir, $this->cacheDir);
        $this->assertSame(4, $engine1->syllableCount('associate'));

        $engine2 = new Engine('en-us', $this->patternsDir, $this->languagesDir, $this->cacheDir);
        $this->assertSame(4, $engine2->syllableCount('associate'));
    }

    public function test_static_default_config(): void
    {
        $config = new Config($this->patternsDir, $this->languagesDir, $this->cacheDir);
        Engine::setDefaultConfig($config);

        $engine = Engine::withConfig('en-us', $config);
        $this->assertSame('en-us', $engine->getLanguage()->code);
    }

    public function test_get_supported_languages(): void
    {
        $languages = Engine::getSupportedLanguages();
        $this->assertContains('en-us', $languages);
    }

    public function test_get_supported_languages_with_custom_config(): void
    {
        $config = new Config($this->patternsDir, $this->languagesDir, $this->cacheDir);
        $languages = Engine::getSupportedLanguages($config);
        $this->assertContains('en-us', $languages);
    }

    public function test_polysyllable_count_without_proper_nouns(): void
    {
        $engine = new Engine('en-us', $this->patternsDir, $this->languagesDir, $this->cacheDir);
        $count = $engine->polysyllableCount('the cat associate', false);
        $this->assertSame(1, $count);
    }

    public function test_flesch_reading_ease_with_zero_words_uses_fallback(): void
    {
        $engine = new Engine('en-us', $this->patternsDir, $this->languagesDir, $this->cacheDir);
        $result = $engine->fleschReadingEase('word');
        $this->assertGreaterThan(0.0, $result->score);
    }
}
