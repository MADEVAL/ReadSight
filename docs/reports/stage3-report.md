# ReadSight — Stage 3 Report: Full Language Coverage

**Дата:** 25.06.2026  
**Статус:** Completed ✅

---

## 1. Цель этапа

Создание JSON-конфигов для всех 78 языков с TeX-паттернами и многоязыковые интеграционные тесты.

---

## 2. Что сделано

### 2.1. Генератор конфигов

Создан скрипт `generate-languages.php`, который для каждого `.pat.txt` файла генерирует JSON-конфиг языка со следующими автоматическими определениями:

| Параметр | Источник |
|---|---|
| `code`, `name`, `nativeName` | Таблица 78 языков с нативными названиями |
| `script` | Маппинг кода → алфавит (Latin, Cyrillic, Greek, Arabic, Devanagari, etc.) |
| `hyphenMins` | `Syllable/languages/min.json` |
| `letterPattern` | По алфавиту: Latin `[A-Za-zÀ-Ö...]`, Cyrillic `[А-Яа-я...]`, и т.д. |
| `wordSplitPattern` | `[^\p{L}'’-]+` для большинства |
| `sentenceBoundaryPattern` | `[.!?]+` / `[.!?…]+` / `[।.!?|]+` |
| `formulas` | Таблица поддержки формул по языкам |

### 2.2. Сгенерировано

- **78 JSON-конфигов** в `data/languages/`
- 16 алфавитов/письменностей
- 9 языков с FRE-коэффициентами
- 5 языков со специфичными формулами (WSTF, Gulpease, Fernandez-Huerta, FOG-PL, Dale-Chall/Spache)

### 2.3. Покрытие письменностей

| Алфавит | Языков |
|---|---|
| Latin | 52 |
| Cyrillic | 11 |
| Greek | 3 |
| Armenian | 1 |
| Georgian | 1 |
| Thai | 1 |
| Devanagari | 3 |
| Bengali | 1 |
| Tamil | 1 |
| Telugu | 1 |
| Kannada | 1 |
| Malayalam | 1 |
| Gujarati | 1 |
| Gurmukhi | 1 |
| Odia | 1 |
| Ethiopic | 1 |
| Coptic | 1 |

### 2.4. Тесты

```
157 tests, 378 assertions, ALL PASS
PHPStan level max: 0 errors
```

Новые тесты Stage 3:
- `test_syllable_count_positive` — 15 языков (data provider)
- `test_all_supported_languages_load` — загрузка всех 78 языков
- `test_universal_formulas_across_scripts` — GF, ARI, LIX, CL для 5 алфавитов
- `test_fre_all_languages` — FRE для 9 языков
- `test_language_specific_formulas` — WSTF, Gulpease, FH, FOG-PL
- `test_throws_unknown_language`
- `test_formula_lists_per_language` — проверка доступных формул для 4 языков

---

## 3. Статистика проекта (после Stage 3)

| Метрика | Значение |
|---|---|
| Исходных PHP-файлов | 37 |
| Тестовых PHP-файлов | 16 |
| JSON-конфигов языков | 78 |
| TeX-файлов (.pat.txt + .hyp.txt) | 98 |
| Тестов | 157 |
| Assertions | 378 |
| Поддерживаемых языков | 78 |
| Формул | 17 |
| PHPStan level | max (0 errors) |

---

## 4. Следующий этап

**Stage 4: Regression Testing**
- TextStatParityTest — сравнение с Text-Stat (10 548 слов CMU)
- SyllableParityTest — сравнение со Syllable
- Настройка допусков
