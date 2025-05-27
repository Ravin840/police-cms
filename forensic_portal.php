<?php
session_start();
require_once 'config.php';

function h($s) {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'forensic_officer') {
    header('Location: login.php');
    exit;
}

$db = getDbConnection();
$officer_id = $_SESSION['user_id'] ?? 0;
$username = h($_SESSION['username'] ?? '');

$errors = [];
$success = '';
// Evidence Upload Handler (same as before)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_evidence'])) {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $tags        = trim($_POST['tags'] ?? '');
    $file        = $_FILES['evidence_file'] ?? null;

    if ($title === '') $errors[] = 'Title is required.';
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) $errors[] = 'Please select a file to upload.';

    $allowed_ext = ['jpg','jpeg','png','pdf','docx','mp4','avi','mov'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_ext)) {
        $errors[] = 'Invalid file type. Allowed types: ' . implode(', ', $allowed_ext);
    }

    if (empty($errors)) {
        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $newName = uniqid('evi_') . '.' . $ext;
        $destPath = $uploadDir . $newName;

        if (move_uploaded_file($file['tmp_name'], $destPath)) {
            $relPath = 'uploads/' . $newName;
            $stmt = $db->prepare("INSERT INTO evidence (officer_id, title, description, file_path, tags) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('issss', $officer_id, $title, $description, $relPath, $tags);
            if ($stmt->execute()) {
                $success = 'Evidence uploaded successfully.';
                // Log chain of custody entry
                $logStmt = $db->prepare("INSERT INTO custody_log (evidence_id, officer_id, action, timestamp) VALUES (?, ?, ?, NOW())");
                $action = 'Uploaded evidence';
                $logStmt->bind_param('iis', $stmt->insert_id, $officer_id, $action);
                $logStmt->execute();
                $logStmt->close();
            } else {
                $errors[] = 'Database error: ' . $stmt->error;
                unlink($destPath);
            }
            $stmt->close();
        } else {
            $errors[] = 'Failed to move uploaded file.';
        }
    }
}

// Fetch total evidence count
$evStats = $db->query("SELECT COUNT(*) AS num_evidence FROM evidence")->fetch_assoc();
$totalEvidence = $evStats['num_evidence'] ?? 0;

// Evidence fetch with optional search and tag filtering
$where = [];
$params = [];
$types = "";

$searchTitle = trim($_GET['search_title'] ?? '');
$filterTags = trim($_GET['filter_tags'] ?? '');

if ($searchTitle !== '') {
    $where[] = "title LIKE ?";
    $params[] = "%$searchTitle%";
    $types .= 's';
}

if ($filterTags !== '') {
    $where[] = "FIND_IN_SET(?, tags)";
    $params[] = $filterTags;
    $types .= 's';
}

$whereSQL = "";
if (!empty($where)) {
    $whereSQL = "WHERE " . implode(' AND ', $where);
}

$sql = "SELECT evidence_id, officer_id, title, description, file_path, tags, uploaded_at
        FROM evidence
        $whereSQL
        ORDER BY uploaded_at DESC
        LIMIT 15";

$stmt = $db->prepare($sql);
if ($whereSQL) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$recent = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Chain of custody log query
$logSql = "
    SELECT cl.id, cl.evidence_id, cl.action, cl.timestamp AS action_time, s.username
    FROM custody_log cl
    LEFT JOIN staff s ON cl.officer_id = s.id
    ORDER BY cl.timestamp DESC
    LIMIT 15
";
$logResult = $db->query($logSql);
$custody_logs = $logResult ? $logResult->fetch_all(MYSQLI_ASSOC) : [];

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Forensic Analytics Portal</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
<style>
body { background: #f4f6f9; }
.card { border-radius: 1rem; }
.evidence-link { text-decoration: underline; color: #0056b3; }
.tag-badge { cursor: pointer; }
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container">
    <a class="navbar-brand" href="#"><i class="fas fa-microscope me-2"></i> Forensic Analytics</a>
    <span class="navbar-text text-light"><i class="fa fa-user me-1"></i><?= $username ?></span>
    <a href="logout.php" class="btn btn-outline-light ms-3">Log Out</a>
  </div>
</nav>
<div class="container mb-5">

  <!-- Nav tabs -->
  <ul class="nav nav-tabs mb-4" id="forensicTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="upload-tab" data-bs-toggle="tab" data-bs-target="#upload" type="button" role="tab" aria-controls="upload" aria-selected="true">
        <i class="fas fa-upload me-1"></i> Upload & Manage Evidence
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="analysis-tab" data-bs-toggle="tab" data-bs-target="#analysis" type="button" role="tab" aria-controls="analysis" aria-selected="false">
        <i class="fas fa-chart-line me-1"></i> Case Analysis Tools
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="reports-tab" data-bs-toggle="tab" data-bs-target="#reports" type="button" role="tab" aria-controls="reports" aria-selected="false">
        <i class="fas fa-file-alt me-1"></i> Generate Reports
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="chain-tab" data-bs-toggle="tab" data-bs-target="#chain" type="button" role="tab" aria-controls="chain" aria-selected="false">
        <i class="fas fa-link me-1"></i> Chain of Custody Tracking
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="collab-tab" data-bs-toggle="tab" data-bs-target="#collab" type="button" role="tab" aria-controls="collab" aria-selected="false">
        <i class="fas fa-comments me-1"></i> Collaboration
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="search-tab" data-bs-toggle="tab" data-bs-target="#search" type="button" role="tab" aria-controls="search" aria-selected="false">
        <i class="fas fa-search me-1"></i> Search & Filter Evidence
      </button>
    </li>
  </ul>

  <div class="tab-content" id="forensicTabsContent">

    <!-- Upload & Manage Evidence Tab -->
    <div class="tab-pane fade show active" id="upload" role="tabpanel" aria-labelledby="upload-tab">
      <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
          <h4 class="mb-0"><i class="fas fa-upload text-primary me-2"></i> Upload Digital Evidence</h4>
        </div>
        <div class="card-body">
          <?php if ($errors): ?>
            <div class="alert alert-danger"><ul class="mb-0">
              <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
            </ul></div>
          <?php elseif ($success): ?>
            <div class="alert alert-success"><?= h($success) ?></div>
          <?php endif; ?>
          <form method="post" enctype="multipart/form-data" class="row g-3 mb-3">
            <div class="col-md-5">
              <label class="form-label">Title</label>
              <input type="text" name="title" class="form-control" required />
            </div>
            <div class="col-md-5">
              <label class="form-label">Tags (comma separated)</label>
              <input type="text" name="tags" class="form-control" placeholder="e.g. video, phone, chat" />
            </div>
            <div class="col-md-5">
              <label class="form-label">Evidence File</label>
              <input type="file" name="evidence_file" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.docx,.mp4,.avi,.mov" required />
              <div class="form-text">Allowed: JPG, JPEG, PNG, PDF, DOCX, MP4, AVI, MOV.</div>
            </div>
            <div class="col-md-12">
              <label class="form-label">Description</label>
              <textarea name="description" class="form-control" rows="2"></textarea>
            </div>
            <div class="col-12 text-end">
              <button class="btn btn-primary" name="upload_evidence" value="1">
                <i class="fas fa-upload me-1"></i> Upload
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Recent Evidence Table -->
      <div class="card shadow-sm">
        <div class="card-header bg-white"><strong>Recent Digital Evidence</strong></div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-striped table-hover mb-0 align-middle">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Officer ID</th>
                  <th>Title</th>
                  <th>Tags</th>
                  <th>Uploaded At</th>
                  <th>File</th>
                  <th>Description</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($recent)): ?>
                  <tr><td colspan="7" class="text-center text-muted">No evidence found.</td></tr>
                <?php else: foreach ($recent as $ev): ?>
                  <tr>
                    <td><?= h($ev['evidence_id']) ?></td>
                    <td><?= h($ev['officer_id']) ?></td>
                    <td><?= h($ev['title']) ?></td>
                    <td>
                      <?php
                        if (!empty($ev['tags'])) {
                          $tagsArr = explode(',', $ev['tags']);
                          foreach ($tagsArr as $t) {
                            echo '<span class="badge bg-info text-dark me-1 tag-badge">' . h(trim($t)) . '</span>';
                          }
                        }
                      ?>
                    </td>
                    <td><?= h($ev['uploaded_at']) ?></td>
                    <td>
                      <?php if ($ev['file_path']): ?>
                        <a href="<?= h($ev['file_path']) ?>" class="evidence-link" target="_blank">View File</a>
                      <?php else: ?>
                        <span class="text-muted">None</span>
                      <?php endif; ?>
                    </td>
                    <td><?= nl2br(h($ev['description'])) ?></td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Case Analysis Tools Tab -->
    <div class="tab-pane fade" id="analysis" role="tabpanel" aria-labelledby="analysis-tab">
      <div class="card shadow-sm p-3">
        <h5>Case Analysis Tools</h5>
        <p>Automated text analysis, metadata extraction, and pattern recognition tools to assist forensic investigations.</p>
        <ul>
          <li>Analyze call logs, emails, chat transcripts.</li>
          <li>Extract metadata such as timestamps, GPS coordinates, and device info.</li>
          <li>Detect patterns linking evidence to suspects or crime scenes.</li>
        </ul>
        <p><em>Note: These features are placeholders for integration with forensic software.</em></p>
        <form>
          <div class="mb-3">
            <label for="uploadCallLogs" class="form-label">Upload Call Logs / Transcripts</label>
            <input class="form-control" type="file" id="uploadCallLogs" accept=".txt,.csv,.json" />
          </div>
          <button type="button" class="btn btn-primary" disabled>Analyze Text Data (Coming Soon)</button>
        </form>

        <hr />

        <form>
          <div class="mb-3">
            <label for="uploadMetadataFiles" class="form-label">Upload Files for Metadata Extraction</label>
            <input class="form-control" type="file" id="uploadMetadataFiles" multiple />
          </div>
          <button type="button" class="btn btn-primary" disabled>Extract Metadata (Coming Soon)</button>
        </form>

        <hr />

        <button type="button" class="btn btn-primary" disabled>Run Pattern Recognition (Coming Soon)</button>
      </div>
    </div>

    <!-- Generate Reports Tab -->
    <div class="tab-pane fade" id="reports" role="tabpanel" aria-labelledby="reports-tab">
      <div class="card shadow-sm p-3">
        <h5>Generate Forensic Reports</h5>
        <p>Create detailed forensic reports including charts, timelines, and evidence summaries.</p>
        <button class="btn btn-primary" disabled>Generate PDF Report (Coming Soon)</button>
      </div>
    </div>

    <!-- Chain of Custody Tracking Tab -->
    <div class="tab-pane fade" id="chain" role="tabpanel" aria-labelledby="chain-tab">
      <div class="card shadow-sm">
        <div class="card-header bg-white"><strong>Chain of Custody Log</strong></div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-striped table-hover mb-0 align-middle">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Evidence ID</th>
                  <th>User</th>
                  <th>Action</th>
                  <th>Time</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($custody_logs)): ?>
                  <tr><td colspan="5" class="text-center text-muted">No logs found.</td></tr>
                <?php else: foreach ($custody_logs as $log): ?>
                  <tr>
                    <td><?= h($log['id']) ?></td>
                    <td><?= h($log['evidence_id']) ?></td>
                    <td><?= h($log['username'] ?? 'Unknown') ?></td>
                    <td><?= h($log['action']) ?></td>
                    <td><?= h($log['action_time']) ?></td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

   
    <!-- Collaboration Tab -->
<div class="tab-pane fade" id="collab" role="tabpanel" aria-labelledby="collab-tab" style="height: 750px;">
  <iframe src="forensic_collab.php" style="width: 100%; height: 100%; border: none;"></iframe>
</div>

    <!-- Search & Filter Evidence Tab -->
    <div class="tab-pane fade" id="search" role="tabpanel" aria-labelledby="search-tab">
      <div class="card shadow-sm p-3">
        <h5>Search & Filter Evidence</h5>
        <form method="get" class="row g-3 mb-3">
          <div class="col-md-5">
            <input type="text" name="search_title" class="form-control" placeholder="Search by Evidence Title" value="<?= h($searchTitle) ?>" />
          </div>
          <div class="col-md-5">
            <input type="text" name="filter_tags" class="form-control" placeholder="Filter by Tag" value="<?= h($filterTags) ?>" />
          </div>
          <div class="col-md-2">
            <button class="btn btn-secondary w-100" type="submit"><i class="fa fa-search me-1"></i> Filter</button>
          </div>
        </form>

        <hr />

        <!-- Show filtered results again -->
        <div class="table-responsive">
          <table class="table table-striped table-hover align-middle">
            <thead>
              <tr>
                <th>ID</th>
                <th>Officer ID</th>
                <th>Title</th>
                <th>Tags</th>
                <th>Uploaded At</th>
                <th>File</th>
                <th>Description</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($recent)): ?>
                <tr><td colspan="7" class="text-center text-muted">No evidence found.</td></tr>
              <?php else: foreach ($recent as $ev): ?>
                <tr>
                  <td><?= h($ev['evidence_id']) ?></td>
                  <td><?= h($ev['officer_id']) ?></td>
                  <td><?= h($ev['title']) ?></td>
                  <td>
                    <?php
                      if (!empty($ev['tags'])) {
                        $tagsArr = explode(',', $ev['tags']);
                        foreach ($tagsArr as $t) {
                          echo '<span class="badge bg-info text-dark me-1 tag-badge">' . h(trim($t)) . '</span>';
                        }
                      }
                    ?>
                  </td>
                  <td><?= h($ev['uploaded_at']) ?></td>
                  <td>
                    <?php if ($ev['file_path']): ?>
                      <a href="<?= h($ev['file_path']) ?>" class="evidence-link" target="_blank">View File</a>
                    <?php else: ?>
                      <span class="text-muted">None</span>
                    <?php endif; ?>
                  </td>
                  <td><?= nl2br(h($ev['description'])) ?></td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div> <!-- end tab-content -->

</div> <!-- end container -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
