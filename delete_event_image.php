<?php
/**
 * delete_event_image.php
 * ──────────────────────
 * Deletes a single event image (file + DB row).
 * Called via POST from admin.php image management UI.
 *
 * POST fields: image_id (int)
 */

require 'includes/conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Guard: admin only
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

$imageId = isset($_POST['image_id']) ? (int) $_POST['image_id'] : 0;
if ($imageId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid image_id']);
    exit;
}

// Fetch the row so we know the file path
$sel = $conn->prepare("SELECT path FROM event_images WHERE id = ? LIMIT 1");
$sel->bind_param('i', $imageId);
$sel->execute();
$sel->bind_result($relPath);
if (!$sel->fetch()) {
    $sel->close();
    http_response_code(404);
    echo json_encode(['error' => 'Image not found']);
    exit;
}
$sel->close();

// Delete from DB first
$del = $conn->prepare("DELETE FROM event_images WHERE id = ?");
$del->bind_param('i', $imageId);
$del->execute();
$del->close();

// Remove file from disk
$fullPath = __DIR__ . '/' . $relPath;
if (file_exists($fullPath)) {
    @unlink($fullPath);
}

header('Content-Type: application/json');
echo json_encode(['success' => true]);
