<?php 
error_reporting(0);
include '../Includes/dbcon.php';
include '../Includes/session.php';

$selectedClassId = isset($_GET['classId']) ? trim($_GET['classId']) : '';

$classes = [];
$classRs = $conn->query("SELECT Id, className FROM tblclass ORDER BY className ASC");
if ($classRs && $classRs->num_rows > 0) {
  while ($c = $classRs->fetch_assoc()) {
    $classes[] = $c;
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
            <h1 class="h3 mb-0 text-gray-800">All Students</h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">All Students</li>
            </ol>
          </div>

          <div class="row">
            <div class="col-lg-12">
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">Registered Students</h6>
                </div>
                <div class="card-body pb-0">
                  <form method="get" class="form-inline" style="gap:10px; flex-wrap:wrap;">
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
                <div class="table-responsive table-cards p-3">
                  <table class="table align-items-center table-flush table-hover" id="dataTableHover">
                    <thead class="thead-light">
                      <tr>
                        <th>#</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Father Name</th>
                        <th>Admission No</th>
                        <th>Class</th>
                        <th>Semester</th>
                        <th>Email Address</th>
                        <th>Phone Number</th>
                        <th>Session</th>
                        <th>Syllabus Type</th>
                        <th>Division</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                        $where = '';
                        if ($selectedClassId !== '') {
                          $where = ' WHERE s.classId = '.intval($selectedClassId).' ';
                        }

                        $q = "SELECT s.Id, s.firstName, s.lastName, s.otherName, s.admissionNumber, s.emailAddress, s.phoneNo, s.session, s.syllabusType, s.division,
                                     c.className, a.semisterName
                              FROM tblstudents s
                              LEFT JOIN tblclass c ON c.Id = s.classId
                              LEFT JOIN tblclasssemister a ON a.Id = s.classArmId
                              ".$where."
                              ORDER BY s.Id DESC";

                        $rs = $conn->query($q);
                        $sn = 0;
                        if ($rs && $rs->num_rows > 0) {
                          while ($row = $rs->fetch_assoc()) {
                            $sn++;
                            echo "<tr>";
                            echo "<td data-label=\"#\">".$sn."</td>";
                            echo "<td data-label=\"First Name\">".htmlspecialchars($row['firstName'])."</td>";
                            echo "<td data-label=\"Last Name\">".htmlspecialchars($row['lastName'])."</td>";
                            echo "<td data-label=\"Father Name\">".htmlspecialchars($row['otherName'])."</td>";
                            echo "<td data-label=\"Admission No\">".htmlspecialchars($row['admissionNumber'])."</td>";
                            echo "<td data-label=\"Class\">".htmlspecialchars($row['className'] ?? '')."</td>";
                            echo "<td data-label=\"Semester\">".htmlspecialchars($row['semisterName'] ?? '')."</td>";
                            echo "<td data-label=\"Email Address\">".htmlspecialchars($row['emailAddress'])."</td>";
                            echo "<td data-label=\"Phone Number\">".htmlspecialchars($row['phoneNo'])."</td>";
                            echo "<td data-label=\"Session\">".htmlspecialchars($row['session'])."</td>";
                            echo "<td data-label=\"Syllabus Type\">".htmlspecialchars($row['syllabusType'])."</td>";
                            echo "<td data-label=\"Division\">".htmlspecialchars($row['division'])."</td>";
                            echo "</tr>";
                          }
                        } else {
                          echo "<tr><td colspan='12'><div class='alert alert-danger' role='alert' style='margin:0;'>No Record Found!</div></td></tr>";
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
      $('#dataTableHover').DataTable();
    });
  </script>
</body>

</html>
