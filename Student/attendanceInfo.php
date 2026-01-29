include '../Includes/dbcon.php';
include '../Includes/session.php';

// Student-specific role verification
if (!isset($_SESSION['userType']) || $_SESSION['userType'] !== 'Student') {
  header('Location: login.php');
  exit;
}

$admission = isset($_GET['adm']) ? trim($_GET['adm']) : '';
$sig = isset($_GET['sig']) ? trim($_GET['sig']) : '';

// Verify that the logged-in student is only accessing THEIR own record
$stmtCheck = $conn->prepare("SELECT admissionNumber FROM tblstudents WHERE Id = ?");
$stmtCheck->bind_param("i", $_SESSION['userId']);
$stmtCheck->execute();
$resCheck = $stmtCheck->get_result();
$rowCheck = $resCheck->fetch_assoc();
$loggedInAdm = $rowCheck['admissionNumber'] ?? '';

if ($admission !== $loggedInAdm) {
    echo "<div style='color:red; padding:20px; font-weight:bold;'>Access Denied: You are not authorized to view this record.</div>";
    exit;
}

$qrSecret = 'AMS_QR_SECRET_2025';
$expectedSig = hash_hmac('sha256', $admission, $qrSecret);
if (!hash_equals($expectedSig, $sig)) {
  http_response_code(403);
  echo 'Invalid link.';
  exit;
}

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

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbltodaysubjects (
  Id int(10) NOT NULL AUTO_INCREMENT,
  classId varchar(10) NOT NULL,
  classArmId varchar(10) NOT NULL,
  dateTaken varchar(20) NOT NULL,
  subjectId int(10) NOT NULL,
  createdAt datetime DEFAULT NULL,
  PRIMARY KEY (Id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1");

$stmt = $conn->prepare("SELECT admissionNumber, firstName, lastName, otherName, classId, classArmId FROM tblstudents WHERE admissionNumber = ? LIMIT 1");
$stmt->bind_param('s', $admission);
$stmt->execute();
$res = $stmt->get_result();
$student = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$student) {
  http_response_code(404);
  echo 'Student not found.';
  exit;
}

$studentName = trim(($student['firstName'] ?? '').' '.($student['lastName'] ?? '').' '.($student['otherName'] ?? ''));

$overallPct = 0;
$overallTotal = 0;
$overallPresent = 0;

$stmtPct = $conn->prepare("SELECT
    COUNT(ts.Id) as total,
    SUM(CASE WHEN sa.status='1' THEN 1 ELSE 0 END) as presentCount
  FROM tbltodaysubjects ts
  LEFT JOIN tblsubjectattendance sa
    ON sa.admissionNumber = ?
    AND sa.subjectId = ts.subjectId
    AND sa.classId = ts.classId
    AND sa.classArmId = ts.classArmId
    AND sa.dateTaken = ts.dateTaken
  WHERE ts.classId = ? AND ts.classArmId = ?");
$stmtPct->bind_param('sss', $admission, $student['classId'], $student['classArmId']);
$stmtPct->execute();
$resPct = $stmtPct->get_result();
$cnt = $resPct ? $resPct->fetch_assoc() : ['total' => 0, 'presentCount' => 0];
$stmtPct->close();
$overallTotal = intval($cnt['total']);
$overallPresent = intval($cnt['presentCount']);
$overallPct = ($overallTotal > 0) ? round(($overallPresent / $overallTotal) * 100, 2) : 0;

$subjectPctRows = [];
$stmtSubPct = $conn->prepare("SELECT
    subj.Id as subjectId,
    subj.subjectName as subjectName,
    COUNT(ts.Id) as totalClasses,
    SUM(CASE WHEN sa.status='1' THEN 1 ELSE 0 END) as presentClasses
  FROM tblsubjects subj
  LEFT JOIN tbltodaysubjects ts
    ON ts.subjectId = subj.Id
    AND ts.classId = subj.classId
    AND ts.classArmId = subj.classArmId
  LEFT JOIN tblsubjectattendance sa
    ON sa.admissionNumber = ?
    AND sa.subjectId = subj.Id
    AND sa.classId = subj.classId
    AND sa.classArmId = subj.classArmId
    AND sa.dateTaken = ts.dateTaken
  WHERE subj.classId = ? AND subj.classArmId = ?
  GROUP BY subj.Id, subj.subjectName
  ORDER BY subj.subjectName ASC");
$stmtSubPct->bind_param('sss', $admission, $student['classId'], $student['classArmId']);
$stmtSubPct->execute();
$resSubPct = $stmtSubPct->get_result();
if ($resSubPct) {
  while ($r = $resSubPct->fetch_assoc()) {
    $totalC = intval($r['totalClasses']);
    $presentC = intval($r['presentClasses']);
    $pct = ($totalC > 0) ? round(($presentC / $totalC) * 100, 2) : 0;
    $r['pct'] = $pct;
    $subjectPctRows[] = $r;
  }
}
$stmtSubPct->close();
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'Includes/header.php'; ?>
<body class="bg-light">
  <div class="container" style="max-width: 900px; padding-top: 30px; padding-bottom: 30px;">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <h4 class="mb-0">Attendance Details</h4>
            <small class="text-muted">Scanned from QR Code</small>
          </div>
          <div class="text-right">
            <span class="badge badge-info">Total Attendance: <?php echo htmlspecialchars($overallPct); ?>%</span>
          </div>
        </div>

        <hr>

        <div class="mb-3">
          <div><b>Student Name:</b> <?php echo htmlspecialchars($studentName); ?></div>
          <div><b>Registration Number:</b> <?php echo htmlspecialchars($admission); ?></div>
        </div>

        <h6 class="mb-2">Subject-wise Attendance</h6>
        <div class="table-responsive">
          <table class="table table-sm table-bordered mb-0">
            <thead class="thead-light">
              <tr>
                <th>Subject</th>
                <th style="width:90px; text-align:right;">Present</th>
                <th style="width:90px; text-align:right;">Total</th>
                <th style="width:110px; text-align:right;">Percentage</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($subjectPctRows) > 0) {
                foreach ($subjectPctRows as $sr) {
                  $pct = floatval($sr['pct']);
                  $cellStyle = ($pct < 75) ? "color:#b91c1c; font-weight:600;" : "font-weight:600;";
                  echo "<tr>";
                  echo "<td>".htmlspecialchars($sr['subjectName'])."</td>";
                  echo "<td style='text-align:right;'>".intval($sr['presentClasses'])."</td>";
                  echo "<td style='text-align:right;'>".intval($sr['totalClasses'])."</td>";
                  echo "<td style='text-align:right; {$cellStyle}'>".htmlspecialchars($sr['pct'])."%</td>";
                  echo "</tr>";
                }
              } else {
                echo "<tr><td colspan='4'><div class='alert alert-secondary' role='alert' style='margin:0;'>No subjects found.</div></td></tr>";
              } ?>
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </div>
</body>
</html>
