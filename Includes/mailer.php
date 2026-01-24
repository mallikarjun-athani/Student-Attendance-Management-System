<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/src/SMTP.php';

function sendSmtpMail($toEmail, $toName, $subject, $bodyText) {
  $cfgPath = __DIR__ . '/mailer_config.php';
  if (!file_exists($cfgPath)) {
    return [false, 'Mailer config missing: Includes/mailer_config.php'];
  }

  $cfg = include $cfgPath;
  if (!is_array($cfg)) {
    return [false, 'Mailer config invalid.'];
  }

  $required = ['host','port','username','password','encryption','from_email','from_name'];
  foreach ($required as $k) {
    if (!isset($cfg[$k]) || $cfg[$k] === '') {
      return [false, 'Mailer config incomplete. Please set '.$k.' in Includes/mailer_config.php'];
    }
  }

  $mail = new PHPMailer(true);

  $debugEnabled = !empty($cfg['debug']);
  $smtpLog = '';
  if ($debugEnabled) {
    $mail->SMTPDebug = 2;
    $mail->Debugoutput = function($str, $level) use (&$smtpLog) {
      $smtpLog .= "[$level] $str\n";
    };
  }

  try {
    $mail->isSMTP();
    $mail->Host = $cfg['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $cfg['username'];
    $mail->Password = $cfg['password'];

    $enc = strtolower(trim($cfg['encryption']));
    if ($enc === 'tls') {
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    } else if ($enc === 'ssl') {
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } else {
      $mail->SMTPSecure = '';
    }

    $mail->Port = intval($cfg['port']);

    $mail->CharSet = 'UTF-8';
    $mail->setFrom($cfg['from_email'], $cfg['from_name']);
    $mail->addAddress($toEmail, $toName);

    $mail->isHTML(false);
    $mail->Subject = $subject;
    $mail->Body = $bodyText;

    $mail->send();
    return [true, 'Email sent successfully.'];
  } catch (Exception $e) {
    $msg = 'Mailer error: ' . $mail->ErrorInfo;
    if ($debugEnabled && $smtpLog !== '') {
      $msg .= "\n\nSMTP Debug:\n" . $smtpLog;
    }
    return [false, $msg];
  }
}
