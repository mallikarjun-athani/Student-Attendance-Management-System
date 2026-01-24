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

$selectedDateLabel = '';
if (isset($_POST['view'])) {
  if (isset($_POST['dateTaken']) && $_POST['dateTaken'] !== '') {
    $selectedDateLabel = $_POST['dateTaken'];
  }
}

$dateTaken = date('Y-m-d');
$attFilter = 'all';
$grouped = [];
$totalStudents = 0;
$totalPresentStudents = 0;
$totalAbsentStudents = 0;

if (isset($_POST['view'])) {
  $dateTaken = isset($_POST['dateTaken']) ? $_POST['dateTaken'] : $dateTaken;
  $attFilter = isset($_POST['attFilter']) ? strtolower(trim($_POST['attFilter'])) : 'all';
  if ($attFilter !== 'present' && $attFilter !== 'absent' && $attFilter !== 'all') {
    $attFilter = 'all';
  }

  if ($dateTaken !== '') {
    $query = "SELECT
      s.firstName,
      s.lastName,
      s.admissionNumber,
      subj.subjectName,
      COALESCE(sa.status, '0') AS status
    FROM tblstudents s
    INNER JOIN tblsubjects subj
      ON subj.classId = s.classId
      AND subj.classArmId = s.classArmId
    LEFT JOIN tblsubjectattendance sa
      ON sa.admissionNumber = s.admissionNumber
      AND sa.subjectId = subj.Id
      AND sa.classId = '$_SESSION[classId]'
      AND sa.classArmId = '$_SESSION[classArmId]'
      AND sa.dateTaken = '$dateTaken'
    INNER JOIN tbltodaysubjects ts
      ON ts.subjectId = subj.Id
      AND ts.classId = '$_SESSION[classId]'
      AND ts.classArmId = '$_SESSION[classArmId]'
      AND ts.dateTaken = '$dateTaken'
    WHERE s.classId = '$_SESSION[classId]' AND s.classArmId = '$_SESSION[classArmId]'
    ORDER BY s.firstName ASC, s.lastName ASC, s.admissionNumber ASC, subj.subjectName ASC";

    $rs = $conn->query($query);
    $num = $rs ? $rs->num_rows : 0;
    if ($num > 0) {
      while ($rows = $rs->fetch_assoc()) {
        $adm = $rows['admissionNumber'];
        if (!isset($grouped[$adm])) {
          $grouped[$adm] = [
            'firstName' => $rows['firstName'],
            'lastName' => $rows['lastName'],
            'admissionNumber' => $adm,
            'subjects' => [],
            'overallStatus' => '0'
          ];
        }
        $grouped[$adm]['subjects'][] = [
          'subjectName' => $rows['subjectName'],
          'status' => $rows['status']
        ];
      }

      $totalStudents = count($grouped);
      foreach ($grouped as $adm => $stu) {
        $allPresent = true;
        if (!isset($stu['subjects']) || count($stu['subjects']) === 0) {
          $allPresent = false;
        } else {
          foreach ($stu['subjects'] as $subjRow) {
            if ($subjRow['status'] != '1') {
              $allPresent = false;
              break;
            }
          }
        }
        $grouped[$adm]['overallStatus'] = $allPresent ? '1' : '0';
        if ($allPresent) {
          $totalPresentStudents++;
        }
      }
      $totalAbsentStudents = $totalStudents - $totalPresentStudents;
    }
  }
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
            <h1 class="h3 mb-0 text-gray-800">Today's Report</h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">Today's Report</li>
            </ol>
          </div>

          <div class="row">
            <div class="col-lg-12">
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">View Class Attendance</h6>
                </div>
                <div class="card-body">
                  <form method="post">
                    <div class="form-group row mb-3">
                      <div class="col-xl-6">
                        <label class="form-control-label">Select Date<span class="text-danger ml-2">*</span></label>
                        <input type="date" class="form-control" name="dateTaken" value="<?php echo htmlspecialchars($dateTaken); ?>">
                      </div>
                      <div class="col-xl-6">
                        <label class="form-control-label">Attendance Status</label>
                        <select name="attFilter" class="form-control">
                          <option value="all" <?php echo ($attFilter === 'all') ? 'selected' : ''; ?>>All</option>
                          <option value="present" <?php echo ($attFilter === 'present') ? 'selected' : ''; ?>>Present</option>
                          <option value="absent" <?php echo ($attFilter === 'absent') ? 'selected' : ''; ?>>Absent</option>
                        </select>
                      </div>
                    </div>
                    <button type="submit" name="view" class="btn btn-primary">View Attendance</button>
                    <?php if ($selectedDateLabel !== '') { ?>
                      <a class="btn btn-success" href="downloadTodayReport.php?dateTaken=<?php echo urlencode($dateTaken); ?>&attFilter=<?php echo urlencode($attFilter); ?>">Download Report</a>
                    <?php } ?>
                  </form>
                </div>
              </div>

              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">Class Attendance</h6>
                  <?php if ($selectedDateLabel !== '') { ?>
                    <div class="text-right">
                      <span class="badge badge-info">Date: <?php echo htmlspecialchars(date('d-m-Y', strtotime($selectedDateLabel))); ?></span>
                    </div>
                  <?php } ?>
                </div>

                <?php if ($selectedDateLabel !== '') { ?>
                  <div class="card-body" style="padding-bottom: 0;">
                    <div class="row">
                      <div class="col-md-4 mb-3">
                        <div class="card border-left-success shadow-sm">
                          <div class="card-body">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Present Students</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo intval($totalPresentStudents); ?></div>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-4 mb-3">
                        <div class="card border-left-danger shadow-sm">
                          <div class="card-body">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total Absent Students</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo intval($totalAbsentStudents); ?></div>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-4 mb-3">
                        <div class="card border-left-primary shadow-sm">
                          <div class="card-body">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Students</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo intval($totalStudents); ?></div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php } ?>

                <div class="table-responsive p-3">
                  <table class="table align-items-center table-flush table-hover" id="dataTableHover">
                    <thead class="thead-light">
                      <tr>
                        <th>#</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Admission No</th>
                        <th>Subject Name</th>
                        <th style="width:80px;">Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                        if(isset($_POST['view'])){
                          if (count($grouped) > 0) {
                            $displaySn = 0;
                            foreach ($grouped as $stu) {
                              $overall = isset($stu['overallStatus']) ? $stu['overallStatus'] : '0';
                              if ($attFilter === 'present' && $overall != '1') {
                                continue;
                              }
                              if ($attFilter === 'absent' && $overall == '1') {
                                continue;
                              }

                              $displaySn++;
                              $bg = ($displaySn % 2 === 0) ? '#f8f9fc' : '#eef6ff';
                              $rowspan = count($stu['subjects']);
                              if ($rowspan < 1) { $rowspan = 1; }

                              foreach ($stu['subjects'] as $idx => $subjRow) {
                                $statusIcon = ($subjRow['status'] == '1') ? "<span class='text-success' style='font-weight:bold;'>&#10004;</span>" : "<span class='text-danger' style='font-weight:bold;'>&#10006;</span>";
                                echo "<tr style='background-color:".$bg.";'>";

                                if ($idx === 0) {
                                  echo "<td rowspan='".$rowspan."' style='vertical-align: middle; background-color:".$bg.";'>".$displaySn."</td>";
                                  echo "<td rowspan='".$rowspan."' style='vertical-align: middle; background-color:".$bg.";'>".$stu['firstName']."</td>";
                                  echo "<td rowspan='".$rowspan."' style='vertical-align: middle; background-color:".$bg.";'>".$stu['lastName']."</td>";
                                  echo "<td rowspan='".$rowspan."' style='vertical-align: middle; background-color:".$bg.";'>".$stu['admissionNumber']."</td>";
                                }

                                echo "<td>".$subjRow['subjectName']."</td>";
                                echo "<td style='text-align:center; width:80px;'>".$statusIcon."</td>";
                                echo "</tr>";
                              }
                            }

                            if ($displaySn === 0) {
                              echo "<tr><td colspan='6'><div class='alert alert-danger' role='alert'>No Record Found!</div></td></tr>";
                            }
                          } else {
                            echo "<tr><td colspan='6'><div class='alert alert-danger' role='alert'>No Record Found!</div></td></tr>";
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
  <script src="../vendor/datatables/jquery.dataTables.min.js"></script>
  <script src="../vendor/datatables/dataTables.bootstrap4.min.js"></script>

  <script>
    $(document).ready(function () {
      $('#dataTableHover').DataTable();
    });
  </script>
</body>

</html>
