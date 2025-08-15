<?php
$conn = new mysqli('localhost', 'root', '', 'parking_system');
require_once "config_secure.php"; // ✅ Import encryption/decryption functions

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['exit']) && is_numeric($_GET['exit'])) {
    $vehicle_id = intval($_GET['exit']);
    $exit_time = date('Y-m-d H:i:s');

    $result = $conn->query("SELECT entry_time FROM vehicles WHERE vehicle_id = $vehicle_id");
    if ($result && $row = $result->fetch_assoc()) {
        $entry_time = $row['entry_time'];
        $duration_sec = strtotime($exit_time) - strtotime($entry_time);
        $duration_hrs = ceil($duration_sec / 3600);
        $charges = $duration_hrs * 10;

        $update = "UPDATE vehicles 
                   SET exit_time='$exit_time', duration=$duration_hrs, charges=$charges, status='Exited' 
                   WHERE vehicle_id=$vehicle_id";

        if (!$conn->query($update)) {
            die("Error updating vehicle record: " . $conn->error);
        }
    } else {
        die("Error fetching entry time: " . $conn->error);
    }
}

$vehicles = $conn->query("SELECT * FROM vehicles WHERE status='Exited' ORDER BY exit_time DESC");
if (!$vehicles) {
    die("Error fetching vehicles: " . $conn->error);
}

$totalIncome = 0;
$result = $conn->query("SELECT SUM(charges) AS total FROM vehicles WHERE status = 'Exited'");
if ($result && $row = $result->fetch_assoc()) {
    $totalIncome = $row['total'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Exited Vehicles History</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    :root {
      --sidebar-blue: #1e3a8a;
      --primary-blue: #3b82f6;
      --card-bg: #f1f5f9;
      --text-black: #000000;
      --white: #ffffff;
      --success-green: #16a34a;
      --warning-yellow: #fde68a;
      --danger-red: #f87171;
      --gray-blue: #94a3b8;
    }

    body {
      background-color: var(--sidebar-blue);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      color: var(--text-black);
      margin: 0;
      padding: 30px 15px;
    }

    h2 {
      font-weight: 700;
      color: var(--white);
      text-align: center;
      margin-bottom: 30px;
      animation: fadeInDown 0.8s ease-out;
    }

    .card.bg-success {
      background-color: var(--primary-blue) !important;
      border: none;
      animation: fadeSlide 0.9s ease;
    }

    .card.bg-success h2 {
      font-size: 2rem;
      margin: 0;
    }

    .card h5 {
      font-weight: 500;
    }

    .table-responsive {
      background-color: var(--card-bg);
      border-radius: 16px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
      padding: 20px;
      animation: fadeSlide 1s ease-in-out;
    }

    table.table {
      font-size: 0.95rem;
    }

    thead {
      background-color: var(--primary-blue);
      color: var(--white);
    }

    .table tbody tr:hover {
      background-color: #e2e8f0;
    }

    .badge.bg-secondary {
      background-color: var(--gray-blue);
      color: var(--white);
      font-size: 0.85rem;
      padding: 5px 10px;
      border-radius: 10px;
    }

    .btn-secondary {
      background-color: var(--primary-blue);
      border: none;
      color: var(--white);
      font-weight: 600;
      padding: 10px 20px;
      border-radius: 8px;
      transition: background-color 0.3s ease, transform 0.3s ease;
    }

    .btn-secondary:hover {
      background-color: var(--sidebar-blue);
      transform: translateY(-2px);
    }

    @keyframes fadeSlide {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes fadeInDown {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @media (max-width: 575.98px) {
      h2 {
        font-size: 1.7rem;
      }

      .card h5,
      .card h2 {
        font-size: 1.1rem;
      }

      table.table {
        font-size: 0.85rem;
      }
    }
  </style>
</head>
<body>

  <div class="container">
    <h2>Exited Vehicles History</h2>

    <div class="row mb-4">
      <div class="col-md-6 offset-md-3">
        <div class="card text-white bg-success shadow text-center">
          <div class="card-body">
            <h5 class="card-title">Total Income from Exited Vehicles</h5>
            <h2 class="card-text">₹<?= number_format($totalIncome, 2) ?></h2>
          </div>
        </div>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-bordered table-hover text-center align-middle">
        <thead>
          <tr>
            <th>Vehicle No</th>
            <th>Owner Name</th>
            <th>Entry Time</th>
            <th>Exit Time</th>
            <th>Duration (hrs)</th>
            <th>Charges (₹)</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $vehicles->fetch_assoc()) { ?>
            <tr>
              <td><?= htmlspecialchars(decryptVehicleNo($row['vehicle_no'])) ?></td>
              <td><?= "Confidential" // owner_name is hashed, so we don’t decrypt ?></td>
              <td><?= date('d M Y, H:i', strtotime($row['entry_time'])) ?></td>
              <td><?= date('d M Y, H:i', strtotime($row['exit_time'])) ?></td>
              <td><?= $row['duration'] ?></td>
              <td>₹<?= $row['charges'] ?></td>
              <td><span class="badge bg-secondary">Exited</span></td>
            </tr>
          <?php } ?>
        </tbody>
      </table>

      <div class="text-end mt-4">
        <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
      </div>
    </div>
  </div>

</body>
</html>
