# Changelog

All notable changes to ReadSight will be documented in this file.

## [1.0.6] - 2026-06-26

### Fixed
- Fix minor bugs

## [1.0.5] - 2026-06-26

### Fixed
- Fix minor bugs

## [1.0.4] - 2026-06-26

### Added
- **7 new languages**: Arabic (ar), Bengali (bn), Farsi (fa), Hebrew (he), Hindi (hi), Mongolian LMC (mn-cyrl-x-lmc), Vietnamese (vi) - 79 → **86 languages**
- **2 new writing systems**: Arabic, Hebrew - 16 → **19**
- `Script` enum: added `Hebrew` case

### Fixed
- `generate-languages.php`: added missing Arabic/Hebrew/Vietnamese script maps, letter patterns, language names, and min-hyphen values
- 4 formula edge-case tests replaced meaningless `addToAssertionCount(1)` with real score assertions
- `README.md`: fixed architecture diagram, stale file/class counts, writing system count
- Test: `HyphenationOverrideTest::test_creates_exception()` → `test_creates_override()`
- Duplicate `@return` docblocks removed from formula test data providers
- 8 unreachable `.tex` files cleaned up (now properly matched by generated `.json` configs)
- `composer.json` description updated to 86 languages

## [1.0.3] - 2026-06-26

### Added
- **Native `.tex` pattern support** via `TexSource` - parses hyph-utf8 `.tex` files directly, preserving word-boundary markers (dots) for superior accuracy
- `PatternsCollection::getByFirstChar()` - public API for index-based pattern lookup
- `LanguageCode::normalize()` - static factory, now used in `JsonLanguageRepository` to eliminate duplicated normalization
- 28 new tests: `TextStatisticsHelper` (5), `PatternsCollection` (3), `LanguageCode` (1), formula metadata (17), Engine edge cases (+2)

### Changed
- **Pattern source**: `Engine` now uses `.tex` files by default (from hyph-utf8), with no fallback to less accurate `.pat.txt`
- `HyphenationException` renamed to `HyphenationOverride` - clearer semantics (DTO, not PHP exception)
- `PatternsCollectionTest` extracted to its own file (was invisible to PHPUnit)
- `LanguageCode` integrated into production code (was dead code)

### Removed
- `PatTxtSource` and `PatTxtSourceTest` - replaced by `TexSource`
- All `.pat.txt` and `.hyp.txt` files - replaced by `.tex` originals
- `generate-languages.php` now scans `.tex` instead of `.pat.txt`

### Fixed
- **Syllable accuracy** - now **100% identical** to reference `vanderlee/phpSyllable` (10 key words fixed: `character`, `wonderful`, `communication`, `incredible`, etc.)
- `EngineTest` now creates `.tex` fixtures and properly resets static `Config` in `tearDown()`
- Cache version bumped to `2.0` to force rebuild with new patterns
- Demo words replaced with accurately-counted examples

## [1.0.2] - 2026-06-26

### Changed
- Minimum PHP version lowered from 8.5 to **8.2** (verified on PHP 8.2, 8.3, 8.4, 8.5)
- CI runs on PHP 8.2

### Fixed
- Removed typed class constants (`const string`) for PHP 8.2 compatibility (5 files: 1 src + 4 tests)
- Fixed 3 integration test failures
- PHPStan memory limit added to `composer analyse` script

## [1.0.1] - 2026-06-26

### Added
- Unit tests for Dale-Chall and Spache formulas (8 new tests, 7 assertions)
- `Config` immutable DTO for directory paths
- `Engine::setDefaultConfig()` and `Engine::withConfig()` factory method
- `CHANGELOG.md`
- `LICENSE` file (MIT)

### Fixed
- CI: heavy `test_all_supported_languages_load` tagged `#[Group('slow')]`, excluded from main integration step
- CI: `continue-on-error: true` removed from main integration tests
- Engine: static mutable properties replaced with immutable `Config` object (old setters deprecated but preserved)
- composer.json: `analyse` script fixed for cross-platform compatibility

## [1.0.0] - 2026-06-26

### Added
- Initial release
- Liang (TeX) hyphenation algorithm for syllable counting
- 79 languages across 16 writing systems
- 17 readability formulas: Flesch Reading Ease, Flesch-Kincaid Grade Level, Gunning Fog, SMOG, Coleman-Liau, ARI, LIX, Gulpease, Wiener Sachtextformel, Fernandez Huerta, Szigriszt-Pazos, Gutierrez Polini, Crawford, Fog-PL, Dale-Chall, Spache, Osman
- JSON pattern cache for fast engine initialization
- Language-specific formula coefficients
- Text analysis (word/sentence/letter/syllable counts, syllable histogram)
- User-defined hyphenation overrides
- Interactive demo CLI
- Performance benchmark tool
- Comprehensive README with full API reference
- PHPStan level max static analysis
- PHP CS Fixer (PER-CS2.0)
- GitHub Actions CI pipeline
- Mutation testing with Infection

[1.0.4]: https://github.com/MADEVAL/ReadSight/releases/tag/v1.0.4
[1.0.3]: https://github.com/MADEVAL/ReadSight/releases/tag/v1.0.3
[1.0.2]: https://github.com/MADEVAL/ReadSight/releases/tag/v1.0.2
[1.0.1]: https://github.com/MADEVAL/ReadSight/releases/tag/v1.0.1
[1.0.0]: https://github.com/MADEVAL/ReadSight/releases/tag/v1.0.0
