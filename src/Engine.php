<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight;

use GlobusStudio\ReadSight\Exception\EmptyTextException;
use GlobusStudio\ReadSight\Exception\UnsupportedFormulaException;
use GlobusStudio\ReadSight\Exception\UnsupportedLanguageException;
use GlobusStudio\ReadSight\Formula\AutomatedReadabilityIndex;
use GlobusStudio\ReadSight\Formula\ColemanLiau;
use GlobusStudio\ReadSight\Formula\Crawford;
use GlobusStudio\ReadSight\Formula\DaleChall;
use GlobusStudio\ReadSight\Formula\FernandezHuerta;
use GlobusStudio\ReadSight\Formula\FleschKincaidGradeLevel;
use GlobusStudio\ReadSight\Formula\FleschReadingEase;
use GlobusStudio\ReadSight\Formula\FogPL;
use GlobusStudio\ReadSight\Formula\FormulaRegistry;
use GlobusStudio\ReadSight\Formula\FormulaResult;
use GlobusStudio\ReadSight\Formula\Gulpease;
use GlobusStudio\ReadSight\Formula\GunningFog;
use GlobusStudio\ReadSight\Formula\GutierrezPolini;
use GlobusStudio\ReadSight\Formula\Lix;
use GlobusStudio\ReadSight\Formula\Osman;
use GlobusStudio\ReadSight\Formula\SmogIndex;
use GlobusStudio\ReadSight\Formula\Spache;
use GlobusStudio\ReadSight\Formula\SzigrisztPazos;
use GlobusStudio\ReadSight\Formula\WienerSachtextformel;
use GlobusStudio\ReadSight\Hyphenation\Cache\JsonPatternCache;
use GlobusStudio\ReadSight\Hyphenation\Cache\PatternCache;
use GlobusStudio\ReadSight\Hyphenation\HyphenationExceptionsCollection;
use GlobusStudio\ReadSight\Hyphenation\Hyphenator;
use GlobusStudio\ReadSight\Hyphenation\LiangHyphenator;
use GlobusStudio\ReadSight\Hyphenation\PatternsCollection;
use GlobusStudio\ReadSight\Hyphenation\Source\PatTxtSource;
use GlobusStudio\ReadSight\Language\JsonLanguageRepository;
use GlobusStudio\ReadSight\Language\Language;
use GlobusStudio\ReadSight\Language\LanguageRepository;
use GlobusStudio\ReadSight\Text\TextSplitter;
use GlobusStudio\ReadSight\Text\TextStatistics;

final class Engine
{
    private static ?string $defaultPatternsDir = null;
    private static ?string $defaultLanguagesDir = null;
    private static ?string $defaultCacheDir = null;

    private readonly Language $language;
    private readonly Hyphenator $hyphenator;
    private readonly TextSplitter $textSplitter;
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
        $this->textSplitter = new TextSplitter($this->language);
        $this->formulaRegistry = $this->initializeFormulas();
    }

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
        $repository = new JsonLanguageRepository($languagesDir);

        return $repository->listCodes();
    }

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

    // --- Text / Syllable API (Stage 1) ---

    /** @return list<string> */
    public function splitWord(string $word): array
    {
        return $this->hyphenator->hyphenate($word);
    }

    public function syllableCount(string $word): int
    {
        return $this->hyphenator->countSyllables($word);
    }

    public function wordCount(string $text): int
    {
        return $this->textSplitter->countWords($text);
    }

    public function sentenceCount(string $text): int
    {
        return $this->textSplitter->countSentences($text);
    }

    public function letterCount(string $text): int
    {
        return $this->textSplitter->countLetters($text);
    }

    public function totalSyllables(string $text): int
    {
        $words = $this->textSplitter->splitWords($text);
        $total = 0;

        foreach ($words as $word) {
            $total += $this->hyphenator->countSyllables($word);
        }

        return $total;
    }

    public function averageSyllablesPerWord(string $text): float
    {
        $words = $this->textSplitter->splitWords($text);
        $wordCount = \count($words);

        if ($wordCount === 0) {
            return 0.0;
        }

        return $this->totalSyllables($text) / $wordCount;
    }

    public function averageWordsPerSentence(string $text): float
    {
        $wordCount = $this->textSplitter->countWords($text);
        $sentenceCount = $this->textSplitter->countSentences($text);

        if ($sentenceCount === 0) {
            return (float) $wordCount;
        }

        return $wordCount / $sentenceCount;
    }

    public function wordsWithNSyllables(string $text, int $n, bool $countProperNouns = true): int
    {
        $words = $this->textSplitter->splitWords($text);
        $count = 0;

        foreach ($words as $word) {
            if ($this->hyphenator->countSyllables($word) > $n) {
                if ($countProperNouns) {
                    $count++;
                } else {
                    $firstLetter = \mb_substr($word, 0, 1);
                    if ($firstLetter !== \mb_strtoupper($firstLetter)) {
                        $count++;
                    }
                }
            }
        }

        return $count;
    }

    public function polysyllableCount(string $text, bool $countProperNouns = true): int
    {
        return $this->wordsWithNSyllables($text, 2, $countProperNouns);
    }

    /** @return array<int, int> */
    public function histogramSyllables(string $text): array
    {
        $words = $this->textSplitter->splitWords($text);
        $histogram = [];

        foreach ($words as $word) {
            $syllables = $this->hyphenator->countSyllables($word);
            if ($syllables === 0) {
                continue;
            }
            $histogram[$syllables] = ($histogram[$syllables] ?? 0) + 1;
        }

        \ksort($histogram);

        return $histogram;
    }

    public function analyze(string $text): TextStatistics
    {
        $text = \trim($text);

        $words = $this->textSplitter->splitWords($text);
        $wordCount = \count($words);

        if ($wordCount === 0) {
            throw EmptyTextException::create();
        }

        $letterCount = $this->textSplitter->countLetters($text);
        $sentenceCount = $this->textSplitter->countSentences($text);

        $totalSyllables = 0;
        $polysyllableCount = 0;
        $histogram = [];

        foreach ($words as $word) {
            $syllables = $this->hyphenator->countSyllables($word);
            $totalSyllables += $syllables;

            if ($syllables > 2) {
                $polysyllableCount++;
            }

            if ($syllables > 0) {
                $histogram[$syllables] = ($histogram[$syllables] ?? 0) + 1;
            }
        }

        $sentenceCountForAverage = $sentenceCount === 0 ? 1 : $sentenceCount;

        \ksort($histogram);

        $lixConfig = $this->language->getFormulaConfig('lix');
        $longWordThreshold = 6;
        if (is_array($lixConfig) && isset($lixConfig['longWordThreshold']) && is_numeric($lixConfig['longWordThreshold'])) {
            $longWordThreshold = (int) $lixConfig['longWordThreshold'];
        }
        $longWordCount = $this->textSplitter->countLongWords($text, $longWordThreshold);

        return new TextStatistics(
            letterCount: $letterCount,
            wordCount: $wordCount,
            sentenceCount: $sentenceCount,
            syllableCount: $totalSyllables,
            polysyllableCount: $polysyllableCount,
            averageSyllablesPerWord: $totalSyllables / $wordCount,
            averageWordsPerSentence: $wordCount / $sentenceCountForAverage,
            longWordCount: $longWordCount,
            syllableHistogram: $histogram,
        );
    }

    /** @param array<string, string> $hyphenations */
    public function addHyphenations(array $hyphenations): void
    {
        if ($this->hyphenator instanceof LiangHyphenator) {
            $this->hyphenator->addHyphenations($hyphenations);
        }
    }

    // --- Formula API ---

    /** @throws UnsupportedFormulaException */
    public function score(string $formulaName, string $text): FormulaResult
    {
        $stats = $this->analyze($text);

        return $this->formulaRegistry->calculate($formulaName, $this->language, $stats);
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

    private function initializeFormulas(): FormulaRegistry
    {
        $registry = new FormulaRegistry();

        $registry->register(new FleschReadingEase());
        $registry->register(new FleschKincaidGradeLevel());
        $registry->register(new GunningFog());
        $registry->register(new SmogIndex());
        $registry->register(new ColemanLiau());
        $registry->register(new AutomatedReadabilityIndex());
        $registry->register(new Lix());
        $registry->register(new WienerSachtextformel());
        $registry->register(new Gulpease());
        $registry->register(new FernandezHuerta());
        $registry->register(new SzigrisztPazos());
        $registry->register(new GutierrezPolini());
        $registry->register(new Crawford());
        $registry->register(new FogPL());
        $registry->register(new Osman());
        $registry->register(new DaleChall());
        $registry->register(new Spache());

        return $registry;
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
