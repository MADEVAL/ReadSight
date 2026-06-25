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
use GlobusStudio\ReadSight\Hyphenation\Source\PatTxtSource;
use GlobusStudio\ReadSight\Language\JsonLanguageRepository;
use GlobusStudio\ReadSight\Language\Language;
use GlobusStudio\ReadSight\Language\LanguageRepository;
use GlobusStudio\ReadSight\Text\TextAnalyzer;
use GlobusStudio\ReadSight\Text\TextSplitter;
use GlobusStudio\ReadSight\Text\TextStatistics;

final class Engine
{
    private static ?string $defaultPatternsDir = null;
    private static ?string $defaultLanguagesDir = null;
    private static ?string $defaultCacheDir = null;

    private readonly Language $language;
    private readonly Hyphenator $hyphenator;
    private readonly TextAnalyzer $text;
    private readonly LanguageRepository $languageRepository;
    private readonly FormulaRegistry $formulaRegistry;

    public function __construct(
        string $language,
        ?string $patternsDir = null,
        ?string $languagesDir = null,
        ?string $cacheDir = null,
    ) {
        $languagesDir ??= self::$defaultLanguagesDir ?? __DIR__ . '/../data/languages';
        $patternsDir ??= self::$defaultPatternsDir ?? __DIR__ . '/../data/patterns';
        $cacheDir ??= self::$defaultCacheDir ?? __DIR__ . '/../cache';

        $this->languageRepository = new JsonLanguageRepository($languagesDir);
        $this->language = $this->languageRepository->find($language);

        $this->hyphenator = $this->loadHyphenator($this->language, $patternsDir, $cacheDir);
        $textSplitter = new TextSplitter($this->language);

        $this->text = new TextAnalyzer($this->hyphenator, $textSplitter, $this->language);
        $this->formulaRegistry = FormulaRegistryFactory::create();
    }

    // --- Static defaults ---

    public static function setDefaultCacheDir(string $dir): void
    {
        self::$defaultCacheDir = $dir;
    }

    public static function setDefaultPatternsDir(string $dir): void
    {
        self::$defaultPatternsDir = $dir;
    }

    public static function setDefaultLanguagesDir(string $dir): void
    {
        self::$defaultLanguagesDir = $dir;
    }

    /** @return list<string> */
    public static function getSupportedLanguages(): array
    {
        $languagesDir = self::$defaultLanguagesDir ?? __DIR__ . '/../data/languages';

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

    public function wordsWithNSyllables(string $text, int $n, bool $countProperNouns = true): int
    {
        return $this->text->wordsWithNSyllables($text, $n, $countProperNouns);
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

        $patFile = $patternsDir . '/hyph-' . $languageCode . '.pat.txt';
        $hypFile = $patternsDir . '/hyph-' . $languageCode . '.hyp.txt';

        if (!\file_exists($hypFile)) {
            $hypFile = null;
        }

        $source = new PatTxtSource($patFile, $hypFile);
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
