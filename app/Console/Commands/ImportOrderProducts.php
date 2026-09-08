<?php

namespace App\Console\Commands;

use App\Models\OrderProduct;
use Illuminate\Console\Command;
use RuntimeException;
use ZipArchive;

class ImportOrderProducts extends Command
{
    protected $signature = 'order-product:import {file : Path to CSV or XLSX file}';
    protected $description = 'Import the external order product catalog into order_product';

    public function handle(): int
    {
        $path = (string) $this->argument('file');
        if (! is_file($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        try {
            $rows = $this->readRows($path);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        if (count($rows) < 2) {
            $this->error('The file must contain a header and at least one data row.');
            return self::FAILURE;
        }

        $headers = array_map(fn ($value) => strtolower(trim((string) $value)), array_shift($rows));
        $required = ['id', 'fbm/fba', 'order_product_id', 'product_name'];
        foreach ($required as $header) {
            if (! in_array($header, $headers, true)) {
                $this->error("Missing required column: {$header}");
                return self::FAILURE;
            }
        }

        $positions = array_flip($headers);
        $imported = 0;
        foreach ($rows as $line => $row) {
            $sourceId = trim((string) ($row[$positions['id']] ?? ''));
            $type = strtoupper(trim((string) ($row[$positions['fbm/fba']] ?? '')));
            $orderProductId = trim((string) ($row[$positions['order_product_id']] ?? ''));
            $name = trim((string) ($row[$positions['product_name']] ?? ''));
            if ($sourceId === '' && $orderProductId === '' && $name === '') continue;
            if (! ctype_digit($sourceId) || ! in_array($type, ['FBM', 'FBA'], true) || ! ctype_digit($orderProductId) || $name === '') {
                $this->warn('Skipped invalid row ' . ($line + 2));
                continue;
            }
            OrderProduct::updateOrCreate(
                ['source_id' => (int) $sourceId],
                ['fulfillment_type' => $type, 'order_product_id' => (int) $orderProductId, 'product_name' => $name]
            );
            $imported++;
        }

        $this->info("Imported {$imported} order product(s).");
        return self::SUCCESS;
    }

    private function readRows(string $path): array
    {
        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'csv') {
            $handle = fopen($path, 'rb');
            if ($handle === false) throw new RuntimeException('Unable to open CSV file.');
            $rows = [];
            while (($row = fgetcsv($handle)) !== false) $rows[] = array_map(fn ($v) => trim((string) $v), $row);
            fclose($handle);
            return $rows;
        }
        if (! class_exists(ZipArchive::class)) throw new RuntimeException('PHP ZipArchive is required to read XLSX files.');
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) throw new RuntimeException('Unable to open XLSX file.');
        $shared = [];
        if (($xml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            $doc = simplexml_load_string($xml);
            if ($doc !== false) foreach ($doc->si as $item) $shared[] = trim(implode('', (array) $item->t));
        }
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        if (! is_string($sheet)) throw new RuntimeException('Worksheet not found in XLSX file.');
        $doc = simplexml_load_string($sheet);
        if ($doc === false) throw new RuntimeException('Invalid worksheet XML.');
        $rows = [];
        foreach ($doc->sheetData->row as $xmlRow) {
            $values = [];
            foreach ($xmlRow->c as $cell) {
                $ref = (string) $cell['r']; preg_match('/([A-Z]+)/', $ref, $match);
                $index = 0; foreach (str_split($match[1] ?? '') as $char) $index = $index * 26 + ord($char) - 64; $index--;
                $value = (string) ($cell->v ?? '');
                if ((string) $cell['t'] === 's') $value = $shared[(int) $value] ?? '';
                if ((string) $cell['t'] === 'inlineStr') $value = (string) ($cell->is->t ?? '');
                $values[$index] = trim($value);
            }
            $rows[] = array_values(array_replace(array_fill(0, max(4, count($values)), ''), $values));
        }
        return $rows;
    }
}
