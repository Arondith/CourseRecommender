<?php
// auth_check.php

function requireAdmin() {
    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
        exit;
    }
}

function requireRole(array $roles) {
    requireAdmin();
    if (!in_array($_SESSION['admin_role'], $roles)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied. Insufficient permissions.']);
        exit;
    }
}

function getAdminRole() {
    return $_SESSION['admin_role'] ?? null;
}
?>