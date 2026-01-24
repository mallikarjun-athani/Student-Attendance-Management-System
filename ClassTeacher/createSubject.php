<?php 
error_reporting(0);
include '../Includes/dbcon.php';
include '../Includes/session.php';

// Ensure table exists
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

$statusMsg = '';
$editing = false;
$editId = 0;
$editSyllabusType = '';
$editSubjectName = '';

if (isset($_GET['editId'])) {
  $editId = intval($_GET['editId']);
  if ($editId > 0) {
    $stmt = $conn->prepare("SELECT Id, syllabusType, subjectName FROM tblsubjects WHERE Id = ? AND classId = ? AND classArmId = ? LIMIT 1");
    $stmt->bind_param('iss', $editId, $_SESSION['classId'], $_SESSION['classArmId']);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if ($row) {
      $editing = true;
      $editSyllabusType = $row['syllabusType'];
      $editSubjectName = $row['subjectName'];
    }
  }
}

if (isset($_GET['deleteId'])) {
  $deleteId = intval($_GET['deleteId']);
  if ($deleteId > 0) {
    $stmt = $conn->prepare("DELETE FROM tblsubjects WHERE Id = ? AND classId = ? AND classArmId = ?");
    $stmt->bind_param('iss', $deleteId, $_SESSION['classId'], $_SESSION['classArmId']);
    if ($stmt->execute()) {
      $statusMsg = "<div class='alert alert-success' data-autohide='1'>Deleted Successfully</div>";
    } else {
      $statusMsg = "<div class='alert alert-danger' data-autohide='1'>Failed to delete</div>";
    }
    $stmt->close();
  }
}

if (isset($_POST['save'])) {
  $syllabusType = isset($_POST['syllabusType']) ? trim($_POST['syllabusType']) : '';
  $subjectName = isset($_POST['subjectName']) ? trim($_POST['subjectName']) : '';
  $now = date('Y-m-d H:i:s');

  if ($syllabusType === '' || $subjectName === '') {
    $statusMsg = "<div class='alert alert-danger' data-autohide='1'>All fields are required.</div>";
  } else {
    if (isset($_POST['editId']) && intval($_POST['editId']) > 0) {
      $id = intval($_POST['editId']);
      $stmt = $conn->prepare("UPDATE tblsubjects SET syllabusType = ?, subjectName = ?, updatedAt = ? WHERE Id = ? AND classId = ? AND classArmId = ?");
      $stmt->bind_param('sssiss', $syllabusType, $subjectName, $now, $id, $_SESSION['classId'], $_SESSION['classArmId']);
      if ($stmt->execute()) {
        $statusMsg = "<div class='alert alert-success' data-autohide='1'>Saved Successfully</div>";
        $editing = false;
        $editId = 0;
        $editSyllabusType = '';
        $editSubjectName = '';
      } else {
        $statusMsg = "<div class='alert alert-danger' data-autohide='1'>Failed to update</div>";
      }
      $stmt->close();
    } else {
      $stmtD = $conn->prepare("SELECT Id FROM tblsubjects WHERE classId = ? AND classArmId = ? AND syllabusType = ? AND subjectName = ? LIMIT 1");
      $stmtD->bind_param('ssss', $_SESSION['classId'], $_SESSION['classArmId'], $syllabusType, $subjectName);
      $stmtD->execute();
      $resD = $stmtD->get_result();
      $dup = $resD && $resD->num_rows > 0;
      $stmtD->close();

      if ($dup) {
        $statusMsg = "<div class='alert alert-danger' data-autohide='1'>Subject already exists.</div>";
      } else {
        $stmt = $conn->prepare("INSERT INTO tblsubjects(classId,classArmId,syllabusType,subjectName,createdAt,updatedAt) VALUES(?,?,?,?,?,?)");
        $stmt->bind_param('ssssss', $_SESSION['classId'], $_SESSION['classArmId'], $syllabusType, $subjectName, $now, $now);
        if ($stmt->execute()) {
          $statusMsg = "<div class='alert alert-success' data-autohide='1'>Saved Successfully</div>";
        } else {
          $statusMsg = "<div class='alert alert-danger' data-autohide='1'>Failed to save</div>";
        }
        $stmt->close();
      }
    }
  }
}

// syllabus types options: distinct from students for this class/semester, fallback to NEP/SEP
$syllabusTypes = [];
$q = $conn->prepare("SELECT DISTINCT syllabusType FROM tblstudents WHERE classId = ? AND classArmId = ? AND syllabusType <> '' ORDER BY syllabusType ASC");
$q->bind_param('ss', $_SESSION['classId'], $_SESSION['classArmId']);
$q->execute();
$r = $q->get_result();
if ($r) {
  while ($row = $r->fetch_assoc()) {
    $syllabusTypes[] = $row['syllabusType'];
  }
}
$q->close();
if (count($syllabusTypes) === 0) {
  $syllabusTypes = ['NEP', 'SEP'];
}

// load subjects
$subjects = [];
$stmt = $conn->prepare("SELECT Id, syllabusType, subjectName FROM tblsubjects WHERE classId = ? AND classArmId = ? ORDER BY syllabusType ASC, subjectName ASC");
$stmt->bind_param('ss', $_SESSION['classId'], $_SESSION['classArmId']);
$stmt->execute();
$res = $stmt->get_result();
if ($res) {
  while ($row = $res->fetch_assoc()) {
    $subjects[] = $row;
  }
}
$stmt->close();
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
            <h1 class="h3 mb-0 text-gray-800">Create Subject</h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">Create Subject</li>
            </ol>
          </div>

          <div class="row">
            <div class="col-lg-12">
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">Subject Details</h6>
                  <?php echo $statusMsg; ?>
                </div>
                <div class="card-body">
                  <form method="post">
                    <input type="hidden" name="editId" value="<?php echo $editing ? intval($editId) : 0; ?>" />
                    <div class="form-group row mb-3">
                      <div class="col-xl-6">
                        <label class="form-control-label">Syllabus Type<span class="text-danger ml-2">*</span></label>
                        <select required name="syllabusType" class="form-control">
                          <option value="">--Select Syllabus Type--</option>
                          <?php foreach ($syllabusTypes as $st) {
                            $sel = ($editing && $editSyllabusType === $st) ? 'selected' : '';
                            echo '<option value="'.htmlspecialchars($st).'" '.$sel.'>'.htmlspecialchars($st).'</option>';
                          } ?>
                        </select>
                      </div>
                      <div class="col-xl-6">
                        <label class="form-control-label">Subject Name<span class="text-danger ml-2">*</span></label>
                        <input type="text" class="form-control" required name="subjectName" value="<?php echo htmlspecialchars($editing ? $editSubjectName : ''); ?>" placeholder="Subject Name">
                      </div>
                    </div>
                    <button type="submit" name="save" class="btn btn-primary">Save</button>
                    <?php if ($editing) { ?>
                      <a href="createSubject.php" class="btn btn-outline-secondary">Cancel</a>
                    <?php } ?>
                  </form>
                </div>
              </div>

              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">Subjects</h6>
                </div>
                <div class="table-responsive p-3">
                  <table class="table align-items-center table-flush table-hover" id="dataTableHover">
                    <thead class="thead-light">
                      <tr>
                        <th>#</th>
                        <th>Syllabus Type</th>
                        <th>Subject Name</th>
                        <th style="width:70px;">Edit</th>
                        <th style="width:90px;">Delete</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      $sn = 0;
                      if (count($subjects) > 0) {
                        foreach ($subjects as $s) {
                          $sn++;
                          echo "<tr>
                            <td>".$sn."</td>
                            <td>".htmlspecialchars($s['syllabusType'])."</td>
                            <td>".htmlspecialchars($s['subjectName'])."</td>
                            <td><a class='btn btn-sm btn-outline-primary' href='createSubject.php?editId=".intval($s['Id'])."'><i class='fas fa-edit'></i></a></td>
                            <td><a class='btn btn-sm btn-outline-danger' href='createSubject.php?deleteId=".intval($s['Id'])."' onclick='return confirm(".json_encode('Delete this subject?').");'>Delete</a></td>
                          </tr>";
                        }
                      } else {
                        echo "<tr><td colspan='5'><div class='alert alert-secondary' role='alert'>No subjects created yet.</div></td></tr>";
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
  <script src="../vendor/datatables/jquery.dataTables.min.js"></script>
  <script src="../vendor/datatables/dataTables.bootstrap4.min.js"></script>

  <script>
    $(document).ready(function () {
      $('#dataTableHover').DataTable();

      // Auto-hide success message after 3 seconds
      var $msg = $('[data-autohide="1"]');
      if ($msg.length) {
        setTimeout(function(){
          $msg.fadeOut('fast');
        }, 3000);
      }
    });
  </script>
</body>

</html>
