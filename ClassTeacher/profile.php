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
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'Includes/header.php'; ?>
<body id="page-top">
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
              <div class="card mb-4 text-center">
                <div class="card-body">
                  <div class="position-relative d-inline-block mb-3">
                    <?php
                    // Assume teacher photo path is stored in a column 'photo' if added, 
                    // or just check if it exists in uploads folder as teacher_{id}.jpg/png
                    $photoPath = 'img/user-icn.png'; // default
                    $possibleExts = ['jpg', 'png', 'webp'];
                    foreach ($possibleExts as $ext) {
                        if (file_exists("uploads/teacher_{$userId}.{$ext}")) {
                            $photoPath = "uploads/teacher_{$userId}.{$ext}?v=".time();
                            break;
                        }
                    }
                    ?>
                    <img id="profilePreview" src="<?php echo $photoPath; ?>" class="img-profile rounded-circle-xl" style="width: 150px; height: 150px; border: 5px solid var(--primary-glow); object-fit: cover;">
                    <div class="upload-btn-wrapper" style="position: absolute; bottom: 5px; right: 5px;">
                      <button class="btn btn-sm btn-primary rounded-circle shadow-sm" style="width: 40px; height: 40px;"><i class="fas fa-camera"></i></button>
                      <input type="file" id="profilePhotoInput" accept="image/*" style="position: absolute; top: 0; left: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;">
                    </div>
                  </div>
                  <h5 class="font-weight-bold mb-0"><?php echo htmlspecialchars($teacher['firstName']." ".$teacher['lastName']); ?></h5>
                  <p class="text-muted small mb-3">Class Teacher</p>
                  <div class="badge-pro bg-soft-blue text-primary px-3 py-2" style="border-radius: 12px; font-weight: 700;"><?php echo htmlspecialchars($teacher['className'] ?? 'N/A'); ?></div>
                </div>
              </div>

              <!-- Quick Stats Card -->
              <div class="card mb-4 overflow-hidden">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Academic Assignment</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-soft-purple text-purple rounded-lg p-3 mr-3"><i class="fas fa-university"></i></div>
                        <div>
                            <div class="text-xs font-weight-bold text-uppercase text-muted">Assigned Class</div>
                            <div class="h6 mb-0 font-weight-bold"><?php echo htmlspecialchars($teacher['className'] ?? 'None'); ?></div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="bg-soft-green text-green rounded-lg p-3 mr-3"><i class="fas fa-layer-group"></i></div>
                        <div>
                            <div class="text-xs font-weight-bold text-uppercase text-muted">Current Semester</div>
                            <div class="h6 mb-0 font-weight-bold"><?php echo htmlspecialchars($teacher['semisterName'] ?? 'None'); ?></div>
                        </div>
                    </div>
                </div>
              </div>
            </div>

            <div class="col-lg-8">
              <div class="card mb-4 shadow-sm border-0">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">Update Profile Information</h6>
                </div>
                <div class="card-body">
                  <?php echo $statusMsg; ?>
                  <form method="post">
                    <div class="form-row">
                      <div class="form-group col-md-6 mb-3">
                        <label class="form-control-label">First Name</label>
                        <input type="text" class="form-control" name="firstName" value="<?php echo htmlspecialchars($teacher['firstName']); ?>" required>
                      </div>
                      <div class="form-group col-md-6 mb-3">
                        <label class="form-control-label">Last Name</label>
                        <input type="text" class="form-control" name="lastName" value="<?php echo htmlspecialchars($teacher['lastName']); ?>" required>
                      </div>
                    </div>
                    <div class="form-group mb-3">
                      <label class="form-control-label">Email Address</label>
                      <input type="email" class="form-control" name="emailAddress" value="<?php echo htmlspecialchars($teacher['emailAddress']); ?>" required>
                    </div>
                    <div class="form-group mb-4">
                      <label class="form-control-label">Phone Number</label>
                      <input type="text" class="form-control" name="phoneNo" value="<?php echo htmlspecialchars($teacher['phoneNo']); ?>" required>
                    </div>
                    <button type="submit" name="updateProfile" class="btn btn-primary px-5 py-3">Save Changes</button>
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
      $('#profilePhotoInput').on('change', function() {
        var formData = new FormData();
        var files = $(this)[0].files;
        if (files.length > 0) {
          formData.append('photo', files[0]);
          $.ajax({
            url: 'uploadProfilePhoto.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
              if (response.ok) {
                $('#profilePreview').attr('src', response.url);
                if (window.showToast) window.showToast('Profile photo updated!', 'success');
              } else {
                alert(response.message);
              }
            }
          });
        }
      });
    });
  </script>
</body>
</html>
