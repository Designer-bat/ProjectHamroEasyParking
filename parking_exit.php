<?php
$conn = new mysqli('localhost', 'root', '', 'parking_system');
require_once "config_secure.php"; // encryption/decryption functions

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle vehicle exit
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

// Fetch exited vehicles
$vehicles = $conn->query("SELECT * FROM vehicles WHERE status='Exited' ORDER BY exit_time DESC");
if (!$vehicles) die("Error fetching vehicles: " . $conn->error);

// Total income
$totalIncome = 0;
$result = $conn->query("SELECT SUM(charges) AS total FROM vehicles WHERE status = 'Exited'");
if ($result && $row = $result->fetch_assoc()) {
    $totalIncome = $row['total'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Exited Vehicles History</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
  --primary-blue: #000000ff;
  --secondary-blue: #3498db;
  --accent-blue: #1a6ca6;
  --white: #ffffff;
  --black: #212529;
  --light-gray: #f8f9fa;
  --medium-gray: #e9ecef;
  --dark-gray: #6c757d;
  --success: #28a745;
  --error: #dc3545;
  --transition: all 0.3s ease;
}

* { margin:0; padding:0; box-sizing:border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

body {
  background-color: #1e3a8a;
  color: var(--black);
  min-height: 100vh;
  padding: 20px;
}

.container { max-width: 1200px; margin: 0 auto; }

h2 {
  font-weight: 700;
  color: var(--primary-blue);
  text-align: center;
  margin-bottom: 30px;
  animation: fadeInDown 0.8s ease-out;
}

.card {
  background: rgba(255,255,255,0.9);
  backdrop-filter: blur(10px);
  border-radius: 15px;
  padding: 25px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.1);
  margin-bottom: 30px;
  transition: var(--transition);
}

.card:hover { transform: translateY(-3px); }

.card h5 { font-weight: 500; color: var(--primary-blue); }

.table-container {
  overflow-x: auto;
  border-radius: 15px;
  backdrop-filter: blur(10px);
  background: rgba(255,255,255,0.85);
  box-shadow: 0 10px 25px rgba(0,0,0,0.08);
  padding: 20px;
  animation: fadeSlide 1s ease-in-out;
}

table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.95rem;
}

thead { background: var(--primary-blue); color: var(--white); }

th, td {
  padding: 14px 18px;
  text-align: center;
  border-bottom: 1px solid var(--medium-gray);
}

tbody tr {
  transition: var(--transition);
}

tbody tr:hover {
  background-color: rgba(52, 152, 219, 0.08);
  transform: scale(1.01);
}

.status-badge {
  padding: 6px 14px;
  border-radius: 20px;
  font-weight: 500;
  font-size: 0.85rem;
  display: inline-block;
  transition: var(--transition);
}

.status-exited { background-color: rgba(220,53,69,0.15); color: var(--error); }

.btn-back {
  background: var(--primary-blue);
  color: var(--white);
  padding: 12px 25px;
  border-radius: 8px;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
  gap: 10px;
  margin-top: 20px;
  transition: var(--transition);
}

.btn-back:hover {
  background: var(--accent-blue);
  transform: translateY(-2px);
}

@keyframes fadeSlide { from {opacity:0; transform: translateY(20px);} to {opacity:1; transform: translateY(0);} }
@keyframes fadeInDown { from {opacity:0; transform: translateY(-20px);} to {opacity:1; transform: translateY(0);} }

@media (max-width: 768px) {
  h2 { font-size: 1.7rem; }
  th, td { padding: 10px 12px; }
}
</style>
</head>
<body>

<div class="container">
  <h2>Exited Vehicles History</h2>

  <div class="row mb-4">
    <div class="col-md-6 offset-md-3">
      <div class="card text-center">
        <div class="card-body">
          <h5 class="card-title">Total Income from Exited Vehicles</h5>
          <h2 class="card-text">₹<?= number_format($totalIncome, 2) ?></h2>
        </div>
      </div>
    </div>
  </div>

  <div class="table-container">
    <table class="table table-hover">
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
            <td><?= "Confidential" ?></td>
            <td><?= date('d M Y, H:i', strtotime($row['entry_time'])) ?></td>
            <td><?= date('d M Y, H:i', strtotime($row['exit_time'])) ?></td>
            <td><?= $row['duration'] ?></td>
            <td>₹<?= $row['charges'] ?></td>
            <td><span class="status-badge status-exited">Exited</span></td>
          </tr>
        <?php } ?>
      </tbody>
    </table>

    <div class="text-end">
      <a href="index.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>
  </div>
</div>

</body>
</html>
