<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Tests\Unit\Language;

use GlobusStudio\ReadSight\Exception\UnsupportedLanguageException;
use GlobusStudio\ReadSight\Language\JsonLanguageRepository;
use PHPUnit\Framework\TestCase;

final class JsonLanguageRepositoryTest extends TestCase
{
    private string $fixturesDir;

    protected function setUp(): void
    {
        $this->fixturesDir = \sys_get_temp_dir() . '/readsight-test-languages';
        if (!\is_dir($this->fixturesDir)) {
            \mkdir($this->fixturesDir, 0777, true);
        }

        \file_put_contents($this->fixturesDir . '/en-us.json', \json_encode([
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

        \file_put_contents($this->fixturesDir . '/ru.json', \json_encode([
            'code' => 'ru',
            'name' => 'Russian',
            'nativeName' => 'Русский',
            'script' => 'Cyrillic',
            'hyphenMins' => ['left' => 2, 'right' => 2],
            'letterPattern' => '[А-Яа-яЁё]',
            'wordSplitPattern' => "[^А-Яа-яЁё'’-]+",
            'sentenceBoundaryPattern' => '[.!?…]+',
            'formulas' => [
                'flesch_reading_ease' => ['enabled' => true, 'base' => 206.835, 'aslMult' => 1.52, 'aswMult' => 65.14],
                'lix' => ['enabled' => true, 'longWordThreshold' => 6],
            ],
        ], JSON_THROW_ON_ERROR));
    }

    protected function tearDown(): void
    {
        \array_map('unlink', \glob($this->fixturesDir . '/*.json') ?: []);
        \rmdir($this->fixturesDir);
    }

    public function test_find_returns_language(): void
    {
        $repo = new JsonLanguageRepository($this->fixturesDir);
        $language = $repo->find('en-us');
        $this->assertSame('en-us', $language->code);
        $this->assertSame('English (US)', $language->name);
    }

    public function test_find_normalizes_case(): void
    {
        $repo = new JsonLanguageRepository($this->fixturesDir);
        $language = $repo->find('EN-US');
        $this->assertSame('en-us', $language->code);
    }

    public function test_find_throws_for_unknown_language(): void
    {
        $repo = new JsonLanguageRepository($this->fixturesDir);
        $this->expectException(UnsupportedLanguageException::class);
        $repo->find('zz-unknown');
    }

    public function test_find_caches_results(): void
    {
        $repo = new JsonLanguageRepository($this->fixturesDir);
        $lang1 = $repo->find('en-us');
        $lang2 = $repo->find('en-us');
        $this->assertSame($lang1, $lang2);
    }

    public function test_list_codes_returns_all_codes(): void
    {
        $repo = new JsonLanguageRepository($this->fixturesDir);
        $codes = $repo->listCodes();
        $this->assertContains('en-us', $codes);
        $this->assertContains('ru', $codes);
        $this->assertCount(2, $codes);
    }

    public function test_exists_returns_true(): void
    {
        $repo = new JsonLanguageRepository($this->fixturesDir);
        $this->assertTrue($repo->exists('en-us'));
    }

    public function test_exists_returns_false(): void
    {
        $repo = new JsonLanguageRepository($this->fixturesDir);
        $this->assertFalse($repo->exists('zz'));
    }

    public function test_find_russian_language(): void
    {
        $repo = new JsonLanguageRepository($this->fixturesDir);
        $language = $repo->find('ru');
        $this->assertSame('ru', $language->code);
        $this->assertSame('Русский', $language->nativeName);
    }
}

