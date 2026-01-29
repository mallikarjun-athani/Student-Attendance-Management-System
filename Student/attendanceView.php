include '../Includes/dbcon.php';
include '../Includes/session.php';

// Student-specific role verification
if (!isset($_SESSION['userType']) || $_SESSION['userType'] !== 'Student') {
  header('Location: login.php');
  exit;
}

$currentPage = basename($_SERVER['PHP_SELF']);

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

$studentId = intval($_SESSION['studentId']);

$stmt = $conn->prepare("SELECT admissionNumber, firstName, lastName, otherName, classId, classArmId FROM tblstudents WHERE Id = ? LIMIT 1");
$stmt->bind_param('i', $studentId);
$stmt->execute();
$res = $stmt->get_result();
$student = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$student) {
  header('Location: index.php');
  exit;
}

$admissionNumber = trim($student['admissionNumber'] ?? '');
$studentName = trim(($student['firstName'] ?? '').' '.($student['lastName'] ?? '').' '.($student['otherName'] ?? ''));

$selectedDate = '';
if (isset($_GET['date']) && $_GET['date'] !== '') {
  $selectedDate = trim($_GET['date']);
}

$overallPct = 0;
$overallTotal = 0;
$overallPresent = 0;
if ($admissionNumber !== '') {
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
  $stmtPct->bind_param('sss', $admissionNumber, $student['classId'], $student['classArmId']);
  $stmtPct->execute();
  $resPct = $stmtPct->get_result();
  $cnt = $resPct ? $resPct->fetch_assoc() : ['total' => 0, 'presentCount' => 0];
  $stmtPct->close();
  $overallTotal = intval($cnt['total']);
  $overallPresent = intval($cnt['presentCount']);
  $overallPct = ($overallTotal > 0) ? round(($overallPresent / $overallTotal) * 100, 2) : 0;
}

$subjectPctRows = [];
if ($admissionNumber !== '') {
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
  $stmtSubPct->bind_param('sss', $admissionNumber, $student['classId'], $student['classArmId']);
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
}

$rows = [];
if ($admissionNumber !== '') {
  if ($selectedDate !== '') {
    $stmt2 = $conn->prepare("SELECT sa.dateTaken, sa.status, subj.subjectName
      FROM tblsubjectattendance sa
      INNER JOIN tblsubjects subj ON subj.Id = sa.subjectId
      WHERE sa.admissionNumber = ? AND sa.classId = ? AND sa.classArmId = ? AND sa.dateTaken = ?
      ORDER BY sa.dateTaken DESC, subj.subjectName ASC");
    $stmt2->bind_param('ssss', $admissionNumber, $student['classId'], $student['classArmId'], $selectedDate);
  } else {
    $stmt2 = $conn->prepare("SELECT sa.dateTaken, sa.status, subj.subjectName
      FROM tblsubjectattendance sa
      INNER JOIN tblsubjects subj ON subj.Id = sa.subjectId
      WHERE sa.admissionNumber = ? AND sa.classId = ? AND sa.classArmId = ?
      ORDER BY sa.dateTaken DESC, subj.subjectName ASC");
    $stmt2->bind_param('sss', $admissionNumber, $student['classId'], $student['classArmId']);
  }
  $stmt2->execute();
  $res2 = $stmt2->get_result();
  if ($res2) {
    while ($r = $res2->fetch_assoc()) {
      $rows[] = $r;
    }
  }
  $stmt2->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'Includes/header.php'; ?>
<body id="page-top" class="animate-fade-up">
  <div id="wrapper">
    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">
        <!-- Topbar -->
        <nav class="navbar navbar-expand navbar-light topbar mb-4 static-top shadow-sm" style="background: rgba(255,255,255,0.8); backdrop-filter: blur(10px);">
          <div class="container-fluid">
            <div class="d-flex align-items-center">
              <a href="index.php" class="btn btn-link text-primary mr-3" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: rgba(99, 102, 241, 0.1); border-radius: 8px;"><i class="fas fa-arrow-left"></i></a>
              <div class="d-flex flex-column justify-content-center">
                <h4 class="font-weight-bold mb-0" style="color: var(--text-primary); font-size: 1rem; letter-spacing: -0.5px; line-height: 1.2;">My <span style="background: var(--primary-gradient); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;">Attendance</span></h4>
              </div>
            </div>
            <div class="ml-auto d-flex align-items-center">
              <span class="badge-pro bg-primary text-white shadow-sm mr-3" style="background: var(--primary-gradient) !important;">Overall: <?php echo htmlspecialchars($overallPct); ?>%</span>
              <a href="logout.php" class="btn btn-outline-danger btn-sm d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; border-radius: 8px; transition: all 0.3s ease;">
                <i class="fas fa-power-off" style="font-size: 0.75rem;"></i>
              </a>
            </div>
          </div>
        </nav>

        <div class="container-fluid" id="container-wrapper">
          <div class="row">
            <!-- Subject-wise Summary -->
            <div class="col-lg-12">
              <div class="card border-0 shadow-sm mb-4" style="border-radius: 20px;">
                <div class="card-header bg-white py-4">
                  <h5 class="m-0 font-weight-bold text-dark"><i class="fas fa-th-list mr-2 text-primary"></i> Subject-wise Analysis</h5>
                </div>
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table table-borderless align-items-center">
                      <thead class="text-muted small text-uppercase font-weight-bold" style="background: #f8fafc;">
                        <tr>
                          <th class="py-3 px-4" style="border-radius: 12px 0 0 12px;">Subject</th>
                          <th class="py-3 text-center">Present</th>
                          <th class="py-3 text-center">Total</th>
                          <th class="py-3 text-right px-4" style="border-radius: 0 12px 12px 0;">Percentage</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php if (count($subjectPctRows) > 0) {
                          foreach ($subjectPctRows as $sr) {
                            $pct = floatval($sr['pct']);
                            $pctClass = ($pct < 75) ? "bg-soft-red" : "bg-soft-green";
                            $pctText = ($pct < 75) ? "text-danger" : "text-success";
                            echo "<tr class='border-bottom'>";
                            echo "<td class='py-4 px-4 font-weight-bold text-dark'>".htmlspecialchars($sr['subjectName'])."</td>";
                            echo "<td class='text-center py-4'><span class='h6 mb-0 font-weight-bold'>".intval($sr['presentClasses'])."</span></td>";
                            echo "<td class='text-center py-4'><span class='h6 mb-0 text-muted'>".intval($sr['totalClasses'])."</span></td>";
                            echo "<td class='text-right py-4 px-4'>
                                    <div class='d-inline-flex align-items-center justify-content-end'>
                                      <span class='font-weight-bold {$pctText} h5 mb-0 mr-2'>".htmlspecialchars($sr['pct'])."%</span>
                                      <div class='progress' style='width: 60px; height: 6px; border-radius: 10px; background: #eaecf0;'>
                                        <div class='progress-bar ".($pct < 75 ? 'bg-danger' : 'bg-success')."' style='width: {$pct}%'></div>
                                      </div>
                                    </div>
                                  </td>";
                            echo "</tr>";
                          }
                        } else {
                          echo "<tr><td colspan='4' class='text-center py-5'><p class='text-muted mb-0'>No data available yet.</p></td></tr>";
                        } ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>

            <!-- Detailed Log with Filter -->
            <div class="col-lg-12">
              <div class="card border-0 shadow-sm" style="border-radius: 20px;">
                <div class="card-header bg-white py-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
                  <h5 class="m-0 font-weight-bold text-dark"><i class="fas fa-history mr-2 text-primary"></i> Detailed Attendance Log</h5>
                  
                  <form method="get" class="d-flex align-items-center" style="gap:10px;">
                    <input type="date" class="form-control form-control-sm border-0 bg-light" name="date" value="<?php echo htmlspecialchars($selectedDate); ?>" style="border-radius: 8px; width: 160px;">
                    <button type="submit" class="btn btn-primary btn-sm px-3">Filter</button>
                    <?php if($selectedDate != '') { ?>
                        <a href="attendanceView.php" class="btn btn-light btn-sm px-3">Clear</a>
                    <?php } ?>
                  </form>
                </div>
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table align-items-center table-flush">
                      <thead class="thead-light">
                        <tr>
                          <th class="border-0">#</th>
                          <th class="border-0 text-center">Status</th>
                          <th class="border-0">Subject</th>
                          <th class="border-0 text-right">Date</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php
                        $sn = 0;
                        if (count($rows) > 0) {
                          foreach ($rows as $r) {
                            $sn++;
                            $isPresent = ($r['status'] == '1');
                            echo "<tr>";
                            echo "<td>{$sn}</td>";
                            echo "<td class='text-center'>
                                    <span class='badge-pro ".($isPresent ? 'bg-soft-green text-success' : 'bg-soft-red text-danger')."' style='background:".($isPresent ? 'rgba(16,185,129,0.1)' : 'rgba(239,68,68,0.1)')." !important; padding: 4px 12px !important;'>
                                      ".($isPresent ? 'Present' : 'Absent')."
                                    </span>
                                  </td>";
                            echo "<td class='font-weight-bold text-dark'>".htmlspecialchars($r['subjectName'])."</td>";
                            echo "<td class='text-right text-muted'>".htmlspecialchars(date('d M, Y', strtotime($r['dateTaken'])))."</td>";
                            echo "</tr>";
                          }
                        } else {
                          echo "<tr><td colspan='4' class='text-center py-5'><i class='fas fa-folder-open fa-3x text-light mb-3 d-block'></i><p class='text-muted'>No attendance records found for this period.</p></td></tr>";
                        }
                        ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Pro Mobile Bottom Navigation -->
  <nav class="mobile-nav">
    <a href="index.php" class="mobile-nav-item <?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">
      <i class="fas fa-home"></i>
      <span>Home</span>
    </a>
    <a href="attendanceQr.php" class="mobile-nav-item <?php echo ($currentPage == 'attendanceQr.php') ? 'active' : ''; ?>">
      <i class="fas fa-qrcode"></i>
      <span>Scan</span>
    </a>
    <a href="attendanceView.php" class="mobile-nav-item <?php echo ($currentPage == 'attendanceView.php') ? 'active' : ''; ?>">
      <i class="fas fa-chart-line"></i>
      <span>Status</span>
    </a>
    <a href="qr.php" class="mobile-nav-item <?php echo ($currentPage == 'qr.php') ? 'active' : ''; ?>">
      <i class="fas fa-id-card"></i>
      <span>My ID</span>
    </a>
  </nav>

  <script src="../vendor/jquery/jquery.min.js"></script>
  <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
  <script src="../js/ruang-admin.min.js"></script>
</body>
</html>
