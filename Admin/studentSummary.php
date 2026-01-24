<?php 
error_reporting(0);
include '../Includes/dbcon.php';
include '../Includes/session.php';

$rows = [];
$q = "SELECT c.className as className, COUNT(s.Id) as totalStudents
      FROM tblclass c
      LEFT JOIN tblstudents s ON s.classId = c.Id
      GROUP BY c.Id, c.className
      ORDER BY c.className ASC";
$rs = $conn->query($q);
if ($rs) {
  while ($r = $rs->fetch_assoc()) {
    $rows[] = $r;
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
            <h1 class="h3 mb-0 text-gray-800">Student Summary</h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">Student Summary</li>
            </ol>
          </div>

          <div class="row">
            <div class="col-lg-12">
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-dark">Total Students (Class-wise)</h6>
                </div>
                <div class="table-responsive p-3">
                  <table class="table align-items-center table-flush table-hover" id="dataTableHover">
                    <thead class="thead-light">
                      <tr>
                        <th>#</th>
                        <th>Class / Department</th>
                        <th style="text-align:right;">Total Students</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                        if (count($rows) > 0) {
                          $sn = 0;
                          foreach ($rows as $r) {
                            $sn++;
                            echo "<tr>";
                            echo "<td>".$sn."</td>";
                            echo "<td>".htmlspecialchars($r['className'])."</td>";
                            echo "<td style='text-align:right;font-weight:bold;'>".intval($r['totalStudents'])."</td>";
                            echo "</tr>";
                          }
                        } else {
                          echo "<tr><td colspan='3'><div class='alert alert-danger' role='alert' style='margin:0;'>No Record Found!</div></td></tr>";
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
