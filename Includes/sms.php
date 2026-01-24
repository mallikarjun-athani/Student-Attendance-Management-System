<?php

function sendSmsOtp($phoneNumber, $message) {
  $cfgPath = __DIR__ . '/sms_config.php';
  if (!file_exists($cfgPath)) {
    return [false, 'SMS config missing: Includes/sms_config.php'];
  }

  $cfg = include $cfgPath;
  if (!is_array($cfg)) {
    return [false, 'SMS config invalid.'];
  }

  $provider = isset($cfg['provider']) ? strtolower(trim($cfg['provider'])) : '';

  if ($provider === 'fast2sms') {
    $apiKey = isset($cfg['fast2sms_api_key']) ? trim($cfg['fast2sms_api_key']) : '';
    if ($apiKey === '') {
      return [false, 'Fast2SMS API key not set in Includes/sms_config.php'];
    }

    $url = 'https://www.fast2sms.com/dev/bulkV2';

    $payload = [
      'route' => 'q',
      'message' => $message,
      'language' => 'english',
      'numbers' => $phoneNumber
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'authorization: ' . $apiKey,
      'Content-Type: application/x-www-form-urlencoded'
    ]);

    $resp = curl_exec($ch);
    $err = curl_error($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($resp === false) {
      return [false, 'SMS cURL error: ' . $err];
    }

    $data = json_decode($resp, true);

    if ($http >= 200 && $http < 300 && is_array($data) && !empty($data['return'])) {
      return [true, 'SMS sent successfully.'];
    }

    $debug = !empty($cfg['debug']);
    $msg = 'SMS send failed.';
    if (is_array($data) && isset($data['message'])) {
      $msg .= ' ' . $data['message'];
    }
    if ($debug) {
      $msg .= ' HTTP=' . $http . ' Response=' . $resp;
    }

    return [false, $msg];
  }

  return [false, 'Unsupported SMS provider in Includes/sms_config.php'];
}
