
<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../Includes/dbcon.php';
include '../Includes/session.php';

$statusMsg = "";

// Default empty values for form fields when not editing an existing teacher
$row = [
    'firstName'    => '',
    'lastName'     => '',
    'emailAddress' => '',
    'phoneNo'      => ''
];

//------------------------SAVE--------------------------------------------------

if(isset($_POST['save'])){
    
    $firstName=$_POST['firstName'];
  $lastName=$_POST['lastName'];
  $emailAddress=$_POST['emailAddress'];

  $phoneNo=$_POST['phoneNo'];
  $classId=$_POST['classId'];
  $classArmId=$_POST['classArmId'];
  $password = $_POST['password'];
  $dateCreated = date("Y-m-d");
   
    $query=mysqli_query($conn,"select * from tblclassteacher where emailAddress ='$emailAddress'");
    $ret=mysqli_fetch_array($query);

    $sampPass_2 = md5($password);

    if($ret > 0){ 

        $statusMsg = "<div class='alert alert-danger' data-toast='1'>This Email Address Already Exists!</div>";
    }
    else{

    $query=mysqli_query($conn,"INSERT into tblclassteacher(firstName,lastName,emailAddress,password,plainPassword,phoneNo,classId,classArmId,dateCreated) 
    value('$firstName','$lastName','$emailAddress','$sampPass_2','$password','$phoneNo','$classId','$classArmId','$dateCreated')");

    if ($query) {
        
        // mark semester as assigned in new semester table
        $qu=mysqli_query($conn,"update tblclasssemister set isAssigned='1' where Id ='$classArmId'");
            if ($qu) {
                
                $statusMsg = "<div class='alert alert-success' data-toast='1'>Created Successfully!</div>";
            }
            else
            {
                $statusMsg = "<div class='alert alert-danger' data-toast='1'>An error Occurred!</div>";
            }
    }
    else
    {
         $statusMsg = "<div class='alert alert-danger' data-toast='1'>An error Occurred!</div>";
    }
  }
}

//---------------------------------------EDIT-------------------------------------------------------------






//--------------------EDIT------------------------------------------------------------

 if (isset($_GET['Id']) && isset($_GET['action']) && $_GET['action'] == "edit")
	{
        $Id= $_GET['Id'];

        $query=mysqli_query($conn,"select * from tblclassteacher where Id ='$Id'");
        $row=mysqli_fetch_array($query);

        //------------UPDATE-----------------------------

        if(isset($_POST['update'])){
    
             $firstName=$_POST['firstName'];
              $lastName=$_POST['lastName'];
              $emailAddress=$_POST['emailAddress'];

              $phoneNo=$_POST['phoneNo'];
              $classId=$_POST['classId'];
              $classArmId=$_POST['classArmId'];
              $password = $_POST['password'];
              $password_hashed = md5($password);
              $dateCreated = date("Y-m-d");

    $query=mysqli_query($conn,"update tblclassteacher set firstName='$firstName', lastName='$lastName',
    emailAddress='$emailAddress', password='$password_hashed', plainPassword='$password', phoneNo='$phoneNo', classId='$classId',classArmId='$classArmId'
    where Id='$Id'");
            if ($query) {
                
                echo "<script type = \"text/javascript\">
                window.location = (\"createClassTeacher.php\")
                </script>"; 
            }
            else
            {
                $statusMsg = "<div class='alert alert-danger' data-toast='1'>An error Occurred!</div>";
            }
        }
    }


//--------------------------------DELETE------------------------------------------------------------------

  if (isset($_GET['Id']) && isset($_GET['classArmId']) && isset($_GET['action']) && $_GET['action'] == "delete")
	{
        $Id= $_GET['Id'];
        $classArmId= $_GET['classArmId'];

        $query = mysqli_query($conn,"DELETE FROM tblclassteacher WHERE Id='$Id'");

        if ($query == TRUE) {

            // unassign semester in new semester table
            $qu=mysqli_query($conn,"update tblclasssemister set isAssigned='0' where Id ='$classArmId'");
            if ($qu) {
                
                 echo "<script type = \"text/javascript\">
                window.location = (\"createClassTeacher.php\")
                </script>"; 
            }
            else
            {
                $statusMsg = "<div class='alert alert-danger' style='margin-right:700px;' data-toast='1'>An error Occurred!</div>";
            }
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
            <h1 class="h3 mb-0 text-gray-800">Assign Department Teachers</h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">Assign Department Teachers</li>
            </ol>
          </div>

          <div class="row">
            <div class="col-lg-12">
              <!-- Form Basic -->
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">Assign Department Teachers</h6>
                    <?php echo $statusMsg; ?>
                </div>
                <div class="card-body">
                  <form method="post">
                    <div class="form-row">
                      <div class="form-group col-12 col-md-6 mb-3">
                        <label class="form-control-label">Firstname<span class="text-danger ml-2">*</span></label>
                        <input type="text" class="form-control" required name="firstName" value="<?php echo $row['firstName'];?>" id="exampleInputFirstName">
                      </div>
                      <div class="form-group col-12 col-md-6 mb-3">
                        <label class="form-control-label">Lastname<span class="text-danger ml-2">*</span></label>
                        <input type="text" class="form-control" required name="lastName" value="<?php echo $row['lastName'];?>" id="exampleInputFirstName" >
                      </div>

                      <div class="form-group col-12 col-md-6 mb-3">
                        <label class="form-control-label">Email Address<span class="text-danger ml-2">*</span></label>
                        <input type="email" class="form-control" required name="emailAddress" value="<?php echo $row['emailAddress'];?>" id="exampleInputFirstName" >
                      </div>
                      <div class="form-group col-12 col-md-6 mb-3">
                        <label class="form-control-label">Address</label>
                        <input type="text" class="form-control" name="address" id="exampleInputAddress" >
                      </div>

                      <div class="form-group col-12 col-md-6 mb-3">
                        <label class="form-control-label">Phone No<span class="text-danger ml-2">*</span></label>
                        <input type="text" class="form-control" required name="phoneNo" value="<?php echo $row['phoneNo'];?>" id="phoneNo" minlength="10" maxlength="10" inputmode="numeric" pattern="\d{10}" >
                      </div>
                      <div class="form-group col-12 col-md-6 mb-3">
                        <label class="form-control-label">Password<span class="text-danger ml-2">*</span></label>
                        <input type="password" class="form-control" required name="password" id="password" >
                      </div>

                      <div class="form-group col-12 col-md-6 mb-3">
                        <label class="form-control-label">Confirm Password<span class="text-danger ml-2">*</span></label>
                        <input type="password" class="form-control" required name="password2" id="password2" >
                      </div>

                      <div class="w-100"></div>

                      <div class="form-group col-12 col-md-4 mb-3">
                            <label class="form-control-label">Session</label>
                            <?php
                                // load sessions from new semester table
                                $sessQry = "SELECT DISTINCT session FROM tblclasssemister WHERE session <> '' ORDER BY session DESC";
                                $sessResult = $conn->query($sessQry);
                                $sessNum = $sessResult ? $sessResult->num_rows : 0;
                                if ($sessNum > 0) {
                                    echo '<select name="session" class="form-control">';
                                    echo '<option value="">--Select Session--</option>';
                                    while ($sessRow = $sessResult->fetch_assoc()) {
                                        $sval = $sessRow['session'];
                                        echo '<option value="'.$sval.'">'.$sval.'</option>';
                                    }
                                    echo '</select>';
                                } else {
                                    echo '<select name="session" class="form-control">';
                                    echo '<option value="">--No Session Defined--</option>';
                                    echo '</select>';
                                }
                            ?>
                      </div>
                       <div class="form-group col-12 col-md-4 mb-3">
                        <label class="form-control-label">Select Department<span class="text-danger ml-2">*</span></label>
                         <?php
                        $qry= "SELECT * FROM tblclass ORDER BY className ASC";
                        $result = $conn->query($qry);
                        $num = $result->num_rows;		
                        if ($num > 0){
                          echo ' <select required name="classId" onchange="classArmDropdown(this.value)" class="form-control">';
                          echo'<option value="">--Select Department--</option>';
                          while ($rows = $result->fetch_assoc()){
                          echo'<option value="'.$rows['Id'].'" >'.$rows['className'].'</option>';
                              }
                                  echo '</select>';
                              }
                            ?>  
                      </div>
                      <div class="form-group col-12 col-md-4 mb-3">
                            <label class="form-control-label">Syllabus Type</label>
                            <select name="syllabusType" class="form-control">
                                <option value="">--Select Syllabus Type--</option>
                                <option value="SEP">SEP</option>
                                <option value="NEP">NEP</option>
                            </select>
                      </div>

                      <div class="form-group col-12 col-md-6 mb-3">
                        <label class="form-control-label">Semester<span class="text-danger ml-2">*</span></label>
                            <?php
                                echo "<div id='txtHint'><select class='form-control mb-3'><option value=''>--Select Class First--</option></select></div>";
                            ?>
                      </div>
                      <div class="form-group col-12 col-md-6 mb-3">
                            <label class="form-control-label">Division</label>
                            <select name="division" class="form-control">
                                <option value="Not Applicable" selected>Not Applicable</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
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
                  <h6 class="m-0 font-weight-bold text-primary">All Assigned Teachers</h6>
                </div>
                <div class="table-responsive table-cards p-3">
                  <table class="table align-items-center table-flush table-hover" id="dataTableHover">
                    <thead class="thead-light">
                      <tr>
                        <th>#</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Email Address</th>
                        <th>Phone No</th>
                        <th>Department</th>
                        <th>Semester</th>
                        <th>Password</th>
                        <th>Date Created</th>
                        <th>Edit</th>
                        <th>Delete</th>
                      </tr>
                    </thead>
                   
                    <tbody>

                  <?php
                      $query = "SELECT tblclassteacher.Id,tblclass.className,tblclasssemister.semisterName,tblclasssemister.Id AS classArmId,tblclassteacher.firstName,
                      tblclassteacher.lastName,tblclassteacher.emailAddress,tblclassteacher.phoneNo,tblclassteacher.plainPassword,tblclassteacher.dateCreated
                      FROM tblclassteacher
                      INNER JOIN tblclass ON tblclass.Id = tblclassteacher.classId
                      INNER JOIN tblclasssemister ON tblclasssemister.Id = tblclassteacher.classArmId";
                      $rs = $conn->query($query);
                      $num = $rs->num_rows;
                      $sn=0;
                      $status="";
                      if($num > 0)
                      { 
                        while ($rows = $rs->fetch_assoc())
                          {
                             $sn = $sn + 1;
                            echo"
                              <tr>
                                <td data-label=\"#\">".$sn."</td>
                                <td data-label=\"First Name\">".$rows['firstName']."</td>
                                <td data-label=\"Last Name\">".$rows['lastName']."</td>
                                <td data-label=\"Email\">".$rows['emailAddress']."</td>
                                <td data-label=\"Phone\">".$rows['phoneNo']."</td>
                                <td data-label=\"Dept\">".$rows['className']."</td>
                                <td data-label=\"Sem\">".$rows['semisterName']."</td>
                                <td data-label=\"Pass\">".$rows['plainPassword']."</td>
                                <td data-label=\"Created\">".$rows['dateCreated']."</td>
                                <td data-label=\"Edit\"><a href='?action=edit&Id=".$rows['Id']."'><i class='fas fa-fw fa-edit'></i></a></td>
                                <td data-label=\"Delete\"><a href='?action=delete&Id=".$rows['Id']."&classArmId=".$rows['classArmId']."'><i class='fas fa-fw fa-trash'></i></a></td>
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

    function classArmDropdown(str) {
    if (str == "") {
        document.getElementById("txtHint").innerHTML = "<select class='form-control mb-3'><option value=''>--Select Department First--</option></select>";
        return;
    } else { 
        if (window.XMLHttpRequest) {
            // code for IE7+, Firefox, Chrome, Opera, Safari
            xmlhttp = new XMLHttpRequest();
        } else {
            // code for IE6, IE5
            xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
        }
        xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                document.getElementById("txtHint").innerHTML = this.responseText;
            }
        };
        xmlhttp.open("GET","ajaxClassArms2.php?cid="+str,true);
        xmlhttp.send();
    }
}
  </script>
</body>

</html>