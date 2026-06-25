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
}

