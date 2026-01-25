
<?php 
include '../Includes/dbcon.php';
include '../Includes/session.php';


    $query = "SELECT tblclass.className,tblclasssemister.semisterName 
    FROM tblclassteacher
    INNER JOIN tblclass ON tblclass.Id = tblclassteacher.classId
    INNER JOIN tblclasssemister ON tblclasssemister.Id = tblclassteacher.classArmId
    Where tblclassteacher.Id = '$_SESSION[userId]'";

    $rs = $conn->query($query);
    $num = $rs->num_rows;
    $rrw = $rs->fetch_assoc();


?>

<!DOCTYPE html>
<html lang="en">

<?php include 'Includes/header.php';?>

<body id="page-top">
  <div id="wrapper">
    <!-- Sidebar -->
   <?php include "Includes/sidebar.php";?>
    <!-- Sidebar -->
    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">
        <!-- TopBar -->
           <?php include "Includes/topbar.php";?>
        <!-- Topbar -->
        <!-- Container Fluid-->
        <div class="container-fluid" id="container-wrapper">
          <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">
              Class Teacher Dashboard (
              <?php
                if (!empty($rrw) && isset($rrw['className'], $rrw['semisterName'])) {
                    echo $rrw['className'] . ' - ' . $rrw['semisterName'];
                } else {
                    echo 'Class not assigned';
                }
              ?>
              )
            </h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
            </ol>
          </div>

          <div class="row">
            <!-- Total Students Card -->
            <?php 
            $query1=mysqli_query($conn,"SELECT * from tblstudents where classId = '$_SESSION[classId]' and classArmId = '$_SESSION[classArmId]'");                       
            $students = mysqli_num_rows($query1);
            ?>
            <div class="col-xl-3 col-md-6 mb-4">
              <a href="totalStudents.php" class="text-decoration-none shadow-none">
                <div class="card app-dashboard-card">
                  <div class="card-body">
                    <div class="icon-box bg-soft-blue">
                      <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="stats-value"><?php echo $students;?></div>
                    <div class="stats-label">Total Students</div>
                  </div>
                </div>
              </a>
            </div>

            <!-- Class Card -->
            <div class="col-xl-3 col-md-6 mb-4">
              <a href="classDetails.php" class="text-decoration-none shadow-none">
                <div class="card app-dashboard-card">
                  <div class="card-body">
                    <div class="icon-box bg-soft-purple">
                      <i class="fas fa-chalkboard"></i>
                    </div>
                    <div class="stats-value" style="font-size: 1.5rem !important;"><?php echo (!empty($rrw) && isset($rrw['className'])) ? $rrw['className'] : 'N/A';?></div>
                    <div class="stats-label">My Class</div>
                  </div>
                </div>
              </a>
            </div>

            <!-- Semester Card -->
            <div class="col-xl-3 col-md-6 mb-4">
              <a href="semesterDetails.php" class="text-decoration-none shadow-none">
                <div class="card app-dashboard-card">
                  <div class="card-body">
                    <div class="icon-box bg-soft-green">
                      <i class="fas fa-layer-group"></i>
                    </div>
                    <div class="stats-value" style="font-size: 1.5rem !important;"><?php echo (!empty($rrw) && isset($rrw['semisterName'])) ? $rrw['semisterName'] : 'N/A';?></div>
                    <div class="stats-label">Semester</div>
                  </div>
                </div>
              </a>
            </div>

            <!-- Attendance Card -->
            <?php 
            $query1=mysqli_query($conn,"SELECT * from tblattendance where classId = '$_SESSION[classId]' and classArmId = '$_SESSION[classArmId]'");                       
            $totAttendance = mysqli_num_rows($query1);
            ?>
            <div class="col-xl-3 col-md-6 mb-4">
              <a href="todayAttendanceSummary.php" class="text-decoration-none shadow-none">
                <div class="card app-dashboard-card">
                  <div class="card-body">
                    <div class="icon-box bg-soft-red">
                      <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stats-value"><?php echo $totAttendance;?></div>
                    <div class="stats-label">Total Attendance Records</div>
                  </div>
                </div>
              </a>
            </div>
          </div>
          
          <!--Row-->

          <!-- <div class="row">
            <div class="col-lg-12 text-center">
              <p>Do you like this template ? you can download from <a href="https://github.com/indrijunanda/RuangAdmin"
                  class="btn btn-primary btn-sm" target="_blank"><i class="fab fa-fw fa-github"></i>&nbsp;GitHub</a></p>
            </div>
          </div> -->

        </div>
        <!---Container Fluid-->
      </div>
      <!-- Footer -->
      <?php include 'Includes/footer.php';?>
      <!-- Footer -->
    </div>
  </div>

  <!-- Scroll to top -->
  <a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
  </a>

  <script src="../vendor/jquery/jquery.min.js"></script>
  <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
  <script src="js/ruang-admin.min.js"></script>
  <script src="../vendor/chart.js/Chart.min.js"></script>
  <script src="js/demo/chart-area-demo.js"></script>  

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const cards = document.querySelectorAll('.app-dashboard-card');
      cards.forEach(card => {
        card.style.cursor = 'pointer';
      });
    });
  </script>
</body>

</html>