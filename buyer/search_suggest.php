<?php

require_once '../config/database.php';
require_once '../includes/auth.php';

$q = trim($_GET['q'] ?? '');
$fields = $_GET['search_fields'] ?? ['title', 'author', 'isbn', 'description']; // default if none

if ($q === '') {
    echo json_encode([]);
    exit;
}

// Map allowed fields to DB columns
$fieldMap = [
    'title' => 'books.title',
    'author' => 'books.author',
    'isbn' => 'books.isbn',
    'description' => 'books.description'
];

// Build CASE WHEN SELECT clause and WHERE clause
$selectClauses = [];
$whereClauses = [];
$params = [];

foreach ($fields as $f) {
    if (isset($fieldMap[$f])) {
        $selectClauses[] = "CASE WHEN {$fieldMap[$f]} LIKE ? THEN {$fieldMap[$f]} END AS $f";
        $whereClauses[] = "{$fieldMap[$f]} LIKE ?";
        $params[] = "%$q%"; // for SELECT CASE
        $params[] = "%$q%"; // for WHERE
    }
}

if (!$selectClauses) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT DISTINCT " . implode(", ", $selectClauses) . " 
        FROM books 
        WHERE " . implode(" OR ", $whereClauses) . " 
        LIMIT 8";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$results = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    foreach ($fields as $f) {
        if (!empty($row[$f])) {
            $results[] = $row[$f];
        }
    }
}

echo json_encode(array_values(array_unique($results)));
