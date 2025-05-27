<?php 
session_start();
require_once 'config.php';

// HTML escape helper
function h($s) {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// Only allow forensic_officer
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'forensic_officer') {
    header('Location: login.php');
    exit;
}

$db = getDbConnection();
$officer_id = $_SESSION['user_id'] ?? 0;
$username = h($_SESSION['username'] ?? '');

$errors = [];
$success = '';
$analysisSummary = null;

// Suspicious keywords for pattern recognition
$suspiciousKeywords = ['threat', 'attack', 'bomb', 'explosive', 'weapon', 'gun', 'kill', 'terror'];

// Handle file upload and processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_analysis'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $file = $_FILES['evidence_file'] ?? null;

    if ($title === '') {
        $errors[] = 'Title is required.';
    }
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Please select a file to upload.';
    }

    // Accept only text, log, csv, json, xml files for analysis
    $allowedExt = ['txt', 'log', 'csv', 'json', 'xml'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt)) {
        $errors[] = 'Invalid file type. Allowed: ' . implode(', ', $allowedExt);
    }

    if (empty($errors)) {
        $uploadDir = __DIR__ . '/uploads/forensic_analysis/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $newName = uniqid('fanal_') . '.' . $ext;
        $destPath = $uploadDir . $newName;

        if (move_uploaded_file($file['tmp_name'], $destPath)) {
            $relPath = 'uploads/forensic_analysis/' . $newName;

            // Extract text content for analysis (basic)
            $content = '';
            if (in_array($ext, ['txt', 'log', 'csv', 'json', 'xml'])) {
                $content = file_get_contents($destPath);
            }

            // Metadata extraction: filesize, word count, timestamps, GPS coords, device info
            preg_match_all('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $content, $timestamps);
            preg_match_all('/GPS[: ]?(-?\d+\.\d+),\s*(-?\d+\.\d+)/i', $content, $gpsMatches);
            preg_match_all('/Device[: ]?([a-zA-Z0-9\s]+)/i', $content, $deviceMatches);

            // Pattern recognition: suspicious keywords count
            $lowerContent = strtolower($content);
            $foundKeywords = [];
            foreach ($suspiciousKeywords as $kw) {
                if (strpos($lowerContent, $kw) !== false) {
                    $foundKeywords[] = $kw;
                }
            }

            $metadata = [
                'file_size' => filesize($destPath),
                'word_count' => str_word_count($content),
                'timestamps_found' => count($timestamps[0]),
                'sample_timestamps' => array_slice($timestamps[0], 0, 5),
                'gps_coords' => $gpsMatches ? array_map(null, $gpsMatches[1], $gpsMatches[2]) : [],
                'device_info' => $deviceMatches[1] ?? [],
                'suspicious_keywords' => $foundKeywords,
            ];

            // Store data and metadata as JSON string
            $stmt = $db->prepare("INSERT INTO forensic_evidence (officer_id, title, description, file_path, metadata, content) VALUES (?, ?, ?, ?, ?, ?)");
            $metaJson = json_encode($metadata);
            $stmt->bind_param('isssss', $officer_id, $title, $description, $relPath, $metaJson, $content);

            if ($stmt->execute()) {
                $success = 'File uploaded and analyzed successfully.';
                $analysisSummary = $metadata; // Show analysis summary below upload form
            } else {
                $errors[] = 'DB error: ' . $stmt->error;
                unlink($destPath);
            }
            $stmt->close();
        } else {
            $errors[] = 'Failed to move uploaded file.';
        }
    }
}

// Handle search/filter action
$searchTerm = trim($_GET['search_term'] ?? '');
$where = '';
$params = [];
$types = '';
if ($searchTerm !== '') {
    $where = "WHERE content LIKE ?";
    $params[] = '%' . $searchTerm . '%';
    $types = 's';
}

// Fetch forensic evidence list with optional content search
$sql = "SELECT id, title, description, file_path, metadata, uploaded_at, content FROM forensic_evidence $where ORDER BY uploaded_at DESC LIMIT 20";
$stmt = $db->prepare($sql);
if ($where) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$evidenceList = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Case Analysis Tools - Forensic Analytics</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <style>
        body { background: #f4f6f9; }
        .content-row { display: none; white-space: pre-wrap; background:#f0f0f0; font-family: monospace; max-height: 300px; overflow-y: auto; }
        .badge-suspicious { background-color: #dc3545; color: white; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container">
    <a class="navbar-brand" href="#"><i class="fas fa-chart-line me-2"></i> Case Analysis Tools</a>
    <span class="navbar-text text-light"><i class="fa fa-user me-1"></i> <?= $username ?></span>
    <a href="logout.php" class="btn btn-outline-light ms-3">Log Out</a>
  </div>
</nav>

<div class="container">
    <h2 class="mb-4">Upload and Analyze Forensic Data</h2>

    <?php if ($errors): ?>
        <div class="alert alert-danger"><ul>
            <?php foreach ($errors as $e): ?>
                <li><?= h($e) ?></li>
            <?php endforeach; ?>
        </ul></div>
    <?php elseif ($success): ?>
        <div class="alert alert-success"><?= h($success) ?></div>

        <!-- Analysis Summary -->
        <div class="card mb-4 p-3 shadow-sm">
            <h5>Analysis Summary</h5>
            <ul>
                <li><strong>File Size:</strong> <?= number_format($analysisSummary['file_size'] / 1024, 2) ?> KB</li>
                <li><strong>Word Count:</strong> <?= $analysisSummary['word_count'] ?></li>
                <li><strong>Timestamps Found:</strong> <?= $analysisSummary['timestamps_found'] ?></li>
                <?php if (!empty($analysisSummary['sample_timestamps'])): ?>
                    <li><strong>Sample Timestamps:</strong>
                        <ul>
                            <?php foreach ($analysisSummary['sample_timestamps'] as $ts): ?>
                                <li><?= h($ts) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                <?php endif; ?>
                <?php if (!empty($analysisSummary['gps_coords'])): ?>
                    <li><strong>GPS Coordinates:</strong>
                        <ul>
                            <?php foreach ($analysisSummary['gps_coords'] as $coord): ?>
                                <li><?= h($coord[0]) ?>, <?= h($coord[1]) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                <?php endif; ?>
                <?php if (!empty($analysisSummary['device_info'])): ?>
                    <li><strong>Device Info:</strong>
                        <ul>
                            <?php foreach ($analysisSummary['device_info'] as $device): ?>
                                <li><?= h($device) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                <?php endif; ?>
                <?php if (!empty($analysisSummary['suspicious_keywords'])): ?>
                    <li><strong>Suspicious Keywords Detected:</strong>
                        <?php foreach ($analysisSummary['suspicious_keywords'] as $kw): ?>
                            <span class="badge badge-suspicious me-1"><?= h($kw) ?></span>
                        <?php endforeach; ?>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="mb-4 row g-3">
        <div class="col-md-5">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" required />
        </div>
        <div class="col-md-7">
            <label class="form-label">Description</label>
            <input type="text" name="description" class="form-control" />
        </div>
        <div class="col-md-7">
            <label class="form-label">Upload File (txt, log, csv, json, xml)</label>
            <input type="file" name="evidence_file" class="form-control" accept=".txt,.log,.csv,.json,.xml" required />
        </div>
        <div class="col-md-5 align-self-end">
            <button class="btn btn-primary w-100" name="upload_analysis" value="1"><i class="fas fa-upload me-1"></i> Upload & Analyze</button>
        </div>
    </form>

    <form method="get" class="mb-4 row g-3">
        <div class="col-md-10">
            <input type="text" name="search_term" class="form-control" placeholder="Search within uploaded files content" value="<?= h($searchTerm) ?>" />
        </div>
        <div class="col-md-2">
            <button class="btn btn-secondary w-100" type="submit"><i class="fa fa-search me-1"></i> Search</button>
        </div>
    </form>

    <h4>Uploaded Evidence</h4>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Uploaded At</th>
                    <th>Metadata</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($evidenceList)): ?>
                    <tr><td colspan="5" class="text-center text-muted">No evidence uploaded.</td></tr>
                <?php else: foreach ($evidenceList as $ev): ?>
                    <?php 
                        $meta = json_decode($ev['metadata'], true);
                        $metaStr = '';
                        if ($meta) {
                            $metaStr = "File Size: " . number_format($meta['file_size'] / 1024, 2) . " KB<br>";
                            $metaStr .= "Words: " . $meta['word_count'] . "<br>";
                            $metaStr .= "Timestamps Found: " . $meta['timestamps_found'] . "<br>";
                            if (!empty($meta['sample_timestamps'])) {
                                $metaStr .= "Sample Timestamps:<br>" . implode('<br>', $meta['sample_timestamps']);
                            }
                            if (!empty($meta['gps_coords'])) {
                                $gpsList = array_map(function($c) { return h($c[0]) . ', ' . h($c[1]); }, $meta['gps_coords']);
                                $metaStr .= "<br>GPS Coordinates:<br>" . implode('<br>', $gpsList);
                            }
                            if (!empty($meta['device_info'])) {
                                $metaStr .= "<br>Device Info:<br>" . implode('<br>', array_map('h', $meta['device_info']));
                            }
                            if (!empty($meta['suspicious_keywords'])) {
                                $metaStr .= "<br>Suspicious Keywords:<br>";
                                foreach ($meta['suspicious_keywords'] as $kw) {
                                    $metaStr .= '<span class="badge badge-suspicious me-1">' . h($kw) . '</span> ';
                                }
                            }
                        }
                    ?>
                    <tr>
                        <td><?= h($ev['title']) ?></td>
                        <td><?= h($ev['description']) ?></td>
                        <td><?= h($ev['uploaded_at']) ?></td>
                        <td><?= $metaStr ?></td>
                        <td>
                            <a href="<?= h($ev['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">View File</a>
                            <button class="btn btn-sm btn-outline-success" onclick="toggleContent(<?= $ev['id'] ?>)">View Content</button>
                        </td>
                    </tr>
                    <tr id="content-<?= $ev['id'] ?>" class="content-row">
                        <td colspan="5"><?= h($ev['content']) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleContent(id) {
    const el = document.getElementById('content-' + id);
    if (el.style.display === 'none' || el.style.display === '') {
        el.style.display = 'table-row';
    } else {
        el.style.display = 'none';
    }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
