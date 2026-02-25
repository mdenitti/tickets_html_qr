<?php
// ─────────────────────────────────────────────────────────────────────────────
// admin.php  –  Admin Dashboard
//   • Events CRUD  (create / read / update / delete)
//   • Multi-image upload per event via Dropzone.js (CDN)
//   • Users CSV import / export  (unchanged from previous version)
// ─────────────────────────────────────────────────────────────────────────────

// Start session early (header.php is included later to allow redirects)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'includes/conn.php';

// ── Auth guard ────────────────────────────────────────────────────────────────
$currentUserId = (int) $_SESSION['user_id'];
$roleResult    = mysqli_query($conn, "SELECT role FROM users WHERE id = $currentUserId LIMIT 1");
$currentRole   = $roleResult ? (mysqli_fetch_assoc($roleResult)['role'] ?? 'client') : 'client';

if ($currentRole !== 'admin') {
    echo '<div class="container mt-4"><div class="alert alert-danger">Access denied. Admins only.</div></div>';
    exit;
}

// ── Active tab (events | users) ───────────────────────────────────────────────
$tab = isset($_GET['tab']) && $_GET['tab'] === 'users' ? 'users' : 'events';

// ── Flash messages ────────────────────────────────────────────────────────────
$flash = '';
$flashType = 'success';

// ── Events CRUD ───────────────────────────────────────────────────────────────

// 1. DELETE event
if (isset($_POST['delete_event'])) {
    $delId = (int) $_POST['event_id'];

    // Collect image paths for disk cleanup (FK cascade handles DB rows)
    $imgRows = $conn->query("SELECT path FROM event_images WHERE event_id = $delId");
    if ($imgRows) {
        while ($imgRow = $imgRows->fetch_assoc()) {
            $fp = __DIR__ . '/' . $imgRow['path'];
            if (file_exists($fp)) {
                @unlink($fp);
            }
        }
    }

    $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
    $stmt->bind_param('i', $delId);
    $stmt->execute();
    $stmt->close();

    $flash = 'Event deleted.';
    $flashType = 'danger';
    header('Location: admin.php?tab=events&flash=' . urlencode($flash) . '&ft=danger');
    exit;
}

// 2. CREATE or UPDATE event
if (isset($_POST['save_event'])) {
    $editId      = isset($_POST['event_id']) ? (int) $_POST['event_id'] : 0;
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $date        = trim($_POST['date'] ?? '');
    $location    = trim($_POST['location'] ?? '');
    $maxTickets  = (int) ($_POST['max_tickets'] ?? 0);

    if ($title === '' || $date === '' || $location === '') {
        $flash = 'Title, date and location are required.';
        $flashType = 'danger';
    } else {
        if ($editId > 0) {
            // UPDATE
            $stmt = $conn->prepare(
                "UPDATE events SET title=?, description=?, date=?, location=?, max_tickets=?, updated_at=NOW() WHERE id=?"
            );
            $stmt->bind_param('ssssii', $title, $description, $date, $location, $maxTickets, $editId);
            $stmt->execute();
            $stmt->close();
            $flash = 'Event updated.';
        } else {
            // INSERT
            $stmt = $conn->prepare(
                "INSERT INTO events (title, description, date, location, max_tickets, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, NOW(), NOW())"
            );
            $stmt->bind_param('ssssi', $title, $description, $date, $location, $maxTickets);
            $stmt->execute();
            $editId = $stmt->insert_id; // for redirect to images tab
            $stmt->close();
            $flash = 'Event created. You can now add images below.';
        }

        header('Location: admin.php?tab=events&flash=' . urlencode($flash) . '&highlight=' . $editId);
        exit;
    }
}

// Pick up flash from redirect
if (isset($_GET['flash'])) {
    $flash     = htmlspecialchars($_GET['flash']);
    $flashType = isset($_GET['ft']) ? htmlspecialchars($_GET['ft']) : 'success';
}

// Highlighted event (after save) – for auto-opening image panel
$highlightId = isset($_GET['highlight']) ? (int) $_GET['highlight'] : 0;

// ── Users CSV import ───────────────────────────────────────────────────────────
$importMessage = '';
if ($tab === 'users' && isset($_POST['import_users'])) {
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $importMessage = 'Please upload a valid CSV file.';
    } else {
        $tmpPath = $_FILES['csv_file']['tmp_name'];
        $handle  = fopen($tmpPath, 'r');
        if ($handle === false) {
            $importMessage = 'Unable to read the uploaded file.';
        } else {
            $header = fgetcsv($handle, 0, ',', '"', '\\');
            $map    = [];
            if ($header) {
                foreach ($header as $i => $col) {
                    $map[strtolower(trim($col))] = $i;
                }
            }
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $inserted = $skipped = 0;

            while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
                $nameIdx  = $map['name']  ?? 1;
                $emailIdx = $map['email'] ?? 2;
                $roleIdx  = $map['role']  ?? 3;

                $nameVal  = trim($row[$nameIdx]  ?? '');
                $emailVal = trim($row[$emailIdx] ?? '');
                $roleVal  = trim($row[$roleIdx]  ?? '');

                if ($nameVal === '' || $emailVal === '') { $skipped++; continue; }
                if ($roleVal !== 'admin' && $roleVal !== 'client') { $roleVal = 'client'; }

                $emailEsc = mysqli_real_escape_string($conn, $emailVal);
                $exists   = mysqli_query($conn, "SELECT id FROM users WHERE email='$emailEsc' LIMIT 1");
                if ($exists && mysqli_num_rows($exists) > 0) { $skipped++; continue; }

                $defaultPassword = password_hash('changeme', PASSWORD_DEFAULT);
                if ($stmt && $stmt->bind_param('ssss', $nameVal, $emailVal, $defaultPassword, $roleVal) && $stmt->execute()) {
                    $inserted++;
                } else {
                    $skipped++;
                }
            }
            if ($stmt) { $stmt->close(); }
            fclose($handle);
            $importMessage = "Import finished. Inserted: $inserted. Skipped: $skipped.";
        }
    }
}

// ── Fetch all events for list ─────────────────────────────────────────────────
$events = $conn->query("SELECT * FROM events ORDER BY date DESC")->fetch_all(MYSQLI_ASSOC);

// Fetch images keyed by event_id
$allImages = [];
$imgResult = $conn->query("SELECT * FROM event_images ORDER BY event_id, sort_order, id");
if ($imgResult) {
    while ($img = $imgResult->fetch_assoc()) {
        $allImages[$img['event_id']][] = $img;
    }
}

// Editing mode?
$editEvent = null;
if (isset($_GET['edit'])) {
    $eId = (int) $_GET['edit'];
    $eStmt = $conn->prepare("SELECT * FROM events WHERE id = ? LIMIT 1");
    $eStmt->bind_param('i', $eId);
    $eStmt->execute();
    $editEvent = $eStmt->get_result()->fetch_assoc();
    $eStmt->close();
}

// All processing done — now safe to output HTML
include 'includes/header.php';

// Load Dropzone BEFORE the forms in the body
echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.css">';
echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.js"></script>';
echo '<script>Dropzone.autoDiscover = false;</script>';
?>

<!-- ─────────────────────────────  EXTRA STYLES  ───────────────────────────── -->
<style>
    /* Dropzone overrides to match site theme */
    /* Dropzone overrides to match site theme */
    .event-dropzone {
        border: 2px dashed var(--gradient-start) !important;
        border-radius: 16px !important;
        background: var(--bg-tertiary) !important;
        min-height: 160px !important;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-wrap: wrap;
        padding: 1rem !important;
        cursor: pointer;
    }
    .event-dropzone .dz-message {
        color: var(--text-secondary);
        font-size: 0.95rem;
    }
    .event-dropzone .dz-preview .dz-image {
        border-radius: 12px !important;
    }
    .event-dropzone .dz-preview .dz-remove {
        font-size: 0.75rem;
    }
    /* Event image grid */
    .event-img-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        margin-top: 0.75rem;
    }
    .event-img-thumb {
        position: relative;
        width: 100px;
        height: 100px;
    }
    .event-img-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 12px;
        border: 1px solid var(--border-color);
    }
    .event-img-thumb .del-img-btn {
        position: absolute;
        top: 4px;
        right: 4px;
        width: 22px;
        height: 22px;
        background: rgba(255,59,48,0.85);
        border: none;
        border-radius: 50%;
        color: #fff;
        font-size: 13px;
        line-height: 1;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
    }
    /* Tab pills */
    .admin-tabs .nav-link {
        border-radius: 12px;
        font-weight: 600;
        color: var(--text-secondary);
        padding: 0.6rem 1.4rem;
    }
    .admin-tabs .nav-link.active {
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        color: #fff !important;
    }
    /* Event list rows */
    .event-row {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 1.25rem 1.5rem;
        margin-bottom: 1rem;
        transition: box-shadow 0.2s;
    }
    .event-row:hover { box-shadow: var(--shadow-hover); }
    .event-row .event-meta {
        font-size: 0.82rem;
        color: var(--text-secondary);
    }
    .event-row .badge-tickets {
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        color: #fff;
        border-radius: 20px;
        padding: 0.25rem 0.8rem;
        font-size: 0.78rem;
        font-weight: 600;
    }
    /* Image collapse panel */
    .images-panel {
        background: var(--bg-tertiary);
        border-radius: 0 0 16px 16px;
        border: 1px solid var(--border-color);
        border-top: none;
        padding: 1rem 1.25rem;
        margin-top: -1px;
    }
</style>

<!-- ─────────────────────────────  MAIN CONTENT  ───────────────────────────── -->
<div class="container mt-4" style="max-width:900px;">
    <h1>⚙️ Admin Dashboard</h1>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flashType ?>"><?= $flash ?></div>
    <?php endif; ?>

    <!-- Tabs -->
    <ul class="nav admin-tabs mb-4" id="adminTabs">
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'events' ? 'active' : '' ?>" href="admin.php?tab=events">🎟 Events</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'users' ? 'active' : '' ?>" href="admin.php?tab=users">👤 Users CSV</a>
        </li>
    </ul>

    <!-- ═══════════════════  EVENTS TAB  ══════════════════════════════════════ -->
    <?php if ($tab === 'events'): ?>

        <!-- CREATE / EDIT FORM -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title" style="text-align:left">
                    <?= $editEvent ? '✏️ Edit Event' : '➕ New Event' ?>
                </h5>
                <form method="post" action="admin.php?tab=events">
                    <?php if ($editEvent): ?>
                        <input type="hidden" name="event_id" value="<?= $editEvent['id'] ?>">
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="title"
                                   value="<?= htmlspecialchars($editEvent['title'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Max Tickets</label>
                            <input type="number" class="form-control" name="max_tickets" min="1"
                                   value="<?= htmlspecialchars($editEvent['max_tickets'] ?? '100') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date & Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" name="date"
                                   value="<?= htmlspecialchars(isset($editEvent['date'])
                                       ? (new DateTime($editEvent['date']))->format('Y-m-d\TH:i')
                                       : '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Location <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="location"
                                   value="<?= htmlspecialchars($editEvent['location'] ?? '') ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"><?= htmlspecialchars($editEvent['description'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="mt-3 d-flex gap-2">
                        <button type="submit" name="save_event" class="btn btn-primary" style="width:auto">
                            <?= $editEvent ? '💾 Save Changes' : '🎉 Create Event' ?>
                        </button>
                        <?php if ($editEvent): ?>
                            <a href="admin.php?tab=events" class="btn btn-secondary" style="width:auto">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- EVENT LIST -->

        <h5 class="mb-3" style="text-align:left">All Events (<?= count($events) ?>)</h5>

        <?php if (empty($events)): ?>
            <div class="alert alert-info">No events yet. Create the first one above!</div>
        <?php endif; ?>

        <?php foreach ($events as $ev): ?>
            <?php
                $evImages    = $allImages[$ev['id']] ?? [];
                $isHighlight = $highlightId === (int) $ev['id'];
                $collapseId  = 'imgs-' . $ev['id'];
            ?>
            <div class="event-row" id="event-<?= $ev['id'] ?>">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div>
                        <strong><?= htmlspecialchars($ev['title']) ?></strong>
                        <div class="event-meta mt-1">
                            📅 <?= htmlspecialchars((new DateTime($ev['date']))->format('d M Y, H:i')) ?>
                            &nbsp;·&nbsp; 📍 <?= htmlspecialchars($ev['location']) ?>
                            <?php if ($ev['description']): ?>
                                &nbsp;·&nbsp; <?= htmlspecialchars(mb_strimwidth($ev['description'], 0, 60, '…')) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="badge-tickets">🎟 <?= $ev['max_tickets'] ?></span>

                        <!-- Images toggle -->
                        <button class="btn btn-sm btn-outline-secondary"
                                style="border-radius:10px;font-size:.8rem;width:auto;padding:.35rem .9rem"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#<?= $collapseId ?>"
                                aria-expanded="<?= $isHighlight ? 'true' : 'false' ?>">
                            🖼 Images (<?= count($evImages) ?>)
                        </button>

                        <!-- Edit -->
                        <a href="admin.php?tab=events&edit=<?= $ev['id'] ?>"
                           class="btn btn-sm btn-warning"
                           style="border-radius:10px;font-size:.8rem;width:auto;padding:.35rem .9rem">
                            ✏️ Edit
                        </a>

                        <!-- Delete -->
                        <form method="post" action="admin.php?tab=events"
                              onsubmit="return confirm('Delete \'<?= addslashes(htmlspecialchars($ev['title'])) ?>\' and all its images?')">
                            <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                            <button type="submit" name="delete_event"
                                    class="btn btn-sm btn-danger"
                                    style="border-radius:10px;font-size:.8rem;width:auto;padding:.35rem .9rem">
                                🗑 Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Image panel (collapsible) -->
            <div class="collapse <?= $isHighlight ? 'show' : '' ?>" id="<?= $collapseId ?>">
                <div class="images-panel">
                    <p class="mb-2" style="font-weight:600;font-size:.9rem;">Event Images</p>

                    <!-- Existing images -->
                    <div class="event-img-grid" id="grid-<?= $ev['id'] ?>">
                        <?php foreach ($evImages as $img): ?>
                            <div class="event-img-thumb" id="thumb-<?= $img['id'] ?>">
                                <img src="<?= htmlspecialchars($img['path']) ?>"
                                     alt="<?= htmlspecialchars($img['filename']) ?>">
                                <button class="del-img-btn"
                                        title="Remove image"
                                        onclick="deleteImage(<?= $img['id'] ?>, <?= $ev['id'] ?>)">×</button>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Dropzone upload area -->
                    <div class="mt-3">
                        <p class="mb-1" style="font-size:.82rem;color:var(--text-secondary)">
                            Drop images here or click to browse — JPG, PNG, WebP, GIF · max 8 MB each
                        </p>
                        <div class="dropzone event-dropzone" id="dz-<?= $ev['id'] ?>"
                             data-event-id="<?= $ev['id'] ?>">
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

    <?php endif; ?>

    <!-- ═══════════════════  USERS TAB  ═══════════════════════════════════════ -->
    <?php if ($tab === 'users'): ?>

        <?php if ($importMessage): ?>
            <div class="alert alert-info"><?= htmlspecialchars($importMessage) ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title" style="text-align:left">Export Users</h5>
                <p class="card-text">Download a simple CSV with ID, Name, Email, Role.</p>
                <a class="btn btn-primary" style="width:auto" href="export_users.php">⬇ Download CSV</a>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title" style="text-align:left">Import Users</h5>
                <p class="card-text">
                    Upload a CSV with header: <code>ID, Name, Email, Role</code>.
                    New users get password <strong>changeme</strong>.
                </p>
                <form action="admin.php?tab=users" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <input class="form-control" type="file" name="csv_file" accept=".csv" required>
                    </div>
                    <button class="btn btn-success" style="width:auto" type="submit" name="import_users">⬆ Import CSV</button>
                </form>
            </div>
        </div>

    <?php endif; ?>
</div>

<!-- ────────────────────────  DROPZONE INIT  ──────────────────────────── -->
<script>

document.addEventListener('DOMContentLoaded', function () {

    // Find every form with class "dropzone"
    document.querySelectorAll('.event-dropzone').forEach(function (el) {
        const eventId = el.dataset.eventId;
        const gridDiv = document.getElementById('grid-' + eventId);

        new Dropzone(el, {
            url           : 'upload_event_image.php',
            paramName     : 'file',
            maxFilesize   : 8,           // MB
            acceptedFiles : 'image/jpeg,image/png,image/webp,image/gif',
            addRemoveLinks: true,
            dictDefaultMessage: '📷 Drop images here or click to browse',
            dictRemoveFile: 'Remove',

            // Attach event_id to every upload request
            sending: function (file, xhr, formData) {
                formData.append('event_id', eventId);
            },

            // After upload: append thumbnail to the existing images grid
            success: function (file, response) {
                console.log('Upload success for event ' + eventId, response);
                if (response.success) {
                    const thumb = document.createElement('div');
                    thumb.className = 'event-img-thumb';
                    thumb.id = 'thumb-' + response.image_id;
                    thumb.innerHTML =
                        '<img src="' + response.path + '" alt="' + response.filename + '">' +
                        '<button class="del-img-btn" title="Remove image" ' +
                        'onclick="deleteImage(' + response.image_id + ', ' + eventId + ')">×</button>';
                    gridDiv.appendChild(thumb);

                    updateImgCount(eventId);
                } else {
                    alert('Server error: ' + (response.error || 'Unknown error'));
                }
                this.removeFile(file);
            },

            error: function (file, msg) {
                console.error('Dropzone error for event ' + eventId, msg);
                alert('Upload error: ' + (typeof msg === 'string' ? msg : (msg.error || 'Check console')));
                this.removeFile(file);
            }
        });
    });
});

// Delete a single image via AJAX
function deleteImage(imageId, eventId) {
    if (!confirm('Remove this image?')) return;

    const formData = new FormData();
    formData.append('image_id', imageId);

    fetch('delete_event_image.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const thumb = document.getElementById('thumb-' + imageId);
                if (thumb) thumb.remove();
                updateImgCount(eventId);
            } else {
                alert('Could not delete image: ' + (data.error || 'unknown error'));
            }
        })
        .catch(() => alert('Network error while deleting image.'));
}

// Refresh the "Images (n)" badge on the event row
function updateImgCount(eventId) {
    const grid     = document.getElementById('grid-' + eventId);
    const collapseTarget = document.getElementById('imgs-' + eventId);
    if (!grid || !collapseTarget) return;

    const count = grid.querySelectorAll('.event-img-thumb').length;

    // Find the toggle button by its data-bs-target attribute
    const btn = document.querySelector('[data-bs-target="#imgs-' + eventId + '"]');
    if (btn) btn.textContent = '🖼 Images (' + count + ')';
}
</script>

</body>
</html>
