<?php 
error_reporting(0);
include '../Includes/dbcon.php';
include '../Includes/session.php';

$query = "SELECT tblclass.className,tblclasssemister.semisterName 
    FROM tblclassteacher
    INNER JOIN tblclass ON tblclass.Id = tblclassteacher.classId
    INNER JOIN tblclasssemister ON tblclasssemister.Id = tblclassteacher.classArmId
    Where tblclassteacher.Id = '$_SESSION[userId]'";

$rs = $conn->query($query);
$rrw = $rs->fetch_assoc();

$qrSecret = 'AMS_QR_SECRET_2025';
$today = date('Y-m-d');

$teacherId = intval($_SESSION['userId']);
$classId = strval($_SESSION['classId']);
$classArmId = strval($_SESSION['classArmId']);

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tblqr_access (
  Id int(10) NOT NULL AUTO_INCREMENT,
  teacherId int(10) NOT NULL,
  classId varchar(10) NOT NULL,
  classArmId varchar(10) NOT NULL,
  dateTaken varchar(20) NOT NULL,
  isOn tinyint(1) NOT NULL DEFAULT 0,
  token varchar(64) DEFAULT NULL,
  createdAt datetime DEFAULT NULL,
  updatedAt datetime DEFAULT NULL,
  PRIMARY KEY (Id),
  UNIQUE KEY uniq_class_day (classId, classArmId, dateTaken)
) ENGINE=MyISAM DEFAULT CHARSET=latin1");

$qrIsOn = 0;
$qrToken = '';
$stmtAcc = $conn->prepare("SELECT isOn, token FROM tblqr_access WHERE classId = ? AND classArmId = ? AND dateTaken = ? LIMIT 1");
$stmtAcc->bind_param('sss', $classId, $classArmId, $today);
$stmtAcc->execute();
$resAcc = $stmtAcc->get_result();
if ($resAcc && $resAcc->num_rows > 0) {
  $r = $resAcc->fetch_assoc();
  $qrIsOn = intval($r['isOn']);
  $qrToken = isset($r['token']) ? strval($r['token']) : '';
}
$stmtAcc->close();

if ($qrIsOn === 1 && $qrToken === '') {
  $qrToken = bin2hex(random_bytes(16));
  $nowDt = date('Y-m-d H:i:s');
  $stmtFix = $conn->prepare("INSERT INTO tblqr_access(teacherId,classId,classArmId,dateTaken,isOn,token,createdAt,updatedAt) VALUES(?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE token = VALUES(token), updatedAt = VALUES(updatedAt)");
  $isOn = 1;
  $stmtFix->bind_param('isssisss', $teacherId, $classId, $classArmId, $today, $isOn, $qrToken, $nowDt, $nowDt);
  $stmtFix->execute();
  $stmtFix->close();
}
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
            <h1 class="h3 mb-0 text-gray-800">Generate QR Codes (<?php echo $rrw['className'].' - '.$rrw['semisterName'];?>)</h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">Generate QR Codes</li>
            </ol>
          </div>

          <div class="row">
            <div class="col-lg-12">
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">Students</h6>
                </div>
                <div class="card-body">
                  <div class="alert alert-info" role="alert">
                    <div><b>QR CODE ACCESS</b></div>
                    <div>ON: Students can scan Today Subjects. OFF: All scans are rejected.</div>
                  </div>

                  <div class="form-group">
                    <div class="custom-control custom-switch">
                      <input type="checkbox" class="custom-control-input" id="globalQrToggle" <?php echo ($qrIsOn === 1) ? 'checked' : ''; ?>>
                      <label class="custom-control-label" for="globalQrToggle" id="globalQrToggleLabel"><?php echo ($qrIsOn === 1) ? 'ON' : 'OFF'; ?></label>
                    </div>
                  </div>

                  <div class="form-group">
                    <input type="text" class="form-control" id="subjectSearch" placeholder="Search subject by name">
                  </div>
                </div>
                <div class="table-responsive p-3">
                  <table class="table align-items-center table-flush table-hover" id="qrSubjectTable">
                    <thead class="thead-light">
                      <tr>
                        <th>#</th>
                        <th>Subject Name</th>
                        <th>QR Code</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                        mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tblsubjects (
                          Id int(10) NOT NULL AUTO_INCREMENT,
                          classId varchar(10) NOT NULL,
                          classArmId varchar(10) NOT NULL,
                          syllabusType varchar(50) NOT NULL,
                          subjectName varchar(255) NOT NULL,
                          createdAt datetime DEFAULT NULL,
                          updatedAt datetime DEFAULT NULL,
                          PRIMARY KEY (Id)
                        ) ENGINE=MyISAM DEFAULT CHARSET=latin1");

                        mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbltodaysubjects (
                          Id int(10) NOT NULL AUTO_INCREMENT,
                          classId varchar(10) NOT NULL,
                          classArmId varchar(10) NOT NULL,
                          dateTaken varchar(20) NOT NULL,
                          subjectId int(10) NOT NULL,
                          createdAt datetime DEFAULT NULL,
                          PRIMARY KEY (Id)
                        ) ENGINE=MyISAM DEFAULT CHARSET=latin1");

                        $query = "SELECT s.Id, s.subjectName
                          FROM tblsubjects s
                          INNER JOIN tbltodaysubjects ts
                            ON ts.subjectId = s.Id
                          WHERE s.classId = '$_SESSION[classId]'
                            AND s.classArmId = '$_SESSION[classArmId]'
                            AND ts.classId = '$_SESSION[classId]'
                            AND ts.classArmId = '$_SESSION[classArmId]'
                            AND ts.dateTaken = '$today'
                          ORDER BY s.subjectName ASC";
                        $rs = $conn->query($query);
                        $num = $rs ? $rs->num_rows : 0;
                        $sn=0;
                        if($num > 0)
                        { 
                          while ($rows = $rs->fetch_assoc())
                          {
                            $sn = $sn + 1;
                            $subjectId = $rows['Id'];
                            $subjectName = $rows['subjectName'];

                            $payload = '';
                            if ($qrIsOn === 1 && $qrToken !== '') {
                              $sigData = $subjectId.'|'.$_SESSION['classId'].'|'.$_SESSION['classArmId'].'|'.$today.'|'.$qrToken;
                              $sig = hash_hmac('sha256', $sigData, $qrSecret);
                              $payload = "AMS_SUBJ|".$subjectId."|".$_SESSION['classId']."|".$_SESSION['classArmId']."|".$today."|".$qrToken."|".$sig;
                            }
                            echo "
                              <tr data-subject=\"".htmlspecialchars($subjectName, ENT_QUOTES)."\" data-name=\"".htmlspecialchars(strtolower($subjectName), ENT_QUOTES)."\"> 
                                <td>".$sn."</td>
                                <td>".htmlspecialchars($subjectName)."</td>
                                <td>
                                  <div class=\"qr-cell\">
                                    <div class=\"qr-side\">
                                      <div class=\"qr-box\" data-qrtext=\"".htmlspecialchars($payload, ENT_QUOTES)."\"></div>
                                      <button type=\"button\" class=\"btn btn-sm btn-primary mt-2 download-qr\" ".(($qrIsOn === 1) ? '' : 'disabled').">Download QR Code</button>
                                    </div>
                                  </div>
                                </td>
                              </tr>
                            ";
                          }
                        }
                        else
                        {
                          echo "<tr><td colspan='3'><div class='alert alert-danger' role='alert'>No Record Found!</div></td></tr>";
                        }
                      ?>
                    </tbody>
                  </table>
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

  <script src="../vendor/jquery/jquery.min.js"></script>
  <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
  <script src="js/ruang-admin.min.js"></script>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

  <script>
    function renderQrs(force) {
      document.querySelectorAll('.qr-box').forEach(function(el) {
        if (!force && el.dataset.rendered === '1') return;

        var cell = el.closest('.qr-cell');
        var details = cell ? cell.querySelector('.qr-student-details') : null;
        var detailsHeight = details ? details.getBoundingClientRect().height : 0;

        var size = Math.floor(detailsHeight);
        if (!size || size < 120) size = 160;
        if (size > 180) size = 180;

        el.style.width = size + 'px';
        el.style.height = size + 'px';

        el.dataset.rendered = '1';
        var text = el.getAttribute('data-qrtext');
        el.innerHTML = '';

        if (!text) {
          el.innerHTML = '<div class="text-muted" style="font-size:12px;text-align:center;">QR Access is OFF</div>';
          return;
        }

        new QRCode(el, {
          text: text,
          width: size,
          height: size,
          correctLevel: QRCode.CorrectLevel.H
        });
      });
    }

    function downloadQrFromRow(row) {
      var box = row.querySelector('.qr-box');
      var subj = row.getAttribute('data-subject') || 'subject';

      var img = box.querySelector('img');
      var canvas = box.querySelector('canvas');
      var dataUrl = null;

      var padding = 24;
      if (canvas) {
        var out = document.createElement('canvas');
        out.width = canvas.width + (padding * 2);
        out.height = canvas.height + (padding * 2);
        var ctx = out.getContext('2d');
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, out.width, out.height);
        ctx.drawImage(canvas, padding, padding);
        dataUrl = out.toDataURL('image/png');
      } else if (img && img.src) {
        var outImg = document.createElement('canvas');
        var ctx2 = outImg.getContext('2d');
        var tmp = new Image();
        tmp.onload = function() {
          outImg.width = tmp.width + (padding * 2);
          outImg.height = tmp.height + (padding * 2);
          ctx2.fillStyle = '#ffffff';
          ctx2.fillRect(0, 0, outImg.width, outImg.height);
          ctx2.drawImage(tmp, padding, padding);
          var a2 = document.createElement('a');
          a2.href = outImg.toDataURL('image/png');
          a2.download = 'QR_' + subj + '.png';
          document.body.appendChild(a2);
          a2.click();
          document.body.removeChild(a2);
        };
        tmp.src = img.src;
        return;
      }

      if (!dataUrl) return;

      var a = document.createElement('a');
      a.href = dataUrl;
      a.download = 'QR_' + subj + '.png';
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
    }

    document.addEventListener('DOMContentLoaded', function() {
      renderQrs(false);

      document.getElementById('subjectSearch').addEventListener('input', function() {
        var q = (this.value || '').toLowerCase().trim();
        document.querySelectorAll('#qrSubjectTable tbody tr').forEach(function(row) {
          var name = (row.getAttribute('data-name') || '').toLowerCase();
          var show = (q === '') || name.indexOf(q) !== -1;
          row.style.display = show ? '' : 'none';
        });
      });

      document.querySelectorAll('.download-qr').forEach(function(btn) {
        btn.addEventListener('click', function() {
          var row = btn.closest('tr');
          downloadQrFromRow(row);
        });
      });

      document.getElementById('globalQrToggle').addEventListener('change', function() {
        var toggle = this;
        var turnOn = toggle.checked ? 1 : 0;
        var label = document.getElementById('globalQrToggleLabel');

        $.ajax({
          url: 'ajaxQrAccessToggle.php',
          method: 'POST',
          dataType: 'json',
          data: { turnOn: turnOn },
          success: function(resp) {
            if (!resp || !resp.ok) {
              toggle.checked = !toggle.checked;
              if (label) label.textContent = toggle.checked ? 'ON' : 'OFF';
              alert((resp && resp.message) ? resp.message : 'Failed to update QR access.');
              return;
            }

            if (label) label.textContent = turnOn ? 'ON' : 'OFF';
            window.location.reload();
          },
          error: function() {
            toggle.checked = !toggle.checked;
            if (label) label.textContent = toggle.checked ? 'ON' : 'OFF';
            alert('Server error while updating QR access.');
          }
        });
      });

      window.addEventListener('resize', function() {
        renderQrs(true);
      });
    });
  </script>
</body>

</html>
