<?php
// test_login.php - delete this file after testing
session_start();
require_once 'db_connect.php';

$username = 'admin';
$password = 'admin123';

$stmt = $conn->prepare("SELECT id, username, password, role FROM admins WHERE username = ?");
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "❌ User not found in database";
    exit;
}

$admin = $result->fetch_assoc();
echo "✅ User found: " . $admin['username'] . " | Role: " . $admin['role'] . "<br>";
echo "Hash in DB: " . $admin['password'] . "<br>";

$verify = password_verify($password, $admin['password']);
echo "Password verify result: " . ($verify ? "✅ MATCH" : "❌ NO MATCH") . "<br>";
?>