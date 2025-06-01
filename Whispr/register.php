<?php
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json; charset=UTF-8');
http_response_code(200);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $db = new mysqli('localhost','s2801913','s2801913','d2801913');
    $db->set_charset('utf8mb4');

    $username = trim($_POST['username']   ?? '');
    $password = trim($_POST['password']   ?? '');
    $userType = trim($_POST['user_type']  ?? '');

    if ($username === '' || $password === '' || $userType === '') {
        throw new Exception('All fields (Username, Password and User type) are required.');
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare(
        "INSERT INTO Users (username, password, user_type)
         VALUES (?, ?, ?)"
    );
    $stmt->bind_param('sss', $username, $hash, $userType);
    $stmt->execute();

    echo json_encode([
        'success' => true,
        'message' => 'User registered successfully.'
    ]);

} catch (mysqli_sql_exception $e) {
    if ($e->getCode() === 1062) {
        echo json_encode([
            'success' => false,
            'message' => 'That username is already taken.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
