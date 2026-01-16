<?php
include ("Aiindex.php");
$conn = new mysqli('localhost', 'root', '', 'parking_system');
include 'config_secure.php'; // 🔐 encryption/decryption functions

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle vehicle exit (optional)
if (isset($_GET['exit']) && is_numeric($_GET['exit'])) {
    $vehicle_id = intval($_GET['exit']);
    $exit_time = date('Y-m-d H:i:s');

    $result = $conn->query("SELECT entry_time, slot_id FROM vehicles WHERE vehicle_id = $vehicle_id");
    if ($result && $row = $result->fetch_assoc()) {
        $entry_time = $row['entry_time'];
        $slot_id = $row['slot_id'];

        $duration_sec = strtotime($exit_time) - strtotime($entry_time);
        $duration_hrs = ceil($duration_sec / 3600);
        $charges = $duration_hrs * 10;

        $update = "UPDATE vehicles 
                   SET exit_time='$exit_time', duration=$duration_hrs, charges=$charges, status='Exited' 
                   WHERE vehicle_id=$vehicle_id";

        if (!$conn->query($update)) {
            die("Error updating vehicle record: " . $conn->error);
        }

        $conn->query("UPDATE parking_slots SET status='Available' WHERE slot_id = $slot_id");
    } else {
        die("Error fetching vehicle info: " . $conn->error);
    }
}

// Handle receipt generation
if (isset($_GET['receipt']) && is_numeric($_GET['receipt'])) {
    $vehicle_id = intval($_GET['receipt']);
    $result = $conn->query("SELECT * FROM vehicles WHERE vehicle_id = $vehicle_id");
    if ($result && $row = $result->fetch_assoc()) {
        $vehicle_no = decryptVehicleNo($row['vehicle_no']);
        $owner_name = decryptOwnerName($row['owner_name']); // make sure this function exists in config_secure.php
        $entry_time = date('M d, Y H:i', strtotime($row['entry_time']));
        $exit_time = $row['exit_time'] ? date('M d, Y H:i', strtotime($row['exit_time'])) : '-';
        $duration = $row['duration'] ?? '-';
        $charges = $row['charges'] ? '₹' . $row['charges'] : '-';

        echo "<!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Parking Receipt</title>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
                .receipt { max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
                h2 { text-align: center; margin-bottom: 20px; color: #2c3e50; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                td { padding: 10px 5px; }
                td.label { font-weight: bold; color: #34495e; width: 40%; }
                .print-btn { background: #3498db; color: #fff; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
                .print-btn:hover { background: #1a6ca6; }
            </style>
        </head>
        <body>
            <div class='receipt'>
                <h2>Parking Receipt</h2>
                <table>
                    <tr><td class='label'>Vehicle No:</td><td>$vehicle_no</td></tr>
                    <tr><td class='label'>Owner Name:</td><td>$owner_name</td></tr>
                    <tr><td class='label'>Entry Time:</td><td>$entry_time</td></tr>
                    <tr><td class='label'>Exit Time:</td><td>$exit_time</td></tr>
                    <tr><td class='label'>Duration:</td><td>$duration hrs</td></tr>
                    <tr><td class='label'>Charges:</td><td>$charges</td></tr>
                </table>
                <button class='print-btn' onclick='window.print()'>Print Receipt</button>
            </div>
        </body>
        </html>";
        exit;
    } else {
        die("Vehicle record not found.");
    }
}

// Fetch all vehicle history
$vehicles = $conn->query("SELECT * FROM vehicles ORDER BY entry_time DESC");
if (!$vehicles) {
    die("Error fetching vehicles: " . $conn->error);
}

// Mask owner name for table display
function maskOwnerName($hashed) {
    return substr($hashed, 0, 6) . "****" . substr($hashed, -4);
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Vehicle Parked Records</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
  --primary: #2563eb;
  --secondary: #3498db;
  --accent: #1a6ca6;
  --light: #ffffff;
  --bg: #1e3a8a;
  --gray: #f1f3f5;
  --text: #212529;
  --success: #28a745;
  --danger: #dc3545;
  --transition: all 0.3s ease;
}

body {
  background: var(--body-bg);
  color: var(--text);
  font-family: 'Segoe UI', sans-serif;
  padding: 30px;
}

.card {
  background: var(--light);
  border-radius: 15px;
  padding: 25px;
  box-shadow: 0 6px 16px rgba(0,0,0,0.15);
  margin: 0 auto;
  max-width: 1100px;
  animation: fadeInCard 0.8s var(--transition);
}

@keyframes fadeInCard {
  from { opacity: 0; transform: translateY(40px);}
  to { opacity: 1; transform: translateY(0);}
}

.card-header {
  display: flex;
  align-items: center;
  gap: 15px;
  margin-bottom: 25px;
  border-bottom: 2px solid var(--gray);
  padding-bottom: 15px;
  animation: slideDownHeader 0.7s var(--transition);
}

@keyframes slideDownHeader {
  from { opacity: 0; transform: translateY(-30px);}
  to { opacity: 1; transform: translateY(0);}
}

.card-header i {
  font-size: 1.8rem;
  color: var(--secondary);
  background: rgba(52,152,219,0.1);
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  animation: popInIcon 0.6s var(--transition);
}

@keyframes popInIcon {
  0% { transform: scale(0.5); opacity: 0;}
  70% { transform: scale(1.1);}
  100% { transform: scale(1); opacity: 1;}
}

.card-title {
  font-size: 1.5rem;
  color: var(--primary);
}

.card-description {
  color: #6c757d;
  font-size: 0.95rem;
}

.table-container {
  overflow-x: auto;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  animation: fadeInTable 0.9s var(--transition);
}

@keyframes fadeInTable {
  from { opacity: 0; transform: scale(0.98);}
  to { opacity: 1; transform: scale(1);}
}

table {
  width: 100%;
  border-collapse: collapse;
  text-align: center;
  font-size: 0.95rem;
}

thead {
  background: var(--primary);
  color: white;
}

th {
  padding: 14px 10px;
  text-transform: uppercase;
  font-size: 0.85rem;
  letter-spacing: 0.5px;
}

td {
  padding: 12px 10px;
  border-bottom: 1px solid #ddd;
  vertical-align: middle;
  transition: background 0.3s;
}

tbody tr:nth-child(even) {
  background: #f9f9f9;
}

tbody tr:hover {
  background: rgba(0,0,0,0.05);
  animation: rowHover 0.3s;
}

@keyframes rowHover {
  from { background: #f9f9f9;}
  to { background: rgba(0,0,0,0.05);}
}

.status-badge {
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 500;
  opacity: 0;
  animation: badgeFadeIn 0.7s forwards;
}

@keyframes badgeFadeIn {
  to { opacity: 1;}
}

.status-in-lot {
  background: rgba(40,167,69,0.1);
  color: var(--success);
  animation-delay: 0.2s;
}

.status-exited {
  background: rgba(108,117,125,0.15);
  color: gray;
  animation-delay: 0.2s;
}

.btn-back {
  background: var(--primary);
  color: white;
  padding: 8px 14px;
  border-radius: 6px;
  text-decoration: none;
  font-weight: 500;
  font-size: 14px;
  transition: background 0.3s ease;
}

.btn-back:hover {
  background: var(--secondary);
}

.empty-state {
  text-align: center;
  padding: 50px 20px;
  color: #6c757d;
}

.empty-state i {
  font-size: 3rem;
  color: #adb5bd;
  margin-bottom: 15px;
}

.empty-state h3 {
  font-size: 1.5rem;
  margin-bottom: 10px;
  color: var(--primary);
}
</style>
</head>
<body>

<div style="text-align:center;" class="card">
  <div class="card-header">
    <i class="fas fa-car"></i>
    <div>
      <h1 class="card-title"> Receipt Status </h1>
      <p class="card-description">All vehicles park Receipt in the facility</p>
    </div>
  </div>

  <div class="table-container">
    <table>
      <thead>
        <tr>
          <th style="width:13%;">Vehicle No</th>
          <th style="width:15%;">Owner</th>
          <th style="width:18%;">Entry</th>
          <th style="width:18%;">Exit</th>
          <th style="width:10%;">Duration (hrs)</th>
          <th style="width:10%;">Charges (₹)</th>
          <th style="width:10%;">Status</th>
          <th style="width:6;">Receipt</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($vehicles->num_rows > 0): ?>
          <?php while($row = $vehicles->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars(decryptVehicleNo($row['vehicle_no'])) ?></td>
            <td><?= htmlspecialchars(maskOwnerName($row['owner_name'])) ?></td>
            <td><?= date('M d, Y H:i', strtotime($row['entry_time'])) ?></td>
            <td><?= $row['exit_time'] ? date('M d, Y H:i', strtotime($row['exit_time'])) : '-' ?></td>
            <td><?= $row['duration'] ?? '-' ?></td>
            <td><?= $row['charges'] ? '₹'.$row['charges'] : '-' ?></td>
            <td>
              <?php if ($row['status'] === 'In Lot'): ?>
                <span class="status-badge status-in-lot"><i class="fas fa-car"></i> In Lot</span>
              <?php else: ?>
                <span class="status-badge status-exited"><i class="fas fa-check-circle"></i> Exited</span>
              <?php endif; ?>
            </td>
            <td>
              <a href="?receipt=<?= $row['vehicle_id'] ?>" class="btn-back"></i> Receipt</a>
            </td>
          </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="8">
              <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>No Parking Records Found</h3>
                <p>There are no vehicles in the parking history yet.</p>
              </div>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
    </div>
  <div style="text-align:center; margin-top:20px;">
  <a href="index.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>
</div>
</body>
</html>