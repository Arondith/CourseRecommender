<?php
// register.php
header('Content-Type: application/json');
session_start();
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

// --- Sanitize inputs ---
$first_name  = trim($_POST['first_name']  ?? '');
$middle_name = trim($_POST['middle_name'] ?? '');
$last_name   = trim($_POST['last_name']   ?? '');
$phone       = trim($_POST['phone']       ?? '');
$email       = trim($_POST['email']       ?? '');
$password    =      $_POST['password']    ?? '';
$strand      = trim($_POST['strand']      ?? '');

// --- Required field check ---
if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($strand) || empty($phone)) {
    echo json_encode(['success' => false, 'message' => 'Please fill all required fields.']);
    exit;
}

// --- Name validation (letters only) ---
if (!preg_match("/^[a-zA-ZÀ-ÿ\s\-']+$/u", $first_name) ||
    !preg_match("/^[a-zA-ZÀ-ÿ\s\-']+$/u", $last_name)) {
    echo json_encode(['success' => false, 'message' => 'Names must contain letters only.']);
    exit;
}

if (!empty($middle_name) && !preg_match("/^[a-zA-ZÀ-ÿ\s\-']+$/u", $middle_name)) {
    echo json_encode(['success' => false, 'message' => 'Middle name must contain letters only.']);
    exit;
}

// --- Phone validation (PH format: 09XXXXXXXXX or +639XXXXXXXXX) ---
if (!preg_match('/^(\+639|09)\d{9}$/', $phone)) {
    echo json_encode(['success' => false, 'message' => 'Enter a valid PH phone number (e.g. 09XXXXXXXXX).']);
    exit;
}

// --- Email validation ---
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
    exit;
}

// --- Password strength (min 8 chars, 1 uppercase, 1 number, 1 special char) ---
if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters.']);
    exit;
}
if (!preg_match('/[A-Z]/', $password)) {
    echo json_encode(['success' => false, 'message' => 'Password must contain at least one uppercase letter.']);
    exit;
}
if (!preg_match('/[0-9]/', $password)) {
    echo json_encode(['success' => false, 'message' => 'Password must contain at least one number.']);
    exit;
}
if (!preg_match('/[\W_]/', $password)) {
    echo json_encode(['success' => false, 'message' => 'Password must contain at least one special character.']);
    exit;
}

// --- Strand validation ---
$allowed_strands = ['STEM', 'ABM', 'HUMSS'];
if (!in_array($strand, $allowed_strands)) {
    echo json_encode(['success' => false, 'message' => 'Invalid strand selected.']);
    exit;
}

// --- Check duplicate email ---
$stmt = $conn->prepare("SELECT id FROM students WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email already registered.']);
    $stmt->close(); exit;
}
$stmt->close();

// --- Check duplicate phone ---
$stmt = $conn->prepare("SELECT id FROM students WHERE phone = ?");
$stmt->bind_param('s', $phone);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Phone number already registered.']);
    $stmt->close(); exit;
}
$stmt->close();

// --- Build full name & hash password ---
$full_name = trim("$first_name $middle_name $last_name");
$hashed    = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// --- Insert ---
$stmt = $conn->prepare("
    INSERT INTO students (first_name, middle_name, last_name, phone, name, email, password, strand)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param('ssssssss', $first_name, $middle_name, $last_name, $phone, $full_name, $email, $hashed, $strand);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Registration successful!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
}

$stmt->close();
$conn->close();
?>