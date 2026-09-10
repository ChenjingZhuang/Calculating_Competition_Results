<?php

function findCategory(
    PDO $pdo,
    string $categoryName,
    int $disciplineId
): ?int {
    $categoryName = trim($categoryName);

    $stmt = $pdo->prepare(
        'SELECT category_id
         FROM category
         WHERE name = ?
           AND discipline_id = ?
         LIMIT 1'
    );

    $stmt->execute([
        $categoryName,
        $disciplineId
    ]);

    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$category) {
        return null;
    }

    return (int) $category['category_id'];
}