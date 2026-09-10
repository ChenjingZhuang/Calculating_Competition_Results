<?php

require __DIR__ . '/db.php';
require __DIR__ . '/result_import_repository.php';

$competitionId = 1;
$uploadedBy = 1;

$resultImportId = createResultImport(
    $pdo,
    $competitionId,
    'test_import.xlsx',
    $uploadedBy
);

echo 'Result Import ID: ' . $resultImportId . PHP_EOL;

completeResultImport(
    $pdo,
    $resultImportId,
    10,
    10,
    0
);

echo 'Import completed.' . PHP_EOL;