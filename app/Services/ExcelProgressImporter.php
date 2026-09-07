<?php

namespace App\Services;

use RuntimeException;
use SimpleXMLElement;

class ExcelProgressImporter
{
    public function read(string $path): array
    {
        $archive = new \PharData($path);
        $sharedStrings = $this->sharedStrings($archive);
        $workbook = simplexml_load_string($archive['xl/workbook.xml']->getContent());
        $workbook->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $workbook->registerXPathNamespace('rel', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $relationships = simplexml_load_string($archive['xl/_rels/workbook.xml.rels']->getContent());
        $relationships->registerXPathNamespace('rel', 'http://schemas.openxmlformats.org/package/2006/relationships');
        $sheet = collect($workbook->xpath('//main:sheets/main:sheet'))->first(fn (SimpleXMLElement $item) => (string) $item['name'] === 'TS (10)')
            ?? $workbook->xpath('//main:sheets/main:sheet')[0];
        $relationshipId = (string) $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
        $relationship = collect($relationships->xpath('//rel:Relationship'))->first(fn (SimpleXMLElement $item) => (string) $item['Id'] === $relationshipId);
        $sheetPath = 'xl/'.ltrim(str_replace('worksheets/', 'worksheets/', (string) $relationship['Target']), '/');
        $xml = simplexml_load_string($archive[$sheetPath]->getContent());
        $xml->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $rows = [];
        foreach ($xml->xpath('//main:sheetData/main:row') as $row) {
            $rowNumber = (int) $row['r'];
            if ($rowNumber < 9 || $rowNumber > 39) continue;
            $cells = array_fill(1, 27, null);
            $row->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            foreach ($row->xpath('./main:c') as $cell) {
                $column = $this->columnNumber((string) $cell['r']);
                if ($column > 27) continue;
                $type = (string) $cell['t'];
                $value = (string) ($cell->v ?? '');
                if ($type === 's') $value = $sharedStrings[(int) $value] ?? '';
                if ($type === 'inlineStr') $value = (string) ($cell->is->t ?? '');
                $cells[$column] = $value;
            }
            $rows[$rowNumber] = $cells;
        }

        $weekLabels = [];
        for ($column = 6; $column <= 27; $column++) {
            $weekLabels[] = trim((string) ($rows[11][$column] ?? '')) ?: 'MINGGU '.($column - 5);
        }
        $targetValues = $this->numericRow($rows[36] ?? []);
        $actualValues = $this->numericRow($rows[38] ?? []);
        if (count($targetValues) !== count($weekLabels) || count($actualValues) !== count($weekLabels)) {
            throw new RuntimeException('Baris target/realisasi atau header minggu tidak lengkap.');
        }

        return [
            'sheet_name' => (string) $sheet['name'],
            'week_labels' => $weekLabels,
            'target_values' => $targetValues,
            'actual_values' => $actualValues,
            'table_rows' => collect($rows)->map(fn (array $cells, int $rowNumber) => ['row' => $rowNumber, 'cells' => array_values($cells)])->values()->all(),
        ];
    }

    private function sharedStrings(\PharData $archive): array
    {
        if (! isset($archive['xl/sharedStrings.xml'])) return [];
        $xml = simplexml_load_string($archive['xl/sharedStrings.xml']->getContent());
        $xml->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        return collect($xml->xpath('//main:si'))->map(function (SimpleXMLElement $item) {
            $item->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            return implode('', array_map('strval', $item->xpath('.//main:t')));
        })->all();
    }

    private function columnNumber(string $reference): int
    {
        preg_match('/^[A-Z]+/', $reference, $matches);
        $number = 0;
        foreach (str_split($matches[0] ?? '') as $letter) $number = $number * 26 + ord($letter) - 64;
        return $number;
    }

    private function numericRow(array $cells): array
    {
        return array_map(fn ($value) => is_numeric(str_replace(',', '.', (string) $value)) ? (float) str_replace(',', '.', (string) $value) : null, array_slice($cells, 5, 22));
    }
}
