<?php

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/validator.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$filePath = __DIR__ . '/../sample-data/original-excel/TSkortteli26.xlsx';

function looksLikeTime(string $value): bool
{
    return preg_match(
        '/^\d{1,2}:\d{2}(?::\d{2})?(?:[.,]\d+)?$/',
        $value
    ) === 1;
}

$spreadsheet = IOFactory::load($filePath);
$worksheet = $spreadsheet->getActiveSheet();

$data = $worksheet->toArray(
    null,
    true,
    true,
    false
);

$currentCategory = null;
$parsedResults = [];
$foundCategories = [];

foreach ($data as $rowNumber => $row) {
    // Convert NULL values to empty strings and trim text.
    $row = array_map(function ($value) {
        return trim((string) ($value ?? ''));
    }, $row);

    /*
     * Remove empty cells ONLY from the beginning.
     */
    while (count($row) > 0 && $row[0] === '') {
        array_shift($row);
    }

    /*
     * Remove empty cells ONLY from the end.
     */
    while (count($row) > 0 && end($row) === '') {
        array_pop($row);
    }

    // Skip completely empty rows.
    if (count($row) === 0) {
        continue;
    }

    /*
     * CATEGORY ROW
     *
     * Handles examples such as:
     *
     * Naiset Yleinen | '' | 25 km
     * MU9 | 1 x 2,2 km
     *
     * The distance does not always appear in the same column,
     * so search the whole row for a value containing "km".
     */
    $isCategoryRow = false;

    if (
        isset($row[0]) &&
        $row[0] !== '' &&
        !is_numeric($row[0])
    ) {
        foreach ($row as $value) {
            if (
                is_string($value) &&
                preg_match('/\bkm\b/i', $value)
            ) {
                $isCategoryRow = true;
                break;
            }
        }
    }

    if ($isCategoryRow) {
        $currentCategory = $row[0];

        if (!in_array($currentCategory, $foundCategories, true)) {
            $foundCategories[] = $currentCategory;
        }

        echo PHP_EOL;
        echo 'CATEGORY: ' . $currentCategory . PHP_EOL;
        continue;
    }

    /*
     * FINISHED RESULT
     *
     * Examples:
     *
     * 1 | Savaste | Joni | Team EVOC | 2:20:43
     *
     * 1 | Teronen | Anni | HDT | '' | 46:24 | 0:46:24
     *
     * 15 | Karisaari | Mikko | '' | 2:34:30
     */
    if (isset($row[0]) && is_numeric($row[0])) {
        $placement = (int) $row[0];

        $lastName = $row[1] ?? '';
        $firstName = $row[2] ?? '';
        $club = $row[3] ?? '';

        $finishTime = null;

        /*
         * Search cells after the club for the first value
         * that actually looks like a race time.
         *
         * This prevents values such as:
         *
         * Kategoria 2
         * OVL
         *
         * from being mistaken for finish times.
         */
        for ($i = 4; $i < count($row); $i++) {
            if (looksLikeTime($row[$i])) {
                $finishTime = $row[$i];
                break;
            }
        }

        $parsedResults[] = [
            'category' => $currentCategory,
            'placement' => $placement,
            'last_name' => $lastName,
            'first_name' => $firstName,
            'club' => $club,
            'finish_time' => $finishTime,
            'status' => 'Finished'
        ];

        continue;
    }

    /*
     * DNS / DNF / DSQ
     *
     * Some files use "-" in the placement column.
     *
     * Example:
     *
     * - | Ihalainen | Reko | Porvoo | DNS | -
     */
    if (isset($row[0]) && $row[0] === '-') {
        array_shift($row);
    }

    $status = null;

    foreach ($row as $value) {
        if (in_array($value, ['DNS', 'DNF', 'DSQ'], true)) {
            $status = $value;
            break;
        }
    }

    if ($status !== null) {
        // Some result files put "-" in the placement column.
        if (isset($row[0]) && $row[0] === '-') {
            array_shift($row);
        }

        $lastName = $row[0] ?? '';
        $firstName = $row[1] ?? '';
        $club = '';

        if (
            isset($row[2]) &&
            $row[2] !== '' &&
            !in_array($row[2], ['DNS', 'DNF', 'DSQ', '-'], true)
        ) {
            $club = $row[2];
        }

        $parsedResults[] = [
            'category' => $currentCategory,
            'placement' => null,
            'last_name' => $lastName,
            'first_name' => $firstName,
            'club' => $club,
            'finish_time' => null,
            'status' => $status
        ];
    }
}

$validResults = [];
$invalidResults = [];
$warningResults = [];

foreach ($parsedResults as $result) {
    $validation = validateResult($result);

    $errors = $validation['errors'];
    $warnings = $validation['warnings'];

    if (empty($errors)) {
        $validResults[] = $result;

        if (!empty($warnings)) {
            $warningResults[] = [
                'result' => $result,
                'warnings' => $warnings
            ];
        }
    } else {
        $invalidResults[] = [
            'result' => $result,
            'errors' => $errors
        ];
    }
}

echo PHP_EOL;
echo '==============================' . PHP_EOL;
echo 'VALIDATION SUMMARY' . PHP_EOL;
echo '==============================' . PHP_EOL;
echo 'Parsed results: ' . count($parsedResults) . PHP_EOL;
echo 'Valid results: ' . count($validResults) . PHP_EOL;
echo 'Invalid results: ' . count($invalidResults) . PHP_EOL;
echo 'Warnings: ' . count($warningResults) . PHP_EOL;
echo 'Categories found: ' . count($foundCategories) . PHP_EOL;

foreach ($foundCategories as $category) {
    echo '- ' . $category . PHP_EOL;
}

echo PHP_EOL;
echo '==============================' . PHP_EOL;
echo 'SAMPLE PARSED RESULTS' . PHP_EOL;
echo '==============================' . PHP_EOL;

$shownPerCategory = [];

foreach ($parsedResults as $result) {
    $category = $result['category'];

    if (!isset($shownPerCategory[$category])) {
        $shownPerCategory[$category] = 0;
    }

    // Show parsed riders for checking
    if ($shownPerCategory[$category] < 2) {
        print_r($result);
        $shownPerCategory[$category]++;
    }
}

if (!empty($invalidResults)) {
    echo PHP_EOL;
    echo 'INVALID RESULTS:' . PHP_EOL;

    foreach ($invalidResults as $invalid) {
        print_r($invalid['result']);

        echo 'Errors:' . PHP_EOL;

        foreach ($invalid['errors'] as $error) {
            echo '- ' . $error . PHP_EOL;
        }

        echo PHP_EOL;
    }
}