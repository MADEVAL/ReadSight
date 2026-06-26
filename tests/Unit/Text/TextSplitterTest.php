<?php

declare(strict_types=1);

namespace GlobusStudio\ReadSight\Tests\Unit\Text;

use GlobusStudio\ReadSight\Language\Language;
use GlobusStudio\ReadSight\Text\TextSplitter;
use PHPUnit\Framework\TestCase;

final class TextSplitterTest extends TestCase
{
    private function createTestLanguage(): Language
    {
        return new Language([
            'code' => 'en-us',
            'name' => 'English (US)',
            'nativeName' => 'English (US)',
            'script' => 'Latin',
            'hyphenMins' => ['left' => 2, 'right' => 2],
            'letterPattern' => '[A-Za-z]',
            'wordSplitPattern' => "[^A-Za-z'’-]+",
            'sentenceBoundaryPattern' => '[.!?]+',
        ]);
    }

    public function test_split_words_simple(): void
    {
        $splitter = new TextSplitter($this->createTestLanguage());
        $words = $splitter->splitWords('The quick brown fox');
        $this->assertSame(['The', 'quick', 'brown', 'fox'], $words);
    }

    public function test_split_words_with_punctuation(): void
    {
        $splitter = new TextSplitter($this->createTestLanguage());
        $words = $splitter->splitWords('Hello, world! How are you?');
        $this->assertSame(['Hello', 'world', 'How', 'are', 'you'], $words);
    }

    public function test_split_words_empty(): void
    {
        $splitter = new TextSplitter($this->createTestLanguage());
        $words = $splitter->splitWords('');
        $this->assertSame([], $words);
    }

    public function test_count_words(): void
    {
        $splitter = new TextSplitter($this->createTestLanguage());
        $this->assertSame(4, $splitter->countWords('The quick brown fox'));
    }

    public function test_count_words_empty(): void
    {
        $splitter = new TextSplitter($this->createTestLanguage());
        $this->assertSame(0, $splitter->countWords(''));
    }

    public function test_count_letters(): void
    {
        $splitter = new TextSplitter($this->createTestLanguage());
        $this->assertSame(13, $splitter->countLetters('The quick brown'));
    }

    public function test_count_letters_empty(): void
    {
        $splitter = new TextSplitter($this->createTestLanguage());
        $this->assertSame(0, $splitter->countLetters(''));
    }

    public function test_count_letters_only_letters(): void
    {
        $splitter = new TextSplitter($this->createTestLanguage());
        $this->assertSame(10, $splitter->countLetters('Hello 123 world!'));
    }

    public function test_count_sentences(): void
    {
        $splitter = new TextSplitter($this->createTestLanguage());
        $this->assertSame(3, $splitter->countSentences('One. Two! Three?'));
    }

    public function test_count_sentences_empty(): void
    {
        $splitter = new TextSplitter($this->createTestLanguage());
        $this->assertSame(0, $splitter->countSentences(''));
    }

    public function test_count_long_words(): void
    {
        $splitter = new TextSplitter($this->createTestLanguage());
        $count = $splitter->countLongWords('short but longer words here', 5);
        $this->assertSame(1, $count);
    }

    public function test_russian_text(): void
    {
        $russian = new Language([
            'code' => 'ru',
            'name' => 'Russian',
            'nativeName' => 'Русский',
            'script' => 'Cyrillic',
            'hyphenMins' => ['left' => 2, 'right' => 2],
            'letterPattern' => '[А-Яа-яЁё]',
            'wordSplitPattern' => "[^А-Яа-яЁё'’-]+",
            'sentenceBoundaryPattern' => '[.!?…]+',
        ]);

        $splitter = new TextSplitter($russian);
        $words = $splitter->splitWords('Привет, мир! Как дела?');
        $this->assertCount(4, $words);
        $this->assertContains('Привет', $words);
        $this->assertContains('мир', $words);
    }

    // --- Boundary tests ---

    public function test_words_with_hyphens(): void
    {
        $splitter = new TextSplitter($this->createTestLanguage());
        $words = $splitter->splitWords('self-contained well-known re-evaluate');
        $this->assertCount(3, $words);
        $this->assertSame('self-contained', $words[0]);
    }

    public function test_words_with_numbers_are_split(): void
    {
        $splitter = new TextSplitter($this->createTestLanguage());
        $words = $splitter->splitWords('Hello 123 world 456test');
        $this->assertContains('Hello', $words);
        $this->assertContains('world', $words);
        $this->assertContains('test', $words);
    }

    public function test_multiple_spaces_collapsed(): void
    {
        $splitter = new TextSplitter($this->createTestLanguage());
        $words = $splitter->splitWords('Hello    world');
        $this->assertSame(['Hello', 'world'], $words);
    }

    public function test_words_with_apostrophes(): void
    {
        $splitter = new TextSplitter($this->createTestLanguage());
        $words = $splitter->splitWords("don't can't it's");
        $this->assertSame(["don't", "can't", "it's"], $words);
    }

    public function test_no_sentence_boundary_returns_one_sentence(): void
    {
        $splitter = new TextSplitter($this->createTestLanguage());
        $this->assertSame(1, $splitter->countSentences('Hello world without punctuation'));
    }

    public function test_only_punctuation_returns_zero_words(): void
    {
        $splitter = new TextSplitter($this->createTestLanguage());
        $words = $splitter->splitWords('!!! ??? ...');
        $this->assertSame([], $words);
    }

    public function test_letters_with_special_chars(): void
    {
        $splitter = new TextSplitter($this->createTestLanguage());
        $count = $splitter->countLetters('Hello @world #2024!');
        $this->assertSame(10, $count);
    }

    public function test_words_with_newlines_and_tabs(): void
    {
        $splitter = new TextSplitter($this->createTestLanguage());
        $words = $splitter->splitWords("Hello\tworld\nagain");
        $this->assertSame(['Hello', 'world', 'again'], $words);
    }
}
