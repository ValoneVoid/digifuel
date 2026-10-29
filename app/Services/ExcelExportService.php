<?php

namespace App\Services;

use ZipArchive;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExcelExportService
{
    /**
     * Generate a genuine .xlsx Excel file using PHP's native ZipArchive (OpenXML format)
     */
    public function downloadXlsx(string $filename, array $headers, array $rows, string $sheetTitle = 'Laporan DigiFuel'): BinaryFileResponse
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'digifuel_xlsx_') . '.xlsx';

        $zip = new ZipArchive();
        if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Tidak dapat membuat file Excel (.xlsx).');
        }

        // 1. [Content_Types].xml
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
    <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
    <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>');

        // 2. _rels/.rels
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>');

        // 3. xl/_rels/workbook.xml.rels
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>');

        // 4. xl/workbook.xml
        $safeSheetTitle = htmlspecialchars(mb_substr(preg_replace('/[\[\]\*\/\\\?\:]/', ' ', $sheetTitle), 0, 31), ENT_XML1);
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <sheets>
        <sheet name="' . $safeSheetTitle . '" sheetId="1" r:id="rId1"/>
    </sheets>
    <calcPr calcId="191029" fullCalcOnLoad="1"/>
</workbook>');

        // 5. xl/styles.xml (Header bold white on UT Blue #0284C7, bordered data cells)
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <fonts count="2">
        <font><sz val="10"/><color rgb="FF1E293B"/><name val="Calibri"/></font>
        <font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>
    </fonts>
    <fills count="3">
        <fill><patternFill patternType="none"/></fill>
        <fill><patternFill patternType="gray125"/></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FF0284C7"/><bgColor indexed="64"/></patternFill></fill>
    </fills>
    <borders count="2">
        <border><left/><right/><top/><bottom/><diagonal/></border>
        <border>
            <left style="thin"><color rgb="FFCBD5E1"/></left>
            <right style="thin"><color rgb="FFCBD5E1"/></right>
            <top style="thin"><color rgb="FFCBD5E1"/></top>
            <bottom style="thin"><color rgb="FFCBD5E1"/></bottom>
            <diagonal/>
        </border>
    </borders>
    <cellStyleXfs count="1">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
    </cellStyleXfs>
    <cellXfs count="3">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
        <xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>
        <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/>
    </cellXfs>
</styleSheet>');

        // 6. xl/worksheets/sheet1.xml
        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $sheetXml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>' . "\n";

        // Row 1: Headers
        $sheetXml .= '<row r="1">';
        foreach (array_values($headers) as $colIdx => $headerText) {
            $colLetter = $this->columnLetter($colIdx);
            $escaped = htmlspecialchars((string)$headerText, ENT_XML1);
            $sheetXml .= "<c r=\"{$colLetter}1\" s=\"1\" t=\"inlineStr\"><is><t>{$escaped}</t></is></c>";
        }
        $sheetXml .= "</row>\n";

        // Data Rows
        foreach (array_values($rows) as $rowIdx => $rowData) {
            $excelRow = $rowIdx + 2;
            $sheetXml .= "<row r=\"{$excelRow}\">";
            foreach (array_values($rowData) as $colIdx => $cellVal) {
                $colLetter = $this->columnLetter($colIdx);
                if (is_array($cellVal) && isset($cellVal['formula'])) {
                    $formula = htmlspecialchars(ltrim((string)$cellVal['formula'], '='), ENT_XML1);
                    $cachedVal = $cellVal['value'] ?? 0;
                    if (is_string($cachedVal) && !is_numeric($cachedVal)) {
                        $escapedVal = htmlspecialchars($cachedVal, ENT_XML1);
                        $sheetXml .= "<c r=\"{$colLetter}{$excelRow}\" s=\"2\" t=\"str\"><f>{$formula}</f><v>{$escapedVal}</v></c>";
                    } else {
                        $numVal = (float)$cachedVal;
                        $sheetXml .= "<c r=\"{$colLetter}{$excelRow}\" s=\"2\"><f>{$formula}</f><v>{$numVal}</v></c>";
                    }
                } elseif (is_string($cellVal) && str_starts_with($cellVal, '=') && strlen($cellVal) > 1) {
                    $formula = htmlspecialchars(ltrim($cellVal, '='), ENT_XML1);
                    $sheetXml .= "<c r=\"{$colLetter}{$excelRow}\" s=\"2\"><f>{$formula}</f></c>";
                } elseif (is_int($cellVal) || is_float($cellVal) || (is_numeric($cellVal) && !preg_match('/^0\d+/', (string)$cellVal))) {
                    $num = (float)$cellVal;
                    $sheetXml .= "<c r=\"{$colLetter}{$excelRow}\" s=\"2\"><v>{$num}</v></c>";
                } else {
                    $escaped = htmlspecialchars((string)($cellVal ?? ''), ENT_XML1);
                    $sheetXml .= "<c r=\"{$colLetter}{$excelRow}\" s=\"2\" t=\"inlineStr\"><is><t>{$escaped}</t></is></c>";
                }
            }
            $sheetXml .= "</row>\n";
        }

        $sheetXml .= '</sheetData></worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->close();

        if (!str_ends_with(strtolower($filename), '.xlsx')) {
            $filename .= '.xlsx';
        }

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    protected function columnLetter(int $index): string
    {
        $letter = '';
        while ($index >= 0) {
            $letter = chr($index % 26 + 65) . $letter;
            $index = intdiv($index, 26) - 1;
        }
        return $letter;
    }
}

