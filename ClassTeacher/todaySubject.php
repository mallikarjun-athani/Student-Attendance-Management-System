<?php 
error_reporting(0);
include '../Includes/dbcon.php';
include '../Includes/session.php';

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

$statusMsg = '';
$today = date('Y-m-d');

// Load subjects for teacher class/semester
$subjects = [];
$rs = $conn->query("SELECT Id, subjectName FROM tblsubjects WHERE classId = '".$_SESSION['classId']."' AND classArmId = '".$_SESSION['classArmId']."' ORDER BY subjectName ASC");
if ($rs) {
  while ($r = $rs->fetch_assoc()) {
    $subjects[] = $r;
  }
}

// Load today's selected subjectIds
$selectedIds = [];
$stmtSel = $conn->prepare("SELECT subjectId FROM tbltodaysubjects WHERE classId = ? AND classArmId = ? AND dateTaken = ?");
$stmtSel->bind_param('sss', $_SESSION['classId'], $_SESSION['classArmId'], $today);
$stmtSel->execute();
$resSel = $stmtSel->get_result();
if ($resSel) {
  while ($r = $resSel->fetch_assoc()) {
    $selectedIds[] = intval($r['subjectId']);
  }
}
$stmtSel->close();

$isEditMode = isset($_GET['edit']) && $_GET['edit'] === '1';

if (isset($_POST['saveTodaySubjects'])) {
  $picked = isset($_POST['subjectIds']) && is_array($_POST['subjectIds']) ? $_POST['subjectIds'] : [];
  $picked = array_values(array_filter(array_map('intval', $picked), function($v){ return $v > 0; }));

  // Replace today's selection atomically-ish (MyISAM -> best effort)
  $stmtDel = $conn->prepare("DELETE FROM tbltodaysubjects WHERE classId = ? AND classArmId = ? AND dateTaken = ?");
  $stmtDel->bind_param('sss', $_SESSION['classId'], $_SESSION['classArmId'], $today);
  $stmtDel->execute();
  $stmtDel->close();

  if (count($picked) > 0) {
    $now = date('Y-m-d H:i:s');
    $stmtIns = $conn->prepare("INSERT INTO tbltodaysubjects(classId,classArmId,dateTaken,subjectId,createdAt) VALUES(?,?,?,?,?)");
    foreach ($picked as $sid) {
      $stmtIns->bind_param('sssis', $_SESSION['classId'], $_SESSION['classArmId'], $today, $sid, $now);
      $stmtIns->execute();
    }
    $stmtIns->close();
  }

  $statusMsg = "<div class='alert alert-success' data-autohide='1'>Saved Successfully</div>";

  // reload selected ids for display
  $selectedIds = [];
  $stmtSel = $conn->prepare("SELECT subjectId FROM tbltodaysubjects WHERE classId = ? AND classArmId = ? AND dateTaken = ?");
  $stmtSel->bind_param('sss', $_SESSION['classId'], $_SESSION['classArmId'], $today);
  $stmtSel->execute();
  $resSel = $stmtSel->get_result();
  if ($resSel) {
    while ($r = $resSel->fetch_assoc()) {
      $selectedIds[] = intval($r['subjectId']);
    }
  }
  $stmtSel->close();

  $isEditMode = false;
}

// Build selected subject names in order
$selectedSubjects = [];
if (count($selectedIds) > 0) {
  $map = [];
  foreach ($subjects as $s) {
    $map[intval($s['Id'])] = $s['subjectName'];
  }
  foreach ($selectedIds as $sid) {
    if (isset($map[$sid])) {
      $selectedSubjects[] = ['Id' => $sid, 'subjectName' => $map[$sid]];
    }
  }
  usort($selectedSubjects, function($a, $b) {
    return strcasecmp($a['subjectName'], $b['subjectName']);
  });
}
?>

<!DOCTYPE html>
<html lang="en">

<?php include 'Includes/header.php';?>


<body id="page-top">
  <div id="wrapper">
    <?php include "Includes/sidebar.php";?>
    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">
        <?php include "Includes/topbar.php";?>

        <div class="container-fluid" id="container-wrapper">
          <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Today Subject</h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">Today Subject</li>
            </ol>
          </div>

          <div class="row">
            <div class="col-lg-12">
              <div class="card mb-4" id="todaySubjectForm">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">Select Subjects for Today</h6>
                  <?php echo $statusMsg; ?>
                </div>
                <div class="card-body">
                  <?php if (count($subjects) === 0) { ?>
                    <div class="alert alert-warning" role="alert">No subjects found. Please create subjects first.</div>
                  <?php } else { ?>
                    <form method="post">
                      <div class="row">
                        <?php foreach ($subjects as $s) {
                          $sid = intval($s['Id']);
                          $checked = in_array($sid, $selectedIds, true) ? 'checked' : '';
                          echo "<div class='col-md-4 mb-2'>
                            <div class='custom-control custom-checkbox'>
                              <input type='checkbox' class='custom-control-input' id='subj_{$sid}' name='subjectIds[]' value='{$sid}' {$checked}>
                              <label class='custom-control-label' for='subj_{$sid}'>".htmlspecialchars($s['subjectName'])."</label>
                            </div>
                          </div>";
                        } ?>
                      </div>

                      <div class="mt-3">
                        <button type="submit" name="saveTodaySubjects" class="btn btn-primary">Save</button>
                      </div>
                    </form>
                  <?php } ?>
                </div>
              </div>

              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">Today's Selected Subjects</h6>
                  <span class="badge badge-info">Date: <?php echo htmlspecialchars(date('d-m-Y', strtotime($today))); ?></span>
                </div>
                <div class="table-responsive p-3">
                  <table class="table align-items-center table-flush table-hover">
                    <thead class="thead-light">
                      <tr>
                        <th style="width:60px;">#</th>
                        <th>Subject Name</th>
                        <th style="width:140px;">Date</th>
                        <th style="width:80px;">Edit</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                        if (count($selectedSubjects) === 0) {
                          echo "<tr><td colspan='4'><div class='alert alert-secondary' role='alert'>No subjects selected for today.</div></td></tr>";
                        } else {
                          $rowspan = count($selectedSubjects);
                          $sn = 0;
                          foreach ($selectedSubjects as $idx => $s) {
                            $sn++;
                            echo "<tr>";
                            echo "<td>{$sn}</td>";
                            echo "<td>".htmlspecialchars($s['subjectName'])."</td>";

                            if ($idx === 0) {
                              echo "<td rowspan='{$rowspan}' style='vertical-align: middle;'>".htmlspecialchars(date('d-m-Y', strtotime($today)))."</td>";
                              echo "<td rowspan='{$rowspan}' style='vertical-align: middle;'>
                                <a class='btn btn-sm btn-outline-primary' href='todaySubject.php?edit=1#todaySubjectForm' title='Edit'><i class='fas fa-edit'></i></a>
                              </td>";
                            }

                            echo "</tr>";
                          }
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
      <?php include "Includes/footer.php";?>
    </div>
  </div>

  <a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
  </a>

  <script src="../vendor/jquery/jquery.min.js"></script>
  <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
  <script src="js/ruang-admin.min.js"></script>

  <script>
    $(document).ready(function () {
      var $msg = $('[data-autohide="1"]');
      if ($msg.length) {
        setTimeout(function(){
          $msg.fadeOut('fast');
        }, 3000);
      }
    });
  </script>
</body>

</html>
