<?php
header('Content-Type: application/json');
include_once __DIR__ . '/../config/database.php';
if (!isset($conn) && isset($mysqli)) { $conn = $mysqli; }
if (!isset($conn) || !($conn instanceof mysqli)) { http_response_code(500); echo json_encode(['success'=>false,'data'=>[],'message'=>'Database connection not available']); exit; }
function respond($success,$data=[],$message='',$code=200){ http_response_code($code); echo json_encode(['success'=>$success,'data'=>$data,'message'=>$message]); exit; }
$method = $_SERVER['REQUEST_METHOD'];
if ($method==='GET'){
    $sql = "SELECT equipment_id, equipment_name, quantity, status FROM equipment ORDER BY equipment_name";
    $stmt=$conn->prepare($sql); if(!$stmt) respond(false,[], 'Database error',500); if(!$stmt->execute()){ $stmt->close(); respond(false,[], 'Query failed',500); } $res=$stmt->get_result(); $rows=$res->fetch_all(MYSQLI_ASSOC); $stmt->close(); respond(true,$rows,'',200);
} elseif($method==='POST'){
    if (!isset($_SERVER['CONTENT_TYPE']) || stripos($_SERVER['CONTENT_TYPE'],'application/json')===false) respond(false,[], 'Content-Type must be application/json',400);
    $input=json_decode(file_get_contents('php://input'), true); if(!is_array($input)) respond(false,[], 'Invalid JSON',400);
    $name=substr(trim($input['equipment_name']??''),0,255); $qty=isset($input['quantity'])?intval($input['quantity']):null; $status=substr(trim($input['status']??'Available'),0,50);
    if($name==='') respond(false,[], 'equipment_name required',400); if($qty===null) respond(false,[], 'quantity required',400); if($qty<0) respond(false,[], 'quantity must be non-negative',400);
    $stmt=$conn->prepare("INSERT INTO equipment (equipment_name, quantity, status) VALUES (?, ?, ?)"); if(!$stmt) respond(false,[], 'Prepare failed',500); $stmt->bind_param('sis',$name,$qty,$status);
    if($stmt->execute()){ $id=$stmt->insert_id; $stmt->close(); respond(true,['equipment_id'=>$id],'Equipment created',201); } else { $err=$stmt->error; $stmt->close(); respond(false,[],'Insert failed: '.$err,500); }
} elseif($method==='PUT'){
    parse_str(file_get_contents('php://input'), $data); $equipment_id=isset($data['equipment_id'])?intval($data['equipment_id']):0; if($equipment_id<=0) respond(false,[], 'equipment_id required',400);
    $fields=[]; $params=[]; if(isset($data['equipment_name'])){ $fields[]='equipment_name=?'; $params[]=substr(trim($data['equipment_name']),0,255); } if(isset($data['quantity'])){ $q=intval($data['quantity']); if($q<0) respond(false,[], 'quantity must be non-negative',400); $fields[]='quantity=?'; $params[]=$q; } if(isset($data['status'])){ $fields[]='status=?'; $params[]=substr(trim($data['status']),0,50); }
    if(empty($fields)) respond(false,[], 'No fields to update',400);
    $types=''; $bind_vals=[]; foreach($params as $p){ $types .= is_int($p)?'i':'s'; $bind_vals[]=$p; } $types.='i'; $bind_vals[]=$equipment_id; $sql='UPDATE equipment SET '.implode(', ',$fields).' WHERE equipment_id = ?';
    $stmt=$conn->prepare($sql); if(!$stmt) respond(false,[], 'Prepare failed',500); $refs=[]; $refs[]=&$types; foreach($bind_vals as $k=>$v) $refs[]=&$bind_vals[$k]; call_user_func_array([$stmt,'bind_param'],$refs);
    if($stmt->execute()){ $stmt->close(); respond(true,[],'Equipment updated',200); } else { $err=$stmt->error; $stmt->close(); respond(false,[],'Update failed: '.$err,500); }
} elseif($method==='DELETE'){
    parse_str(file_get_contents('php://input'), $data); $equipment_id=isset($data['equipment_id'])?intval($data['equipment_id']):0; if($equipment_id<=0) respond(false,[], 'equipment_id required',400);
    $stmt=$conn->prepare("DELETE FROM equipment WHERE equipment_id = ?"); if(!$stmt) respond(false,[], 'Prepare failed',500); $stmt->bind_param('i',$equipment_id);
    if($stmt->execute()){ $stmt->close(); respond(true,[],'Equipment deleted',200); } else { $err=$stmt->error; $stmt->close(); respond(false,[],'Delete failed: '.$err,500); }
} else { http_response_code(405); respond(false,[], 'Method not allowed',405); }
