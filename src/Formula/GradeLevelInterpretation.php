<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Formula;

final class GradeLevelInterpretation
{
    public static function forScore(float $score): string
    {
        return match (true) {
            $score <= 1.0 => 'Kindergarten',
            $score <= 2.0 => '1st Grade',
            $score <= 3.0 => '2nd Grade',
            $score <= 4.0 => '3rd Grade',
            $score <= 5.0 => '4th Grade',
            $score <= 6.0 => '5th Grade',
            $score <= 7.0 => '6th Grade',
            $score <= 8.0 => '7th Grade',
            $score <= 9.0 => '8th Grade',
            $score <= 10.0 => '9th Grade',
            $score <= 11.0 => '10th Grade',
            $score <= 12.0 => '11th Grade',
            $score <= 13.0 => '12th Grade',
            $score <= 16.0 => 'College',
            default => 'Graduate',
        };
    }
}
