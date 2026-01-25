<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../Includes/dbcon.php';
include '../Includes/session.php';

$sessionEmailVerified = isset($_SESSION['email_verified']) ? $_SESSION['email_verified'] : '';
$sessionPhoneVerified = isset($_SESSION['phone_verified']) ? $_SESSION['phone_verified'] : '';

// Default empty values for form fields when not editing an existing student
$row = [
    'firstName'       => '',
    'lastName'        => '',
    'otherName'       => '',
    'admissionNumber' => ''
];

 $editSemRow = [
     'session' => '',
     'division' => '',
     'syllabusType' => ''
 ];

$statusMsg = "";

function ensureStudentsPhotoColumn($conn) {
  $rs = mysqli_query($conn, "SHOW COLUMNS FROM tblstudents LIKE 'photo'");
  if ($rs && mysqli_num_rows($rs) > 0) {
    return;
  }
  @mysqli_query($conn, "ALTER TABLE tblstudents ADD COLUMN photo VARCHAR(255) NULL AFTER dateCreated");
}

function requiredFieldError($label) {
  return "<div id='flashMsg' class='alert alert-danger flash-msg' data-toast='1'>".htmlspecialchars($label)." is required.</div>";
}

function validateStudentRequiredFields($isEdit, $existingPhotoRelPath = '') {
  $firstName = isset($_POST['firstName']) ? trim($_POST['firstName']) : '';
  $lastName = isset($_POST['lastName']) ? trim($_POST['lastName']) : '';
  $otherName = isset($_POST['otherName']) ? trim($_POST['otherName']) : '';
  $admissionNumber = isset($_POST['admissionNumber']) ? trim($_POST['admissionNumber']) : '';
  $emailAddress = isset($_POST['emailAddress']) ? trim($_POST['emailAddress']) : '';
  $phoneNo = isset($_POST['phoneNo']) ? preg_replace('/\s+/', '', $_POST['phoneNo']) : '';
  $classId = isset($_POST['classId']) ? trim($_POST['classId']) : '';
  $classArmId = isset($_POST['classArmId']) ? trim($_POST['classArmId']) : '';
  $session = isset($_POST['session']) ? trim($_POST['session']) : '';
  $syllabusType = isset($_POST['syllabusType']) ? trim($_POST['syllabusType']) : '';
  $division = isset($_POST['division']) ? trim($_POST['division']) : '';

  if ($firstName === '') return requiredFieldError('First Name');
  if ($otherName === '') return requiredFieldError('Father Name');
  if ($lastName === '') return requiredFieldError('Last Name');
  if ($emailAddress === '') return requiredFieldError('Email Address');
  if ($phoneNo === '') return requiredFieldError('Phone Number');
  if ($admissionNumber === '') return requiredFieldError('Registration Number');
  if ($session === '') return requiredFieldError('Session');
  if ($classId === '') return requiredFieldError('Class');
  if ($syllabusType === '') return requiredFieldError('Syllabus Type');
  if ($classArmId === '') return requiredFieldError('Semester');
  if ($division === '') return requiredFieldError('Division');

  $needsPhoto = true;
  if ($isEdit && $existingPhotoRelPath !== '') {
    $needsPhoto = false;
  }

  if ($needsPhoto) {
    if (!isset($_FILES['studentPhoto']) || !isset($_FILES['studentPhoto']['error']) || $_FILES['studentPhoto']['error'] === UPLOAD_ERR_NO_FILE) {
      return requiredFieldError('Upload Photo');
    }
  }

  return '';
}

function saveStudentPhotoUpload($studentId, $existingRelPath = '') {
  if (!isset($_FILES['studentPhoto']) || !isset($_FILES['studentPhoto']['error'])) {
    return [true, $existingRelPath, ''];
  }

  if ($_FILES['studentPhoto']['error'] === UPLOAD_ERR_NO_FILE) {
    return [true, $existingRelPath, ''];
  }

  if ($_FILES['studentPhoto']['error'] !== UPLOAD_ERR_OK) {
    return [false, $existingRelPath, 'Upload failed.'];
  }

  $tmp = $_FILES['studentPhoto']['tmp_name'];
  $size = isset($_FILES['studentPhoto']['size']) ? intval($_FILES['studentPhoto']['size']) : 0;
  if ($size <= 0) {
    return [false, $existingRelPath, 'Invalid file.'];
  }

  $maxBytes = 2 * 1024 * 1024;
  if ($size > $maxBytes) {
    return [false, $existingRelPath, 'File too large. Maximum size is 2MB.'];
  }

  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $mime = $finfo ? finfo_file($finfo, $tmp) : '';
  if ($finfo) finfo_close($finfo);

  $ext = '';
  if ($mime === 'image/jpeg') $ext = 'jpg';
  else if ($mime === 'image/png') $ext = 'png';
  else if ($mime === 'image/webp') $ext = 'webp';
  else {
    return [false, $existingRelPath, 'Only JPG, PNG, or WEBP images allowed.'];
  }

  $dir = __DIR__ . '/../uploads/students';
  if (!is_dir($dir)) {
    @mkdir($dir, 0755, true);
  }

  $base = 'student_' . intval($studentId);
  $targetAbs = $dir . '/' . $base . '.' . $ext;
  $targetRel = 'uploads/students/' . $base . '.' . $ext;

  foreach (['jpg','png','webp'] as $e) {
    $p = $dir . '/' . $base . '.' . $e;
    if (file_exists($p) && $p !== $targetAbs) {
      @unlink($p);
    }
  }

  if (!move_uploaded_file($tmp, $targetAbs)) {
    return [false, $existingRelPath, 'Failed to save uploaded file.'];
  }

  if ($existingRelPath !== '' && $existingRelPath !== $targetRel) {
    $oldAbs = __DIR__ . '/../' . ltrim($existingRelPath, '/');
    if (file_exists($oldAbs)) {
      @unlink($oldAbs);
    }
  }

  return [true, $targetRel, ''];
}

ensureStudentsPhotoColumn($conn);

if (isset($_GET['status'])) {
  if ($_GET['status'] === 'updated') {
    $statusMsg = "<div id='flashMsg' class='alert alert-success flash-msg' data-toast='1'>Updated Successfully!</div>";
  } else if ($_GET['status'] === 'deleted') {
    $statusMsg = "<div id='flashMsg' class='alert alert-success flash-msg' data-toast='1'>Deleted Successfully!</div>";
  }
}

//------------------------SAVE--------------------------------------------------

if(isset($_POST['save'])){
    
    $firstName=$_POST['firstName'];
  $lastName=$_POST['lastName'];
  $otherName=$_POST['otherName'];

  $admissionNumber=$_POST['admissionNumber'];
  $classId=$_POST['classId'];
  $classArmId=$_POST['classArmId'];
  $emailAddress = isset($_POST['emailAddress']) ? trim($_POST['emailAddress']) : '';
  $phoneNo = isset($_POST['phoneNo']) ? preg_replace('/\s+/', '', $_POST['phoneNo']) : '';
  $dateCreated = date("Y-m-d");

  $reqErr = validateStudentRequiredFields(false, '');
  if ($reqErr !== '') {
       $statusMsg = $reqErr;
  } else if ($emailAddress === '' || !filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
       $statusMsg = "<div id='flashMsg' class='alert alert-danger flash-msg' data-toast='1'>Email Address is required.</div>";
   } else if (!isset($_SESSION['email_verified']) || strtolower($_SESSION['email_verified']) !== strtolower($emailAddress)) {
       $statusMsg = "<div id='flashMsg' class='alert alert-danger flash-msg' data-toast='1'>Email not valid.</div>";
   } else {
   
     // Check for duplicate student within the same semester (classArmId)
     $query=mysqli_query($conn,"select * from tblstudents where admissionNumber ='$admissionNumber' and classArmId ='$classArmId'");
     $ret=mysqli_fetch_array($query);

    if($ret > 0){ 

        $statusMsg = "<div id='flashMsg' class='alert alert-danger flash-msg' data-toast='1'>This student already exists in this semester!</div>";
   } else {
     $query=mysqli_query($conn,"insert into tblstudents(firstName,lastName,otherName,admissionNumber,emailAddress,phoneNo,password,classId,classArmId,dateCreated) 
     value('$firstName','$lastName','$otherName','$admissionNumber','$emailAddress','$phoneNo','12345','$classId','$classArmId','$dateCreated')");

    if ($query) {
        $newId = mysqli_insert_id($conn);

        list($okPhoto, $photoRelPath, $photoErr) = saveStudentPhotoUpload($newId, '');
        if ($okPhoto && $photoRelPath !== '') {
          @mysqli_query($conn, "update tblstudents set photo='".mysqli_real_escape_string($conn, $photoRelPath)."' where Id='".intval($newId)."'");
        }

        if (!$okPhoto) {
          $statusMsg = "<div id='flashMsg' class='alert alert-danger flash-msg' data-toast='1'>".htmlspecialchars($photoErr)."</div>";
        } else {
          $statusMsg = "<div id='flashMsg' class='alert alert-success flash-msg' data-toast='1'>Created Successfully!</div>";
        }
            
    } else {
         $statusMsg = "<div id='flashMsg' class='alert alert-danger flash-msg' data-toast='1'>An error Occurred!</div>";
    }
    }
  }
}

//---------------------------------------EDIT-------------------------------------------------------------






//--------------------EDIT------------------------------------------------------------

 if (isset($_GET['Id']) && isset($_GET['action']) && $_GET['action'] == "edit")
	{
        $Id= $_GET['Id'];

        $query=mysqli_query($conn,"select * from tblstudents where Id ='$Id'");
        $row=mysqli_fetch_array($query);

        if (isset($row['classArmId']) && $row['classArmId'] != '') {
          $semId = intval($row['classArmId']);
          $semQ = mysqli_query($conn, "select * from tblclasssemister where Id = ".$semId." limit 1");
          $semRow = mysqli_fetch_array($semQ);
          if (is_array($semRow)) {
            $editSemRow['session'] = isset($semRow['session']) ? $semRow['session'] : '';
            $editSemRow['division'] = isset($semRow['division']) ? $semRow['division'] : '';
            $editSemRow['syllabusType'] = isset($semRow['syllabusType']) ? $semRow['syllabusType'] : '';
          }
        }

        if (isset($row['emailAddress']) && $row['emailAddress'] !== '') {
          $_SESSION['email_verified'] = $row['emailAddress'];
          $sessionEmailVerified = $row['emailAddress'];
        }

        //------------UPDATE-----------------------------

        if(isset($_POST['update'])){
    
             $firstName=$_POST['firstName'];
  $lastName=$_POST['lastName'];
  $otherName=$_POST['otherName'];

  $admissionNumber=$_POST['admissionNumber'];
  $classId=$_POST['classId'];
  $classArmId=$_POST['classArmId'];
  $emailAddress = isset($_POST['emailAddress']) ? trim($_POST['emailAddress']) : '';
  $phoneNo = isset($_POST['phoneNo']) ? preg_replace('/\s+/', '', $_POST['phoneNo']) : '';
  $dateCreated = date("Y-m-d");

  $existingRel = isset($row['photo']) ? $row['photo'] : '';
  $reqErr = validateStudentRequiredFields(true, $existingRel);
  if ($reqErr !== '') {
      $statusMsg = $reqErr;
  } else if ($emailAddress === '' || !filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
      $statusMsg = "<div id='flashMsg' class='alert alert-danger flash-msg' data-toast='1'>Email Address is required.</div>";
  } else if (!isset($_SESSION['email_verified']) || strtolower($_SESSION['email_verified']) !== strtolower($emailAddress)) {
      $statusMsg = "<div id='flashMsg' class='alert alert-danger flash-msg' data-toast='1'>Email not valid.</div>";
  } else {
   
  $query=mysqli_query($conn,"update tblstudents set firstName='$firstName', lastName='$lastName',
     otherName='$otherName', admissionNumber='$admissionNumber', emailAddress='$emailAddress', phoneNo='$phoneNo', password='12345', classId='$classId',classArmId='$classArmId'
     where Id='$Id'");
             if ($query) {
                 list($okPhoto, $photoRelPath, $photoErr) = saveStudentPhotoUpload($Id, $existingRel);
                 if ($okPhoto && $photoRelPath !== '' && $photoRelPath !== $existingRel) {
                   @mysqli_query($conn, "update tblstudents set photo='".mysqli_real_escape_string($conn, $photoRelPath)."' where Id='".intval($Id)."'");
                 }

                 if (!$okPhoto) {
                   $statusMsg = "<div id='flashMsg' class='alert alert-danger flash-msg' data-toast='1'>".htmlspecialchars($photoErr)."</div>";
                 } else {
                   header('Location: createStudents.php?status=updated');
                   exit;
                 }
             }
             else
             {
                 $statusMsg = "<div id='flashMsg' class='alert alert-danger flash-msg' data-toast='1'>An error Occurred!</div>";
             }
  }
        }
    }


//--------------------------------DELETE------------------------------------------------------------------

  if (isset($_GET['Id']) && isset($_GET['action']) && $_GET['action'] == "delete")
	{
        $Id= $_GET['Id'];
        $classArmId= $_GET['classArmId'];

        $query = mysqli_query($conn,"DELETE FROM tblstudents WHERE Id='$Id'");

        if ($query == TRUE) {
            header('Location: createStudents.php?status=deleted');
            exit;
        }
        else{

            $statusMsg = "<div id='flashMsg' class='alert alert-danger flash-msg' data-toast='1'>An error Occurred!</div>"; 
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
            <h1 class="h3 mb-0 text-gray-800">Create Students</h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">Create Students</li>
            </ol>
          </div>

          <div class="row">
            <div class="col-lg-12">
              <!-- Form Basic -->
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">Create Students</h6>
                    <?php echo $statusMsg; ?>
                </div>
                <div class="card-body">
                  <form method="post" enctype="multipart/form-data">
                    <div class="form-row">
                      <div class="form-group col-12 col-md-6 mb-3">
                        <label class="form-control-label">First Name<span class="text-danger ml-2">*</span></label>
                        <input type="text" class="form-control" required name="firstName" value="<?php echo $row['firstName'];?>" id="exampleInputFirstName">
                      </div>
                      <div class="form-group col-12 col-md-6 mb-3">
                        <label class="form-control-label">Father Name<span class="text-danger ml-2">*</span></label>
                        <input type="text" class="form-control" required name="otherName" value="<?php echo $row['otherName'];?>" id="exampleInputFirstName">
                      </div>

                      <div class="form-group col-12 col-md-6 mb-3">
                        <label class="form-control-label">Last Name<span class="text-danger ml-2">*</span></label>
                        <input type="text" class="form-control" required name="lastName" value="<?php echo $row['lastName'];?>" id="exampleInputFirstName">
                      </div>
                      <div class="form-group col-12 col-md-6 mb-3">
                        <label class="form-control-label">Email Address<span class="text-danger ml-2">*</span></label>
                        <input type="email" class="form-control" required name="emailAddress" id="emailAddress" value="<?php echo isset($row['emailAddress']) ? $row['emailAddress'] : ''; ?>" >
                        <div class="mt-2" id="emailValidateWrap" style="display:none;">
                          <button type="button" class="btn btn-sm btn-outline-primary" id="btnEmailSendOtp">Validate</button>
                          <small class="form-text text-muted" id="emailStatus"></small>
                        </div>
                        <div class="mt-2" id="emailOtpWrap" style="display:none;">
                          <input type="text" class="form-control" id="emailOtp" placeholder="Enter Email OTP">
                          <div class="mt-2">
                            <button type="button" class="btn btn-sm btn-outline-success" id="btnEmailVerifyOtp">Verify OTP</button>
                            <small class="form-text" id="emailVerifyStatus"></small>
                          </div>
                        </div>
                      </div>

                      <div class="form-group col-12 col-md-6 mb-3">
                        <label class="form-control-label">Registration Number<span class="text-danger ml-2">*</span></label>
                        <input type="text" class="form-control" required name="admissionNumber" value="<?php echo $row['admissionNumber'];?>" id="exampleInputFirstName" oninvalid="this.setCustomValidity('Registration Number is required.')" oninput="this.setCustomValidity('')" >
                      </div>
                      <div class="form-group col-12 col-md-6 mb-3">
                        <label class="form-control-label">Phone Number<span class="text-danger ml-2">*</span></label>
                        <input type="text" class="form-control" required name="phoneNo" id="phoneNo" value="<?php echo isset($row['phoneNo']) ? $row['phoneNo'] : ''; ?>" oninvalid="this.setCustomValidity('Phone Number is required.')" oninput="this.setCustomValidity('')" >
                      </div>

                      <div class="form-group col-12 col-md-6 mb-3">
                        <label class="form-control-label">Upload Photo<span class="text-danger ml-2">*</span></label>
                        <input type="file" class="form-control" name="studentPhoto" accept="image/*" oninvalid="this.setCustomValidity('Upload Photo is required.')" oninput="this.setCustomValidity('')">
                      </div>

                      <div class="w-100"></div>

                      <div class="form-group col-12 col-md-4 mb-3">
                        <label class="form-control-label">Session<span class="text-danger ml-2">*</span></label>
                        <?php
                            $sessQry = "SELECT DISTINCT session FROM tblclasssemister WHERE session <> '' ORDER BY session DESC";
                            $sessResult = $conn->query($sessQry);
                            $sessNum = $sessResult ? $sessResult->num_rows : 0;
                            if ($sessNum > 0) {
                                echo '<select name="session" required class="form-control" oninvalid="this.setCustomValidity(\'Session is required.\')" oninput="this.setCustomValidity(\'\')">';
                                echo '<option value="">--Select Session--</option>';
                                while ($sessRow = $sessResult->fetch_assoc()) {
                                    $sval = $sessRow['session'];
                                    $ssel = ($editSemRow['session'] !== '' && $editSemRow['session'] === $sval) ? ' selected' : '';
                                    echo '<option value="'.$sval.'"'.$ssel.'>'.$sval.'</option>';
                                }
                                echo '</select>';
                            } else {
                                echo '<select name="session" required class="form-control" oninvalid="this.setCustomValidity(\'Session is required.\')" oninput="this.setCustomValidity(\'\')">';
                                echo '<option value="">--No Session Defined--</option>';
                                echo '</select>';
                            }
                        ?>
                      </div>
                      <div class="form-group col-12 col-md-4 mb-3">
                        <label class="form-control-label">Class<span class="text-danger ml-2">*</span></label>
                        <?php
                        $qry= "SELECT * FROM tblclass ORDER BY className ASC";
                        $result = $conn->query($qry);
                        $num = $result->num_rows;		
                        if ($num > 0){
                          echo ' <select required name="classId" onchange="classArmDropdown(this.value)" class="form-control" oninvalid="this.setCustomValidity(\'Class is required.\')" oninput="this.setCustomValidity(\'\')">';
                          echo'<option value="">--Select Class--</option>';
                          while ($rows = $result->fetch_assoc()){
                          $sel = (isset($row['classId']) && $row['classId'] == $rows['Id']) ? ' selected' : '';
                          echo'<option value="'.$rows['Id'].'"'.$sel.' >'.$rows['className'].'</option>';
                              }
                                  echo '</select>';
                              }
                            ?>
                      </div>
                      <div class="form-group col-12 col-md-4 mb-3">
                        <label class="form-control-label">Syllabus Type</label>
                        <select name="syllabusType" required class="form-control" oninvalid="this.setCustomValidity('Syllabus Type is required.')" oninput="this.setCustomValidity('')">
                            <option value="">--Select Syllabus Type--</option>
                            <option value="SEP" <?php echo ($editSemRow['syllabusType'] === 'SEP') ? 'selected' : ''; ?>>SEP</option>
                            <option value="NEP" <?php echo ($editSemRow['syllabusType'] === 'NEP') ? 'selected' : ''; ?>>NEP</option>
                        </select>
                      </div>

                      <div class="form-group col-12 col-md-6 mb-3">
                        <label class="form-control-label">Semester<span class="text-danger ml-2">*</span></label>
                        <?php
                            if (isset($row['classId']) && $row['classId'] != '') {
                              $cid = intval($row['classId']);
                              $semQry = mysqli_query($conn,"select * from tblclasssemister where classId=".$cid." ORDER BY semisterName ASC");
                              echo "<div id='txtHint'><select required name='classArmId' class='form-control' oninvalid=\"this.setCustomValidity('Semester is required.')\" oninput=\"this.setCustomValidity('')\">";
                              echo "<option value=''>--Select Semester--</option>";
                              while ($srow = mysqli_fetch_array($semQry)) {
                                $ssel = (isset($row['classArmId']) && $row['classArmId'] == $srow['Id']) ? ' selected' : '';
                                echo "<option value='".$srow['Id']."'".$ssel." >".$srow['semisterName']."</option>";
                              }
                              echo "</select></div>";
                            } else {
                              echo "<div id='txtHint'><select required class='form-control' oninvalid=\"this.setCustomValidity('Semester is required.')\" oninput=\"this.setCustomValidity('')\"><option value=''>--Select Class First--</option></select></div>";
                            }
                        ?>
                      </div>
                      <div class="form-group col-12 col-md-6 mb-3">
                        <label class="form-control-label">Division<span class="text-danger ml-2">*</span></label>
                        <select name="division" required class="form-control" oninvalid="this.setCustomValidity('Division is required.')" oninput="this.setCustomValidity('')">
                            <option value="Not Applicable" <?php echo ($editSemRow['division'] === 'Not Applicable' || $editSemRow['division'] === '') ? 'selected' : ''; ?>>Not Applicable</option>
                            <option value="A" <?php echo ($editSemRow['division'] === 'A') ? 'selected' : ''; ?>>A</option>
                            <option value="B" <?php echo ($editSemRow['division'] === 'B') ? 'selected' : ''; ?>>B</option>
                            <option value="C" <?php echo ($editSemRow['division'] === 'C') ? 'selected' : ''; ?>>C</option>
                            <option value="D" <?php echo ($editSemRow['division'] === 'D') ? 'selected' : ''; ?>>D</option>
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
                  <h6 class="m-0 font-weight-bold text-primary">All Student</h6>
                </div>
                <div class="table-responsive p-3">
                  <table class="table align-items-center table-flush table-hover" id="dataTableHover">
                    <thead class="thead-light">
                      <tr>
                        <th>#</th>
                        <th>First Name</th>
                        <th>Father Name</th>
                        <th>Last Name</th>
                        <th>Email Address</th>
                        <th>Phone Number</th>
                        <th>Registration Number</th>
                        <th>Class</th>
                        <th>Semester</th>
                        <th>Session</th>
                        <th>Division</th>
                        <th>Syllabus Type</th>
                        <th>Date Created</th>
                        <th>Photo</th>
                        <th>Edit</th>
                        <th>Delete</th>
                      </tr>
                    </thead>
                
                    <tbody>

                  <?php
                      $query = "SELECT 
                                tblstudents.Id,
                                tblclass.className,
                                tblclasssemister.semisterName,
                                tblclasssemister.Id AS classArmId,
                                tblclasssemister.session,
                                tblclasssemister.division,
                                tblclasssemister.syllabusType,
                                tblstudents.firstName,
                                tblstudents.lastName,
                                tblstudents.otherName,
                                tblstudents.admissionNumber,
                                tblstudents.emailAddress,
                                tblstudents.phoneNo,
                                tblstudents.photo,
                                tblstudents.dateCreated
                              FROM tblstudents
                              INNER JOIN tblclass ON tblclass.Id = tblstudents.classId
                              INNER JOIN tblclasssemister ON tblclasssemister.Id = tblstudents.classArmId";
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
                                <td>".$sn."</td>
                                <td>".$rows['firstName']."</td>
                                <td>".$rows['otherName']."</td>
                                <td>".$rows['lastName']."</td>
                                <td>".$rows['emailAddress']."</td>
                                <td>".$rows['phoneNo']."</td>
                                <td>".$rows['admissionNumber']."</td>
                                <td>".$rows['className']."</td>
                                <td>".$rows['semisterName']."</td>
                                <td>".$rows['session']."</td>
                                <td>".$rows['division']."</td>
                                <td>".$rows['syllabusType']."</td>
                                <td>".$rows['dateCreated']."</td>
                                <td>".(($rows['photo'] ?? '') !== '' ? "<img src='../".htmlspecialchars($rows['photo'])."' alt='Photo' style='width:40px;height:40px;object-fit:cover;border-radius:4px;'>" : "")."</td>
                                <td><a href='?action=edit&Id=".$rows['Id']."'><i class='fas fa-fw fa-edit'></i></a></td>
                                <td><a href='?action=delete&Id=".$rows['Id']."&classArmId=".$rows['classArmId']."'><i class='fas fa-fw fa-trash'></i></a></td>
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

      if ($('.flash-msg').length) {
        setTimeout(function () {
          $('.flash-msg').fadeOut('fast');
        }, 3000);
      }

      var emailVerified = <?php echo json_encode($sessionEmailVerified); ?>;
      var isEditMode = <?php echo isset($Id) ? 'true' : 'false'; ?>;
      var hasExistingPhoto = <?php echo (isset($row['photo']) && $row['photo'] !== '') ? 'true' : 'false'; ?>;

      if (!isEditMode || !hasExistingPhoto) {
        $('input[name="studentPhoto"]').prop('required', true);
      }

      function setText($el, text, cls) {
        $el.text(text);
        $el.removeClass('text-danger text-success text-muted');
        if (cls) $el.addClass(cls);
      }

      function updateEmailUi() {
        var email = ($('#emailAddress').val() || '').trim();
        if (email === '') {
          $('#emailValidateWrap').hide();
          $('#emailOtpWrap').hide();
          return;
        }
        $('#emailValidateWrap').show();
        if (emailVerified && emailVerified.toLowerCase() === email.toLowerCase()) {
          setText($('#emailStatus'), 'Valid', 'text-success');
          $('#emailOtpWrap').hide();
        }
      }

      $('#emailAddress').on('input', function () {
        emailVerified = '';
        $('#emailOtp').val('');
        setText($('#emailStatus'), '', 'text-muted');
        setText($('#emailVerifyStatus'), '', 'text-muted');
        $('#emailOtpWrap').hide();
        updateEmailUi();
      });

      $('#btnEmailSendOtp').on('click', function () {
        var email = ($('#emailAddress').val() || '').trim();
        if (email === '') return;
        setText($('#emailStatus'), 'Sending OTP...', 'text-muted');
        $.post('ajaxContactOtp.php', { action: 'send_email_otp', email: email }, function (res) {
          if (res && res.ok) {
            setText($('#emailStatus'), res.message, 'text-success');
            $('#emailOtpWrap').show();
          } else {
            setText($('#emailStatus'), (res && res.message) ? res.message : 'Failed to send OTP.', 'text-danger');
            $('#emailOtpWrap').hide();
          }
        }, 'json');
      });

      $('#btnEmailVerifyOtp').on('click', function () {
        var email = ($('#emailAddress').val() || '').trim();
        var otp = ($('#emailOtp').val() || '').trim();
        if (email === '' || otp === '') return;
        setText($('#emailVerifyStatus'), 'Verifying...', 'text-muted');
        $.post('ajaxContactOtp.php', { action: 'verify_email_otp', email: email, otp: otp }, function (res) {
          if (res && res.ok) {
            emailVerified = email;
            setText($('#emailVerifyStatus'), 'Valid', 'text-success');
            setText($('#emailStatus'), 'Valid', 'text-success');
            $('#emailOtpWrap').hide();
          } else {
            setText($('#emailVerifyStatus'), (res && res.message) ? res.message : 'Wrong OTP', 'text-danger');
          }
        }, 'json');
      });

      updateEmailUi();
    });

    function classArmDropdown(str) {
    if (str == "") {
        document.getElementById("txtHint").innerHTML = "";
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