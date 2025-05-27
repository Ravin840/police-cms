<?php
session_start();
require_once 'config.php';

// Show all errors for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Step-1 fields
    $incident_datetime = $_POST['incident_datetime'] ?? '';
    $description       = trim($_POST['description'] ?? '');
    $latitude          = $_POST['latitude'] ?? '';
    $longitude         = $_POST['longitude'] ?? '';
    // Step-2 fields
    $suspect_desc = trim($_POST['suspect_description'] ?? '');
    $vehicle_info = trim($_POST['vehicle_info'] ?? '');

    // Validation
    if (empty($incident_datetime)) {
        $errors[] = 'Date and time of incident is required.';
    }
    if ($latitude === '' || $longitude === '' 
        || !is_numeric($latitude) || !is_numeric($longitude)) {
        $errors[] = 'Incident location is required.';
    }
    if (empty($description)) {
        $errors[] = 'Incident description is required.';
    }

    if (empty($errors)) {
        $db = getDbConnection();
        // === INSERT INTO cases ===
        // Generate unique case_number
        $case_number = 'CASE-' . strtoupper(uniqid());
        $title       = substr($description, 0, 50)
                     . (strlen($description) > 50 ? '…' : '');
        $report_type = 'Incident';

        $stmt = $db->prepare(
            "INSERT INTO cases 
               (case_number, title, report_type, status, date_reported)
             VALUES (?, ?, ?, 'open', ?)"
        );
        $stmt->bind_param(
            'ssss',
            $case_number,
            $title,
            $report_type,
            $incident_datetime  // store the original date/time
        );
        if (!$stmt->execute()) {
            $errors[] = 'Failed to save case: ' . $stmt->error;
        } else {
            $case_id = $stmt->insert_id;
            $stmt->close();
            // === OPTIONAL: store geo, suspect & vehicle in a details table ===
            // Assuming you have a table `case_details`:
            $dstmt = $db->prepare(
                "INSERT INTO case_details
                 (case_id, latitude, longitude, suspect_description, vehicle_info)
                 VALUES (?, ?, ?, ?, ?)"
            );
            if ($dstmt) {
                $dstmt->bind_param(
                    'iddss',
                    $case_id,
                    $latitude,
                    $longitude,
                    $suspect_desc,
                    $vehicle_info
                );
                $dstmt->execute();
                $dstmt->close();
            }
            $success = 'Report submitted successfully!';
        }
        $db->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Report a Crime | Police NSW CMS</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap 5 -->
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    rel="stylesheet"
  >
  <!-- Font Awesome -->
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
  <!-- Leaflet CSS -->
  <link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css"
  />
  <style>
    body { background: #f4f6f9; font-family: Arial, sans-serif; }
    .wizard-step { display: none; }
    .wizard-step.active { display: block; }
    #map { height: 250px; border-radius: .5rem; }
    .progress { height: 1.5rem; }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="index.php">
      <i class="fas fa-shield-alt"></i> Police NSW CMS
    </a>
  </div>
</nav>

<div class="container my-5">
  <h2 class="mb-4"><i class="fas fa-exclamation-triangle text-danger"></i>
    Report a Crime
  </h2>

  <?php if ($errors): ?>
    <div class="alert alert-danger">
      <ul class="mb-0">
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <?php if ($success): ?>
    <div class="alert alert-success">
      <?= htmlspecialchars($success) ?>
    </div>
  <?php endif; ?>

  <form method="post">
    <div class="progress mb-4">
      <div id="progressBar" class="progress-bar bg-primary" role="progressbar"
           style="width:33%;">Step 1 of 3</div>
    </div>

    <!-- STEP 1 -->
    <div id="step1" class="wizard-step active">
      <h4>Step 1: Incident Details</h4>
      <div class="mb-3">
        <label class="form-label">
          <i class="fas fa-calendar-alt"></i> Date & Time
        </label>
        <input type="datetime-local" name="incident_datetime"
               class="form-control"
               value="<?= htmlspecialchars($_POST['incident_datetime'] ?? '') ?>"
               required>
      </div>
      <div class="mb-3">
        <label class="form-label">
          <i class="fas fa-map-marker-alt"></i> Location
        </label>
        <div id="map">Loading map…</div>
        <input type="hidden" name="latitude"  id="latitude">
        <input type="hidden" name="longitude" id="longitude">
      </div>
      <div class="mb-3">
        <label class="form-label">
          <i class="fas fa-align-left"></i> Description
        </label>
        <textarea name="description" class="form-control" rows="4"
          placeholder="Describe what happened" required><?= 
            htmlspecialchars($_POST['description'] ?? '') 
        ?></textarea>
      </div>
      <button type="button" class="btn btn-primary" id="next1">Next</button>
    </div>

    <!-- STEP 2 -->
    <div id="step2" class="wizard-step">
      <h4>Step 2: Suspect & Vehicle</h4>
      <div class="mb-3">
        <label class="form-label">
          <i class="fas fa-user-secret"></i> Suspect Description
        </label>
        <input type="text" name="suspect_description" class="form-control"
               placeholder="Height, clothing, features"
               value="<?= htmlspecialchars($_POST['suspect_description'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">
          <i class="fas fa-car"></i> Vehicle Info
        </label>
        <input type="text" name="vehicle_info" class="form-control"
               placeholder="Make, model, plate"
               value="<?= htmlspecialchars($_POST['vehicle_info'] ?? '') ?>">
      </div>
      <button type="button" class="btn btn-secondary me-2" id="prev2">
        Back
      </button>
      <button type="button" class="btn btn-primary" id="next2">
        Next
      </button>
    </div>

    <!-- STEP 3 -->
    <div id="step3" class="wizard-step">
      <h4>Step 3: Review & Submit</h4>
      <p class="text-muted">Click “Submit Report” when you’re ready.</p>
      <button type="button" class="btn btn-secondary me-2" id="prev3">
        Back
      </button>
      <button type="submit" class="btn btn-success">
        Submit Report
      </button>
    </div>
  </form>
</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
<!-- Bootstrap Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Wizard navigation
  document.getElementById('next1').onclick = () => showStep(2, 66);
  document.getElementById('prev2').onclick = () => showStep(1, 33);
  document.getElementById('next2').onclick = () => showStep(3, 100);
  document.getElementById('prev3').onclick = () => showStep(2, 66);
  function showStep(n, pct) {
    document.querySelectorAll('.wizard-step').forEach(s => s.classList.remove('active'));
    document.getElementById('step'+n).classList.add('active');
    document.getElementById('progressBar').style.width = pct+'%';
    document.getElementById('progressBar').textContent = 'Step '+n+' of 3';
  }

  // Leaflet map
  var map = L.map('map').setView([-33.8688,151.2093],12);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap'
  }).addTo(map);
  var marker;
  map.on('click', e => {
    var {lat,lng} = e.latlng;
    lat = lat.toFixed(6); lng = lng.toFixed(6);
    document.getElementById('latitude').value = lat;
    document.getElementById('longitude').value = lng;
    if (marker) marker.setLatLng(e.latlng);
    else marker = L.marker(e.latlng).addTo(map);
  });
</script>
</body>
</html>
