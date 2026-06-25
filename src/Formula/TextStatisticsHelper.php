<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Formula;

use GlobusStudio\ReadSight\Text\TextStatistics;

final class TextStatisticsHelper
{
    public static function estimateDifficultPercentage(TextStatistics $stats): float
    {
        if ($stats->wordCount === 0) {
            return 0.0;
        }

        $easyWordCount = $stats->syllableHistogram[1] ?? 0;
        $difficultCount = $stats->wordCount - $easyWordCount;

        if ($difficultCount < 0) {
            $difficultCount = 0;
        }

        return ($difficultCount / $stats->wordCount) * 100.0;
    }
}
