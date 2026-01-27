
<?php 
error_reporting(0);
include '../Includes/dbcon.php';
include '../Includes/session.php';

//------------------------SAVE--------------------------------------------------

if(isset($_POST['save'])){
    
    $sessionName=$_POST['sessionName'];
    $termId=$_POST['termId'];
    $dateCreated = date("Y-m-d");
   
    // use new session/division table (divisionId column)
    $query=mysqli_query($conn,"select * from tblsessiondivision where sessionName ='$sessionName' and divisionId = '$termId'");
    $ret=mysqli_fetch_array($query);

    if($ret > 0){ 

        $statusMsg = "<div class='alert alert-danger' style='margin-right:700px;' data-toast='1'>This Session and Term Already Exists!</div>";
    }
    else{

        $query=mysqli_query($conn,"insert into tblsessiondivision(sessionName,divisionId,isActive,dateCreated) value('$sessionName','$termId','0','$dateCreated')");

    if ($query) {
        
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

        $query=mysqli_query($conn,"select * from tblsessiondivision where Id ='$Id'");
        $row=mysqli_fetch_array($query);

        //------------UPDATE-----------------------------

        if(isset($_POST['update'])){
    
             $sessionName=$_POST['sessionName'];
    $termId=$_POST['termId'];
    $dateCreated = date("Y-m-d");
        
            $query=mysqli_query($conn,"update tblsessiondivision set sessionName='$sessionName',divisionId='$termId',isActive='0' where Id='$Id'");

            if ($query) {
                
                echo "<script type = \"text/javascript\">
                window.location = (\"createSessionTerm.php\")
                </script>"; 
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

        $query = mysqli_query($conn,"DELETE FROM tblsessiondivision WHERE Id='$Id'");

        if ($query == TRUE) {

                echo "<script type = \"text/javascript\">
                window.location = (\"createSessionTerm.php\")
                </script>";  
        }
        else{

            $statusMsg = "<div class='alert alert-danger' style='margin-right:700px;' data-toast='1'>An error Occurred!</div>"; 
         }
      
  }


  //--------------------------------ACTIVATE------------------------------------------------------------------

  if (isset($_GET['Id']) && isset($_GET['action']) && $_GET['action'] == "activate")
	{
        $Id= $_GET['Id'];

        $query=mysqli_query($conn,"update tblsessiondivision set isActive='0' where isActive='1'");

            if ($query) {
                
                $que=mysqli_query($conn,"update tblsessiondivision set isActive='1' where Id='$Id'");

                if ($que) {
                    
                    echo "<script type = \"text/javascript\">
                    window.location = (\"createSessionTerm.php\")
                    </script>";  
                }
                else
                {
                    $statusMsg = "<div class='alert alert-danger' style='margin-right:700px;' data-toast='1'>An error Occurred!</div>";
                }
            }
            else
            {
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
            <h1 class="h3 mb-0 text-gray-800">Create Session and Division</h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">Create Session and Division<</li>
            </ol>
          </div>

          <div class="row">
            <div class="col-lg-12">
              <!-- Form Basic -->
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">Create Session and Division</h6>
                    <?php echo $statusMsg; ?>
                </div>
                <div class="card-body">
                  <form method="post">
                    <div class="form-group row mb-3">
                        <div class="col-xl-6">
                            <label class="form-control-label">Session Name<span class="text-danger ml-2">*</span></label>
                      <input type="text" class="form-control" name="sessionName" value="<?php echo $row['sessionName'];?>" id="exampleInputFirstName" placeholder="Session">
                        </div>
                        <div class="col-xl-6">
                            <label class="form-control-label">Division<span class="text-danger ml-2">*</span></label>
                              <?php
                        // load divisions from renamed table tblDivision
                        $qry= "SELECT * FROM tblDivision ORDER BY divisionName ASC";
                        $result = $conn->query($qry);
                        $num = $result->num_rows;	
                        if ($num > 0){
                          echo ' <select required name="termId" class="form-control mb-3">';
                          echo'<option value="">--Select Division--</option>';
                          while ($rows = $result->fetch_assoc()){
                              echo'<option value="'.$rows['Id'].'" >'.$rows['divisionName'].'</option>';
                          }
                          echo '</select>';
                        }
                            ?>  
                        </div>
                    </div>
                      <?php
                    if (isset($Id))
                    {
                    ?>
                    <button type="submit" name="update" class="btn btn-warning">Update</button>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <?php
                    } else {           
                    ?>
                    <button type="submit" name="save" class="btn btn-primary">Save</button>
                    <?php
                    }         
                    ?>
                  </form>
                </div>
              </div>

              <!-- Input Group -->
                 <div class="row">
              <div class="col-lg-12">
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">All Session and Division</h6>
                  <h6 class="m-0 font-weight-bold text-danger">Note: <i>Click on the check symbol besides each to make session and division active!</i></h6>
                </div>
                <div class="table-responsive table-cards p-3">
                  <table class="table align-items-center table-flush table-hover" id="dataTableHover">
                    <thead class="thead-light">
                      <tr>
                        <th>#</th>
                        <th>Session</th>
                        <th>Division</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Activate</th>
                        <th>Edit</th>
                        <th>Delete</th>
                      </tr>
                    </thead>
                  
                    <tbody>

                  <?php
                      $query = "SELECT tblsessiondivision.Id,tblsessiondivision.sessionName,tblsessiondivision.isActive,tblsessiondivision.dateCreated,
                      tblDivision.divisionName
                      FROM tblsessiondivision
                      INNER JOIN tblDivision ON tblDivision.Id = tblsessiondivision.divisionId";
                      $rs = $conn->query($query);
                      $num = $rs->num_rows;
                      $sn=0;
                      if($num > 0)
                      { 
                        while ($rows = $rs->fetch_assoc())
                          {
                            if($rows['isActive'] == '1'){$status = "Active";}else{$status = "InActive";}
                             $sn = $sn + 1;
                            echo"
                              <tr>
                                <td data-label=\"#\">".$sn."</td>
                                <td data-label=\"Session\">".$rows['sessionName']."</td>
                                <td data-label=\"Division\">".$rows['divisionName']."</td>
                                <td data-label=\"Status\">".$status."</td>
                                <td data-label=\"Date\">".$rows['dateCreated']."</td>
                                <td data-label=\"Activate\"><a href='?action=activate&Id=".$rows['Id']."'><i class='fas fa-fw fa-check'></i></a></td>
                                <td data-label=\"Edit\"><a href='?action=edit&Id=".$rows['Id']."'><i class='fas fa-fw fa-edit'></i></a></td>
                                <td data-label=\"Delete\"><a href='?action=delete&Id=".$rows['Id']."'><i class='fas fa-fw fa-trash'></i></a></td>
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
  </script>
</body>

</html>