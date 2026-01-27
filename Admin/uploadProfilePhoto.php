<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';

header('Content-Type: application/json');

function jsonOut($ok, $msg, $extra = []) {
  echo json_encode(array_merge(['ok' => $ok, 'message' => $msg], $extra));
  exit;
}

if (!isset($_SESSION['userId']) || $_SESSION['userId'] === '' || $_SESSION['userType'] !== 'Admin') {
  jsonOut(false, 'Unauthorized.');
}

$userId = intval($_SESSION['userId']);
$dir = __DIR__ . '/uploads';
$base = 'admin_' . $userId;

// Handle Removal
if (isset($_POST['action']) && $_POST['action'] === 'remove') {
    foreach (['jpg','png','jpeg','webp'] as $e) {
        $p = $dir . '/' . $base . '.' . $e;
        if (file_exists($p)) {
            @unlink($p);
        }
    }
    jsonOut(true, 'Photo removed successfully.', ['url' => 'img/user-icn.png']);
}

if (!isset($_FILES['photo'])) {
  jsonOut(false, 'No file uploaded.');
}

$file = $_FILES['photo'];
if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
  jsonOut(false, 'Upload failed.');
}

$tmp = $file['tmp_name'];
$size = isset($file['size']) ? intval($file['size']) : 0;
if ($size <= 0) {
  jsonOut(false, 'Invalid file.');
}

$maxBytes = 2 * 1024 * 1024;
if ($size > $maxBytes) {
  jsonOut(false, 'File too large. Max 2MB.');
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = $finfo ? finfo_file($finfo, $tmp) : '';
if ($finfo) finfo_close($finfo);

$ext = '';
if ($mime === 'image/jpeg') $ext = 'jpg';
else if ($mime === 'image/png') $ext = 'png';
else if ($mime === 'image/webp') $ext = 'webp';
else {
  jsonOut(false, 'Only JPG, PNG, or WEBP images allowed.');
}

if (!is_dir($dir)) {
  @mkdir($dir, 0755, true);
}

$target = $dir . '/' . $base . '.' . $ext;

// Clean up old photos regardless of extension
foreach (['jpg','png','jpeg','webp'] as $e) {
  $p = $dir . '/' . $base . '.' . $e;
  if (file_exists($p)) {
    @unlink($p);
  }
}

if (!move_uploaded_file($tmp, $target)) {
  jsonOut(false, 'Failed to save uploaded file.');
}

$url = 'uploads/' . $base . '.' . $ext . '?v=' . time();
jsonOut(true, 'Uploaded', ['url' => $url]);
