<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';

$userId = intval($_SESSION['userId']);
$admin = null;

$stmt = $conn->prepare("SELECT * FROM tbladmin WHERE Id = ? LIMIT 1");
$stmt->bind_param('i', $userId);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows > 0) {
    $admin = $res->fetch_assoc();
}
$stmt->close();

$statusMsg = '';
if (isset($_POST['updateProfile'])) {
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);
    $emailAddress = trim($_POST['emailAddress']);
    $password = trim($_POST['password']);

    if ($emailAddress === '') {
        $statusMsg = "<div class='alert alert-danger' data-toast='1'>Email Address is mandatory!</div>";
    } else {
        if ($password !== '') {
            $hashedPass = md5($password);
            $stmt = $conn->prepare("UPDATE tbladmin SET firstName=?, lastName=?, emailAddress=?, password=? WHERE Id=?");
            $stmt->bind_param('ssssi', $firstName, $lastName, $emailAddress, $hashedPass, $userId);
        } else {
            $stmt = $conn->prepare("UPDATE tbladmin SET firstName=?, lastName=?, emailAddress=? WHERE Id=?");
            $stmt->bind_param('sssi', $firstName, $lastName, $emailAddress, $userId);
        }

        if ($stmt->execute()) {
            $statusMsg = "<div class='alert alert-success' data-toast='1'>Profile updated successfully!</div>";
            // Refresh local data
            $admin['firstName'] = $firstName;
            $admin['lastName'] = $lastName;
            $admin['emailAddress'] = $emailAddress;
            // Update session if email changed
            $_SESSION['emailAddress'] = $emailAddress;
        } else {
            $statusMsg = "<div class='alert alert-danger' data-toast='1'>Failed to update profile.</div>";
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
            <h1 class="h3 mb-0 text-gray-800">Admin Profile</h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">Profile</li>
            </ol>
          </div>

          <div class="row">
            <div class="col-lg-4 text-center">
              <div class="card mb-4 border-0 shadow-sm py-5" style="border-radius: 20px;">
                <div class="card-body">
                   <div class="position-relative d-inline-block mb-4">
                        <?php
                        $photoPath = 'img/user-icn.png';
                        $hasCustomPhoto = false;
                        $dir = __DIR__ . '/../uploads';
                        $base = 'admin_' . $userId;
                        foreach (['jpg','png','jpeg','webp'] as $ext) {
                          if (file_exists($dir . '/' . $base . '.' . $ext)) {
                            $photoPath = 'uploads/' . $base . '.' . $ext . '?v=' . time();
                            $hasCustomPhoto = true;
                            break;
                          }
                        }
                        ?>
                        <div class="profile-img-container" style="position: relative;">
                            <img id="profilePreview" src="<?php echo $photoPath; ?>" class="img-profile rounded-circle" style="width: 160px; height: 160px; border: 6px solid #f8fafc; box-shadow: 0 10px 25px rgba(0,0,0,0.1); object-fit: cover;">
                            <div class="upload-options" style="position: absolute; bottom: 5px; right: 5px; display: flex; gap: 8px;">
                                <button class="btn btn-primary rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;" onclick="document.getElementById('profilePhotoInput').click()" title="Upload Photo">
                                    <i class="fas fa-camera"></i>
                                </button>
                                <input type="file" id="profilePhotoInput" accept="image/*" style="display: none;">
                                
                                <button id="removePhotoBtn" class="btn btn-danger rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; <?php echo $hasCustomPhoto ? '' : 'display:none;'; ?>" title="Remove Photo">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                   </div>
                   <h5 class="font-weight-bold text-dark mb-1">
                    <?php 
                      $dispName = trim($admin['firstName']." ".$admin['lastName']);
                      echo $dispName !== "" ? htmlspecialchars($dispName) : htmlspecialchars($admin['emailAddress']);
                    ?>
                   </h5>
                   <p class="text-muted small mb-3">System Administrator</p>
                   <div class="badge-pro bg-primary text-white px-4 py-2 shadow-sm" style="border-radius: 12px; font-weight: 700;">Full Access</div>
                </div>
              </div>
            </div>

            <div class="col-lg-8">
              <div class="card mb-4 shadow-sm border-0" style="border-radius: 20px;">
                <div class="card-header bg-white border-0 py-4">
                  <h6 class="m-0 font-weight-bold text-dark"><i class="fas fa-user-shield mr-2 text-primary"></i> Account Credentials</h6>
                </div>
                <div class="card-body">
                  <?php echo $statusMsg; ?>
                  <form method="post">
                    <div class="row">
                      <div class="col-md-6 mb-3">
                        <label class="font-weight-bold small text-uppercase text-muted">First Name (Optional)</label>
                        <input type="text" class="form-control bg-light" style="border-radius: 12px; height: 48px;" name="firstName" value="<?php echo htmlspecialchars($admin['firstName']); ?>">
                      </div>
                      <div class="col-md-6 mb-3">
                        <label class="font-weight-bold small text-uppercase text-muted">Last Name (Optional)</label>
                        <input type="text" class="form-control bg-light" style="border-radius: 12px; height: 48px;" name="lastName" value="<?php echo htmlspecialchars($admin['lastName']); ?>">
                      </div>
                    </div>
                    <div class="form-group mb-3">
                      <label class="font-weight-bold small text-uppercase text-muted">Email Address (Mandatory)</label>
                      <input type="email" class="form-control bg-light border-primary-soft" style="border-radius: 12px; height: 48px;" name="emailAddress" value="<?php echo htmlspecialchars($admin['emailAddress']); ?>" required>
                    </div>
                    <div class="form-group mb-4">
                      <label class="font-weight-bold small text-uppercase text-muted">New Password (Leave blank to keep current)</label>
                      <input type="password" class="form-control bg-light" style="border-radius: 12px; height: 48px;" name="password" placeholder="Enter new password to change">
                    </div>
                    <button type="submit" name="updateProfile" class="btn btn-primary px-5 font-weight-bold" style="border-radius: 14px; height: 50px;">Apply Changes</button>
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
                location.reload(); 
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
