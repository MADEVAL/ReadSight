# Changelog

All notable changes to ReadSight will be documented in this file.

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

[1.0.0]: https://github.com/MADEVAL/ReadSight/releases/tag/v1.0.0
