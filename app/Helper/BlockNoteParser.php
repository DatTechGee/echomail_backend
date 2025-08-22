<?php

namespace App\Helper;

class BlockNoteParser
{
    public static function parse($jsonContent)
    {
        try {
            $blocks = json_decode($jsonContent, true);
            if (!is_array($blocks)) {
                return '';
            }

            $html = '';
            foreach ($blocks as $block) {
                $html .= self::renderBlock($block);
            }

            return $html;
        } catch (\Exception $e) {
            \Log::error('Error parsing BlockNote content: ' . $e->getMessage());
            return '';
        }
    }

    private static function renderBlock($block)
    {
        $type = $block['type'] ?? '';
        $props = $block['props'] ?? [];
        $content = $block['content'] ?? [];
        $children = $block['children'] ?? [];

        $html = '';

        switch ($type) {
            case 'heading':
                $level = $props['level'] ?? 2;
                $html .= "<h{$level}>" . self::renderInlineContent($content) . "</h{$level}>";
                break;

            case 'paragraph':
                if (!empty($content) || !empty($children)) {
                    $html .= "<p>" . self::renderInlineContent($content);
                    foreach ($children as $child) {
                        $html .= self::renderBlock($child);
                    }
                    $html .= "</p>";
                }
                break;

            case 'bulletListItem':
                $html .= "<li>" . self::renderInlineContent($content);
                foreach ($children as $child) {
                    $html .= self::renderBlock($child);
                }
                $html .= "</li>";
                // Wrap in <ul> if it's the first item in a sequence
                if (!isset($GLOBALS['in_list'])) {
                    $html = "<ul>" . $html;
                    $GLOBALS['in_list'] = true;
                }
                // Close the list if it's the last item
                if (empty($children)) {
                    $html .= "</ul>";
                    unset($GLOBALS['in_list']);
                }
                break;

            case 'orderedListItem':
                $html .= "<li>" . self::renderInlineContent($content);
                foreach ($children as $child) {
                    $html .= self::renderBlock($child);
                }
                $html .= "</li>";
                if (!isset($GLOBALS['in_ordered_list'])) {
                    $html = "<ol>" . $html;
                    $GLOBALS['in_ordered_list'] = true;
                }
                if (empty($children)) {
                    $html .= "</ol>";
                    unset($GLOBALS['in_ordered_list']);
                }
                break;

            case 'image':
                $url = $props['url'] ?? '';
                $alt = $props['alt'] ?? '';
                if ($url) {
                    $html .= "<img src=\"{$url}\" alt=\"{$alt}\" style=\"max-width: 100%; height: auto;\">";
                }
                break;

            case 'link':
                $url = $props['url'] ?? '';
                if ($url) {
                    $html .= "<a href=\"{$url}\">" . self::renderInlineContent($content) . "</a>";
                }
                break;

            default:
                if (!empty($content)) {
                    $html .= self::renderInlineContent($content);
                }
                foreach ($children as $child) {
                    $html .= self::renderBlock($child);
                }
        }

        return $html;
    }

    private static function renderInlineContent($content)
    {
        $html = '';
        foreach ($content as $item) {
            $text = $item['text'] ?? '';
            $styles = $item['styles'] ?? [];

            if (!empty($styles)) {
                if (!empty($styles['bold'])) {
                    $text = "<strong>{$text}</strong>";
                }
                if (!empty($styles['italic'])) {
                    $text = "<em>{$text}</em>";
                }
                if (!empty($styles['underline'])) {
                    $text = "<u>{$text}</u>";
                }
                if (!empty($styles['strike'])) {
                    $text = "<s>{$text}</s>";
                }
                if (!empty($styles['code'])) {
                    $text = "<code>{$text}</code>";
                }
            }

            $html .= $text;
        }

        return $html;
    }
}

