<?php
header('Content-Type: application/json');
include_once __DIR__ . '/../config/database.php';
if (!isset($conn) && isset($mysqli)) { $conn = $mysqli; }
if (!isset($conn) || !($conn instanceof mysqli)) { http_response_code(500); echo json_encode(['success' => false, 'data' => [], 'message' => 'Database connection not available']); exit; }
function respond($success, $data = [], $message = '', $code = 200) { http_response_code($code); echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]); exit; }
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'GET') {
    $sql = "SELECT e.event_id, e.event_name, e.event_date, e.start_time, e.end_time, e.organizer_id, o.organizer_name
            FROM events e
            LEFT JOIN organizers o ON e.organizer_id = o.organizer_id
            ORDER BY e.event_date DESC, e.start_time ASC";
    $stmt = $conn->prepare($sql); if (!$stmt) respond(false, [], 'Database error', 500);
    if (!$stmt->execute()) { $stmt->close(); respond(false, [], 'Database error', 500); }
    $res = $stmt->get_result(); $rows = $res->fetch_all(MYSQLI_ASSOC); $stmt->close(); respond(true, $rows, '', 200);
} elseif ($method === 'POST') {
    if (!isset($_SERVER['CONTENT_TYPE']) || stripos($_SERVER['CONTENT_TYPE'], 'application/json') === false) respond(false, [], 'Content-Type must be application/json', 400);
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) respond(false, [], 'Invalid JSON payload', 400);
    $event_name = substr(trim($input['event_name'] ?? ''), 0, 255);
    $event_date = trim($input['event_date'] ?? '');
    $start_time = trim($input['start_time'] ?? '');
    $end_time = trim($input['end_time'] ?? '');
    $organizer_id = isset($input['organizer_id']) ? intval($input['organizer_id']) : 0;
    if ($event_name === '' || $event_date === '' || $start_time === '' || $end_time === '' || $organizer_id <= 0) respond(false, [], 'Missing required fields', 400);
    $d = DateTime::createFromFormat('Y-m-d', $event_date); if (!$d || $d->format('Y-m-d') !== $event_date) respond(false, [], 'Invalid event_date format', 400);
    $s = DateTime::createFromFormat('H:i:s', $start_time) ?: DateTime::createFromFormat('H:i', $start_time);
    $e = DateTime::createFromFormat('H:i:s', $end_time) ?: DateTime::createFromFormat('H:i', $end_time);
    if (!$s || !$e) respond(false, [], 'Invalid time format', 400);
    if ($s >= $e) respond(false, [], 'start_time must be earlier than end_time', 400);
    $today = date('Y-m-d'); if ($event_date < $today) respond(false, [], 'Event date cannot be in the past', 400);
    if ($event_date === $today) { $current_time = date('H:i:s'); if ($start_time <= $current_time) respond(false, [], 'Start time must be in the future', 400); }
    $chk = $conn->prepare("SELECT 1 FROM organizers WHERE organizer_id = ? LIMIT 1"); if (!$chk) respond(false, [], 'Database error', 500);
    $chk->bind_param('i', $organizer_id); if (!$chk->execute()) { $chk->close(); respond(false, [], 'Database error', 500); }
    $chk->store_result(); if ($chk->num_rows === 0) { $chk->close(); respond(false, [], 'Organizer not found', 404); } $chk->close();
    $stmt = $conn->prepare("INSERT INTO events (event_name, event_date, start_time, end_time, organizer_id) VALUES (?, ?, ?, ?, ?)"); if (!$stmt) respond(false, [], 'Database error', 500);
    $stmt->bind_param('ssssi', $event_name, $event_date, $start_time, $end_time, $organizer_id);
    if ($stmt->execute()) { $id = $stmt->insert_id; $stmt->close(); respond(true, ['event_id' => $id], 'Event created', 201); } else { $err = $stmt->error; $stmt->close(); respond(false, [], 'Insert failed: ' . $err, 500); }
} elseif ($method === 'PUT') {
    parse_str(file_get_contents('php://input'), $data);
    $event_id = isset($data['event_id']) ? intval($data['event_id']) : 0; if ($event_id <= 0) respond(false, [], 'event_id is required for update', 400);
    $fields = []; $params = [];
    if (isset($data['event_name'])) { $fields[] = 'event_name = ?'; $params[] = substr(trim($data['event_name']),0,255); }
    if (isset($data['event_date'])) { $fields[] = 'event_date = ?'; $params[] = trim($data['event_date']); }
    if (isset($data['start_time'])) { $fields[] = 'start_time = ?'; $params[] = trim($data['start_time']); }
    if (isset($data['end_time'])) { $fields[] = 'end_time = ?'; $params[] = trim($data['end_time']); }
    if (isset($data['organizer_id'])) { $fields[] = 'organizer_id = ?'; $params[] = intval($data['organizer_id']); }
    if (empty($fields)) respond(false, [], 'No fields to update', 400);
    if (isset($data['event_date'])) { $d = DateTime::createFromFormat('Y-m-d', trim($data['event_date'])); if (!$d || $d->format('Y-m-d') !== trim($data['event_date'])) respond(false, [], 'Invalid event_date format', 400); }
    if (isset($data['start_time']) || isset($data['end_time'])) {
        $start_time = isset($data['start_time']) ? trim($data['start_time']) : null;
        $end_time = isset($data['end_time']) ? trim($data['end_time']) : null;
        if ($start_time && $end_time) {
            $s = DateTime::createFromFormat('H:i:s', $start_time) ?: DateTime::createFromFormat('H:i', $start_time);
            $e = DateTime::createFromFormat('H:i:s', $end_time) ?: DateTime::createFromFormat('H:i', $end_time);
            if (!$s || !$e) respond(false, [], 'Invalid time format', 400);
            if ($s >= $e) respond(false, [], 'start_time must be earlier than end_time', 400);
        }
    }
    if (isset($data['organizer_id'])) { $orgId = intval($data['organizer_id']); $chk = $conn->prepare("SELECT 1 FROM organizers WHERE organizer_id = ? LIMIT 1"); if (!$chk) respond(false, [], 'Database error', 500); $chk->bind_param('i', $orgId); if (!$chk->execute()) { $chk->close(); respond(false, [], 'Database error', 500); } $chk->store_result(); if ($chk->num_rows === 0) { $chk->close(); respond(false, [], 'Organizer not found', 404); } $chk->close(); }
    $types = ''; $bind_values = [];
    foreach ($params as $p) { $types .= is_int($p) ? 'i' : 's'; $bind_values[] = $p; }
    $types .= 'i'; $bind_values[] = $event_id;
    $sql = 'UPDATE events SET ' . implode(', ', $fields) . ' WHERE event_id = ?';
    $stmt = $conn->prepare($sql); if (!$stmt) respond(false, [], 'Database error', 500);
    $refs = []; $refs[] = & $types; foreach ($bind_values as $k => $v) $refs[] = & $bind_values[$k]; call_user_func_array([$stmt, 'bind_param'], $refs);
    if ($stmt->execute()) { $stmt->close(); respond(true, [], 'Event updated', 200); } else { $err = $stmt->error; $stmt->close(); respond(false, [], 'Update failed: ' . $err, 500); }
} elseif ($method === 'DELETE') {
    parse_str(file_get_contents('php://input'), $data);
    $event_id = isset($data['event_id']) ? intval($data['event_id']) : 0; if ($event_id <= 0) respond(false, [], 'event_id is required for delete', 400);
    $chk = $conn->prepare("SELECT 1 FROM bookings WHERE event_id = ? LIMIT 1"); if (!$chk) respond(false, [], 'Database error', 500);
    $chk->bind_param('i', $event_id); if (!$chk->execute()) { $chk->close(); respond(false, [], 'Database error', 500); }
    $chk->store_result(); if ($chk->num_rows > 0) { $chk->close(); respond(false, [], 'Cannot delete event with existing bookings', 409); }
    $chk->close();
    $stmt = $conn->prepare("DELETE FROM events WHERE event_id = ?"); if (!$stmt) respond(false, [], 'Database error', 500);
    $stmt->bind_param('i', $event_id);
    if ($stmt->execute()) { $stmt->close(); respond(true, [], 'Event deleted', 200); } else { $err = $stmt->error; $stmt->close(); respond(false, [], 'Delete failed: ' . $err, 500); }
} else { http_response_code(405); respond(false, [], 'Method not allowed', 405); }
