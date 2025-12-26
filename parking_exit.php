<?php
include "Aiindex.php";
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
  --primary: #2563eb;
  --secondary: #1e40af;
  --success: #10b981;
  --warning: #f59e0b;
  --danger: #ef4444;
  --light: #f8fafc;
  --dark: #1e293b;
  --gray: #64748b;
  --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
   --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  --white: #ffffff;
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

body {
  background: linear-gradient(120deg, var(--light-gray) 0%, var(--medium-gray) 100%);
  color: var(--black);
  min-height: 100vh;
  padding: 24px;
  animation: fadeBg 1.2s;
}

.container {
  max-width: 1100px;
  margin: 0 auto;
}

h2 {
  font-weight: 700;
  color: var(--primary-blue);
  text-align: center;
  margin-bottom: 32px;
  letter-spacing: 1px;
  animation: fadeInDown 0.8s;
}

.card {
  background: rgba(255,255,255,0.95);
  border-radius: 18px;
  padding: 28px 24px;
  box-shadow: var(--shadow);
  margin-bottom: 32px;
  transition: var(--transition);
  animation: fadeSlide 1s;
}

.card:hover {
  transform: translateY(-4px) scale(1.01);
  box-shadow: 0 16px 40px rgba(26,108,166,0.13);
}

.card h5 {
  font-weight: 600;
  color: var(--accent-blue);
  margin-bottom: 10px;
}

.card-text {
  font-size: 2.1rem;
  color: var(--success);
  font-weight: 700;
  letter-spacing: 1px;
  animation: pulse 1.2s infinite alternate;
}

.table-container {
  overflow-x: auto;
  border-radius: 16px;
  background: rgba(255,255,255,0.92);
  box-shadow: var(--shadow);
  padding: 22px 18px;
  animation: fadeSlide 1.2s;
}

table {
  width: 100%;
  border-collapse: collapse;
  font-size: 1rem;
  background: transparent;
}

thead {
  background: linear-gradient(90deg, var(--primary-blue) 60%, var(--secondary-blue) 100%);
  color: var(--white);
  animation: fadeInDown 1s;
}

th, td {
  padding: 15px 20px;
  text-align: center;
  border-bottom: 1px solid var(--medium-gray);
  vertical-align: middle;
}

tbody tr {
  transition: var(--transition);
  animation: rowFadeIn 0.7s;
}

tbody tr:hover {
  background-color: rgba(26,108,166,0.07);
  transform: scale(1.01);
  box-shadow: 0 2px 8px rgba(26,108,166,0.07);
}

.status-badge {
  padding: 7px 18px;
  border-radius: 22px;
  font-weight: 600;
  font-size: 0.92rem;
  display: inline-block;
  transition: var(--transition);
  box-shadow: 0 2px 8px rgba(220,53,69,0.08);
}

.status-exited {
  background: linear-gradient(90deg, rgba(220,53,69,0.18) 60%, rgba(220,53,69,0.10) 100%);
  color: var(--error);
  letter-spacing: 0.5px;
}

.btn-back {
  background: var(--primary-blue);
  color: var(--white);
  padding: 13px 28px;
  border-radius: 9px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 12px;
  margin-top: 24px;
  border: none;
  box-shadow: 0 2px 8px rgba(26,108,166,0.10);
  transition: var(--transition);
  text-decoration: none;
  font-size: 1rem;
}

.btn-back:hover {
  background: var(--accent-blue);
  transform: translateY(-2px) scale(1.03);
  box-shadow: 0 6px 18px rgba(26,108,166,0.18);
}

@keyframes fadeSlide {
  from { opacity: 0; transform: translateY(30px);}
  to { opacity: 1; transform: translateY(0);}
}
@keyframes fadeInDown {
  from { opacity: 0; transform: translateY(-20px);}
  to { opacity: 1; transform: translateY(0);}
}
@keyframes rowFadeIn {
  from { opacity: 0; transform: translateY(10px);}
  to { opacity: 1; transform: translateY(0);}
}
@keyframes fadeBg {
  from { background: var(--white); }
  to { background: linear-gradient(120deg, var(--light-gray) 0%, var(--medium-gray) 100%);}
}
@keyframes pulse {
  0% { color: var(--success); text-shadow: 0 0 0px var(--success);}
  100% { color: #34c759; text-shadow: 0 0 8px #34c759;}
}

@media (max-width: 900px) {
  .container { max-width: 98vw; }
  th, td { padding: 10px 8px; font-size: 0.95rem; }
  .card { padding: 18px 10px; }
  .table-container { padding: 12px 4px; }
}

@media (max-width: 600px) {
  h2 { font-size: 1.3rem; }
  .card-text { font-size: 1.3rem; }
  th, td { padding: 7px 4px; font-size: 0.85rem; }
  .btn-back { padding: 10px 16px; font-size: 0.95rem; }
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
