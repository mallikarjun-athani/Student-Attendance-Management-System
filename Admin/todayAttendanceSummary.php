<?php 
error_reporting(0);
include '../Includes/dbcon.php';
include '../Includes/session.php';

$selectedDate = isset($_GET['date']) ? trim($_GET['date']) : '';
if ($selectedDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
  $selectedDate = date('Y-m-d');
}

$selectedClassId = isset($_GET['classId']) ? trim($_GET['classId']) : '';
if ($selectedClassId !== '' && !ctype_digit($selectedClassId)) {
  $selectedClassId = '';
}

$classes = [];
$rsC = $conn->query("SELECT Id, className FROM tblclass ORDER BY className ASC");
if ($rsC) {
  while ($cr = $rsC->fetch_assoc()) {
    $classes[] = $cr;
  }
}


$dateEsc = $conn->real_escape_string($selectedDate);
$classEsc = $conn->real_escape_string($selectedClassId);
$whereStudents = " WHERE 1=1 ";
if ($selectedClassId !== '') {
  $whereStudents .= " AND s.classId = '".$classEsc."' ";
}

$totals = [
  'totalStudents' => 0,
  'totalPresent' => 0,
  'totalAbsent' => 0,
];

$sumQ = "SELECT
            COUNT(*) as totalCount,
            SUM(CASE WHEN p.presentFlag = 1 THEN 1 ELSE 0 END) as presentCount
          FROM tblstudents s
          LEFT JOIN (
            SELECT sa.admissionNumber, 1 as presentFlag
            FROM tblsubjectattendance sa
            WHERE sa.dateTaken = '".$dateEsc."' AND sa.status = '1'
            GROUP BY sa.admissionNumber
          ) p ON p.admissionNumber = s.admissionNumber
          ".$whereStudents;
$sumRs = $conn->query($sumQ);
if ($sumRs && $sumRs->num_rows > 0) {
  $row = $sumRs->fetch_assoc();
  $totals['totalStudents'] = isset($row['totalCount']) ? intval($row['totalCount']) : 0;
  $totals['totalPresent'] = isset($row['presentCount']) ? intval($row['presentCount']) : 0;
  $totals['totalAbsent'] = $totals['totalStudents'] - $totals['totalPresent'];
}

$students = [];
$listQ = "SELECT
            s.admissionNumber as admissionNo,
            COALESCE(p.presentFlag, 0) as status,
            s.firstName, s.lastName, s.otherName,
            c.className
          FROM tblstudents s
          LEFT JOIN tblclass c ON c.Id = s.classId
          LEFT JOIN (
            SELECT sa.admissionNumber, 1 as presentFlag
            FROM tblsubjectattendance sa
            WHERE sa.dateTaken = '".$dateEsc."' AND sa.status = '1'
            GROUP BY sa.admissionNumber
          ) p ON p.admissionNumber = s.admissionNumber
          ".$whereStudents.
          " ORDER BY c.className ASC, s.firstName ASC, s.lastName ASC, s.admissionNumber ASC";
$listRs = $conn->query($listQ);
if ($listRs) {
  while ($r = $listRs->fetch_assoc()) {
    $students[] = $r;
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
            <h1 class="h3 mb-0 text-gray-800">Today Summary</h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">Today Summary</li>
            </ol>
          </div>

          <div class="row mb-3">
            <div class="col-lg-12 d-flex align-items-center justify-content-between" style="gap:12px; flex-wrap:wrap;">
              <form method="get" class="form-inline" style="gap:10px; flex-wrap:wrap;">
                <label for="date" class="mr-2" style="margin-bottom:0;">Date:</label>
                <input type="date" name="date" id="date" class="form-control" value="<?php echo htmlspecialchars($selectedDate, ENT_QUOTES); ?>" onchange="this.form.submit()">

                <label for="classId" class="mr-2" style="margin-bottom:0;">Class:</label>
                <select name="classId" id="classId" class="form-control" onchange="this.form.submit()">
                  <option value="">All Classes</option>
                  <?php foreach ($classes as $c) {
                    $cid = strval($c['Id']);
                    $sel = ($selectedClassId !== '' && $selectedClassId === $cid) ? 'selected' : '';
                    echo "<option value=\"".htmlspecialchars($cid, ENT_QUOTES)."\" ".$sel.">".htmlspecialchars($c['className'])."</option>";
                  } ?>
                </select>
              </form>
            </div>
          </div>

          <div class="row">
            <div class="col-lg-12">
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">Summary</h6>
                </div>
                <div class="card-body" style="padding-bottom:0;">
                  <div class="row">
                    <div class="col-md-4 mb-3">
                      <div class="card border-left-primary shadow-sm">
                        <div class="card-body">
                          <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Students</div>
                          <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo intval($totals['totalStudents']); ?></div>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-4 mb-3">
                      <div class="card border-left-success shadow-sm">
                        <div class="card-body">
                          <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Present</div>
                          <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo intval($totals['totalPresent']); ?></div>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-4 mb-3">
                      <div class="card border-left-danger shadow-sm">
                        <div class="card-body">
                          <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total Absent</div>
                          <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo intval($totals['totalAbsent']); ?></div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="table-responsive table-cards p-3">
                  <table class="table align-items-center table-flush table-hover" id="dataTableHover">
                    <thead class="thead-light">
                      <tr>
                        <th>#</th>
                        <th>Student Name</th>
                        <th>Registration Number</th>
                        <th>Class</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                        if (count($students) > 0) {
                          $sn = 0;
                          foreach ($students as $stu) {
                            $sn++;
                            $name = trim(($stu['firstName'] ?? '').' '.($stu['lastName'] ?? '').' '.($stu['otherName'] ?? ''));
                            $isPresent = (isset($stu['status']) && strval($stu['status']) === '1');
                            echo "<tr>";
                            echo "<td data-label=\"#\">".$sn."</td>";
                            echo "<td data-label=\"Student Name\">".htmlspecialchars($name)."</td>";
                            echo "<td data-label=\"Registration Number\">".htmlspecialchars($stu['admissionNo'] ?? '')."</td>";
                            echo "<td data-label=\"Class\">".htmlspecialchars($stu['className'] ?? '')."</td>";
                            echo "<td data-label=\"Status\">".($isPresent ? '<span class=\"badge badge-success\">Present</span>' : '<span class=\"badge badge-danger\">Absent</span>')."</td>";
                            echo "</tr>";
                          }
                        } else {
                          echo "<tr><td colspan='5'><div class='alert alert-danger' role='alert' style='margin:0;'>No Record Found!</div></td></tr>";
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

      <?php include 'Includes/footer.php';?>
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
      $('#dataTableHover').DataTable({
        pageLength: 10,
        order: [[1, 'asc']]
      });
    });
  </script>
</body>

</html>
