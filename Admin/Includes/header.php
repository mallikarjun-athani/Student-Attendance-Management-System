<?php
// Get the current page filename
$currentPage = basename($_SERVER['PHP_SELF']);

// A simple map of filenames to titles
$pageTitles = [
    'index.php' => 'Dashboard',
    'allStudents.php' => 'All Students',
    'classesList.php' => 'Classes',
    'createClass.php' => 'Create Class',
    'createClassArms.php' => 'Create Class Arms',
    'createClassTeacher.php' => 'Create Class Teacher',
    'createSessionTerm.php' => 'Create Session/Term',
    'createStudents.php' => 'Create Students',
    'createUsers.php' => 'Create Users',
    'divisionsList.php' => 'Divisions',
    'semestersList.php' => 'Semesters',
    'sessionTermsList.php' => 'Session/Terms',
    'studentAttendanceSummary.php' => 'Student Attendance Summary',
    'studentSummary.php' => 'Student Summary',
    'teachersList.php' => 'Teachers',
    'todayAttendanceSummary.php' => 'Today\'s Attendance Summary',
];

// Set a default title if the page is not in the map
$title = isset($pageTitles[$currentPage]) ? $pageTitles[$currentPage] : 'Admin Panel';

?>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="Student Attendance System - Admin Panel">
  <meta name="author" content="Code Camp BD">
  <link href="img/logo/attnlg.jpg" rel="icon">
  <title>Admin - <?php echo $title; ?></title>
  <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="css/ruang-admin.min.css" rel="stylesheet">
  <link href="css/premium-admin.css" rel="stylesheet">
</head>
