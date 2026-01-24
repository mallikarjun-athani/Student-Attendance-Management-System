<?php
include '../Includes/dbcon.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$statusMsg = '';

if (isset($_GET['status']) && $_GET['status'] === 'registered') {
  $statusMsg = "<div class='alert' style='background: rgba(34, 197, 94, 0.1); border-color: rgba(34, 197, 94, 0.2); color: #4ade80;'>Registration successful. Please login.</div>";
}

if (isset($_POST['login'])) {
  $admissionNumber = isset($_POST['admissionNumber']) ? trim($_POST['admissionNumber']) : '';
  $passwordRaw = isset($_POST['password']) ? $_POST['password'] : '';

  if ($admissionNumber === '' || $passwordRaw === '') {
    $statusMsg = "<div class='alert' role='alert'>Admission Number and Password are required.</div>";
  } else {
    $passwordMd5 = md5($passwordRaw);

    $stmt = $conn->prepare("SELECT Id, firstName, lastName, otherName, admissionNumber, emailAddress, phoneNo, classId, classArmId, password FROM tblstudents WHERE admissionNumber = ? LIMIT 1");
    $stmt->bind_param('s', $admissionNumber);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows > 0) {
      $row = $res->fetch_assoc();
      $stored = isset($row['password']) ? $row['password'] : '';

      if ($stored === $passwordMd5 || $stored === $passwordRaw) {
        $_SESSION['userType'] = 'Student';
        $_SESSION['userId'] = $row['Id'];
        $_SESSION['studentId'] = $row['Id'];
        $_SESSION['firstName'] = $row['firstName'];
        $_SESSION['lastName'] = $row['lastName'];
        $_SESSION['emailAddress'] = isset($row['emailAddress']) ? $row['emailAddress'] : '';
        $_SESSION['classId'] = $row['classId'];
        $_SESSION['classArmId'] = $row['classArmId'];

        header('Location: index.php');
        exit;
      }
      $statusMsg = "<div class='alert' role='alert'>Invalid Admission Number/Password!</div>";
    } else {
      $statusMsg = "<div class='alert' role='alert'>Invalid Admission Number/Password!</div>";
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
  <title>Student - Login</title>
  
  <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">

  <style>
    :root {
        --primary: #8b5cf6; /* Violet Start */
        --primary-gradient: linear-gradient(135deg, #a855f7 0%, #8b5cf6 100%);
        --primary-hover: #7c3aed;
        --bg-body: #FEF0B3; 
        --text-main: #000000;
        --text-secondary: #4b5563;
        --card-border: #fde68a;
    }

    body, html {
        height: 100%;
        margin: 0;
        font-family: 'Outfit', sans-serif;
        background-color: var(--bg-body);
    }

    .bg-gradient-login {
        background: var(--bg-body);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .container-login {
        width: 100%;
        max-width: 440px;
    }

    .glass-card {
        background: #ffffff;
        border: 1px solid var(--card-border);
        border-radius: 24px;
        padding: 50px 40px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
        position: relative;
        overflow: hidden;
        animation: fadeIn 0.6s ease-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .glass-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--primary-gradient);
    }

    .brand-text {
        color: var(--text-main);
        font-weight: 800;
        font-size: 1.5rem;
        letter-spacing: -0.5px;
        margin-bottom: 25px;
    }

    .logo-container {
        margin-bottom: 25px;
        display: flex;
        justify-content: center;
    }

    .logo-container img {
        width: 70px;
        height: 70px;
        object-fit: contain;
        border-radius: 16px;
        padding: 8px;
        background: var(--bg-body);
        border: 1px solid var(--card-border);
        transition: all 0.3s ease;
    }

    .form-group {
        margin-bottom: 24px;
        text-align: left;
    }

    .form-label {
        color: var(--text-secondary);
        font-size: 0.8rem;
        font-weight: 700;
        margin-bottom: 8px;
        display: block;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .form-control {
        background: #fff;
        border: 1px solid var(--card-border);
        border-radius: 12px;
        color: var(--text-main);
        padding: 12px 16px;
        height: auto;
        font-size: 0.95rem;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(234, 46, 46, 0.1);
        background: #fff;
    }

    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 20px 25px -5px rgba(139, 92, 246, 0.3);
    }

    .btn-login {
        background: var(--primary-gradient);
        border: none;
        border-radius: 12px;
        color: white;
        padding: 14px;
        font-weight: 700;
        font-size: 1rem;
        width: 100%;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 10px 15px -3px rgba(139, 92, 246, 0.25);
        text-transform: uppercase;
        letter-spacing: 1px;
        position: relative;
        z-index: 1;
        overflow: hidden;
    }

    .btn-login::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #7c3aed 0%, #8b5cf6 100%);
        opacity: 0;
        z-index: -1;
        transition: opacity 0.4s ease;
    }

    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 20px 25px -5px rgba(139, 92, 246, 0.3);
    }

    .btn-login:hover::before {
        opacity: 1;
    }

    .alert {
        position: fixed;
        top: 25px;
        left: 50%;
        transform: translateX(-50%) translateY(-100px);
        background: #ef4444;
        color: #fff;
        border-radius: 12px;
        padding: 14px 28px;
        font-size: 0.9rem;
        font-weight: 700;
        z-index: 10000;
        box-shadow: 0 20px 25px -5px rgba(239, 68, 68, 0.3);
        transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        display: flex;
        align-items: center;
        gap: 10px;
        opacity: 0;
        pointer-events: none;
    }

    .alert.show {
        transform: translateX(-50%) translateY(0);
        opacity: 1;
        pointer-events: auto;
    }

    .alert.success {
        background: #10b981;
        box-shadow: 0 20px 25px -5px rgba(16, 185, 129, 0.3);
    }

    .links-container {
        text-align: center;
        margin-top: 25px;
    }

    .links-container a {
        color: var(--text-secondary);
        font-size: 0.85rem;
        transition: all 0.3s ease;
        text-decoration: none;
        display: block;
        margin-top: 10px;
        font-weight: 500;
    }

    .links-container a:hover {
        color: var(--primary);
        transform: translateX(4px);
    }

    .links-container .divider {
        height: 1px;
        background: var(--card-border);
        margin: 20px 0;
    }
  </style>
</head>

<body class="bg-gradient-login">
  <?php echo $statusMsg; ?>
  <div class="container-login">
    <div class="glass-card text-center">
      <div class="logo-container">
        <i class="fas fa-user-graduate fa-4x mb-3" style="color: var(--primary);"></i>
      </div>
      <h5 class="brand-text">SAMS <span style="color: var(--primary);">ADMIN</span></h5>
      <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 30px;">Student Portal</p>

      <form class="user" method="post" action="">
        <div class="form-group">
          <label class="form-label">Admission Number</label>
          <input type="text" class="form-control" required name="admissionNumber" placeholder="e.g. ADM123456">
        </div>
        <div class="form-group">
          <label class="form-label">Password</label>
          <input type="password" name="password" required class="form-control" placeholder="••••••••">
        </div>
        <input type="submit" class="btn btn-login" value="Sign In" name="login" />
      </form>

      <div class="links-container">
        <div class="divider"></div>
        <a href="register.php">New student? <span style="color: var(--primary); font-weight: 600;">Register here</span></a>
        <a href="forgotPassword.php">Forgot Password?</a>
        <a href="../index.php" style="margin-top: 20px; opacity: 0.8;">← Back to Main Login</a>
      </div>
    </div>
  </div>

  <script src="../vendor/jquery/jquery.min.js"></script>
  <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script>
    $(document).ready(function() {
        var toast = $('.alert');
        if(toast.length) {
            setTimeout(function() {
                toast.addClass('show');
            }, 100);

            setTimeout(function() {
                toast.removeClass('show');
            }, 5000);
        }
    });
  </script>
</body>
</html>
