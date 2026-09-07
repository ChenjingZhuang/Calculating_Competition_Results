<?php

require __DIR__ . '/db.php';
require __DIR__ . '/club_repository.php';

$clubId = findOrCreateClub(
    $pdo,
    'Test Cycling Club'
);

echo 'Club ID: ' . $clubId . PHP_EOL;