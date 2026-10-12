<?php

namespace EasyCorp\Bundle\EasyAdminBundle\UiKit;

/**
 * Adds syntax highlighting to the short Twig snippets of the UI Kit.
 * It only understands what those snippets use (HTML and component tags, their
 * attributes, Twig tags, Twig expressions and comments), so it's not a general
 * purpose Twig highlighter.
 *
 * The returned HTML escapes all the given code, so it's safe to output as is.
 *
 * @internal
 */
final class TwigSourceHighlighter
{
    private const TOKEN_PATTERN = '~
        (?<twig_comment>\{\#.*?\#\})
        | (?<twig_tag>\{%-?\s*(?<twig_tag_name>\w+)(?<twig_tag_body>.*?)-?%\})
        | (?<twig_expression>\{\{.*?\}\})
        | (?<html_comment><!--.*?-->)
        | (?<html_tag></?(?<html_tag_name>[a-zA-Z][\w:.-]*)(?<html_tag_attributes>(?:\s+[^\s=>/]+(?:\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+))?)*)\s*/?>)
    ~xs';

    private const ATTRIBUTE_PATTERN = '~(?<name>[^\s=>/]+)(?:(?<equals>\s*=\s*)(?<value>"[^"]*"|\'[^\']*\'|[^\s>]+))?~';

    public function highlight(string $code): string
    {
        $html = '';
        $offset = 0;

        preg_match_all(self::TOKEN_PATTERN, $code, $matches, \PREG_SET_ORDER | \PREG_OFFSET_CAPTURE | \PREG_UNMATCHED_AS_NULL);
        foreach ($matches as $match) {
            [$token, $tokenOffset] = $match[0];
            $html .= $this->escape(substr($code, $offset, $tokenOffset - $offset));
            $html .= $this->highlightToken($match);
            $offset = $tokenOffset + \strlen($token);
        }

        return $html.$this->escape(substr($code, $offset));
    }

    /**
     * @param array<int|string, array{0: string|null, 1: int}> $match
     */
    private function highlightToken(array $match): string
    {
        if (null !== $match['twig_comment'][0] || null !== $match['html_comment'][0]) {
            return $this->span('comment', $this->escape($match[0][0]));
        }

        if (null !== $match['twig_expression'][0]) {
            return $this->span('template-variable', $this->escape($match[0][0]));
        }

        if (null !== $match['twig_tag'][0]) {
            $token = $match['twig_tag'][0];
            $tagName = $match['twig_tag_name'][0];
            $tagNamePosition = $match['twig_tag_name'][1] - $match['twig_tag'][1];

            return $this->span('template-tag',
                $this->escape(substr($token, 0, $tagNamePosition))
                .$this->span('keyword', $this->escape($tagName))
                .$this->escape(substr($token, $tagNamePosition + \strlen($tagName)))
            );
        }

        return $this->highlightHtmlTag($match['html_tag'][0], (string) $match['html_tag_name'][0], (string) $match['html_tag_attributes'][0]);
    }

    private function highlightHtmlTag(string $token, string $tagName, string $attributes): string
    {
        $opening = str_starts_with($token, '</') ? '</' : '<';
        $closing = substr($token, \strlen($opening) + \strlen($tagName) + \strlen($attributes));

        // the pattern matches every non-blank part of the attributes, so the only
        // text left unescaped by this replacement is the whitespace between them
        $highlightedAttributes = preg_replace_callback(self::ATTRIBUTE_PATTERN, function (array $attribute): string {
            $highlighted = $this->span('attr', $this->escape($attribute['name']));
            if ('' !== ($attribute['value'] ?? '')) {
                $highlighted .= $this->escape($attribute['equals']).$this->span('string', $this->escape($attribute['value']));
            }

            return $highlighted;
        }, $attributes);

        return $this->span('tag', $this->escape($opening).$this->span('name', $this->escape($tagName)).$highlightedAttributes.$this->escape($closing));
    }

    private function span(string $type, string $html): string
    {
        return sprintf('<span class="hl-%s">%s</span>', $type, $html);
    }

    private function escape(string $text): string
    {
        return htmlspecialchars($text, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
    }
}
