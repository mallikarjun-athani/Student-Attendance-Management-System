<?php
include '../Includes/dbcon.php';

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

$statusMsg = '';
$token = isset($_GET['token']) ? $_GET['token'] : '';
$isValidToken = false;
$email = '';

if ($token === '') {
  $statusMsg = "<div class='alert alert-danger mb-4' style='border-radius:12px; font-weight:600;'>Invalid password reset link.</div>";
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
      $statusMsg = "<div class='alert alert-danger mb-4' style='border-radius:12px; font-weight:600;'>This reset link has already been used.</div>";
    } else if ($now > $expires) {
      $statusMsg = "<div class='alert alert-danger mb-4' style='border-radius:12px; font-weight:600;'>This reset link has expired. Links are valid for 15 minutes.</div>";
    } else {
      $isValidToken = true;
      $email = $row['email'];
    }
  } else {
    $statusMsg = "<div class='alert alert-danger mb-4' style='border-radius:12px; font-weight:600;'>Invalid or tampered reset link.</div>";
  }
  $stmt->close();
}

if ($isValidToken && isset($_POST['update_password'])) {
  $password = isset($_POST['password']) ? $_POST['password'] : '';
  $password2 = isset($_POST['password2']) ? $_POST['password2'] : '';

  if (strlen($password) < 6) {
    $statusMsg = "<div class='alert alert-danger mb-4' style='border-radius:12px; font-weight:600;'>Password must be at least 6 characters long.</div>";
  } else if ($password !== $password2) {
    $statusMsg = "<div class='alert alert-danger mb-4' style='border-radius:12px; font-weight:600;'>Passwords do not match.</div>";
  } else {
    $passwordHash = md5($password);
    
    $stmt_update = $conn->prepare("UPDATE tblstudents SET password = ? WHERE emailAddress = ?");
    $stmt_update->bind_param('ss', $passwordHash, $email);

    if ($stmt_update->execute()) {
      $stmt_expire = $conn->prepare("UPDATE tblpassword_resets SET is_used = 1 WHERE token = ?");
      $stmt_expire->bind_param('s', $token);
      $stmt_expire->execute();
      $stmt_expire->close();

      $statusMsg = "<div class='alert alert-success mb-4' style='border-radius:12px; font-weight:600;'>Password reset successful! You can now <a href='login.php' class='alert-link'>Sign In</a>.</div>";
      $isValidToken = false; // Hide form
    } else {
      $statusMsg = "<div class='alert alert-danger mb-4' style='border-radius:12px; font-weight:600;'>Could not update password. System error.</div>";
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
  <title>Reset Password - SAMS</title>
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
    .reset-card {
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
    #strength-meter {
      height: 4px;
      background: #e2e8f0;
      border-radius: 2px;
      margin-top: 8px;
      overflow: hidden;
    }
    #strength-bar {
      height: 100%;
      width: 0;
      transition: all 0.3s ease;
    }
  </style>
</head>

<body class="animate-fade-up">
  <div class="container p-4">
    <div class="row justify-content-center">
      <div class="col-lg-12">
        <div class="reset-card mx-auto">
          <div class="header-accent">
            <div class="brand-logo-circle">
               <i class="fas fa-shield-alt fa-2x"></i>
            </div>
            <h2 class="font-weight-bold mb-1" style="color:white !important;">Reset Password</h2>
            <p class="small opacity-75 mb-0" style="color:white !important;">Set your new secure password</p>
          </div>
          
          <div class="form-content">
            <?php echo $statusMsg; ?>

            <?php if ($isValidToken) : ?>
              <form method="post" action="">
                <div class="form-group mb-4">
                  <label class="form-label text-uppercase small font-weight-bold" style="color: var(--primary) !important;">New Password</label>
                  <input type="password" class="form-control mb-1" required name="password" id="password" placeholder="••••••••" style="border-radius: 12px; height: 50px;">
                  <div id="strength-meter"><div id="strength-bar"></div></div>
                  <small id="strength-text" class="text-muted"></small>
                </div>

                <div class="form-group mb-4">
                  <label class="form-label text-uppercase small font-weight-bold" style="color: var(--primary) !important;">Confirm Password</label>
                  <input type="password" class="form-control" required name="password2" id="password2" placeholder="••••••••" style="border-radius: 12px; height: 50px;">
                </div>
                
                <button type="submit" name="update_password" class="btn btn-primary btn-block py-3 mb-4" style="border-radius: 12px; font-weight: 700;">
                  Update Password
                </button>
              </form>
            <?php endif; ?>

            <div class="text-center">
              <a href="login.php" class="text-primary font-weight-bold small">
                Back to Login
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="../vendor/jquery/jquery.min.js"></script>
  <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script>
    $(document).ready(function() {
      $('#password').on('input', function() {
        var val = $(this).val();
        var strength = 0;
        if (val.length >= 6) strength++;
        if (val.match(/[A-Z]/)) strength++;
        if (val.match(/[0-9]/)) strength++;
        if (val.match(/[^A-Za-z0-9]/)) strength++;

        var color = '#e3342f';
        var text = 'Weak';
        var width = '25%';

        if (strength === 2) { color = '#f6993f'; text = 'Fair'; width = '50%'; }
        else if (strength === 3) { color = '#38c172'; text = 'Good'; width = '75%'; }
        else if (strength === 4) { color = '#38c172'; text = 'Strong'; width = '100%'; }
        
        if (val === '') { width = '0%'; text = ''; }

        $('#strength-bar').css({'width': width, 'background-color': color});
        $('#strength-text').text(text).css('color', color);
      });
    });
  </script>
</body>
</html>
