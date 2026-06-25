<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Formula;

use GlobusStudio\ReadSight\Language\Language;
use GlobusStudio\ReadSight\Text\TextStatistics;

interface Formula
{
    public function name(): string;

    public function description(): string;

    /** @return list<string> Language codes where this formula is applicable; ['*'] means all */
    public function supportedLanguages(): array;

    public function calculate(TextStatistics $stats, Language $language): FormulaResult;
}

