<?php

require __DIR__ . '/db.php';
require __DIR__ . '/club_repository.php';
require __DIR__ . '/rider_repository.php';
require __DIR__ . '/category_repository.php';
require __DIR__ . '/result_import_repository.php';
require __DIR__ . '/result_repository.php';

$competitionId = 1;
$disciplineId = 1;
$uploadedBy = 1;

/*
 * 1. Find or create club.
 */
$clubId = findOrCreateClub(
    $pdo,
    'Test Cycling Club'
);

/*
 * 2. Find or create rider.
 */
$riderId = findOrCreateRider(
    $pdo,
    'Test',
    'Result Rider',
    $clubId
);

/*
 * 3. Find category.
 */
$categoryId = findCategory(
    $pdo,
    'N Elite',
    $disciplineId
);

if ($categoryId === null) {
    die('Category not found.' . PHP_EOL);
}

/*
 * 4. Create result import record.
 */
$resultImportId = createResultImport(
    $pdo,
    $competitionId,
    'test_result.xlsx',
    $uploadedBy
);

/*
 * 5. Insert result.
 */
$resultId = createResult(
    $pdo,
    $competitionId,
    $riderId,
    $categoryId,
    1,
    '46:24',
    'Finished',
    $resultImportId
);

/*
 * Create a separate rider for the DNS test.
 */
$dnsRiderId = findOrCreateRider(
    $pdo,
    'Test',
    'DNS Rider',
    $clubId
);

/*
 * Insert DNS result.
 */
$dnsResultId = createResult(
    $pdo,
    $competitionId,
    $dnsRiderId,
    $categoryId,
    null,
    null,
    'DNS',
    $resultImportId
);

echo 'DNS Result ID: ' . $dnsResultId . PHP_EOL;

/*
 * 6. Complete import record.
 */
completeResultImport(
    $pdo,
    $resultImportId,
    1,
    1,
    0
);

echo 'Result Import ID: ' . $resultImportId . PHP_EOL;
echo 'Result ID: ' . $resultId . PHP_EOL;
echo 'Result inserted successfully.' . PHP_EOL;