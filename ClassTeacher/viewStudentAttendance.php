
<?php 
error_reporting(0);
include '../Includes/dbcon.php';
include '../Includes/session.php';

include '../Includes/mailer.php';

// Ensure required tables exist
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

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tblsubjectattendance (
  Id int(10) NOT NULL AUTO_INCREMENT,
  admissionNumber varchar(255) NOT NULL,
  subjectId int(10) NOT NULL,
  classId varchar(10) NOT NULL,
  classArmId varchar(10) NOT NULL,
  dateTaken varchar(20) NOT NULL,
  status varchar(10) NOT NULL,
  createdAt datetime DEFAULT NULL,
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


$statusMsg = '';
$overallPct = null;
$overallTotal = 0;
$overallPresent = 0;
$selectedStudent = null;
$selectedAdmissionNumber = '';
$subjectPctSelected = null;
$subjectPctRows = [];
$selectedSubjectIdPct = '';
$pctFilter = '';

if (isset($_POST['view']) || isset($_POST['sendMail'])) {
  $selectedAdmissionNumber = isset($_POST['admissionNumber']) ? $_POST['admissionNumber'] : '';
  $admissionNumber = $selectedAdmissionNumber;
  $selectedSubjectIdPct = isset($_POST['subjectIdPct']) ? $_POST['subjectIdPct'] : '';
  $pctFilter = isset($_POST['pctFilter']) ? strtolower(trim($_POST['pctFilter'])) : '';
  if ($pctFilter !== 'below75' && $pctFilter !== 'above75' && $pctFilter !== '') {
    $pctFilter = '';
  }

  $type = isset($_POST['type']) ? $_POST['type'] : '';

  if (isset($_POST['sendMail']) && $admissionNumber === 'ALL') {
    if ($pctFilter === '') {
      $statusMsg = "<div class='alert alert-danger' data-toast='1'>Please select Below 75% or Above 75% to send bulk mail.</div>";
    } elseif ($type === '') {
      $statusMsg = "<div class='alert alert-danger' data-toast='1'>Please select a Type before sending bulk mail.</div>";
    } else {
      $dateCondTs = "";
      if ($type == "2") {
        $singleDate = isset($_POST['singleDate']) ? $_POST['singleDate'] : '';
        if ($singleDate !== '') {
          $dateCondTs = " AND ts.dateTaken = '".$singleDate."'";
        }
      }
      if ($type == "3") {
        $fromDate = isset($_POST['fromDate']) ? $_POST['fromDate'] : '';
        $toDate = isset($_POST['toDate']) ? $_POST['toDate'] : '';
        if ($fromDate !== '' && $toDate !== '') {
          $dateCondTs = " AND ts.dateTaken between '".$fromDate."' and '".$toDate."'";
        }
      }

      $totalSessions = 0;
      $rsTot = $conn->query("SELECT COUNT(*) as total FROM tbltodaysubjects ts WHERE ts.classId = '$_SESSION[classId]' AND ts.classArmId = '$_SESSION[classArmId]'".$dateCondTs);
      if ($rsTot) {
        $rowTot = $rsTot->fetch_assoc();
        $totalSessions = intval($rowTot['total']);
      }

      if ($totalSessions <= 0) {
        $statusMsg = "<div class='alert alert-danger' data-toast='1'>No taught sessions found for the selected filter.</div>";
      } else {
        $presentByAdm = [];
        $qPres = "SELECT
          s.admissionNumber as admissionNumber,
          SUM(CASE WHEN sa.status='1' THEN 1 ELSE 0 END) as presentCount
        FROM tblstudents s
        LEFT JOIN tbltodaysubjects ts
          ON ts.classId = s.classId
          AND ts.classArmId = s.classArmId".$dateCondTs." 
        LEFT JOIN tblsubjectattendance sa
          ON sa.admissionNumber = s.admissionNumber
          AND sa.subjectId = ts.subjectId
          AND sa.classId = ts.classId
          AND sa.classArmId = ts.classArmId
          AND sa.dateTaken = ts.dateTaken
        WHERE s.classId = '$_SESSION[classId]' AND s.classArmId = '$_SESSION[classArmId]'
        GROUP BY s.admissionNumber";

        $rsPres = $conn->query($qPres);
        if ($rsPres) {
          while ($pr = $rsPres->fetch_assoc()) {
            $presentByAdm[$pr['admissionNumber']] = intval($pr['presentCount']);
          }
        }

        $allowedAdmissions = [];
        foreach ($presentByAdm as $admKey => $pCount) {
          $pct = ($totalSessions > 0) ? (($pCount / $totalSessions) * 100) : 0;
          if ($pctFilter === 'below75' && $pct < 75) {
            $allowedAdmissions[$admKey] = ['pct' => round($pct, 2)];
          }
          if ($pctFilter === 'above75' && $pct >= 75) {
            $allowedAdmissions[$admKey] = ['pct' => round($pct, 2)];
          }
        }

        if (count($allowedAdmissions) === 0) {
          $statusMsg = "<div class='alert alert-danger' data-toast='1'>No students found for the selected percentage filter.</div>";
        } else {
          $subjectTotals = [];
          $rsSubTot = $conn->query("SELECT ts.subjectId as subjectId, subj.subjectName as subjectName, COUNT(ts.Id) as totalCount
            FROM tbltodaysubjects ts
            INNER JOIN tblsubjects subj ON subj.Id = ts.subjectId
            WHERE ts.classId = '$_SESSION[classId]' AND ts.classArmId = '$_SESSION[classArmId]'".$dateCondTs."
            GROUP BY ts.subjectId, subj.subjectName
            ORDER BY subj.subjectName ASC");
          if ($rsSubTot) {
            while ($st = $rsSubTot->fetch_assoc()) {
              $sid = strval($st['subjectId']);
              $subjectTotals[$sid] = [
                'subjectName' => $st['subjectName'],
                'total' => intval($st['totalCount'])
              ];
            }
          }

          $presentByAdmSubj = [];
          $admsForIn = array_keys($allowedAdmissions);
          $placeAdm = implode(',', array_fill(0, count($admsForIn), '?'));
          $typesAdm = str_repeat('s', count($admsForIn));
          $sqlPres = "SELECT sa.admissionNumber as admissionNumber, sa.subjectId as subjectId,
              SUM(CASE WHEN sa.status='1' THEN 1 ELSE 0 END) as presentCount
            FROM tblsubjectattendance sa
            INNER JOIN tbltodaysubjects ts
              ON ts.subjectId = sa.subjectId
              AND ts.classId = sa.classId
              AND ts.classArmId = sa.classArmId
              AND ts.dateTaken = sa.dateTaken
            WHERE sa.classId = ? AND sa.classArmId = ? AND sa.admissionNumber IN (".$placeAdm.")";
          $stmtPres = $conn->prepare($sqlPres);
          $bindTypes2 = 'ss'.$typesAdm;
          $params2 = array_merge([$_SESSION['classId'], $_SESSION['classArmId']], $admsForIn);
          $bindParams2 = [];
          $bindParams2[] = &$bindTypes2;
          foreach ($params2 as $k => $v) {
            $bindParams2[] = &$params2[$k];
          }
          call_user_func_array([$stmtPres, 'bind_param'], $bindParams2);
          $stmtPres->execute();
          $resPres2 = $stmtPres->get_result();
          if ($resPres2) {
            while ($pr2 = $resPres2->fetch_assoc()) {
              $admK = strval($pr2['admissionNumber']);
              $sidK = strval($pr2['subjectId']);
              if (!isset($presentByAdmSubj[$admK])) {
                $presentByAdmSubj[$admK] = [];
              }
              $presentByAdmSubj[$admK][$sidK] = intval($pr2['presentCount']);
            }
          }
          $stmtPres->close();

          $adms = array_keys($allowedAdmissions);
          $placeholders = implode(',', array_fill(0, count($adms), '?'));
          $typesStr = str_repeat('s', count($adms));
          $sqlE = "SELECT admissionNumber, firstName, lastName, otherName, emailAddress FROM tblstudents WHERE classId = ? AND classArmId = ? AND admissionNumber IN (".$placeholders.")";
          $stmtE = $conn->prepare($sqlE);

          $bindTypes = 'ss'.$typesStr;
          $params = array_merge([$_SESSION['classId'], $_SESSION['classArmId']], $adms);
          $bindParams = [];
          $bindParams[] = &$bindTypes;
          foreach ($params as $k => $v) {
            $bindParams[] = &$params[$k];
          }
          call_user_func_array([$stmtE, 'bind_param'], $bindParams);

          $stmtE->execute();
          $resE = $stmtE->get_result();

          $sent = 0;
          $failed = 0;
          $skipped = 0;

          $mailSubject = 'Attendance Status';
          while ($stu = ($resE ? $resE->fetch_assoc() : null)) {
            $email = trim($stu['emailAddress'] ?? '');
            if ($email === '') {
              $skipped++;
              continue;
            }

            $to = $email;
            $toName = trim(($stu['firstName'] ?? '').' '.($stu['lastName'] ?? '').' '.($stu['otherName'] ?? ''));
            $pctVal = isset($allowedAdmissions[$stu['admissionNumber']]['pct']) ? $allowedAdmissions[$stu['admissionNumber']]['pct'] : '';

            $lines = [];
            $lines[] = "Student Name: ".$toName;
            $lines[] = "Registration Number: ".($stu['admissionNumber'] ?? '');
            $lines[] = "";
            $lines[] = "Subject-wise Attendance:";

            foreach ($subjectTotals as $sid => $meta) {
              $t = intval($meta['total']);
              $p = 0;
              if (isset($presentByAdmSubj[strval($stu['admissionNumber'])]) && isset($presentByAdmSubj[strval($stu['admissionNumber'])][strval($sid)])) {
                $p = intval($presentByAdmSubj[strval($stu['admissionNumber'])][strval($sid)]);
              }
              $spct = ($t > 0) ? round(($p / $t) * 100, 2) : 0;

              if ($pctFilter === 'below75') {
                $mark = ($spct < 75) ? ' [LOW]' : '';
                $lines[] = "- ".$meta['subjectName'].": ".$spct."% (".$p."/".$t.")".$mark;
              } else {
                $result = ($spct >= 75) ? 'Present' : 'Absent';
                $lines[] = "- ".$meta['subjectName'].": ".$spct."% (".$p."/".$t.") | Result: ".$result;
              }
            }

            $lines[] = "";
            $lines[] = "Overall Attendance (All Subjects Combined): ".$pctVal."%";
            $lines[] = "";
            if ($pctFilter === 'below75') {
              $lines[] = "If you do not complete the required attendance, you are not eligible to attend the examination.";
            } else {
              $lines[] = "You are eligible.";
            }

            $message = implode("\n", $lines);

            list($ok, $msg) = sendSmtpMail($to, $toName, $mailSubject, $message);
            if ($ok) {
              $sent++;
            } else {
              $failed++;
            }
          }
          $stmtE->close();

          $statusMsg = "<div class='alert alert-success' data-toast='1'>Bulk mail done. Sent: ".$sent.", Failed: ".$failed.", Skipped (no email): ".$skipped.".</div>";
        }
      }
    }
  }

  if ($admissionNumber !== '' && $admissionNumber !== 'ALL') {
    $stmtS = $conn->prepare("SELECT firstName,lastName,otherName,admissionNumber,emailAddress FROM tblstudents WHERE admissionNumber = ? AND classId = ? AND classArmId = ? LIMIT 1");
    $stmtS->bind_param('sss', $admissionNumber, $_SESSION['classId'], $_SESSION['classArmId']);
    $stmtS->execute();
    $resS = $stmtS->get_result();
    $selectedStudent = $resS ? $resS->fetch_assoc() : null;
    $stmtS->close();

    // Overall attendance percentage across all subjects combined:
    // Total sessions = count of taught subject sessions (tbltodaysubjects)
    // Present sessions = count of student's subject attendance records matching those sessions
    $stmtC = $conn->prepare("SELECT
        COUNT(ts.Id) as total,
        SUM(CASE WHEN sa.status='1' THEN 1 ELSE 0 END) as presentCount
      FROM tbltodaysubjects ts
      LEFT JOIN tblsubjectattendance sa
        ON sa.admissionNumber = ?
        AND sa.subjectId = ts.subjectId
        AND sa.classId = ts.classId
        AND sa.classArmId = ts.classArmId
        AND sa.dateTaken = ts.dateTaken
      WHERE ts.classId = ? AND ts.classArmId = ?");
    $stmtC->bind_param('sss', $admissionNumber, $_SESSION['classId'], $_SESSION['classArmId']);
    $stmtC->execute();
    $resC = $stmtC->get_result();
    $cnt = $resC ? $resC->fetch_assoc() : ['total' => 0, 'presentCount' => 0];
    $stmtC->close();

    $overallTotal = intval($cnt['total']);
    $overallPresent = intval($cnt['presentCount']);
    $overallPct = ($overallTotal > 0) ? round(($overallPresent / $overallTotal) * 100, 2) : 0;

    // Subject-wise percentages for the selected student
    $stmtP = $conn->prepare("SELECT
        subj.Id as subjectId,
        subj.subjectName as subjectName,
        COUNT(ts.Id) as totalClasses,
        SUM(CASE WHEN sa.status='1' THEN 1 ELSE 0 END) as presentClasses
      FROM tblsubjects subj
      LEFT JOIN tbltodaysubjects ts
        ON ts.subjectId = subj.Id
        AND ts.classId = subj.classId
        AND ts.classArmId = subj.classArmId
      LEFT JOIN tblsubjectattendance sa
        ON sa.admissionNumber = ?
        AND sa.subjectId = subj.Id
        AND sa.classId = subj.classId
        AND sa.classArmId = subj.classArmId
        AND sa.dateTaken = ts.dateTaken
      WHERE subj.classId = ? AND subj.classArmId = ?
      GROUP BY subj.Id, subj.subjectName
      ORDER BY subj.subjectName ASC");
    $stmtP->bind_param('sss', $admissionNumber, $_SESSION['classId'], $_SESSION['classArmId']);
    $stmtP->execute();
    $resP = $stmtP->get_result();
    if ($resP) {
      while ($r = $resP->fetch_assoc()) {
        $totalC = intval($r['totalClasses']);
        $presentC = intval($r['presentClasses']);
        $pct = ($totalC > 0) ? round(($presentC / $totalC) * 100, 2) : 0;
        $r['pct'] = $pct;
        $subjectPctRows[] = $r;
      }
    }
    $stmtP->close();

    if ($selectedSubjectIdPct !== '') {
      foreach ($subjectPctRows as $sr) {
        if (strval($sr['subjectId']) === strval($selectedSubjectIdPct)) {
          $subjectPctSelected = $sr;
          break;
        }
      }
    }

    if (isset($_POST['sendMail'])) {
      if (!$selectedStudent) {
        $statusMsg = "<div class='alert alert-danger' data-toast='1'>Student not found.</div>";
      } else {
        $email = trim($selectedStudent['emailAddress']);
        if ($email === '') {
          $statusMsg = "<div class='alert alert-danger' data-toast='1'>Student email address is not set.</div>";
        } else {
          $to = $email;
          $subject = 'Attendance Status';

          if ($overallPct >= 75) {
            $message = "Your attendance percentage is {$overallPct}.\nSince your attendance is above 75%, you are eligible to attend the exam.";
          } else {
            $message = "Your attendance percentage is {$overallPct}.\nSince your attendance is below 75%, you are NOT eligible to attend the exam.\nPlease make sure you attend classes regularly.";
          }

          $toName = trim(($selectedStudent['firstName'] ?? '').' '.($selectedStudent['lastName'] ?? '').' '.($selectedStudent['otherName'] ?? ''));
          list($ok, $msg) = sendSmtpMail($to, $toName, $subject, $message);
          if ($ok) {
            $statusMsg = "<div class='alert alert-success' data-toast='1'>".htmlspecialchars($msg)."</div>";
          } else {
            $statusMsg = "<div class='alert alert-danger' data-toast='1'>".htmlspecialchars($msg)."</div>";
          }
        }
      }
    }
  }
}



?>

<!DOCTYPE html>
<html lang="en">

<?php include 'Includes/header.php';?>
<body id="page-top">
  <div id="wrapper">
    <!-- Sidebar -->
      <?php include "Includes/sidebar.php";?>
    <!-- Sidebar -->
    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">
        <!-- TopBar -->
       <?php include "Includes/topbar.php";?>
        <!-- Topbar -->

        <!-- Container Fluid-->
        <div class="container-fluid" id="container-wrapper">
          <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">View Student Attendance</h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">View Student Attendance</li>
            </ol>
          </div>

          <div class="row">
            <div class="col-lg-12">
              <!-- Form Basic -->
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">View Student Attendance</h6>
                  <?php if ($overallPct !== null && $selectedStudent) { ?>
                    <div class="text-right">
                      <span class="badge badge-info">Attendance: <?php echo htmlspecialchars($overallPct); ?>%</span>
                    </div>
                  <?php } ?>
                    <?php echo $statusMsg; ?>
                </div>
                <div class="card-body">
                  <form method="post">
                    <div class="form-group row mb-3">
                        <div class="col-xl-6">
                        <label class="form-control-label">Select Student<span class="text-danger ml-2">*</span></label>
                        <?php
                        $qry= "SELECT * FROM tblstudents where classId = '$_SESSION[classId]' and classArmId = '$_SESSION[classArmId]' ORDER BY firstName ASC";
                        $result = $conn->query($qry);
                        $num = $result->num_rows;		
                        if ($num > 0){
                          echo ' <select required name="admissionNumber" class="form-control mb-3">';
                          echo'<option value="">--Select Student--</option>';
                          echo'<option value="ALL" '.(($selectedAdmissionNumber === 'ALL') ? 'selected' : '').'>All</option>';
                          while ($rows = $result->fetch_assoc()){
                            $val = $rows['admissionNumber'];
                            $label = $rows['firstName'].' '.$rows['lastName'];
                            $sel = ($selectedAdmissionNumber !== '' && $selectedAdmissionNumber === $val) ? 'selected' : '';
                            echo'<option value="'.$val.'" '.$sel.'>'.$label.'</option>';
                          }
                          echo '</select>';
                        }
                            ?>  
                        </div>
                        <div class="col-xl-6">
                        <label class="form-control-label">Type<span class="text-danger ml-2">*</span></label>
                          <select required name="type" onchange="typeDropDown(this.value)" class="form-control mb-3">
                          <option value="">--Select--</option>
                          <option value="1" >All</option>
                          <option value="2" >By Single Date</option>
                          <option value="3" >By Date Range</option>
                        </select>
                        </div>
                    </div>
                    <div class="form-group row mb-3">
                      <div class="col-xl-6">
                        <label class="form-control-label">Attendance Percentage Filter</label>
                        <select name="pctFilter" class="form-control mb-3">
                          <option value="" <?php echo ($pctFilter === '') ? 'selected' : ''; ?>>--None--</option>
                          <option value="below75" <?php echo ($pctFilter === 'below75') ? 'selected' : ''; ?>>Below 75%</option>
                          <option value="above75" <?php echo ($pctFilter === 'above75') ? 'selected' : ''; ?>>Above 75%</option>
                        </select>
                        <small class="text-muted">Applies only when <b>All Students</b> is selected.</small>
                      </div>
                    </div>
                    <?php if ($selectedStudent) { ?>
                    <div class="form-group row mb-3">
                      <div class="col-xl-6">
                        <label class="form-control-label">Subject (for Percentage)</label>
                        <select name="subjectIdPct" class="form-control mb-3">
                          <option value="">--Select Subject--</option>
                          <?php
                            $qrySub = "SELECT Id, subjectName FROM tblsubjects WHERE classId = '$_SESSION[classId]' AND classArmId = '$_SESSION[classArmId]' ORDER BY subjectName ASC";
                            $resSub = $conn->query($qrySub);
                            if ($resSub && $resSub->num_rows > 0) {
                              while ($sr = $resSub->fetch_assoc()) {
                                $sid = $sr['Id'];
                                $sname = $sr['subjectName'];
                                $sel = ($selectedSubjectIdPct !== '' && strval($selectedSubjectIdPct) === strval($sid)) ? 'selected' : '';
                                echo "<option value='".htmlspecialchars($sid, ENT_QUOTES)."' {$sel}>".htmlspecialchars($sname)."</option>";
                              }
                            }
                          ?>
                        </select>
                      </div>
                    </div>
                    <?php } ?>
                      <?php
                        echo"<div id='txtHint'></div>";
                      ?>
                    <!-- <div class="form-group row mb-3">
                        <div class="col-xl-6">
                        <label class="form-control-label">Select Student<span class="text-danger ml-2">*</span></label>
                    </div> -->
                    <button type="submit" name="view" class="btn btn-primary">View Attendance</button>
                    <button type="submit" name="sendMail" class="btn btn-success">Send Mail</button>
                  </form>
                </div>
              </div>

              <?php if ($selectedStudent && $selectedAdmissionNumber !== 'ALL') { ?>
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">Attendance Percentage</h6>
                  <div class="text-right">
                    <span class="badge badge-info">Total Attendance: <?php echo htmlspecialchars($overallPct); ?>%</span>
                  </div>
                </div>
                <div class="card-body">
                  <?php if ($subjectPctSelected) { ?>
                    <div class="alert alert-info" role="alert" style="margin-bottom: 18px;">
                      Subject: <b><?php echo htmlspecialchars($subjectPctSelected['subjectName']); ?></b>
                      &nbsp;|&nbsp;
                      Percentage: <b><?php echo htmlspecialchars($subjectPctSelected['pct']); ?>%</b>
                      &nbsp;|&nbsp;
                      Present: <b><?php echo intval($subjectPctSelected['presentClasses']); ?></b>
                      / Total: <b><?php echo intval($subjectPctSelected['totalClasses']); ?></b>
                    </div>
                  <?php } ?>

                  <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                      <thead class="thead-light">
                        <tr>
                          <th>Subject Name</th>
                          <th style="width:120px; text-align:right;">Present</th>
                          <th style="width:120px; text-align:right;">Total</th>
                          <th style="width:140px; text-align:right;">Percentage</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php if (count($subjectPctRows) > 0) {
                          foreach ($subjectPctRows as $sr) {
                            echo "<tr>";
                            echo "<td>".htmlspecialchars($sr['subjectName'])."</td>";
                            echo "<td style='text-align:right;'>".intval($sr['presentClasses'])."</td>";
                            echo "<td style='text-align:right;'>".intval($sr['totalClasses'])."</td>";
                            echo "<td style='text-align:right; font-weight:bold;'>".htmlspecialchars($sr['pct'])."%</td>";
                            echo "</tr>";
                          }
                        } else {
                          echo "<tr><td colspan='4'><div class='alert alert-secondary' role='alert' style='margin:0;'>No subjects found.</div></td></tr>";
                        } ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
              <?php } ?>

              <!-- Input Group -->
                 <div class="row">
              <div class="col-lg-12">
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">Class Attendance</h6>
                </div>
                <div class="table-responsive p-3">
                  <table class="table align-items-center table-flush table-hover" id="dataTableHover">
                    <thead class="thead-light">
                      <tr>
                        <th>#</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Admission No</th>
                        <th>Subject Name</th>
                        <th>Status</th>
                        <th>Date</th>
                      </tr>
                    </thead>
                   
                    <tbody>

                  <?php

                    if(isset($_POST['view'])){

                       $admissionNumber =  $_POST['admissionNumber'];
                       $type =  $_POST['type'];
                       $isAllStudents = ($admissionNumber === 'ALL');
                       $pctFilterLocal = isset($_POST['pctFilter']) ? strtolower(trim($_POST['pctFilter'])) : '';
                       if ($pctFilterLocal !== 'below75' && $pctFilterLocal !== 'above75' && $pctFilterLocal !== '') {
                         $pctFilterLocal = '';
                       }
                       if (!$isAllStudents) {
                         $pctFilterLocal = '';
                       }
                       $admissionFilterSql = $isAllStudents ? "" : " and tblattendance.admissionNo = '$admissionNumber'";

                       $allowedAdmissions = null;
                       if ($isAllStudents && $pctFilterLocal !== '') {
                         $dateCondTs = "";
                         if ($type == "2") {
                           $singleDate = isset($_POST['singleDate']) ? $_POST['singleDate'] : '';
                           if ($singleDate !== '') {
                             $dateCondTs = " AND ts.dateTaken = '".$singleDate."'";
                           }
                         }
                         if ($type == "3") {
                           $fromDate = isset($_POST['fromDate']) ? $_POST['fromDate'] : '';
                           $toDate = isset($_POST['toDate']) ? $_POST['toDate'] : '';
                           if ($fromDate !== '' && $toDate !== '') {
                             $dateCondTs = " AND ts.dateTaken between '".$fromDate."' and '".$toDate."'";
                           }
                         }

                         $totalSessions = 0;
                         $rsTot = $conn->query("SELECT COUNT(*) as total FROM tbltodaysubjects ts WHERE ts.classId = '$_SESSION[classId]' AND ts.classArmId = '$_SESSION[classArmId]'".$dateCondTs);
                         if ($rsTot) {
                           $rowTot = $rsTot->fetch_assoc();
                           $totalSessions = intval($rowTot['total']);
                         }

                         $presentByAdm = [];
                         if ($totalSessions > 0) {
                           $qPres = "SELECT
                             s.admissionNumber as admissionNumber,
                             SUM(CASE WHEN sa.status='1' THEN 1 ELSE 0 END) as presentCount
                           FROM tblstudents s
                           LEFT JOIN tbltodaysubjects ts
                             ON ts.classId = s.classId
                             AND ts.classArmId = s.classArmId".$dateCondTs." 
                           LEFT JOIN tblsubjectattendance sa
                             ON sa.admissionNumber = s.admissionNumber
                             AND sa.subjectId = ts.subjectId
                             AND sa.classId = ts.classId
                             AND sa.classArmId = ts.classArmId
                             AND sa.dateTaken = ts.dateTaken
                           WHERE s.classId = '$_SESSION[classId]' AND s.classArmId = '$_SESSION[classArmId]'
                           GROUP BY s.admissionNumber";
                           $rsPres = $conn->query($qPres);
                           if ($rsPres) {
                             while ($pr = $rsPres->fetch_assoc()) {
                               $presentByAdm[$pr['admissionNumber']] = intval($pr['presentCount']);
                             }
                           }

                           $allowedAdmissions = [];
                           foreach ($presentByAdm as $admKey => $pCount) {
                             $pct = ($totalSessions > 0) ? (($pCount / $totalSessions) * 100) : 0;
                             if ($pctFilterLocal === 'below75' && $pct < 75) {
                               $allowedAdmissions[$admKey] = true;
                             }
                             if ($pctFilterLocal === 'above75' && $pct >= 75) {
                               $allowedAdmissions[$admKey] = true;
                             }
                           }
                         } else {
                           $allowedAdmissions = [];
                         }
                       }

                       if($type == "1"){ //All Attendance

                        $query = "SELECT
                          sa.Id,
                          sa.status,
                          sa.dateTaken,
                          sa.admissionNumber,
                          s.firstName,
                          s.lastName,
                          subj.subjectName
                        FROM tblsubjectattendance sa
                        INNER JOIN tblstudents s
                          ON s.admissionNumber = sa.admissionNumber
                          AND s.classId = sa.classId
                          AND s.classArmId = sa.classArmId
                        INNER JOIN tblsubjects subj ON subj.Id = sa.subjectId
                        INNER JOIN tbltodaysubjects ts
                          ON ts.subjectId = sa.subjectId
                          AND ts.classId = sa.classId
                          AND ts.classArmId = sa.classArmId
                          AND ts.dateTaken = sa.dateTaken
                        WHERE sa.classId = '$_SESSION[classId]' AND sa.classArmId = '$_SESSION[classArmId]'".
                        ($isAllStudents ? "" : " AND sa.admissionNumber = '$admissionNumber'").
                        " ORDER BY s.firstName ASC, s.lastName ASC, sa.admissionNumber ASC, sa.dateTaken DESC, subj.subjectName ASC";

                       }
                       if($type == "2"){ //Single Date Attendance

                        $singleDate =  $_POST['singleDate'];

                         $query = "SELECT
                          sa.Id,
                          sa.status,
                          sa.dateTaken,
                          sa.admissionNumber,
                          s.firstName,
                          s.lastName,
                          subj.subjectName
                        FROM tblsubjectattendance sa
                        INNER JOIN tblstudents s
                          ON s.admissionNumber = sa.admissionNumber
                          AND s.classId = sa.classId
                          AND s.classArmId = sa.classArmId
                        INNER JOIN tblsubjects subj ON subj.Id = sa.subjectId
                        INNER JOIN tbltodaysubjects ts
                          ON ts.subjectId = sa.subjectId
                          AND ts.classId = sa.classId
                          AND ts.classArmId = sa.classArmId
                          AND ts.dateTaken = sa.dateTaken
                        WHERE sa.dateTaken = '$singleDate' AND sa.classId = '$_SESSION[classId]' AND sa.classArmId = '$_SESSION[classArmId]'".
                        ($isAllStudents ? "" : " AND sa.admissionNumber = '$admissionNumber'").
                        " ORDER BY s.firstName ASC, s.lastName ASC, sa.admissionNumber ASC, subj.subjectName ASC";
                        

                       }
                       if($type == "3"){ //Date Range Attendance

                         $fromDate =  $_POST['fromDate'];
                         $toDate =  $_POST['toDate'];

                         $query = "SELECT
                          sa.Id,
                          sa.status,
                          sa.dateTaken,
                          sa.admissionNumber,
                          s.firstName,
                          s.lastName,
                          subj.subjectName
                        FROM tblsubjectattendance sa
                        INNER JOIN tblstudents s
                          ON s.admissionNumber = sa.admissionNumber
                          AND s.classId = sa.classId
                          AND s.classArmId = sa.classArmId
                        INNER JOIN tblsubjects subj ON subj.Id = sa.subjectId
                        INNER JOIN tbltodaysubjects ts
                          ON ts.subjectId = sa.subjectId
                          AND ts.classId = sa.classId
                          AND ts.classArmId = sa.classArmId
                          AND ts.dateTaken = sa.dateTaken
                        WHERE sa.dateTaken between '$fromDate' and '$toDate' AND sa.classId = '$_SESSION[classId]' AND sa.classArmId = '$_SESSION[classArmId]'".
                        ($isAllStudents ? "" : " AND sa.admissionNumber = '$admissionNumber'").
                        " ORDER BY s.firstName ASC, s.lastName ASC, sa.admissionNumber ASC, sa.dateTaken DESC, subj.subjectName ASC";
                        
                       }

                      $rs = $conn->query($query);
                      $num = $rs->num_rows;
                      if($num > 0)
                      { 
                        // Group rows by (student + dateTaken) so merging stays readable across multiple dates
                        $grouped = [];
                        while ($rows = $rs->fetch_assoc()) {
                          if ($isAllStudents && is_array($allowedAdmissions)) {
                            $admTmp = $rows['admissionNumber'];
                            if (!isset($allowedAdmissions[$admTmp])) {
                              continue;
                            }
                          }
                          $key = $rows['admissionNumber'].'|'.$rows['dateTaken'];
                          if (!isset($grouped[$key])) {
                            $grouped[$key] = [
                              'firstName' => $rows['firstName'],
                              'lastName' => $rows['lastName'],
                              'admissionNumber' => $rows['admissionNumber'],
                              'dateTaken' => $rows['dateTaken'],
                              'subjects' => []
                            ];
                          }
                          $grouped[$key]['subjects'][] = [
                            'subjectName' => $rows['subjectName'],
                            'status' => $rows['status']
                          ];
                        }

                        $sn = 0;
                        foreach ($grouped as $g) {
                          $sn++;
                          $bg = ($sn % 2 === 0) ? '#f8f9fc' : '#eef6ff';
                          $rowspan = count($g['subjects']);
                          if ($rowspan < 1) { $rowspan = 1; }

                          foreach ($g['subjects'] as $idx => $subjRow) {
                            $statusIcon = ($subjRow['status'] == '1') ? "<span class='text-success' style='font-weight:bold;'>&#10004;</span>" : "<span class='text-danger' style='font-weight:bold;'>&#10006;</span>";
                            echo "<tr style='background-color:".$bg.";'>";

                            if ($idx === 0) {
                              echo "<td rowspan='".$rowspan."' style='vertical-align: middle; background-color:".$bg.";'>".$sn."</td>";
                              echo "<td rowspan='".$rowspan."' style='vertical-align: middle; background-color:".$bg.";'>".$g['firstName']."</td>";
                              echo "<td rowspan='".$rowspan."' style='vertical-align: middle; background-color:".$bg.";'>".$g['lastName']."</td>";
                              echo "<td rowspan='".$rowspan."' style='vertical-align: middle; background-color:".$bg.";'>".$g['admissionNumber']."</td>";
                            }

                            echo "<td>".$subjRow['subjectName']."</td>";
                            echo "<td style='text-align:center;'>".$statusIcon."</td>";
                            echo "<td>".date('d-m-Y', strtotime($g['dateTaken']))."</td>";
                            echo "</tr>";
                          }
                        }
                      }
                      else
                      {
                           echo   
                           "<div class='alert alert-danger' role='alert'>
                            No Record Found!
                            </div>";
                      }
                    }
                      ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
            </div>
          </div>
          <!--Row-->

          <!-- Documentation Link -->
          <!-- <div class="row">
            <div class="col-lg-12 text-center">
              <p>For more documentations you can visit<a href="https://getbootstrap.com/docs/4.3/components/forms/"
                  target="_blank">
                  bootstrap forms documentations.</a> and <a
                  href="https://getbootstrap.com/docs/4.3/components/input-group/" target="_blank">bootstrap input
                  groups documentations</a></p>
            </div>
          </div> -->

        </div>
        <!---Container Fluid-->
      </div>
      <!-- Footer -->
       <?php include "Includes/footer.php";?>
      <!-- Footer -->
    </div>
  </div>

  <!-- Scroll to top -->
  <a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
  </a>

  <script src="../vendor/jquery/jquery.min.js"></script>
  <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
  <script src="js/ruang-admin.min.js"></script>
   <!-- Page level plugins -->
  <script src="../vendor/datatables/jquery.dataTables.min.js"></script>
  <script src="../vendor/datatables/dataTables.bootstrap4.min.js"></script>

  <!-- Page level custom scripts -->
  <script>
    $(document).ready(function () {
      $('#dataTable').DataTable(); // ID From dataTable 
      $('#dataTableHover').DataTable(); // ID From dataTable with Hover
    });
  </script>
</body>

</html>