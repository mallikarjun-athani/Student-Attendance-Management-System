<?php
include '../Includes/mailer.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

header('Content-Type: application/json');

function jsonOut($ok, $msg, $extra = []) {
  echo json_encode(array_merge(['ok' => $ok, 'message' => $msg], $extra));
  exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action === 'send_email_otp') {
  $email = isset($_POST['email']) ? trim($_POST['email']) : '';
  if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonOut(false, 'Invalid email address.');
  }

  $otp = strval(random_int(100000, 999999));
  $_SESSION['otp_email'] = [
    'email' => $email,
    'otp' => password_hash($otp, PASSWORD_DEFAULT),
    'expires' => time() + 10 * 60
  ];

  unset($_SESSION['email_verified']);

  $subject = 'Email Verification OTP';
  $body = "Your OTP is: {$otp}. It will expire in 10 minutes.";

  list($sent, $sendMsg) = sendSmtpMail($email, $email, $subject, $body);
  if (!$sent) {
    jsonOut(false, $sendMsg);
  }

  jsonOut(true, 'OTP sent to email.');
}

if ($action === 'verify_email_otp') {
  $email = isset($_POST['email']) ? trim($_POST['email']) : '';
  $otp = isset($_POST['otp']) ? trim($_POST['otp']) : '';

  if ($email === '' || $otp === '') {
    jsonOut(false, 'Email and OTP are required.');
  }

  if (!isset($_SESSION['otp_email'])) {
    jsonOut(false, 'Please request OTP first.');
  }

  $rec = $_SESSION['otp_email'];
  if (!isset($rec['expires']) || time() > intval($rec['expires'])) {
    unset($_SESSION['otp_email']);
    jsonOut(false, 'OTP expired. Please request a new OTP.');
  }

  if (!isset($rec['email']) || strtolower($rec['email']) !== strtolower($email)) {
    jsonOut(false, 'Email does not match OTP request.');
  }

  if (!isset($rec['otp']) || !password_verify($otp, $rec['otp'])) {
    jsonOut(false, 'Wrong OTP.');
  }

  $_SESSION['email_verified'] = $email;
  unset($_SESSION['otp_email']);

  jsonOut(true, 'Valid');
}

jsonOut(false, 'Invalid action.');
