<?php

require __DIR__ . '/db.php';
require __DIR__ . '/category_repository.php';

$disciplineId = 1;

$categoryId = findCategory(
    $pdo,
    'N Elite',
    $disciplineId
);

if ($categoryId === null) {
    echo 'Category not found.' . PHP_EOL;
} else {
    echo 'Category ID: ' . $categoryId . PHP_EOL;
}