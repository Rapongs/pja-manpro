<?php

namespace App\Services;

use RuntimeException;
use SimpleXMLElement;

class ExcelProgressImporter
{
    private const DEFAULT_CONFIG = [
        'sheet_name' => null,
        'start_row' => 9,
        'end_row' => 39,
        'week_row' => 11,
        'week_start_col' => 6,
        'week_end_col' => 27,
        'target_row' => 36,
        'actual_row' => 38,
    ];

    public function read(string $path, array $config = []): array
    {
        $config = array_merge(self::DEFAULT_CONFIG, $config);
        $sheet = $this->resolveSheet($path, $config['sheet_name']);
        $rows = $this->readRows($path, $sheet['path'], $config['start_row'], $config['end_row']);

        $weekLabels = [];
        for ($column = $config['week_start_col']; $column <= $config['week_end_col']; $column++) {
            $weekLabels[] = trim((string) ($rows[$config['week_row']][$column] ?? '')) ?: 'MINGGU '.($column - $config['week_start_col'] + 1);
        }
        $targetValues = $this->numericRow($rows[$config['target_row']] ?? [], $config['week_start_col'], $config['week_end_col']);
        $actualValues = $this->numericRow($rows[$config['actual_row']] ?? [], $config['week_start_col'], $config['week_end_col']);
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

    public function preview(string $path): array
    {
        $archive = new \PharData($path);
        $workbook = simplexml_load_string($archive['xl/workbook.xml']->getContent());
        $workbook->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $workbook->registerXPathNamespace('rel', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $relationships = simplexml_load_string($archive['xl/_rels/workbook.xml.rels']->getContent());
        $relationships->registerXPathNamespace('rel', 'http://schemas.openxmlformats.org/package/2006/relationships');

        $sheets = [];
        foreach ($workbook->xpath('//main:sheets/main:sheet') as $sheet) {
            $relationshipId = (string) $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
            $relationship = collect($relationships->xpath('//rel:Relationship'))->first(fn (SimpleXMLElement $item) => (string) $item['Id'] === $relationshipId);
            $sheetPath = 'xl/'.ltrim(str_replace('worksheets/', 'worksheets/', (string) $relationship['Target']), '/');
            $sheets[] = [
                'name' => (string) $sheet['name'],
                'rows' => $this->readRows($path, $sheetPath, 1, 60),
            ];
        }

        return $sheets;
    }

    private function resolveSheet(string $path, ?string $sheetName): array
    {
        $archive = new \PharData($path);
        $workbook = simplexml_load_string($archive['xl/workbook.xml']->getContent());
        $workbook->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $workbook->registerXPathNamespace('rel', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $relationships = simplexml_load_string($archive['xl/_rels/workbook.xml.rels']->getContent());
        $relationships->registerXPathNamespace('rel', 'http://schemas.openxmlformats.org/package/2006/relationships');
        $sheet = collect($workbook->xpath('//main:sheets/main:sheet'))->first(fn (SimpleXMLElement $item) => (string) $item['name'] === $sheetName)
            ?? $workbook->xpath('//main:sheets/main:sheet')[0];
        $relationshipId = (string) $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
        $relationship = collect($relationships->xpath('//rel:Relationship'))->first(fn (SimpleXMLElement $item) => (string) $item['Id'] === $relationshipId);
        $sheetPath = 'xl/'.ltrim(str_replace('worksheets/', 'worksheets/', (string) $relationship['Target']), '/');

        return ['name' => (string) $sheet['name'], 'path' => $sheetPath];
    }

    private function readRows(string $path, string $sheetPath, int $startRow, int $endRow): array
    {
        $archive = new \PharData($path);
        $sharedStrings = $this->sharedStrings($archive);
        $xml = simplexml_load_string($archive[$sheetPath]->getContent());
        $xml->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $rows = [];
        foreach ($xml->xpath('//main:sheetData/main:row') as $row) {
            $rowNumber = (int) $row['r'];
            if ($rowNumber < $startRow || $rowNumber > $endRow) continue;
            $cells = [];
            $row->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            foreach ($row->xpath('./main:c') as $cell) {
                $column = $this->columnNumber((string) $cell['r']);
                $type = (string) $cell['t'];
                $value = (string) ($cell->v ?? '');
                if ($type === 's') $value = $sharedStrings[(int) $value] ?? '';
                if ($type === 'inlineStr') $value = (string) ($cell->is->t ?? '');
                $cells[$column] = $value;
            }
            $rows[$rowNumber] = $cells;
        }

        return $rows;
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

    private function columnLetter(int $number): string
    {
        $letter = '';
        while ($number > 0) {
            $letter = chr(65 + (($number - 1) % 26)).$letter;
            $number = intdiv($number - 1, 26);
        }
        return $letter;
    }

    private function numericRow(array $cells, int $startCol, int $endCol): array
    {
        $values = [];
        for ($column = $startCol; $column <= $endCol; $column++) {
            $value = $cells[$column] ?? null;
            $values[] = is_numeric(str_replace(',', '.', (string) $value)) ? (float) str_replace(',', '.', (string) $value) : null;
        }
        return $values;
    }

    public function configForForm(array $config): array
    {
        $config = array_merge(self::DEFAULT_CONFIG, $config);
        return [
            'start_row' => (int) $config['start_row'],
            'end_row' => (int) $config['end_row'],
            'week_row' => (int) $config['week_row'],
            'week_start_col' => $this->columnLetter((int) $config['week_start_col']),
            'week_end_col' => $this->columnLetter((int) $config['week_end_col']),
            'target_row' => (int) $config['target_row'],
            'actual_row' => (int) $config['actual_row'],
        ];
    }

    public function configFromForm(array $input): array
    {
        return [
            'sheet_name' => $input['sheet_name'] ?? null,
            'start_row' => (int) ($input['start_row'] ?? self::DEFAULT_CONFIG['start_row']),
            'end_row' => (int) ($input['end_row'] ?? self::DEFAULT_CONFIG['end_row']),
            'week_row' => (int) ($input['week_row'] ?? self::DEFAULT_CONFIG['week_row']),
            'week_start_col' => $this->columnNumber(strtoupper((string) ($input['week_start_col'] ?? 'F'))),
            'week_end_col' => $this->columnNumber(strtoupper((string) ($input['week_end_col'] ?? 'AA'))),
            'target_row' => (int) ($input['target_row'] ?? self::DEFAULT_CONFIG['target_row']),
            'actual_row' => (int) ($input['actual_row'] ?? self::DEFAULT_CONFIG['actual_row']),
        ];
    }
}