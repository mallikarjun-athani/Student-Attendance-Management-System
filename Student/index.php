<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';

// Student-specific role verification
if (!isset($_SESSION['userType']) || $_SESSION['userType'] !== 'Student') {
  header('Location: login.php');
  exit;
}

$currentPage = basename($_SERVER['PHP_SELF']);
$studentId = intval($_SESSION['studentId']);
$student = null;

$stmt = $conn->prepare("SELECT * FROM tblstudents WHERE Id = ? LIMIT 1");
$stmt->bind_param('i', $studentId);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows > 0) {
  $student = $res->fetch_assoc();
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'Includes/header.php'; ?>

<body id="page-top" class="animate-fade-up">
  <div id="wrapper">
    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">
        <!-- Topbar -->
        <nav class="navbar navbar-expand navbar-light topbar mb-4 static-top shadow-sm" style="background: rgba(255,255,255,0.8); backdrop-filter: blur(10px);">
          <div class="container-fluid">
            <div class="d-flex flex-column justify-content-center">
              <h4 class="font-weight-bold mb-0" style="color: var(--text-primary); font-size: 1rem; letter-spacing: -0.5px; line-height: 1.2;"><span class="brand-text-primary">SAMS</span> <span class="brand-text-grad">Portal</span></h4>
            </div>
            <ul class="navbar-nav ml-auto align-items-center">
              <li class="nav-item">
                <div class="user-profile-container d-flex align-items-center p-2 transition-all">
                  <div class="text-right mr-3">
                    <span class="small font-weight-bold text-dark">Student</span>
                    <span class="d-block text-muted" style="font-size: 0.70rem;"><?php echo htmlspecialchars($student['admissionNumber'] ?? 'N/A'); ?></span>
                  </div>
                  <a href="logout.php" class="btn btn-outline-danger btn-sm d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; border-radius: 12px; transition: all 0.3s ease;">
                    <i class="fas fa-power-off"></i>
                  </a>
                </div>
              </li>
            </ul>
          </div>
        </nav>

        <div class="container-fluid" id="container-wrapper">
          <?php if (isset($_GET['status']) && $_GET['status'] == 'profile_updated'): ?>
            <div class='alert alert-success alert-dismissible fade show mb-4' role='alert' style='border-radius:15px; background: rgba(16, 185, 129, 0.1); border-color: rgba(16, 185, 129, 0.2); color: #10b981;'>
              <i class="fas fa-check-circle mr-2"></i> Your profile has been updated successfully!
              <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
          <?php endif; ?>

          <div class="mb-4">
            <h1 class="h2 font-weight-bold text-gray-900" style="letter-spacing: -1px;">My Dashboard</h1>
            <p class="text-muted">Manage your profile and track your academic progress.</p>
          </div>

          <div class="row">
            <div class="col-lg-12">
              <!-- Pro Profile Card -->
              <div class="card border-0 shadow-lg mb-5 overflow-hidden" style="border-radius: 24px;">
                <div class="card-body p-0">
                    <div class="row no-gutters">
                        <!-- Left Side: Visual Identity -->
                        <div class="col-md-5 d-flex align-items-center justify-content-center p-4 p-md-5" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-right: 1px solid rgba(0,0,0,0.03);">
                            <div class="text-center">
                                <div class="position-relative mb-4 d-inline-block">
                                    <?php
                                    $photo = isset($student['photo']) ? trim($student['photo']) : '';
                                    if ($photo !== '') {
                                        echo '<img src="../'.htmlspecialchars($photo).'" style="width:180px;height:180px;object-fit:cover;border-radius:30px;box-shadow: 0 20px 40px -10px rgba(0,0,0,0.15); border: 6px solid #fff;">';
                                    } else {
                                        echo '<div style="width:180px;height:180px;background:#e2e8f0;border-radius:30px;display:flex;align-items:center;justify-content:center;"><i class="fas fa-user fa-5x text-white"></i></div>';
                                    }
                                    ?>
                                    <div class="badge-pro bg-success text-white position-absolute" style="bottom: -10px; left: 50%; transform: translateX(-50%); white-space:nowrap; border: 4px solid #fff;">Active Student</div>
                                </div>
                                <h3 class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars($student['firstName']." ".$student['lastName']); ?></h3>
                                <p class="text-uppercase font-weight-bold text-primary mb-0" style="font-size: 0.75rem; letter-spacing: 2px;"><?php echo htmlspecialchars($student['admissionNumber']); ?></p>
                            </div>
                        </div>
                        
                        <!-- Right Side: Contact & Actions -->
                        <div class="col-md-7 p-4 p-md-5">
                            <div class="d-flex justify-content-between align-items-start mb-4">
                                <h5 class="font-weight-bold text-dark"><i class="fas fa-info-circle mr-2 text-primary"></i> Personal Information</h5>
                            </div>
                            
                            <div class="row mb-5">
                                <div class="col-md-6 mb-4">
                                    <label class="text-muted small font-weight-bold text-uppercase mb-1 d-block">Father's Name</label>
                                    <div class="h6 font-weight-bold border-bottom pb-2"><?php echo htmlspecialchars($student['otherName'] ?? 'Not Specified'); ?></div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="text-muted small font-weight-bold text-uppercase mb-1 d-block">Phone Number</label>
                                    <div class="h6 font-weight-bold border-bottom pb-2"><?php echo htmlspecialchars($student['phoneNo'] ?? 'Not Specified'); ?></div>
                                </div>
                                <div class="col-md-12">
                                    <label class="text-muted small font-weight-bold text-uppercase mb-1 d-block">Email Address</label>
                                    <div class="h6 font-weight-bold border-bottom pb-2"><?php echo htmlspecialchars($student['emailAddress'] ?? 'Not Specified'); ?></div>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap" style="gap:15px;">
                                <a class="btn btn-primary px-4 py-3" href="register.php"><i class="fas fa-user-edit mr-2"></i> Edit Profile</a>
                                <a class="btn btn-success px-4 py-3" href="attendanceQr.php"><i class="fas fa-qrcode mr-2"></i> Portal Scan</a>
                                <a class="btn btn-info px-4 py-3" href="attendanceView.php"><i class="fas fa-chart-line mr-2"></i> Attendance</a>
                                <a class="btn btn-secondary px-4 py-3" href="qr.php" style="background: #334155 !important;"><i class="fas fa-id-card mr-2"></i> Your ID</a>
                            </div>
                        </div>
                    </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Pro Mobile Bottom Navigation -->
  <nav class="mobile-nav">
    <a href="index.php" class="mobile-nav-item <?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">
      <i class="fas fa-home"></i>
      <span>Home</span>
    </a>
    <a href="attendanceQr.php" class="mobile-nav-item <?php echo ($currentPage == 'attendanceQr.php') ? 'active' : ''; ?>">
      <i class="fas fa-qrcode"></i>
      <span>Scan</span>
    </a>
    <a href="attendanceView.php" class="mobile-nav-item <?php echo ($currentPage == 'attendanceView.php') ? 'active' : ''; ?>">
      <i class="fas fa-chart-line"></i>
      <span>Status</span>
    </a>
    <a href="qr.php" class="mobile-nav-item <?php echo ($currentPage == 'qr.php') ? 'active' : ''; ?>">
      <i class="fas fa-id-card"></i>
      <span>My ID</span>
    </a>
  </nav>

  <script src="../vendor/jquery/jquery.min.js"></script>
  <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
  <script src="../js/ruang-admin.min.js"></script>
</body>
</html>
