<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight;

use GlobusStudio\ReadSight\Exception\UnsupportedFormulaException;
use GlobusStudio\ReadSight\Formula\FormulaRegistry;
use GlobusStudio\ReadSight\Formula\FormulaRegistryFactory;
use GlobusStudio\ReadSight\Formula\FormulaResult;
use GlobusStudio\ReadSight\Formula\WienerSachtextformel;
use GlobusStudio\ReadSight\Hyphenation\Cache\JsonPatternCache;
use GlobusStudio\ReadSight\Hyphenation\Hyphenator;
use GlobusStudio\ReadSight\Hyphenation\LiangHyphenator;
use GlobusStudio\ReadSight\Hyphenation\Source\TexSource;
use GlobusStudio\ReadSight\Language\JsonLanguageRepository;
use GlobusStudio\ReadSight\Language\Language;

use GlobusStudio\ReadSight\Syllable\CompositeSyllableCounter;
use GlobusStudio\ReadSight\Syllable\HeuristicSyllableCounter;
use GlobusStudio\ReadSight\Syllable\SyllableCounter;
use GlobusStudio\ReadSight\Syllable\TexSyllableCounter;
use GlobusStudio\ReadSight\Text\TextAnalyzer;
use GlobusStudio\ReadSight\Text\TextSplitter;
use GlobusStudio\ReadSight\Text\TextStatistics;

final class Engine
{
    private static ?Config $defaultConfig = null;

    private readonly Language $language;
    private readonly Hyphenator $hyphenator;
    private readonly SyllableCounter $syllableCounter;
    private readonly TextAnalyzer $text;
    private readonly FormulaRegistry $formulaRegistry;

    public function __construct(
        string $language,
        ?string $patternsDir = null,
        ?string $languagesDir = null,
        ?string $cacheDir = null,
    ) {
        $config = self::resolveConfig($patternsDir, $languagesDir, $cacheDir);

        $languageRepository = new JsonLanguageRepository($config->languagesDir);
        $this->language = $languageRepository->find($language);

        $this->hyphenator = $this->loadHyphenator($this->language, $config->patternsDir, $config->cacheDir);
        $this->syllableCounter = $this->loadSyllableCounter();
        $textSplitter = new TextSplitter($this->language);

        $this->text = new TextAnalyzer($this->hyphenator, $this->syllableCounter, $textSplitter, $this->language);
        $this->formulaRegistry = FormulaRegistryFactory::create();
    }

    public static function withConfig(string $language, Config $config): self
    {
        return new self($language, $config->patternsDir, $config->languagesDir, $config->cacheDir);
    }

    // --- Static configuration ---

    /**
     * Set a global default configuration for all Engine instances.
     * Call once at application bootstrap before creating any Engine.
     */
    public static function setDefaultConfig(Config $config): void
    {
        self::$defaultConfig = $config;
    }

    /** @deprecated Use setDefaultConfig(Config) instead */
    public static function setDefaultCacheDir(string $dir): void
    {
        $prev = self::$defaultConfig ?? Config::default();
        self::$defaultConfig = new Config($prev->patternsDir, $prev->languagesDir, $dir);
    }

    /** @deprecated Use setDefaultConfig(Config) instead */
    public static function setDefaultPatternsDir(string $dir): void
    {
        $prev = self::$defaultConfig ?? Config::default();
        self::$defaultConfig = new Config($dir, $prev->languagesDir, $prev->cacheDir);
    }

    /** @deprecated Use setDefaultConfig(Config) instead */
    public static function setDefaultLanguagesDir(string $dir): void
    {
        $prev = self::$defaultConfig ?? Config::default();
        self::$defaultConfig = new Config($prev->patternsDir, $dir, $prev->cacheDir);
    }

    /** @return list<string> */
    public static function getSupportedLanguages(?Config $config = null): array
    {
        $languagesDir = ($config ?? self::$defaultConfig ?? Config::default())->languagesDir;

        return (new JsonLanguageRepository($languagesDir))->listCodes();
    }

    // --- Accessors ---

    public function getLanguage(): Language
    {
        return $this->language;
    }

    public function getHyphenator(): Hyphenator
    {
        return $this->hyphenator;
    }

    public function getFormulaRegistry(): FormulaRegistry
    {
        return $this->formulaRegistry;
    }

    /** @return list<string> */
    public function getSupportedFormulas(): array
    {
        return $this->formulaRegistry->listForLanguage($this->language);
    }

    // --- Text / Syllable API → TextAnalyzer ---

    /** @return list<string> */
    public function splitWord(string $word): array
    {
        return $this->text->splitWord($word);
    }

    /** @return list<string> */
    public function splitSyllables(string $word): array
    {
        return $this->text->splitSyllables($word);
    }

    public function syllableCount(string $word): int
    {
        return $this->text->syllableCount($word);
    }

    public function wordCount(string $text): int
    {
        return $this->text->wordCount($text);
    }

    public function sentenceCount(string $text): int
    {
        return $this->text->sentenceCount($text);
    }

    public function letterCount(string $text): int
    {
        return $this->text->letterCount($text);
    }

    public function totalSyllables(string $text): int
    {
        return $this->text->totalSyllables($text);
    }

    public function averageSyllablesPerWord(string $text): float
    {
        return $this->text->averageSyllablesPerWord($text);
    }

    public function averageWordsPerSentence(string $text): float
    {
        return $this->text->averageWordsPerSentence($text);
    }

    public function polysyllableCount(string $text, bool $countProperNouns = true): int
    {
        return $this->text->polysyllableCount($text, $countProperNouns);
    }

    public function wordsWithMoreThanNSyllables(string $text, int $n, bool $countProperNouns = true): int
    {
        return $this->text->wordsWithMoreThanNSyllables($text, $n, $countProperNouns);
    }

    /** @return array<int, int> */
    public function histogramSyllables(string $text): array
    {
        return $this->text->histogramSyllables($text);
    }

    public function analyze(string $text): TextStatistics
    {
        return $this->text->analyze($text);
    }

    /** @param array<string, string> $hyphenations */
    public function addHyphenations(array $hyphenations): void
    {
        $this->text->addHyphenations($hyphenations);
    }

    // --- Formula API ---

    /** @throws UnsupportedFormulaException */
    public function score(string $formulaName, string $text): FormulaResult
    {
        return $this->formulaRegistry->calculate($formulaName, $this->language, $this->analyze($text));
    }

    /** @throws UnsupportedFormulaException */
    public function fleschReadingEase(string $text): FormulaResult
    {
        return $this->score('flesch_reading_ease', $text);
    }

    /** @throws UnsupportedFormulaException */
    public function fleschKincaidGradeLevel(string $text): FormulaResult
    {
        return $this->score('flesch_kincaid_grade_level', $text);
    }

    /** @throws UnsupportedFormulaException */
    public function gunningFog(string $text): FormulaResult
    {
        return $this->score('gunning_fog', $text);
    }

    /** @throws UnsupportedFormulaException */
    public function smogIndex(string $text): FormulaResult
    {
        return $this->score('smog', $text);
    }

    /** @throws UnsupportedFormulaException */
    public function colemanLiau(string $text): FormulaResult
    {
        return $this->score('coleman_liau', $text);
    }

    /** @throws UnsupportedFormulaException */
    public function automatedReadabilityIndex(string $text): FormulaResult
    {
        return $this->score('ari', $text);
    }

    /** @throws UnsupportedFormulaException */
    public function lix(string $text): FormulaResult
    {
        return $this->score('lix', $text);
    }

    /** @throws UnsupportedFormulaException */
    public function wienerSachtextformel(string $text, int $variant = 1): FormulaResult
    {
        $stats = $this->analyze($text);
        $formula = $this->formulaRegistry->get('wiener_sachtextformel');

        if ($formula instanceof WienerSachtextformel) {
            return $formula->calculateVariant($stats, $this->language, $variant);
        }

        throw UnsupportedFormulaException::forLanguage('wiener_sachtextformel', $this->language->code);
    }

    /** @throws UnsupportedFormulaException */
    public function gulpease(string $text): FormulaResult
    {
        return $this->score('gulpease', $text);
    }

    /** @throws UnsupportedFormulaException */
    public function fernandezHuerta(string $text): FormulaResult
    {
        return $this->score('fernandez_huerta', $text);
    }

    /** @throws UnsupportedFormulaException */
    public function szigrisztPazos(string $text): FormulaResult
    {
        return $this->score('szigriszt_pazos', $text);
    }

    /** @throws UnsupportedFormulaException */
    public function gutierrezPolini(string $text): FormulaResult
    {
        return $this->score('gutierrez_polini', $text);
    }

    /** @throws UnsupportedFormulaException */
    public function crawford(string $text): FormulaResult
    {
        return $this->score('crawford', $text);
    }

    /** @throws UnsupportedFormulaException */
    public function fogPL(string $text): FormulaResult
    {
        return $this->score('fog_pl', $text);
    }

    /** @throws UnsupportedFormulaException */
    public function daleChall(string $text): FormulaResult
    {
        return $this->score('dale_chall', $text);
    }

    /** @throws UnsupportedFormulaException */
    public function spache(string $text): FormulaResult
    {
        return $this->score('spache', $text);
    }

    /** @throws UnsupportedFormulaException */
    public function osman(string $text): FormulaResult
    {
        return $this->score('osman', $text);
    }

    // --- Private helpers ---

    private static function resolveConfig(?string $patternsDir, ?string $languagesDir, ?string $cacheDir): Config
    {
        $default = self::$defaultConfig ?? Config::default();

        return new Config(
            patternsDir: $patternsDir ?? $default->patternsDir,
            languagesDir: $languagesDir ?? $default->languagesDir,
            cacheDir: $cacheDir ?? $default->cacheDir,
        );
    }

    private function loadSyllableCounter(): SyllableCounter
    {
        $tex = new TexSyllableCounter($this->hyphenator);
        $mode = $this->language->syllableMode;

        if ($mode === 'tex' || $this->language->syllableHeuristics === null) {
            return $tex;
        }

        $heuristic = new HeuristicSyllableCounter($this->language->syllableHeuristics);

        if ($mode === 'heuristic') {
            return $heuristic;
        }

        return new CompositeSyllableCounter([$heuristic, $tex]);
    }

    private function loadHyphenator(Language $language, string $patternsDir, string $cacheDir): Hyphenator
    {
        $cache = new JsonPatternCache($cacheDir);
        $languageCode = $language->code;

        if ($cache->has($languageCode)) {
            $cached = $cache->get($languageCode);
            if ($cached !== null) {
                return new LiangHyphenator(
                    $cached['patterns'],
                    $cached['exceptions'],
                    $language->minHyphenLeft,
                    $language->minHyphenRight,
                );
            }
        }

        $texFile = $patternsDir . '/hyph-' . $languageCode . '.tex';
        $source = new TexSource($texFile);
        $loaded = $source->load();

        $cache->set($languageCode, $loaded);

        return new LiangHyphenator(
            $loaded['patterns'],
            $loaded['exceptions'],
            $language->minHyphenLeft,
            $language->minHyphenRight,
        );
    }
}
