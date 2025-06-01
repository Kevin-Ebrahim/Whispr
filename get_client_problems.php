<?php
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json; charset=UTF-8');

$mysqli = new mysqli('localhost','s2801913','s2801913','d2801913');
if ($mysqli->connect_error) {
    echo json_encode([
        'success' => false,
        'message' => 'DB connection failed: ' . $mysqli->connect_error
    ]);
    exit;
}

$username = trim($_POST['username'] ?? '');
if ($username === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Username is required.'
    ]);
    exit;
}

$stmt = $mysqli->prepare(
    "SELECT user_id FROM Users WHERE username = ?"
);
$stmt->bind_param('s', $username);
$stmt->execute();
$stmt->bind_result($user_id);
if (!$stmt->fetch()) {
    echo json_encode([
        'success' => false,
        'message' => 'Unknown user.'
    ]);
    exit;
}
$stmt->close();

$stmt = $mysqli->prepare(
    "SELECT problem_id FROM Client_Problems WHERE user_id = ?"
);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($pid);

$problems = [];
while ($stmt->fetch()) {
    $problems[] = $pid;
}
$stmt->close();

echo json_encode([
    'success'  => true,
    'message'  => 'Fetched client problems.',
    'problems' => $problems
]);

$mysqli->close();
