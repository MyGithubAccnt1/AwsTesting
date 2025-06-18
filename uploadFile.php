<?php

require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();
$dotenv->required(['AWS_ACCESS_KEY_ID', 'AWS_SECRET_ACCESS_KEY', 'AWS_REGION', 'AWS_BUCKET_NAME'])->notEmpty();

use Aws\S3\S3Client;

header('Content-Type: application/json');

// ✅ 1. Validate file presence
if (!isset($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

$tmpFile = $_FILES['file']['tmp_name'];
$originalName = $_FILES['file']['name'];

// ✅ 2. Validate MIME type and extension
$mimeType = 'application/json';
$fileExtension = pathinfo($originalName, PATHINFO_EXTENSION);
if (strtolower($fileExtension) !== 'json') {
    http_response_code(400);
    echo json_encode(['error' => 'Only .json files are allowed']);
    exit;
}

// ✅ 3. Define S3 upload path
$s3Key = 'Sync-DIR/TRIPKO/Pending/' . basename($originalName);

// ✅ 4. Setup S3 client
$s3Client = new S3Client([
    'version' => 'latest',
    'region'  => $_ENV['AWS_REGION'],
    'credentials' => [
        'key'    => $_ENV['AWS_ACCESS_KEY_ID'],
        'secret' => $_ENV['AWS_SECRET_ACCESS_KEY']
    ]
]);

try {
    // ✅ 5. Upload file directly using putObject
    $result = $s3Client->putObject([
        'Bucket' => $_ENV['AWS_BUCKET_NAME'],
        'Key'    => $s3Key,
        'Body'   => fopen($tmpFile, 'r'),
        'ContentType' => 'application/json',
        'ACL'    => 'private'
    ]);

    echo json_encode([
        'message' => '✅ Upload successful',
        's3_path' => $s3Key,
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'message' => '❌ Upload failed',
        'error' => $e->getMessage()
    ]);
}