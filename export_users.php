<?php
// Standalone CSV export to avoid any HTML output.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'includes/conn.php';


$currentUserId = $_SESSION['user_id'];
$roleResult = mysqli_query($conn, "SELECT role FROM users WHERE id = $currentUserId LIMIT 1");
// if the query succeeded, fetch the first row and use its role value; if the row is missing, fall back to 'client'. If the query failed, also fall back to 'client'.
$currentRole = $roleResult ? mysqli_fetch_assoc($roleResult)['role'] ?? 'client' : 'client';

if ($currentRole !== 'admin') {
    http_response_code(403);
    exit('Forbidden');
}

// Fetch users (simple fields only)
$users = [];
$result = mysqli_query($conn, "SELECT id, name, email, role FROM users ORDER BY id ASC");
if ($result) {
    $users = mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Ensure no previous output breaks the CSV
if (ob_get_length()) {
    ob_clean();
}

// Set headers so the browser downloads the CSV file.
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=users.csv');

// Example from request: fputcsv with delimiter, enclosure, escape.
$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'Name', 'Email', 'Role'], ',', '"', '\\');

foreach ($users as $user) { // e.g. $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    fputcsv($output, [$user['id'], $user['name'], $user['email'], $user['role']], ',', '"', '\\');
}
// The CSV is generated in-memory and streamed to the response at fopen('php://output', 'w') and the fputcsv(...) loop. There’s no physical file created on disk.
fclose($output);
exit;
