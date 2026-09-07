<?php

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$filePath = __DIR__ . '/../sample-data/original-excel/evoc_2026_tulokset_final.xlsx';

$spreadsheet = IOFactory::load($filePath);

$worksheet = $spreadsheet->getActiveSheet();

$data = $worksheet->toArray();

foreach ($data as $rowNumber => $row) {
    echo 'Row ' . ($rowNumber + 1) . ': ';
    print_r($row);
    echo PHP_EOL;
}

## can be used for debugging