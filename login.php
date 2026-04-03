<?php
// login.php
header('Content-Type: application/json');
session_start();
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$email    = trim($_POST['email']    ?? '');
$password =      $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Please fill all fields.']);
    exit;
}

$stmt = $conn->prepare("SELECT id, name, email, password, strand FROM students WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid login']);
    $stmt->close(); exit;
}

$student = $result->fetch_assoc();
$stmt->close();

if (!password_verify($password, $student['password'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid login']);
    exit;
}

// Set server session
$_SESSION['student_id']   = $student['id'];
$_SESSION['student_name'] = $student['name'];
$_SESSION['strand']       = $student['strand'];
$_SESSION['logged_in']    = true;

echo json_encode([
    'success' => true,
    'id'      => $student['id'],
    'name'    => $student['name'],
    'email'   => $student['email'],
    'strand'  => $student['strand']
]);

$conn->close();
?>