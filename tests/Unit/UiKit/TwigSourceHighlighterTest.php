<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\UiKit;

use EasyCorp\Bundle\EasyAdminBundle\UiKit\TwigSourceHighlighter;
use PHPUnit\Framework\TestCase;

class TwigSourceHighlighterTest extends TestCase
{
    /**
     * @dataProvider provideCode
     */
    public function testHighlight(string $code, string $expectedHtml): void
    {
        $this->assertSame($expectedHtml, (new TwigSourceHighlighter())->highlight($code));
    }

    public static function provideCode(): iterable
    {
        yield 'plain text' => ['Paid', 'Paid'];

        yield 'component with attributes' => [
            '<twig:ea:Badge variant="success" icon=\'fa-solid fa-check\'>Paid</twig:ea:Badge>',
            '<span class="hl-tag">&lt;<span class="hl-name">twig:ea:Badge</span> <span class="hl-attr">variant</span>=<span class="hl-string">&quot;success&quot;</span> <span class="hl-attr">icon</span>=<span class="hl-string">&#039;fa-solid fa-check&#039;</span>&gt;</span>Paid<span class="hl-tag">&lt;/<span class="hl-name">twig:ea:Badge</span>&gt;</span>',
        ];

        yield 'self-closing tag and boolean attribute' => [
            '<twig:ea:Switch checked />',
            '<span class="hl-tag">&lt;<span class="hl-name">twig:ea:Switch</span> <span class="hl-attr">checked</span> /&gt;</span>',
        ];

        yield 'Twig tag' => [
            '{% for status in statuses %}',
            '<span class="hl-template-tag">{% <span class="hl-keyword">for</span> status in statuses %}</span>',
        ];

        yield 'Twig expression inside an attribute value' => [
            '<a href="{{ url }}">',
            '<span class="hl-tag">&lt;<span class="hl-name">a</span> <span class="hl-attr">href</span>=<span class="hl-string">&quot;{{ url }}&quot;</span>&gt;</span>',
        ];

        yield 'Twig expression' => [
            '{{ order.status|upper }}',
            '<span class="hl-template-variable">{{ order.status|upper }}</span>',
        ];

        yield 'comments' => [
            "{# a Twig comment #}\n<!-- an HTML comment -->",
            "<span class=\"hl-comment\">{# a Twig comment #}</span>\n<span class=\"hl-comment\">&lt;!-- an HTML comment --&gt;</span>",
        ];

        yield 'text is always escaped' => [
            'Tom & Jerry <3 "quotes"',
            'Tom &amp; Jerry &lt;3 &quot;quotes&quot;',
        ];

        yield 'attributes are always escaped' => [
            '<b title="<script>alert(1)</script>">',
            '<span class="hl-tag">&lt;<span class="hl-name">b</span> <span class="hl-attr">title</span>=<span class="hl-string">&quot;&lt;script&gt;alert(1)&lt;/script&gt;&quot;</span>&gt;</span>',
        ];
    }
}
