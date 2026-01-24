<?php
error_reporting(0);
include '../Includes/dbcon.php';
include '../Includes/session.php';

header('Content-Type: application/json');

echo json_encode([
  'ok' => false,
  'message' => 'Teacher QR attendance is disabled. Use Student QR Scan only.'
]);
exit;

date_default_timezone_set('Asia/Kolkata');
@mysqli_query($conn, "SET time_zone = '+05:30'");

// Ensure tblstudents.photo exists (older DB dumps may not include it)
$photoCol = @mysqli_query($conn, "SHOW COLUMNS FROM tblstudents LIKE 'photo'");
if (!$photoCol || mysqli_num_rows($photoCol) === 0) {
  @mysqli_query($conn, "ALTER TABLE tblstudents ADD COLUMN photo VARCHAR(255) NULL AFTER dateCreated");
}

// Ensure required tables exist (keeps feature working even if DB was imported from older SQL dump)
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tblqrattendance (
  Id int(10) NOT NULL AUTO_INCREMENT,
  admissionNumber varchar(255) NOT NULL,
  classId varchar(10) NOT NULL,
  classArmId varchar(10) NOT NULL,
  dateTaken varchar(20) NOT NULL,
  checkInAt datetime DEFAULT NULL,
  checkOutAt datetime DEFAULT NULL,
  createdAt datetime DEFAULT NULL,
  updatedAt datetime DEFAULT NULL,
  PRIMARY KEY (Id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tblqrsettings (
  Id int(10) NOT NULL AUTO_INCREMENT,
  classId varchar(10) NOT NULL,
  classArmId varchar(10) NOT NULL,
  minCheckoutGapMinutes int(10) NOT NULL DEFAULT 30,
  createdAt datetime DEFAULT NULL,
  PRIMARY KEY (Id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1");

function jsonOut($ok, $message, $data = null) {
  echo json_encode([
    'ok' => $ok,
    'message' => $message,
    'data' => $data
  ]);
  exit;
}

if (!isset($_POST['qrText'])) {
  jsonOut(false, 'Missing QR data.');
}

$qrText = trim($_POST['qrText']);
$minGap = isset($_POST['minGap']) ? intval($_POST['minGap']) : 30;
if ($minGap < 1) $minGap = 30;

$qrSecret = 'AMS_QR_SECRET_2025';

$parts = array_map('trim', explode('|', $qrText));
$parts = array_values(array_filter($parts, function($p) { return $p !== ''; }));
if (count($parts) < 3 || $parts[0] !== 'AMS') {
  jsonOut(false, 'Invalid QR code.');
}

$admissionNumber = trim($parts[1]);
$sig = trim($parts[2]);

$expectedSig = hash_hmac('sha256', $admissionNumber, $qrSecret);
if (!hash_equals($expectedSig, $sig)) {
  jsonOut(false, 'QR signature mismatch.');
}

// Find student in teacher class
$stmt = $conn->prepare("SELECT Id, firstName, lastName, otherName, admissionNumber, photo FROM tblstudents WHERE admissionNumber = ? AND classId = ? AND classArmId = ? LIMIT 1");
$stmt->bind_param('sss', $admissionNumber, $_SESSION['classId'], $_SESSION['classArmId']);
$stmt->execute();
$res = $stmt->get_result();
$student = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$student) {
  jsonOut(false, 'Student not found for this class/semester.');
}

$dateTaken = date('Y-m-d');
$now = date('Y-m-d H:i:s');

// Ensure base attendance table has a row for today (optional)
$chk = $conn->prepare("SELECT Id FROM tblattendance WHERE admissionNo = ? AND classId = ? AND classArmId = ? AND dateTimeTaken = ? LIMIT 1");
$chk->bind_param('ssss', $admissionNumber, $_SESSION['classId'], $_SESSION['classArmId'], $dateTaken);
$chk->execute();
$chkRes = $chk->get_result();
$attRow = $chkRes ? $chkRes->fetch_assoc() : null;
$chk->close();

if (!$attRow) {
  // use latest sessiondivision as in takeAttendance.php
  $q = mysqli_query($conn, "select * from tblsessiondivision ORDER BY Id DESC LIMIT 1");
  $rwws = mysqli_fetch_array($q);
  $sessionTermId = isset($rwws['Id']) ? $rwws['Id'] : 0;

  $ins = $conn->prepare("INSERT INTO tblattendance(admissionNo,classId,classArmId,sessiondivisionId,status,dateTimeTaken) VALUES(?,?,?,?,?,?)");
  $status = '0';
  $ins->bind_param('ssssss', $admissionNumber, $_SESSION['classId'], $_SESSION['classArmId'], $sessionTermId, $status, $dateTaken);
  $ins->execute();
  $ins->close();
}

// Ensure qr settings row exists
$settings = $conn->prepare("SELECT Id, minCheckoutGapMinutes FROM tblqrsettings WHERE classId = ? AND classArmId = ? LIMIT 1");
$settings->bind_param('ss', $_SESSION['classId'], $_SESSION['classArmId']);
$settings->execute();
$sres = $settings->get_result();
$srow = $sres ? $sres->fetch_assoc() : null;
$settings->close();

if (!$srow) {
  $insSet = $conn->prepare("INSERT INTO tblqrsettings(classId,classArmId,minCheckoutGapMinutes,createdAt) VALUES(?,?,?,?)");
  $insSet->bind_param('ssis', $_SESSION['classId'], $_SESSION['classArmId'], $minGap, $now);
  $insSet->execute();
  $insSet->close();
} else {
  // update teacher-set gap (latest value)
  $updSet = $conn->prepare("UPDATE tblqrsettings SET minCheckoutGapMinutes = ? WHERE Id = ?");
  $updSet->bind_param('ii', $minGap, $srow['Id']);
  $updSet->execute();
  $updSet->close();
}

// Get today's QR attendance row
$q1 = $conn->prepare("SELECT Id, checkInAt, checkOutAt FROM tblqrattendance WHERE admissionNumber = ? AND classId = ? AND classArmId = ? AND dateTaken = ? LIMIT 1");
$q1->bind_param('ssss', $admissionNumber, $_SESSION['classId'], $_SESSION['classArmId'], $dateTaken);
$q1->execute();
$q1res = $q1->get_result();
$qrRow = $q1res ? $q1res->fetch_assoc() : null;
$q1->close();

$action = '';
$message = '';

if (!$qrRow) {
  // first scan => check-in
  $insQr = $conn->prepare("INSERT INTO tblqrattendance(admissionNumber,classId,classArmId,dateTaken,checkInAt,checkOutAt,createdAt,updatedAt) VALUES(?,?,?,?,?,?,?,?)");
  $checkOutAt = null;
  $insQr->bind_param('ssssssss', $admissionNumber, $_SESSION['classId'], $_SESSION['classArmId'], $dateTaken, $now, $checkOutAt, $now, $now);
  $insQr->execute();
  $insQr->close();

  // mark present in tblattendance
  $upd = $conn->prepare("UPDATE tblattendance SET status='1' WHERE admissionNo = ? AND classId = ? AND classArmId = ? AND dateTimeTaken = ?");
  $upd->bind_param('ssss', $admissionNumber, $_SESSION['classId'], $_SESSION['classArmId'], $dateTaken);
  $upd->execute();
  $upd->close();

  $action = 'Check-In';
  $message = 'Check-In recorded.';
} else {
  if (!empty($qrRow['checkOutAt'])) {
    $action = 'Already Checked-Out';
    $message = 'Student has already checked-out today.';
  } else {
    $checkInTs = strtotime($qrRow['checkInAt']);
    $nowTs = strtotime($now);
    $diffMin = ($nowTs - $checkInTs) / 60;

    if ($diffMin < $minGap) {
      $action = 'Too Early';
      $message = 'Check-Out not allowed yet. Try again later.';
    } else {
      $updQr = $conn->prepare("UPDATE tblqrattendance SET checkOutAt = ?, updatedAt = ? WHERE Id = ?");
      $updQr->bind_param('ssi', $now, $now, $qrRow['Id']);
      $updQr->execute();
      $updQr->close();

      $action = 'Check-Out';
      $message = 'Check-Out recorded.';
    }
  }
}

$studentName = trim($student['firstName'].' '.$student['lastName'].' '.$student['otherName']);

$photoUrl = 'img/user-icn.png';
if (isset($student['photo']) && trim($student['photo']) !== '') {
  $photoUrl = '../' . ltrim(trim($student['photo']), '/');
}

jsonOut(true, $message, [
  'studentName' => $studentName,
  'admissionNumber' => $admissionNumber,
  'action' => $action,
  'time' => date('d-m-Y H:i:s', strtotime($now)),
  'photoUrl' => $photoUrl
]);
