<?php
header('Content-Type: application/json');
include_once __DIR__ . '/../config/database.php';
if (!isset($conn) && isset($mysqli)) { $conn = $mysqli; }
if (!isset($conn) || !($conn instanceof mysqli)) { http_response_code(500); echo json_encode(['success'=>false,'data'=>[],'message'=>'Database connection not available']); exit; }
function respond($success,$data=[],$message='',$code=200){ http_response_code($code); echo json_encode(['success'=>$success,'data'=>$data,'message'=>$message]); exit; }
$method = $_SERVER['REQUEST_METHOD'];
if ($method==='GET'){
    $sql = "SELECT s.staff_id, s.staff_name, s.role, s.contact_number, s.auditorium_id, a.auditorium_name FROM staff s LEFT JOIN auditoriums a ON s.auditorium_id = a.auditorium_id ORDER BY s.staff_name";
    $stmt=$conn->prepare($sql); if(!$stmt) respond(false,[], 'Database error',500); if(!$stmt->execute()){ $stmt->close(); respond(false,[], 'Query failed',500);} $res=$stmt->get_result(); $rows=$res->fetch_all(MYSQLI_ASSOC); $stmt->close(); respond(true,$rows,'',200);
} elseif($method==='POST'){
    if (!isset($_SERVER['CONTENT_TYPE']) || stripos($_SERVER['CONTENT_TYPE'],'application/json')===false) respond(false,[], 'Content-Type must be application/json',400);
    $input=json_decode(file_get_contents('php://input'), true)?:[]; $staff_name=substr(trim($input['staff_name']??''),0,255); $role=substr(trim($input['role']??''),0,255); $contact=substr(trim($input['contact_number']??''),0,50); $auditorium_id=isset($input['auditorium_id'])?intval($input['auditorium_id']):null;
    if($staff_name==='') respond(false,[], 'staff_name required',400);
    if($auditorium_id!==null && $auditorium_id>0){ $chk=$conn->prepare("SELECT 1 FROM auditoriums WHERE auditorium_id = ? LIMIT 1"); if(!$chk) respond(false,[], 'Prepare failed',500); $chk->bind_param('i',$auditorium_id); if(!$chk->execute()){ $chk->close(); respond(false,[], 'Query failed',500); } $chk->store_result(); if($chk->num_rows===0){ $chk->close(); respond(false,[], 'auditorium not found',404); } $chk->close(); } else { $auditorium_id=null; }
    if($auditorium_id===null){ $stmt=$conn->prepare("INSERT INTO staff (staff_name, role, contact_number, auditorium_id) VALUES (?, ?, ?, NULL)"); if(!$stmt) respond(false,[], 'Prepare failed',500); $stmt->bind_param('sss',$staff_name,$role,$contact); } else { $stmt=$conn->prepare("INSERT INTO staff (staff_name, role, contact_number, auditorium_id) VALUES (?, ?, ?, ?)"); if(!$stmt) respond(false,[], 'Prepare failed',500); $stmt->bind_param('sssi',$staff_name,$role,$contact,$auditorium_id); }
    if($stmt->execute()){ $id=$stmt->insert_id; $stmt->close(); respond(true,['staff_id'=>$id],'Staff created',201); } else { $err=$stmt->error; $stmt->close(); respond(false,[],'Insert failed: '.$err,500); }
} elseif($method==='PUT'){
    parse_str(file_get_contents('php://input'), $data); $staff_id=isset($data['staff_id'])?intval($data['staff_id']):0; if($staff_id<=0) respond(false,[], 'staff_id required',400);
    $fields=[]; $params=[]; if(isset($data['staff_name'])){ $fields[]='staff_name=?'; $params[]=substr(trim($data['staff_name']),0,255); } if(isset($data['role'])){ $fields[]='role=?'; $params[]=substr(trim($data['role']),0,255); } if(isset($data['contact_number'])){ $fields[]='contact_number=?'; $params[]=substr(trim($data['contact_number']),0,50); } if(isset($data['auditorium_id'])){ $aid=intval($data['auditorium_id']); $chk=$conn->prepare("SELECT 1 FROM auditoriums WHERE auditorium_id = ? LIMIT 1"); if(!$chk) respond(false,[], 'Prepare failed',500); $chk->bind_param('i',$aid); if(!$chk->execute()){ $chk->close(); respond(false,[], 'Query failed',500); } $chk->store_result(); if($chk->num_rows===0){ $chk->close(); respond(false,[], 'auditorium not found',404); } $chk->close(); $fields[]='auditorium_id=?'; $params[]=$aid; }
    if(empty($fields)) respond(false,[], 'No fields to update',400); $types=''; $bind_vals=[]; foreach($params as $p){ $types .= is_int($p)?'i':'s'; $bind_vals[]=$p; } $types.='i'; $bind_vals[]=$staff_id; $sql='UPDATE staff SET '.implode(', ',$fields).' WHERE staff_id = ?'; $stmt=$conn->prepare($sql); if(!$stmt) respond(false,[], 'Prepare failed',500); $refs=[]; $refs[]=&$types; foreach($bind_vals as $k=>$v) $refs[]=&$bind_vals[$k]; call_user_func_array([$stmt,'bind_param'],$refs);
    if($stmt->execute()){ $stmt->close(); respond(true,[],'Staff updated',200); } else { $err=$stmt->error; $stmt->close(); respond(false,[],'Update failed: '.$err,500); }
} elseif($method==='DELETE'){
    parse_str(file_get_contents('php://input'), $data); $staff_id=isset($data['staff_id'])?intval($data['staff_id']):0; if($staff_id<=0) respond(false,[], 'staff_id required',400);
    $stmt=$conn->prepare("DELETE FROM staff WHERE staff_id = ?"); if(!$stmt) respond(false,[], 'Prepare failed',500); $stmt->bind_param('i',$staff_id);
    if($stmt->execute()){ $stmt->close(); respond(true,[],'Staff deleted',200); } else { $err=$stmt->error; $stmt->close(); respond(false,[],'Delete failed: '.$err,500); }
} else { http_response_code(405); respond(false,[], 'Method not allowed',405); }
