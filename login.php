<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/session_bootstrap.php';
require_once __DIR__ . '/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$email = strtolower(trim($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Enter a valid email and password.']);
    exit;
}

$stmt = $conn->prepare('SELECT id, name, email, password, strand FROM students WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student || !password_verify($password, $student['password'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
    exit;
}

session_regenerate_id(true);

$_SESSION['student_id'] = (int) $student['id'];
$_SESSION['student_name'] = $student['name'];
$_SESSION['strand'] = $student['strand'];
$_SESSION['logged_in'] = true;

echo json_encode([
    'success' => true,
    'id' => (int) $student['id'],
    'name' => $student['name'],
    'email' => $student['email'],
    'strand' => $student['strand']
]);
