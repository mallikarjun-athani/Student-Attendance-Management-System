<?php
include '../Includes/dbcon.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$statusMsg = '';

$sessionEmailVerified = isset($_SESSION['email_verified']) ? $_SESSION['email_verified'] : '';

function ensureStudentsExtraColumns($conn) {
  $cols = ['emailAddress' => "ALTER TABLE tblstudents ADD COLUMN emailAddress VARCHAR(255) NULL AFTER admissionNumber",
           'phoneNo' => "ALTER TABLE tblstudents ADD COLUMN phoneNo VARCHAR(50) NULL AFTER emailAddress",
           'photo' => "ALTER TABLE tblstudents ADD COLUMN photo VARCHAR(255) NULL AFTER dateCreated",
           'session' => "ALTER TABLE tblstudents ADD COLUMN session VARCHAR(50) NULL AFTER classArmId",
           'division' => "ALTER TABLE tblstudents ADD COLUMN division VARCHAR(20) NULL AFTER session",
           'syllabusType' => "ALTER TABLE tblstudents ADD COLUMN syllabusType VARCHAR(20) NULL AFTER division"]; 

  foreach ($cols as $col => $sql) {
    $rs = mysqli_query($conn, "SHOW COLUMNS FROM tblstudents LIKE '".mysqli_real_escape_string($conn, $col)."'");
    if (!$rs || mysqli_num_rows($rs) === 0) {
      @mysqli_query($conn, $sql);
    }
  }
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

ensureStudentsExtraColumns($conn);

// Load classes for dropdown
$classes = [];
$qry = $conn->query("SELECT * FROM tblclass ORDER BY className ASC");
if ($qry) {
  while ($r = $qry->fetch_assoc()) {
    $classes[] = $r;
  }
}

// Load sessions for dropdown (from tblclasssemister)
$sessions = [];
$sessQ = $conn->query("SELECT DISTINCT session FROM tblclasssemister WHERE session <> '' ORDER BY session DESC");
if ($sessQ) {
  while ($sr = $sessQ->fetch_assoc()) {
    $sessions[] = $sr['session'];
  }
}

// Helper: semesters for selected class
$selectedClassId = isset($_POST['classId']) ? intval($_POST['classId']) : 0;
$semesters = [];

// Check if user is logged in as student to pre-fill form
$isEditing = false;
$editData = [];
if (isset($_SESSION['studentId']) && $_SESSION['userType'] === 'Student') {
    $isEditing = true;
    $stmtEdit = $conn->prepare("SELECT * FROM tblstudents WHERE Id = ? LIMIT 1");
    $stmtEdit->bind_param('i', $_SESSION['studentId']);
    $stmtEdit->execute();
    $resEdit = $stmtEdit->get_result();
    if ($resEdit && $resEdit->num_rows > 0) {
        $editData = $resEdit->fetch_assoc();
        if ($selectedClassId === 0) {
            $selectedClassId = intval($editData['classId']);
        }
    }
    $stmtEdit->close();
}

if ($selectedClassId > 0) {
  $semQ = $conn->query("SELECT * FROM tblclasssemister WHERE classId = ".$selectedClassId." ORDER BY semisterName ASC");
  if ($semQ) {
    while ($sr = $semQ->fetch_assoc()) {
      $semesters[] = $sr;
    }
  }
}

if (isset($_POST['register'])) {
  $firstName = isset($_POST['firstName']) ? trim($_POST['firstName']) : '';
  $lastName = isset($_POST['lastName']) ? trim($_POST['lastName']) : '';
  $otherName = isset($_POST['otherName']) ? trim($_POST['otherName']) : '';
  $admissionNumber = isset($_POST['admissionNumber']) ? trim($_POST['admissionNumber']) : '';
  $emailAddress = isset($_POST['emailAddress']) ? trim($_POST['emailAddress']) : '';
  $phoneNo = isset($_POST['phoneNo']) ? preg_replace('/\s+/', '', $_POST['phoneNo']) : '';
  $classId = isset($_POST['classId']) ? intval($_POST['classId']) : 0;
  $classArmId = isset($_POST['classArmId']) ? intval($_POST['classArmId']) : 0;
  $session = isset($_POST['session']) ? trim($_POST['session']) : '';
  $syllabusType = isset($_POST['syllabusType']) ? trim($_POST['syllabusType']) : '';
  $division = isset($_POST['division']) ? trim($_POST['division']) : '';
  $passwordRaw = isset($_POST['password']) ? $_POST['password'] : '';
  $password2 = isset($_POST['password2']) ? $_POST['password2'] : '';

  $isPhotoUploaded = (isset($_FILES['studentPhoto']) && isset($_FILES['studentPhoto']['error']) && $_FILES['studentPhoto']['error'] !== UPLOAD_ERR_NO_FILE);
  $isEmailChanged = ($isEditing && strtolower($emailAddress) !== strtolower($editData['emailAddress'] ?? ''));

  if ($firstName === '' || $lastName === '' || $otherName === '' || $admissionNumber === '' || $emailAddress === '' || $phoneNo === '' || $classId <= 0 || $classArmId <= 0 || $session === '' || $syllabusType === '' || $division === '' || ($passwordRaw === '' && !$isEditing)) {
    $statusMsg = "<div class='alert alert-danger' role='alert'>All fields are required.</div>";
  } else if (strlen($admissionNumber) !== 12) {
    $statusMsg = "<div class='alert alert-danger' role='alert'>Registration Number must be exactly 12 characters.</div>";
  } else if (!preg_match('/^\d{10}$/', $phoneNo)) {
    $statusMsg = "<div class='alert alert-danger' role='alert'>Phone Number must be exactly 10 digits.</div>";
  } else if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
    $statusMsg = "<div class='alert alert-danger' role='alert'>Invalid email address.</div>";
  } else if (!$isEditing && (!isset($_SESSION['email_verified']) || strtolower($_SESSION['email_verified']) !== strtolower($emailAddress))) {
    $statusMsg = "<div class='alert alert-danger' role='alert'>Email verification is required.</div>";
  } else if ($isEmailChanged && (!isset($_SESSION['email_verified']) || strtolower($_SESSION['email_verified']) !== strtolower($emailAddress))) {
    $statusMsg = "<div class='alert alert-danger' role='alert'>Email verification is required for the new email address.</div>";
  } else if ($passwordRaw !== '' && $passwordRaw !== $password2) {
    $statusMsg = "<div class='alert alert-danger' role='alert'>Passwords do not match.</div>";
  } else if (!$isEditing && !$isPhotoUploaded) {
    $statusMsg = "<div class='alert alert-danger' role='alert'>Photo upload is required.</div>";
  } else {
    // Duplicate check within same semester
    $stmt = $conn->prepare("SELECT Id, emailAddress FROM tblstudents WHERE admissionNumber = ? AND classArmId = ? LIMIT 1");
    $stmt->bind_param('si', $admissionNumber, $classArmId);
    $stmt->execute();
    $dupRes = $stmt->get_result();
    $existingStudent = $dupRes->fetch_assoc();

    if ($existingStudent && strtolower($existingStudent['emailAddress']) !== strtolower($emailAddress)) {
      $statusMsg = "<div class='alert alert-danger' role='alert'>This registration number is already taken by another student in this semester!</div>";
    } else {
      $dateCreated = date('Y-m-d');
      $passwordHash = ($passwordRaw !== '') ? md5($passwordRaw) : ($editData['password'] ?? '');

      if ($isEditing) {
        // UPDATE existing record for current logged-in user
        $studentId = $_SESSION['studentId'];
        $stmt2 = $conn->prepare("UPDATE tblstudents SET firstName=?, lastName=?, otherName=?, admissionNumber=?, emailAddress=?, phoneNo=?, password=?, classId=?, classArmId=?, session=?, division=?, syllabusType=? WHERE Id=?");
        $stmt2->bind_param('sssssssiisssi', $firstName, $lastName, $otherName, $admissionNumber, $emailAddress, $phoneNo, $passwordHash, $classId, $classArmId, $session, $division, $syllabusType, $studentId);
      } else if ($existingStudent) {
        // Handle potential duplication if admission number exists for another student
        $statusMsg = "<div class='alert alert-danger' role='alert'>This registration number is already taken!</div>";
        goto end_processing;
      } else {
        // INSERT new record
        $stmt2 = $conn->prepare("INSERT INTO tblstudents(firstName,lastName,otherName,admissionNumber,emailAddress,phoneNo,password,classId,classArmId,session,division,syllabusType,dateCreated) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt2->bind_param('sssssssiissss', $firstName, $lastName, $otherName, $admissionNumber, $emailAddress, $phoneNo, $passwordHash, $classId, $classArmId, $session, $division, $syllabusType, $dateCreated);
      }

      if (isset($stmt2) && $stmt2->execute()) {
        $studentId = $isEditing ? $_SESSION['studentId'] : $conn->insert_id;
        
        // Handle photo upload
        list($okPhoto, $photoRelPath, $photoErr) = saveStudentPhotoUpload($studentId, $isEditing ? ($editData['photo'] ?? '') : '');
        if ($okPhoto && $photoRelPath !== '') {
          @mysqli_query($conn, "UPDATE tblstudents SET photo='".mysqli_real_escape_string($conn, $photoRelPath)."' WHERE Id='".intval($studentId)."'");
        }

        if (!$okPhoto) {
          $statusMsg = "<div class='alert alert-danger' role='alert'>".htmlspecialchars($photoErr)."</div>";
        } else {
          if ($isEditing) {
            header('Location: index.php?status=profile_updated');
          } else {
            $successParam = $existingStudent ? 'updated' : 'registered';
            header('Location: login.php?status='.$successParam);
          }
          exit;
        }
      } else {
        $statusMsg = "<div class='alert alert-danger' role='alert'>An error occurred while saving. Please try again.</div>";
      }

      end_processing:
      if (isset($stmt2)) {
        $stmt2->close();
      }
    }

    if (isset($stmt)) {
      $stmt->close();
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link href="../img/logo/attnlg.jpg" rel="icon">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="../Admin/css/ruang-admin.min.css" rel="stylesheet">
  <link href="../Admin/css/premium-admin.css" rel="stylesheet">
  <title><?php echo $isEditing ? 'Edit Profile' : 'Student Registration'; ?></title>
  <style>
    .container-login {
      width: 100%;
      max-width: 1100px;
      margin-left: auto !important;
      margin-right: auto !important;
      padding-left: 40px !important;
      padding-right: 40px !important;
    }

    @media (max-width: 991.98px) {
      .container-login {
        max-width: 90%;
        padding-left: 30px !important;
        padding-right: 30px !important;
      }
    }

    @media (max-width: 575.98px) {
      .container-login {
        max-width: 100%;
        padding-left: 20px !important;
        padding-right: 20px !important;
      }
    }

    #otpInvalidPopup { display:none; position:fixed; top:20px; right:20px; z-index:9999; }

    .req-star { color: #e3342f; }
  </style>
</head>

<body id="page-top" class="animate-fade-up" style="min-height: 100vh; display: flex; align-items: center; background: var(--bg-main);">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-xl-9 col-lg-11">
        <div class="card border-0 shadow-lg my-5" style="border-radius: 30px; overflow: hidden;">
          <div class="row no-gutters">
            <!-- Branding Sidebar (Desktop) -->
            <div class="col-lg-4 d-none d-lg-flex align-items-center justify-content-center p-5 text-white" style="background: var(--primary-gradient);">
                <div class="text-center">
                    <div class="icon-box mx-auto mb-4" style="width: 100px; height: 100px; background: rgba(255,255,255,0.2); border-radius: 30px; backdrop-filter: blur(10px);">
                         <i class="fas fa-user-graduate fa-3x"></i>
                    </div>
                    <h2 class="font-weight-bold mb-3">Join Us</h2>
                    <p class="opacity-75 small">Create your student account to access your attendance and academic records anywhere.</p>
                    <div class="mt-5">
                        <a href="login.php" class="btn btn-light btn-sm px-4" style="border-radius: 12px; color: var(--primary); font-weight: 700;">Sign In</a>
                    </div>
                </div>
            </div>

            <!-- Registration Form -->
            <div class="col-lg-8">
              <div class="card-body p-4 p-md-5">
                <div class="text-center mb-5 d-lg-none">
                    <img src="../Admin/img/logo/attnlg.jpg" style="width: 70px; border-radius: 12px; margin-bottom: 15px;">
                    <h3 class="font-weight-bold text-dark"><?php echo $isEditing ? 'Edit Profile' : 'Student Registration'; ?></h3>
                </div>
                
                <div class="d-none d-lg-block mb-5">
                    <h3 class="font-weight-bold text-dark mb-1"><?php echo $isEditing ? 'Update Profile' : 'Get Started'; ?></h3>
                    <p class="text-muted small"><?php echo $isEditing ? 'Modify your personal information below.' : 'Please fill in your details to create an account.'; ?></p>
                </div>

                <?php echo $statusMsg; ?>

                <form method="post" enctype="multipart/form-data">
                  <div class="form-group row">
                    <div class="col-md-6 mb-3 mb-md-0">
                      <label class="font-weight-bold small text-uppercase">First Name</label>
                      <input type="text" class="form-control form-control-lg border-0 bg-light" required name="firstName" value="<?php echo isset($_POST['firstName']) ? htmlspecialchars($_POST['firstName']) : (isset($editData['firstName']) ? htmlspecialchars($editData['firstName']) : ''); ?>" style="border-radius: 12px;">
                    </div>
                    <div class="col-md-6">
                      <label class="font-weight-bold small text-uppercase">Father's Name</label>
                      <input type="text" class="form-control form-control-lg border-0 bg-light" required name="otherName" value="<?php echo isset($_POST['otherName']) ? htmlspecialchars($_POST['otherName']) : (isset($editData['otherName']) ? htmlspecialchars($editData['otherName']) : ''); ?>" style="border-radius: 12px;">
                    </div>
                  </div>

                  <div class="form-group row">
                    <div class="col-md-6 mb-3 mb-md-0">
                      <label class="font-weight-bold small text-uppercase">Last Name</label>
                      <input type="text" class="form-control form-control-lg border-0 bg-light" required name="lastName" value="<?php echo isset($_POST['lastName']) ? htmlspecialchars($_POST['lastName']) : (isset($editData['lastName']) ? htmlspecialchars($editData['lastName']) : ''); ?>" style="border-radius: 12px;">
                    </div>
                    <div class="col-md-6">
                      <label class="font-weight-bold small text-uppercase">Email Address</label>
                      <input type="email" class="form-control form-control-lg border-0 bg-light" required name="emailAddress" id="emailAddress" value="<?php echo isset($_POST['emailAddress']) ? htmlspecialchars($_POST['emailAddress']) : (isset($editData['emailAddress']) ? htmlspecialchars($editData['emailAddress']) : ''); ?>" style="border-radius: 12px;">
                      
                      <div class="mt-2" id="emailValidateWrap" style="display:none;">
                        <button type="button" class="btn btn-sm btn-primary py-1" id="btnEmailSendOtp">Send OTP</button>
                        <small class="form-text text-muted" id="emailStatus"></small>
                      </div>
                      
                      <div class="mt-2" id="emailOtpWrap" style="display:none;">
                        <div class="input-group">
                            <input type="text" class="form-control form-control-sm border-0 bg-light" id="emailOtp" placeholder="Enter OTP">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-success btn-sm px-3" id="btnEmailVerifyOtp">Verify</button>
                            </div>
                        </div>
                        <small class="form-text" id="emailVerifyStatus"></small>
                      </div>
                    </div>
                  </div>

                  <div class="form-group row">
                    <div class="col-md-6 mb-3 mb-md-0">
                      <label class="font-weight-bold small text-uppercase">Registration Number</label>
                      <input type="text" class="form-control form-control-lg border-0 bg-light" required name="admissionNumber" id="admissionNumber" minlength="12" maxlength="12" placeholder="12 digit ID" value="<?php echo isset($_POST['admissionNumber']) ? htmlspecialchars($_POST['admissionNumber']) : (isset($editData['admissionNumber']) ? htmlspecialchars($editData['admissionNumber']) : ''); ?>" style="border-radius: 12px;" <?php echo $isEditing ? 'readonly' : ''; ?>>
                    </div>
                    <div class="col-md-6">
                      <label class="font-weight-bold small text-uppercase">Phone Number</label>
                      <input type="text" class="form-control form-control-lg border-0 bg-light" required name="phoneNo" id="phoneNo" minlength="10" maxlength="10" inputmode="numeric" pattern="\d{10}" value="<?php echo isset($_POST['phoneNo']) ? htmlspecialchars($_POST['phoneNo']) : (isset($editData['phoneNo']) ? htmlspecialchars($editData['phoneNo']) : ''); ?>" style="border-radius: 12px;">
                    </div>
                  </div>

                  <div class="form-group row">
                    <div class="col-md-6 mb-3 mb-md-0">
                      <label class="font-weight-bold small text-uppercase">Password <?php echo $isEditing ? '(Leave blank to keep current)' : ''; ?></label>
                      <input type="password" class="form-control form-control-lg border-0 bg-light" <?php echo $isEditing ? '' : 'required'; ?> name="password" id="password" style="border-radius: 12px;">
                    </div>
                    <div class="col-md-6">
                      <label class="font-weight-bold small text-uppercase">Confirm Password</label>
                      <input type="password" class="form-control form-control-lg border-0 bg-light" <?php echo $isEditing ? '' : 'required'; ?> name="password2" id="password2" style="border-radius: 12px;">
                    </div>
                  </div>

                  <div class="form-group row">
                    <div class="col-md-12 mb-3">
                      <label class="font-weight-bold small text-uppercase">Academic Info</label>
                      <div class="row">
                          <div class="col-4">
                              <select name="session" class="form-control border-0 bg-light" required style="border-radius: 10px;">
                                <option value="">Session</option>
                                <?php foreach ($sessions as $sess) { 
                                  $selSess = (isset($_POST['session']) && $_POST['session'] === $sess) || (isset($editData['session']) && $editData['session'] === $sess) ? 'selected' : '';
                                  echo '<option value="'.htmlspecialchars($sess).'" '.$selSess.'>'.htmlspecialchars($sess).'</option>'; 
                                } ?>
                              </select>
                          </div>
                          <div class="col-4">
                              <select name="syllabusType" class="form-control border-0 bg-light" required style="border-radius: 10px;">
                                <option value="">Syllabus</option>
                                <?php 
                                  $currSyl = isset($_POST['syllabusType']) ? $_POST['syllabusType'] : ($editData['syllabusType'] ?? '');
                                  echo '<option value="SEP" '.($currSyl === 'SEP' ? 'selected' : '').'>SEP</option>';
                                  echo '<option value="NEP" '.($currSyl === 'NEP' ? 'selected' : '').'>NEP</option>';
                                ?>
                              </select>
                          </div>
                          <div class="col-4">
                              <select name="division" class="form-control border-0 bg-light" required style="border-radius: 10px;">
                                <option value="Not Applicable" <?php echo (isset($_POST['division']) ? $_POST['division'] : ($editData['division'] ?? '')) === 'Not Applicable' ? 'selected' : ''; ?>>Not Applicable</option>
                                <?php 
                                  foreach(['A','B','C','D'] as $div) {
                                    $selDiv = (isset($_POST['division']) ? $_POST['division'] : ($editData['division'] ?? '')) === $div ? 'selected' : '';
                                    echo '<option value="'.$div.'" '.$selDiv.'>'.$div.'</option>';
                                  }
                                ?>
                              </select>
                          </div>
                      </div>
                    </div>
                  </div>

                  <div class="form-group row">
                    <div class="col-md-6 mb-3 mb-md-0">
                      <label class="font-weight-bold small text-uppercase">Class</label>
                      <select name="classId" class="form-control border-0 bg-light" required onchange="this.form.submit()" style="border-radius: 10px;">
                        <option value="">--Select--</option>
                        <?php foreach ($classes as $c) {
                          $sel = ($selectedClassId === intval($c['Id'])) ? 'selected' : '';
                          echo '<option value="'.intval($c['Id']).'" '.$sel.'>'.htmlspecialchars($c['className']).'</option>';
                        } ?>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label class="font-weight-bold small text-uppercase">Semester</label>
                      <select name="classArmId" class="form-control border-0 bg-light" required style="border-radius: 10px;">
                        <option value="">--Select--</option>
                        <?php foreach ($semesters as $s) {
                          $currSem = isset($_POST['classArmId']) ? intval($_POST['classArmId']) : (isset($editData['classArmId']) ? intval($editData['classArmId']) : 0);
                          $ssel = ($currSem === intval($s['Id'])) ? 'selected' : '';
                          echo '<option value="'.intval($s['Id']).'" '.$ssel.'>'.htmlspecialchars($s['semisterName']).'</option>';
                        } ?>
                      </select>
                    </div>
                  </div>

                  <div class="form-group mb-5">
                    <label class="font-weight-bold small text-uppercase">Profile Photo <?php echo $isEditing ? '(Leave blank to keep current)' : ''; ?></label>
                    <div class="custom-file mb-3">
                      <input type="file" class="custom-file-input" name="studentPhoto" accept="image/*" <?php echo $isEditing ? '' : 'required'; ?> id="customFile">
                      <label class="custom-file-label border-0 bg-light" for="customFile" style="border-radius: 12px;">Choose image...</label>
                    </div>
                    <div class="text-center">
                        <?php 
                          $preImg = isset($editData['photo']) && $editData['photo'] != '' ? '../'.$editData['photo'] : '#';
                          $imgDisp = isset($editData['photo']) && $editData['photo'] != '' ? 'block' : 'none';
                        ?>
                        <img id="imagePreview" src="<?php echo $preImg; ?>" alt="Preview" style="display:<?php echo $imgDisp; ?>; width: 120px; height: 120px; object-fit: cover; border-radius: 20px; border: 3px solid var(--primary-light); box-shadow: 0 10px 20px rgba(0,0,0,0.1);">
                    </div>
                  </div>

                  <button type="submit" name="register" id="btnRegister" class="btn btn-primary btn-block py-3" <?php echo $isEditing ? '' : 'disabled'; ?>><?php echo $isEditing ? 'Save Changes' : 'Complete Registration'; ?></button>
                  
                  <div class="text-center mt-4">
                    <p class="text-muted small">Already registered? <a href="login.php" class="text-primary font-weight-bold">Sign In</a></p>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="../vendor/jquery/jquery.min.js"></script>
  <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
  <script src="../js/ruang-admin.min.js"></script>
  <script>
    $(document).ready(function () {
      var emailVerified = <?php echo json_encode($sessionEmailVerified); ?>;

      function setText($el, text, cls) {
        $el.text(text);
        $el.removeClass('text-danger text-success text-muted');
        if (cls) $el.addClass(cls);
      }

      function showInvalidOtpPopup() {
        $('#otpInvalidPopup').stop(true, true).show();
        setTimeout(function () {
          $('#otpInvalidPopup').fadeOut('fast');
        }, 3000);
      }

      function updateEmailUi() {
        var email = ($('#emailAddress').val() || '').trim();
        if (email === '') {
          $('#emailValidateWrap').hide();
          $('#emailOtpWrap').hide();
          $('#btnRegister').prop('disabled', true);
          return;
        }
        $('#emailValidateWrap').show();

        if (emailVerified && emailVerified.toLowerCase() === email.toLowerCase()) {
          setText($('#emailStatus'), 'Valid', 'text-success');
          setText($('#emailVerifyStatus'), 'Valid', 'text-success');
          $('#emailOtpWrap').hide();
          $('#btnRegister').prop('disabled', false);
        } else {
          $('#btnRegister').prop('disabled', true);
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
          updateEmailUi();
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
            showInvalidOtpPopup();
            setText($('#emailVerifyStatus'), (res && res.message) ? res.message : 'Wrong OTP', 'text-danger');
          }
          updateEmailUi();
        }, 'json');
      });

      function validateEmailFormat() {
        var email = ($('#emailAddress').val() || '').trim();
        if (email === '') {
          $('#emailAddress').removeClass('is-valid is-invalid');
          return;
        }

        // Simple & correct rule: has one @ with text before/after, and ends with a dot-domain of 2+ chars
        // Examples: name@site.com, name@site.in, name@university.edu
        var re = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
        if (re.test(email)) {
          markValid($('#emailAddress'));
        } else {
          markInvalid($('#emailAddress'));
        }
      }

      function markValid($el) {
        $el.removeClass('is-invalid').addClass('is-valid');
      }

      function markInvalid($el) {
        $el.removeClass('is-valid').addClass('is-invalid');
      }

      function validateAdmissionNumber() {
        var v = ($('#admissionNumber').val() || '').trim();
        if (v.length === 0) {
          $('#admissionNumber').removeClass('is-valid is-invalid');
          return;
        }
        if (v.length === 12) {
          markValid($('#admissionNumber'));
        } else {
          markInvalid($('#admissionNumber'));
        }
      }

      function validatePhone() {
        var raw = ($('#phoneNo').val() || '').replace(/\s+/g, '');
        if (raw.length === 0) {
          $('#phoneNo').removeClass('is-valid is-invalid');
          return;
        }
        if (/^\d{10}$/.test(raw)) {
          markValid($('#phoneNo'));
        } else {
          markInvalid($('#phoneNo'));
        }
      }

      function validatePasswords() {
        var p1 = ($('#password').val() || '');
        var p2 = ($('#password2').val() || '');

        if (p1.length === 0 && p2.length === 0) {
          $('#password').removeClass('is-valid is-invalid');
          $('#password2').removeClass('is-valid is-invalid');
          return;
        }

        if (p1 !== '' && p2 !== '' && p1 === p2) {
          markValid($('#password'));
          markValid($('#password2'));
        } else {
          if (p1 !== '') markInvalid($('#password'));
          if (p2 !== '') markInvalid($('#password2'));
        }
      }

      $('#admissionNumber').on('input', validateAdmissionNumber);
      $('#password, #password2').on('input', validatePasswords);
      $('#emailAddress').on('input', validateEmailFormat);
      $('#phoneNo').on('input', validatePhone);

      validateAdmissionNumber();
      validatePasswords();
      validateEmailFormat();
      validatePhone();

      updateEmailUi();

      $("#customFile").on("change", function(e) {
        var fileName = e.target.files[0].name;
        $(this).next(".custom-file-label").html(fileName);
        
        if (e.target.files && e.target.files[0]) {
          var reader = new FileReader();
          reader.onload = function(re) {
            $("#imagePreview").attr("src", re.target.result).fadeIn();
          };
          reader.readAsDataURL(e.target.files[0]);
        }
      });
    });
  </script>
</body>
</html>
