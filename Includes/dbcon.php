<?php
// Detect environment and set database credentials accordingly
// For InfinityFree hosting, update the 'live' credentials below

$isLocalhost = (
    isset($_SERVER['SERVER_NAME']) && 
    ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === '127.0.0.1')
);

if ($isLocalhost) {
    // Local development (XAMPP)
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db = "attendencemsystem01";
} else {
    // Live server (InfinityFree) - UPDATE THESE WITH YOUR ACTUAL CREDENTIALS
    $host = "sql113.infinityfree.com"; // Check your InfinityFree cPanel for exact host
    $user = "if0_38216498";            // Your InfinityFree database username
    $pass = "YOUR_DB_PASSWORD";        // Your InfinityFree database password  
    $db = "if0_38216498_attendancedb"; // Your InfinityFree database name
}

// Suppress errors for production
if (!$isLocalhost) {
    error_reporting(0);
    ini_set('display_errors', 0);
}

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    if ($isLocalhost) {
        die("Database connection failed: " . $conn->connect_error);
    } else {
        // On live server, redirect to a friendly error page or show minimal error
        die("<div style='text-align:center; padding:50px; font-family:Arial;'><h2>Service Temporarily Unavailable</h2><p>Please try again later.</p></div>");
    }
}

// Set charset
$conn->set_charset("utf8mb4");
