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

//--------------------------------DELETE------------------------------------------------------------------

  if (isset($_GET['Id']) && isset($_GET['action']) && $_GET['action'] == "delete")
	{
        $Id= $_GET['Id'];

        $query = mysqli_query($conn,"DELETE FROM tblstudents WHERE Id='$Id'");

        if ($query == TRUE) {
            header('Location: createStudents.php?status=deleted');
            exit;
        }
        else {
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
            <h1 class="h3 mb-0 text-gray-800">Manage Students</h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">Manage Students</li>
            </ol>
          </div>

          <div class="row">
            <div class="col-lg-12">
              <?php echo $statusMsg; ?>
              
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">All Registered Students</h6>
                </div>
                <div class="table-responsive table-cards p-3">
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
                        <th>Department</th>
                        <th>Semester</th>
                        <th>Session</th>
                        <th>Division</th>
                        <th>Syllabus Type</th>
                        <th>Date Created</th>
                        <th>Photo</th>
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
                              LEFT JOIN tblclass ON tblclass.Id = tblstudents.classId
                              LEFT JOIN tblclasssemister ON tblclasssemister.Id = tblstudents.classArmId";
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
                                <td data-label=\"Father Name\">".$rows['otherName']."</td>
                                <td data-label=\"Last Name\">".$rows['lastName']."</td>
                                <td data-label=\"Email Address\">".$rows['emailAddress']."</td>
                                <td data-label=\"Phone\">".$rows['phoneNo']."</td>
                                <td data-label=\"Reg No\">".$rows['admissionNumber']."</td>
                                <td data-label=\"Department\">".($rows['className'] ?? 'N/A')."</td>
                                <td data-label=\"Semester\">".($rows['semisterName'] ?? 'N/A')."</td>
                                <td data-label=\"Session\">".($rows['session'] ?? 'N/A')."</td>
                                <td data-label=\"Division\">".($rows['division'] ?? 'N/A')."</td>
                                <td data-label=\"Syllabus\">".($rows['syllabusType'] ?? 'N/A')."</td>
                                <td data-label=\"Date\">".$rows['dateCreated']."</td>
                                <td data-label=\"Photo\">".(($rows['photo'] ?? '') !== '' ? "<img src='../".htmlspecialchars($rows['photo'])."' alt='Photo' style='width:40px;height:40px;object-fit:cover;border-radius:4px;'>" : "")."</td>
                                <td data-label=\"Action\"><a href='?action=delete&Id=".$rows['Id']."' onclick='return confirm(\"Are you sure you want to delete this student permanently?\")'><i class='fas fa-fw fa-trash text-danger'></i></a></td>
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

      $('#studentPhotoInput').on('change', function (e) {
        var file = e.target.files[0];
        var $error = $('#fileSizeError');
        var $preview = $('#imagePreview');
        
        if (file) {
          // Validate Size (2MB = 2097152 bytes)
          if (file.size > 2 * 1024 * 1024) {
             $error.text('File is too large! Please choose an image smaller than 2MB.').show();
             $(this).val(''); // Clear the input
             $preview.hide();
             return;
          }
          
          $error.hide();
          var reader = new FileReader();
          reader.onload = function(re) {
            $preview.attr('src', re.target.result).fadeIn();
          }
          reader.readAsDataURL(file);
        }
      });
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