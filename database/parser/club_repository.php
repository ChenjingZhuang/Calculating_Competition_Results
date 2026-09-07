<?php

function findOrCreateClub(PDO $pdo, ?string $clubName): ?int
{
    $clubName = trim((string) $clubName);

    // Club is optional.
    if ($clubName === '') {
        return null;
    }

    /*
     * First check whether the club already exists.
     */
    $stmt = $pdo->prepare(
        'SELECT club_id
         FROM club
         WHERE name = ?
         LIMIT 1'
    );

    $stmt->execute([$clubName]);

    $club = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($club) {
        return (int) $club['club_id'];
    }

    /*
     * Club does not exist, so create it.
     */
    $stmt = $pdo->prepare(
        'INSERT INTO club (name)
         VALUES (?)'
    );

    $stmt->execute([$clubName]);

    return (int) $pdo->lastInsertId();
}