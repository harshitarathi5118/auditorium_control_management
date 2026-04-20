<?php
header('Content-Type: application/json');
include_once __DIR__ . '/../config/database.php';
if (!isset($conn) && isset($mysqli)) {
    $conn = $mysqli;
}
if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'data' => [], 'message' => 'Database connection not available']);
    exit;
}
function respond($success, $data = [], $message = '', $code = 200) {
    http_response_code($code);
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
    exit;
}
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'GET') {
    $sql = "SELECT organizer_id, organizer_name, department, contact_number, email FROM organizers ORDER BY organizer_name";
    $stmt = $conn->prepare($sql);
    if (!$stmt) respond(false, [], 'Database error: failed to prepare statement', 500);
    if (!$stmt->execute()) { $stmt->close(); respond(false, [], 'Database error: failed to execute query', 500); }
    $res = $stmt->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    respond(true, $rows, '', 200);
} elseif ($method === 'POST') {
    if (!isset($_SERVER['CONTENT_TYPE']) || stripos($_SERVER['CONTENT_TYPE'], 'application/json') === false) {
        respond(false, [], 'Content-Type must be application/json', 400);
    }
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) respond(false, [], 'Invalid JSON', 400);
    $organizer_name = substr(trim($input['organizer_name'] ?? ''), 0, 255);
    $department = substr(trim($input['department'] ?? ''), 0, 255);
    $contact = substr(trim($input['contact_number'] ?? ''), 0, 50);
    $email = substr(trim($input['email'] ?? ''), 0, 255);
    if ($organizer_name === '') respond(false, [], 'organizer_name is required', 400);
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) respond(false, [], 'Invalid email format', 400);
    $stmt = $conn->prepare("INSERT INTO organizers (organizer_name, department, contact_number, email) VALUES (?, ?, ?, ?)");
    if (!$stmt) respond(false, [], 'Database error: failed to prepare insert', 500);
    $stmt->bind_param('ssss', $organizer_name, $department, $contact, $email);
    if ($stmt->execute()) {
        $id = $stmt->insert_id;
        $stmt->close();
        respond(true, ['organizer_id' => $id], 'Organizer created', 201);
    } else {
        $err = $stmt->error;
        $stmt->close();
        respond(false, [], 'Insert failed: ' . $err, 500);
    }
} elseif ($method === 'PUT') {
    parse_str(file_get_contents('php://input'), $data);
    $organizer_id = isset($data['organizer_id']) ? intval($data['organizer_id']) : 0;
    if ($organizer_id <= 0) respond(false, [], 'organizer_id required', 400);
    $fields = [];
    $params = [];
    if (isset($data['organizer_name'])) {
        $fields[] = 'organizer_name=?';
        $params[] = substr(trim($data['organizer_name']), 0, 255);
    }
    if (isset($data['department'])) {
        $fields[] = 'department=?';
        $params[] = substr(trim($data['department']), 0, 255);
    }
    if (isset($data['contact_number'])) {
        $fields[] = 'contact_number=?';
        $params[] = substr(trim($data['contact_number']), 0, 50);
    }
    if (isset($data['email'])) {
        $email = substr(trim($data['email']), 0, 255);
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) respond(false, [], 'Invalid email format', 400);
        $fields[] = 'email=?';
        $params[] = $email;
    }
    if (empty($fields)) respond(false, [], 'No fields to update', 400);
    $types = '';
    $bind_vals = [];
    foreach ($params as $p) {
        $types .= is_int($p) ? 'i' : 's';
        $bind_vals[] = $p;
    }
    $types .= 'i';
    $bind_vals[] = $organizer_id;
    $sql = 'UPDATE organizers SET ' . implode(', ', $fields) . ' WHERE organizer_id = ?';
    $stmt = $conn->prepare($sql);
    if (!$stmt) respond(false, [], 'Database error: failed to prepare update', 500);
    $refs = [];
    $refs[] = &$types;
    foreach ($bind_vals as $k => $v) $refs[] = &$bind_vals[$k];
    call_user_func_array([$stmt, 'bind_param'], $refs);
    if ($stmt->execute()) {
        $stmt->close();
        respond(true, [], 'Organizer updated', 200);
    } else {
        $err = $stmt->error;
        $stmt->close();
        respond(false, [], 'Update failed: ' . $err, 500);
    }
} elseif ($method === 'DELETE') {
    parse_str(file_get_contents('php://input'), $data);
    $organizer_id = isset($data['organizer_id']) ? intval($data['organizer_id']) : 0;
    if ($organizer_id <= 0) respond(false, [], 'organizer_id required', 400);
    $chk = $conn->prepare("SELECT 1 FROM events WHERE organizer_id = ? LIMIT 1");
    if (!$chk) respond(false, [], 'Database error: failed to prepare check', 500);
    $chk->bind_param('i', $organizer_id);
    if (!$chk->execute()) { $chk->close(); respond(false, [], 'Database error: failed to execute check', 500); }
    $chk->store_result();
    if ($chk->num_rows > 0) {
        $chk->close();
        respond(false, [], 'Cannot delete organizer in use by events', 409);
    }
    $chk->close();
    $stmt = $conn->prepare("DELETE FROM organizers WHERE organizer_id = ?");
    if (!$stmt) respond(false, [], 'Database error: failed to prepare delete', 500);
    $stmt->bind_param('i', $organizer_id);
    if ($stmt->execute()) {
        $stmt->close();
        respond(true, [], 'Organizer deleted', 200);
    } else {
        $err = $stmt->error;
        $stmt->close();
        respond(false, [], 'Delete failed: ' . $err, 500);
    }
} else {
    http_response_code(405);
    respond(false, [], 'Method not allowed', 405);
}
