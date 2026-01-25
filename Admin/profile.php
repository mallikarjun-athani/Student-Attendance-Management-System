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

    $stmt = $conn->prepare("UPDATE tbladmin SET firstName=?, lastName=?, emailAddress=? WHERE Id=?");
    $stmt->bind_param('sssi', $firstName, $lastName, $emailAddress, $userId);
    if ($stmt->execute()) {
        $statusMsg = "<div class='alert alert-success' data-toast='1'>Profile updated successfully!</div>";
        // Refresh local data
        $admin['firstName'] = $firstName;
        $admin['lastName'] = $lastName;
        $admin['emailAddress'] = $emailAddress;
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
            <h1 class="h3 mb-0 text-gray-800">Admin Profile</h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">Profile</li>
            </ol>
          </div>

          <div class="row">
            <div class="col-lg-4 text-center">
              <div class="card mb-4 py-4">
                <div class="card-body">
                   <div class="position-relative d-inline-block mb-3">
                        <img src="img/user-icn.png" class="img-profile rounded-circle-xl" style="width: 150px; height: 150px; border: 5px solid var(--primary-glow); object-fit: cover;">
                   </div>
                   <h5 class="font-weight-bold mb-1"><?php echo htmlspecialchars($admin['firstName']." ".$admin['lastName']); ?></h5>
                   <p class="text-muted small">System Administrator</p>
                   <div class="badge-pro bg-soft-purple text-purple px-4 py-2" style="border-radius: 12px; font-weight: 700;">Full Access</div>
                </div>
              </div>
            </div>

            <div class="col-lg-8">
              <div class="card mb-4 shadow-sm border-0">
                <div class="card-header py-3">
                  <h6 class="m-0 font-weight-bold text-primary">Personal Details</h6>
                </div>
                <div class="card-body">
                  <?php echo $statusMsg; ?>
                  <form method="post">
                    <div class="form-row">
                      <div class="form-group col-md-6 mb-3">
                        <label class="form-control-label">First Name</label>
                        <input type="text" class="form-control" name="firstName" value="<?php echo htmlspecialchars($admin['firstName']); ?>" required>
                      </div>
                      <div class="form-group col-md-6 mb-3">
                        <label class="form-control-label">Last Name</label>
                        <input type="text" class="form-control" name="lastName" value="<?php echo htmlspecialchars($admin['lastName']); ?>" required>
                      </div>
                    </div>
                    <div class="form-group mb-4">
                      <label class="form-control-label">Email Address</label>
                      <input type="email" class="form-control" name="emailAddress" value="<?php echo htmlspecialchars($admin['emailAddress']); ?>" required>
                    </div>
                    <button type="submit" name="updateProfile" class="btn btn-primary px-5 py-3">Update Profile</button>
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
</body>
</html>
