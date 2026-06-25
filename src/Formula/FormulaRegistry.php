<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Formula;

use GlobusStudio\ReadSight\Exception\UnsupportedFormulaException;
use GlobusStudio\ReadSight\Language\Language;

final class FormulaRegistry
{
    /** @var array<string, Formula> */
    private array $formulas = [];

    public function register(Formula $formula): void
    {
        $this->formulas[$formula->name()] = $formula;
    }

    public function has(string $name): bool
    {
        return isset($this->formulas[$name]);
    }

    public function get(string $name): ?Formula
    {
        return $this->formulas[$name] ?? null;
    }

    /** @return list<string> */
    public function listNames(): array
    {
        return \array_keys($this->formulas);
    }

    /** @return list<string> */
    public function listForLanguage(Language $language): array
    {
        $result = [];

        foreach ($this->formulas as $name => $formula) {
            $langs = $formula->supportedLanguages();
            if ($langs === ['*'] || \in_array($language->code, $langs, true)) {
                $result[] = $name;
            }
        }

        return $result;
    }

    /** @throws UnsupportedFormulaException */
    public function calculate(string $name, Language $language, \GlobusStudio\ReadSight\Text\TextStatistics $stats): FormulaResult
    {
        $formula = $this->formulas[$name] ?? null;

        if ($formula === null || !$this->isSupportedForLanguage($formula, $language)) {
            throw UnsupportedFormulaException::forLanguage($name, $language->code);
        }

        return $formula->calculate($stats, $language);
    }

    private function isSupportedForLanguage(Formula $formula, Language $language): bool
    {
        $langs = $formula->supportedLanguages();

        if ($langs === ['*']) {
            return true;
        }

        return \in_array($language->code, $langs, true);
    }
}

