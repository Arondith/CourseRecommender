<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

mysqli_report(MYSQLI_REPORT_OFF);

$conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if ($conn->connect_errno) {
    http_response_code(500);
    error_log('CourseMatch database connection failed: ' . $conn->connect_error);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection unavailable. Please try again later.'
    ]);
    exit;
}

$conn->set_charset('utf8mb4');
