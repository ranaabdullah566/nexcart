<?php

namespace App\Support;

/**
 * Turns the plain text an owner types into a page body. Everything is escaped first, then a few
 * harmless conventions become formatting:
 *   "# Title" / "## Title"  headings      "- item" / "* item"  bullet lists      "1. item"  numbered lists
 *   blank line = new paragraph            https://… links become clickable (opened safely)
 */
class PageText
{
    public static function render(?string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", trim((string) $text));
        if ($text === '') {
            return '';
        }
        $html = '';
        foreach (preg_split('/\n{2,}/', $text) as $block) {
            $lines = array_values(array_filter(array_map('rtrim', explode("\n", $block)), fn ($l) => trim($l) !== ''));
            if ($lines === []) {
                continue;
            }
            if (preg_match('/^(#{1,3})\s+(.+)$/', $lines[0], $m)) {
                $level = min(4, strlen($m[1]) + 1);   // # → h2 (the page title is the h1)
                $html .= "<h{$level}>".static::inline($m[2])."</h{$level}>";
                array_shift($lines);
                if ($lines === []) {
                    continue;
                }
            }
            if (count(array_filter($lines, fn ($l) => preg_match('/^\s*[-*•]\s+/', $l))) === count($lines)) {
                $html .= '<ul>'.implode('', array_map(fn ($l) => '<li>'.static::inline(preg_replace('/^\s*[-*•]\s+/', '', $l)).'</li>', $lines)).'</ul>';
            } elseif (count(array_filter($lines, fn ($l) => preg_match('/^\s*\d+[.)]\s+/', $l))) === count($lines)) {
                $html .= '<ol>'.implode('', array_map(fn ($l) => '<li>'.static::inline(preg_replace('/^\s*\d+[.)]\s+/', '', $l)).'</li>', $lines)).'</ol>';
            } else {
                $html .= '<p>'.implode('<br>', array_map(fn ($l) => static::inline($l), $lines)).'</p>';
            }
        }

        return $html;
    }

    /** Escape, then link bare http(s) addresses. */
    private static function inline(string $line): string
    {
        $safe = e($line);

        return preg_replace_callback('~\bhttps?://[^\s<]+~i', function ($m) {
            $url = rtrim($m[0], '.,;:)!?');
            $tail = substr($m[0], strlen($url));

            return '<a href="'.$url.'" target="_blank" rel="noopener noreferrer nofollow">'.$url.'</a>'.$tail;
        }, $safe);
    }
}
