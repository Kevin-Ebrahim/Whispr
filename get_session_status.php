<?php
header('Content-Type: application/json; charset=UTF-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $db = new mysqli('localhost','s2801913','s2801913','d2801913');
    $db->set_charset('utf8mb4');

    $sid = (int)($_POST['sessionId'] ?? 0);
    if (!$sid) throw new Exception('Missing id');

    $stmt = $db->prepare("SELECT status FROM Sessions WHERE session_id = ? LIMIT 1");
    $stmt->bind_param('i',$sid);
    $stmt->execute();
    $status = $stmt->get_result()->fetch_column();
    if (!$status) throw new Exception('Session not found');

    echo json_encode(['success'=>true,'status'=>$status]);
}
catch (Exception $e){
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
