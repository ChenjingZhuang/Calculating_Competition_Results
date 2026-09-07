<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/club_repository.php';
require_once __DIR__ . '/rider_repository.php';
require_once __DIR__ . '/category_repository.php';
require_once __DIR__ . '/result_import_repository.php';
require_once __DIR__ . '/result_repository.php';
require_once __DIR__ . '/validator.php';

/*
 * The Excel file being imported.
 */
$filePath = __DIR__ . '/../sample-data/original-excel/TSkortteli26.xlsx';

/*
 * These must already exist in the database.
 */
$competitionId = 2;
$disciplineId = 1;
$uploadedBy = 1;

/*
 * Run the parser.
 *
 * parse_results.php should create $parsedResults.
 */
require __DIR__ . '/parse_results.php';

/*
 * Validate parsed results.
 */
$validResults = [];
$invalidResults = [];

foreach ($parsedResults as $result) {

    $validation = validateResult($result);

    if (empty($validation['errors'])) {
        $validResults[] = $result;
    } else {
        $invalidResults[] = [
            'result' => $result,
            'errors' => $validation['errors']
        ];
    }
}

/*
 * Prepare import information.
 */
$fileName = basename($filePath);

$importedRecords = 0;
$failedRecords = count($invalidResults);
$totalRecords = count($parsedResults);

/*
 * Stop duplicate imports for the same competition.
 */
if (competitionHasResults($pdo, $competitionId)) {
    die(
        'Import stopped: results already exist for this competition.'
        . PHP_EOL
    );
}

try {

    /*
     * Start database transaction.
     */
    $pdo->beginTransaction();

    /*
     * Create result import record.
     */
    $resultImportId = createResultImport(
        $pdo,
        $competitionId,
        $fileName,
        $uploadedBy
    );

    echo 'Result Import ID: ' . $resultImportId . PHP_EOL;

    /*
     * Import valid results.
     */
    foreach ($validResults as $result) {

        /*
         * 1. Club
         */
        $clubId = findOrCreateClub(
            $pdo,
            $result['club']
        );

        /*
         * 2. Rider
         */
        $riderId = findOrCreateRider(
            $pdo,
            $result['first_name'],
            $result['last_name'],
            $clubId
        );

        /*
         * 3. Category
         */
        $categoryId = findCategory(
            $pdo,
            $result['category'],
            $disciplineId
        );

        if ($categoryId === null) {
            throw new RuntimeException(
                'Category not found: ' . $result['category']
            );
        }

        /*
         * 4. Insert result
         */
        createResult(
            $pdo,
            $competitionId,
            $riderId,
            $categoryId,
            $result['placement'],
            $result['finish_time'],
            $result['status'],
            $resultImportId
        );

        $importedRecords++;
    }

    /*
     * Mark import as completed.
     */
    completeResultImport(
        $pdo,
        $resultImportId,
        $totalRecords,
        $importedRecords,
        $failedRecords
    );

    /*
     * Save all database changes.
     */
    $pdo->commit();

    echo PHP_EOL;
    echo 'Import completed successfully.' . PHP_EOL;
    echo 'Total records: ' . $totalRecords . PHP_EOL;
    echo 'Imported records: ' . $importedRecords . PHP_EOL;
    echo 'Failed records: ' . $failedRecords . PHP_EOL;

} catch (Throwable $e) {

    /*
     * Undo all database changes if something fails.
     */
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo PHP_EOL;
    echo 'Import failed.' . PHP_EOL;
    echo 'Reason: ' . $e->getMessage() . PHP_EOL;
}