<?php
include '../Includes/dbcon.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$statusMsg = '';
$token = isset($_GET['token']) ? $_GET['token'] : '';
$isValidToken = false;
$email = '';

if ($token === '') {
  $statusMsg = "<div class='alert alert-danger' role='alert'>Invalid password reset link.</div>";
} else {
  $stmt = $conn->prepare("SELECT email, expires_at, is_used FROM tblpassword_resets WHERE token = ?");
  $stmt->bind_param('s', $token);
  $stmt->execute();
  $res = $stmt->get_result();

  if ($res && $res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $expires = new DateTime($row['expires_at']);
    $now = new DateTime('NOW');

    if ($row['is_used'] == 1) {
      $statusMsg = "<div class='alert alert-danger' role='alert'>This link has already been used.</div>";
    } else if ($now > $expires) {
      $statusMsg = "<div class='alert alert-danger' role='alert'>This link has expired.</div>";
    } else {
      $isValidToken = true;
      $email = $row['email'];
    }
  } else {
    $statusMsg = "<div class='alert alert-danger' role='alert'>Invalid password reset link.</div>";
  }
  $stmt->close();
}

if ($isValidToken && isset($_POST['update_password'])) {
  $password = isset($_POST['password']) ? $_POST['password'] : '';
  $password2 = isset($_POST['password2']) ? $_POST['password2'] : '';

  // Password validation
  $uppercase = preg_match('@[A-Z]@', $password);
  $lowercase = preg_match('@[a-z]@', $password);
  $number    = preg_match('@[0-9]@', $password);
  $specialChars = preg_match('@[^\w]@', $password);

  if (strlen($password) < 8 || !$uppercase || !$lowercase || !$number || !$specialChars) {
    $statusMsg = "<div class='alert alert-danger' role='alert'>Password must be at least 8 characters long and contain at least one uppercase letter, one number, and one special character.</div>";
  } else if ($password !== $password2) {
    $statusMsg = "<div class='alert alert-danger' role='alert'>Passwords do not match.</div>";
  } else {
    $passwordHash = md5($password);
    
    $stmt_update = $conn->prepare("UPDATE tblstudents SET password = ? WHERE emailAddress = ?");
    $stmt_update->bind_param('ss', $passwordHash, $email);

    if ($stmt_update->execute()) {
      $stmt_expire = $conn->prepare("UPDATE tblpassword_resets SET is_used = 1 WHERE token = ?");
      $stmt_expire->bind_param('s', $token);
      $stmt_expire->execute();
      $stmt_expire->close();

      $statusMsg = "<div class='alert alert-success' role='alert'>Password reset successful. You can now <a href='login.php'>login</a> with your new password.</div>";
      $isValidToken = false; // Hide form after successful reset
    } else {
      $statusMsg = "<div class='alert alert-danger' role='alert'>Could not update password. Please try again.</div>";
    }
    $stmt_update->close();
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
  <title>Reset Password</title>
  <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="../css/ruang-admin.min.css" rel="stylesheet">
  <link href="../css/site-theme.css" rel="stylesheet">
  <link href="../css/mobile.css" rel="stylesheet">
</head>

<body class="bg-gradient-login" style="background-image: url('../img/logo/loral1.jpe00g');">
  <div class="container-login">
    <div class="row justify-content-center">
      <div class="col-xl-8 col-lg-10 col-md-9">
        <div class="card shadow-sm my-5">
          <div class="card-body p-0">
            <div class="row">
              <div class="col-lg-12">
                <div class="login-form">
                  <div class="text-center">
                    <img src="../img/logo/attnlg.jpg" style="width:100px;height:100px">
                    <br><br>
                    <h1 class="h4 text-gray-900 mb-4">Reset Password</h1>
                  </div>

                  <?php echo $statusMsg; ?>

                  <?php if ($isValidToken) : ?>
                    <form class="user" method="post" action="">
                      <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                      <div class="form-group">
                        <label for="password">New Password</label>
                        <input type="password" class="form-control" required name="password" id="password" placeholder="Enter New Password">
                      </div>
                      <div class="form-group">
                        <label for="password2">Confirm New Password</label>
                        <input type="password" class="form-control" required name="password2" id="password2" placeholder="Confirm New Password">
                      </div>
                      <div id="password-strength-status"></div>
                      <div class="form-group">
                        <input type="submit" class="btn btn-primary btn-block" value="Update Password" name="update_password" />
                      </div>
                    </form>
                  <?php else : ?>
                    <div class="text-center mt-3">
                      <a class="small" href="login.php">Back to Login</a>
                    </div>
                  <?php endif; ?>
                </div>
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
    // Password strength checker
    $(document).ready(function() {
      $('#password').on('keyup', function() {
        var password = $(this).val();
        var strength = 0;
        if (password.length >= 8) strength++;
        if (password.match(/([a-z].*[A-Z])|([A-Z].*[a-z])/)) strength++;
        if (password.match(/([0-9])/)) strength++;
        if (password.match(/([!,%,&,@,#,$,^,*,?,_,~])/)) strength++;

        var status = '';
        var color = '';
        if (strength < 2) {
          status = 'Weak';
          color = 'red';
        } else if (strength == 2) {
          status = 'Medium';
          color = 'orange';
        } else if (strength >= 3) {
          status = 'Strong';
          color = 'green';
        }
        $('#password-strength-status').html('<small style="color:' + color + '">Password strength: ' + status + '</small>');
      });
    });
  </script>
</body>
</html>
