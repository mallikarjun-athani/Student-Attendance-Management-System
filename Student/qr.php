<?php
include '../Includes/dbcon.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($_SESSION['userType']) || $_SESSION['userType'] !== 'Student' || !isset($_SESSION['studentId'])) {
  header('Location: login.php');
  exit;
}

$studentId = intval($_SESSION['studentId']);
$student = null;

$stmt = $conn->prepare("SELECT admissionNumber, firstName, lastName, otherName FROM tblstudents WHERE Id = ? LIMIT 1");
$stmt->bind_param('i', $studentId);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows > 0) {
  $student = $res->fetch_assoc();
}
$stmt->close();

if (!$student || !isset($student['admissionNumber']) || trim($student['admissionNumber']) === '') {
  header('Location: index.php');
  exit;
}

$admission = trim($student['admissionNumber']);

$qrSecret = 'AMS_QR_SECRET_2025';
$sig = hash_hmac('sha256', $admission, $qrSecret);

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? '';
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
$infoUrl = $scheme.'://'.$host.$basePath.'/attendanceInfo.php?adm='.urlencode($admission).'&sig='.urlencode($sig);
$payload = $infoUrl;

$studentName = trim(($student['firstName'] ?? '').' '.($student['lastName'] ?? '').' '.($student['otherName'] ?? ''));
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
            <div class="d-flex align-items-center">
              <a href="index.php" class="btn btn-link text-primary mr-3" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: rgba(99, 102, 241, 0.1); border-radius: 8px;"><i class="fas fa-arrow-left"></i></a>
              <div class="d-flex flex-column justify-content-center">
                <h4 class="font-weight-bold mb-0" style="color: var(--text-primary); font-size: 1rem; letter-spacing: -0.5px; line-height: 1.2;">Identity <span style="background: var(--primary-gradient); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;">Pass</span></h4>
              </div>
            </div>
            <div class="ml-auto">
              <a href="logout.php" class="btn btn-outline-danger btn-sm d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; border-radius: 8px; transition: all 0.3s ease;">
                <i class="fas fa-power-off" style="font-size: 0.75rem;"></i>
              </a>
            </div>
          </div>
        </nav>

        <div class="container-fluid" id="container-wrapper">
          <div class="row justify-content-center">
            <div class="col-xl-6 col-lg-8">
              <!-- Pro QR Card -->
              <div class="card border-0 shadow-lg mb-4" style="border-radius: 30px; overflow: hidden;">
                <div class="card-body p-0">
                    <div class="p-5 text-center" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-bottom: 1px solid rgba(0,0,0,0.05);">
                        <h4 class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars($studentName); ?></h4>
                        <span class="badge-pro bg-primary text-white" style="background: var(--primary-gradient) !important;">ADM: <?php echo htmlspecialchars($admission); ?></span>
                    </div>
                    
                    <div class="p-5 text-center">
                        <div class="qr-container d-inline-block p-4 bg-white shadow-sm mb-4" style="border-radius: 24px; border: 1px solid #edf2f7;">
                            <div id="qr" style="padding: 10px;"></div>
                        </div>
                        
                        <p class="text-muted small mb-4">Your personal access code for verification.<br>Keep this private for security purposes.</p>

                        <div class="d-flex flex-wrap justify-content-center" style="gap:15px;">
                            <button class="btn btn-primary px-4" id="btnDownload"><i class="fas fa-download mr-2"></i> Download ID</button>
                            <button class="btn btn-outline-primary px-4" id="btnCopy"><i class="fas fa-copy mr-2"></i> Copy Code</button>
                        </div>
                    </div>
                </div>
              </div>

              <!-- Information Alert -->
              <div class="card border-0 shadow-sm" style="border-radius: 20px; background: rgba(14, 165, 233, 0.05);">
                <div class="card-body d-flex align-items-center p-4">
                    <div class="mr-4 text-info"><i class="fas fa-info-circle fa-2x"></i></div>
                    <p class="mb-0 text-dark small" style="line-height: 1.6;">
                        This QR code is for <strong>viewing</strong> your attendance details only. 
                        To mark your attendance in class, please scan the <strong>Subject QR Code</strong> provided by your teacher.
                    </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <script>
    (function(){
      var qrText = <?php echo json_encode($payload); ?>;
      var adm = <?php echo json_encode($admission); ?>;

      var el = document.getElementById('qr');
      el.innerHTML = '';
      new QRCode(el, {
        text: qrText,
        width: 220,
        height: 220,
        colorDark : "#000000",
        colorLight : "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
      });

      function getCanvasOrImg() {
        var img = el.querySelector('img');
        var canvas = el.querySelector('canvas');
        return { img: img, canvas: canvas };
      }

      document.getElementById('btnCopy').addEventListener('click', function(){
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(qrText).then(function(){
            alert('QR text copied to clipboard.');
          }).catch(function(){
            alert('Copy failed.');
          });
        }
      });

      document.getElementById('btnDownload').addEventListener('click', function(){
        var r = getCanvasOrImg();
        var padding = 40;

        if (r.canvas) {
          var out = document.createElement('canvas');
          out.width = r.canvas.width + padding * 2;
          out.height = r.canvas.height + padding * 2;
          var ctx = out.getContext('2d');
          ctx.fillStyle = '#ffffff';
          ctx.fillRect(0, 0, out.width, out.height);
          ctx.drawImage(r.canvas, padding, padding);
          var dataUrl = out.toDataURL('image/png');

          var a = document.createElement('a');
          a.href = dataUrl;
          a.download = 'ID_Pass_' + adm + '.png';
          document.body.appendChild(a);
          a.click();
          a.remove();
        }
      });
    })();
  </script>

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
