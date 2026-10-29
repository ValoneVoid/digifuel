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

    /**
     * Generate Evaluation Dashboard .xlsx matching Dashboard_Monitoring_BBM MEI 2026.xlsx layout & formulas
     * Row 1: Title "DASHBOARD MONITORING KONSUMSI BBM LIGHT VEHICLE (LV)"
     * Row 4-5: KPI Summary Box referencing Row 24 totals
     * Row 7: Subtitle "Rangkuman Performa per Unit LV"
     * Row 8: 10 Headers (A8:J8)
     * Row 9..N: Unit Evaluation Rows with exact Excel formulas
     * Row N+1: "Total / Rata-rata Nasional" with SUM / AVERAGE / Ratio formulas
     */
    public function downloadEvaluationDashboardXlsx(string $filename, array $headers, array $rows, string $period = '2026-05'): BinaryFileResponse
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'digifuel_eval_xlsx_') . '.xlsx';

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
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <sheets>
        <sheet name="Dashboard Monitoring BBM" sheetId="1" r:id="rId1"/>
    </sheets>
    <calcPr calcId="191029" fullCalcOnLoad="1"/>
</workbook>');

        // 5. xl/styles.xml
        // Styles:
        // s=0: Default
        // s=1: Title Banner (Dark Blue #1F4E78, White Bold 12pt, Centered)
        // s=2: KPI Label (Light Blue #D9E1F2, Muted Bold 9pt, Centered)
        // s=3: KPI Value (Light Blue #D9E1F2, Navy Bold 14pt, Centered)
        // s=4: Table Header (Navy #1F4E78, White Bold 10pt, Bordered)
        // s=5: Normal Data Cell (Bordered)
        // s=6: Wajar Badge Cell (Light Green #C6EFCE, Dark Green #006100 Bold, Centered, Bordered)
        // s=7: Tidak Wajar Badge Cell (Light Red #FFC7CE, Dark Red #9C0006 Bold, Centered, Bordered)
        // s=8: Footer Total Row (Bold, Top/Bottom Border)
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <fonts count="7">
        <font><sz val="10"/><color rgb="FF1E293B"/><name val="Calibri"/></font>
        <font><b/><sz val="12"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>
        <font><b/><sz val="9"/><color rgb="FF334155"/><name val="Calibri"/></font>
        <font><b/><sz val="14"/><color rgb="FF1F4E78"/><name val="Calibri"/></font>
        <font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>
        <font><b/><sz val="10"/><color rgb="FF006100"/><name val="Calibri"/></font>
        <font><b/><sz val="10"/><color rgb="FF9C0006"/><name val="Calibri"/></font>
    </fonts>
    <fills count="6">
        <fill><patternFill patternType="none"/></fill>
        <fill><patternFill patternType="gray125"/></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FF1F4E78"/><bgColor indexed="64"/></patternFill></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FFD9E1F2"/><bgColor indexed="64"/></patternFill></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FFC6EFCE"/><bgColor indexed="64"/></patternFill></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FFFFC7CE"/><bgColor indexed="64"/></patternFill></fill>
    </fills>
    <borders count="2">
        <border><left/><right/><top/><bottom/><diagonal/></border>
        <border>
            <left style="thin"><color rgb="FF94A3B8"/></left>
            <right style="thin"><color rgb="FF94A3B8"/></right>
            <top style="thin"><color rgb="FF94A3B8"/></top>
            <bottom style="thin"><color rgb="FF94A3B8"/></bottom>
            <diagonal/>
        </border>
    </borders>
    <cellStyleXfs count="1">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
    </cellStyleXfs>
    <cellXfs count="9">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
        <xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
        <xf numFmtId="0" fontId="2" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
        <xf numFmtId="0" fontId="3" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
        <xf numFmtId="0" fontId="4" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
        <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/>
        <xf numFmtId="0" fontId="5" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
        <xf numFmtId="0" fontId="6" fillId="5" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
        <xf numFmtId="0" fontId="2" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1"/>
    </cellXfs>
</styleSheet>');

        $rowCount = count($rows);
        $firstDataRow = 9;
        $lastDataRow = max($firstDataRow, $firstDataRow + $rowCount - 1);
        $totalRow = $lastDataRow + 1; // Row 24 when 15 units

        // Calculate cached summary values
        $sumDist = 0.0;
        $sumFuel = 0.0;
        $sumStdRatio = 0.0;
        $sumCost = 0.0;
        $sumWaste = 0.0;
        $sumLoss = 0.0;
        foreach ($rows as $rData) {
            $sumDist += (float)($rData[2] ?? 0);
            $sumFuel += (float)($rData[3] ?? 0);
            $sumStdRatio += (float)($rData[5] ?? 0);
            $sumCost += (float)($rData[7]['value'] ?? 0);
            $sumWaste += (float)($rData[8]['value'] ?? 0);
            $sumLoss += (float)($rData[9]['value'] ?? 0);
        }
        $avgRatio = $sumFuel > 0 ? round($sumDist / $sumFuel, 2) : 0;
        $avgStdRatio = $rowCount > 0 ? round($sumStdRatio / $rowCount, 2) : 8.0;

        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $sheetXml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>' . "\n";

        // Row 1: Title Banner
        $sheetXml .= '<row r="1" ht="26" customHeight="1">';
        $sheetXml .= '<c r="A1" s="1" t="inlineStr"><is><t>DASHBOARD MONITORING KONSUMSI BBM LIGHT VEHICLE (LV)</t></is></c>';
        for ($c = 1; $c < 10; $c++) {
            $colL = $this->columnLetter($c);
            $sheetXml .= "<c r=\"{$colL}1\" s=\"1\"/>";
        }
        $sheetXml .= "</row>\n";

        // Row 4: KPI Header Labels (matches Sheet 1 Row 4 of Dashboard_Monitoring_BBM MEI 2026.xlsx)
        $sheetXml .= '<row r="4" ht="18" customHeight="1">';
        $sheetXml .= '<c r="A4" s="2" t="inlineStr"><is><t>TOTAL JARAK (KM)</t></is></c>';
        $sheetXml .= '<c r="B4" s="2"/>';
        $sheetXml .= '<c r="C4" s="2" t="inlineStr"><is><t>TOTAL PENGISIAN FUEL (L)</t></is></c>';
        $sheetXml .= '<c r="D4" s="2" t="inlineStr"><is><t>RATA-RATA RASIO (KM/L)</t></is></c>';
        $sheetXml .= '<c r="E4" s="2"/>';
        $sheetXml .= '<c r="F4" s="2" t="inlineStr"><is><t>TOTAL BIAYA (IDR)</t></is></c>';
        $sheetXml .= '<c r="G4" s="2"/>';
        $sheetXml .= "</row>\n";

        // Row 5: KPI Formula Values referencing Total Row (C24, D24, E24, H24)
        $sheetXml .= '<row r="5" ht="26" customHeight="1">';
        $sheetXml .= "<c r=\"A5\" s=\"3\"><f>C{$totalRow}</f><v>{$sumDist}</v></c>";
        $sheetXml .= '<c r="B5" s="3"/>';
        $sheetXml .= "<c r=\"C5\" s=\"3\"><f>D{$totalRow}</f><v>{$sumFuel}</v></c>";
        $sheetXml .= "<c r=\"D5\" s=\"3\"><f>E{$totalRow}</f><v>{$avgRatio}</v></c>";
        $sheetXml .= '<c r="E5" s="3"/>';
        $sheetXml .= "<c r=\"F5\" s=\"3\"><f>H{$totalRow}</f><v>{$sumCost}</v></c>";
        $sheetXml .= '<c r="G5" s="3"/>';
        $sheetXml .= "</row>\n";

        // Row 7: Section Title
        $sheetXml .= '<row r="7">';
        $sheetXml .= '<c r="A7" s="8" t="inlineStr"><is><t>Rangkuman Performa per Unit LV</t></is></c>';
        $sheetXml .= "</row>\n";

        // Row 8: Table Headers (A8:J8)
        $sheetXml .= '<row r="8" ht="22" customHeight="1">';
        foreach (array_values($headers) as $colIdx => $headerText) {
            $colLetter = $this->columnLetter($colIdx);
            $escaped = htmlspecialchars((string)$headerText, ENT_XML1);
            $sheetXml .= "<c r=\"{$colLetter}8\" s=\"4\" t=\"inlineStr\"><is><t>{$escaped}</t></is></c>";
        }
        $sheetXml .= "</row>\n";

        // Rows 9..23: Unit Data Rows with exact formulas
        foreach (array_values($rows) as $rowIdx => $rowData) {
            $excelRow = $firstDataRow + $rowIdx;
            $sheetXml .= "<row r=\"{$excelRow}\">";
            foreach (array_values($rowData) as $colIdx => $cellVal) {
                $colLetter = $this->columnLetter($colIdx);
                $styleId = 5;

                if (is_array($cellVal) && isset($cellVal['formula'])) {
                    $formula = htmlspecialchars(ltrim((string)$cellVal['formula'], '='), ENT_XML1);
                    $cachedVal = $cellVal['value'] ?? 0;
                    if ($colIdx === 6) {
                        // Evaluasi per Unit column (G): Green for Wajar, Red for Tidak Wajar
                        $styleId = ($cachedVal === 'Tidak Wajar') ? 7 : 6;
                    }
                    if (is_string($cachedVal) && !is_numeric($cachedVal)) {
                        $escapedVal = htmlspecialchars($cachedVal, ENT_XML1);
                        $sheetXml .= "<c r=\"{$colLetter}{$excelRow}\" s=\"{$styleId}\" t=\"str\"><f>{$formula}</f><v>{$escapedVal}</v></c>";
                    } else {
                        $numVal = (float)$cachedVal;
                        $sheetXml .= "<c r=\"{$colLetter}{$excelRow}\" s=\"{$styleId}\"><f>{$formula}</f><v>{$numVal}</v></c>";
                    }
                } elseif (is_int($cellVal) || is_float($cellVal) || (is_numeric($cellVal) && !preg_match('/^0\d+/', (string)$cellVal))) {
                    $num = (float)$cellVal;
                    $sheetXml .= "<c r=\"{$colLetter}{$excelRow}\" s=\"{$styleId}\"><v>{$num}</v></c>";
                } else {
                    $escaped = htmlspecialchars((string)($cellVal ?? ''), ENT_XML1);
                    $sheetXml .= "<c r=\"{$colLetter}{$excelRow}\" s=\"{$styleId}\" t=\"inlineStr\"><is><t>{$escaped}</t></is></c>";
                }
            }
            $sheetXml .= "</row>\n";
        }

        // Row 24 (Total / Rata-rata Nasional)
        $sheetXml .= "<row r=\"{$totalRow}\" ht=\"20\" customHeight=\"1\">";
        $sheetXml .= "<c r=\"A{$totalRow}\" s=\"8\"/>";
        $sheetXml .= "<c r=\"B{$totalRow}\" s=\"8\" t=\"inlineStr\"><is><t>Total / Rata-rata Nasional</t></is></c>";
        $sheetXml .= "<c r=\"C{$totalRow}\" s=\"8\"><f>SUM(C{$firstDataRow}:C{$lastDataRow})</f><v>{$sumDist}</v></c>";
        $sheetXml .= "<c r=\"D{$totalRow}\" s=\"8\"><f>SUM(D{$firstDataRow}:D{$lastDataRow})</f><v>{$sumFuel}</v></c>";
        $sheetXml .= "<c r=\"E{$totalRow}\" s=\"8\"><f>IF(D{$totalRow}&gt;0, C{$totalRow}/D{$totalRow}, 0)</f><v>{$avgRatio}</v></c>";
        $sheetXml .= "<c r=\"F{$totalRow}\" s=\"8\"><f>AVERAGE(F{$firstDataRow}:F{$lastDataRow})</f><v>{$avgStdRatio}</v></c>";
        $sheetXml .= "<c r=\"G{$totalRow}\" s=\"8\"/>";
        $sheetXml .= "<c r=\"H{$totalRow}\" s=\"8\"><f>SUM(H{$firstDataRow}:H{$lastDataRow})</f><v>{$sumCost}</v></c>";
        $sheetXml .= "<c r=\"I{$totalRow}\" s=\"8\"><f>SUM(I{$firstDataRow}:I{$lastDataRow})</f><v>{$sumWaste}</v></c>";
        $sheetXml .= "<c r=\"J{$totalRow}\" s=\"8\"><f>SUM(J{$firstDataRow}:J{$lastDataRow})</f><v>{$sumLoss}</v></c>";
        $sheetXml .= "</row>\n";

        $sheetXml .= '</sheetData>';
        $sheetXml .= '<mergeCells count="3"><mergeCell ref="A1:J2"/><mergeCell ref="A4:B4"/><mergeCell ref="A5:B5"/></mergeCells>';
        $sheetXml .= '</worksheet>';

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

