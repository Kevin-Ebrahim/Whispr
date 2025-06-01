<?php
// ------------------------------------------------------------
// update_session_status.php
//   Returns JSON for every outcome and treats a duplicate‐key
//   error on uniq_client_open as “already ended”.
// ------------------------------------------------------------

ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json; charset=UTF-8');

// Enable strict reporting so duplicate-key throws mysqli_sql_exception
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // 1) Connect to the database
    $mysqli = new mysqli('localhost','s2801913','s2801913','d2801913');
    $mysqli->set_charset('utf8mb4');
    if ($mysqli->connect_error) {
        throw new Exception('DB connection failed: ' . $mysqli->connect_error);
    }

    // 2) Read and validate POST parameters
    $sid   = isset($_POST['sessionId']) ? (int) $_POST['sessionId'] : 0;
    $state = isset($_POST['newStatus']) ? $_POST['newStatus']        : '';

    if (
        ! $sid
        || ! in_array($state, ['in_progress','ended'], true)
    ) {
        throw new Exception('Bad parameters: sessionId=' . $sid . ' newStatus=' . $state);
    }

    // 3) Prepare the UPDATE statement
    $sql = "
        UPDATE Sessions
           SET status   = ?,
               end_time = CASE WHEN ? = 'ended' THEN NOW() ELSE NULL END
         WHERE session_id = ?
    ";
    $stmt = $mysqli->prepare($sql);
    if (! $stmt) {
        throw new Exception('Prepare failed: ' . $mysqli->error);
    }

    // 4) Bind parameters
    $stmt->bind_param('ssi', $state, $state, $sid);

    // 5) Execute, catching duplicate-key (1062) as “already ended”
    try {
        $stmt->execute();
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() === 1062) {
            // Duplicate entry for uniq_client_open → already in that status
            echo json_encode([
                'success' => true,
                'message' => 'Status already ' . $state
            ]);
            $stmt->close();
            $mysqli->close();
            exit;
        }
        // Other SQL error
        throw new Exception('Execute failed: ' . $e->getMessage());
    }

    // 6) If no rows affected, check if session exists or is already in that state
    if ($stmt->affected_rows === 0) {
        $stmt->close();
        $chk = $mysqli->prepare("SELECT status FROM Sessions WHERE session_id = ?");
        if (! $chk) {
            throw new Exception('Prepare failed: ' . $mysqli->error);
        }
        $chk->bind_param('i', $sid);
        $chk->execute();
        $chk->bind_result($curr);
        if ($chk->fetch()) {
            $chk->close();
            if ($curr === $state) {
                // Already in desired status
                echo json_encode([
                    'success' => true,
                    'message' => 'Status already ' . $state
                ]);
                $mysqli->close();
                exit;
            } else {
                throw new Exception('Could not update status; current status is ' . $curr);
            }
        } else {
            // session_id not found
            throw new Exception('Session not found: ' . $sid);
        }
    }

    $stmt->close();

    // 7) Successful update
    echo json_encode([
        'success' => true,
        'message' => 'Status updated'
    ]);
    $mysqli->close();
    exit;
}
catch (Exception $e) {
    // Return any PHP or caught exception as JSON with HTTP 500
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'PHP error: ' . $e->getMessage()
    ]);
    exit;
}

