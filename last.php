<?php
// Path to the text file and the folder with images
$lastFileTxt = __DIR__ . '/last.txt';
$imagesDir   = __DIR__ . '/';

// Check if last.txt exists
if (!file_exists($lastFileTxt)) {
    http_response_code(404);
    echo "Error: last.txt not found.";
    exit;
}

// Read filename from last.txt
$filename = trim(file_get_contents($lastFileTxt));
$imagePath = $imagesDir . $filename;

// Check if image exists
if (!file_exists($imagePath)) {
    http_response_code(404);
    echo "Error: Image $filename not found.";
    exit;
}

// Detect MIME type (jpeg, png, etc.)
$mime = mime_content_type($imagePath);
header("Content-Type: $mime");
header('Content-Length: ' . filesize($imagePath));
// Set cache headers: max-age 10 seconds
header('Cache-Control: public, max-age=10, must-revalidate');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 10) . ' GMT');

// Output image
readfile($imagePath);
exit;
?>
