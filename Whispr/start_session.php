<?php
header('Content-Type: application/json; charset=UTF-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $db = new mysqli('localhost','s2801913','s2801913','d2801913');
    $db->set_charset('utf8mb4');

    /* ---------- inputs ---------- */
    $clientId   = (int)($_POST['clientId'] ?? 0);
    $problemIds = $_POST['problemIds']     ?? [];

    if ($clientId === 0 || empty($problemIds)) {
        throw new Exception('Missing clientId or problemIds');
    }

    /* ---------- 1. reuse open session ---------- */
    $stmt = $db->prepare(
        "SELECT session_id, counsellor_id
           FROM Sessions
          WHERE client_id = ? AND end_time IS NULL
          LIMIT 1"
    );
    $stmt->bind_param('i', $clientId);
    $stmt->execute();
    $reuse = $stmt->get_result()->fetch_assoc();

    if ($reuse) {
        echo json_encode([
            'success'      => true,
            'session_id'   => $reuse['session_id'],
            'counsellorId' => $reuse['counsellor_id'],
            'reused'       => true
        ]);
        exit;
    }

    /* ---------- 2. least-busy counsellor ---------- */
    $ph  = implode(',', array_fill(0, count($problemIds), '?'));
    $typ = str_repeat('i', count($problemIds));      // e.g. 'iii'

    $sql = "
        SELECT cp.user_id AS counsellor_id
          FROM Counsellor_Problems cp
         WHERE cp.problem_id IN ($ph)
      GROUP BY cp.user_id
      ORDER BY (
                 SELECT COUNT(*)
                   FROM Sessions s
                  WHERE s.counsellor_id = cp.user_id
                    AND s.end_time IS NULL
               ) ASC
         LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->bind_param($typ, ...$problemIds);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row || empty($row['counsellor_id'])) {
        throw new Exception('No counsellor available for those problems');
    }
    $counsellorId = (int)$row['counsellor_id'];

    /* ---------- 3. look up usernames for aliases ---------- */
    $stmt = $db->prepare("SELECT username FROM Users WHERE user_id = ? LIMIT 1");

    $stmt->bind_param('i', $clientId);
    $stmt->execute();
    $clientAlias = ($stmt->get_result()->fetch_column()) ?: 'Client';

    $stmt->bind_param('i', $counsellorId);
    $stmt->execute();
    $counsellorAlias = ($stmt->get_result()->fetch_column()) ?: 'Couns';

    /* ---------- 4. create session ---------- */
    $stmt = $db->prepare(
        "INSERT INTO Sessions
               (client_id, counsellor_id, client_alias, counsellor_alias,
                status, start_time)
         VALUES (?,?,?,?, 'in_progress', NOW())"
    );
    $stmt->bind_param('iiss', $clientId, $counsellorId,
                               $clientAlias, $counsellorAlias);
    $stmt->execute();
    $sessionId = $stmt->insert_id;

    echo json_encode([
        'success'      => true,
        'session_id'   => $sessionId,
        'counsellorId' => $counsellorId,
        'reused'       => false
    ]);
}
catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
