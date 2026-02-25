<?php
/**
 * upload_event_image.php
 * ─────────────────────
 * Dropzone.js sends files ONE AT A TIME to this endpoint.
 * We validate, store and record each file in event_images.
 *
 * Expected POST fields
 *   event_id  – the event this image belongs to
 *   file      – the uploaded file (Dropzone default param name)
 */

require 'includes/conn.php';

// Only admins may upload images
session_start();
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$userId     = (int) $_SESSION['user_id'];
$roleResult = mysqli_query($conn, "SELECT role FROM users WHERE id = $userId LIMIT 1");
$role       = $roleResult ? (mysqli_fetch_assoc($roleResult)['role'] ?? 'client') : 'client';

if ($role !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Admins only']);
    exit;
}

// ── Validate event_id ────────────────────────────────────────────────────────
$eventId = isset($_POST['event_id']) ? (int) $_POST['event_id'] : 0;
if ($eventId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid event_id']);
    exit;
}

// Confirm the event exists
$evCheck = $conn->prepare("SELECT id FROM events WHERE id = ? LIMIT 1");
$evCheck->bind_param('i', $eventId);
$evCheck->execute();
$evCheck->store_result();
if ($evCheck->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Event not found']);
    exit;
}
$evCheck->close();

// ── Validate uploaded file ───────────────────────────────────────────────────
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No file or upload error']);
    exit;
}

$file      = $_FILES['file'];
$maxBytes  = 8 * 1024 * 1024; // 8 MB
$allowed   = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

if ($file['size'] > $maxBytes) {
    http_response_code(400);
    echo json_encode(['error' => 'File too large (max 8 MB)']);
    exit;
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'File type not allowed. Use: ' . implode(', ', $allowed)]);
    exit;
}

// Double-check MIME type
$finfo    = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);
$allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
if (!in_array($mimeType, $allowedMimes, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid MIME type']);
    exit;
}

// ── Store file ───────────────────────────────────────────────────────────────
$uploadDir = __DIR__ . '/uploads/events/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Unique filename to prevent collisions
$filename = sprintf('%d_%s_%s.%s', $eventId, date('Ymd_His'), bin2hex(random_bytes(4)), $ext);
$destPath = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save file']);
    exit;
}

// ── Insert into event_images ─────────────────────────────────────────────────
$relPath   = 'uploads/events/' . $filename;
$sortOrder = 0; // Dropzone sends one file at a time; order can be adjusted later

$stmt = $conn->prepare(
    "INSERT INTO event_images (event_id, filename, path, sort_order) VALUES (?, ?, ?, ?)"
);
$stmt->bind_param('issi', $eventId, $filename, $relPath, $sortOrder);

if (!$stmt->execute()) {
    // Roll back: remove the file we just saved
    @unlink($destPath);
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $stmt->error]);
    exit;
}

$imageId = $stmt->insert_id;
$stmt->close();

// ── Return success ───────────────────────────────────────────────────────────
if (ob_get_length()) ob_clean(); 
header('Content-Type: application/json');
echo json_encode([
    'success'  => true,
    'image_id' => $imageId,
    'path'     => $relPath,
    'filename' => $filename,
]);
