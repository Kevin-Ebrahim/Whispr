<?php
ini_set('display_errors',0); error_reporting(0);
header('Content-Type: application/json; charset=UTF-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $db = new mysqli('localhost','s2801913','s2801913','d2801913');
    $db->set_charset('utf8mb4');

    $sessionId = (int)($_POST['sessionId'] ?? 0);
    $senderId  = (int)($_POST['senderId']  ?? 0);
    $text      = trim($_POST['text']      ?? '');

    if ($sessionId===0 || $senderId===0 || $text==='') {
        throw new Exception('Missing fields.');
    }

$stmt = $db->prepare(
  "INSERT INTO Messages (session_id,sender_id,message_text,sent_at)
   SELECT ?,?, ?,NOW()
   FROM dual
   WHERE ? IN (SELECT client_id FROM Sessions WHERE session_id=?)
   OR ? IN (SELECT counsellor_id FROM Sessions WHERE session_id=?)"
);
	$stmt->bind_param('iisiisi', $sessionId,$senderId,$text,$senderId,$sessionId,$senderId,$sessionId);
    $stmt->execute();
    echo json_encode(['success'=>true,'id'=>$db->insert_id]);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
