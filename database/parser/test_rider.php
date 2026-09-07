<?php

require __DIR__ . '/db.php';
require __DIR__ . '/club_repository.php';
require __DIR__ . '/rider_repository.php';

$clubId = findOrCreateClub(
    $pdo,
    'Test Cycling Club'
);

$riderId = findOrCreateRider(
    $pdo,
    'Test',
    'Rider',
    $clubId
);

echo 'Club ID: ' . $clubId . PHP_EOL;
echo 'Rider ID: ' . $riderId . PHP_EOL;