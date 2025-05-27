<?php
session_start();

// 1) Auth check: only police_officer can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'police_officer') {
    header('Location: login.php');
    exit;
}

// 2) Officer ID: prefer session user_id, otherwise default to Ravin (1)
if (!isset($_SESSION['user_id'])) {
    // for testing/demo only: default to officer_id = 1 (Ravin)
    $_SESSION['user_id'] = 1;
}
$officer_id = (int)$_SESSION['user_id'];
$username   = htmlspecialchars($_SESSION['username'] ?? 'Ravin');

// 3) Database connection
$mysqli = new mysqli('localhost','root','','crime_db');
if ($mysqli->connect_error) {
    die('DB Connection Error: '.$mysqli->connect_error);
}

// 4) Handle new-shift submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date  = $_POST['shift_date'] ?? '';
    $start = $_POST['start_time'] ?? '';
    $end   = $_POST['end_time'] ?? '';
    if ($date && $start && $end) {
        $ins = $mysqli->prepare(
            "INSERT INTO shifts (officer_id, shift_date, start_time, end_time)
             VALUES (?, ?, ?, ?)"
        );
        $ins->bind_param('isss', $officer_id, $date, $start, $end);
        $ins->execute();
        $ins->close();
    }
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

// 5) Compute Monday … Sunday of current week
$today   = new DateTime();
$weekDay = (int)$today->format('N');              // 1 (Mon)–7 (Sun)
$monday  = (clone $today)->modify('-'.($weekDay-1).' days')->format('Y-m-d');

// 6) Fetch this officer’s shifts for the week
$stmt = $mysqli->prepare(
    "SELECT shift_date, start_time, end_time
       FROM shifts
      WHERE officer_id = ?
        AND shift_date BETWEEN ? AND DATE_ADD(?, INTERVAL 6 DAY)
      ORDER BY shift_date, start_time"
);
$stmt->bind_param('iss', $officer_id, $monday, $monday);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Group by date
$byDate = [];
foreach ($rows as $r) {
    $byDate[$r['shift_date']][] = $r;
}

// Helper to format day header
function dayHeader(string $d): string {
    return (new DateTime($d))->format('D (d M)');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Weekly Shifts</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap 5 & FontAwesome -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    body { background: #f8f9fa; }
    .navbar { border-bottom: 1px solid #dee2e6; }
    .day-col { width:14.28%; text-align:center; }
    .no-shift { color:#6c757d; }
    .content { padding:2rem; }
  </style>
</head>
<body>
  <!-- NAVBAR -->
  <nav class="navbar navbar-expand-lg navbar-light bg-white">
    <div class="container-fluid">
      <a class="navbar-brand" href="#"><i class="fa-solid fa-user-clock me-2"></i>My Shifts</a>
      <div class="d-flex align-items-center">
        <span class="me-3"><i class="fa-solid fa-user"></i> <?= $username ?></span>
        <a href="logout.php" class="btn btn-outline-secondary btn-sm">
          <i class="fa-solid fa-right-from-bracket"></i> Log Out
        </a>
      </div>
    </div>
  </nav>

  <div class="container content">
    <div class="row">
      <!-- SCHEDULE TABLE -->
      <div class="col-lg-8 mb-4">
        <h4>Week of <?= (new DateTime($monday))->format('d M, Y') ?></h4>
        <div class="table-responsive">
          <table class="table table-bordered bg-white">
            <thead class="table-light">
              <tr>
                <?php for($i=0; $i<7; $i++):
                  $d = (new DateTime($monday))->modify("+{$i} days")->format('Y-m-d');
                ?>
                  <th class="day-col"><?= dayHeader($d) ?></th>
                <?php endfor; ?>
              </tr>
            </thead>
            <tbody>
              <tr>
                <?php for($i=0; $i<7; $i++):
                  $d   = (new DateTime($monday))->modify("+{$i} days")->format('Y-m-d');
                  $day = $byDate[$d] ?? [];
                ?>
                  <td class="align-top">
                    <?php if (empty($day)): ?>
                      <div class="no-shift">— no shift —</div>
                    <?php else: foreach($day as $s): ?>
                      <div class="mb-2">
                        <strong>
                          <?= substr($s['start_time'],0,5) ?>–<?= substr($s['end_time'],0,5) ?>
                        </strong>
                      </div>
                    <?php endforeach; endif; ?>
                  </td>
                <?php endfor; ?>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ADD SHIFT FORM -->
      <div class="col-lg-4">
        <div class="card shadow-sm">
          <div class="card-header bg-white">
            <i class="fa-solid fa-plus me-2 text-success"></i>Assign Shift
          </div>
          <div class="card-body">
            <form method="post" class="row g-3">
              <div class="col-12">
                <label class="form-label">Date</label>
                <input type="date" name="shift_date" class="form-control" required>
              </div>
              <div class="col-6">
                <label class="form-label">Start</label>
                <input type="time" name="start_time" class="form-control" required>
              </div>
              <div class="col-6">
                <label class="form-label">End</label>
                <input type="time" name="end_time" class="form-control" required>
              </div>
              <div class="col-12 text-end">
                <button class="btn btn-success">
                  <i class="fa-solid fa-save me-1"></i>Save
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>
