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

$stmt = $conn->prepare("SELECT Id, admissionNumber, firstName, lastName, otherName, classId, classArmId FROM tblstudents WHERE Id = ? LIMIT 1");
$stmt->bind_param('i', $studentId);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows > 0) {
  $student = $res->fetch_assoc();
}
$stmt->close();

if (!$student) {
  header('Location: index.php');
  exit;
}

$studentName = trim(($student['firstName'] ?? '').' '.($student['lastName'] ?? '').' '.($student['otherName'] ?? ''));
?>

<!DOCTYPE html>
<html lang="en">

<?php include 'Includes/header.php'; ?>

<body class="bg-gradient-login" style="background-image: url('../img/logo/loral1.jpe00g');">
  <div class="container" style="max-width: 1000px; padding-top: 40px; padding-bottom: 40px;">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <h4 class="mb-0">Attendance Using QR Code</h4>
            <small class="text-muted">Scan subject QR to mark attendance</small>
          </div>
          <div>
            <a class="btn btn-outline-secondary btn-sm" href="index.php">Back</a>
          </div>
        </div>

        <hr>

        <div class="row">
          <div class="col-lg-7">
            <div class="alert alert-secondary" role="alert">
              <div><b>Student:</b> <?php echo htmlspecialchars($studentName); ?></div>
              <div><b>Registration No:</b> <?php echo htmlspecialchars($student['admissionNumber'] ?? ''); ?></div>
            </div>

            <div class="card mb-3">
              <div class="card-body">
                <div class="form-group">
                  <button type="button" class="btn btn-outline-primary btn-sm" id="requestCameraPerm">Request Camera Permission</button>
                  <button type="button" class="btn btn-primary btn-sm" id="openCameraBtn">Open Camera</button>
                  <button type="button" class="btn btn-outline-danger btn-sm" id="closeCameraBtn" disabled>Close Camera</button>
                  <button type="button" class="btn btn-outline-secondary btn-sm" id="switchCameraBtn" disabled>Switch Camera</button>
                </div>

                <div class="alert alert-warning" id="secureContextWarning" style="display:none;"></div>

                <div class="form-group">
                  <label class="form-control-label">Camera</label>
                  <select class="form-control" id="cameraSelect"></select>
                </div>

                <div id="qr-reader" class="mb-3"></div>

                <div class="alert alert-info" id="scanStatus" style="display:none;"></div>

                <div class="alert alert-success" id="scanResult" style="display:none;"></div>
              </div>
            </div>
          </div>

          <div class="col-lg-5">
            <div class="card" id="scanResultCard">
              <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Scan Result</h6>
              </div>
              <div class="card-body">
                <div id="resultBox" style="display:none;">
                  <div><b>Student Name:</b> <span id="rStudent"></span></div>
                  <div><b>Admission Number:</b> <span id="rAdm"></span></div>
                  <div><b>Subject Name:</b> <span id="rSubject"></span></div>
                  <div><b>Date &amp; Time:</b> <span id="rTime"></span></div>
                </div>
                <div class="alert alert-secondary" id="emptyBox" role="alert">No scan yet.</div>
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
    async function ensureHtml5QrLoaded() {
      if (typeof Html5Qrcode !== 'undefined' && typeof Html5Qrcode.getCameras === 'function') return true;

      function loadScript(url) {
        return new Promise(function(resolve, reject) {
          var s = document.createElement('script');
          s.src = url;
          s.async = true;
          s.onload = function() { resolve(true); };
          s.onerror = function() { reject(new Error('Failed to load ' + url)); };
          document.head.appendChild(s);
        });
      }

      var sources = [
        '../vendor/html5-qrcode/html5-qrcode.min.js',
        'https://unpkg.com/html5-qrcode@2.3.10/html5-qrcode.min.js',
        'https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.10/html5-qrcode.min.js',
        'https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.10/html5-qrcode.min.js'
      ];

      for (var i = 0; i < sources.length; i++) {
        try {
          await loadScript(sources[i]);
          if (typeof Html5Qrcode !== 'undefined' && typeof Html5Qrcode.getCameras === 'function') return true;
        } catch (e) {
          // try next
        }
      }

      return false;
    }

    var html5QrCode = null;
    var isScanning = false;
    var availableCameras = [];
    var statusHideTimer = null;
    var autoCloseTimer = null;
    var isProcessing = false;

    function showStatus(msg, type, autoHideMs) {
      var el = document.getElementById('scanStatus');
      el.className = 'alert ' + (type || 'alert-info');
      el.textContent = msg;
      el.style.display = 'block';
      if (statusHideTimer) {
        clearTimeout(statusHideTimer);
        statusHideTimer = null;
      }

      var ms = (typeof autoHideMs === 'number') ? autoHideMs : 6000;
      if (ms < 6000) ms = 6000;
      if (ms && ms > 0) {
        statusHideTimer = setTimeout(function(){
          el.style.display = 'none';
          statusHideTimer = null;
        }, ms);
      }
    }

    function showResult(resp) {
      document.getElementById('emptyBox').style.display = 'none';
      document.getElementById('resultBox').style.display = 'block';
      document.getElementById('rStudent').textContent = resp.studentName || '';
      document.getElementById('rAdm').textContent = resp.admissionNumber || '';
      document.getElementById('rSubject').textContent = resp.subjectName || '';
      document.getElementById('rTime').textContent = resp.time || '';
    }

    async function fetchCameras() {
      var ok = await ensureHtml5QrLoaded();
      if (!ok) {
        showStatus('QR scanner library failed to load. Please allow CDN access.', 'alert-danger');
        return;
      }

      var devices = await Html5Qrcode.getCameras();
      availableCameras = devices || [];
      var select = document.getElementById('cameraSelect');
      select.innerHTML = '';

      if (!devices || devices.length === 0) {
        var opt = document.createElement('option');
        opt.value = '';
        opt.textContent = 'No camera found';
        select.appendChild(opt);
        return;
      }

      devices.forEach(function(d, idx) {
        var opt = document.createElement('option');
        opt.value = d.id;
        opt.textContent = d.label || ('Camera ' + (idx + 1));
        select.appendChild(opt);
      });

      var back = devices.find(function(d){ return (d.label || '').toLowerCase().includes('back'); });
      if (back) select.value = back.id;
    }

    async function startScanner(cameraIdOrConfig) {
      if (!cameraIdOrConfig) return;

      var ok = await ensureHtml5QrLoaded();
      if (!ok) {
        showStatus('QR scanner library failed to load. Please allow CDN access.', 'alert-danger');
        return;
      }

      if (!html5QrCode) {
        html5QrCode = new Html5Qrcode('qr-reader');
      } else {
        try { await html5QrCode.stop(); } catch (e) {}
        try { await html5QrCode.clear(); } catch (e) {}
      }

      showStatus('Starting camera... Allow camera permission if prompted.', 'alert-info');

      try {
        await html5QrCode.start(
          cameraIdOrConfig,
          {
            fps: 30,
            qrbox: function(viewfinderWidth, viewfinderHeight) {
              var minEdge = Math.min(viewfinderWidth, viewfinderHeight);
              var edge = Math.floor(minEdge * 0.75);
              edge = Math.max(220, Math.min(edge, 360));
              return { width: edge, height: edge };
            },
            experimentalFeatures: { useBarCodeDetectorIfSupported: true },
            disableFlip: false
          },
          function(decodedText) {
            onScan(decodedText);
          },
          function() {
            // ignore noisy scan errors
          }
        );
      } catch (e) {
        var msg = (e && (e.message || e.name)) ? (e.name ? (e.name + ': ' + (e.message || '')) : e.message) : String(e);
        showStatus('Camera failed to start. ' + msg, 'alert-danger', 3000);
        throw e;
      }

      isScanning = true;
      document.getElementById('openCameraBtn').disabled = true;
      document.getElementById('closeCameraBtn').disabled = false;
      document.getElementById('switchCameraBtn').disabled = false;
      showStatus('Camera started. Scan a subject QR code.', 'alert-success', 6000);
    }

    async function stopScanner() {
      try {
        if (html5QrCode) {
          try { await html5QrCode.stop(); } catch (e) {}
          try { await html5QrCode.clear(); } catch (e) {}
        }
      } finally {
        isScanning = false;
        document.getElementById('openCameraBtn').disabled = false;
        document.getElementById('closeCameraBtn').disabled = true;
        document.getElementById('switchCameraBtn').disabled = true;
        showStatus('Camera closed.', 'alert-secondary', 6000);
      }
    }

    var lastText = null;
    var lastTextAt = 0;

    function onScan(qrText) {
      var now = Date.now();
      if (qrText === lastText && (now - lastTextAt) < 250) return;
      if (isProcessing) return;
      lastText = qrText;
      lastTextAt = now;

      isProcessing = true;

      showStatus('QR detected. Processing...', 'alert-info');

      $.ajax({
        url: 'ajaxSubjectQrAttendance.php',
        method: 'POST',
        dataType: 'json',
        data: { qrText: qrText },
        success: function(resp) {
          if (!resp || !resp.ok) {
            showStatus((resp && resp.message) ? resp.message : 'Scan failed.', 'alert-danger', 6500);
            isProcessing = false;
            var rc1 = document.getElementById('scanResultCard');
            if (rc1 && rc1.scrollIntoView) {
              rc1.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            return;
          }

          showStatus(resp.message || 'Success', 'alert-success', 6500);
          showResult(resp.data || {});
          isProcessing = false;

          var rc2 = document.getElementById('scanResultCard');
          if (rc2 && rc2.scrollIntoView) {
            rc2.scrollIntoView({ behavior: 'smooth', block: 'start' });
          }

          // Camera remains open. User closes it manually.
        },
        error: function() {
          showStatus('Server error while processing scan.', 'alert-danger', 6500);
          isProcessing = false;
          var rc3 = document.getElementById('scanResultCard');
          if (rc3 && rc3.scrollIntoView) {
            rc3.scrollIntoView({ behavior: 'smooth', block: 'start' });
          }
        }
      });
    }

    document.addEventListener('DOMContentLoaded', async function() {
      try {
        var warn = document.getElementById('secureContextWarning');
        var isLocal = (location.hostname === 'localhost' || location.hostname === '127.0.0.1');
        if (!window.isSecureContext && !isLocal) {
          warn.style.display = 'block';
          warn.textContent = 'Camera requires HTTPS or a localhost URL. Open this page using http://localhost/... (not a LAN IP). Current origin: ' + location.origin;
        }
      } catch (e) {}

      document.getElementById('requestCameraPerm').addEventListener('click', async function() {
        try {
          var ok = await ensureHtml5QrLoaded();
          if (!ok) {
            showStatus('QR scanner library failed to load. Please allow CDN access.', 'alert-danger', 6500);
            return;
          }
          if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showStatus('Camera API not supported in this browser.', 'alert-danger', 6500);
            return;
          }
          var stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
          stream.getTracks().forEach(function(t){ t.stop(); });
          showStatus('Camera permission granted. Loading cameras...', 'alert-success', 6500);
          await fetchCameras();
        } catch (e) {
          var msg = (e && (e.message || e.name)) ? (e.name ? (e.name + ': ' + (e.message || '')) : e.message) : String(e);
          showStatus('Permission request failed. ' + msg, 'alert-danger', 6500);
        }
      });

      await fetchCameras();

      var select = document.getElementById('cameraSelect');
      select.addEventListener('change', function() {
        if (isScanning) startScanner(select.value);
      });

      document.getElementById('switchCameraBtn').addEventListener('click', async function() {
        try {
          if (!isScanning) return;

          var currentId = select.value;
          if (availableCameras && availableCameras.length >= 2) {
            var idx = availableCameras.findIndex(function(c){ return c.id === currentId; });
            var nextIdx = (idx >= 0) ? ((idx + 1) % availableCameras.length) : 0;
            select.value = availableCameras[nextIdx].id;
            await startScanner(select.value);
            return;
          }

          await startScanner({ facingMode: 'environment' });
        } catch (e) {
          var msg = (e && (e.message || e.name)) ? (e.name ? (e.name + ': ' + (e.message || '')) : e.message) : String(e);
          showStatus('Unable to switch camera. ' + msg, 'alert-danger', 3000);
        }
      });

      document.getElementById('openCameraBtn').addEventListener('click', async function() {
        var id = select.value;
        if (!id) {
          await startScanner({ facingMode: 'environment' });
          return;
        }
        await startScanner(id);
      });

      document.getElementById('closeCameraBtn').addEventListener('click', function() {
        stopScanner();
      });

      showStatus('Ready. Click Open Camera to start scanning.', 'alert-secondary', 6000);
    });
  </script>
</body>

</html>
