<?php

function createResultImport(
    PDO $pdo,
    int $competitionId,
    string $fileName,
    int $uploadedBy
): int {
    $stmt = $pdo->prepare(
        'INSERT INTO result_import (
            competition_id,
            file_name,
            import_status,
            total_records,
            imported_records,
            failed_records,
            uploaded_by
        )
        VALUES (?, ?, ?, 0, 0, 0, ?)'
    );

    $stmt->execute([
        $competitionId,
        $fileName,
        'Pending',
        $uploadedBy
    ]);

    return (int) $pdo->lastInsertId();
}
function completeResultImport(
    PDO $pdo,
    int $resultImportId,
    int $totalRecords,
    int $importedRecords,
    int $failedRecords
): void {
    $stmt = $pdo->prepare(
        'UPDATE result_import
         SET import_status = ?,
             total_records = ?,
             imported_records = ?,
             failed_records = ?
         WHERE result_import_id = ?'
    );

    $stmt->execute([
        'Completed',
        $totalRecords,
        $importedRecords,
        $failedRecords,
        $resultImportId
    ]);
}
function competitionHasResults(
    PDO $pdo,
    int $competitionId
): bool {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM result
         WHERE competition_id = ?'
    );

    $stmt->execute([
        $competitionId
    ]);

    return (int) $stmt->fetchColumn() > 0;
}