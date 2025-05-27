<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'police_officer') {
    header('Location: login.php');
    exit;
}
$username = htmlspecialchars($_SESSION['username']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Officer Portal</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body {
      background-color: #f8f9fa;
      color: #212529;
    }
    .navbar {
      background-color: #ffffff;
      border-bottom: 1px solid #dee2e6;
    }
    .sidebar {
      background-color: #f8f9fa;
      min-width: 220px;
      max-width: 220px;
    }
    .sidebar .nav-link {
      color: #495057;
    }
    .sidebar .nav-link.active {
      font-weight: 600;
      color: #0d6efd;
    }
    .card {
      background-color: #ffffff;
      border: 1px solid #dee2e6;
    }
    .card-icon {
      font-size: 2rem;
      color: #0d6efd;
    }
    .list-group-item {
      background-color: #ffffff;
      border: 1px solid #dee2e6;
    }
    .list-group-item + .list-group-item {
      border-top: none;
    }
    .list-group-item a {
      color: #0d6efd;
      text-decoration: none;
    }
    .list-group-item a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <!-- Top Navbar -->
  <nav class="navbar navbar-expand-lg navbar-light">
    <div class="container-fluid">
      <a class="navbar-brand" href="#">Officer Portal</a>
      <div class="d-flex align-items-center">
        <span class="me-3"><i class="fa-solid fa-user-shield"></i> Officer <?= $username ?></span>
        <a href="logout.php" class="btn btn-outline-secondary btn-sm">
          <i class="fa-solid fa-right-from-bracket"></i> Log Out
        </a>
      </div>
    </div>
  </nav>

  <div class="container-fluid">
    <div class="row">
      <!-- Sidebar -->
      <nav class="col-md-2 d-none d-md-block sidebar py-3">
        <div class="position-sticky">
          <ul class="nav flex-column">
            <li class="nav-item mb-2">
              <a class="nav-link active" href="#dashboard">
                <i class="fa-solid fa-gauge-high me-2"></i>Dashboard
              </a>
            </li>
            <li class="nav-item mb-2">
              <a class="nav-link" href="#incidentMgmt">
                <i class="fa-solid fa-file-police me-2"></i>Incident & Case Mgmt
              </a>
            </li>
            <li class="nav-item mb-2">
              <a class="nav-link" href="#lookupTools">
                <i class="fa-solid fa-magnifying-glass me-2"></i>Lookup Tools
              </a>
            </li>
            <li class="nav-item mb-2">
              <a class="nav-link" href="#alertsGallery">
                <i class="fa-solid fa-bell-exclamation me-2"></i>Alerts & Missing
              </a>
            </li>
            <li class="nav-item mb-2">
              <a class="nav-link" href="#trafficCitations">
                <i class="fa-solid fa-car-side me-2"></i>Traffic & Citations
              </a>
            </li>
            <li class="nav-item mb-2">
              <a class="nav-link" href="#mappingAVL">
                <i class="fa-solid fa-map-location-dot me-2"></i>Mapping & AVL
              </a>
            </li>
            <li class="nav-item mb-2">
              <a class="nav-link" href="#commReports">
                <i class="fa-solid fa-comments me-2"></i>Communication & Reports
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#adminProfile">
                <i class="fa-solid fa-user-gear me-2"></i>Admin & Profile
              </a>
            </li>
          </ul>
        </div>
      </nav>

      <!-- Main Content -->
      <main class="col-md-10 ms-sm-auto col-lg-10 px-md-4 py-4">
        <!-- Dashboard -->
        <section id="dashboard">
          <h2 class="mb-4"><i class="fa-solid fa-gauge-high me-2"></i>Dashboard</h2>
          <div class="row g-4">
            <div class="col-md-4">
              <div class="card shadow-sm">
                <div class="card-body d-flex align-items-center">
                  <i class="fa-solid fa-clock card-icon me-3"></i>
                  <div>
                    <h5 class="card-title mb-1">Shift Info</h5>
                    <p class="card-text small">Today: 0800–1700 hrs</p>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              
                                  
                                      </div>
            <div class="col-md-4">
              <div class="card shadow-sm">
                <div class="card-body d-flex align-items-center">
                  <i class="fa-solid fa-bolt card-icon text-warning me-3"></i>
                  <div>
                    <h5 class="card-title mb-1">Quick Actions</h5>
                    <p class="card-text small">
                      <a href="report.php">File Report</a> ·
                      <a href="upload.php">Upload Evidence</a>
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <hr>

        <!-- Incident & Case Management -->
        <section id="incidentMgmt" class="mt-4">
          <h3><i class="fa-solid fa-file-police me-2"></i>Incident & Case Management</h3>
          <ul class="list-group list-group-flush mb-4">
            <li class="list-group-item">
              <i class="fa-solid fa-plus me-2"></i><a href="report.php">File New Report</a>
            </li>
            <li class="list-group-item">
              <i class="fa-solid fa-tasks me-2"></i><a href="view.php">Track Case Status</a>
            </li>
            <li class="list-group-item">
              <i class="fa-solid fa-folder-open me-2"></i><a href="upload.php">Upload Evidence</a>
            </li>
          </ul>
        </section>

        <!-- Lookup Tools -->
        <section id="lookupTools" class="mt-4">
          <h3><i class="fa-solid fa-magnifying-glass me-2"></i>Lookup Tools</h3>
          <ul class="list-group list-group-flush mb-4">
            <li class="list-group-item">
              <i class="fa-solid fa-user me-2"></i><a href="search_people.php">Search People</a>
            </li>
            <li class="list-group-item">
              <i class="fa-solid fa-car me-2"></i>
              <a href="https://www.service.nsw.gov.au/transaction/check-vehicle-registration" target="_blank" rel="noopener">
                Search Vehicles (Service NSW)
              </a>
            </li>
            <li class="list-group-item">
              <i class="fa-solid fa-gavel me-2"></i><a href="missing.php">Missing Person</a>
            </li>
                     </ul>
        </section>

        
        <!-- Traffic & Citations -->
        <section id="trafficCitations" class="mt-4">
          <h3><i class="fa-solid fa-car-side me-2"></i>Traffic & Citations</h3>
          <ul class="list-group list-group-flush mb-4">
            <li class="list-group-item">
              <i class="fa-solid fa-plate-wai me-2"></i><a href="https://www.service.nsw.gov.au/transaction/check-vehicle-registration">Plate Checks</a>
            </li>
                        <li class="list-group-item">
              <i class="fa-solid fa-truck-moving me-2"></i><a href="pay.php">Tow Requests</a>
            </li>
          </ul>
        </section>

        <!-- Mapping & AVL -->
        <section id="mappingAVL" class="mt-4">
          <h3><i class="fa-solid fa-map-location-dot me-2"></i>Mapping & AVL</h3>
          <ul class="list-group list-group-flush mb-4">
            <li class="list-group-item">
              <i class="fa-solid fa-location-dot me-2"></i><a href="https://anytrip.com.au/region/nsw">Live Unit Locations</a>
            </li>
            <li class="list-group-item">
              <i class="fa-solid fa-fire-flame-curved me-2"></i><a href="https://redsuburbs.com.au/?lat=-24.4871&lng=138.8232&zoom=4">Crime Heatmaps</a>
            </li>
            <li class="list-group-item">
              <i class="fa-solid fa-route me-2"></i><a href="https://www.google.com.au/maps">Route Planning</a>
            </li>
          </ul>
        </section>

        <!-- Communication & Reports -->
        <section id="commReports" class="mt-4">
          <h3><i class="fa-solid fa-comments me-2"></i>Communication & Reports</h3>
          <ul class="list-group list-group-flush mb-4">
            <li class="list-group-item">
              <i class="fa-solid fa-envelope me-2"></i><a href="https://www.service.nsw.gov.au/nswgovdirectory/police-force-nsw">Secure Messaging</a>
            </li>
                        <li class="list-group-item">
              <i class="fa-solid fa-file-export me-2"></i><a href="stats_exports.php">Stats Exports</a>
            </li>
          </ul>
        </section>

        <!-- Admin & Profile -->
        <section id="adminProfile" class="mt-4">
          <h3><i class="fa-solid fa-user-gear me-2"></i>Admin & Profile</h3>
          <ul class="list-group list-group-flush mb-4">
            <li class="list-group-item">
              <i class="fa-solid fa-calendar-days me-2"></i><a href="shift.php">Shift Scheduling</a>
            </li>
            <li class="list-group-item">
              <i class="fa-solid fa-book me-2"></i><a href="https://copstrainingportal.org/">Training Resources</a>
            </li>
            
          </ul>
        </section>
      </main>
    </div>
  </div>

  <!-- Bootstrap JS & dependencies -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
