<?php

function findOrCreateRider(
    PDO $pdo,
    string $firstName,
    string $lastName,
    ?int $clubId
): int {
    $firstName = trim($firstName);
    $lastName = trim($lastName);

    /*
     * Try to find an existing rider.
     *
     * Because club_id can be NULL, we handle
     * riders with and without clubs separately.
     */
    if ($clubId === null) {

        $stmt = $pdo->prepare(
            'SELECT rider_id
             FROM rider
             WHERE first_name = ?
               AND last_name = ?
               AND club_id IS NULL
             LIMIT 1'
        );

        $stmt->execute([
            $firstName,
            $lastName
        ]);

    } else {

        $stmt = $pdo->prepare(
            'SELECT rider_id
             FROM rider
             WHERE first_name = ?
               AND last_name = ?
               AND club_id = ?
             LIMIT 1'
        );

        $stmt->execute([
            $firstName,
            $lastName,
            $clubId
        ]);
    }

    $rider = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($rider) {
        return (int) $rider['rider_id'];
    }

    /*
     * Rider does not exist, so create one.
     */
    $stmt = $pdo->prepare(
        'INSERT INTO rider (
            club_id,
            first_name,
            last_name
        )
        VALUES (?, ?, ?)'
    );

    $stmt->execute([
        $clubId,
        $firstName,
        $lastName
    ]);

    return (int) $pdo->lastInsertId();
}