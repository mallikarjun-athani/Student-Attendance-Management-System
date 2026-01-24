
<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../Includes/dbcon.php';
include '../Includes/session.php';
$statusMsg = "";

//------------------------SAVE--------------------------------------------------

if(isset($_POST['save'])){
    
    $classId=$_POST['classId'];
    $classArmName=$_POST['classArmName'];
    $syllabusType = isset($_POST['syllabusType']) ? $_POST['syllabusType'] : '';
    $session = isset($_POST['session']) ? $_POST['session'] : '';
    $division = isset($_POST['division']) ? $_POST['division'] : '';
   
    // use new semester table/column names
    $query=mysqli_query($conn,"select * from tblclasssemister where semisterName ='$classArmName' and classId = '$classId'");
    $ret=mysqli_fetch_array($query);

    if($ret > 0){ 

        $statusMsg = "<div class='alert alert-danger' style='margin-right:700px;' data-toast='1'>This Semester Already Exists!</div>";
    }
    else{

        $query=mysqli_query($conn,"insert into tblclasssemister(classId,semisterName,syllabusType,session,division,isAssigned) value('$classId','$classArmName','$syllabusType','$session','$division','0')");

        if ($query) {

            // also persist session + division into normalized tables if provided
            if ($session != '' && $division != '') {
                $dateCreated = date("Y-m-d");

                // ensure division exists in tbldivision
                $divisionRes = mysqli_query($conn, "SELECT Id FROM tbldivision WHERE divisionName = '$division' LIMIT 1");
                $divisionRow = $divisionRes ? mysqli_fetch_assoc($divisionRes) : null;

                if ($divisionRow && isset($divisionRow['Id'])) {
                    $divisionId = $divisionRow['Id'];
                } else {
                    $insertDivision = mysqli_query($conn, "INSERT INTO tbldivision(divisionName) VALUE('$division')");
                    if ($insertDivision) {
                        $divisionId = mysqli_insert_id($conn);
                    } else {
                        $divisionId = null;
                    }
                }

                if ($divisionId !== null) {
                    // ensure session+division pair exists in tblsessiondivision (using divisionId column)
                    $sessCheck = mysqli_query($conn, "SELECT Id FROM tblsessiondivision WHERE sessionName = '$session' AND divisionId = '$divisionId' LIMIT 1");
                    $sessRow = $sessCheck ? mysqli_fetch_assoc($sessCheck) : null;

                    if (!($sessRow && isset($sessRow['Id']))) {
                        mysqli_query($conn, "INSERT INTO tblsessiondivision(sessionName,divisionId,isActive,dateCreated) VALUE('$session','$divisionId','0','$dateCreated')");
                    }
                }
            }

            $statusMsg = "<div class='alert alert-success'  style='margin-right:700px;' data-toast='1'>Created Successfully!</div>";
        }
        else
        {
             $statusMsg = "<div class='alert alert-danger' style='margin-right:700px;' data-toast='1'>An error Occurred!</div>";
        }
  }
}

//---------------------------------------EDIT-------------------------------------------------------------






//--------------------EDIT------------------------------------------------------------

 if (isset($_GET['Id']) && isset($_GET['action']) && $_GET['action'] == "edit")
	{
        $Id= $_GET['Id'];

        $query=mysqli_query($conn,"select * from tblclasssemister where Id ='$Id'");
        $row=mysqli_fetch_array($query);

        //------------UPDATE-----------------------------

        if(isset($_POST['update'])){
    
            $classId=$_POST['classId'];
            $classArmName=$_POST['classArmName'];
            $syllabusType = isset($_POST['syllabusType']) ? $_POST['syllabusType'] : '';
            $session = isset($_POST['session']) ? $_POST['session'] : '';
            $division = isset($_POST['division']) ? $_POST['division'] : '';

            $query=mysqli_query($conn,"update tblclasssemister set classId = '$classId', semisterName='$classArmName', syllabusType='$syllabusType', session='$session', division='$division' where Id='$Id'");

            if ($query) {

                // also ensure session + division pair exists in normalized tables when updating
                if ($session != '' && $division != '') {
                    $dateCreated = date("Y-m-d");

                    // ensure division exists in tblDivision
                    $divisionRes = mysqli_query($conn, "SELECT Id FROM tblDivision WHERE divisionName = '$division' LIMIT 1");
                    $divisionRow = $divisionRes ? mysqli_fetch_assoc($divisionRow) : null;

                    if ($divisionRow && isset($divisionRow['Id'])) {
                        $divisionId = $divisionRow['Id'];
                    } else {
                        $insertDivision = mysqli_query($conn, "INSERT INTO tblDivision(divisionName) VALUE('$division')");
                        if ($insertDivision) {
                            $divisionId = mysqli_insert_id($conn);
                        } else {
                            $divisionId = null;
                        }
                    }

                    if ($divisionId !== null) {
                        // ensure session+division pair exists in tblsessiondivision (using divisionId column)
                        $sessCheck = mysqli_query($conn, "SELECT Id FROM tblsessiondivision WHERE sessionName = '$session' AND divisionId = '$divisionId' LIMIT 1");
                        $sessRow = $sessCheck ? mysqli_fetch_assoc($sessRow) : null;

                        if (!($sessRow && isset($sessRow['Id']))) {
                            mysqli_query($conn, "INSERT INTO tblsessiondivision(sessionName,divisionId,isActive,dateCreated) VALUE('$session','$divisionId','0','$dateCreated')");
                        }
                    }
                }

                echo "<script type = \"text/javascript\">\n                window.location = (\"createClassArms.php\")\n                </script>"; 
            }
            else
            {
                $statusMsg = "<div class='alert alert-danger' style='margin-right:700px;' data-toast='1'>An error Occurred!</div>";
            }
        }
    }


//--------------------------------DELETE------------------------------------------------------------------

  if (isset($_GET['Id']) && isset($_GET['action']) && $_GET['action'] == "delete")
	{
        $Id= $_GET['Id'];

        $query = mysqli_query($conn,"DELETE FROM tblclasssemister WHERE Id='$Id'");

        if ($query == TRUE) {

                echo "<script type = \"text/javascript\">
                window.location = (\"createClassArms.php\")
                </script>";  
        }
        else{

            $statusMsg = "<div class='alert alert-danger' style='margin-right:700px;' data-toast='1'>An error Occurred!</div>"; 
         }
      
  }


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
            <h1 class="h3 mb-0 text-gray-800">Create Semesters</h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">Create Semesters</li>
            </ol>
          </div>

          <div class="row">
            <div class="col-lg-12">
              <!-- Form Basic -->
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">Create Semesters</h6>
                    <?php echo $statusMsg; ?>
                </div>
                <div class="card-body">
                  <form method="post">
                    <div class="form-row">
                      <div class="form-group col-12 col-md-6 mb-3">
                        <label class="form-control-label">Select Class<span class="text-danger ml-2">*</span></label>
                        <?php
                        $qry= "SELECT * FROM tblclass ORDER BY className ASC";
                        $result = $conn->query($qry);
                        $num = $result->num_rows;		
                        if ($num > 0){
                          echo ' <select required name="classId" class="form-control">';
                          echo'<option value="">--Select Class--</option>';
                          while ($rows = $result->fetch_assoc()){
                          echo'<option value="'.$rows['Id'].'" >'.$rows['className'].'</option>';
                              }
                                  echo '</select>';
                              }
                            ?>
                      </div>

                      <div class="form-group col-12 col-md-6 mb-3">
                        <label class="form-control-label">Syllabus Type Name<span class="text-danger ml-2">*</span></label>
                        <select name="syllabusType" id="syllabusType" class="form-control">
                          <option value="">--Select Syllabus Type--</option>
                          <option value="SEP">SEP</option>
                          <option value="NEP">NEP</option>
                        </select>
                      </div>

                      <div class="form-group col-12 col-md-6 mb-3">
                        <label class="form-control-label">Semester Name<span class="text-danger ml-2">*</span></label>
                        <?php
                          $semesters = array('First Sem','Second Sem','Third Sem','Fourth Sem','Fifth Sem','Sixth Sem','Seventh Sem','Eighth Sem');
                          echo '<select required name="classArmName" id="semesterName" class="form-control">';
                          echo '<option value="">--Select Semester--</option>';
                          foreach ($semesters as $sem) {
                            $selected = (isset($row['semisterName']) && $row['semisterName'] == $sem) ? 'selected' : '';
                            echo '<option value="'.$sem.'" '.$selected.'>'.$sem.'</option>';
                          }
                          echo '</select>';
                        ?>
                      </div>

                      <div class="form-group col-12 col-md-6 mb-3">
                        <label class="form-control-label">Session</label>
                        <input type="text" class="form-control" name="session" placeholder="Session">
                      </div>

                      <div class="form-group col-12 col-md-6 mb-3">
                        <label class="form-control-label">Division</label>
                        <select name="division" class="form-control">
                          <option value="Not Applicable" <?php echo (isset($row['division']) && $row['division'] == 'Not Applicable') || !isset($row['division']) ? 'selected' : ''; ?>>Not Applicable</option>
                          <option value="A" <?php echo (isset($row['division']) && $row['division'] == 'A') ? 'selected' : ''; ?>>A</option>
                          <option value="B" <?php echo (isset($row['division']) && $row['division'] == 'B') ? 'selected' : ''; ?>>B</option>
                          <option value="C" <?php echo (isset($row['division']) && $row['division'] == 'C') ? 'selected' : ''; ?>>C</option>
                          <option value="D" <?php echo (isset($row['division']) && $row['division'] == 'D') ? 'selected' : ''; ?>>D</option>
                        </select>
                      </div>

                      <div class="form-group col-12 col-md-6 mb-3 d-flex align-items-end justify-content-md-end">
                        <?php
                        if (isset($Id))
                        {
                        ?>
                        <button type="submit" name="update" class="btn btn-warning">Update</button>
                        <?php
                        } else {
                        ?>
                        <button type="submit" name="save" class="btn btn-primary">Save</button>
                        <?php
                        }
                        ?>
                      </div>
                    </div>
                  </form>
                </div>
              </div>

              <!-- Input Group -->
                 <div class="row">
              <div class="col-lg-12">
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">All Semesters</h6>
                </div>
                <div class="table-responsive p-3">
                  <table class="table align-items-center table-flush table-hover" id="dataTableHover">
                    <thead class="thead-light">
                      <tr>
                        <th>#</th>
                        <th>Class Name</th>
                        <th>Semester Name</th>
                        <th>Session</th>
                        <th>Division</th>
                        <th>Syllabus Type</th>
                        <th>Status</th>
                        <th>Edit</th>
                        <th>Delete</th>
                      </tr>
                    </thead>
                  
                    <tbody>

                  <?php
                      $query = "SELECT tblclasssemister.Id,tblclasssemister.isAssigned,tblclass.className,tblclasssemister.semisterName,tblclasssemister.session,tblclasssemister.division,tblclasssemister.syllabusType 
                      FROM tblclasssemister
                      INNER JOIN tblclass ON tblclass.Id = tblclasssemister.classId";
                      $rs = $conn->query($query);
                      $num = $rs->num_rows;
                      $sn=0;
                      $status="";
                      if($num > 0)
                      { 
                        while ($rows = $rs->fetch_assoc())
                          {
                              if($rows['isAssigned'] == '1'){$status = "Assigned";}else{$status = "UnAssigned";}
                             $sn = $sn + 1;
                            echo"
                              <tr>
                                <td>".$sn."</td>
                                <td>".$rows['className']."</td>
                                <td>".$rows['semisterName']."</td>
                                <td>".$rows['session']."</td>
                                <td>".$rows['division']."</td>
                                <td>".$rows['syllabusType']."</td>
                                <td>".$status."</td>
                                <td><a href='?action=edit&Id=".$rows['Id']."'><i class='fas fa-fw fa-edit'></i>Edit</a></td>
                                <td><a href='?action=delete&Id=".$rows['Id']."'><i class='fas fa-fw fa-trash'></i>Delete</a></td>
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

      var initialSemester = "<?php echo isset($row['semisterName']) ? $row['semisterName'] : ''; ?>";
      var $semesterSelect = $('#semesterName');
      var allSemesters = ['First Sem','Second Sem','Third Sem','Fourth Sem','Fifth Sem','Sixth Sem','Seventh Sem','Eighth Sem'];

      function populateSemesters(type, selected) {
        var allowed = [];
        // Both SEP and NEP should show all 6 semesters
        allowed = allSemesters.slice();

        var current = selected || $semesterSelect.val();
        $semesterSelect.empty();
        $semesterSelect.append($('<option>', { value: '', text: '--Select Semester--' }));
        for (var i = 0; i < allowed.length; i++) {
          var sem = allowed[i];
          var opt = $('<option>', { value: sem, text: sem });
          if (current === sem) {
            opt.attr('selected', 'selected');
          }
          $semesterSelect.append(opt);
        }
      }

      $('#syllabusType').on('change', function () {
        var type = $(this).val();
        populateSemesters(type, null);
      });

      populateSemesters($('#syllabusType').val(), initialSemester);
    });
  </script>
</body>

</html>