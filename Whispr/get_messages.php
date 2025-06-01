<?php
/* POST: sessionId, lastId */
header('Content-Type: application/json; charset=UTF-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    /* ---------- 1) CONNECT TO DB ---------- */
    $db = new mysqli('localhost','s2801913','s2801913','d2801913');
    $db->set_charset('utf8mb4');  // ensure UTF‐8 / emojis

    /* ---------- 2) READ INPUT ---------- */
    $sessionId = (int) ($_POST['sessionId'] ?? 0);
    $lastId    = (int) ($_POST['lastId']    ?? 0);
    if (!$sessionId) {
        throw new Exception('Missing sessionId');
    }

    /* ---------- 3) PREPARE & EXECUTE QUERY ---------- */
    $stmt = $db->prepare("
        SELECT
            message_id                  AS id,
            sender_id,
            message_text                AS message_text,
            UNIX_TIMESTAMP(sent_at)*1000 AS sent_ms
        FROM Messages
        WHERE session_id = ?
          AND message_id   > ?
        ORDER BY message_id ASC
    ");
    $stmt->bind_param('ii', $sessionId, $lastId);
    $stmt->execute();

    /* ---------- 4) COLLECT ROWS INTO $rows[] ---------- */
    $rows = [];
    $res  = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }

    /* ---------- 5) JSON‐ENCODE ONLY $rows ---------- */
    $json = json_encode(
        $rows,
        JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR
    );
    if ($json === false) {
        // If encoding fails, log and return an error object
        error_log('JSON encoding error in get_messages.php: ' . json_last_error_msg());
        echo json_encode(['success'=>false,'message'=>'Encoding error']);
        exit;
    }

    /* ---------- 6) ECHO THE ARRAY OF MESSAGE OBJECTS ---------- */
    // This prints something like:
    // [
    //   {"id":71,"sender_id":32,"message_text":"helo","sent_ms":1748690963000},
    //   {"id":72,"sender_id":32,"message_text":"yes","sent_ms":1748690967000},
    //   …
    // ]
    echo $json;
}
catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
