<?php

namespace App\Services\Wiki;

/**
 * Конвертер DokuWiki-разметки в TipTap JSON.
 *
 * Поддерживаемые элементы:
 *  Блоки: заголовки (======..==), списки (* / -), таблицы (| / ^),
 *         блоки кода (<code>), WRAP-блоки, горизонтальные линии (----)
 *  Инлайн: **жирный**, //курсив//, __подчёркнутый__, ~~зачёркнутый__,
 *          ''моноширинный'', [[ссылки]], {{изображения}}, <wrap>теги
 */
class DokuWikiParser
{
    // ─── Публичный API ────────────────────────────────────────────────────────

    /**
     * Извлекает первый заголовок из DokuWiki-разметки.
     * Возвращает null если заголовков нет.
     */
    public function extractTitle(string $dokuwiki): ?string
    {
        $lines = explode("\n", $this->normalizeNewlines($dokuwiki));
        foreach ($lines as $line) {
            if (preg_match('/^={2,6}\s*(.+?)\s*={2,6}\s*$/', $line, $m)) {
                return trim($m[1]);
            }
        }
        return null;
    }

    /** Возвращает ['json' => [...], 'text' => '...'] */
    public function parse(string $dokuwiki): array
    {
        $lines  = explode("\n", $this->normalizeNewlines($dokuwiki));
        $nodes  = $this->parseBlocks($lines);
        $doc    = ['type' => 'doc', 'content' => $nodes ?: [['type' => 'paragraph', 'content' => []]]];
        $text   = $this->extractText($nodes);

        return ['json' => $doc, 'text' => trim($text)];
    }

    // ─── Блочный парсер ───────────────────────────────────────────────────────

    private function parseBlocks(array $lines): array
    {
        $nodes = [];
        $i     = 0;
        $total = count($lines);

        while ($i < $total) {
            $line = $lines[$i];

            // Пустая строка
            if (trim($line) === '') {
                $i++;
                continue;
            }

            // Блок кода <code>
            if (preg_match('/^<code(?:\s+(\w+))?>/i', $line, $m)) {
                [$node, $i] = $this->parseCodeBlock($lines, $i, $m[1] ?? null);
                $nodes[] = $node;
                continue;
            }

            // WRAP-блок → blockquote
            if (preg_match('/^<WRAP\b[^>]*>/i', $line)) {
                [$node, $i] = $this->parseWrapBlock($lines, $i);
                $nodes[] = $node;
                continue;
            }

            // Горизонтальная линия
            if (preg_match('/^-{4,}\s*$/', $line)) {
                $nodes[] = ['type' => 'horizontalRule'];
                $i++;
                continue;
            }

            // Заголовок
            if (preg_match('/^(={2,6})\s*(.+?)\s*={2,6}\s*$/', $line, $m)) {
                $level   = min(6, 7 - strlen($m[1])); // ====== → 1, ===== → 2 ...
                $nodes[] = [
                    'type'    => 'heading',
                    'attrs'   => ['level' => $level],
                    'content' => $this->parseInline($m[2]),
                ];
                $i++;
                continue;
            }

            // Таблица
            if (preg_match('/^\s*[\|^]/', $line)) {
                [$node, $i] = $this->parseTable($lines, $i);
                if ($node) {
                    $nodes[] = $node;
                }
                continue;
            }

            // Список
            if (preg_match('/^( {2,})([*-]) /', $line)) {
                [$node, $i] = $this->parseList($lines, $i, 0);
                if ($node) {
                    $nodes[] = $node;
                }
                continue;
            }

            // Обычный параграф
            [$node, $i] = $this->parseParagraph($lines, $i);
            if ($node) {
                // parseParagraph может вернуть __multi если внутри есть images
                if (($node['type'] ?? '') === '__multi') {
                    foreach ($node['blocks'] as $block) {
                        $nodes[] = $block;
                    }
                } else {
                    $nodes[] = $node;
                }
            }
        }

        return $nodes;
    }

    // ─── Блок кода ────────────────────────────────────────────────────────────

    private function parseCodeBlock(array $lines, int $i, ?string $lang): array
    {
        $codeLines = [];
        $i++; // пропускаем открывающий тег
        while ($i < count($lines)) {
            if (stripos($lines[$i], '</code>') !== false) {
                $i++;
                break;
            }
            $codeLines[] = $lines[$i];
            $i++;
        }
        $node = [
            'type'    => 'codeBlock',
            'attrs'   => ['language' => $lang ?? null],
            'content' => [['type' => 'text', 'text' => implode("\n", $codeLines)]],
        ];
        return [$node, $i];
    }

    // ─── WRAP-блок → blockquote ───────────────────────────────────────────────

    private function parseWrapBlock(array $lines, int $i): array
    {
        $inner = [];
        $depth = 1;
        $i++;
        while ($i < count($lines) && $depth > 0) {
            if (preg_match('/^<WRAP\b/i', $lines[$i])) {
                $depth++;
            }
            if (preg_match('/<\/WRAP>/i', $lines[$i])) {
                $depth--;
                if ($depth === 0) {
                    $i++;
                    break;
                }
            }
            if ($depth > 0) {
                $inner[] = $lines[$i];
            }
            $i++;
        }

        $innerNodes = $this->parseBlocks($inner);
        if (empty($innerNodes)) {
            return [['type' => 'paragraph', 'content' => []], $i];
        }
        $node = ['type' => 'blockquote', 'content' => $innerNodes];
        return [$node, $i];
    }

    // ─── Таблица ──────────────────────────────────────────────────────────────

    private function parseTable(array $lines, int $i): array
    {
        $rows = [];
        while ($i < count($lines) && preg_match('/^\s*[\|^]/', $lines[$i])) {
            $line     = trim($lines[$i]);
            $isHeader = str_starts_with($line, '^');

            // Нормализуем: заменяем ^ на | для единообразия
            $line  = str_replace('^', '|', $line);
            $cells = explode('|', trim($line, '|'));

            $rowCells = [];
            foreach ($cells as $cell) {
                $cell       = trim($cell);
                $cellType   = $isHeader ? 'tableHeader' : 'tableCell';
                $rowCells[] = [
                    'type'    => $cellType,
                    'attrs'   => ['colspan' => 1, 'rowspan' => 1, 'colwidth' => null],
                    'content' => [['type' => 'paragraph', 'content' => $this->parseInline($cell)]],
                ];
            }

            if (!empty($rowCells)) {
                $rows[] = ['type' => 'tableRow', 'content' => $rowCells];
            }
            $i++;
        }

        if (empty($rows)) {
            return [null, $i];
        }

        return [['type' => 'table', 'content' => $rows], $i];
    }

    // ─── Список ───────────────────────────────────────────────────────────────

    private function parseList(array $lines, int $i, int $baseIndent): array
    {
        if (!preg_match('/^( {2,})([*-]) /', $lines[$i], $firstM)) {
            return [null, $i];
        }

        // При вызове из parseBlocks (baseIndent=0) берём отступ первой строки,
        // иначе будет бесконечный цикл на глубоко вложенных списках.
        if ($baseIndent === 0) {
            $baseIndent = strlen($firstM[1]);
        }

        $listType = $firstM[2] === '*' ? 'bulletList' : 'orderedList';
        $items    = [];

        while ($i < count($lines)) {
            $line = $lines[$i];

            if (!preg_match('/^( {2,})([*-]) (.*)$/', $line, $m)) {
                break;
            }

            $indent  = strlen($m[1]);
            $content = $m[3];

            if ($indent < $baseIndent) {
                break; // поднимаемся на уровень выше
            }

            if ($indent > $baseIndent) {
                break; // это вложенный список — его подберёт рекурсия
            }

            $i++;

            // Собираем дочерние пункты (следующий уровень = baseIndent + 2)
            $children    = [];
            $childIndent = $baseIndent + 2;
            if ($i < count($lines) && preg_match('/^( {' . $childIndent . ',})([*-]) /', $lines[$i])) {
                [$childList, $i] = $this->parseList($lines, $i, $childIndent);
                if ($childList) {
                    $children[] = $childList;
                }
            }

            $itemContent = [['type' => 'paragraph', 'content' => $this->parseInline($content)]];
            if ($children) {
                $itemContent = array_merge($itemContent, $children);
            }

            $items[] = ['type' => 'listItem', 'content' => $itemContent];
        }

        if (empty($items)) {
            return [null, $i];
        }

        return [['type' => $listType, 'content' => $items], $i];
    }

    // ─── Параграф ─────────────────────────────────────────────────────────────

    private function parseParagraph(array $lines, int $i): array
    {
        $textLines = [];
        while ($i < count($lines)) {
            $line = $lines[$i];
            if (trim($line) === '') {
                break;
            }
            // Прерываем на блочных элементах
            if (
                preg_match('/^(={2,6})\s*.+?\s*={2,6}\s*$/', $line) ||
                preg_match('/^<code/i', $line) ||
                preg_match('/^<WRAP\b/i', $line) ||
                preg_match('/^-{4,}\s*$/', $line) ||
                preg_match('/^\s*[\|^]/', $line) ||
                preg_match('/^( {2,})[*-] /', $line)
            ) {
                break;
            }
            $textLines[] = $line;
            $i++;
        }

        if (empty($textLines)) {
            return [null, $i];
        }

        $text   = implode(' ', $textLines);
        $inline = $this->parseInline($text);

        if (empty($inline)) {
            return [null, $i];
        }

        // Если есть изображения — разбиваем на отдельные блоки,
        // т.к. TipTap Image — блочный узел, его нельзя вкладывать в paragraph.
        $hasImages = array_filter($inline, fn ($n) => ($n['type'] ?? '') === 'image');
        if (!empty($hasImages)) {
            // Возвращаем составной узел-маркер, обрабатываемый в parseBlocks через
            // вспомогательный метод splitInlineNodes.
            $blocks = $this->splitInlineIntoBlocks($inline);
            // Возвращаем только первый блок; остальные добавит вызывающий код.
            // Вместо этого возвращаем специальный тип-мультиблок.
            return [['type' => '__multi', 'blocks' => $blocks], $i];
        }

        return [['type' => 'paragraph', 'content' => $inline], $i];
    }

    /**
     * Разбивает массив инлайн-узлов (включая image) на массив блочных узлов:
     * текстовые части → paragraph, image → image (блок).
     */
    private function splitInlineIntoBlocks(array $inline): array
    {
        $blocks  = [];
        $textBuf = [];

        $flush = function () use (&$textBuf, &$blocks) {
            $filtered = array_filter($textBuf, fn ($n) => !($n['type'] === 'text' && trim($n['text'] ?? '') === ''));
            if (!empty($filtered)) {
                $blocks[] = ['type' => 'paragraph', 'content' => array_values($filtered)];
            }
            $textBuf = [];
        };

        foreach ($inline as $node) {
            if (($node['type'] ?? '') === 'image') {
                $flush();
                $blocks[] = $node;
            } else {
                $textBuf[] = $node;
            }
        }
        $flush();

        return $blocks;
    }

    // ─── Инлайн-парсер ───────────────────────────────────────────────────────

    private function parseInline(string $text): array
    {
        // Убираем инлайновые wrap-теги: <wrap ...>content</wrap>
        $text = preg_replace('/<\/?wrap[^>]*>/i', '', $text);
        // Убираем WRAP блоки которые оказались инлайн
        $text = preg_replace('/<\/?WRAP[^>]*>/i', '', $text);
        // Убираем HTML-комментарии DokuWiki
        $text = preg_replace('/\/\/.*?\/\/(?=\s|$)/', '', $text);  // нет, //italic// нельзя так убирать!

        // Сбрасываем замену выше и используем токенизацию
        $text = preg_replace('/<\/?wrap[^>]*>/i', '', $text);
        $text = preg_replace('/<\/?WRAP[^>]*>/i', '', $text);

        if (trim($text) === '') {
            return [];
        }

        $nodes   = [];
        $pattern = implode('|', [
            '/\*\*(.+?)\*\*/',           // **bold**
            '/__(.+?)__/',               // __underline__ → bold
            '/~~(.+?)~~/',               // ~~strike~~
            "/''(.+?)''/",               // ''monospace'' → code
            '/\[\[(.+?)\]\]/',           // [[link]] or [[link|title]]
            '/\{\{(.+?)\}\}/',           // {{image}}
            '|//(.+?)//',                // //italic//
        ]);

        // Используем preg_split с захватом разделителей
        $parts = preg_split(
            '/(\*\*(?:[^*]|\*(?!\*))+\*\*|__(?:[^_]|_(?!_))+__|~~(?:[^~]|~(?!~))+~~|\'\'(?:[^\']|\'(?!\'))+\'\'|\[\[.+?\]\]|\{\{.+?\}\}|\/\/(?:[^\/]|\/(?!\/))+\/\/)/',
            $text,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );

        foreach ($parts as $part) {
            if ($part === '' || $part === null) {
                continue;
            }

            // **bold**
            if (preg_match('/^\*\*(.+)\*\*$/s', $part, $m)) {
                foreach ($this->parseInline($m[1]) as $child) {
                    $nodes[] = $this->addMark($child, 'bold');
                }
                continue;
            }

            // __underline__ → bold (TipTap StarterKit не имеет underline)
            if (preg_match('/^__(.+)__$/s', $part, $m)) {
                foreach ($this->parseInline($m[1]) as $child) {
                    $nodes[] = $this->addMark($child, 'bold');
                }
                continue;
            }

            // ~~strike~~
            if (preg_match('/^~~(.+)~~$/s', $part, $m)) {
                foreach ($this->parseInline($m[1]) as $child) {
                    $nodes[] = $this->addMark($child, 'strike');
                }
                continue;
            }

            // ''monospace''
            if (preg_match("/^''(.+)''$/s", $part, $m)) {
                $nodes[] = ['type' => 'text', 'text' => $m[1], 'marks' => [['type' => 'code']]];
                continue;
            }

            // //italic//
            if (preg_match('#^//(.+)//$#s', $part, $m)) {
                foreach ($this->parseInline($m[1]) as $child) {
                    $nodes[] = $this->addMark($child, 'italic');
                }
                continue;
            }

            // [[link|title]] или [[link]]
            if (preg_match('/^\[\[(.+)\]\]$/', $part, $m)) {
                $inner = $m[1];
                if (str_contains($inner, '|')) {
                    [$href, $label] = explode('|', $inner, 2);
                } else {
                    $href  = $inner;
                    $label = $inner;
                }
                $href = trim($href);
                // Внутренние ссылки — оставляем как есть (без домена)
                if (!preg_match('/^https?:\/\//', $href)) {
                    $href = '#page:' . $href;
                }
                $nodes[] = [
                    'type'  => 'text',
                    'text'  => trim($label),
                    'marks' => [['type' => 'link', 'attrs' => ['href' => $href, 'target' => '_blank']]],
                ];
                continue;
            }

            // {{image.jpg?size|alt}}
            if (preg_match('/^\{\{(.+?)\}\}$/', $part, $m)) {
                $inner = $m[1];
                // убираем параметры размера
                $inner = preg_replace('/\?.+/', '', $inner);
                if (str_contains($inner, '|')) {
                    [$src, $alt] = explode('|', $inner, 2);
                } else {
                    $src = $inner;
                    $alt = '';
                }
                $src = ltrim(trim($src), ':');
                $src = str_replace(':', '/', $src); // DokuWiki uses : as namespace separator
                // Ссылка будет заменена после копирования медиафайлов
                $nodes[] = [
                    'type'  => 'image',
                    'attrs' => [
                        'src'   => '/storage/wiki-media/' . $src,
                        'alt'   => trim($alt),
                        'title' => null,
                    ],
                ];
                continue;
            }

            // Обычный текст
            $nodes[] = ['type' => 'text', 'text' => $part];
        }

        return $nodes;
    }

    private function addMark(array $node, string $markType): array
    {
        if ($node['type'] !== 'text') {
            return $node;
        }
        $marks   = $node['marks'] ?? [];
        $marks[] = ['type' => $markType];
        return array_merge($node, ['marks' => $marks]);
    }

    // ─── Вспомогательные ─────────────────────────────────────────────────────

    private function normalizeNewlines(string $text): string
    {
        return str_replace(["\r\n", "\r"], "\n", $text);
    }

    /** Извлекает plain text из TipTap-узлов для поиска/RAG */
    public function extractText(array $nodes): string
    {
        $parts = [];
        foreach ($nodes as $node) {
            if (($node['type'] ?? '') === 'text') {
                $parts[] = $node['text'] ?? '';
            } elseif (!empty($node['content'])) {
                $parts[] = $this->extractText($node['content']);
            }
        }
        return implode(' ', array_filter($parts));
    }
}
