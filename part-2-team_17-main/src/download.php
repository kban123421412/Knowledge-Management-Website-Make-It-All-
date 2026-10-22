<?php
session_start();
if (!isset($_SESSION['userID'])) { exit("Access Denied"); }

$fileName = $_GET['file'] ?? exit("No file specified");
// Path to your folder outside web root
$filePath = "/var/www/html/pakms-uploads/" . basename($fileName);

if (file_exists($filePath)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
    readfile($filePath);
    exit;
} else {
    echo "File not found.";
}