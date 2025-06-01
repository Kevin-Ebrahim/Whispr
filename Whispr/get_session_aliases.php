<?php
ini_set('display_errors',0); error_reporting(0);
header('Content-Type: application/json; charset=UTF-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $db = new mysqli('localhost','s2801913','s2801913','d2801913');
    $db->set_charset('utf8mb4');

    $sessionId = (int)($_POST['sessionId'] ?? 0);
    if ($sessionId === 0) throw new Exception('Missing sessionId');

    $stmt = $db->prepare(
      "SELECT client_alias, counsellor_alias
       FROM Sessions
       WHERE session_id = ?"
    );
    $stmt->bind_param('i', $sessionId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    if (!$res) throw new Exception('Session not found');

    echo json_encode([
        'success'         => true,
        'client_alias'    => $res['client_alias'],
        'counsellor_alias'=> $res['counsellor_alias']
    ]);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
