<?php

declare(strict_types=1);

require __DIR__ . '/db.php';

header('Content-Type: application/json');

try {
    conexion()->query('SELECT 1');
    echo json_encode(['estado' => 'ok', 'base_de_datos' => 'conectada']);
} catch (PDOException $e) {
    http_response_code(503);
    echo json_encode(['estado' => 'error', 'base_de_datos' => 'sin conexion']);
}
