<?php
error_reporting(0);
include '../Includes/dbcon.php';
include '../Includes/session.php';

header('Content-Type: application/json');

date_default_timezone_set('Asia/Kolkata');
@mysqli_query($conn, "SET time_zone = '+05:30'");

function jsonOut($ok, $message, $data = null) {
  echo json_encode([
    'ok' => $ok,
    'message' => $message,
    'data' => $data
  ]);
  exit;
}

$teacherId = intval($_SESSION['userId']);
$classId = strval($_SESSION['classId'] ?? '');
$classArmId = strval($_SESSION['classArmId'] ?? '');

if ($teacherId <= 0 || $classId === '' || $classArmId === '') {
  jsonOut(false, 'Session missing. Please re-login.');
}

$turnOn = isset($_POST['turnOn']) ? intval($_POST['turnOn']) : 0;

$today = date('Y-m-d');
$now = date('Y-m-d H:i:s');

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tblqr_access (
  Id int(10) NOT NULL AUTO_INCREMENT,
  teacherId int(10) NOT NULL,
  classId varchar(10) NOT NULL,
  classArmId varchar(10) NOT NULL,
  dateTaken varchar(20) NOT NULL,
  isOn tinyint(1) NOT NULL DEFAULT 0,
  token varchar(64) DEFAULT NULL,
  createdAt datetime DEFAULT NULL,
  updatedAt datetime DEFAULT NULL,
  PRIMARY KEY (Id),
  UNIQUE KEY uniq_class_day (classId, classArmId, dateTaken)
) ENGINE=MyISAM DEFAULT CHARSET=latin1");

// Backward compatibility: if older versions created multiple rows (e.g., per-subject/per-teacher), keep only one row per class/day
@mysqli_query($conn, "DELETE FROM tblqr_access WHERE classId = '".mysqli_real_escape_string($conn, $classId)."' AND classArmId = '".mysqli_real_escape_string($conn, $classArmId)."' AND dateTaken = '".mysqli_real_escape_string($conn, $today)."'");

$qrSecret = 'AMS_QR_SECRET_2025';
$qrText = '';

if ($turnOn === 1) {
  $token = bin2hex(random_bytes(16));

  $stmtIns = $conn->prepare("INSERT INTO tblqr_access(teacherId,classId,classArmId,dateTaken,isOn,token,createdAt,updatedAt) VALUES(?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE isOn = VALUES(isOn), token = VALUES(token), updatedAt = VALUES(updatedAt)");
  $isOn = 1;
  $stmtIns->bind_param('isssisss', $teacherId, $classId, $classArmId, $today, $isOn, $token, $now, $now);
  if (!$stmtIns->execute()) {
    $stmtIns->close();
    jsonOut(false, 'Failed to enable QR access.');
  }
  $stmtIns->close();

  jsonOut(true, 'QR Code Access Enabled', [
    'isOn' => 1,
    'qrText' => ''
  ]);
}

$stmtOff = $conn->prepare("INSERT INTO tblqr_access(teacherId,classId,classArmId,dateTaken,isOn,token,createdAt,updatedAt) VALUES(?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE isOn = VALUES(isOn), updatedAt = VALUES(updatedAt)");
$isOn = 0;
$token = '';
$stmtOff->bind_param('isssisss', $teacherId, $classId, $classArmId, $today, $isOn, $token, $now, $now);
if (!$stmtOff->execute()) {
  $stmtOff->close();
  jsonOut(false, 'Failed to disable QR access.');
}
$stmtOff->close();

jsonOut(true, 'QR Code Access Disabled', [
  'isOn' => 0,
  'qrText' => ''
]);
