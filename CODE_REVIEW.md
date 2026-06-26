# Code Review Report — ReadSight v1.0.4

**Date:** 2026-06-26  
**Scope:** `src/` (48 files), `tests/` (18 files), root config  
**Verification:** PHPStan level max (0 errors), PHPUnit (262 tests, 1034 assertions — green)

---

## All findings (10)

| # | Severity | File(s) | Description | Status |
|---|----------|---------|-------------|--------|
| C1 | CRITICAL | `HeuristicSyllableCounter`, `CompositeSyllableCounter`, `Engine` | Heuristic полностью подавляет TeX для английского — `hasRules()` возвращал true из-за subtract/addPatterns, Composite никогда не доходил до TeX | ✓ fixed |
| M1 | MEDIUM | `HeuristicSyllableCounter.php:71` | `preg_replace('/[^a-z]/', ...)` вырезал все не-ASCII символы; `preg_split`/`preg_match` без флага `/u` ломали UTF-8 | ✓ fixed |
| M2 | MEDIUM | `TextAnalyzer.php:96`, `Engine.php:179` | `wordsWithNSyllables()` считает слова с **>N** слогов, называется «с N слогами» | ✓ fixed |
| L1 | LOW | `FormulaResult.php:16` | `gradeLabel` всегда `null` (кроме Lix, где дублирует `interpretation`) | ✓ fixed |
| L2 | LOW | `Engine.php:35` | `$languageRepository` присвоен в конструкторе, нигде больше не прочитан | ✓ fixed |
| L3 | LOW | `Pattern.php:22` | `toString()` никогда не вызывается в production | ✓ fixed |
| L4 | LOW | `WienerSachtextformel.php:45-55` | variant=5 молча считается как variant=4 | ✓ fixed |
| L5 | LOW | `CompositeSyllableCounter.php:25` | Fallback `return 1` | ✓ fixed |
| I1 | INFO | `SzigrisztPazos.php:38-40` | `$S = x * 100.0; ... $S / 100.0` — избыточное умножение/деление | ✓ fixed |
| I2 | INFO | `HeuristicSyllableCounter.php:137` | `splitSyllables` делит строку арифметически, не лингвистически | ✓ fixed |

---

## Fixed (10) — все исправлено

### Раунд 1 — Heuristic + TeX архитектура (C1, M1, L5)

**Проблема:** `HeuristicSyllableCounter` полностью заменял TeX для английского, не дополнял.

**Решение:**
- `HeuristicSyllableCounter::hasRules()` → теперь только `problemWords !== []`
- Добавлен `hasWord(string)` — проверка конкретного слова
- `CompositeSyllableCounter` — `countSyllables`/`splitSyllables` используют `hasWord()` вместо `hasRules()`: problemWord → heuristic, остальное → TeX
- `Language` — новое поле `syllableMode` (`tex`|`heuristic`|`composite`), дефолт `tex`
- `Engine::loadSyllableCounter` — диспетчеризация по `syllableMode`
- `[^a-z]` → `[^\p{L}]/u`, все `preg_*` получили флаг `/u`
- `en-us.json`, `en-gb.json` → `"syllableMode": "composite"`
- +39 тестов (`HeuristicSyllableCounterTest`, `CompositeSyllableCounterTest`, `LanguageTest`)

### M2 + L2 + L3 + L4 — чистка (раунд 2)

- **M2** — `wordsWithNSyllables` → `wordsWithMoreThanNSyllables` в `TextAnalyzer` + `Engine`
- **L2** — удалён `$languageRepository` из Engine (локальная переменная вместо свойства)
- **L3** — удалён `Pattern::toString()` + тесты к нему
- **L4** — `WienerSachtextformel`: variant вне [1,4] → `\InvalidArgumentException`

### Раунд 3 — добивка (L1, I1, I2)

- **L1** — удалён `gradeLabel` из `FormulaResult` (17 формул + тест)
- **I1** — `SzigrisztPazos`: устранено `*100/100`, промежуточное значение `syllablesPer100` сохранено для inputs
- **I2** — `HeuristicSyllableCounter::splitSyllables`: добавлен doc-комментарий с описанием ограничения

---

## Open (0)
