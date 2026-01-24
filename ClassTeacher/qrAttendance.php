<?php 
error_reporting(0);
include '../Includes/dbcon.php';
include '../Includes/session.php';

header('Location: index.php');
exit;

$query = "SELECT tblclass.className,tblclasssemister.semisterName 
    FROM tblclassteacher
    INNER JOIN tblclass ON tblclass.Id = tblclassteacher.classId
    INNER JOIN tblclasssemister ON tblclasssemister.Id = tblclassteacher.classArmId
    Where tblclassteacher.Id = '$_SESSION[userId]'";
$rs = $conn->query($query);
$rrw = $rs->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<?php include 'Includes/header.php';?>
<body id="page-top">
  <div id="wrapper">
    <?php include "Includes/sidebar.php";?>
    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">
        <?php include "Includes/topbar.php";?>

        <div class="container-fluid" id="container-wrapper">
          <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Attendance Using QR Code (<?php echo $rrw['className'].' - '.$rrw['semisterName'];?>)</h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">Attendance Using QR Code</li>
            </ol>
          </div>

          <div class="row">
            <div class="col-lg-7">
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">All Students</h6>
                </div>
                <div class="table-responsive p-3">
                  <table class="table align-items-center table-flush table-hover" id="qrAttendanceStudentTable">
                    <thead class="thead-light">
                      <tr>
                        <th>#</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Admission No</th>
                        <th>Action</th>
                        <th>Check Time</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr id="noScannedRow"><td colspan="6"><div class="alert alert-secondary" role="alert">No scanned students yet.</div></td></tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <div class="col-lg-5">
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">Scan QR Code</h6>
                </div>
                <div class="card-body">
                  <div class="form-group">
                    <label class="form-control-label">Check-Out Allowed After (minutes)<span class="text-danger ml-2">*</span></label>
                    <input type="number" class="form-control" id="minCheckoutGap" value="30" min="1" />
                    <small class="text-muted">Teacher can set the minimum minutes between Check-In and Check-Out.</small>
                  </div>

                  <div class="alert alert-warning" id="secureContextWarning" style="display:none;"></div>

                  <div class="form-group">
                    <button type="button" class="btn btn-outline-primary btn-sm" id="requestCameraPerm">Request Camera Permission</button>
                    <small class="text-muted d-block mt-1">If camera doesn’t start, click this and allow permission.</small>
                  </div>

                  <div class="form-group">
                    <button type="button" class="btn btn-primary btn-sm" id="openCameraBtn">Open Camera</button>
                    <button type="button" class="btn btn-outline-danger btn-sm" id="closeCameraBtn" disabled>Close Camera</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="switchCameraBtn" disabled>Switch Camera</button>
                    <small class="text-muted d-block mt-1">Use Open Camera to start scanning. Use Close Camera to stop scanning.</small>
                  </div>

                  <div class="form-group">
                    <label class="form-control-label">Camera</label>
                    <select class="form-control" id="cameraSelect"></select>
                  </div>

                  <div id="qr-reader" class="mb-3"></div>

                  <div class="alert alert-info" id="scanStatus" style="display:none;"></div>

                  <div class="alert alert-secondary" id="scanDebug" style="display:none;"></div>

                  <div class="d-flex align-items-start" id="scanResult" style="display:none;">
                    <img src="img/user-icn.png" id="scanResultPhoto" alt="Student Photo" />
                    <div class="ml-3">
                      <div><strong>Name:</strong> <span id="scanResultName"></span></div>
                      <div><strong>Admission No:</strong> <span id="scanResultAdm"></span></div>
                      <div><strong>Action:</strong> <span id="scanResultAction"></span></div>
                      <div><strong>Time:</strong> <span id="scanResultTime"></span></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
      <?php include "Includes/footer.php";?>
    </div>
  </div>

  <a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
  </a>

  <span id="version-ruangadmin" style="display:none;"></span>

  <script src="../vendor/jquery/jquery.min.js"></script>
  <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
  <script src="js/ruang-admin.min.js"></script>

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
    var lastText = null;
    var lastTextAt = 0;
    var lastScanError = null;
    var lastScanErrorAt = 0;
    var scanErrorCount = 0;
    var isScanning = false;
    var statusHideTimer = null;
    var scannedIndex = {};
    var availableCameras = [];
    var currentFacingMode = null;

    function escapeHtml(s) {
      return String(s || '').replace(/[&<>"']/g, function(c) {
        return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[c]);
      });
    }

    function upsertScannedStudentRow(data) {
      if (!data || !data.admissionNumber) return;

      var tbody = document.querySelector('#qrAttendanceStudentTable tbody');
      var adm = String(data.admissionNumber);
      var firstName = '';
      var lastName = '';
      if (data.studentName) {
        var parts = String(data.studentName).trim().split(/\s+/);
        firstName = parts[0] || '';
        lastName = parts.length > 1 ? parts.slice(1).join(' ') : '';
      }

      var action = data.action || '';
      var time = data.time || '';

      var existing = scannedIndex[adm] || null;
      if (!existing) {
        var tr = document.createElement('tr');
        tr.setAttribute('data-adm', adm);
        tr.innerHTML =
          '<td class="sn"></td>'+
          '<td>'+escapeHtml(firstName)+'</td>'+
          '<td>'+escapeHtml(lastName)+'</td>'+
          '<td>'+escapeHtml(adm)+'</td>'+
          '<td class="action">'+escapeHtml(action)+'</td>'+
          '<td class="time">'+escapeHtml(time)+'</td>';

        var noRow = document.getElementById('noScannedRow');
        if (noRow) noRow.remove();

        tbody.insertBefore(tr, tbody.firstChild);
        scannedIndex[adm] = tr;
      } else {
        var actionCell = existing.querySelector('.action');
        var timeCell = existing.querySelector('.time');
        if (actionCell) actionCell.textContent = action;
        if (timeCell) timeCell.textContent = time;
        tbody.insertBefore(existing, tbody.firstChild);
      }

      var rows = tbody.querySelectorAll('tr');
      var sn = 0;
      rows.forEach(function(r){
        if (r.id === 'noScannedRow') return;
        sn++;
        var c = r.querySelector('.sn');
        if (c) c.textContent = String(sn);
      });
    }

    function playBeep() {
      try {
        var AudioContext = window.AudioContext || window.webkitAudioContext;
        if (!AudioContext) return;
        var ctx = new AudioContext();
        var osc = ctx.createOscillator();
        var gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.value = 880;
        gain.gain.value = 0.08;
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start();
        setTimeout(function(){
          osc.stop();
          if (ctx && typeof ctx.close === 'function') ctx.close();
        }, 180);
      } catch (e) {
        // ignore audio errors
      }
    }

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
        }, ms);
      }
    }

    function showDebug(msg) {
      var el = document.getElementById('scanDebug');
      el.textContent = '';
      el.style.display = 'none';
    }

    function updateDebug() {
      var parts = [];
      parts.push('Last decoded text: ' + (lastText ? lastText : '(none yet)'));
      parts.push('Scan errors: ' + scanErrorCount);
      if (lastScanError) {
        parts.push('Last scan error: ' + lastScanError);
      }
      showDebug(parts.join(' | '));
    }

    function showResult(data) {
      document.getElementById('scanResultName').textContent = data.studentName || '';
      document.getElementById('scanResultAdm').textContent = data.admissionNumber || '';
      document.getElementById('scanResultAction').textContent = data.action || '';
      document.getElementById('scanResultTime').textContent = data.time || '';

      var photo = document.getElementById('scanResultPhoto');
      photo.src = data.photoUrl || 'img/user-icn.png';

      document.getElementById('scanResult').style.display = 'flex';
    }

    async function fetchCameras() {
      var ok = await ensureHtml5QrLoaded();
      if (!ok) {
        showStatus('QR scanner library failed to load. Please check internet access or allow CDN requests (unpkg/jsdelivr/cdnjs).', 'alert-danger');
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

      // Prefer back camera if label hints it
      var back = devices.find(function(d){ return (d.label || '').toLowerCase().includes('back'); });
      if (back) select.value = back.id;

      return devices;
    }

    async function startScanner(cameraIdOrConfig) {
      if (!cameraIdOrConfig) return;

      var ok = await ensureHtml5QrLoaded();
      if (!ok) {
        showStatus('QR scanner library failed to load. Please check internet access or allow CDN requests (unpkg/jsdelivr/cdnjs).', 'alert-danger');
        return;
      }

      if (!html5QrCode) {
        html5QrCode = new Html5Qrcode('qr-reader');
      } else {
        try { await html5QrCode.stop(); } catch (e) {}
        try { await html5QrCode.clear(); } catch (e) {}
      }

      showStatus('Starting camera... If prompted, allow camera access.', 'alert-info');

      scanErrorCount = 0;
      lastScanError = null;
      lastScanErrorAt = 0;
      updateDebug();

      try {
        await html5QrCode.start(
          cameraIdOrConfig,
          {
            fps: 15,
            qrbox: function(viewfinderWidth, viewfinderHeight) {
              var minEdge = Math.min(viewfinderWidth, viewfinderHeight);
              var edge = Math.floor(minEdge * 0.75);
              edge = Math.max(240, Math.min(edge, 360));
              return { width: edge, height: edge };
            },
            experimentalFeatures: {
              useBarCodeDetectorIfSupported: true
            },
            disableFlip: false
          },
          function(decodedText) {
            var now = Date.now();
            if (decodedText === lastText && (now - lastTextAt) < 2500) return;
            lastText = decodedText;
            lastTextAt = now;
            playBeep();
            updateDebug();
            onScan(decodedText);
          },
          function(errorMessage) {
            scanErrorCount++;
            var now = Date.now();
            var msg = String(errorMessage || '');
            var noisy = msg.toLowerCase().includes('no multiformat readers were able to detect the code');
            if (!noisy && (now - lastScanErrorAt) > 1000) {
              lastScanError = msg;
              lastScanErrorAt = now;
              updateDebug();
            }
          }
        );
      } catch (e) {
        var msg = (e && (e.message || e.name)) ? (e.name ? (e.name + ': ' + (e.message || '')) : e.message) : String(e);
        showStatus('Camera failed to start. ' + msg, 'alert-danger');
        throw e;
      }

      isScanning = true;
      document.getElementById('openCameraBtn').disabled = true;
      document.getElementById('closeCameraBtn').disabled = false;
      document.getElementById('switchCameraBtn').disabled = false;
      showStatus('Camera started. Scan a student QR code.', 'alert-success');
      updateDebug();
    }

    async function stopScanner() {
      try {
        if (html5QrCode) {
          try { await html5QrCode.stop(); } catch (e) {}
          try { await html5QrCode.clear(); } catch (e) {}
        }
      } finally {
        isScanning = false;
        currentFacingMode = null;
        document.getElementById('openCameraBtn').disabled = false;
        document.getElementById('closeCameraBtn').disabled = true;
        document.getElementById('switchCameraBtn').disabled = true;
        showStatus('Camera closed. Scanning is off.', 'alert-secondary');
        updateDebug();
      }
    }

    function onScan(qrText) {
      var minGap = parseInt(document.getElementById('minCheckoutGap').value || '30', 10);
      if (!minGap || minGap < 1) minGap = 30;

      showStatus('QR detected. Processing...', 'alert-info');

      $.ajax({
        url: 'ajaxQrAttendance.php',
        method: 'POST',
        dataType: 'json',
        data: {
          qrText: qrText,
          minGap: minGap
        },
        success: function(resp) {
          if (!resp || !resp.ok) {
            showStatus((resp && resp.message) ? resp.message : 'Scan failed.', 'alert-danger', 6000);
            return;
          }

          var data = resp.data || {};
          showResult(data);
          upsertScannedStudentRow(data);

          if (data.action === 'Check-In') {
            showStatus(resp.message || 'Check-In recorded.', 'alert-success', 6000);
            playBeep();
          } else if (data.action === 'Check-Out') {
            showStatus(resp.message || 'Check-Out recorded.', 'alert-success', 6000);
            playBeep();
          } else {
            showStatus(resp.message || 'Success', 'alert-success');
          }
        },
        error: function() {
          showStatus('Server error while processing scan.', 'alert-danger', 6000);
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

        document.getElementById('requestCameraPerm').addEventListener('click', async function() {
          try {
            var ok = await ensureHtml5QrLoaded();
            if (!ok) {
              showStatus('QR scanner library failed to load. Please check internet access or allow CDN requests (unpkg/jsdelivr/cdnjs).', 'alert-danger');
              return;
            }
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
              showStatus('Camera API not supported in this browser.', 'alert-danger');
              return;
            }
            var stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
            stream.getTracks().forEach(function(t){ t.stop(); });
            showStatus('Camera permission granted. Loading cameras...', 'alert-success');
            await fetchCameras();
          } catch (e) {
            var msg = (e && (e.message || e.name)) ? (e.name ? (e.name + ': ' + (e.message || '')) : e.message) : String(e);
            showStatus('Permission request failed. ' + msg, 'alert-danger');
          }
        });

        await fetchCameras();
        var select = document.getElementById('cameraSelect');
        select.addEventListener('change', function() {
          currentFacingMode = null;
          if (isScanning) startScanner(select.value);
        });

        document.getElementById('switchCameraBtn').addEventListener('click', async function() {
          try {
            if (!isScanning) return;

            var select = document.getElementById('cameraSelect');
            var currentId = select.value;

            if (availableCameras && availableCameras.length >= 2) {
              var idx = availableCameras.findIndex(function(c){ return c.id === currentId; });
              var nextIdx = (idx >= 0) ? ((idx + 1) % availableCameras.length) : 0;
              select.value = availableCameras[nextIdx].id;
              currentFacingMode = null;
              await startScanner(select.value);
              return;
            }

            currentFacingMode = (currentFacingMode === 'environment') ? 'user' : 'environment';
            await startScanner({ facingMode: currentFacingMode });
          } catch (e) {
            var msg = (e && (e.message || e.name)) ? (e.name ? (e.name + ': ' + (e.message || '')) : e.message) : String(e);
            showStatus('Unable to switch camera. ' + msg, 'alert-danger', 3000);
          }
        });

        document.getElementById('openCameraBtn').addEventListener('click', async function() {
          var id = select.value;
          if (!id) {
            currentFacingMode = 'environment';
            await startScanner({ facingMode: currentFacingMode });
            return;
          }
          currentFacingMode = null;
          await startScanner(id);
        });

        document.getElementById('closeCameraBtn').addEventListener('click', function() {
          stopScanner();
        });

        showStatus('Ready. Click Open Camera to start scanning.', 'alert-secondary');
        updateDebug();
      } catch (e) {
        var msg = (e && (e.message || e.name)) ? (e.name ? (e.name + ': ' + (e.message || '')) : e.message) : String(e);
        showStatus('Unable to start camera. ' + msg, 'alert-danger');
      }
    });
  </script>
</body>

</html>
