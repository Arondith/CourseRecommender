<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/session_bootstrap.php';
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/db_connect.php';



$action = $_GET['action'] ?? $_POST['action'] ?? '';
requireAdmin();

switch ($action) {

    // ── GET SESSION ROLE ───────────────────────────────────────────────────────
    case 'get_role':
        echo json_encode([
            'success'  => true,
            'role'     => $_SESSION['admin_role'],
            'username' => $_SESSION['admin_username']
        ]);
        break;

    // ── GET DASHBOARD STATS ────────────────────────────────────────────────────
    case 'stats':
        requireRole(['superadmin', 'moderator', 'viewer']);

        $total   = $conn->query("SELECT COUNT(*) AS c FROM students")->fetch_assoc()['c'];
        $strands = $conn->query("SELECT strand, COUNT(*) AS c FROM students GROUP BY strand")->fetch_all(MYSQLI_ASSOC);
        $recent  = $conn->query("SELECT id, name, email, strand, created_at FROM students ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

        echo json_encode([
            'success' => true,
            'total'   => $total,
            'strands' => $strands,
            'recent'  => $recent
        ]);
        break;

    // ── GET ALL STUDENTS (with up to 3 attempts each) ──────────────────────────
    case 'get_students':
        requireRole(['superadmin', 'moderator', 'viewer']);

        // 1. Fetch all students
        $students = $conn->query(
            "SELECT id, name, email, strand, phone, created_at
             FROM students
             ORDER BY created_at DESC"
        )->fetch_all(MYSQLI_ASSOC);

        if (empty($students)) {
            echo json_encode(['success' => true, 'students' => []]);
            break;
        }

        // 2. Collect all student IDs for a single batch query
        $ids        = array_column($students, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types      = str_repeat('i', count($ids));

        // 3. Fetch the 3 most recent attempts per student
        //    We use a subquery that ranks attempts newest-first per student,
        //    then keep only ranks 1–3.
        $sql = "
            SELECT a.student_id,
                   a.personality,
                   a.score_r, a.score_i, a.score_a,
                   a.score_s, a.score_e, a.score_c,
                   a.taken_at
            FROM student_attempts a
            INNER JOIN (
                SELECT id,
                       student_id,
                       ROW_NUMBER() OVER (
                           PARTITION BY student_id
                           ORDER BY taken_at ASC       -- oldest first so #1 = first attempt
                       ) AS rn
                FROM student_attempts
                WHERE student_id IN ($placeholders)
            ) ranked ON a.id = ranked.id
            WHERE ranked.rn <= 3
            ORDER BY a.student_id, a.taken_at ASC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$ids);
        $stmt->execute();
        $attemptRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // 4. Group attempts by student_id and shape the riasec object
        $attemptsByStudent = [];
        foreach ($attemptRows as $row) {
            $attemptsByStudent[$row['student_id']][] = [
                'personality' => $row['personality'],
                'riasec'      => [
                    'R' => (int)$row['score_r'],
                    'I' => (int)$row['score_i'],
                    'A' => (int)$row['score_a'],
                    'S' => (int)$row['score_s'],
                    'E' => (int)$row['score_e'],
                    'C' => (int)$row['score_c'],
                ],
                'taken_at' => $row['taken_at'],
            ];
        }

        // 5. Attach attempts array to each student
        foreach ($students as &$s) {
            $s['attempts'] = $attemptsByStudent[$s['id']] ?? [];
        }
        unset($s);

        echo json_encode(['success' => true, 'students' => $students]);
        break;


    // ── DELETE STUDENT ─────────────────────────────────────────────────────────
    // student_attempts rows are removed automatically via ON DELETE CASCADE
    case 'delete_student':
        requireRole(['superadmin']);

        $id   = intval($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
        $stmt->bind_param('i', $id);
        echo json_encode(['success' => $stmt->execute(), 'message' => 'Student deleted.']);
        $stmt->close();
        break;

    // ── GET ALL ADMINS ─────────────────────────────────────────────────────────
    case 'get_admins':
        requireRole(['superadmin']);

        $rows = $conn->query("SELECT id, username, role, created_at FROM admins ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'admins' => $rows]);
        break;

    // ── ADD ADMIN ──────────────────────────────────────────────────────────────
    case 'add_admin':
        requireRole(['superadmin']);

        $uname   = trim($_POST['username'] ?? '');
        $pass    = $_POST['password'] ?? '';
        $role    = $_POST['role']     ?? '';
        $allowed = ['superadmin', 'moderator', 'viewer'];

        if (empty($uname) || empty($pass) || !in_array($role, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Invalid input.']);
            break;
        }

        $hashed = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt   = $conn->prepare("INSERT INTO admins (username, role, password) VALUES (?, ?, ?)");
        $stmt->bind_param('sss', $uname, $role, $hashed);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Admin added successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Username already exists.']);
        }
        $stmt->close();
        break;

    // ── DELETE ADMIN ───────────────────────────────────────────────────────────
    case 'delete_admin':
        requireRole(['superadmin']);

        $id = intval($_POST['id'] ?? 0);
        if ($id === $_SESSION['admin_id']) {
            echo json_encode(['success' => false, 'message' => 'Cannot delete your own account.']);
            break;
        }
        $stmt = $conn->prepare("DELETE FROM admins WHERE id = ?");
        $stmt->bind_param('i', $id);
        echo json_encode(['success' => $stmt->execute(), 'message' => 'Admin deleted.']);
        $stmt->close();
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
}

$conn->close();
?>