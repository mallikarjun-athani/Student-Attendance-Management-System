<?php 
error_reporting(0);
include '../Includes/dbcon.php';
include '../Includes/session.php';
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
            <h1 class="h3 mb-0 text-gray-800">All Teachers</h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">Teachers</li>
            </ol>
          </div>

          <div class="row">
            <div class="col-lg-12">
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">Registered Class Teachers</h6>
                </div>
                <div class="table-responsive table-cards p-3">
                  <table class="table align-items-center table-flush table-hover" id="dataTableHover">
                    <thead class="thead-light">
                      <tr>
                        <th>#</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Class</th>
                        <th>Semester</th>
                        <th>Date Created</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                        $q = "SELECT t.Id, t.firstName, t.lastName, t.emailAddress, t.phoneNo, t.dateCreated,
                                     c.className, a.semisterName
                              FROM tblclassteacher t
                              LEFT JOIN tblclass c ON c.Id = t.classId
                              LEFT JOIN tblclasssemister a ON a.Id = t.classArmId
                              ORDER BY t.Id DESC";
                        $rs = $conn->query($q);
                        $sn = 0;
                        if ($rs && $rs->num_rows > 0) {
                          while ($row = $rs->fetch_assoc()) {
                            $sn++;
                            echo "<tr>";
                            echo "<td data-label=\"#\">".$sn."</td>";
                            echo "<td data-label=\"First Name\">".htmlspecialchars($row['firstName'])."</td>";
                            echo "<td data-label=\"Last Name\">".htmlspecialchars($row['lastName'])."</td>";
                            echo "<td data-label=\"Email\">".htmlspecialchars($row['emailAddress'])."</td>";
                            echo "<td data-label=\"Phone\">".htmlspecialchars($row['phoneNo'])."</td>";
                            echo "<td data-label=\"Class\">".htmlspecialchars($row['className'] ?? '')."</td>";
                            echo "<td data-label=\"Semester\">".htmlspecialchars($row['semisterName'] ?? '')."</td>";
                            echo "<td data-label=\"Date Created\">".htmlspecialchars($row['dateCreated'])."</td>";
                            echo "</tr>";
                          }
                        } else {
                          echo "<tr><td colspan='8'><div class='alert alert-danger' role='alert' style='margin:0;'>No Record Found!</div></td></tr>";
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
