<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'forensic_officer') {
    header('Location: login.php');
    exit;
}

$db = getDbConnection();
$user_id = $_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username'] ?? 'Forensic Analyst');

function h($s) {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// Handle new note submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['evidence_id'], $_POST['note_text'])) {
    $evidence_id = (int)$_POST['evidence_id'];
    $note_text = trim($_POST['note_text']);
    if ($note_text !== '') {
        $stmt = $db->prepare("INSERT INTO forensic_notes (evidence_id, author, note_text) VALUES (?, ?, ?)");
        $stmt->bind_param('iss', $evidence_id, $username, $note_text);
        $stmt->execute();
        $stmt->close();

        // Redirect to avoid resubmission
        header("Location: forensic_collab.php?evidence_id=$evidence_id");
        exit;
    }
}

// Handle task assignment (simple demo storing assigned user in session or DB)
// For demo, we'll just accept POST with evidence_id and assigned_user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_evidence_id'], $_POST['assign_user_id'])) {
    $assign_evidence_id = (int)$_POST['assign_evidence_id'];
    $assign_user_id = (int)$_POST['assign_user_id'];

    // For demo, we insert/update a simple assignment table:
    // Assuming you have table forensic_tasks (task_id, evidence_id, assigned_to, assigned_at)
    $stmt = $db->prepare("REPLACE INTO forensic_tasks (evidence_id, assigned_to, assigned_at) VALUES (?, ?, NOW())");
    $stmt->bind_param('ii', $assign_evidence_id, $assign_user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: forensic_collab.php?evidence_id=$assign_evidence_id");
    exit;
}

// Fetch list of forensic analysts to assign tasks
$analysts = [];
$result = $db->query("SELECT id, username FROM staff WHERE role = 'forensic_officer' ORDER BY username");
while ($row = $result->fetch_assoc()) {
    $analysts[] = $row;
}
$result->free();

// Fetch all evidence for dropdown & display
$evidenceList = [];
$result = $db->query("SELECT evidence_id, title FROM evidence ORDER BY uploaded_at DESC LIMIT 50");
while ($row = $result->fetch_assoc()) {
    $evidenceList[] = $row;
}
$result->free();

// Get selected evidence_id from GET param or first evidence
$selected_evidence_id = isset($_GET['evidence_id']) ? (int)$_GET['evidence_id'] : ($evidenceList[0]['evidence_id'] ?? 0);

// Fetch notes for selected evidence
$notes = [];
if ($selected_evidence_id) {
    $stmt = $db->prepare("SELECT author, note_text, created_at FROM forensic_notes WHERE evidence_id = ? ORDER BY created_at DESC");
    $stmt->bind_param('i', $selected_evidence_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $notes[] = $row;
    }
    $stmt->close();

    // Fetch assigned user (if any)
    $assigned_user_id = null;
    $stmt2 = $db->prepare("SELECT assigned_to FROM forensic_tasks WHERE evidence_id = ?");
    $stmt2->bind_param('i', $selected_evidence_id);
    $stmt2->execute();
    $stmt2->bind_result($assigned_user_id);
    $stmt2->fetch();
    $stmt2->close();
} else {
    $notes = [];
    $assigned_user_id = null;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Forensic Collaboration & Notes</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
<style>
body { background: #f8f9fa; }
.card-header { background-color: #343a40; color: white; }
.note-author { font-weight: 600; }
.note-time { font-size: 0.85em; color: #666; }
</style>
</head>
<body>
<div class="container py-4">
  <h2 class="mb-4">Forensic Collaboration & Notes</h2>
  <p>Logged in as: <strong><?= h($username) ?></strong></p>

  <form method="get" class="mb-3">
    <label for="evidenceSelect" class="form-label">Select Evidence:</label>
    <select name="evidence_id" id="evidenceSelect" class="form-select" onchange="this.form.submit()">
      <?php foreach ($evidenceList as $ev): ?>
        <option value="<?= $ev['evidence_id'] ?>" <?= $ev['evidence_id'] == $selected_evidence_id ? 'selected' : '' ?>>
          <?= h($ev['title']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </form>

  <?php if (!$selected_evidence_id): ?>
    <p class="text-muted">No evidence found.</p>
  <?php else: ?>
    <div class="card mb-4">
      <div class="card-header">Notes for Evidence: <?= h(current(array_filter($evidenceList, fn($e) => $e['evidence_id'] == $selected_evidence_id))['title']) ?></div>
      <div class="card-body" style="max-height: 350px; overflow-y: auto;">
        <?php if (!$notes): ?>
          <p class="text-muted">No notes yet.</p>
        <?php else: ?>
          <?php foreach ($notes as $note): ?>
            <div class="mb-3 border-bottom pb-2">
              <div class="note-author"><?= h($note['author']) ?></div>
              <div class="note-time"><?= h($note['created_at']) ?></div>
              <div><?= nl2br(h($note['note_text'])) ?></div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
      <form method="post" class="mt-3">
        <input type="hidden" name="evidence_id" value="<?= $selected_evidence_id ?>" />
        <textarea name="note_text" class="form-control mb-2" rows="3" placeholder="Add a new note..." required></textarea>
        <button class="btn btn-primary" type="submit">Add Note</button>
      </form>
    </div>

    <div class="card mb-4">
      <div class="card-header">Assign Task</div>
      <div class="card-body">
        <form method="post" class="row g-3 align-items-center">
          <input type="hidden" name="assign_evidence_id" value="<?= $selected_evidence_id ?>" />
          <div class="col-auto">
            <label for="assignUser" class="col-form-label">Assign to forensic analyst:</label>
          </div>
          <div class="col-auto">
            <select name="assign_user_id" id="assignUser" class="form-select" required>
              <option value="">-- Select Analyst --</option>
              <?php foreach ($analysts as $a): ?>
                <option value="<?= $a['id'] ?>" <?= $a['id'] == $assigned_user_id ? 'selected' : '' ?>>
                  <?= h($a['username']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-auto">
            <button type="submit" class="btn btn-success">Assign Task</button>
          </div>
        </form>
        <?php if ($assigned_user_id): ?>
          <p class="mt-2 text-success">
            Current Task Assigned To: <strong><?= h(current(array_filter($analysts, fn($a) => $a['id'] == $assigned_user_id))['username'] ?? 'Unknown') ?></strong>
          </p>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
