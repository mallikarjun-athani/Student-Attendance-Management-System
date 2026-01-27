<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start(); 
}

// Role-based protection logic
$currentDir = basename(dirname($_SERVER['PHP_SELF']));
$isAuthorized = false;

if (isset($_SESSION['userId']) && isset($_SESSION['userType'])) {
    if ($currentDir == 'Admin' && $_SESSION['userType'] == 'Admin') {
        $isAuthorized = true;
    } elseif ($currentDir == 'ClassTeacher' && $_SESSION['userType'] == 'Teacher') {
        $isAuthorized = true;
    } elseif ($currentDir == 'Student' && $_SESSION['userType'] == 'Student') {
        $isAuthorized = true;
    }
}

if (!$isAuthorized) {
    // Store the requested URL to redirect back after login
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $currentUrl = $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $_SESSION['redirect_url'] = $currentUrl;

    // Determine the correct login page path
    if ($currentDir == 'Student') {
        header('Location: login.php');
    } else {
        header('Location: ../index.php');
    }
    exit;
}

// $expiry = 1800 ;//session expiry required after 30 mins
// if (isset($_SESSION['LAST']) && (time() - $_SESSION['LAST'] > $expiry)) {

//     session_unset();
//     session_destroy();
//     echo "<script type = \"text/javascript\">
//           window.location = (\"../index.php\");
//           </script>";

// }
// $_SESSION['LAST'] = time();
    
?>