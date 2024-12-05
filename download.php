<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Get file ID from request
$file_id = isset($_GET['file_id']) ? (int)$_GET['file_id'] : 0;

if (!$file_id) {
    header("Location: browse.php");
    exit();
}

// Get file information from database
$stmt = $pdo->prepare("SELECT * FROM lesson_files WHERE id = ?");
$stmt->execute([$file_id]);
$file = $stmt->fetch();

if (!$file) {
    header("Location: browse.php");
    exit();
}

$file_path = __DIR__ . '/uploads/' . $file['file_name'];

// Check if file exists
if (!file_exists($file_path)) {
    header("Location: browse.php");
    exit();
}

// Set appropriate content type based on file extension
$extension = strtolower(pathinfo($file['original_name'], PATHINFO_EXTENSION));
$content_type = match($extension) {
    'pdf' => 'application/pdf',
    'doc', 'docx' => 'application/msword',
    'xls', 'xlsx' => 'application/vnd.ms-excel',
    'zip' => 'application/zip',
    'rar' => 'application/x-rar-compressed',
    'txt' => 'text/plain',
    'jpg', 'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    default => 'application/octet-stream'
};

// Set headers for file download
header('Content-Type: ' . $content_type);
header('Content-Disposition: attachment; filename="' . $file['original_name'] . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: public');

// Clear output buffer
ob_clean();
flush();

// Output file content
readfile($file_path);
exit();
