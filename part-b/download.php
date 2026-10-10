<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$type = $_GET['type'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0 || !in_array($type, ['document', 'homework'], true)) {
    http_response_code(400);
    echo 'Bad request';
    exit;
}

$file_path = '';
$download_name = 'download';

if ($type === 'document') {
    $stmt = db()->prepare('SELECT file_path, title FROM documents WHERE id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    if (!$row) {
        http_response_code(404);
        echo 'Not found';
        exit;
    }
    $file_path = $row['file_path'];
    $download_name = $row['title'];
} else {
    $stmt = db()->prepare('SELECT spec_file_path FROM homeworks WHERE id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    if (!$row) {
        http_response_code(404);
        echo 'Not found';
        exit;
    }
    $file_path = $row['spec_file_path'];
    $download_name = 'ergasia' . $id;
}

$abs = realpath(__DIR__ . '/' . $file_path);
$allowed = false;
foreach (['downloads', 'uploads/documents', 'uploads/homeworks'] as $directory) {
    $base = realpath(__DIR__ . '/' . $directory);
    if ($abs && $base && strpos($abs, $base . DIRECTORY_SEPARATOR) === 0) {
        $allowed = true;
        break;
    }
}
$extension = $abs ? strtolower(pathinfo($abs, PATHINFO_EXTENSION)) : '';
if (!$allowed || !is_file($abs) || !in_array($extension, ['pdf', 'doc', 'docx', 'txt', 'odt', 'ppt', 'pptx'], true)) {
    http_response_code(404);
    echo 'File not found';
    exit;
}

$basename = basename($abs);
$ext = pathinfo($basename, PATHINFO_EXTENSION);
$fname = safe_filename($download_name);
if ($ext) { $fname .= '.' . $ext; }

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: attachment; filename="' . $fname . '"');
header('Content-Length: ' . filesize($abs));
header('Cache-Control: private, no-store');

readfile($abs);
exit;
