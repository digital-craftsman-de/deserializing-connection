<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Profiler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SqlHighlighter::class)]
final class SqlHighlighterTest extends TestCase
{
    #[Test]
    public function highlight_works(): void
    {
        // -- Arrange
        $sql = <<<'SQL'
            SELECT `a`, 'b', 1, :name -- comment
                FROM t
            SQL;

        $expectedHtml = <<<'HTML'
            <pre class="highlight highlight-sql"><span class="keyword">SELECT</span> <span class="string">`a`</span><span class="symbol">,</span> <span class="string">'b'</span><span class="symbol">,</span> <span class="number">1</span><span class="symbol">,</span> <span class="variable">:name</span> <span class="comment">-- comment</span>
                <span class="keyword">FROM</span> <span class="word">t</span></pre>
            HTML;

        // -- Act
        $html = new SqlHighlighter()->highlight($sql);

        // -- Assert
        self::assertSame($expectedHtml, $html);
    }
}
