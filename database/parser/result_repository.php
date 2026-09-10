<?php

function createResult(
    PDO $pdo,
    int $competitionId,
    int $riderId,
    int $categoryId,
    ?int $placement,
    ?string $finishTime,
    string $status,
    int $resultImportId
): int {
    $stmt = $pdo->prepare(
        'INSERT INTO result (
            competition_id,
            rider_id,
            category_id,
            placement,
            finish_time,
            status,
            points,
            result_import_id
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );

    $points = 0;

    $stmt->execute([
        $competitionId,
        $riderId,
        $categoryId,
        $placement,
        $finishTime,
        $status,
        $points,
        $resultImportId
    ]);

    return (int) $pdo->lastInsertId();
}