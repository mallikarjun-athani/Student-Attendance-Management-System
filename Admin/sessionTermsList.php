<?php 
error_reporting(0);
include '../Includes/dbcon.php';
include '../Includes/session.php';

$tblCheck = mysqli_query($conn, "SHOW TABLES LIKE 'tblsessionterm'");
$hasSessionTerm = ($tblCheck && mysqli_num_rows($tblCheck) > 0);
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
            <h1 class="h3 mb-0 text-gray-800">Sessions & Terms</h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">Sessions & Terms</li>
            </ol>
          </div>

          <div class="row">
            <div class="col-lg-12">
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">Available Session Records</h6>
                </div>
                <div class="table-responsive table-cards p-3">
                  <table class="table align-items-center table-flush table-hover" id="dataTableHover">
                    <thead class="thead-light">
                      <tr>
                        <th>#</th>
                        <th>Session</th>
                        <th>Term/Division</th>
                        <th>Active</th>
                        <th>Date Created</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                        $sn = 0;

                        if ($hasSessionTerm) {
                          $rs = $conn->query("SELECT Id, sessionName, termId, isActive, dateCreated FROM tblsessionterm ORDER BY Id DESC");
                          if ($rs && $rs->num_rows > 0) {
                            while ($row = $rs->fetch_assoc()) {
                              $sn++;
                              echo "<tr>";
                              echo "<td data-label=\"#\">".$sn."</td>";
                              echo "<td data-label=\"Session\">".htmlspecialchars($row['sessionName'])."</td>";
                              echo "<td data-label=\"Term\">".htmlspecialchars($row['termId'])."</td>";
                              echo "<td data-label=\"Active\">".(intval($row['isActive']) === 1 ? 'Yes' : 'No')."</td>";
                              echo "<td data-label=\"Date Created\">".htmlspecialchars($row['dateCreated'])."</td>";
                              echo "</tr>";
                            }
                          }
                        } else {
                          $q = "SELECT sd.Id, sd.sessionName, sd.isActive, sd.dateCreated, d.divisionName
                                FROM tblsessiondivision sd
                                LEFT JOIN tblDivision d ON d.Id = sd.divisionId
                                ORDER BY sd.Id DESC";
                          $rs = $conn->query($q);
                          if ($rs && $rs->num_rows > 0) {
                            while ($row = $rs->fetch_assoc()) {
                              $sn++;
                              echo "<tr>";
                              echo "<td data-label=\"#\">".$sn."</td>";
                              echo "<td data-label=\"Session\">".htmlspecialchars($row['sessionName'])."</td>";
                              echo "<td data-label=\"Division\">".htmlspecialchars($row['divisionName'] ?? '')."</td>";
                              echo "<td data-label=\"Active\">".(intval($row['isActive']) === 1 ? 'Yes' : 'No')."</td>";
                              echo "<td data-label=\"Date Created\">".htmlspecialchars($row['dateCreated'])."</td>";
                              echo "</tr>";
                            }
                          }
                        }

                        if ($sn === 0) {
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
      $('#dataTableHover').DataTable();
    });
  </script>
</body>

</html>
