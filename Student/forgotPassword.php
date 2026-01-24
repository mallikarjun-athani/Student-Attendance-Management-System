<?php
include '../Includes/dbcon.php';
include '../Includes/mailer_config.php'; // For using the mailer

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$statusMsg = '';

if (isset($_POST['reset_password'])) {
  $email = isset($_POST['email']) ? trim($_POST['email']) : '';

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $statusMsg = "<div class='alert alert-danger' role='alert'>Please enter a valid email address.</div>";
  } else {
    $stmt = $conn->prepare("SELECT Id FROM tblstudents WHERE emailAddress = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows > 0) {
      // Email exists, generate token and send email
      $token = bin2hex(random_bytes(32));
      $expires = new DateTime('NOW');
      $expires->add(new DateInterval('PT15M')); // 15 minute expiry

      $stmt_insert = $conn->prepare("INSERT INTO tblpassword_resets (email, token, expires_at) VALUES (?, ?, ?)");
      $stmt_insert->bind_param('sss', $email, $token, $expires->format('Y-m-d H:i:s'));
      
      if ($stmt_insert->execute()) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $basePath = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\'); // Go up one level
        $resetLink = $scheme . '://' . $host . $basePath . '/Student/resetPassword.php?token=' . $token;

        $subject = "Password Reset Request";
        $body = "
          <p>Hello,</p>
          <p>You requested a password reset. Click the link below to reset your password. This link is valid for 15 minutes.</p>
          <p><a href='{$resetLink}'>Reset Password</a></p>
          <p>If you did not request this, please ignore this email.</p>
        ";

        list($ok, $msg) = sendSmtpMail($email, 'Student', $subject, $body, true);
        
        if ($ok) {
          $statusMsg = "<div class='alert alert-success' role='alert'>A password reset link has been sent to your email.</div>";
        } else {
          $statusMsg = "<div class='alert alert-danger' role='alert'>Could not send email. Please try again later. Error: " . htmlspecialchars($msg) . "</div>";
        }
      } else {
        $statusMsg = "<div class='alert alert-danger' role='alert'>Could not generate reset link. Please try again.</div>";
      }
      $stmt_insert->close();
    } else {
      $statusMsg = "<div class='alert alert-danger' role='alert'>This email ID is not registered.</div>";
    }
    $stmt->close();
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
  <title>Forgot Password</title>
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
                    <h1 class="h4 text-gray-900 mb-4">Forgot Password</h1>
                  </div>

                  <?php echo $statusMsg; ?>

                  <form class="user" method="post" action="">
                    <div class="form-group">
                      <input type="email" class="form-control" required name="email" placeholder="Enter Your Registered Email Address">
                    </div>
                    <div class="form-group">
                      <input type="submit" class="btn btn-primary btn-block" value="Send Reset Link" name="reset_password" />
                    </div>
                  </form>

                  <div class="text-center mt-3">
                    <a class="small" href="login.php">Back to Login</a>
                  </div>
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
</body>
</html>
