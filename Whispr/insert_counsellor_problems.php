<?php
header('Content-Type: application/json; charset=UTF-8');
ini_set('display_errors',0);
error_reporting(0);

$db = new mysqli('localhost','s2801913','s2801913','d2801913');
if ($db->connect_error) {
  echo json_encode(['success'=>false,'message'=>'DB connect failed']);
  exit;
}

$username  = trim($_POST['username']   ?? '');
$ids_csv   = trim($_POST['problemIds'] ?? '');

if ($username === '') {
  echo json_encode(['success'=>false,'message'=>'Username required']);
  exit;
}

$stmt = $db->prepare("SELECT user_id FROM Users WHERE username=?");
$stmt->bind_param('s',$username);
$stmt->execute();
$stmt->bind_result($user_id);
if (!$stmt->fetch()) {
  echo json_encode(['success'=>false,'message'=>'Unknown user']);
  exit;
}
$stmt->close();

$del = $db->prepare("DELETE FROM Counsellor_Problems WHERE user_id=?");
$del->bind_param('i',$user_id);
$del->execute();
$del->close();

if ($ids_csv !== '') {
  $ids = explode(',', $ids_csv);
  $ins = $db->prepare(
    "INSERT INTO Counsellor_Problems (user_id,problem_id) VALUES (?,?)"
  );
  foreach ($ids as $pid) {
    $pid = (int)$pid;
    $ins->bind_param('ii', $user_id, $pid);
    $ins->execute();
  }
  $ins->close();
}

echo json_encode(['success'=>true,'message'=>'Problems saved.']);
$db->close();
