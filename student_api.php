<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/session_bootstrap.php';
require_once __DIR__ . '/db_connect.php';

function respond(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function requireStudentId(): int
{
    $studentId = (int) ($_SESSION['student_id'] ?? 0);
    if ($studentId <= 0 || empty($_SESSION['logged_in'])) {
        respond(['success' => false, 'message' => 'Authentication required.'], 401);
    }

    return $studentId;
}

function validateRiasec(array $payload): array
{
    $traits = ['R', 'I', 'A', 'S', 'E', 'C'];
    $scores = [];

    foreach ($traits as $trait) {
        if (!array_key_exists($trait, $payload) || filter_var($payload[$trait], FILTER_VALIDATE_INT) === false) {
            respond(['success' => false, 'message' => 'Invalid assessment scores.'], 422);
        }

        $value = (int) $payload[$trait];
        if ($value < 5 || $value > 25) {
            respond(['success' => false, 'message' => 'Assessment scores are out of range.'], 422);
        }

        $scores[$trait] = $value;
    }

    return $scores;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'me') {
    $studentId = requireStudentId();

    $stmt = $conn->prepare('SELECT id, name, email, strand FROM students WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $studentId);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$student) {
        session_unset();
        session_destroy();
        respond(['success' => false, 'message' => 'Account not found.'], 401);
    }

    $attemptStmt = $conn->prepare(
        'SELECT personality, score_r, score_i, score_a, score_s, score_e, score_c, taken_at
         FROM student_attempts
         WHERE student_id = ?
         ORDER BY taken_at DESC
         LIMIT 1'
    );
    $attemptStmt->bind_param('i', $studentId);
    $attemptStmt->execute();
    $attempt = $attemptStmt->get_result()->fetch_assoc();
    $attemptStmt->close();

    $user = [
        'id' => (int) $student['id'],
        'name' => $student['name'],
        'email' => $student['email'],
        'strand' => $student['strand'],
    ];

    if ($attempt) {
        $user['personality'] = $attempt['personality'];
        $user['riasec'] = [
            'R' => (int) $attempt['score_r'],
            'I' => (int) $attempt['score_i'],
            'A' => (int) $attempt['score_a'],
            'S' => (int) $attempt['score_s'],
            'E' => (int) $attempt['score_e'],
            'C' => (int) $attempt['score_c'],
        ];
        $user['lastAssessmentAt'] = $attempt['taken_at'];
    }

    respond(['success' => true, 'user' => $user]);
}

if ($action === 'save_attempt') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(['success' => false, 'message' => 'Method not allowed.'], 405);
    }

    $studentId = requireStudentId();
    $riasec = json_decode($_POST['riasec'] ?? '{}', true);

    if (!is_array($riasec)) {
        respond(['success' => false, 'message' => 'Invalid assessment payload.'], 422);
    }

    $scores = validateRiasec($riasec);

    $personality = array_key_first($scores);
    $topScore = -1;
    foreach ($scores as $trait => $score) {
        if ($score > $topScore) {
            $topScore = $score;
            $personality = $trait;
        }
    }

    $conn->begin_transaction();

    try {
        $countStmt = $conn->prepare('SELECT COUNT(*) AS c FROM student_attempts WHERE student_id = ?');
        $countStmt->bind_param('i', $studentId);
        $countStmt->execute();
        $count = (int) $countStmt->get_result()->fetch_assoc()['c'];
        $countStmt->close();

        if ($count >= 3) {
            $deleteStmt = $conn->prepare(
                'DELETE FROM student_attempts
                 WHERE student_id = ?
                 ORDER BY taken_at ASC
                 LIMIT 1'
            );
            $deleteStmt->bind_param('i', $studentId);
            $deleteStmt->execute();
            $deleteStmt->close();
        }

        $insertStmt = $conn->prepare(
            'INSERT INTO student_attempts
                (student_id, personality, score_r, score_i, score_a, score_s, score_e, score_c)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $insertStmt->bind_param(
            'isiiiiii',
            $studentId,
            $personality,
            $scores['R'],
            $scores['I'],
            $scores['A'],
            $scores['S'],
            $scores['E'],
            $scores['C']
        );
        $insertStmt->execute();
        $insertStmt->close();

        $conn->commit();

        respond([
            'success' => true,
            'message' => 'Assessment saved.',
            'personality' => $personality,
            'riasec' => $scores,
        ]);
    } catch (Throwable $e) {
        $conn->rollback();
        error_log('CourseMatch save_attempt failed: ' . $e->getMessage());
        respond(['success' => false, 'message' => 'Unable to save assessment right now.'], 500);
    }
}

respond(['success' => false, 'message' => 'Unknown action.'], 404);
