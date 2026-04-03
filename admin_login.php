<?php
// admin_login.php
header('Content-Type: application/json');
session_start();
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$username = trim($_POST['username'] ?? '');
$password =      $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Please fill all fields.']);
    exit;
}

$stmt = $conn->prepare("SELECT id, username, password, role FROM admins WHERE username = ?");
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid credentials.']);
    $stmt->close(); exit;
}

$admin = $result->fetch_assoc();
$stmt->close();

if (!password_verify($password, $admin['password'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid credentials.']);
    exit;
}

// Set session with role
$_SESSION['admin_id']       = $admin['id'];
$_SESSION['admin_username'] = $admin['username'];
$_SESSION['admin_role']     = $admin['role'];
$_SESSION['is_admin']       = true;

echo json_encode([
    'success'  => true,
    'role'     => $admin['role'],
    'redirect' => 'admin.html'
]);

$conn->close();
?>