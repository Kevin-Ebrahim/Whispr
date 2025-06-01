<?php
ini_set('display_errors',0); error_reporting(0);
header('Content-Type: application/json; charset=UTF-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $db = new mysqli('localhost','s2801913','s2801913','d2801913');
    $db->set_charset('utf8mb4');

    $userId   = (int)($_POST['userId']   ?? 0);
    $userType = strtolower($_POST['userType'] ?? '');

    if ($userId===0 || !in_array($userType,['client','counsellor']))
        throw new Exception('Missing or bad params');

    /*  Build a role-sensitive query.
        We alias the “name to show” as display_alias so Android doesn’t care  */
    $where   = $userType === 'client' ? 's.client_id = ?'      : 's.counsellor_id = ?';
    $alias   = $userType === 'client' ? 's.counsellor_alias'   : 's.client_alias';

    $sql = "
        SELECT s.session_id,
               $alias              AS display_alias,
               COALESCE(m.message_text,'') AS last_text,
               COALESCE(UNIX_TIMESTAMP(m.sent_at)*1000,0) AS last_time
         FROM Sessions s
	     LEFT JOIN Messages m
         ON m.session_id = s.session_id
		 AND m.message_id = (SELECT MAX(message_id) FROM Messages WHERE session_id = s.session_id)
         WHERE $where
         ORDER BY m.sent_at DESC, s.session_id DESC";

    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
	$result = $stmt->get_result();

	$rows = [];
	while ($row = $result->fetch_assoc()) {   // <-- guarantees associative
    	$rows[] = $row;
	}	

    echo json_encode($rows);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
