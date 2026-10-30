<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Profiler;

use Doctrine\SqlFormatter\HtmlHighlighter;
use Doctrine\SqlFormatter\SqlFormatter;

/**
 * Highlights the query with the same CSS classes as the Doctrine profiler panel. Only highlights the query and keeps the original
 * formatting.
 */
final readonly class SqlHighlighter
{
    private SqlFormatter $sqlFormatter;

    public function __construct()
    {
        $this->sqlFormatter = new SqlFormatter(new HtmlHighlighter([
            HtmlHighlighter::HIGHLIGHT_PRE => 'class="highlight highlight-sql"',
            HtmlHighlighter::HIGHLIGHT_QUOTE => 'class="string"',
            HtmlHighlighter::HIGHLIGHT_BACKTICK_QUOTE => 'class="string"',
            HtmlHighlighter::HIGHLIGHT_RESERVED => 'class="keyword"',
            HtmlHighlighter::HIGHLIGHT_BOUNDARY => 'class="symbol"',
            HtmlHighlighter::HIGHLIGHT_NUMBER => 'class="number"',
            HtmlHighlighter::HIGHLIGHT_WORD => 'class="word"',
            HtmlHighlighter::HIGHLIGHT_COMMENT => 'class="comment"',
            HtmlHighlighter::HIGHLIGHT_VARIABLE => 'class="variable"',
        ]));
    }

    public function highlight(string $sql): string
    {
        return $this->sqlFormatter->highlight($sql);
    }
}
