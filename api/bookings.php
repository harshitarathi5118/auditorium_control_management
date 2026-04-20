<?php
header('Content-Type: application/json');
include_once __DIR__ . '/../config/database.php';
if (!isset($conn) && isset($mysqli)) { $conn = $mysqli; }
if (!isset($conn) || !($conn instanceof mysqli)) { http_response_code(500); echo json_encode(['success'=>false,'data'=>[],'message'=>'Database connection not available']); exit; }
function respond($success,$data=[],$message='',$code=200){ http_response_code($code); echo json_encode(['success'=>$success,'data'=>$data,'message'=>$message]); exit; }
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'GET'){
    $sql = "SELECT b.booking_id, b.booking_date, e.event_id, e.event_name, e.event_date, e.start_time, e.end_time, a.auditorium_id, a.auditorium_name FROM bookings b JOIN events e ON b.event_id = e.event_id JOIN auditoriums a ON b.auditorium_id = a.auditorium_id ORDER BY b.booking_date DESC, e.event_date DESC, e.start_time ASC";
    $stmt = $conn->prepare($sql); if (!$stmt) respond(false,[], 'Database error',500); if (!$stmt->execute()){ $stmt->close(); respond(false,[], 'Query failed',500); }
    $res = $stmt->get_result(); $rows = $res->fetch_all(MYSQLI_ASSOC); $stmt->close(); respond(true,$rows,'',200);
} elseif ($method === 'POST'){
    if (!isset($_SERVER['CONTENT_TYPE']) || stripos($_SERVER['CONTENT_TYPE'],'application/json') === false) respond(false,[], 'Content-Type must be application/json',400);
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) respond(false,[], 'Invalid JSON payload',400);
    $event_id = isset($input['event_id']) ? intval($input['event_id']) : 0; $auditorium_id = isset($input['auditorium_id']) ? intval($input['auditorium_id']) : 0;
    if ($event_id<=0 || $auditorium_id<=0) respond(false,[], 'event_id and auditorium_id are required and must be positive integers',400);
    $stmt = $conn->prepare("SELECT event_date, start_time, end_time FROM events WHERE event_id = ? LIMIT 1"); if (!$stmt) respond(false,[], 'Prepare failed',500); $stmt->bind_param('i',$event_id);
    if (!$stmt->execute()){ $stmt->close(); respond(false,[], 'Query failed',500); }
    $res = $stmt->get_result(); $event = $res->fetch_assoc(); $stmt->close(); if (!$event) respond(false,[], 'Event not found',404);
    $event_date = $event['event_date']; $start_time = $event['start_time']; $end_time = $event['end_time'];
    $aStmt = $conn->prepare("SELECT status FROM auditoriums WHERE auditorium_id = ? LIMIT 1 FOR UPDATE");
    if (!$aStmt) { $conn->rollback(); respond(false,[], 'Prepare failed',500); }
    $conn->begin_transaction();
    $aStmt->bind_param('i', $auditorium_id);
    if (!$aStmt->execute()){ $aStmt->close(); $conn->rollback(); respond(false,[], 'Query failed',500); }
    $aRes = $aStmt->get_result(); $aud = $aRes->fetch_assoc(); $aStmt->close(); if (!$aud) { $conn->rollback(); respond(false,[], 'Auditorium not found',404); }
    if (isset($aud['status']) && strtolower($aud['status']) === 'unavailable') { $conn->rollback(); respond(false,[], 'Auditorium is unavailable',409); }
    $conflict_sql = "SELECT b.booking_id FROM bookings b JOIN events e ON b.event_id = e.event_id WHERE b.auditorium_id = ? AND e.event_date = ? AND (? < e.end_time AND ? > e.start_time) LIMIT 1 FOR UPDATE";
    $cstmt = $conn->prepare($conflict_sql); if (!$cstmt) { $conn->rollback(); respond(false,[], 'Prepare failed',500); }
    $cstmt->bind_param('isss', $auditorium_id, $event_date, $end_time, $start_time);
    if (!$cstmt->execute()){ $cstmt->close(); $conn->rollback(); respond(false,[], 'Query failed',500); }
    $cres = $cstmt->get_result(); $conflict = $cres->fetch_assoc(); $cstmt->close(); if ($conflict) { $conn->rollback(); respond(false,[], 'Auditorium already booked for this time',409); }
    $booking_date = date('Y-m-d');
    $istmt = $conn->prepare("INSERT INTO bookings (booking_date, event_id, auditorium_id) VALUES (?, ?, ?)"); if (!$istmt) { $conn->rollback(); respond(false,[], 'Prepare failed',500); }
    $istmt->bind_param('sii', $booking_date, $event_id, $auditorium_id);
    if ($istmt->execute()){ $id=$istmt->insert_id; $istmt->close(); $conn->commit(); respond(true,['booking_id'=>$id],'Booking created',201); } else { $err=$istmt->error; $istmt->close(); $conn->rollback(); respond(false,[],'Insert failed: '.$err,500); }
} elseif ($method === 'DELETE'){
    parse_str(file_get_contents('php://input'), $data);
    $booking_id = isset($data['booking_id']) ? intval($data['booking_id']) : 0; if ($booking_id<=0) respond(false,[], 'booking_id required',400);
    $stmt = $conn->prepare("SELECT 1 FROM bookings WHERE booking_id = ? LIMIT 1"); if (!$stmt) respond(false,[], 'Prepare failed',500);
    $stmt->bind_param('i',$booking_id); if (!$stmt->execute()){ $stmt->close(); respond(false,[], 'Query failed',500); }
    $stmt->store_result(); if ($stmt->num_rows===0){ $stmt->close(); respond(false,[], 'Booking not found',404); } $stmt->close();
    $dstmt = $conn->prepare("DELETE FROM bookings WHERE booking_id = ?"); if (!$dstmt) respond(false,[], 'Prepare failed',500); $dstmt->bind_param('i',$booking_id);
    if ($dstmt->execute()){ $dstmt->close(); respond(true,[],'Booking cancelled',200); } else { $err=$dstmt->error; $dstmt->close(); respond(false,[],'Delete failed: '.$err,500); }
} else { http_response_code(405); respond(false,[], 'Method not allowed',405); }