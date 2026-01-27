<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';

$userId = intval($_SESSION['userId']);
$teacher = null;

$stmt = $conn->prepare("SELECT t.*, c.className, cs.semisterName 
    FROM tblclassteacher t
    LEFT JOIN tblclass c ON c.Id = t.classId
    LEFT JOIN tblclasssemister cs ON cs.Id = t.classArmId
    WHERE t.Id = ? LIMIT 1");
$stmt->bind_param('i', $userId);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows > 0) {
    $teacher = $res->fetch_assoc();
}
$stmt->close();

$statusMsg = '';
if (isset($_POST['updateProfile'])) {
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);
    $emailAddress = trim($_POST['emailAddress']);
    $phoneNo = trim($_POST['phoneNo']);

    $stmt = $conn->prepare("UPDATE tblclassteacher SET firstName=?, lastName=?, emailAddress=?, phoneNo=? WHERE Id=?");
    $stmt->bind_param('ssssi', $firstName, $lastName, $emailAddress, $phoneNo, $userId);
    if ($stmt->execute()) {
        $statusMsg = "<div class='alert alert-success' data-toast='1'>Profile updated successfully!</div>";
        // Refresh local teacher data
        $teacher['firstName'] = $firstName;
        $teacher['lastName'] = $lastName;
        $teacher['emailAddress'] = $emailAddress;
        $teacher['phoneNo'] = $phoneNo;
    } else {
        $statusMsg = "<div class='alert alert-danger' data-toast='1'>Failed to update profile.</div>";
    }
    $stmt->close();
}

// Handle Password Change
if (isset($_POST['changePassword'])) {
    $currentPass = $_POST['currentPassword'];
    $newPass = $_POST['newPassword'];
    $confirmPass = $_POST['confirmPassword'];

    $currentPassHashed = md5($currentPass);

    if ($currentPassHashed != $teacher['password']) {
        $statusMsg = "<div class='alert alert-danger' data-toast='1'>Current password is incorrect!</div>";
    } elseif ($newPass != $confirmPass) {
        $statusMsg = "<div class='alert alert-danger' data-toast='1'>New passwords do not match!</div>";
    } else {
        $newPassHashed = md5($newPass);
        $stmt = $conn->prepare("UPDATE tblclassteacher SET password=?, plainPassword=? WHERE Id=?");
        $stmt->bind_param('ssi', $newPassHashed, $newPass, $userId);
        if ($stmt->execute()) {
            $statusMsg = "<div class='alert alert-success' data-toast='1'>Password updated successfully!</div>";
            $teacher['password'] = $newPassHashed; // update local cache
        } else {
            $statusMsg = "<div class='alert alert-danger' data-toast='1'>Failed to update password.</div>";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'Includes/header.php'; ?>
<body id="page-top" class="animate-fade-up">
  <div id="wrapper">
    <?php include "Includes/sidebar.php"; ?>
    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">
        <?php include "Includes/topbar.php"; ?>

        <div class="container-fluid" id="container-wrapper">
          <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">My Profile</h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">Profile</li>
            </ol>
          </div>

          <div class="row">
            <div class="col-lg-4">
              <!-- Profile Photo Card -->
              <div class="card mb-4 text-center border-0 shadow-sm" style="border-radius: 20px;">
                <div class="card-body py-5">
                  <div class="position-relative d-inline-block mb-4">
                    <?php
                    $photoPath = 'img/user-icn.png'; // default
                    $possibleExts = ['jpg', 'png', 'webp'];
                    $hasCustomPhoto = false;
                    foreach ($possibleExts as $ext) {
                        if (file_exists("uploads/teacher_{$userId}.{$ext}")) {
                            $photoPath = "uploads/teacher_{$userId}.{$ext}?v=".time();
                            $hasCustomPhoto = true;
                            break;
                        }
                    }
                    ?>
                    <div class="profile-img-container" style="position: relative;">
                        <img id="profilePreview" src="<?php echo $photoPath; ?>" class="img-profile rounded-circle" style="width: 160px; height: 160px; border: 6px solid #f8fafc; box-shadow: 0 10px 25px rgba(0,0,0,0.1); object-fit: cover;">
                        <div class="upload-options" style="position: absolute; bottom: 5px; right: 5px; display: flex; gap: 8px;">
                            <button class="btn btn-primary rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;" onclick="document.getElementById('profilePhotoInput').click()" title="Upload New Photo">
                                <i class="fas fa-camera"></i>
                            </button>
                            <input type="file" id="profilePhotoInput" accept="image/*" style="display: none;">
                            
                            <button id="removePhotoBtn" class="btn btn-danger rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; <?php echo $hasCustomPhoto ? '' : 'display:none;'; ?>" title="Remove Photo">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                  </div>
                  <h4 class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars($teacher['firstName']." ".$teacher['lastName']); ?></h4>
                  <p class="text-muted mb-3"><?php echo htmlspecialchars($teacher['emailAddress']); ?></p>
                  <div class="badge-pro bg-primary text-white px-4 py-2 shadow-sm" style="border-radius: 12px; font-weight: 700;">Department Teacher</div>
                </div>
              </div>

              <!-- Assignment Info -->
              <div class="card mb-4 border-0 shadow-sm overflow-hidden" style="border-radius: 20px;">
                <div class="card-header bg-white border-0 py-4">
                    <h6 class="m-0 font-weight-bold text-dark"><i class="fas fa-briefcase mr-2 text-primary"></i> Current Assignment</h6>
                </div>
                <div class="card-body pt-0">
                    <div class="d-flex align-items-start mb-4">
                        <div class="bg-soft-indigo text-indigo flex-shrink-0 rounded-xl p-3 mr-3" style="background: rgba(99, 102, 241, 0.1); color: #6366f1; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;"><i class="fas fa-university"></i></div>
                        <div>
                            <div class="text-xs font-weight-bold text-uppercase text-muted mb-1" style="letter-spacing: 0.5px;">Department</div>
                            <div class="h6 mb-0 font-weight-bold text-dark"><?php echo htmlspecialchars($teacher['className'] ?? 'None'); ?></div>
                        </div>
                    </div>
                    <div class="d-flex align-items-start">
                        <div class="bg-soft-orange text-orange flex-shrink-0 rounded-xl p-3 mr-3" style="background: rgba(249, 115, 22, 0.1); color: #f97316; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;"><i class="fas fa-calendar-alt"></i></div>
                        <div>
                            <div class="text-xs font-weight-bold text-uppercase text-muted mb-1" style="letter-spacing: 0.5px;">Semester</div>
                            <div class="h6 mb-0 font-weight-bold text-dark"><?php echo htmlspecialchars($teacher['semisterName'] ?? 'None'); ?></div>
                        </div>
                    </div>
                </div>
              </div>
            </div>

            <div class="col-lg-8">
              <!-- Info Update Card -->
              <div class="card mb-4 border-0 shadow-sm" style="border-radius: 20px;">
                <div class="card-header bg-white border-0 py-4 d-flex align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-dark"><i class="fas fa-user-edit mr-2 text-primary"></i> Personal Information</h6>
                </div>
                <div class="card-body">
                  <?php if(isset($_POST['updateProfile'])) echo $statusMsg; ?>
                  <form method="post">
                    <div class="row">
                      <div class="col-md-6 mb-3">
                        <label class="font-weight-bold small text-uppercase text-muted">First Name</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-light border-right-0" style="border-radius: 12px 0 0 12px;"><i class="fas fa-user text-muted"></i></span>
                            </div>
                            <input type="text" class="form-control bg-light border-left-0" style="border-radius: 0 12px 12px 0; height: 48px;" name="firstName" value="<?php echo htmlspecialchars($teacher['firstName']); ?>" required>
                        </div>
                      </div>
                      <div class="col-md-6 mb-3">
                        <label class="font-weight-bold small text-uppercase text-muted">Last Name</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-light border-right-0" style="border-radius: 12px 0 0 12px;"><i class="fas fa-user text-muted"></i></span>
                            </div>
                            <input type="text" class="form-control bg-light border-left-0" style="border-radius: 0 12px 12px 0; height: 48px;" name="lastName" value="<?php echo htmlspecialchars($teacher['lastName']); ?>" required>
                        </div>
                      </div>
                    </div>
                    <div class="row">
                      <div class="col-md-6 mb-3">
                        <label class="font-weight-bold small text-uppercase text-muted">Email Address</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-light border-right-0" style="border-radius: 12px 0 0 12px;"><i class="fas fa-envelope text-muted"></i></span>
                            </div>
                            <input type="email" class="form-control bg-light border-left-0" style="border-radius: 0 12px 12px 0; height: 48px;" name="emailAddress" value="<?php echo htmlspecialchars($teacher['emailAddress']); ?>" required>
                        </div>
                      </div>
                      <div class="col-md-6 mb-4">
                        <label class="font-weight-bold small text-uppercase text-muted">Phone Number</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-light border-right-0" style="border-radius: 12px 0 0 12px;"><i class="fas fa-phone text-muted"></i></span>
                            </div>
                            <input type="text" class="form-control bg-light border-left-0" style="border-radius: 0 12px 12px 0; height: 48px;" name="phoneNo" value="<?php echo htmlspecialchars($teacher['phoneNo']); ?>" required>
                        </div>
                      </div>
                    </div>
                    <button type="submit" name="updateProfile" class="btn btn-primary px-5 font-weight-bold" style="border-radius: 14px; height: 50px;">Update Information</button>
                  </form>
                </div>
              </div>

              <!-- Password Update Card -->
              <div class="card mb-4 border-0 shadow-sm" style="border-radius: 20px;">
                <div class="card-header bg-white border-0 py-4">
                  <h6 class="m-0 font-weight-bold text-dark"><i class="fas fa-lock mr-2 text-primary"></i> Change Password</h6>
                </div>
                <div class="card-body">
                  <?php if(isset($_POST['changePassword'])) echo $statusMsg; ?>
                  <form method="post">
                    <div class="mb-3">
                      <label class="font-weight-bold small text-uppercase text-muted">Current Password</label>
                      <input type="password" class="form-control bg-light" style="border-radius: 12px; height: 48px;" name="currentPassword" required placeholder="Enter current password">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold small text-uppercase text-muted">New Password</label>
                            <input type="password" class="form-control bg-light" style="border-radius: 12px; height: 48px;" name="newPassword" required placeholder="New password">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="font-weight-bold small text-uppercase text-muted">Confirm New Password</label>
                            <input type="password" class="form-control bg-light" style="border-radius: 12px; height: 48px;" name="confirmPassword" required placeholder="Confirm new password">
                        </div>
                    </div>
                    <button type="submit" name="changePassword" class="btn btn-indigo px-5 font-weight-bold" style="background: #6366f1; color: white; border-radius: 14px; height: 50px;">Security Update</button>
                  </form>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>
      <?php include "Includes/footer.php"; ?>
    </div>
  </div>

  <a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
  </a>

  <script src="../vendor/jquery/jquery.min.js"></script>
  <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
  <script src="js/ruang-admin.min.js"></script>

  <script>
    $(document).ready(function() {
      // Photo Upload handling
      $('#profilePhotoInput').on('change', function() {
        var formData = new FormData();
        var files = $(this)[0].files;
        if (files.length > 0) {
          var file = files[0];
          if (file.size > 2 * 1024 * 1024) {
            alert('File too large! Max 2MB.');
            $(this).val('');
            return;
          }

          formData.append('photo', file);
          $.ajax({
            url: 'uploadProfilePhoto.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
              if (response.ok) {
                location.reload(); // Refresh to update all UI parts
              } else {
                alert(response.message);
              }
            }
          });
        }
      });

      // Photo Removal handling
      $('#removePhotoBtn').on('click', function() {
        if(!confirm('Are you sure you want to remove your profile photo?')) return;
        
        $.ajax({
          url: 'uploadProfilePhoto.php',
          type: 'POST',
          data: { action: 'remove' },
          success: function(response) {
            if (response.ok) {
              location.reload();
            } else {
              alert(response.message);
            }
          }
        });
      });
    });
  </script>
</body>
</html>
