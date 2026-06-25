<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Tests\Unit\Hyphenation;

use GlobusStudio\ReadSight\Exception\PatternFileNotFoundException;
use GlobusStudio\ReadSight\Hyphenation\Source\PatTxtSource;
use PHPUnit\Framework\TestCase;

final class PatTxtSourceTest extends TestCase
{
    private string $fixturesDir;

    protected function setUp(): void
    {
        $this->fixturesDir = \sys_get_temp_dir() . '/readsight-test-patsrc';
        if (!\is_dir($this->fixturesDir)) {
            \mkdir($this->fixturesDir, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        $files = \glob($this->fixturesDir . '/*') ?: [];
        foreach ($files as $file) {
            \unlink($file);
        }
        if (\is_dir($this->fixturesDir)) {
            \rmdir($this->fixturesDir);
        }
    }

    public function test_throws_when_pat_file_not_found(): void
    {
        $source = new PatTxtSource($this->fixturesDir . '/nonexistent.pat.txt');
        $this->expectException(PatternFileNotFoundException::class);
        $source->load();
    }

    public function test_parses_simple_pattern(): void
    {
        \file_put_contents($this->fixturesDir . '/test.pat.txt', ".a4b\na5ban\n");

        $source = new PatTxtSource($this->fixturesDir . '/test.pat.txt');
        $result = $source->load();

        $this->assertSame(2, $result['patterns']->count());
        $this->assertSame(4, $result['maxPatternLength']);
    }

    public function test_parses_pattern_weights_correctly(): void
    {
        \file_put_contents($this->fixturesDir . '/test.pat.txt', "a5ban\n");

        $source = new PatTxtSource($this->fixturesDir . '/test.pat.txt');
        $result = $source->load();

        $this->assertSame('05000', $result['patterns']->getWeights('aban'));
    }

    public function test_skips_comment_lines(): void
    {
        \file_put_contents($this->fixturesDir . '/test.pat.txt', "% comment line\n.e2d\n% another comment\n");

        $source = new PatTxtSource($this->fixturesDir . '/test.pat.txt');
        $result = $source->load();

        $this->assertSame(1, $result['patterns']->count());
    }

    public function test_skips_empty_lines(): void
    {
        \file_put_contents($this->fixturesDir . '/test.pat.txt', "\n\na5ban\n\n");

        $source = new PatTxtSource($this->fixturesDir . '/test.pat.txt');
        $result = $source->load();

        $this->assertSame(1, $result['patterns']->count());
    }

    public function test_parses_hyp_file(): void
    {
        \file_put_contents($this->fixturesDir . '/test.pat.txt', "a5ban\n");
        \file_put_contents($this->fixturesDir . '/test.hyp.txt', "as-so-ci-ate\nta-ble\n");

        $source = new PatTxtSource($this->fixturesDir . '/test.pat.txt', $this->fixturesDir . '/test.hyp.txt');
        $result = $source->load();

        $this->assertSame(2, $result['exceptions']->count());
        $this->assertTrue($result['exceptions']->has('associate'));
        $this->assertSame('as-so-ci-ate', $result['exceptions']->get('associate'));
    }

    public function test_null_hyp_file_returns_empty_exceptions(): void
    {
        \file_put_contents($this->fixturesDir . '/test.pat.txt', "a5ban\n");

        $source = new PatTxtSource($this->fixturesDir . '/test.pat.txt');
        $result = $source->load();

        $this->assertTrue($result['exceptions']->isEmpty());
    }

    public function test_ignores_non_digit_patterns(): void
    {
        \file_put_contents($this->fixturesDir . '/test.pat.txt', "abc\n"); // no digits

        $source = new PatTxtSource($this->fixturesDir . '/test.pat.txt');
        $result = $source->load();

        $this->assertSame(0, $result['patterns']->count());
    }
}
