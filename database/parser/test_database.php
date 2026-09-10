<?php

require __DIR__ . '/db.php';

$sql = "SELECT * FROM season";

$stmt = $pdo->query($sql);

$seasons = $stmt->fetchAll(PDO::FETCH_ASSOC);

print_r($seasons);