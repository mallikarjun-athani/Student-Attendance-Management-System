<?php
error_reporting(0);
include '../Includes/dbcon.php';
include '../Includes/session.php';

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

$dateTaken = isset($_GET['dateTaken']) ? trim($_GET['dateTaken']) : date('Y-m-d');
$attFilter = isset($_GET['attFilter']) ? strtolower(trim($_GET['attFilter'])) : 'all';
if ($attFilter !== 'present' && $attFilter !== 'absent' && $attFilter !== 'all') {
  $attFilter = 'all';
}

$grouped = [];
$totalStudents = 0;
$totalPresentStudents = 0;
$totalAbsentStudents = 0;

if ($dateTaken !== '') {
  $query = "SELECT
    s.firstName,
    s.lastName,
    s.admissionNumber,
    subj.subjectName,
    COALESCE(sa.status, '0') AS status
  FROM tblstudents s
  INNER JOIN tblsubjects subj
    ON subj.classId = s.classId
    AND subj.classArmId = s.classArmId
  LEFT JOIN tblsubjectattendance sa
    ON sa.admissionNumber = s.admissionNumber
    AND sa.subjectId = subj.Id
    AND sa.classId = '$_SESSION[classId]'
    AND sa.classArmId = '$_SESSION[classArmId]'
    AND sa.dateTaken = '$dateTaken'
  INNER JOIN tbltodaysubjects ts
    ON ts.subjectId = subj.Id
    AND ts.classId = '$_SESSION[classId]'
    AND ts.classArmId = '$_SESSION[classArmId]'
    AND ts.dateTaken = '$dateTaken'
  WHERE s.classId = '$_SESSION[classId]' AND s.classArmId = '$_SESSION[classArmId]'
  ORDER BY s.firstName ASC, s.lastName ASC, s.admissionNumber ASC, subj.subjectName ASC";

  $rs = $conn->query($query);
  if ($rs && $rs->num_rows > 0) {
    while ($rows = $rs->fetch_assoc()) {
      $adm = $rows['admissionNumber'];
      if (!isset($grouped[$adm])) {
        $grouped[$adm] = [
          'firstName' => $rows['firstName'],
          'lastName' => $rows['lastName'],
          'admissionNumber' => $adm,
          'subjects' => [],
          'overallStatus' => '0'
        ];
      }
      $grouped[$adm]['subjects'][] = [
        'subjectName' => $rows['subjectName'],
        'status' => $rows['status']
      ];
    }

    $totalStudents = count($grouped);
    foreach ($grouped as $adm => $stu) {
      $allPresent = true;
      if (!isset($stu['subjects']) || count($stu['subjects']) === 0) {
        $allPresent = false;
      } else {
        foreach ($stu['subjects'] as $subjRow) {
          if ($subjRow['status'] != '1') {
            $allPresent = false;
            break;
          }
        }
      }
      $grouped[$adm]['overallStatus'] = $allPresent ? '1' : '0';
      if ($allPresent) {
        $totalPresentStudents++;
      }
    }
    $totalAbsentStudents = $totalStudents - $totalPresentStudents;
  }
}

$filename = 'Today-Report-'.$dateTaken;
header("Content-type: application/octet-stream");
header("Content-Disposition: attachment; filename={$filename}.xls");
header("Pragma: no-cache");
header("Expires: 0");

?>
<table border="1">
  <thead>
    <tr>
      <th colspan="6">Today's Report</th>
    </tr>
    <tr>
      <th colspan="6">Date: <?php echo htmlspecialchars(date('d-m-Y', strtotime($dateTaken))); ?> | Filter: <?php echo htmlspecialchars(strtoupper($attFilter)); ?></th>
    </tr>
    <tr>
      <th colspan="2">Total Present Students</th>
      <th colspan="4"><?php echo intval($totalPresentStudents); ?></th>
    </tr>
    <tr>
      <th colspan="2">Total Absent Students</th>
      <th colspan="4"><?php echo intval($totalAbsentStudents); ?></th>
    </tr>
    <tr>
      <th colspan="2">Total Students</th>
      <th colspan="4"><?php echo intval($totalStudents); ?></th>
    </tr>
    <tr>
      <th>#</th>
      <th>First Name</th>
      <th>Last Name</th>
      <th>Admission No</th>
      <th>Subject Name</th>
      <th>Status</th>
    </tr>
  </thead>
  <tbody>
    <?php
    $displaySn = 0;
    if (count($grouped) > 0) {
      foreach ($grouped as $stu) {
        $overall = isset($stu['overallStatus']) ? $stu['overallStatus'] : '0';
        if ($attFilter === 'present' && $overall != '1') {
          continue;
        }
        if ($attFilter === 'absent' && $overall == '1') {
          continue;
        }

        $displaySn++;
        foreach ($stu['subjects'] as $subjRow) {
          $statusText = ($subjRow['status'] == '1') ? 'Present' : 'Absent';
          echo '<tr>';
          echo '<td>'.intval($displaySn).'</td>';
          echo '<td>'.htmlspecialchars($stu['firstName']).'</td>';
          echo '<td>'.htmlspecialchars($stu['lastName']).'</td>';
          echo '<td>'.htmlspecialchars($stu['admissionNumber']).'</td>';
          echo '<td>'.htmlspecialchars($subjRow['subjectName']).'</td>';
          echo '<td>'.htmlspecialchars($statusText).'</td>';
          echo '</tr>';
        }
      }
    }

    if ($displaySn === 0) {
      echo '<tr><td colspan="6">No Record Found!</td></tr>';
    }
    ?>
  </tbody>
</table>
