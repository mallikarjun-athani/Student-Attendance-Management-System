<?php
// Get the current page filename
$currentPage = basename($_SERVER['PHP_SELF']);

// A simple map of filenames to titles
$pageTitles = [
    'index.php' => 'Dashboard',
    'attendanceInfo.php' => 'Attendance Info',
    'attendanceQr.php' => 'QR Attendance',
    'attendanceView.php' => 'View Attendance',
    'qr.php' => 'My QR Code',
    'login.php' => 'Login',
    'register.php' => 'Register',
];

// Set a default title if the page is not in the map
$title = isset($pageTitles[$currentPage]) ? $pageTitles[$currentPage] : 'Student Panel';

?>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link href="../img/logo/attnlg.jpg" rel="icon">
  <title>Student - <?php echo $title; ?></title>
  <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="../Admin/css/ruang-admin.min.css" rel="stylesheet">
  <link href="../Admin/css/premium-admin.css" rel="stylesheet">
</head>