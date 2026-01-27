
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
            <h1 class="h3 mb-0 text-gray-800">All Students in (<?php echo $rrw['className'].' - '.$rrw['semisterName'];?>) Department</h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">All Students in Department</li>
            </ol>
          </div>

          <div class="row">
            <div class="col-lg-12">
              <!-- Form Basic -->


              <!-- Input Group -->
                 <div class="row">
              <div class="col-lg-12">
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">All Students In Department</h6>
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
                        <th>Department</th>
                        <th>Semester</th>
                        <th>Email Address</th>
                        <th>Phone Number</th>
                        <th>Session</th>
                        <th>Syllabus Type</th>
                        <th>Division</th>
                        <th>Photo</th>
                      </tr>
                    </thead>
                    
                    <tbody>

                  <?php
                      $query = "SELECT tblstudents.Id,tblclass.className,tblclasssemister.semisterName,tblclasssemister.Id AS classArmId,tblstudents.firstName,
                      tblstudents.lastName,tblstudents.otherName,tblstudents.admissionNumber,tblstudents.dateCreated,
                      tblstudents.emailAddress,tblstudents.phoneNo,tblstudents.session,tblstudents.syllabusType,tblstudents.division,tblstudents.photo
                      FROM tblstudents
                      INNER JOIN tblclass ON tblclass.Id = tblstudents.classId
                      INNER JOIN tblclasssemister ON tblclasssemister.Id = tblstudents.classArmId
                      where tblstudents.classId = '$_SESSION[classId]' and tblstudents.classArmId = '$_SESSION[classArmId]'";
                      $rs = $conn->query($query);
                      $num = $rs->num_rows;
                      $sn=0;
                      $status="";
                      if($num > 0)
                      { 
                        while ($rows = $rs->fetch_assoc())
                          {
                             $sn = $sn + 1;
                             $photoUrl = "img/user-icn.png";
                             if (isset($rows['photo']) && trim($rows['photo']) !== '') {
                               $photoUrl = '../' . ltrim(trim($rows['photo']), '/');
                             }
                           echo"
                              <tr>
                                <td data-label=\"#\">".$sn."</td>
                                <td data-label=\"First Name\">".$rows['firstName']."</td>
                                <td data-label=\"Last Name\">".$rows['lastName']."</td>
                                <td data-label=\"Father Name\">".$rows['otherName']."</td>
                                <td data-label=\"Admission No\">".$rows['admissionNumber']."</td>
                                <td data-label=\"Department\">".$rows['className']."</td>
                                <td data-label=\"Semester\">".$rows['semisterName']."</td>
                                <td data-label=\"Email Address\">".$rows['emailAddress']."</td>
                                <td data-label=\"Phone Number\">".$rows['phoneNo']."</td>
                                <td data-label=\"Session\">".$rows['session']."</td>
                                <td data-label=\"Syllabus Type\">".$rows['syllabusType']."</td>
                                <td data-label=\"Division\">".$rows['division']."</td>
                                <td data-label=\"Photo\"><img src=\"".htmlspecialchars($photoUrl, ENT_QUOTES)."\" alt=\"Photo\" style=\"width:42px;height:42px;object-fit:cover;border-radius:6px;border:1px solid #eee;\" /></td>
                              </tr>";
                          }
                      }
                      else
                      {
                           echo   
                           "<div class='alert alert-danger' role='alert'>
                            No Record Found!
                            </div>";
                      }
                      
                      ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
            </div>
          </div>
          <!--Row-->

          <!-- Documentation Link -->
          <!-- <div class="row">
            <div class="col-lg-12 text-center">
              <p>For more documentations you can visit<a href="https://getbootstrap.com/docs/4.3/components/forms/"
                  target="_blank">
                  bootstrap forms documentations.</a> and <a
                  href="https://getbootstrap.com/docs/4.3/components/input-group/" target="_blank">bootstrap input
                  groups documentations</a></p>
            </div>
          </div> -->

        </div>
        <!---Container Fluid-->
      </div>
      <!-- Footer -->
       <?php include "Includes/footer.php";?>
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
   <!-- Page level plugins -->
  <script src="../vendor/datatables/jquery.dataTables.min.js"></script>
  <script src="../vendor/datatables/dataTables.bootstrap4.min.js"></script>

  <!-- Page level custom scripts -->
  <script>
    $(document).ready(function () {
      $('#dataTable').DataTable(); // ID From dataTable 
      $('#dataTableHover').DataTable(); // ID From dataTable with Hover
    });
  </script>
</body>

</html>