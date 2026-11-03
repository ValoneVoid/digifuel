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

        // 5. xl/styles.xml
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
     * Generate Evaluation Dashboard .xlsx
     * If $multiSheetData is passed, creates full multi-sheet workbook:
     * Sheet 1: Dashboard (KPI cards + Unit Summary with exact formulas to unit sheets)
     * Sheet 2: Master_Kendaraan (Vehicle catalog lookup)
     * Sheet 3..N: Dedicated Per-Unit sheets (Daily trip logs, fuelings, VLOOKUP & evaluation formulas)
     */
    public function downloadEvaluationDashboardXlsx(
        string $filename,
        array $headers,
        array $rows,
        string $period = '2026-05',
        ?array $multiSheetData = null
    ): BinaryFileResponse {
        if (!empty($multiSheetData)) {
            return $this->generateMultiSheetWorkbook($filename, $multiSheetData);
        }

        // Single-sheet fallback if no multiSheetData supplied
        return $this->generateSingleSheetEvaluation($filename, $headers, $rows, $period);
    }

    /**
     * Generate full multi-sheet workbook matching Dashboard_Monitoring_BBM MEI 2026.xlsx
     */
    protected function generateMultiSheetWorkbook(string $filename, array $data): BinaryFileResponse
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'digifuel_eval_full_') . '.xlsx';

        $zip = new ZipArchive();
        if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Tidak dapat membuat file Excel multi-sheet (.xlsx).');
        }

        $period = $data['period'] ?? '2026-05';
        $price = (float)($data['price'] ?? 27000.0);
        $masterVehicles = $data['master_vehicles'] ?? [];
        $unitSheets = $data['unit_sheets'] ?? [];

        // Build list of all sheets in workbook
        // Sheet 1: Dashboard
        // Sheet 2: Master_Kendaraan
        // Sheet 3..N: Per-unit sheets
        $sheetsMeta = [
            1 => ['name' => 'Dashboard', 'target' => 'worksheets/sheet1.xml'],
            2 => ['name' => 'Master_Kendaraan', 'target' => 'worksheets/sheet2.xml'],
        ];

        $sheetCounter = 3;
        $unitCodeToSheetId = [];
        foreach ($masterVehicles as $mv) {
            $code = $mv['unit_code'];
            $safeName = $this->sanitizeSheetName($code);
            $sheetsMeta[$sheetCounter] = [
                'name' => $safeName,
                'target' => "worksheets/sheet{$sheetCounter}.xml",
                'original_code' => $code,
            ];
            $unitCodeToSheetId[$code] = $sheetCounter;
            $sheetCounter++;
        }

        $totalSheets = count($sheetsMeta);

        // 1. [Content_Types].xml
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $contentTypes .= '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' . "\n";
        $contentTypes .= '    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' . "\n";
        $contentTypes .= '    <Default Extension="xml" ContentType="application/xml"/>' . "\n";
        $contentTypes .= '    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' . "\n";
        $contentTypes .= '    <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' . "\n";
        for ($i = 1; $i <= $totalSheets; $i++) {
            $contentTypes .= "    <Override PartName=\"/xl/worksheets/sheet{$i}.xml\" ContentType=\"application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml\"/>\n";
        }
        $contentTypes .= '</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);

        // 2. _rels/.rels
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>');

        // 3. xl/_rels/workbook.xml.rels
        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $wbRels .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . "\n";
        for ($i = 1; $i <= $totalSheets; $i++) {
            $target = $sheetsMeta[$i]['target'];
            $wbRels .= "    <Relationship Id=\"rId{$i}\" Type=\"http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet\" Target=\"{$target}\"/>\n";
        }
        $stylesRId = $totalSheets + 1;
        $wbRels .= "    <Relationship Id=\"rId{$stylesRId}\" Type=\"http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles\" Target=\"styles.xml\"/>\n";
        $wbRels .= '</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);

        // 4. xl/workbook.xml
        $wbXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $wbXml .= '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' . "\n";
        $wbXml .= '    <sheets>' . "\n";
        for ($i = 1; $i <= $totalSheets; $i++) {
            $sName = htmlspecialchars($sheetsMeta[$i]['name'], ENT_XML1);
            $wbXml .= "        <sheet name=\"{$sName}\" sheetId=\"{$i}\" r:id=\"rId{$i}\"/>\n";
        }
        $wbXml .= '    </sheets>' . "\n";
        $wbXml .= '    <calcPr calcId="191029" fullCalcOnLoad="1"/>' . "\n";
        $wbXml .= '</workbook>';
        $zip->addFromString('xl/workbook.xml', $wbXml);

        // 5. xl/styles.xml (Professional Corporate Theme matching reference workbook)
        $zip->addFromString('xl/styles.xml', $this->getCorporateStyleXml());

        // 6. Generate Sheet 2: Master_Kendaraan
        $zip->addFromString('xl/worksheets/sheet2.xml', $this->buildMasterKendaraanXml($masterVehicles));

        // 7. Generate Sheets 3..N: Dedicated Per-Unit Sheets
        foreach ($masterVehicles as $mv) {
            $code = $mv['unit_code'];
            $sheetId = $unitCodeToSheetId[$code];
            $txList = $unitSheets[$code] ?? [];
            $unitXml = $this->buildUnitDetailXml($mv, $txList, $price);
            $zip->addFromString("xl/worksheets/sheet{$sheetId}.xml", $unitXml);
        }

        // 8. Generate Sheet 1: Dashboard (Calculates and references unit sheets + Master_Kendaraan)
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->buildDashboardXml($masterVehicles, $unitSheets, $price));

        $zip->close();

        if (!str_ends_with(strtolower($filename), '.xlsx')) {
            $filename .= '.xlsx';
        }

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Sheet 2: Master_Kendaraan XML Builder
     */
    protected function buildMasterKendaraanXml(array $masterVehicles): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' . "\n";
        
        // Column Widths
        $xml .= '<cols>';
        $xml .= '<col min="1" max="1" width="27" customWidth="1"/>';
        $xml .= '<col min="2" max="2" width="28" customWidth="1"/>';
        $xml .= '<col min="3" max="3" width="24" customWidth="1"/>';
        $xml .= '<col min="4" max="4" width="24" customWidth="1"/>';
        $xml .= '</cols>' . "\n";

        $xml .= '<sheetData>' . "\n";

        // Row 1: Headers (s=4: Navy Header)
        $xml .= '<row r="1" ht="24" customHeight="1">';
        $xml .= '<c r="A1" s="4" t="inlineStr"><is><t>ID Kendaraan</t></is></c>';
        $xml .= '<c r="B1" s="4" t="inlineStr"><is><t>Tipe Unit</t></is></c>';
        $xml .= '<c r="C1" s="4" t="inlineStr"><is><t>Target Rasio (Km/L)</t></is></c>';
        $xml .= '<c r="D1" s="4" t="inlineStr"><is><t>Kapasitas Tangki (L)</t></is></c>';
        $xml .= '<c r="E1" s="4" t="inlineStr"><is><t>Vendor Unit</t></is></c>';
        $xml .= "</row>\n";

        // Data Rows starting at Row 2
        foreach (array_values($masterVehicles) as $idx => $mv) {
            $rowNum = $idx + 2;
            $code = htmlspecialchars($mv['unit_code'], ENT_XML1);
            $model = htmlspecialchars($mv['model'], ENT_XML1);
            $ratio = (float)($mv['target_ratio'] ?? 8.0);
            $tank = (float)($mv['tank_capacity'] ?? 80.0);
            $vendor = htmlspecialchars($mv['unit_vendor'] ?? 'Internal UT', ENT_XML1);

            $xml .= "<row r=\"{$rowNum}\" ht=\"20\" customHeight=\"1\">";
            $xml .= "<c r=\"A{$rowNum}\" s=\"5\" t=\"inlineStr\"><is><t>{$code}</t></is></c>";
            $xml .= "<c r=\"B{$rowNum}\" s=\"6\" t=\"inlineStr\"><is><t>{$model}</t></is></c>";
            $xml .= "<c r=\"C{$rowNum}\" s=\"8\"><v>{$ratio}</v></c>";
            $xml .= "<c r=\"D{$rowNum}\" s=\"8\"><v>{$tank}</v></c>";
            $xml .= "<c r=\"E{$rowNum}\" s=\"6\" t=\"inlineStr\"><is><t>{$vendor}</t></is></c>";
            $xml .= "</row>\n";
        }

        $xml .= '</sheetData></worksheet>';
        return $xml;
    }

    /**
     * Sheets 3..N: Dedicated Per-Unit Sheet XML Builder
     */
    protected function buildUnitDetailXml(array $vehicle, array $transactions, float $price): string
    {
        $code = $vehicle['unit_code'];
        $escapedCode = htmlspecialchars($code, ENT_XML1);

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' . "\n";

        // Column widths
        $widths = [23, 18, 22, 21, 17, 18, 21, 20, 23, 24, 19, 16, 22, 24];
        $xml .= '<cols>';
        foreach ($widths as $idx => $w) {
            $cNum = $idx + 1;
            $xml .= "<col min=\"{$cNum}\" max=\"{$cNum}\" width=\"{$w}\" customWidth=\"1\"/>";
        }
        $xml .= '</cols>' . "\n";

        $xml .= '<sheetData>' . "\n";

        // Row 1: Headers (A1:N1)
        $headers = [
            'Tanggal', 'ID Kendaraan', 'Tipe Unit', 'Driver',
            'Odo Awal (Km)', 'Odo Akhir (Km)', 'Jarak Tempuh (Km)', 'Pengisian Fuel (L)',
            'Rasio Aktual (Km/L)', 'Rasio Standar (Km/L)', 'Biaya BBM (IDR)', 'Evaluasi',
            'Varian Pemborosan (L)', 'Biaya Kerugian (IDR)'
        ];
        $xml .= '<row r="1" ht="24" customHeight="1">';
        foreach ($headers as $colIdx => $h) {
            $colLetter = $this->columnLetter($colIdx);
            $hEsc = htmlspecialchars($h, ENT_XML1);
            $xml .= "<c r=\"{$colLetter}1\" s=\"4\" t=\"inlineStr\"><is><t>{$hEsc}</t></is></c>";
        }
        $xml .= "</row>\n";

        $txCount = count($transactions);
        $firstRow = 2;
        $lastRow = max($firstRow, $firstRow + $txCount - 1);
        $totalRow = $lastRow + 1;

        $sumDist = 0.0;
        $sumFuel = 0.0;
        $sumCost = 0.0;
        $sumWaste = 0.0;
        $sumLoss = 0.0;
        $stdRatioVal = (float)($vehicle['target_ratio'] ?? 8.0);

        if ($txCount === 0) {
            // One empty or placeholder row to keep formula references intact
            $xml .= "<row r=\"2\" ht=\"20\" customHeight=\"1\">";
            $xml .= "<c r=\"A2\" s=\"11\" t=\"inlineStr\"><is><t>-</t></is></c>";
            $xml .= "<c r=\"B2\" s=\"5\" t=\"inlineStr\"><is><t>{$escapedCode}</t></is></c>";
            $xml .= "<c r=\"C2\" s=\"6\"><f>VLOOKUP(B2, Master_Kendaraan!\$A\$2:\$C\$50, 2, FALSE)</f><v>-</v></c>";
            $xml .= "<c r=\"D2\" s=\"6\" t=\"inlineStr\"><is><t>-</t></is></c>";
            $xml .= "<c r=\"E2\" s=\"8\"><v>0</v></c>";
            $xml .= "<c r=\"F2\" s=\"8\"><v>0</v></c>";
            $xml .= "<c r=\"G2\" s=\"8\"><f>F2-E2</f><v>0</v></c>";
            $xml .= "<c r=\"H2\" s=\"8\"><v>0</v></c>";
            $xml .= "<c r=\"I2\" s=\"9\"><f>IF(H2&gt;0, G2/H2, 0)</f><v>0</v></c>";
            $xml .= "<c r=\"J2\" s=\"8\"><f>VLOOKUP(B2, Master_Kendaraan!\$A\$2:\$C\$50, 3, FALSE)</f><v>{$stdRatioVal}</v></c>";
            $xml .= "<c r=\"K2\" s=\"10\"><f>H2*{$price}</f><v>0</v></c>";
            $xml .= "<c r=\"L2\" s=\"12\"><f>IF(I2&lt;(J2-0.5), \"Tidak Wajar\", \"Wajar\")</f><v>Wajar</v></c>";
            $xml .= "<c r=\"M2\" s=\"9\"><f>IF(I2&lt;J2, H2-(G2/J2), 0)</f><v>0</v></c>";
            $xml .= "<c r=\"N2\" s=\"10\"><f>M2*{$price}</f><v>0</v></c>";
            $xml .= "</row>\n";
            $lastRow = 2;
            $totalRow = 3;
        } else {
            foreach (array_values($transactions) as $tIdx => $t) {
                $r = $firstRow + $tIdx;
                $dateVal = htmlspecialchars($t['date'] ?? '-', ENT_XML1);
                $driver = htmlspecialchars($t['driver'] ?? '-', ENT_XML1);
                $odoStart = (float)($t['odo_start'] ?? 0);
                $odoEnd = (float)($t['odo_end'] ?? 0);
                $dist = (float)($t['distance'] ?? ($odoEnd - $odoStart));
                $fuel = (float)($t['fuel_liters'] ?? 0);
                $actRatio = $fuel > 0 ? round($dist / $fuel, 2) : 0.0;
                $cost = round($fuel * $price, 2);
                $isWajar = $actRatio >= ($stdRatioVal - 0.5);
                $evalText = $isWajar ? 'Wajar' : 'Tidak Wajar';
                $evalStyle = $isWajar ? 12 : 13;
                $waste = ($actRatio < $stdRatioVal && $stdRatioVal > 0) ? round($fuel - ($dist / $stdRatioVal), 2) : 0.0;
                $loss = round($waste * $price, 2);

                $sumDist += $dist;
                $sumFuel += $fuel;
                $sumCost += $cost;
                $sumWaste += $waste;
                $sumLoss += $loss;

                $xml .= "<row r=\"{$r}\" ht=\"20\" customHeight=\"1\">";
                $xml .= "<c r=\"A{$r}\" s=\"11\" t=\"inlineStr\"><is><t>{$dateVal}</t></is></c>";
                $xml .= "<c r=\"B{$r}\" s=\"5\" t=\"inlineStr\"><is><t>{$escapedCode}</t></is></c>";
                $xml .= "<c r=\"C{$r}\" s=\"6\"><f>VLOOKUP(B{$r}, Master_Kendaraan!\$A\$2:\$C\$50, 2, FALSE)</f><v>-</v></c>";
                $xml .= "<c r=\"D{$r}\" s=\"6\" t=\"inlineStr\"><is><t>{$driver}</t></is></c>";
                $xml .= "<c r=\"E{$r}\" s=\"8\"><v>{$odoStart}</v></c>";
                $xml .= "<c r=\"F{$r}\" s=\"8\"><v>{$odoEnd}</v></c>";
                $xml .= "<c r=\"G{$r}\" s=\"8\"><f>F{$r}-E{$r}</f><v>{$dist}</v></c>";
                $xml .= "<c r=\"H{$r}\" s=\"8\"><v>{$fuel}</v></c>";
                $xml .= "<c r=\"I{$r}\" s=\"9\"><f>IF(H{$r}&gt;0, G{$r}/H{$r}, 0)</f><v>{$actRatio}</v></c>";
                $xml .= "<c r=\"J{$r}\" s=\"8\"><f>VLOOKUP(B{$r}, Master_Kendaraan!\$A\$2:\$C\$50, 3, FALSE)</f><v>{$stdRatioVal}</v></c>";
                $xml .= "<c r=\"K{$r}\" s=\"10\"><f>H{$r}*{$price}</f><v>{$cost}</v></c>";
                $xml .= "<c r=\"L{$r}\" s=\"{$evalStyle}\" t=\"str\"><f>IF(I{$r}&lt;(J{$r}-0.5), \"Tidak Wajar\", \"Wajar\")</f><v>{$evalText}</v></c>";
                $xml .= "<c r=\"M{$r}\" s=\"9\"><f>IF(I{$r}&lt;J{$r}, H{$r}-(G{$r}/J{$r}), 0)</f><v>{$waste}</v></c>";
                $xml .= "<c r=\"N{$r}\" s=\"10\"><f>M{$r}*{$price}</f><v>{$loss}</v></c>";
                $xml .= "</row>\n";
            }
        }

        $overallRatio = $sumFuel > 0 ? round($sumDist / $sumFuel, 2) : 0.0;

        // Total / Rata-rata Row
        $xml .= "<row r=\"{$totalRow}\" ht=\"22\" customHeight=\"1\">";
        $xml .= "<c r=\"A{$totalRow}\" s=\"14\"/>";
        $xml .= "<c r=\"B{$totalRow}\" s=\"14\"/>";
        $xml .= "<c r=\"C{$totalRow}\" s=\"14\"/>";
        $xml .= "<c r=\"D{$totalRow}\" s=\"14\" t=\"inlineStr\"><is><t>Total / Rata-rata</t></is></c>";
        $xml .= "<c r=\"E{$totalRow}\" s=\"14\"/>";
        $xml .= "<c r=\"F{$totalRow}\" s=\"14\"/>";
        $xml .= "<c r=\"G{$totalRow}\" s=\"15\"><f>SUM(G{$firstRow}:G{$lastRow})</f><v>{$sumDist}</v></c>";
        $xml .= "<c r=\"H{$totalRow}\" s=\"15\"><f>SUM(H{$firstRow}:H{$lastRow})</f><v>{$sumFuel}</v></c>";
        $xml .= "<c r=\"I{$totalRow}\" s=\"16\"><f>IF(H{$totalRow}&gt;0, G{$totalRow}/H{$totalRow}, 0)</f><v>{$overallRatio}</v></c>";
        $xml .= "<c r=\"J{$totalRow}\" s=\"15\"><f>AVERAGE(J{$firstRow}:J{$lastRow})</f><v>{$stdRatioVal}</v></c>";
        $xml .= "<c r=\"K{$totalRow}\" s=\"17\"><f>SUM(K{$firstRow}:K{$lastRow})</f><v>{$sumCost}</v></c>";
        $xml .= "<c r=\"L{$totalRow}\" s=\"14\"/>";
        $xml .= "<c r=\"M{$totalRow}\" s=\"16\"><f>SUM(M{$firstRow}:M{$lastRow})</f><v>{$sumWaste}</v></c>";
        $xml .= "<c r=\"N{$totalRow}\" s=\"17\"><f>SUM(N{$firstRow}:N{$lastRow})</f><v>{$sumLoss}</v></c>";
        $xml .= "</row>\n";

        $xml .= '</sheetData></worksheet>';
        return $xml;
    }

    /**
     * Sheet 1: Dashboard XML Builder
     */
    protected function buildDashboardXml(array $masterVehicles, array $unitSheets, float $price): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' . "\n";

        // Column Widths
        $widths = [22, 30.2, 23.1, 24, 20, 20, 18, 20, 24, 24];
        $xml .= '<cols>';
        foreach ($widths as $idx => $w) {
            $cNum = $idx + 1;
            $xml .= "<col min=\"{$cNum}\" max=\"{$cNum}\" width=\"{$w}\" customWidth=\"1\"/>";
        }
        $xml .= '</cols>' . "\n";

        $unitCount = count($masterVehicles);
        $firstDataRow = 9;
        $lastDataRow = max($firstDataRow, $firstDataRow + $unitCount - 1);
        $totalRow = $lastDataRow + 1; // Row 24 when 15 units

        // Precompute cached dashboard totals
        $totDist = 0.0;
        $totFuel = 0.0;
        $totCost = 0.0;
        $totWaste = 0.0;
        $totLoss = 0.0;
        $sumStd = 0.0;

        foreach ($masterVehicles as $mv) {
            $code = $mv['unit_code'];
            $std = (float)($mv['target_ratio'] ?? 8.0);
            $sumStd += $std;
            $txs = $unitSheets[$code] ?? [];
            $uDist = 0.0;
            $uFuel = 0.0;
            foreach ($txs as $t) {
                $uDist += (float)($t['distance'] ?? 0);
                $uFuel += (float)($t['fuel_liters'] ?? 0);
            }
            $totDist += $uDist;
            $totFuel += $uFuel;
            $totCost += round($uFuel * $price, 2);
            $uRatio = $uFuel > 0 ? round($uDist / $uFuel, 2) : 0;
            $uWaste = ($uRatio < $std && $std > 0) ? round($uFuel - ($uDist / $std), 2) : 0;
            $totWaste += $uWaste;
            $totLoss += round($uWaste * $price, 2);
        }
        $avgRatio = $totFuel > 0 ? round($totDist / $totFuel, 2) : 0.0;
        $avgStdRatio = $unitCount > 0 ? round($sumStd / $unitCount, 2) : 8.0;

        $xml .= '<sheetData>' . "\n";

        // Row 1 & 2: Title Banner (Merged A1:J2)
        $xml .= '<row r="1" ht="26" customHeight="1">';
        $xml .= '<c r="A1" s="1" t="inlineStr"><is><t>DASHBOARD MONITORING KONSUMSI BBM LIGHT VEHICLE (LV)</t></is></c>';
        for ($c = 1; $c < 10; $c++) {
            $colL = $this->columnLetter($c);
            $xml .= "<c r=\"{$colL}1\" s=\"1\"/>";
        }
        $xml .= "</row>\n";
        $xml .= '<row r="2" ht="12" customHeight="1">';
        for ($c = 0; $c < 10; $c++) {
            $colL = $this->columnLetter($c);
            $xml .= "<c r=\"{$colL}2\" s=\"1\"/>";
        }
        $xml .= "</row>\n";

        // Row 4: KPI Header Labels (A4:B4, C4, D4:E4, F4:G4)
        $xml .= '<row r="4" ht="18" customHeight="1">';
        $xml .= '<c r="A4" s="2" t="inlineStr"><is><t>TOTAL JARAK (KM)</t></is></c>';
        $xml .= '<c r="B4" s="2"/>';
        $xml .= '<c r="C4" s="2" t="inlineStr"><is><t>TOTAL PENGISIAN FUEL (L)</t></is></c>';
        $xml .= '<c r="D4" s="2" t="inlineStr"><is><t>RATA-RATA RASIO (KM/L)</t></is></c>';
        $xml .= '<c r="E4" s="2"/>';
        $xml .= '<c r="F4" s="2" t="inlineStr"><is><t>TOTAL BIAYA (IDR)</t></is></c>';
        $xml .= '<c r="G4" s="2"/>';
        $xml .= "</row>\n";

        // Row 5: KPI Values referencing Row 24 Total (C24, D24, E24, H24)
        $xml .= '<row r="5" ht="28" customHeight="1">';
        $xml .= "<c r=\"A5\" s=\"3\"><f>C{$totalRow}</f><v>{$totDist}</v></c>";
        $xml .= '<c r="B5" s="3"/>';
        $xml .= "<c r=\"C5\" s=\"3\"><f>D{$totalRow}</f><v>{$totFuel}</v></c>";
        $xml .= "<c r=\"D5\" s=\"3\"><f>E{$totalRow}</f><v>{$avgRatio}</v></c>";
        $xml .= '<c r="E5" s="3"/>';
        $xml .= "<c r=\"F5\" s=\"3\"><f>H{$totalRow}</f><v>{$totCost}</v></c>";
        $xml .= '<c r="G5" s="3"/>';
        $xml .= "</row>\n";

        // Row 7: Section Title
        $xml .= '<row r="7" ht="20" customHeight="1">';
        $xml .= '<c r="A7" s="19" t="inlineStr"><is><t>Rangkuman Performa per Unit LV</t></is></c>';
        $xml .= "</row>\n";

        // Row 8: Table Headers (A8:J8)
        $headers = [
            'ID Kendaraan', 'Tipe Unit', 'Total Jarak (Km)', 'Total Pengisian Fuel (L)',
            'Rasio Aktual (Km/L)', 'Rasio Standar (Km/L)', 'Evaluasi per Unit',
            'Total Biaya (IDR)', 'Varian Pemborosan (L)', 'Biaya Kerugian (IDR)'
        ];
        $xml .= '<row r="8" ht="24" customHeight="1">';
        foreach ($headers as $colIdx => $h) {
            $colLetter = $this->columnLetter($colIdx);
            $hEsc = htmlspecialchars($h, ENT_XML1);
            $xml .= "<c r=\"{$colLetter}8\" s=\"4\" t=\"inlineStr\"><is><t>{$hEsc}</t></is></c>";
        }
        $xml .= "</row>\n";

        // Rows 9..N: Unit Evaluation Rows with genuine formulas to Unit Sheets & Master_Kendaraan
        foreach (array_values($masterVehicles) as $idx => $mv) {
            $r = $firstDataRow + $idx;
            $mRow = $idx + 2; // Corresponding row in Master_Kendaraan (starts at row 2)
            $code = $mv['unit_code'];
            $safeSheetName = $this->sanitizeSheetName($code);
            $stdRatio = (float)($mv['target_ratio'] ?? 8.0);

            // Compute cached values from unit sheet transactions
            $txs = $unitSheets[$code] ?? [];
            $uDist = 0.0;
            $uFuel = 0.0;
            foreach ($txs as $t) {
                $uDist += (float)($t['distance'] ?? 0);
                $uFuel += (float)($t['fuel_liters'] ?? 0);
            }
            $uRatio = $uFuel > 0 ? round($uDist / $uFuel, 2) : 0.0;
            $uCost = round($uFuel * $price, 2);
            $isWajar = $uRatio >= ($stdRatio - 0.5);
            $evalText = $isWajar ? 'Wajar' : 'Tidak Wajar';
            $evalStyle = $isWajar ? 12 : 13;
            $uWaste = ($uRatio < $stdRatio && $stdRatio > 0) ? round($uFuel - ($uDist / $stdRatio), 2) : 0.0;
            $uLoss = round($uWaste * $price, 2);

            $xml .= "<row r=\"{$r}\" ht=\"20\" customHeight=\"1\">";
            // Col A: =Master_Kendaraan!A{m}
            $xml .= "<c r=\"A{$r}\" s=\"5\"><f>Master_Kendaraan!A{$mRow}</f><v>" . htmlspecialchars($code, ENT_XML1) . "</v></c>";
            // Col B: =Master_Kendaraan!B{m}
            $xml .= "<c r=\"B{$r}\" s=\"6\"><f>Master_Kendaraan!B{$mRow}</f><v>" . htmlspecialchars($mv['model'], ENT_XML1) . "</v></c>";
            // Col C: =SUM('{unit}'!G2:G1000)
            $xml .= "<c r=\"C{$r}\" s=\"8\"><f>SUM('{$safeSheetName}'!G2:G1000)</f><v>{$uDist}</v></c>";
            // Col D: =SUM('{unit}'!H2:H1000)
            $xml .= "<c r=\"D{$r}\" s=\"8\"><f>SUM('{$safeSheetName}'!H2:H1000)</f><v>{$uFuel}</v></c>";
            // Col E: =IF(D{r}>0, C{r}/D{r}, 0)
            $xml .= "<c r=\"E{$r}\" s=\"9\"><f>IF(D{$r}&gt;0, C{$r}/D{$r}, 0)</f><v>{$uRatio}</v></c>";
            // Col F: =Master_Kendaraan!C{m}
            $xml .= "<c r=\"F{$r}\" s=\"8\"><f>Master_Kendaraan!C{$mRow}</f><v>{$stdRatio}</v></c>";
            // Col G: =IF(E{r}<(F{r}-0.5), "Tidak Wajar", "Wajar")
            $xml .= "<c r=\"G{$r}\" s=\"{$evalStyle}\" t=\"str\"><f>IF(E{$r}&lt;(F{$r}-0.5), \"Tidak Wajar\", \"Wajar\")</f><v>{$evalText}</v></c>";
            // Col H: =SUM('{unit}'!K2:K1000)
            $xml .= "<c r=\"H{$r}\" s=\"10\"><f>SUM('{$safeSheetName}'!K2:K1000)</f><v>{$uCost}</v></c>";
            // Col I: =IF(E{r}<F{r}, D{r}-(C{r}/F{r}), 0)
            $xml .= "<c r=\"I{$r}\" s=\"9\"><f>IF(E{$r}&lt;F{$r}, D{$r}-(C{$r}/F{$r}), 0)</f><v>{$uWaste}</v></c>";
            // Col J: =I{r}*27000
            $xml .= "<c r=\"J{$r}\" s=\"10\"><f>I{$r}*{$price}</f><v>{$uLoss}</v></c>";
            $xml .= "</row>\n";
        }

        // Row Total / Rata-rata Nasional (Row 24 when 15 units)
        $xml .= "<row r=\"{$totalRow}\" ht=\"22\" customHeight=\"1\">";
        $xml .= "<c r=\"A{$totalRow}\" s=\"14\"/>";
        $xml .= "<c r=\"B{$totalRow}\" s=\"14\" t=\"inlineStr\"><is><t>Total / Rata-rata Nasional</t></is></c>";
        $xml .= "<c r=\"C{$totalRow}\" s=\"15\"><f>SUM(C{$firstDataRow}:C{$lastDataRow})</f><v>{$totDist}</v></c>";
        $xml .= "<c r=\"D{$totalRow}\" s=\"15\"><f>SUM(D{$firstDataRow}:D{$lastDataRow})</f><v>{$totFuel}</v></c>";
        $xml .= "<c r=\"E{$totalRow}\" s=\"16\"><f>IF(D{$totalRow}&gt;0, C{$totalRow}/D{$totalRow}, 0)</f><v>{$avgRatio}</v></c>";
        $xml .= "<c r=\"F{$totalRow}\" s=\"15\"><f>AVERAGE(F{$firstDataRow}:F{$lastDataRow})</f><v>{$avgStdRatio}</v></c>";
        $xml .= "<c r=\"G{$totalRow}\" s=\"18\"/>";
        $xml .= "<c r=\"H{$totalRow}\" s=\"17\"><f>SUM(H{$firstDataRow}:H{$lastDataRow})</f><v>{$totCost}</v></c>";
        $xml .= "<c r=\"I{$totalRow}\" s=\"16\"><f>SUM(I{$firstDataRow}:I{$lastDataRow})</f><v>{$totWaste}</v></c>";
        $xml .= "<c r=\"J{$totalRow}\" s=\"17\"><f>SUM(J{$firstDataRow}:J{$lastDataRow})</f><v>{$totLoss}</v></c>";
        $xml .= "</row>\n";

        $xml .= '</sheetData>';

        // Merges for Banner Title and KPI boxes
        $xml .= '<mergeCells count="5">';
        $xml .= '<mergeCell ref="A1:J2"/>';
        $xml .= '<mergeCell ref="A4:B4"/><mergeCell ref="A5:B5"/>';
        $xml .= '<mergeCell ref="D4:E4"/><mergeCell ref="D5:E5"/>';
        $xml .= '<mergeCell ref="F4:G4"/><mergeCell ref="F5:G5"/>';
        $xml .= '</mergeCells>';

        $xml .= '</worksheet>';
        return $xml;
    }

    /**
     * Corporate Styles XML matching Dashboard_Monitoring_BBM MEI 2026.xlsx
     */
    protected function getCorporateStyleXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <numFmts count="4">
        <numFmt numFmtId="164" formatCode="#,##0.0"/>
        <numFmt numFmtId="165" formatCode="#,##0"/>
        <numFmt numFmtId="166" formatCode="&quot;Rp &quot;#,##0"/>
        <numFmt numFmtId="167" formatCode="yyyy\-mm\-dd"/>
    </numFmts>
    <fonts count="8">
        <font><sz val="10"/><color rgb="FF1E293B"/><name val="Calibri"/></font>
        <font><b/><sz val="14"/><color rgb="FFFFFFFF"/><name val="Segoe UI"/></font>
        <font><b/><sz val="9"/><color rgb="FF595959"/><name val="Segoe UI"/></font>
        <font><b/><sz val="16"/><color rgb="FF1F4E79"/><name val="Segoe UI"/></font>
        <font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Segoe UI"/></font>
        <font><b/><sz val="10"/><color rgb="FF006100"/><name val="Segoe UI"/></font>
        <font><b/><sz val="10"/><color rgb="FF9C0006"/><name val="Segoe UI"/></font>
        <font><b/><sz val="10"/><color rgb="FF1E293B"/><name val="Segoe UI"/></font>
    </fonts>
    <fills count="7">
        <fill><patternFill patternType="none"/></fill>
        <fill><patternFill patternType="gray125"/></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FF1F4E79"/><bgColor indexed="64"/></patternFill></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FFDDEBF7"/><bgColor indexed="64"/></patternFill></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FFC6EFCE"/><bgColor indexed="64"/></patternFill></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FFFFC7CE"/><bgColor indexed="64"/></patternFill></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FFF2F2F2"/><bgColor indexed="64"/></patternFill></fill>
    </fills>
    <borders count="3">
        <border><left/><right/><top/><bottom/><diagonal/></border>
        <border>
            <left style="thin"><color rgb="FFD9D9D9"/></left>
            <right style="thin"><color rgb="FFD9D9D9"/></right>
            <top style="thin"><color rgb="FFD9D9D9"/></top>
            <bottom style="thin"><color rgb="FFD9D9D9"/></bottom>
            <diagonal/>
        </border>
        <border>
            <left/><right/>
            <top style="thin"><color rgb="FFD9D9D9"/></top>
            <bottom style="double"><color rgb="FF1F4E79"/></bottom>
            <diagonal/>
        </border>
    </borders>
    <cellStyleXfs count="1">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
    </cellStyleXfs>
    <cellXfs count="20">
        <!-- 0: Default -->
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
        <!-- 1: Title Banner (Navy, White Bold, Centered) -->
        <xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
        <!-- 2: KPI Label (Light Blue, Muted 9pt Bold, Centered) -->
        <xf numFmtId="0" fontId="2" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
        <!-- 3: KPI Value (Light Blue, Navy 16pt Bold, Centered) -->
        <xf numFmtId="165" fontId="3" fillId="3" borderId="0" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
        <!-- 4: Table Header (Navy, White Bold, Centered, Bordered) -->
        <xf numFmtId="0" fontId="4" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
        <!-- 5: Table Data Center (Bordered) -->
        <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
        <!-- 6: Table Data Left (Bordered) -->
        <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>
        <!-- 7: Table Data Right (Bordered) -->
        <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>
        <!-- 8: Table Data Integer #,##0 (Bordered) -->
        <xf numFmtId="165" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>
        <!-- 9: Table Data Decimal #,##0.0 (Bordered) -->
        <xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>
        <!-- 10: Table Data Currency Rp #,##0 (Bordered) -->
        <xf numFmtId="166" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>
        <!-- 11: Table Data Date yyyy-mm-dd (Bordered) -->
        <xf numFmtId="167" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
        <!-- 12: Wajar Badge (Green Fill, Green Bold Text, Centered) -->
        <xf numFmtId="0" fontId="5" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
        <!-- 13: Tidak Wajar Badge (Red Fill, Red Bold Text, Centered) -->
        <xf numFmtId="0" fontId="6" fillId="5" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
        <!-- 14: Total Row Left/Text (Bold, Double Bottom Border) -->
        <xf numFmtId="0" fontId="7" fillId="0" borderId="2" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>
        <!-- 15: Total Row Integer #,##0 (Bold, Double Bottom Border) -->
        <xf numFmtId="165" fontId="7" fillId="0" borderId="2" xfId="0" applyNumberFormat="1" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>
        <!-- 16: Total Row Decimal #,##0.0 (Bold, Double Bottom Border) -->
        <xf numFmtId="164" fontId="7" fillId="0" borderId="2" xfId="0" applyNumberFormat="1" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>
        <!-- 17: Total Row Currency Rp #,##0 (Bold, Double Bottom Border) -->
        <xf numFmtId="166" fontId="7" fillId="0" borderId="2" xfId="0" applyNumberFormat="1" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>
        <!-- 18: Total Row Center (Bold, Double Bottom Border) -->
        <xf numFmtId="0" fontId="7" fillId="0" borderId="2" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
        <!-- 19: Section Title Bold -->
        <xf numFmtId="0" fontId="7" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>
    </cellXfs>
</styleSheet>';
    }

    /**
     * Sanitizes a sheet name to meet Excel specifications:
     * - Maximum 31 characters
     * - No special forbidden characters: [ ] * / \ ? :
     */
    protected function sanitizeSheetName(string $name): string
    {
        $cleaned = preg_replace('/[\[\]\*\/\\\?\:]/', ' ', $name);
        return trim(mb_substr($cleaned, 0, 31));
    }

    /**
     * Legacy Single-sheet fallback
     */
    protected function generateSingleSheetEvaluation(string $filename, array $headers, array $rows, string $period): BinaryFileResponse
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'digifuel_eval_single_') . '.xlsx';
        $zip = new ZipArchive();
        if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Tidak dapat membuat file Excel (.xlsx).');
        }

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
    <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
    <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>');

        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>');

        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>');

        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <sheets>
        <sheet name="Dashboard" sheetId="1" r:id="rId1"/>
    </sheets>
    <calcPr calcId="191029" fullCalcOnLoad="1"/>
</workbook>');

        $zip->addFromString('xl/styles.xml', $this->getCorporateStyleXml());

        $rowCount = count($rows);
        $firstDataRow = 9;
        $lastDataRow = max($firstDataRow, $firstDataRow + $rowCount - 1);
        $totalRow = $lastDataRow + 1;

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

        // Row 1 & 2
        $sheetXml .= '<row r="1" ht="26" customHeight="1">';
        $sheetXml .= '<c r="A1" s="1" t="inlineStr"><is><t>DASHBOARD MONITORING KONSUMSI BBM LIGHT VEHICLE (LV)</t></is></c>';
        for ($c = 1; $c < 10; $c++) {
            $colL = $this->columnLetter($c);
            $sheetXml .= "<c r=\"{$colL}1\" s=\"1\"/>";
        }
        $sheetXml .= "</row>\n";

        // Row 4
        $sheetXml .= '<row r="4" ht="18" customHeight="1">';
        $sheetXml .= '<c r="A4" s="2" t="inlineStr"><is><t>TOTAL JARAK (KM)</t></is></c>';
        $sheetXml .= '<c r="B4" s="2"/>';
        $sheetXml .= '<c r="C4" s="2" t="inlineStr"><is><t>TOTAL PENGISIAN FUEL (L)</t></is></c>';
        $sheetXml .= '<c r="D4" s="2" t="inlineStr"><is><t>RATA-RATA RASIO (KM/L)</t></is></c>';
        $sheetXml .= '<c r="E4" s="2"/>';
        $sheetXml .= '<c r="F4" s="2" t="inlineStr"><is><t>TOTAL BIAYA (IDR)</t></is></c>';
        $sheetXml .= '<c r="G4" s="2"/>';
        $sheetXml .= "</row>\n";

        // Row 5
        $sheetXml .= '<row r="5" ht="26" customHeight="1">';
        $sheetXml .= "<c r=\"A5\" s=\"3\"><f>C{$totalRow}</f><v>{$sumDist}</v></c>";
        $sheetXml .= '<c r="B5" s="3"/>';
        $sheetXml .= "<c r=\"C5\" s=\"3\"><f>D{$totalRow}</f><v>{$sumFuel}</v></c>";
        $sheetXml .= "<c r=\"D5\" s=\"3\"><f>E{$totalRow}</f><v>{$avgRatio}</v></c>";
        $sheetXml .= '<c r="E5" s="3"/>';
        $sheetXml .= "<c r=\"F5\" s=\"3\"><f>H{$totalRow}</f><v>{$sumCost}</v></c>";
        $sheetXml .= '<c r="G5" s="3"/>';
        $sheetXml .= "</row>\n";

        // Row 7
        $sheetXml .= '<row r="7"><c r="A7" s="19" t="inlineStr"><is><t>Rangkuman Performa per Unit LV</t></is></c></row>' . "\n";

        // Row 8
        $sheetXml .= '<row r="8" ht="22" customHeight="1">';
        foreach (array_values($headers) as $colIdx => $headerText) {
            $colLetter = $this->columnLetter($colIdx);
            $escaped = htmlspecialchars((string)$headerText, ENT_XML1);
            $sheetXml .= "<c r=\"{$colLetter}8\" s=\"4\" t=\"inlineStr\"><is><t>{$escaped}</t></is></c>";
        }
        $sheetXml .= "</row>\n";

        // Data Rows
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
                        $styleId = ($cachedVal === 'Tidak Wajar') ? 13 : 12;
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

        // Total Row
        $sheetXml .= "<row r=\"{$totalRow}\" ht=\"20\" customHeight=\"1\">";
        $sheetXml .= "<c r=\"A{$totalRow}\" s=\"14\"/>";
        $sheetXml .= "<c r=\"B{$totalRow}\" s=\"14\" t=\"inlineStr\"><is><t>Total / Rata-rata Nasional</t></is></c>";
        $sheetXml .= "<c r=\"C{$totalRow}\" s=\"15\"><f>SUM(C{$firstDataRow}:C{$lastDataRow})</f><v>{$sumDist}</v></c>";
        $sheetXml .= "<c r=\"D{$totalRow}\" s=\"15\"><f>SUM(D{$firstDataRow}:D{$lastDataRow})</f><v>{$sumFuel}</v></c>";
        $sheetXml .= "<c r=\"E{$totalRow}\" s=\"16\"><f>IF(D{$totalRow}&gt;0, C{$totalRow}/D{$totalRow}, 0)</f><v>{$avgRatio}</v></c>";
        $sheetXml .= "<c r=\"F{$totalRow}\" s=\"15\"><f>AVERAGE(F{$firstDataRow}:F{$lastDataRow})</f><v>{$avgStdRatio}</v></c>";
        $sheetXml .= "<c r=\"G{$totalRow}\" s=\"18\"/>";
        $sheetXml .= "<c r=\"H{$totalRow}\" s=\"17\"><f>SUM(H{$firstDataRow}:H{$lastDataRow})</f><v>{$sumCost}</v></c>";
        $sheetXml .= "<c r=\"I{$totalRow}\" s=\"16\"><f>SUM(I{$firstDataRow}:I{$lastDataRow})</f><v>{$sumWaste}</v></c>";
        $sheetXml .= "<c r=\"J{$totalRow}\" s=\"17\"><f>SUM(J{$firstDataRow}:J{$lastDataRow})</f><v>{$sumLoss}</v></c>";
        $sheetXml .= "</row>\n";

        $sheetXml .= '</sheetData>';
        $sheetXml .= '<mergeCells count="4"><mergeCell ref="A1:J2"/><mergeCell ref="A4:B4"/><mergeCell ref="A5:B5"/><mergeCell ref="D4:E4"/></mergeCells>';
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
