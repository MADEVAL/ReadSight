<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Tests\Unit\Formula;

use GlobusStudio\ReadSight\Formula\GradeLevelInterpretation;
use PHPUnit\Framework\TestCase;

final class GradeLevelInterpretationTest extends TestCase
{
    public function test_for_score(): void
    {
        $cases = [
            [-5.0, 'Kindergarten'],
            [0.0,  'Kindergarten'],
            [0.5,  'Kindergarten'],
            [1.0,  'Kindergarten'],
            [1.1,  '1st Grade'],
            [2.0,  '1st Grade'],
            [2.1,  '2nd Grade'],
            [3.0,  '2nd Grade'],
            [4.0,  '3rd Grade'],
            [5.0,  '4th Grade'],
            [6.0,  '5th Grade'],
            [7.0,  '6th Grade'],
            [8.0,  '7th Grade'],
            [9.0,  '8th Grade'],
            [10.0, '9th Grade'],
            [11.0, '10th Grade'],
            [12.0, '11th Grade'],
            [13.0, '12th Grade'],
            [13.1, 'College'],
            [16.0, 'College'],
            [16.1, 'Graduate'],
            [25.0, 'Graduate'],
        ];

        foreach ($cases as [$score, $expected]) {
            $this->assertSame(
                $expected,
                GradeLevelInterpretation::forScore((float) $score),
                \sprintf('Score %.1f should map to "%s"', (float) $score, $expected),
            );
        }
    }
}
