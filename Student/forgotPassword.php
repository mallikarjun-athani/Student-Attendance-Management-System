<?php
include '../Includes/dbcon.php';
include '../Includes/mailer.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Ensure table exists on the server
$createTableQuery = "CREATE TABLE IF NOT EXISTS tblpassword_resets (
    Id INT AUTO_INCREMENT PRIMARY KEY, 
    email VARCHAR(191) NOT NULL, 
    token VARCHAR(255) NOT NULL, 
    expires_at DATETIME NOT NULL, 
    is_used TINYINT(1) DEFAULT 0, 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($createTableQuery);

// Fallback for random_bytes if PHP < 7.0
if (!function_exists('random_bytes')) {
    function random_bytes($length) {
        return openssl_random_pseudo_bytes($length);
    }
}

$statusMsg = '';
$emailSent = false;

if (isset($_POST['reset_password'])) {
  $email = isset($_POST['email']) ? trim($_POST['email']) : '';

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $statusMsg = "<div class='alert alert-danger mb-4' style='border-radius:12px; font-weight:600;'>Please enter a valid email address.</div>";
  } else {
    $stmt = $conn->prepare("SELECT firstName, lastName FROM tblstudents WHERE emailAddress = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows > 0) {
      $student = $res->fetch_assoc();
      $fullName = $student['firstName'] . ' ' . $student['lastName'];
      
      // Email exists, generate token and send email
      $token = bin2hex(random_bytes(32));
      $expires = new DateTime('NOW');
      $expires->add(new DateInterval('PT15M')); // 15 minute expiry

      $stmt_insert = $conn->prepare("INSERT INTO tblpassword_resets (email, token, expires_at) VALUES (?, ?, ?)");
      $formattedExpires = $expires->format('Y-m-d H:i:s');
      $stmt_insert->bind_param('sss', $email, $token, $formattedExpires);
      
      if ($stmt_insert->execute()) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $basePath = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
        $resetLink = $scheme . '://' . $host . $basePath . '/Student/resetPassword.php?token=' . $token;

        $subject = "Password Reset Request - SAMS Portal";
        $body = "
          <div style='font-family: sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
            <h2 style='color: #6366f1;'>Password Reset Request</h2>
            <p>Hello <strong>{$fullName}</strong>,</p>
            <p>We received a request to reset your password for your SAMS Account. Click the button below to set a new password. This link will expire in 15 minutes.</p>
            <div style='text-align: center; margin: 30px 0;'>
              <a href='{$resetLink}' style='background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;'>Reset My Password</a>
            </div>
            <p style='color: #777; font-size: 0.9em;'>If you did not request this, please ignore this email or contact support if you have concerns.</p>
            <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
            <p style='color: #999; font-size: 0.8em;'>SAMS - Student Attendance Management System</p>
          </div>
        ";

        list($ok, $msg) = sendSmtpMail($email, $fullName, $subject, $body, true);
        
        if ($ok) {
          $statusMsg = "<div class='alert alert-success mb-4' style='border-radius:12px; font-weight:600;'>A password reset link has been sent to your email. Check your inbox.</div>";
          $emailSent = true;
        } else {
          $statusMsg = "<div class='alert alert-danger mb-4' style='border-radius:12px; font-weight:600;'>Could not send email. Please try again later.</div>";
        }
      } else {
        $statusMsg = "<div class='alert alert-danger mb-4' style='border-radius:12px; font-weight:600;'>System error. Please try again.</div>";
      }
      $stmt_insert->close();
    } else {
      $statusMsg = "<div class='alert alert-danger mb-4' style='border-radius:12px; font-weight:600;'>This email is not registered. Please register first.</div>";
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
  <title>Forgot Password - SAMS</title>
  <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="../Admin/css/ruang-admin.min.css" rel="stylesheet">
  <link href="../Admin/css/premium-admin.css" rel="stylesheet">
  <style>
    body {
      background: var(--bg-main);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .forgot-card {
      background: #ffffff;
      border-radius: 30px;
      overflow: hidden;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(0,0,0,0.05);
      max-width: 500px;
      width: 100%;
    }
    .header-accent {
      background: var(--primary-gradient);
      padding: 40px;
      text-align: center;
      color: white;
    }
    .form-content {
      padding: 40px;
    }
    .brand-logo-circle {
      width: 80px;
      height: 80px;
      background: rgba(255,255,255,0.2);
      border-radius: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 20px;
      backdrop-filter: blur(10px);
    }
  </style>
</head>

<body class="animate-fade-up">
  <div class="container p-4">
    <div class="row justify-content-center">
      <div class="col-lg-12">
        <div class="forgot-card mx-auto">
          <div class="header-accent">
            <div class="brand-logo-circle">
               <i class="fas fa-key fa-2x"></i>
            </div>
            <h2 class="font-weight-bold mb-1" style="color:white !important;">Forgot Password</h2>
            <?php if (!$emailSent): ?>
              <p class="small opacity-75 mb-0" style="color:white !important;">Enter your email to receive a reset link</p>
            <?php endif; ?>
          </div>
          
          <div class="form-content">
            <?php echo $statusMsg; ?>

            <?php if (!$emailSent): ?>
            <form method="post" action="">
              <div class="form-group mb-4">
                <label class="form-label text-uppercase small font-weight-bold" style="color: var(--primary) !important;">Registered Email</label>
                <input type="email" class="form-control form-control-lg border-0 bg-light" required name="email" id="email" placeholder="name@example.com" style="border-radius: 12px; height: 55px;">
              </div>
              
              <button type="submit" name="reset_password" class="btn btn-primary btn-block py-3 mb-4" style="border-radius: 12px; font-weight: 700;">
                Send Reset Link
              </button>
            </form>
            <?php endif; ?>

            <div class="text-center">
              <a href="login.php" class="text-primary font-weight-bold small">
                <i class="fas fa-arrow-left mr-1"></i> Back to Login
              </a>
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
