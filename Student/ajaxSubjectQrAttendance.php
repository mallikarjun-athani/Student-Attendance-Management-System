<?php
error_reporting(0);
include '../Includes/dbcon.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

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

if (!isset($_SESSION['userType']) || $_SESSION['userType'] !== 'Student' || !isset($_SESSION['studentId'])) {
  jsonOut(false, 'Not authorized.');
}

if (!isset($_POST['qrText'])) {
  jsonOut(false, 'Missing QR data.');
}

// Ensure required tables exist
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tblsubjects (
  Id int(10) NOT NULL AUTO_INCREMENT,
  classId varchar(10) NOT NULL,
  classArmId varchar(10) NOT NULL,
  syllabusType varchar(50) NOT NULL,
  subjectName varchar(255) NOT NULL,
  createdAt datetime DEFAULT NULL,
  updatedAt datetime DEFAULT NULL,
  PRIMARY KEY (Id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbltodaysubjects (
  Id int(10) NOT NULL AUTO_INCREMENT,
  classId varchar(10) NOT NULL,
  classArmId varchar(10) NOT NULL,
  dateTaken varchar(20) NOT NULL,
  subjectId int(10) NOT NULL,
  createdAt datetime DEFAULT NULL,
  PRIMARY KEY (Id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tblsubjectattendance (
  Id int(10) NOT NULL AUTO_INCREMENT,
  admissionNumber varchar(255) NOT NULL,
  subjectId int(10) NOT NULL,
  classId varchar(10) NOT NULL,
  classArmId varchar(10) NOT NULL,
  dateTaken varchar(20) NOT NULL,
  status varchar(10) NOT NULL,
  createdAt datetime DEFAULT NULL,
  PRIMARY KEY (Id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1");

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

$qrText = trim($_POST['qrText']);
$parts = array_map('trim', explode('|', $qrText));
$parts = array_values(array_filter($parts, function($p) { return $p !== ''; }));

if (count($parts) !== 7 || $parts[0] !== 'AMS_SUBJ') {
  jsonOut(false, 'Invalid QR Code');
}

$subjectId = intval($parts[1]);
$classId = trim($parts[2]);
$classArmId = trim($parts[3]);
$qrDate = trim($parts[4]);
$qrToken = trim($parts[5]);
$sig = trim($parts[6]);

$qrSecret = 'AMS_QR_SECRET_2025';

$today = date('Y-m-d');
if ($qrDate === '' || $qrDate !== $today) {
  jsonOut(false, 'Invalid QR Code');
}

$sigData = $subjectId.'|'.$classId.'|'.$classArmId.'|'.$qrDate.'|'.$qrToken;
$expectedSig = hash_hmac('sha256', $sigData, $qrSecret);
if (!hash_equals($expectedSig, $sig)) {
  jsonOut(false, 'Invalid QR Code');
}

$studentId = intval($_SESSION['studentId']);
$stmt = $conn->prepare("SELECT admissionNumber, firstName, lastName, otherName, classId, classArmId FROM tblstudents WHERE Id = ? LIMIT 1");
$stmt->bind_param('i', $studentId);
$stmt->execute();
$res = $stmt->get_result();
$student = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$student) {
  jsonOut(false, 'Student not found.');
}

$admissionNumber = trim($student['admissionNumber']);
if ($admissionNumber === '') {
  jsonOut(false, 'Student admission number missing.');
}

// QR must belong to student's class/semester
if (strval($student['classId']) !== strval($classId) || strval($student['classArmId']) !== strval($classArmId)) {
  jsonOut(false, 'Invalid QR Code');
}

// subject must exist for that class/semester
$stmt2 = $conn->prepare("SELECT subjectName FROM tblsubjects WHERE Id = ? AND classId = ? AND classArmId = ? LIMIT 1");
$stmt2->bind_param('iss', $subjectId, $classId, $classArmId);
$stmt2->execute();
$res2 = $stmt2->get_result();
$subject = $res2 ? $res2->fetch_assoc() : null;
$stmt2->close();

if (!$subject) {
  jsonOut(false, 'Invalid Subject QR Code');
}

$dateTaken = $today;
$now = date('Y-m-d H:i:s');

// Global ON/OFF (must be checked first)
$stmtA = $conn->prepare("SELECT isOn, token FROM tblqr_access WHERE classId = ? AND classArmId = ? AND dateTaken = ? LIMIT 1");
$stmtA->bind_param('sss', $classId, $classArmId, $dateTaken);
$stmtA->execute();
$resA = $stmtA->get_result();
$acc = $resA ? $resA->fetch_assoc() : null;
$stmtA->close();

if (!$acc || intval($acc['isOn']) !== 1) {
  jsonOut(false, 'QR Code Access Disabled by Teacher');
}

$activeToken = isset($acc['token']) ? trim(strval($acc['token'])) : '';
if ($activeToken === '' || !hash_equals($activeToken, $qrToken)) {
  jsonOut(false, 'Invalid QR Code');
}

// Enforce: only subjects selected for TODAY can be scanned
$stmtT = $conn->prepare("SELECT Id FROM tbltodaysubjects WHERE classId = ? AND classArmId = ? AND dateTaken = ? AND subjectId = ? LIMIT 1");
$stmtT->bind_param('sssi', $classId, $classArmId, $dateTaken, $subjectId);
$stmtT->execute();
$resT = $stmtT->get_result();
$todayRow = $resT ? $resT->fetch_assoc() : null;
$stmtT->close();

if (!$todayRow) {
  jsonOut(false, 'Subject not active today');
}

// No duplicate attendance per subject per day
$chk = $conn->prepare("SELECT Id FROM tblsubjectattendance WHERE admissionNumber = ? AND subjectId = ? AND classId = ? AND classArmId = ? AND dateTaken = ? LIMIT 1");
$chk->bind_param('sisss', $admissionNumber, $subjectId, $classId, $classArmId, $dateTaken);
$chk->execute();
$chkRes = $chk->get_result();
$existing = $chkRes ? $chkRes->fetch_assoc() : null;
$chk->close();

$studentName = trim(($student['firstName'] ?? '').' '.($student['lastName'] ?? '').' '.($student['otherName'] ?? ''));
$subjectName = $subject['subjectName'];

if ($existing) {
  jsonOut(true, 'Attendance already recorded', [
    'studentName' => $studentName,
    'admissionNumber' => $admissionNumber,
    'subjectName' => $subjectName,
    'time' => date('d-m-Y H:i:s', strtotime($now))
  ]);
}

$ins = $conn->prepare("INSERT INTO tblsubjectattendance(admissionNumber,subjectId,classId,classArmId,dateTaken,status,createdAt) VALUES(?,?,?,?,?,?,?)");
$status = '1';
$ins->bind_param('sisssss', $admissionNumber, $subjectId, $classId, $classArmId, $dateTaken, $status, $now);
if (!$ins->execute()) {
  $ins->close();
  jsonOut(false, 'Failed to save attendance.');
}
$ins->close();

jsonOut(true, 'Attendance marked successfully', [
  'studentName' => $studentName,
  'admissionNumber' => $admissionNumber,
  'subjectName' => $subjectName,
  'time' => date('d-m-Y H:i:s', strtotime($now))
]);
