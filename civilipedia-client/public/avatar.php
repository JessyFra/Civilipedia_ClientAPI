<?php
session_start();

$filename = basename($_GET['file'] ?? '');

if (empty($filename)) {
    http_response_code(404);
    exit;
}

$path = __DIR__ . '/../../civilipedia-api/private/avatars/' . $filename;

if (!file_exists($path)) {
    http_response_code(404);
    exit;
}

$mime = mime_content_type($path);
$allowed = ['image/jpeg', 'image/png', 'image/webp'];

if (!in_array($mime, $allowed)) {
    http_response_code(403);
    exit;
}

header('Content-Type: ' . $mime);
readfile($path);
