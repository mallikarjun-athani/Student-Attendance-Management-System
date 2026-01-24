<?php 
error_reporting(0);
include '../Includes/dbcon.php';
include '../Includes/session.php';

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

$byClass = [];

$q = "SELECT 
        c.Id as classId,
        c.className as className,
        s.Id as studentId,
        s.firstName,
        s.lastName,
        s.otherName,
        s.admissionNumber,
        COUNT(a.Id) as totalAttendance,
        SUM(CASE WHEN a.status = '1' THEN 1 ELSE 0 END) as presentAttendance
      FROM tblstudents s
      INNER JOIN tblclass c ON c.Id = s.classId
      LEFT JOIN tblattendance a ON a.admissionNo = s.admissionNumber
      ".($selectedClassId !== '' ? " WHERE c.Id = '".$conn->real_escape_string($selectedClassId)."'" : "")."
      GROUP BY c.Id, c.className, s.Id, s.firstName, s.lastName, s.otherName, s.admissionNumber
      ORDER BY c.className ASC, s.firstName ASC, s.lastName ASC";

$rs = $conn->query($q);
if ($rs) {
  while ($r = $rs->fetch_assoc()) {
    $cid = strval($r['classId']);
    if (!isset($byClass[$cid])) {
      $byClass[$cid] = [
        'className' => $r['className'],
        'students' => [],
        'totalStudents' => 0
      ];
    }

    $byClass[$cid]['students'][] = $r;
    $byClass[$cid]['totalStudents'] = $byClass[$cid]['totalStudents'] + 1;
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
            <h1 class="h3 mb-0 text-gray-800">Student Attendance Summary</h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">Student Attendance Summary</li>
            </ol>
          </div>

          <div class="row mb-3">
            <div class="col-lg-12 d-flex align-items-center justify-content-between" style="gap:12px; flex-wrap:wrap;">
              <form method="get" class="form-inline" style="gap:10px;">
                <label for="classId" class="mr-2" style="margin-bottom:0;">Filter:</label>
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
                  <h6 class="m-0 font-weight-bold text-dark">Students (Class-wise) with Attendance Totals</h6>
                </div>
                <div class="card-body">

                  <?php if (count($byClass) === 0) { ?>
                    <div class="alert alert-danger" role="alert" style="margin:0;">No Record Found!</div>
                  <?php } else { ?>

                    <div class="accordion" id="classAccordion">
                      <?php 
                        $i = 0;
                        foreach ($byClass as $cid => $meta) {
                          $i++;
                          $collapseId = 'collapseClass_'.$cid;
                          $headingId = 'headingClass_'.$cid;
                          $isOpen = ($i === 1);
                      ?>
                        <div class="card mb-2">
                          <div class="card-header" id="<?php echo htmlspecialchars($headingId); ?>" data-toggle="collapse" data-target="#<?php echo htmlspecialchars($collapseId); ?>" aria-expanded="<?php echo $isOpen ? 'true' : 'false'; ?>" aria-controls="<?php echo htmlspecialchars($collapseId); ?>">
                            <div class="d-flex align-items-center justify-content-between">
                              <div>
                                <b><?php echo htmlspecialchars($meta['className']); ?></b>
                                <span class="text-muted">(Total Students: <?php echo intval($meta['totalStudents']); ?>)</span>
                              </div>
                              <div class="text-muted"><i class="fas fa-chevron-down"></i></div>
                            </div>
                          </div>
                          <div id="<?php echo htmlspecialchars($collapseId); ?>" class="collapse <?php echo $isOpen ? 'show' : ''; ?>" aria-labelledby="<?php echo htmlspecialchars($headingId); ?>" data-parent="#classAccordion">
                            <div class="card-body" style="padding:0;">
                              <div class="table-responsive p-3">
                                <table class="table align-items-center table-flush table-hover js-stu-table" style="width:100%">
                                  <thead class="thead-light">
                                    <tr>
                                      <th>#</th>
                                      <th>Student Name</th>
                                      <th>Admission No</th>
                                      <th style="text-align:right;">Total Attendance</th>
                                      <th style="text-align:right;">Present</th>
                                    </tr>
                                  </thead>
                                  <tbody>
                                    <?php
                                      $sn = 0;
                                      foreach ($meta['students'] as $stu) {
                                        $sn++;
                                        $name = trim(($stu['firstName'] ?? '').' '.($stu['lastName'] ?? '').' '.($stu['otherName'] ?? ''));
                                        echo "<tr>";
                                        echo "<td>".$sn."</td>";
                                        echo "<td>".htmlspecialchars($name)."</td>";
                                        echo "<td>".htmlspecialchars($stu['admissionNumber'])."</td>";
                                        echo "<td style='text-align:right;'>".intval($stu['totalAttendance'])."</td>";
                                        echo "<td style='text-align:right;font-weight:bold;'>".intval($stu['presentAttendance'])."</td>";
                                        echo "</tr>";
                                      }
                                    ?>
                                  </tbody>
                                </table>
                              </div>
                            </div>
                          </div>
                        </div>
                      <?php } ?>
                    </div>

                  <?php } ?>

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
      $('.js-stu-table').DataTable({
        pageLength: 10,
        lengthChange: true,
        order: [[1, 'asc']],
        columnDefs: [
          { orderable: false, targets: [0] }
        ]
      });
    });
  </script>
</body>

</html>
