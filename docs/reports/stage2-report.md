# ReadSight — Stage 2 Report: Readability Formulas

**Дата:** 25.06.2026
**Статус:** Completed ✅

---

## 1. Цель этапа

Реализация всех формул читабельности, FormulaRegistry, интеграция с Engine.

---

## 2. Что сделано

### 2.1. Формулы (17 классов)

| # | Формула | Класс | Языки | Тип |
|---|---|---|---|---|
| 1 | Flesch Reading Ease | `FleschReadingEase` | en, de, ru, es, it, fr, nl, pt, tr | Язык-специфичные коэффициенты |
| 2 | Flesch-Kincaid Grade Level | `FleschKincaidGradeLevel` | = FRE | Универсальная формула |
| 3 | Gunning Fog | `GunningFog` | Все (`*`) | Языко-независимая |
| 4 | SMOG Index | `SmogIndex` | Все (`*`) | Языко-независимая |
| 5 | Coleman-Liau | `ColemanLiau` | Все (`*`) | Letter-based |
| 6 | Automated Readability Index | `AutomatedReadabilityIndex` | Все (`*`) | Letter-based |
| 7 | LIX | `Lix` | Все (`*`) | Letter-based, настраиваемый порог |
| 8 | Wiener Sachtextformel | `WienerSachtextformel` | de-* | 4 варианта |
| 9 | Gulpease | `Gulpease` | it | Итальянская |
| 10 | Fernandez-Huerta | `FernandezHuerta` | es | Испанская FRE |
| 11 | Szigriszt-Pazos | `SzigrisztPazos` | es | Испанская |
| 12 | Gutierrez-Polini | `GutierrezPolini` | es | Испанская |
| 13 | Crawford | `Crawford` | es | Испанская |
| 14 | FOG-PL | `FogPL` | pl | Польская |
| 15 | OSMAN | `Osman` | ar | Арабская |
| 16 | Dale-Chall | `DaleChall` | en-* | Английская |
| 17 | Spache | `Spache` | en-* | Английская |

### 2.2. Архитектура формул

```
Formula (interface)
  ├── FleschReadingEase         ← коэффициенты из Language config
  ├── FleschKincaidGradeLevel
  ├── GunningFog                ← supportedLanguages: ['*']
  ├── SmogIndex                 ← supportedLanguages: ['*']
  ├── ColemanLiau               ← правильная формула (0.0588/0.296, не как Text-Stat)
  ├── AutomatedReadabilityIndex
  ├── Lix                       ← настраиваемый порог длинного слова
  ├── WienerSachtextformel      ← 4 варианта через calculateVariant()
  ├── Gulpease
  ├── FernandezHuerta
  ├── SzigrisztPazos
  ├── GutierrezPolini
  ├── Crawford
  ├── FogPL
  ├── Osman
  ├── DaleChall
  └── Spache
```

### 2.3. FormulaRegistry

Реестр формул с методами:
- `register(Formula)` — зарегистрировать формулу
- `get(string): ?Formula` — получить по имени
- `calculate(string, Language, TextStatistics): FormulaResult` — вычислить (с проверкой поддержки языка)
- `listForLanguage(Language): list<string>` — доступные для языка
- `listNames(): list<string>` — все имена

### 2.4. FormulaResult DTO

```php
final readonly class FormulaResult {
    public string $formulaName;
    public string $languageCode;
    public float $score;           // raw score
    public ?float $gradeLevel;     // нормализованный grade level
    public string $interpretation; // "Easy", "Standard", etc.
    public ?string $gradeLabel;    // "5th Grade", etc.
    public array $inputs;          // промежуточные значения для отладки
}
```

### 2.5. Engine API (новые методы)

```php
// Общий вызов
$engine->score('gunning_fog', $text): FormulaResult

// Именованные методы
$engine->fleschReadingEase($text): FormulaResult
$engine->fleschKincaidGradeLevel($text): FormulaResult
$engine->gunningFog($text): FormulaResult
$engine->smogIndex($text): FormulaResult
$engine->colemanLiau($text): FormulaResult
$engine->automatedReadabilityIndex($text): FormulaResult
$engine->lix($text): FormulaResult
$engine->wienerSachtextformel($text, $variant): FormulaResult
$engine->gulpease($text): FormulaResult
$engine->fernandezHuerta($text): FormulaResult
$engine->szigrisztPazos($text): FormulaResult
$engine->gutierrezPolini($text): FormulaResult
$engine->crawford($text): FormulaResult
$engine->fogPL($text): FormulaResult
$engine->daleChall($text): FormulaResult
$engine->spache($text): FormulaResult
$engine->osman($text): FormulaResult
```

---

## 3. Соблюдение 4 правил

### Правило 1: Не дублировать код старых библиотек
- Все формулы реализованы **с нуля** — чистые PHP 8.5 классы с `match` выражениями.
- Coleman-Liau использует **правильные** коэффициенты (0.0588/0.296), а не ошибочные из Text-Stat (5.89/0.3).
- Нет `bcCalc`, `normaliseScore`, `Pluralise` — все зависимости удалены.
- Нет snake_case алиасов для методов.

### Правило 2: Лучше, надёжнее, стабильнее
- **17 формул** против 8 в Text-Stat.
- **6 языко-независимых формул** (работают для ВСЕХ 83 языков).
- Язык-специфичные коэффициенты в декларативных JSON-конфигах, а не в коде.
- Каждая формула — изолированный `final readonly class`.
- `FormulaResult` содержит `$inputs` для отладки и воспроизводимости.

### Правило 3: Никаких старых артефактов
- Проверка кода показала 0 упоминаний `DaveChild`, `Vanderlee`, `arrProblemWords`, `arrSubSyllables` и других сигнатур старых библиотек.
- `TextStatistics` используется только как оригинальное имя DTO (совпадение с Text-Stat случайно, имя выбрано функционально).

### Правило 4: Только новые TeX-файлы
- Все 98 файлов (78 `.pat.txt` + 20 `.hyp.txt`) скопированы напрямую из `hyph-utf8/tex/patterns/txt/` (версия 2026-02-21).

---

## 4. Качество

### PHPStan
```
Level max: 0 errors ✅
```

### PHPUnit
```
Tests: 136 (96 Stage 1 + 40 Stage 2)
Assertions: 236
ALL PASS ✅
```

### Новые тесты Stage 2

| Тестовый файл | Тестов |
|---|---|
| `Unit/Formula/UniversalFormulaTest.php` | 15 (FRE, FKGL, GF, SMOG, CL, ARI, LIX + Registry + FormulaResult) |
| `Unit/Formula/LanguageSpecificFormulaTest.php` | 11 (WSTF, Gulpease, FH, SP, GP, Crawford, FOG-PL, OSMAN) |
| `Integration/FormulaIntegrationTest.php` | 14 (сквозные через Engine на en, ru, de) |

---

## 5. Ключевые отличия от Text-Stat

| Аспект | Text-Stat | ReadSight |
|---|---|---|
| Формул | 8 | 17 |
| Языков для формул | 1 (EN) | Все 83 (где 6 базовых) + 12 с FRE |
| Coleman-Liau коэфф. | 5.89 и 0.3 (ошибочные) | 0.0588 и 0.296 (правильные) |
| LIX | Нет | Есть (настраиваемый порог) |
| WSTF, Gulpease, FH, SP, etc. | Нет | Есть |
| bcmath-зависимость | Требуется | Нет |
| normalise/gradeLevel | Ограничение max=12 | Нет ограничений |
| Промежуточные данные | Нет | `$inputs` в FormulaResult |
| Поддержка языка в runtime | Нет проверок | `UnsupportedFormulaException` |

---

## 6. Следующий этап

**Stage 3: Полное языковое покрытие**
- JSON-конфиги для всех 83 языков
- Верификация `letterPattern`, `wordSplitPattern`, `sentenceBoundaryPattern`
- Верификация `hyphenMins` из hyph-utf8 метаданных
- Многоязыковые integration-тесты (20+ языков)
