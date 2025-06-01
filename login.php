<?php
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json; charset=UTF-8');

$mysqli = new mysqli('localhost','s2801913','s2801913','d2801913');
if ($mysqli->connect_error) {
    echo json_encode([
        'success' => false,
        'message' => 'DB connect failed: ' . $mysqli->connect_error
    ]);
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    echo json_encode([
        'success'  => false,
        'message'  => 'Username and password are required.'
    ]);
    exit;
}

$stmt = $mysqli->prepare(
    "SELECT user_id, password, user_type FROM Users WHERE username = ?"
);
$stmt->bind_param('s', $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'No account found with that username.'
    ]);
    exit;
}

$stmt->bind_result($user_id, $hash, $user_type);
$stmt->fetch();

if (!password_verify($password, $hash)) {
    echo json_encode([
        'success' => false,
        'message' => 'Incorrect password.'
    ]);
    exit;
}

echo json_encode([
    'success'  => true,
    'message'  => 'Login successful.',
    'user_type' => $user_type,
	'user_id' => $user_id
]);

$stmt->close();
$mysqli->close();
