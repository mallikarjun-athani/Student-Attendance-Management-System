<?php 
include '../Includes/dbcon.php';
include '../Includes/session.php';
?>

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
            <h1 class="h3 mb-0 font-weight-bold">Dashboard <span class="text-primary-grad">Overview</span></h1>
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="./">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
              </ol>
            </nav>
          </div>

          <div class="row">
            <!-- Students Card -->
            <?php 
            $query1=mysqli_query($conn,"SELECT COUNT(*) AS total FROM tblstudents");
            $row1 = $query1 ? mysqli_fetch_assoc($query1) : null;
            $students = $row1 && isset($row1['total']) ? intval($row1['total']) : 0;
            ?>
            <div class="col-xl-3 col-md-6 mb-4">
              <a href="createStudents.php" class="text-decoration-none shadow-none">
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

            <!-- Teachers Card -->
            <?php 
            $query1=mysqli_query($conn,"SELECT * from tblclassteacher");                       
            $classTeacher = mysqli_num_rows($query1);
            ?>
            <div class="col-xl-3 col-md-6 mb-4">
              <a href="createClassTeacher.php" class="text-decoration-none shadow-none">
                <div class="card app-dashboard-card">
                  <div class="card-body">
                    <div class="icon-box bg-soft-purple">
                      <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="stats-value"><?php echo $classTeacher;?></div>
                    <div class="stats-label">Department Teachers</div>
                  </div>
                </div>
              </a>
            </div>

            <!-- Class Card -->
            <?php 
            $query1=mysqli_query($conn,"SELECT * from tblclass");                       
            $class = mysqli_num_rows($query1);
            ?>
            <div class="col-xl-3 col-md-6 mb-4">
              <a href="createClass.php" class="text-decoration-none shadow-none">
                <div class="card app-dashboard-card">
                  <div class="card-body">
                    <div class="icon-box bg-soft-green">
                      <i class="fas fa-chalkboard"></i>
                    </div>
                    <div class="stats-value"><?php echo $class;?></div>
                    <div class="stats-label">Total Departments</div>
                  </div>
                </div>
              </a>
            </div>

            <!-- Attendance Card -->
            <?php 
            // Count from tblsubjectattendance where actual attendance is stored
            $query1=mysqli_query($conn,"SELECT COUNT(DISTINCT admissionNumber, dateTaken) as cnt FROM tblsubjectattendance WHERE status = '1'");
            $row1 = $query1 ? mysqli_fetch_assoc($query1) : null;
            $totAttendance = $row1 && isset($row1['cnt']) ? intval($row1['cnt']) : 0;
            ?>
            <div class="col-xl-3 col-md-6 mb-4">
              <a href="todayAttendanceSummary.php" class="text-decoration-none shadow-none">
                <div class="card app-dashboard-card">
                  <div class="card-body">
                    <div class="icon-box bg-soft-red">
                      <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stats-value"><?php echo $totAttendance;?></div>
                    <div class="stats-label">Attendance Logs</div>
                  </div>
                </div>
              </a>
            </div>

            <!-- Session Card -->
            <?php 
            $query1 = mysqli_query($conn,"SELECT * from tblsessiondivision");
            $sessTerm = $query1 ? mysqli_num_rows($query1) : 0;
            ?>
            <div class="col-xl-3 col-md-6 mb-4">
              <a href="createSessionTerm.php" class="text-decoration-none shadow-none">
                <div class="card app-dashboard-card">
                  <div class="card-body">
                    <div class="icon-box bg-soft-orange">
                      <i class="fas fa-history"></i>
                    </div>
                    <div class="stats-value"><?php echo $sessTerm;?></div>
                    <div class="stats-label">Sessions & Terms</div>
                  </div>
                </div>
              </a>
            </div>

            <!-- Semesters Card -->
            <?php 
            $query1=mysqli_query($conn,"SELECT * from tblclasssemister");                       
            $classArms = mysqli_num_rows($query1);
            ?>
            <div class="col-xl-3 col-md-6 mb-4">
              <a href="createClassArms.php" class="text-decoration-none shadow-none">
                <div class="card app-dashboard-card">
                  <div class="card-body">
                    <div class="icon-box bg-soft-cyan">
                      <i class="fas fa-layer-group"></i>
                    </div>
                    <div class="stats-value"><?php echo $classArms;?></div>
                    <div class="stats-label">Total Semesters</div>
                  </div>
                </div>
              </a>
            </div>

            <!-- Divisions Card -->
            <?php 
            $query1=mysqli_query($conn,"SELECT * from tbldivision");                       
            $termonly = mysqli_num_rows($query1);
            ?>
            <div class="col-xl-3 col-md-6 mb-4">
              <a href="divisionsList.php" class="text-decoration-none shadow-none">
                <div class="card app-dashboard-card">
                  <div class="card-body">
                    <div class="icon-box bg-soft-orange">
                      <i class="fas fa-th"></i>
                    </div>
                    <div class="stats-value"><?php echo $termonly;?></div>
                    <div class="stats-label">Total Divisions</div>
                  </div>
                </div>
              </a>
            </div>
          </div>
          <!--Row-->

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