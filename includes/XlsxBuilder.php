<?php

class XlsxBuilder
{
    private string $sheetName;
    private array $colWidths = [];
    private array $rows = [];

    public function __construct(string $sheetName = 'Hoja1')
    {
        $this->sheetName = $sheetName;
    }

    public function setColWidths(array $widths): void
    {
        $this->colWidths = $widths;
    }

    /** @param array[] $cells Cada celda: ['v' => valor, 's' => idEstilo] */
    public function addRow(array $cells): void
    {
        $this->rows[] = $cells;
    }

    public function output(): string
    {
        $files = [];

        $files['[Content_Types].xml'] = $this->contentTypesXml();
        $files['_rels/.rels'] = $this->rootRelsXml();
        $files['xl/workbook.xml'] = $this->workbookXml();
        $files['xl/_rels/workbook.xml.rels'] = $this->workbookRelsXml();
        $files['xl/styles.xml'] = $this->stylesXml();
        $files['xl/worksheets/sheet1.xml'] = $this->sheetXml();

        return $this->zip($files);
    }

    private function sheetXml(): string
    {
        $cols = '';
        foreach ($this->colWidths as $i => $w) {
            $cols .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $w . '" customWidth="1"/>';
        }

        $body = '';
        $rowNum = 1;
        foreach ($this->rows as $cells) {
            $rowCells = '';
            $colNum = 1;
            foreach ($cells as $cell) {
                $ref = $this->colLetter($colNum) . $rowNum;
                $v = (string) ($cell['v'] ?? '');
                $style = $cell['s'] ?? 0;

                if (is_numeric($v) && $v !== '') {
                    $rowCells .= '<c r="' . $ref . '" s="' . $style . '"><v>' . $this->esc($v) . '</v></c>';
                } else {
                    $rowCells .= '<c r="' . $ref . '" s="' . $style . '" t="inlineStr"><is><t>' . $this->esc($v) . '</t></is></c>';
                }
                $colNum++;
            }
            $body .= '<row r="' . $rowNum . '">' . $rowCells . '</row>';
            $rowNum++;
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . ($cols ? '<cols>' . $cols . '</cols>' : '')
            . '<sheetData>' . $body . '</sheetData>'
            . '</worksheet>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'

            . '<fonts count="5">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'                                        // 0 default
            . '<font><b/><sz val="14"/><color rgb="FF1F2937"/><name val="Calibri"/></font>'             // 1 title
            . '<font><sz val="10"/><color rgb="FF374151"/><name val="Calibri"/></font>'                 // 2 info
            . '<font><b/><color rgb="FFFFFFFF"/><name val="Calibri"/><sz val="11"/></font>'             // 3 header
            . '<font><b/><color rgb="FFFFFFFF"/><name val="Calibri"/><sz val="11"/></font>'             // 4 group
            . '</fonts>'

            . '<fills count="4">'
            . '<fill><patternFill patternType="none"/></fill>'                                          // 0
            . '<fill><patternFill patternType="gray125"/></fill>'                                       // 1
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF4F46E5"/><bgColor indexed="64"/></patternFill></fill>' // 2 header
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF7C3AED"/><bgColor indexed="64"/></patternFill></fill>' // 3 group
            . '</fills>'

            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'

            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'

            . '<cellXfs count="7">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'                                            // 0 default
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'                             // 1 title
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>'                             // 2 info
            . '<xf numFmtId="0" fontId="3" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'               // 3 header
            . '<xf numFmtId="0" fontId="4" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'               // 4 group
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>' // 5 num
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>' // 6 text
            . '</cellXfs>'

            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    private function workbookXml(): string
    {
        $sheet = $this->esc($this->sheetName);
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . $sheet . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private function workbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    private function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
    }

    private function colLetter(int $n): string
    {
        $s = '';
        while ($n > 0) {
            $n--;
            $s = chr(65 + ($n % 26)) . $s;
            $n = intdiv($n, 26);
        }
        return $s;
    }

    private function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /**
     * Empaqueta en ZIP con método STORE (sin compresión).
     * No requiere la extensión zip: usa CRC32 + estructuras binarias estándar.
     */
    private function zip(array $files): string
    {
        $local = '';
        $central = '';
        $offset = 0;

        foreach ($files as $name => $content) {
            $crc = crc32($content);
            $size = strlen($content);
            $nameLen = strlen($name);

            $local .= pack('VvvvvvVVVvv',
                0x04034b50,          // firma local file header
                20,                  // versión
                0,                   // flags (sin encriptar)
                0,                   // método STORE
                0,                   // time
                0,                   // date
                $crc,
                $size,               // compressed size (igual a uncompressed en STORE)
                $size,               // uncompressed size
                $nameLen,
                0                    // extra length
            ) . $name . $content;

            $central .= pack('VvvvvvvVVVvvvvvVV',
                0x02014b50,          // firma central directory
                0x031E,              // version made by
                20,                  // version needed
                0,                   // flags
                0,                   // method
                0,                   // time
                0,                   // date
                $crc,
                $size,
                $size,
                $nameLen,
                0,                   // extra length
                0,                   // comment length
                0,                   // disk number start
                0,                   // internal attrs
                0,                   // external attrs
                $offset              // relative offset of local header
            ) . $name;

            $offset += strlen($local) === 0 ? 0 : 0;
            $offset = $offset; // placeholder
            $offset += $size + $nameLen + 30;
        }

        $cdSize = strlen($central);

        $eocd = pack('VvvvvVVv',
            0x06054b50,              // firma end of central directory
            0,                       // disk number
            0,                       // disk with central directory
            count($files),           // entries on this disk
            count($files),           // total entries
            $cdSize,
            $offset,                 // size of central directory offset
            0                        // comment length
        );

        return $local . $central . $eocd;
    }
}
