<?php 
error_reporting(0);
include '../Includes/dbcon.php';
include '../Includes/session.php';

$query = "SELECT tblclass.className,tblclasssemister.semisterName 
    FROM tblclassteacher
    INNER JOIN tblclass ON tblclass.Id = tblclassteacher.classId
    INNER JOIN tblclasssemister ON tblclasssemister.Id = tblclassteacher.classArmId
    Where tblclassteacher.Id = '$_SESSION[userId]'";

$rs = $conn->query($query);
$rrw = $rs ? $rs->fetch_assoc() : null;

$className = (!empty($rrw) && isset($rrw['className'])) ? $rrw['className'] : 'Not Assigned';
$semesterName = (!empty($rrw) && isset($rrw['semisterName'])) ? $rrw['semisterName'] : 'Not Assigned';

$qStudents = mysqli_query($conn,"SELECT * from tblstudents where classId = '$_SESSION[classId]' and classArmId = '$_SESSION[classArmId]'");
$totalStudents = $qStudents ? mysqli_num_rows($qStudents) : 0;
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
            <h1 class="h3 mb-0 text-gray-800">Class Details</h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">Class Details</li>
            </ol>
          </div>

          <div class="row">
            <div class="col-lg-12">
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">Assigned Class</h6>
                </div>
                <div class="card-body">
                  <div class="list-group">
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                      <strong>Class</strong>
                      <span><?php echo htmlspecialchars($className); ?></span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                      <strong>Semester</strong>
                      <span><?php echo htmlspecialchars($semesterName); ?></span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                      <strong>Total Students</strong>
                      <span><?php echo intval($totalStudents); ?></span>
                    </div>
                  </div>

                  <div class="mt-3">
                    <a href="viewStudents.php" class="btn btn-primary">View Students</a>
                  </div>
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
</body>

</html>
