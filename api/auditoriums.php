<?php
header('Content-Type: application/json');
include_once __DIR__ . '/../config/database.php';
if (!isset($conn) && isset($mysqli)) { $conn = $mysqli; }
if (!isset($conn) || !($conn instanceof mysqli)) { http_response_code(500); echo json_encode(['success'=>false,'data'=>[],'message'=>'Database connection not available']); exit; }
function respond($success,$data=[],$message='',$code=200){ http_response_code($code); echo json_encode(['success'=>$success,'data'=>$data,'message'=>$message]); exit; }
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'GET'){
    $sql = "SELECT auditorium_id, auditorium_name, location, capacity, status FROM auditoriums ORDER BY auditorium_name";
    $stmt = $conn->prepare($sql); if (!$stmt) respond(false,[], 'Database error',500); if (!$stmt->execute()){ $stmt->close(); respond(false,[], 'Query failed',500);} $res=$stmt->get_result(); $rows=$res->fetch_all(MYSQLI_ASSOC); $stmt->close(); respond(true,$rows,'',200);
} elseif ($method === 'POST'){
    if (!isset($_SERVER['CONTENT_TYPE']) || stripos($_SERVER['CONTENT_TYPE'],'application/json')===false) respond(false,[], 'Content-Type must be application/json',400);
    $input=json_decode(file_get_contents('php://input'), true); if(!is_array($input)) respond(false,[], 'Invalid JSON',400);
    $auditorium_name = substr(trim($input['auditorium_name'] ?? ''),0,255); $location = substr(trim($input['location'] ?? ''),0,255); $capacity = isset($input['capacity']) ? intval($input['capacity']) : null; $status = substr(trim($input['status'] ?? 'Available'),0,50);
    if($auditorium_name === '' || $location === '' || $capacity === null) respond(false,[], 'Missing required fields',400); if($capacity<0) respond(false,[], 'capacity must be non-negative',400);
    $stmt=$conn->prepare("INSERT INTO auditoriums (auditorium_name, location, capacity, status) VALUES (?, ?, ?, ?)"); if(!$stmt) respond(false,[], 'Prepare failed',500); $stmt->bind_param('ssis',$auditorium_name,$location,$capacity,$status);
    if($stmt->execute()){ $id=$stmt->insert_id; $stmt->close(); respond(true,['auditorium_id'=>$id],'Auditorium created',201); } else { $err=$stmt->error; $stmt->close(); respond(false,[],'Insert failed: '.$err,500); }
} elseif ($method === 'PUT'){
    parse_str(file_get_contents('php://input'), $data);
    $auditorium_id = isset($data['auditorium_id']) ? intval($data['auditorium_id']) : 0; if ($auditorium_id<=0) respond(false,[], 'auditorium_id required',400);
    $fields=[]; $params=[]; if(isset($data['auditorium_name'])){ $fields[]='auditorium_name=?'; $params[]=substr(trim($data['auditorium_name']),0,255); } if(isset($data['location'])){ $fields[]='location=?'; $params[]=substr(trim($data['location']),0,255); } if(isset($data['capacity'])){ $cap=intval($data['capacity']); if($cap<0) respond(false,[], 'capacity must be non-negative',400); $fields[]='capacity=?'; $params[]=$cap; } if(isset($data['status'])){ $fields[]='status=?'; $params[]=substr(trim($data['status']),0,50); }
    if(empty($fields)) respond(false,[], 'No fields to update',400);
    $types=''; $bind_vals=[]; foreach($params as $p){ $types .= is_int($p)?'i':'s'; $bind_vals[]=$p; } $types.='i'; $bind_vals[]=$auditorium_id; $sql='UPDATE auditoriums SET '.implode(', ',$fields).' WHERE auditorium_id = ?';
    $stmt=$conn->prepare($sql); if(!$stmt) respond(false,[], 'Prepare failed',500); $refs=[]; $refs[]=&$types; foreach($bind_vals as $k=>$v) $refs[]=&$bind_vals[$k]; call_user_func_array([$stmt,'bind_param'],$refs);
    if($stmt->execute()){ $stmt->close(); respond(true,[],'Auditorium updated',200); } else { $err=$stmt->error; $stmt->close(); respond(false,[],'Update failed: '.$err,500); }
} elseif ($method === 'DELETE'){
    parse_str(file_get_contents('php://input'), $data); $auditorium_id = isset($data['auditorium_id'])?intval($data['auditorium_id']):0; if($auditorium_id<=0) respond(false,[], 'auditorium_id required',400);
    $chk=$conn->prepare("SELECT 1 FROM bookings WHERE auditorium_id = ? LIMIT 1"); if(!$chk) respond(false,[], 'Prepare failed',500); $chk->bind_param('i',$auditorium_id); if(!$chk->execute()){ $chk->close(); respond(false,[], 'Query failed',500); } $chk->store_result(); if($chk->num_rows>0){ $chk->close(); respond(false,[], 'Cannot delete auditorium in use by bookings',409); } $chk->close();
    $stmt=$conn->prepare("DELETE FROM auditoriums WHERE auditorium_id = ?"); if(!$stmt) respond(false,[], 'Prepare failed',500); $stmt->bind_param('i',$auditorium_id); if($stmt->execute()){ $stmt->close(); respond(true,[],'Auditorium deleted',200); } else { $err=$stmt->error; $stmt->close(); respond(false,[],'Delete failed: '.$err,500); }
} else { http_response_code(405); respond(false,[], 'Method not allowed',405); }
