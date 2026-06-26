<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Tests\Unit\Syllable;

use GlobusStudio\ReadSight\Syllable\CompositeSyllableCounter;
use GlobusStudio\ReadSight\Syllable\HeuristicSyllableCounter;
use GlobusStudio\ReadSight\Syllable\SyllableCounter;
use PHPUnit\Framework\TestCase;

final class CompositeSyllableCounterTest extends TestCase
{
    private function makeHeuristic(): HeuristicSyllableCounter
    {
        return new HeuristicSyllableCounter([
            'problemWords' => [
                'banana' => 3,
                'extraordinary' => 6,
            ],
            'subtractPatterns' => ['eous$'],
            'addPatterns' => ['ia'],
            'prefixes' => ['un' => 1],
            'suffixes' => ['tion' => 1],
            'vowelPattern' => '[aeiouy]',
        ]);
    }

    private function makeTexStub(): SyllableCounter
    {
        return new readonly class implements SyllableCounter {
            public function countSyllables(string $word): int
            {
                if ($word === '') {
                    return 0;
                }

                return \max(1, (int) \floor(\mb_strlen($word) / 2));
            }

            public function splitSyllables(string $word): array
            {
                return $word === '' ? [] : [$word];
            }
        };
    }

    // --- countSyllables: heuristic override for problem words ---

    public function test_problem_word_uses_heuristic(): void
    {
        $heuristic = $this->makeHeuristic();
        $tex = $this->makeTexStub();
        $composite = new CompositeSyllableCounter([$heuristic, $tex]);

        $this->assertSame(3, $composite->countSyllables('banana'));
        $this->assertSame(6, $composite->countSyllables('extraordinary'));
    }

    // --- countSyllables: non-problem word uses heuristic algorithm ---

    public function test_non_problem_word_uses_heuristic_algorithm(): void
    {
        $heuristic = $this->makeHeuristic();
        $tex = $this->makeTexStub();
        $composite = new CompositeSyllableCounter([$heuristic, $tex]);

        $this->assertSame(3, $composite->countSyllables('computer'));
        $this->assertSame(1, $composite->countSyllables('cat'));
    }

    // --- splitSyllables: heuristic override for problem words ---

    public function test_problem_word_split_uses_heuristic(): void
    {
        $heuristic = $this->makeHeuristic();
        $tex = $this->makeTexStub();
        $composite = new CompositeSyllableCounter([$heuristic, $tex]);

        $parts = $composite->splitSyllables('banana');
        $this->assertCount(3, $parts);
        $this->assertSame('banana', \implode('', $parts));
    }

    // --- splitSyllables: non-problem word uses heuristic algorithm ---

    public function test_non_problem_word_split_uses_heuristic_algorithm(): void
    {
        $heuristic = $this->makeHeuristic();
        $tex = $this->makeTexStub();
        $composite = new CompositeSyllableCounter([$heuristic, $tex]);

        $parts = $composite->splitSyllables('computer');
        $this->assertCount(3, $parts);
        $this->assertSame('computer', \implode('', $parts));
    }

    // --- chain without heuristic uses last counter ---

    public function test_chain_without_heuristic_uses_last(): void
    {
        $tex = $this->makeTexStub();
        $composite = new CompositeSyllableCounter([$tex]);

        $this->assertSame(2, $composite->countSyllables('word'));
        $this->assertSame(['hello'], $composite->splitSyllables('hello'));
    }

    // --- heuristic without relevant problemWords falls through ---

    public function test_heuristic_without_matching_problem_word_falls_through(): void
    {
        $heuristic = $this->makeHeuristic();
        $tex = $this->makeTexStub();
        $composite = new CompositeSyllableCounter([$heuristic, $tex]);

        $this->assertSame(1, $composite->countSyllables('dog'));
    }

    // --- empty word ---

    public function test_empty_word(): void
    {
        $heuristic = $this->makeHeuristic();
        $tex = $this->makeTexStub();
        $composite = new CompositeSyllableCounter([$heuristic, $tex]);

        $this->assertSame(0, $composite->countSyllables(''));
        $this->assertSame([], $composite->splitSyllables(''));
    }

    // --- empty chain ---

    public function test_empty_chain_returns_one(): void
    {
        $composite = new CompositeSyllableCounter([]);
        $this->assertSame(1, $composite->countSyllables('word'));
    }
}
