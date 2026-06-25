# ReadSight — Stage 1 Report: Core MVP

**Дата:** 25.06.2026
**Статус:** Completed ✅

---

## 1. Цель этапа

Реализация ядра библиотеки ReadSight: подсчёт слогов по алгоритму Лянга (TeX) для 3 языков,
текстовые метрики, кеширование паттернов и фасадный API.

---

## 2. Что сделано

### 2.1. Структура проекта

```
ReadSight/
├── composer.json              # globus-studio/readsight, PHP >= 8.5.0
├── phpstan.neon               # Level max
├── phpunit.xml                # PHPUnit 11/12
├── .php-cs-fixer.php          # PER CS 2.0
├── .gitignore
├── src/
│   ├── Engine.php                              # Фасад (публичный API)
│   ├── Exception/
│   │   ├── ReadabilityEngineException.php      # Базовое исключение
│   │   ├── UnsupportedLanguageException.php
│   │   ├── UnsupportedFormulaException.php
│   │   ├── PatternFileNotFoundException.php
│   │   ├── PatternParseException.php
│   │   └── EmptyTextException.php
│   ├── Language/
│   │   ├── LanguageCode.php                    # Value object: код языка
│   │   ├── Script.php                          # Enum: Latin, Cyrillic, etc.
│   │   ├── Language.php                        # Value object: конфигурация языка
│   │   ├── LanguageRepository.php              # Интерфейс хранилища
│   │   └── JsonLanguageRepository.php          # Реализация через JSON
│   ├── Hyphenation/
│   │   ├── Pattern.php                         # Один TeX-паттерн
│   │   ├── PatternsCollection.php              # Коллекция паттернов
│   │   ├── HyphenationException.php            # Слово-исключение
│   │   ├── HyphenationExceptionsCollection.php # Коллекция исключений
│   │   ├── Hyphenator.php                      # Интерфейс слогоделения
│   │   ├── LiangHyphenator.php                 # Алгоритм Лянга
│   │   ├── Source/
│   │   │   ├── PatternSource.php               # Интерфейс источника
│   │   │   └── PatTxtSource.php               # Парсер .pat.txt / .hyp.txt
│   │   └── Cache/
│   │       ├── PatternCache.php                # Интерфейс кеша
│   │       └── JsonPatternCache.php            # JSON-кеш
│   └── Text/
│       ├── TextSplitter.php                    # Разбивка текста
│       └── TextStatistics.php                  # DTO метрик текста
├── data/
│   ├── languages/
│   │   ├── en-us.json                          # Английский (US)
│   │   ├── ru.json                             # Русский
│   │   └── de-1996.json                        # Немецкий
│   └── patterns/                               # (копии из hyph-utf8)
│       ├── hyph-en-us.pat.txt
│       ├── hyph-en-us.hyp.txt
│       ├── hyph-ru.pat.txt
│       ├── hyph-ru.hyp.txt
│       └── hyph-de-1996.pat.txt
└── tests/
    ├── Unit/
    │   ├── EngineTest.php                      # 18 тестов фасада
    │   ├── Language/
    │   │   ├── LanguageCodeTest.php            # 6 тестов
    │   │   ├── LanguageTest.php                # 8 тестов
    │   │   └── JsonLanguageRepositoryTest.php  # 9 тестов
    │   ├── Hyphenation/
    │   │   ├── PatternTest.php                 # 8 тестов
    │   │   ├── HyphenationExceptionTest.php    # 1 тест
    │   │   ├── HyphenationExceptionsCollectionTest.php  # 6 тестов
    │   │   ├── PatTxtSourceTest.php            # 8 тестов
    │   │   ├── JsonPatternCacheTest.php        # 6 тестов
    │   │   └── LiangHyphenatorTest.php         # 9 тестов
    │   └── Text/
    │       └── TextSplitterTest.php            # 10 тестов
    ├── Integration/
    │   └── EngineIntegrationTest.php           # 8 интеграционных тестов
    └── Fixtures/
        ├── patterns/
        │   ├── hyph-en-minimal.pat.txt
        │   └── hyph-en-minimal.hyp.txt
        └── text/
            ├── moby-dick-opening.txt
            ├── russian-sample.txt
            └── german-sample.txt
```

### 2.2. Ключевые архитектурные решения

1. **Plain-text паттерны (.pat.txt)** вместо TeX-синтаксиса — проще парсинг, меньше кода.
2. **JSON-конфиги языков** — декларативное описание каждого языка (скрипт, regex-паттерны, hyphenMins, формулы).
3. **Property hooks (PHP 8.4+)** для красивого публичного чтения свойств без boilerplate-геттеров.
4. **Алгоритм Лянга** — полная реализация splitByPatterns с поддержкой исключений и пользовательских hyphenations.
5. **Кеширование** — JSON-кеш скомпилированных паттернов в `cache/`.
6. **Strict typing** — `declare(strict_types=1)` во всех файлах.

### 2.3. Формат JSON-конфига языка

```json
{
    "code": "en-us",
    "name": "English (US)",
    "nativeName": "English (US)",
    "script": "Latin",
    "hyphenMins": { "left": 2, "right": 2 },
    "letterPattern": "[A-Za-z]",
    "wordSplitPattern": "[^A-Za-z'’\\-]+",
    "sentenceBoundaryPattern": "[.!?]+",
    "formulas": {
        "flesch_reading_ease": { "enabled": true, "base": 206.835, "aslMult": 1.015, "aswMult": 84.6 },
        "lix": { "enabled": true, "longWordThreshold": 6 }
    }
}
```

### 2.4. API (доступные методы Engine)

| Метод | Назначение |
|---|---|
| `syllableCount($word): int` | Подсчёт слогов в слове |
| `splitWord($word): array` | Разбивка слова на слоги |
| `wordCount($text): int` | Подсчёт слов |
| `sentenceCount($text): int` | Подсчёт предложений |
| `letterCount($text): int` | Подсчёт букв |
| `totalSyllables($text): int` | Всего слогов в тексте |
| `averageSyllablesPerWord($text): float` | Среднее слогов на слово |
| `averageWordsPerSentence($text): float` | Среднее слов на предложение |
| `polysyllableCount($text): int` | Слов с >2 слогами |
| `histogramSyllables($text): array` | Гистограмма слогов |
| `analyze($text): TextStatistics` | Полный DTO со всеми метриками |
| `addHyphenations(array)` | Пользовательские правила переносов |
| `getLanguage(): Language` | Конфигурация текущего языка |
| `getSupportedFormulas(): array` | Доступные формулы для языка |
| `getSupportedLanguages(): array` (статический) | Список всех языков |

---

## 3. Качество кода

### 3.1. Статический анализ

```
PHPStan level max: 0 errors ✅
```

### 3.2. Тесты

```
PHPUnit: 96 tests, 150 assertions, ALL PASS ✅
```

**Покрытие по компонентам:**

| Компонент | Тестов |
|---|---|
| LanguageCode | 6 |
| Language | 8 |
| JsonLanguageRepository | 9 |
| Pattern + PatternsCollection | 8 |
| HyphenationException + Collection | 7 |
| PatTxtSource | 8 |
| JsonPatternCache | 6 |
| LiangHyphenator | 9 |
| TextSplitter | 10 |
| Engine (unit) | 18 |
| Engine (integration) | 8 |
| **Всего** | **96** |

### 3.3. Поддерживаемые языки (Stage 1)

| Код | Язык | Паттернов | Слов-исключений | FRE | LIX |
|---|---|---|---|---|---|
| `en-us` | English (US) | 4,938 | 14 | 206.835 - 1.015ASL - 84.6ASW | ✓ |
| `ru` | Russian | ~7,255 | 184 | 206.835 - 1.52ASL - 65.14ASW | ✓ |
| `de-1996` | German | ~36,700 | — | 180 - 1.0ASL - 58.5ASW | ✓ |

---

## 4. Возникшие проблемы и решения

1. **Property hooks + readonly incompatibility.** В PHP 8.4+ `{ get; }` hooks несовместимы с `readonly` классом. Решение: убрать `readonly` с класса, использовать `public readonly` на свойствах с инициализацией в конструкторе.

2. **`{ get; }` + constructor assignment.** В PHP 8.4+ `{ get; }` без тела — виртуальное свойство, нельзя присвоить через `$this->prop =`. Решение: убрать hooks, использовать `public readonly $prop`.

3. **Формат весов в паттернах.** Веса паттерна должны быть на 1 длиннее символов (вес ДО первого символа и ПОСЛЕ каждого). Тесты были исправлены для отражения этого.

4. **PHPStan level max + mixed cast.** Каст из `mixed` в `int` запрещён на level max. Решение: проверка `is_numeric()` перед кастом.

---

## 5. Следующий этап

**Stage 2: Readability Formulas**
- `Formula` interface
- `FormulaResult` DTO
- `FormulaRegistry`
- Реализация формул: FRE, FKGL, Gunning Fog, SMOG, Coleman-Liau, ARI, LIX
- Тесты каждой формулы на ≥2 языках
- Интеграционные тесты с реальными текстами
