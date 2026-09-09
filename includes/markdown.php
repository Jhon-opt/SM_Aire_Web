<?php
/**
 * Conversor Markdown → HTML (subconjunto).
 *
 * Soporta: encabezados (#), citas (>), tablas (|), bloques de código
 * cercados (```), listas (-, *, 1.), reglas (---), negrita (**),
 * cursiva (*), código en línea (`) y párrafos.
 *
 * @return array{html: string, toc: array, title: string}
 */
function markdown_to_html(string $markdown): array
{
    $lines = preg_split("/\r\n|\r|\n/", $markdown);
    $html = '';
    $toc = [];
    $title = 'Documentación';
    $slugs = [];
    $i = 0;
    $n = count($lines);
    $inCode = false;
    $codeLang = '';
    $codeBuf = [];
    $listTag = null;

    $closeList = function () use (&$html, &$listTag) {
        if ($listTag !== null) {
            $html .= "</{$listTag}>\n";
            $listTag = null;
        }
    };

    $slugify = function (string $text) use (&$slugs): string {
        $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        $slug = strtolower((string) $slug);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        if ($slug === '') {
            $slug = 'seccion';
        }
        if (isset($slugs[$slug])) {
            $slugs[$slug]++;
            $slug .= '-' . $slugs[$slug];
        } else {
            $slugs[$slug] = 1;
        }
        return $slug;
    };

    while ($i < $n) {
        $line = $lines[$i];
        $trim = trim($line);

        // Bloque de código cercado.
        if (str_starts_with($trim, '```')) {
            if (!$inCode) {
                $closeList();
                $inCode = true;
                $codeLang = trim(substr($trim, 3));
                $codeBuf = [];
            } else {
                $inCode = false;
                $cls = $codeLang !== '' ? ' class="language-' . htmlspecialchars($codeLang) . '"' : '';
                $html .= '<pre><code' . $cls . '>'
                    . htmlspecialchars(implode("\n", $codeBuf))
                    . "</code></pre>\n";
            }
            $i++;
            continue;
        }

        if ($inCode) {
            $codeBuf[] = $line;
            $i++;
            continue;
        }

        // Línea vacía: cierra listas abiertas.
        if ($trim === '') {
            $closeList();
            $i++;
            continue;
        }

        // Regla horizontal.
        if (preg_match('/^(-{3,}|\*{3,}|_{3,})$/', $trim)) {
            $closeList();
            $html .= "<hr>\n";
            $i++;
            continue;
        }

        // Encabezados.
        if (preg_match('/^(#{1,4})\s+(.*)$/', $trim, $m)) {
            $closeList();
            $level = strlen($m[1]);
            $text = markdown_inline($m[2]);
            $plain = strip_tags($text);
            $id = $slugify($plain);
            if ($level === 1) {
                $title = $plain;
            }
            if ($level >= 2) {
                $toc[] = ['level' => $level, 'id' => $id, 'text' => $plain];
            }
            $html .= "<h{$level} id=\"{$id}\">{$text}</h{$level}>\n";
            $i++;
            continue;
        }

        // Citas.
        if (str_starts_with($trim, '>')) {
            $closeList();
            $quote = [];
            while ($i < $n && str_starts_with(trim($lines[$i]), '>')) {
                $quote[] = ltrim(preg_replace('/^>\s?/', '', trim($lines[$i])));
                $i++;
            }
            $html .= '<blockquote><p>'
                . implode('<br>', array_map('markdown_inline', $quote))
                . "</p></blockquote>\n";
            continue;
        }

        // Tablas.
        if (str_starts_with($trim, '|') && $i + 1 < $n
            && preg_match('/^\|?[\s:|\-]+\|?$/', trim($lines[$i + 1]))
            && str_contains(trim($lines[$i + 1]), '-')
        ) {
            $closeList();
            $header = array_map('trim', explode('|', trim($trim, '|')));
            $i += 2;
            $html .= "<div class=\"table-responsive\"><table class=\"data-table\">\n<thead>\n<tr>";
            foreach ($header as $cell) {
                $html .= '<th>' . markdown_inline($cell) . '</th>';
            }
            $html .= "</tr>\n</thead>\n<tbody>\n";
            while ($i < $n && str_starts_with(trim($lines[$i]), '|')) {
                $cells = array_map('trim', explode('|', trim(trim($lines[$i]), '|')));
                $html .= '<tr>';
                foreach ($cells as $cell) {
                    $html .= '<td>' . markdown_inline($cell) . '</td>';
                }
                $html .= "</tr>\n";
                $i++;
            }
            $html .= "</tbody>\n</table></div>\n";
            continue;
        }

        // Listas.
        if (preg_match('/^([-*]|\d+\.)\s+(.*)$/', $trim, $m)) {
            $tag = preg_match('/^\d+\.$/', $m[1]) ? 'ol' : 'ul';
            if ($listTag !== $tag) {
                $closeList();
                $html .= "<{$tag}>\n";
                $listTag = $tag;
            }
            $html .= '<li>' . markdown_inline($m[2]) . "</li>\n";
            $i++;
            continue;
        }

        // Párrafo.
        $closeList();
        $html .= '<p>' . markdown_inline($trim) . "</p>\n";
        $i++;
    }

    $closeList();

    return ['html' => $html, 'toc' => $toc, 'title' => $title];
}

/**
 * Formato en línea: código, negrita, cursiva.
 */
function markdown_inline(string $text): string
{
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);
    $text = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text);
    $text = preg_replace('/\*([^*]+)\*/', '<em>$1</em>', $text);
    return $text;
}
