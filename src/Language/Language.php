<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Language;

final class Language
{
    public readonly string $code;
    public readonly string $name;
    public readonly string $nativeName;
    public readonly Script $script;
    public readonly int $minHyphenLeft;
    public readonly int $minHyphenRight;
    public readonly string $letterPattern;
    public readonly string $wordSplitPattern;
    public readonly string $sentenceBoundaryPattern;

    /** @var array<string, array<string, mixed>> */
    public readonly array $formulaConfigs;

    /** @var array<string, mixed>|null */
    public readonly ?array $syllableHeuristics;

    /**
     * @param array{
     *     code: string,
     *     name: string,
     *     nativeName: string,
     *     script: string,
     *     hyphenMins: array{left: int, right: int},
     *     letterPattern: string,
     *     wordSplitPattern: string,
     *     sentenceBoundaryPattern: string,
     *     formulas?: array<string, array<string, mixed>>,
     *     syllableHeuristics?: array<string, mixed>
     * } $data
     */
    public function __construct(array $data)
    {
        $this->code = $data['code'];
        $this->name = $data['name'];
        $this->nativeName = $data['nativeName'];
        $this->script = Script::from($data['script']);
        $this->minHyphenLeft = $data['hyphenMins']['left'];
        $this->minHyphenRight = $data['hyphenMins']['right'];
        $this->letterPattern = $data['letterPattern'];
        $this->wordSplitPattern = $data['wordSplitPattern'];
        $this->sentenceBoundaryPattern = $data['sentenceBoundaryPattern'];
        $this->formulaConfigs = $data['formulas'] ?? [];
        $this->syllableHeuristics = $data['syllableHeuristics'] ?? null;
    }

    public function supportsFormula(string $formulaName): bool
    {
        return isset($this->formulaConfigs[$formulaName]);
    }

    /** @return array<string, mixed>|null */
    public function getFormulaConfig(string $formulaName): ?array
    {
        return $this->formulaConfigs[$formulaName] ?? null;
    }

    /** @return list<string> */
    public function getSupportedFormulas(): array
    {
        return \array_keys($this->formulaConfigs);
    }
}
