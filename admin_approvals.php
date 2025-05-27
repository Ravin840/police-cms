<?php
session_start();
require_once 'config.php';

// Only allow admins
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$db = getDbConnection();

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type   = $_POST['type'] ?? '';
    $id     = intval($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($id && in_array($action, ['approve', 'reject'], true)) {
        $status = $action === 'approve' ? 'approved' : 'rejected';
        if ($type === 'fine') {
            $stmt = $db->prepare(
                "UPDATE fines SET status = ?, approved_by = ?, approved_at = NOW() WHERE fine_id = ?"
            );
        } elseif ($type === 'permit') {
            $stmt = $db->prepare(
                "UPDATE permits SET status = ?, approved_by = ?, approved_at = NOW() WHERE permit_id = ?"
            );
        }
        if (isset($stmt) && $stmt) {
            $stmt->bind_param('sii', $status, $_SESSION['user_id'], $id);
            $stmt->execute();
            $stmt->close();
        }
    }
    // Redirect to avoid form resubmission
    header('Location: admin_approvals.php');
    exit;
}

// Fetch pending fines (*** THE FIXED LINE! ***)
$finesRes = $db->query(
    "SELECT fine_id, offender_license, fine_amount, issued_date AS submitted_at FROM fines WHERE status = 'pending'"
);

// Fetch pending permits
$permRes = $db->query(
    "SELECT permit_id, applicant_name, permit_type, submitted_at FROM permits WHERE status = 'pending'"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Fines & Permits Approval</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #000; color: #fff; }
    .table-striped tbody tr:nth-of-type(odd) { background-color: rgba(255,255,255,0.05); }
    table { border-color: #333; }
    th, td { border-top: 1px solid #333; }
    h2, h4 { border-bottom: 1px solid #444; padding-bottom: .5rem; }
    .btn-success { background-color: #fff; color: #000; border: 1px solid #fff; }
    .btn-success:hover { background-color: #e0e0e0; }
    .btn-danger { background-color: #555; color: #fff; border: 1px solid #fff; }
    .btn-danger:hover { background-color: #444; }
    .section { margin-bottom: 2rem; }
  </style>
</head>
<body class="bg-light p-4">
  <div class="container">
    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <h2 class="card-title mb-1">Fines & Permits Approval</h2>
        <p class="text-muted">Review and process submitted fines and permit applications.</p>
      </div>
    </div>

    <!-- Fines -->
    <section class="mb-5">
      <div class="card shadow-sm">
        <div class="card-header bg-white">
          <h4 class="mb-0"><i class="fas fa-money-check-alt me-2"></i>Pending Fines</h4>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th>ID</th>
                  <th>License</th>
                  <th>Amount</th>
                  <th>Issued Date</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($fine = $finesRes->fetch_assoc()): ?>
                <tr>
                  <td><?= htmlspecialchars($fine['fine_id']) ?></td>
                  <td><?= htmlspecialchars($fine['offender_license']) ?></td>
                  <td>$<?= htmlspecialchars(number_format($fine['fine_amount'], 2)) ?></td>
                  <td><?= htmlspecialchars($fine['submitted_at']) ?></td>
                  <td>
                    <form method="post" class="d-inline">
                      <input type="hidden" name="type" value="fine">
                      <input type="hidden" name="id" value="<?= $fine['fine_id'] ?>">
                      <button name="action" value="approve" class="btn btn-success btn-sm me-1">Approve</button>
                      <button name="action" value="reject" class="btn btn-outline-danger btn-sm">Reject</button>
                    </form>
                  </td>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </section>

    <!-- Permits -->
    <section>
      <div class="card shadow-sm">
        <div class="card-header bg-white">
          <h4 class="mb-0"><i class="fas fa-file-signature me-2"></i>Pending Permits</h4>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th>ID</th>
                  <th>Applicant</th>
                  <th>Type</th>
                  <th>Submitted At</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($permit = $permRes->fetch_assoc()): ?>
                <tr>
                  <td><?= htmlspecialchars($permit['permit_id']) ?></td>
                  <td><?= htmlspecialchars($permit['applicant_name']) ?></td>
                  <td><?= htmlspecialchars($permit['permit_type']) ?></td>
                  <td><?= htmlspecialchars($permit['submitted_at']) ?></td>
                  <td>
                    <form method="post" class="d-inline">
                      <input type="hidden" name="type" value="permit">
                      <input type="hidden" name="id" value="<?= $permit['permit_id'] ?>">
                      <button name="action" value="approve" class="btn btn-success btn-sm me-1">Approve</button>
                      <button name="action" value="reject" class="btn btn-outline-danger btn-sm">Reject</button>
                    </form>
                  </td>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </section>
  </div>
</body>
</html>
<?php
$db->close();
?>
