<?php

declare(strict_types=1);

/**
 * Language config generator for ReadSight.
 * Generates JSON configs for all languages with .tex files.
 *
 * Usage: php tools/generate-languages.php
 */

$baseDir = __DIR__ . '/..';
$patternsDir = $baseDir . '/data/patterns';
$outputDir = $baseDir . '/data/languages';

if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

$minHyphens = [
    'af' => [1, 2], 'ar' => [0, 0], 'as' => [1, 1], 'bg' => [2, 2],
    'bn' => [1, 1], 'ca' => [2, 2], 'cop' => [1, 1], 'cs' => [2, 3],
    'cy' => [2, 3], 'da' => [2, 2], 'de' => [2, 2], 'de-1901' => [2, 2],
    'de-1996' => [2, 2], 'de-ch-1901' => [2, 2], 'el-monoton' => [1, 1],
    'el-polyton' => [1, 1], 'en-gb' => [2, 2], 'en-us' => [2, 2],
    'eo' => [2, 2], 'es' => [2, 2], 'et' => [2, 3], 'eu' => [2, 2],
    'fa' => [0, 0], 'fi' => [2, 2], 'fr' => [2, 3], 'fur' => [2, 2],
    'ga' => [2, 3], 'gl' => [2, 2], 'grc' => [1, 1], 'gu' => [1, 1],
    'hi' => [1, 1], 'hr' => [2, 2], 'hsb' => [2, 2], 'hu' => [2, 2],
    'hy' => [1, 2], 'ia' => [2, 2], 'id' => [2, 2], 'is' => [2, 2],
    'it' => [2, 2], 'ka' => [1, 2], 'kmr' => [2, 2], 'kn' => [1, 1],
    'la' => [2, 2], 'la-x-classic' => [2, 2], 'lt' => [2, 2],
    'lv' => [2, 2], 'ml' => [1, 1], 'mn-cyrl' => [2, 2],
    'mn-cyrl-x-lmc' => [2, 2], 'mr' => [1, 1], 'mul-ethi' => [1, 1],
    'nb' => [2, 2], 'nl' => [2, 2], 'nn' => [2, 2], 'no' => [2, 2],
    'or' => [1, 1], 'pa' => [1, 1], 'pl' => [2, 2], 'pms' => [2, 2],
    'pt' => [2, 3], 'rm' => [2, 2], 'ro' => [2, 2], 'ru' => [2, 2],
    'sa' => [1, 3], 'sh-cyrl' => [2, 2], 'sk' => [2, 3], 'sl' => [2, 2],
    'sr-latn' => [2, 2], 'sv' => [2, 2], 'ta' => [1, 1], 'te' => [1, 1],
    'th' => [2, 3], 'tk' => [2, 2], 'tr' => [2, 2], 'uk' => [2, 2],
    'zh-latn-pinyin' => [1, 1],
];

$scriptMap = [
    'Latin' => [
        'af', 'ca', 'cs', 'cy', 'da', 'de-1901', 'de-1996', 'de-ch-1901',
        'en-gb', 'en-us', 'eo', 'es', 'et', 'eu', 'fi', 'fi-x-school',
        'fr', 'fur', 'ga', 'gl', 'hr', 'hsb', 'hu', 'ia', 'id', 'is', 'it',
        'kmr', 'la', 'la-x-classic', 'la-x-liturgic', 'lt', 'lv',
        'nb', 'nl', 'nn', 'oc', 'pi', 'pl', 'pms', 'pt', 'rm', 'ro',
        'sh-latn', 'sk', 'sl', 'sq', 'sr-latn', 'sv', 'tk', 'tr',
        'zh-latn-pinyin',
    ],
    'Cyrillic' => [
        'be', 'bg', 'cu', 'kk', 'mk', 'mn-cyrl', 'mn-cyrl-x-lmc',
        'ru', 'sh-cyrl', 'sr-cyrl', 'uk',
    ],
    'Greek' => ['el-monoton', 'el-polyton', 'grc'],
    'Armenian' => ['hy'],
    'Georgian' => ['ka'],
    'Thai' => ['th'],
    'Devanagari' => ['hi', 'mr', 'sa'],
    'Bengali' => ['bn'],
    'Tamil' => ['ta'],
    'Telugu' => ['te'],
    'Kannada' => ['kn'],
    'Malayalam' => ['ml'],
    'Gujarati' => ['gu'],
    'Gurmukhi' => ['pa'],
    'Odia' => ['or'],
    'Ethiopic' => ['mul-ethi'],
    'Coptic' => ['cop'],
];

$languageNames = [
    'af' => ['Afrikaans', 'Afrikaans'],
    'as' => ['Assamese', 'অসমীয়া'],
    'be' => ['Belarusian', 'Беларуская'],
    'bg' => ['Bulgarian', 'Български'],
    'ca' => ['Catalan', 'Català'],
    'cop' => ['Coptic', 'ϯⲙⲉⲧⲣⲉⲙⲛⲭⲏⲙⲓ'],
    'cs' => ['Czech', 'Čeština'],
    'cu' => ['Church Slavonic', 'ⰔⰎⰑⰂⰡⰐⰠⰔⰍⰟ'],
    'cy' => ['Welsh', 'Cymraeg'],
    'da' => ['Danish', 'Dansk'],
    'de-1901' => ['German (traditional)', 'Deutsch (traditionell)'],
    'de-1996' => ['German (reformed)', 'Deutsch (reformiert)'],
    'de-ch-1901' => ['German (Swiss traditional)', 'Deutsch (Schweiz, traditionell)'],
    'el-monoton' => ['Modern Greek (monotonic)', 'Νέα Ελληνικά (μονοτονικό)'],
    'el-polyton' => ['Modern Greek (polytonic)', 'Νέα Ελληνικά (πολυτονικό)'],
    'en-gb' => ['English (UK)', 'English (UK)'],
    'en-us' => ['English (US)', 'English (US)'],
    'eo' => ['Esperanto', 'Esperanto'],
    'es' => ['Spanish', 'Español'],
    'et' => ['Estonian', 'Eesti'],
    'eu' => ['Basque', 'Euskara'],
    'fi' => ['Finnish', 'Suomi'],
    'fi-x-school' => ['Finnish (school rules)', 'Suomi (koulusäännöt)'],
    'fr' => ['French', 'Français'],
    'fur' => ['Friulian', 'Furlan'],
    'ga' => ['Irish', 'Gaeilge'],
    'gl' => ['Galician', 'Galego'],
    'grc' => ['Ancient Greek', 'Ἀρχαία Ἑλληνική'],
    'gu' => ['Gujarati', 'ગુજરાતી'],
    'hr' => ['Croatian', 'Hrvatski'],
    'hsb' => ['Upper Sorbian', 'Hornjoserbšćina'],
    'hu' => ['Hungarian', 'Magyar'],
    'hy' => ['Armenian', 'Հայերեն'],
    'ia' => ['Interlingua', 'Interlingua'],
    'id' => ['Indonesian', 'Bahasa Indonesia'],
    'is' => ['Icelandic', 'Íslenska'],
    'it' => ['Italian', 'Italiano'],
    'ka' => ['Georgian', 'Ქართული'],
    'kk' => ['Kazakh', 'Қазақша'],
    'kmr' => ['Kurmanji (Kurdish)', 'Kurmancî'],
    'kn' => ['Kannada', 'ಕನ್ನಡ'],
    'la' => ['Latin', 'Latina'],
    'la-x-classic' => ['Latin (Classic)', 'Latina (Classica)'],
    'la-x-liturgic' => ['Latin (Liturgical)', 'Latina (Liturgica)'],
    'lt' => ['Lithuanian', 'Lietuvių'],
    'lv' => ['Latvian', 'Latviešu'],
    'mk' => ['Macedonian', 'Македонски'],
    'ml' => ['Malayalam', 'മലയാളം'],
    'mn-cyrl' => ['Mongolian (Cyrillic)', 'Монгол (Кирилл)'],
    'mr' => ['Marathi', 'मराठी'],
    'mul-ethi' => ['Ethiopic (multi)', 'የኢትዮጵያ'],
    'nl' => ['Dutch', 'Nederlands'],
    'nn' => ['Norwegian Nynorsk', 'Norsk Nynorsk'],
    'oc' => ['Occitan', 'Occitan'],
    'or' => ['Odia', 'ଓଡ଼ିଆ'],
    'pa' => ['Panjabi', 'ਪੰਜਾਬੀ'],
    'pi' => ['Pali', 'Pāli'],
    'pl' => ['Polish', 'Polski'],
    'pms' => ['Piedmontese', 'Piemontèis'],
    'pt' => ['Portuguese', 'Português'],
    'rm' => ['Romansh', 'Rumantsch'],
    'ro' => ['Romanian', 'Română'],
    'ru' => ['Russian', 'Русский'],
    'sa' => ['Sanskrit', 'संस्कृतम्'],
    'sh-cyrl' => ['Serbo-Croatian (Cyrillic)', 'Српскохрватски (Ћирилица)'],
    'sh-latn' => ['Serbo-Croatian (Latin)', 'Srpskohrvatski (Latinica)'],
    'sk' => ['Slovak', 'Slovenčina'],
    'sl' => ['Slovenian', 'Slovenščina'],
    'sq' => ['Albanian', 'Shqip'],
    'sr-cyrl' => ['Serbian (Cyrillic)', 'Српски (Ћирилица)'],
    'sv' => ['Swedish', 'Svenska'],
    'ta' => ['Tamil', 'தமிழ்'],
    'te' => ['Telugu', 'తెలుగు'],
    'th' => ['Thai', 'ไทย'],
    'tk' => ['Turkmen', 'Türkmençe'],
    'tr' => ['Turkish', 'Türkçe'],
    'uk' => ['Ukrainian', 'Українська'],
    'zh-latn-pinyin' => ['Chinese (Pinyin)', '中文 (拼音)'],
    'bn' => ['Bengali', 'বাংলা'],
    'hi' => ['Hindi', 'हिन्दी'],
    'nb' => ['Norwegian Bokmål', 'Norsk Bokmål'],
    'no' => ['Norwegian', 'Norsk'],
    'sr-latn' => ['Serbian (Latin)', 'Srpski (Latinica)'],
];

$patternByScript = [
    'Latin' => [
        'letterPattern' => '[A-Za-zÀ-ÖØ-öø-ÿĀ-žƀ-ɏḀ-ỿ]',
        'wordSplitPattern' => "[^\\p{L}'’-]+",
        'sentenceBoundaryPattern' => '[.!?]+',
    ],
    'Cyrillic' => [
        'letterPattern' => '[А-Яа-яЁёҐ-ӿЀ-ӿ]',
        'wordSplitPattern' => "[^\\p{L}'’-]+",
        'sentenceBoundaryPattern' => '[.!?…]+',
    ],
    'Greek' => [
        'letterPattern' => '[Α-Ωα-ωἀ-῾]',
        'wordSplitPattern' => "[^\\p{L}'’-]+",
        'sentenceBoundaryPattern' => '[.!?;]+',
    ],
    'Armenian' => [
        'letterPattern' => '[Ա-Ֆա-և]',
        'wordSplitPattern' => "[^\\p{L}'’-]+",
        'sentenceBoundaryPattern' => '[.!?]+',
    ],
    'Georgian' => [
        'letterPattern' => '[ა-ჰ]',
        'wordSplitPattern' => "[^\\p{L}'’-]+",
        'sentenceBoundaryPattern' => '[.!?]+',
    ],
    'Thai' => [
        'letterPattern' => '[ก-๛]',
        'wordSplitPattern' => "[^\\p{L}]+",
        'sentenceBoundaryPattern' => '[.!?]+',
    ],
    'Devanagari' => [
        'letterPattern' => '[ऀ-ॿ]',
        'wordSplitPattern' => "[^\\p{L}]+",
        'sentenceBoundaryPattern' => '[।.!?|]+',
    ],
    'Bengali' => [
        'letterPattern' => '[ঀ-৿]',
        'wordSplitPattern' => "[^\\p{L}]+",
        'sentenceBoundaryPattern' => '[।.!?]+',
    ],
    'Tamil' => [
        'letterPattern' => '[஀-௿]',
        'wordSplitPattern' => "[^\\p{L}]+",
        'sentenceBoundaryPattern' => '[.!?]+',
    ],
    'Telugu' => [
        'letterPattern' => '[ఀ-౿]',
        'wordSplitPattern' => "[^\\p{L}]+",
        'sentenceBoundaryPattern' => '[.!?]+',
    ],
    'Kannada' => [
        'letterPattern' => '[ಀ-೿]',
        'wordSplitPattern' => "[^\\p{L}]+",
        'sentenceBoundaryPattern' => '[.!?]+',
    ],
    'Malayalam' => [
        'letterPattern' => '[ഀ-ൿ]',
        'wordSplitPattern' => "[^\\p{L}]+",
        'sentenceBoundaryPattern' => '[.!?]+',
    ],
    'Gujarati' => [
        'letterPattern' => '[઀-૿]',
        'wordSplitPattern' => "[^\\p{L}]+",
        'sentenceBoundaryPattern' => '[.!?]+',
    ],
    'Gurmukhi' => [
        'letterPattern' => '[਀-੿]',
        'wordSplitPattern' => "[^\\p{L}]+",
        'sentenceBoundaryPattern' => '[.!?]+',
    ],
    'Odia' => [
        'letterPattern' => '[଀-୿]',
        'wordSplitPattern' => "[^\\p{L}]+",
        'sentenceBoundaryPattern' => '[.!?]+',
    ],
    'Ethiopic' => [
        'letterPattern' => '[ሀ-፼]',
        'wordSplitPattern' => "[^\\p{L}]+",
        'sentenceBoundaryPattern' => '[.!?]+',
    ],
    'Coptic' => [
        'letterPattern' => '[Ⲁ-⳿]',
        'wordSplitPattern' => "[^\\p{L}]+",
        'sentenceBoundaryPattern' => '[.!?]+',
    ],
];

$defaultPattern = [
    'letterPattern' => '\p{L}',
    'wordSplitPattern' => "[^\\p{L}'’-]+",
    'sentenceBoundaryPattern' => '[.!?]+',
];

$freLanguages = ['en-us', 'en-gb', 'de-1996', 'de-1901', 'de-ch-1901', 'ru', 'es', 'it', 'fr', 'nl', 'pt', 'tr'];
$freCoefficients = [
    'en-us' => ['base' => 206.835, 'aslMult' => 1.015, 'aswMult' => 84.6],
    'en-gb' => ['base' => 206.835, 'aslMult' => 1.015, 'aswMult' => 84.6],
    'de-1996' => ['base' => 180.0, 'aslMult' => 1.0, 'aswMult' => 58.5],
    'de-1901' => ['base' => 180.0, 'aslMult' => 1.0, 'aswMult' => 58.5],
    'de-ch-1901' => ['base' => 180.0, 'aslMult' => 1.0, 'aswMult' => 58.5],
    'ru' => ['base' => 206.835, 'aslMult' => 1.52, 'aswMult' => 65.14],
    'es' => ['base' => 206.84, 'aslMult' => 1.02, 'aswMult' => 60.0],
    'it' => ['base' => 217.0, 'aslMult' => 1.3, 'aswMult' => 0.6],
    'fr' => ['base' => 207.0, 'aslMult' => 1.015, 'aswMult' => 73.6],
    'nl' => ['base' => 206.835, 'aslMult' => 0.93, 'aswMult' => 77.0],
    'pt' => ['base' => 248.835, 'aslMult' => 1.015, 'aswMult' => 84.6],
    'tr' => ['base' => 198.825, 'aslMult' => 40.175, 'aswMult' => 2.61],
];

$generated = 0;

$patFiles = glob($patternsDir . '/hyph-*.tex');

foreach ($patFiles as $patFile) {
    $filename = basename($patFile, '.tex');
    $code = substr($filename, 5);

    $name = $languageNames[$code][0] ?? $code;
    $nativeName = $languageNames[$code][1] ?? $code;

    $script = 'Other';
    foreach ($scriptMap as $s => $langs) {
        if (in_array($code, $langs, true)) {
            $script = $s;
            break;
        }
    }

    $patterns = $patternByScript[$script] ?? $defaultPattern;

    $leftMin = $minHyphens[$code][0] ?? 2;
    $rightMin = $minHyphens[$code][1] ?? 2;

    $formulas = [];
    $formulas['gunning_fog'] = ['enabled' => true];
    $formulas['smog'] = ['enabled' => true];
    $formulas['coleman_liau'] = ['enabled' => true];
    $formulas['ari'] = ['enabled' => true];

    $lixThreshold = match ($script) {
        'Cyrillic' => 6, 'Greek' => 6, 'Armenian' => 7,
        'Georgian' => 6, 'Thai' => 8, 'Devanagari' => 5,
        'Ethiopic' => 4, default => 6,
    };
    $formulas['lix'] = ['enabled' => true, 'longWordThreshold' => $lixThreshold];

    if (in_array($code, $freLanguages, true)) {
        $coeffs = $freCoefficients[$code];
        $formulas['flesch_reading_ease'] = [
            'enabled' => true,
            'base' => $coeffs['base'],
            'aslMult' => $coeffs['aslMult'],
            'aswMult' => $coeffs['aswMult'],
        ];
        $formulas['flesch_kincaid_grade_level'] = ['enabled' => true];
    }

    if (in_array($code, ['de-1996', 'de-1901', 'de-ch-1901'], true)) {
        $formulas['wiener_sachtextformel'] = ['enabled' => true];
    }

    if ($code === 'it') {
        $formulas['gulpease'] = ['enabled' => true];
    }

    if ($code === 'es') {
        $formulas['fernandez_huerta'] = ['enabled' => true];
        $formulas['szigriszt_pazos'] = ['enabled' => true];
        $formulas['gutierrez_polini'] = ['enabled' => true];
        $formulas['crawford'] = ['enabled' => true];
    }

    if ($code === 'pl') {
        $formulas['fog_pl'] = ['enabled' => true];
    }

    if (in_array($code, ['en-us', 'en-gb'], true)) {
        $formulas['dale_chall'] = ['enabled' => true];
        $formulas['spache'] = ['enabled' => true];
    }

    $config = [
        'code' => $code,
        'name' => $name,
        'nativeName' => $nativeName,
        'script' => $script,
        'hyphenMins' => ['left' => (int) $leftMin, 'right' => (int) $rightMin],
        'letterPattern' => $patterns['letterPattern'],
        'wordSplitPattern' => $patterns['wordSplitPattern'],
        'sentenceBoundaryPattern' => $patterns['sentenceBoundaryPattern'],
        'formulas' => (object) $formulas,
    ];

    $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

    $outFile = $outputDir . '/' . $code . '.json';
    file_put_contents($outFile, $json . "\n");
    $generated++;
}

echo "Generated: {$generated} configs\n";
