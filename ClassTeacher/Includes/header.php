<?php
// Get the current page filename
$currentPage = basename($_SERVER['PHP_SELF']);

// A simple map of filenames to titles
$pageTitles = [
    'index.php' => 'Dashboard',
    'classDetails.php' => 'Class Details',
    'createSubject.php' => 'Create Subject',
    'downloadRecord.php' => 'Download Record',
    'downloadTodayReport.php' => 'Download Today\'s Report',
    'generateQrCodes.php' => 'Generate QR Codes',
    'qrAttendance.php' => 'QR Attendance',
    'semesterDetails.php' => 'Semester Details',
    'takeAttendance.php' => 'Take Attendance',
    'todayAttendanceSummary.php' => 'Today\'s Attendance Summary',
    'todayReport.php' => 'Today\'s Report',
    'todaySubject.php' => 'Today\'s Subject',
    'totalStudents.php' => 'Total Students',
    'uploadProfilePhoto.php' => 'Upload Profile Photo',
    'viewAttendance.php' => 'View Attendance',
    'viewStudentAttendance.php' => 'View Student Attendance',
    'viewStudents.php' => 'View Students',
];

// Set a default title if the page is not in the map
$title = isset($pageTitles[$currentPage]) ? $pageTitles[$currentPage] : 'Class Teacher Panel';

?>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="Student Attendance System - Class Teacher Panel">
  <meta name="author" content="Code Camp BD">
  <link href="img/logo/attnlg.jpg" rel="icon">
  <title>Teacher - <?php echo $title; ?></title>
  <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="css/ruang-admin.min.css" rel="stylesheet">
  <link href="../Admin/css/premium-admin.css" rel="stylesheet">
</head>