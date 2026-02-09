<?php
// Admin page: simple CSV import/export for users.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'includes/conn.php';

// --- Basic access check (only logged-in admins) ---
if (!isset($_SESSION['user_id'])) {
    echo '<div class="container mt-4"><div class="alert alert-danger">You must be logged in.</div></div>';
    exit;
}

// Get the current user role from the database.
$currentUserId = (int)$_SESSION['user_id'];
$roleResult = mysqli_query($conn, "SELECT role FROM users WHERE id = $currentUserId LIMIT 1");
$currentRole = $roleResult ? mysqli_fetch_assoc($roleResult)['role'] ?? 'client' : 'client';

if ($currentRole !== 'admin') {
    echo '<div class="container mt-4"><div class="alert alert-danger">Access denied. Admins only.</div></div>';
    exit;
}

// --- EXPORT: /admin.php?export=users ---
if (isset($_GET['export']) && $_GET['export'] === 'users') {
    // Fetch users (simple fields only)
    $users = [];
    $result = mysqli_query($conn, "SELECT id, name, email, role FROM users ORDER BY id ASC");
    if ($result) {
        $users = mysqli_fetch_all($result, MYSQLI_ASSOC);
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

    fclose($output);
    exit;
}

include 'includes/header.php';

// --- IMPORT: handle CSV upload ---
$importMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_users'])) {
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $importMessage = 'Please upload a valid CSV file.';
    } else {
        $tmpPath = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($tmpPath, 'r');

        if ($handle === false) {
            $importMessage = 'Unable to read the uploaded file.';
        } else {
            // Read header row (expected columns: ID, Name, Email, Role)
            // We support flexible headers (case-insensitive).
            $header = fgetcsv($handle, 0, ',', '"', '\\');
            $map = [];
            if ($header) {
                foreach ($header as $index => $columnName) {
                    $key = strtolower(trim($columnName));
                    $map[$key] = $index;
                }
            }

            // Prepare insert statement (simple + safe)
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");

            $inserted = 0;
            $skipped = 0;

            while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
                // Read fields by header name (fallback to fixed positions).
                $name = $map['name'] ?? 1;
                $email = $map['email'] ?? 2;
                $role = $map['role'] ?? 3;

                $nameVal = trim($row[$name] ?? '');
                $emailVal = trim($row[$email] ?? '');
                $roleVal = trim($row[$role] ?? '');

                if ($nameVal === '' || $emailVal === '') {
                    $skipped++;
                    continue;
                }

                // Keep it simple: if role is missing/invalid, default to client.
                if ($roleVal !== 'admin' && $roleVal !== 'client') {
                    $roleVal = 'client';
                }

                // Simple default password for imported users (hashed).
                $defaultPassword = password_hash('changeme', PASSWORD_DEFAULT);

                // Skip if email already exists.
                $emailEsc = mysqli_real_escape_string($conn, $emailVal);
                $exists = mysqli_query($conn, "SELECT id FROM users WHERE email = '$emailEsc' LIMIT 1");
                if ($exists && mysqli_num_rows($exists) > 0) {
                    $skipped++;
                    continue;
                }

                if ($stmt && $stmt->bind_param('ssss', $nameVal, $emailVal, $defaultPassword, $roleVal) && $stmt->execute()) {
                    $inserted++;
                } else {
                    $skipped++;
                }
            }

            if ($stmt) {
                $stmt->close();
            }

            fclose($handle);
            $importMessage = "Import finished. Inserted: $inserted. Skipped: $skipped.";
        }
    }
}
?>

<div class="container mt-4">
    <h1>Admin - Users CSV</h1>

    <?php if ($importMessage): ?>
        <div class="alert alert-info"><?php echo $importMessage; ?></div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Export users</h5>
            <p class="card-text">Download a simple CSV with ID, Name, Email, Role.</p>
            <a class="btn btn-primary" href="admin.php?export=users">Download CSV</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Import users</h5>
            <p class="card-text">Upload a CSV with header: ID, Name, Email, Role. New users get password <strong>changeme</strong>.</p>
            <form action="admin.php" method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <input class="form-control" type="file" name="csv_file" accept=".csv" required>
                </div>
                <button class="btn btn-success" type="submit" name="import_users">Import CSV</button>
            </form>
        </div>
    </div>
</div>

</body>
</html>
