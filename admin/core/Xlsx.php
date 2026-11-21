<?php
/**
 * Minimal .xlsx writer with no dependencies (no ZipArchive / Composer needed).
 * Builds a single-sheet workbook with a title block, a styled header row,
 * numeric cells, auto column widths and an optional totals row.
 */
class Xlsx
{
    /**
     * @param string[] $headers
     * @param string[] $kinds   per column: money|count|other
     * @param array    $rows    rows of cells (string|int|float|null)
     * @param string[] $meta    lines shown above the table (title, filters, ...)
     */
    public static function build(string $sheetName, array $meta, array $headers, array $kinds, array $rows, ?array $totals): string
    {
        $colCount = count($headers);
        $r = 1;
        $xmlRows = '';

        foreach ($meta as $i => $line) {
            $xmlRows .= '<row r="' . $r . '">' . self::str($r, 0, $line, $i === 0 ? 4 : 5) . '</row>';
            $r++;
        }
        $r++; // blank line

        $headerRow = $r;
        $cells = '';
        foreach ($headers as $c => $h) {
            $cells .= self::str($r, $c, $h, 1);
        }
        $xmlRows .= '<row r="' . $r . '" ht="24" customHeight="1">' . $cells . '</row>';
        $r++;

        $widths = array_map(static fn ($h) => mb_strlen($h) + 4, $headers);
        foreach ($rows as $row) {
            $cells = '';
            foreach ($row as $c => $v) {
                $cells .= self::cell($r, $c, $v, $kinds[$c] ?? 'text');
                $len = is_string($v) ? mb_strlen($v) : strlen((string) $v) + 3;
                $widths[$c] = max($widths[$c], min(60, $len + 2));
            }
            $xmlRows .= '<row r="' . $r . '">' . $cells . '</row>';
            $r++;
        }

        if ($totals !== null) {
            $cells = '';
            foreach ($totals as $c => $v) {
                if ($c === 0 && $v === null) {
                    $cells .= self::str($r, 0, 'Total', 3);
                } elseif ($v === null) {
                    $cells .= '<c r="' . self::ref($r, $c) . '" s="3"/>';
                } else {
                    $cells .= '<c r="' . self::ref($r, $c) . '" s="' . (($kinds[$c] ?? '') === 'money' ? 6 : 7) . '"><v>' . self::num($v) . '</v></c>';
                }
            }
            $xmlRows .= '<row r="' . $r . '">' . $cells . '</row>';
        }

        $cols = '';
        foreach ($widths as $i => $w) {
            $cols .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . min(60, max(10, $w)) . '" customWidth="1"/>';
        }

        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="' . $headerRow . '" topLeftCell="A' . ($headerRow + 1) . '" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<cols>' . $cols . '</cols><sheetData>' . $xmlRows . '</sheetData>'
            . '<autoFilter ref="A' . $headerRow . ':' . self::ref($headerRow, max(0, $colCount - 1)) . '"/>'
            . '</worksheet>';

        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="1"><numFmt numFmtId="164" formatCode="#,##0.00"/></numFmts>'
            . '<fonts count="4">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="15"/><color rgb="FF2F55C9"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="4"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF2F55C9"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFEEF1FB"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border><left style="thin"><color rgb="FFD9DCE8"/></left><right style="thin"><color rgb="FFD9DCE8"/></right><top style="thin"><color rgb="FFD9DCE8"/></top><bottom style="thin"><color rgb="FFD9DCE8"/></bottom><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="8">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"><alignment vertical="top" wrapText="1"/></xf>'                       // 0 body text
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment vertical="center"/></xf>'    // 1 header
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"><alignment vertical="top"/></xf>'            // 2 money
            . '<xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>'                                       // 3 total label
            . '<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1"/>'                                                                     // 4 title
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'                                                                                   // 5 meta
            . '<xf numFmtId="164" fontId="2" fillId="3" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1"/>'              // 6 total money
            . '<xf numFmtId="1" fontId="2" fillId="3" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1"/>'                // 7 total count
            . '</cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';

        $name = htmlspecialchars(mb_substr(preg_replace('/[\\\\\/?*\[\]:]/', ' ', $sheetName) ?? 'Report', 0, 31), ENT_XML1);

        return self::zip([
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="' . $name . '" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>',
            'xl/styles.xml' => $styles,
            'xl/worksheets/sheet1.xml' => $sheet,
        ]);
    }

    private static function ref(int $row, int $col): string
    {
        $letters = '';
        for ($n = $col + 1; $n > 0; $n = intdiv($n - 1, 26)) {
            $letters = chr(65 + ($n - 1) % 26) . $letters;
        }
        return $letters . $row;
    }

    private static function num($v): string
    {
        return rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.') ?: '0';
    }

    private static function clean(string $s): string
    {
        // Strip characters that are illegal in XML 1.0.
        $s = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $s) ?? '';
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private static function str(int $row, int $col, string $text, int $style): string
    {
        return '<c r="' . self::ref($row, $col) . '" s="' . $style . '" t="inlineStr"><is><t xml:space="preserve">' . self::clean($text) . '</t></is></c>';
    }

    private static function cell(int $row, int $col, $v, string $kind): string
    {
        if ($v === null || $v === '') {
            return '<c r="' . self::ref($row, $col) . '" s="0"/>';
        }
        if ($kind === 'money' || $kind === 'count') {
            return '<c r="' . self::ref($row, $col) . '" s="' . ($kind === 'money' ? 2 : 0) . '"><v>' . self::num($v) . '</v></c>';
        }
        return self::str($row, $col, (string) $v, 0); // strings only -> never treated as formulas
    }

    /** Stores files in a ZIP archive without compression (valid for .xlsx). */
    private static function zip(array $files): string
    {
        $data = '';
        $central = '';
        $offset = 0;
        $time = 0;
        $date = (1 << 5) | 1 | ((2024 - 1980) << 9);

        foreach ($files as $name => $content) {
            $crc = crc32($content);
            $size = strlen($content);
            $header = pack('VvvvvvVVVvv', 0x04034b50, 20, 0x0800, 0, $time, $date, $crc, $size, $size, strlen($name), 0) . $name;
            $data .= $header . $content;
            $central .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0x0800, 0, $time, $date, $crc, $size, $size, strlen($name), 0, 0, 0, 0, 0, $offset) . $name;
            $offset += strlen($header) + $size;
        }

        return $data . $central . pack('VvvvvVVv', 0x06054b50, 0, 0, count($files), count($files), strlen($central), $offset, 0);
    }
}
