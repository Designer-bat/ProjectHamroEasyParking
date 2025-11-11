<?php
include ("index.html");
session_start();
$conn = new mysqli('localhost', 'root', '', 'parking_system');
include 'config_secure.php'; // encryption/decryption functions

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle vehicle exit
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
        $conn->query($update);

        $conn->query("UPDATE parking_slots SET status='Available' WHERE slot_id = $slot_id");

        // Trigger voice alert
        echo "<script>window.onload = () => { 
                let audio = new Audio('https://actions.google.com/sounds/v1/alarms/beep_short.ogg'); 
                audio.play(); 
              }</script>";
    }
}

// Fetch all vehicle history
$vehicles = $conn->query("SELECT * FROM vehicles ORDER BY entry_time DESC");
if (!$vehicles) {
    die("Error fetching vehicles: " . $conn->error);
}

// Mask owner name
function maskOwnerName($hashed) {
    return "Private Owner";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Parking History</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
  --primary: #2c3e50;
  --secondary: #3498db;
  --success: #28a745;
  --danger: #dc3545;
  --light: #f8f9fa;
  --dark: #212529;
  --bg: #1e3a8a;
  --text: #212529;
}

body.dark {
  --bg: #121212;
  --text: #e0e0e0;
  --primary: #90caf9;
  --secondary: #42a5f5;
}

body {
  background: var(--body-bg);  
  color: var(--text);
  font-family: 'Segoe UI', sans-serif;
  padding: 30px;
  transition: background 0.3s, color 0.3s;
}

.card {
  background: var(--light);
  border-radius: 15px;
  padding: 25px;
  box-shadow: 0 6px 16px rgba(0,0,0,0.15);
  margin: 0 auto;
  max-width: 1100px;
}

.card h2 {
  color: var(--primary);
  margin-bottom: 20px;
  font-size: 1.6rem;
  text-align: center;
  border-bottom: 2px solid var(--secondary);
  padding-bottom: 10px;
}

.table-container {
  overflow-x: auto;
  border-radius: 12px;
  overflow: hidden;
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
}

tbody tr:nth-child(even) {
  background: #f9f9f9;
}

tbody tr:hover {
  background: rgba(0, 0, 0, 0.05);
}

.status-badge {
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 500;
}

.in-lot {
  background: rgba(40,167,69,0.1);
  color: var(--success);
}

.exited {
  background: rgba(108,117,125,0.15);
  color: gray;
}

.overstay {
  background: rgba(220,53,69,0.08);
}

.btn-exit {
  background: var(--danger);
  color: white;
  padding: 6px 12px;
  border-radius: 6px;
  text-decoration: none;
  font-weight: 500;
  transition: background 0.3s ease;
}

.btn-exit:hover {
  background: #b52a3a;
}

.toggle-dark {
  position: fixed;
  top: 20px;
  right: 20px;
  background: var(--secondary);
  color: white;
  border: none;
  padding: 10px;
  border-radius: 50%;
  cursor: pointer;
  font-size: 1.2rem;
  transition: background 0.3s ease;
}

.toggle-dark:hover {
  background: var(--primary);
}

.btn-back {
  display: inline-block;
  margin-top: 25px;
  background: var(--primary);
  color: white;
  padding: 10px 20px;
  border-radius: 8px;
  text-decoration: none;
  font-weight: 500;
  transition: background 0.3s ease;
}

.btn-back:hover {
  background: var(--secondary);
}
</style>
</head>
<body>

<button class="toggle-dark" onclick="toggleDarkMode()"><i class="fas fa-moon"></i></button>

<div class="card">
  <h2><i class="card-title"></i> Parking Records and History</h2>
  <div class="table-container">
    <table>
      <thead>
        <tr>
          <th style="width: 13%;">Vehicle No</th>
          <th style="width: 15%;">Owner</th>
          <th style="width: 18%;">Entry</th>
          <th style="width: 18%;">Exit</th>
          <th style="width: 10%;">Duration (hrs)</th>
          <th style="width: 10%;">Charges (₹)</th>
          <th style="width: 10%;">Status</th>
          <th style="width: 6%;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($vehicles->num_rows > 0): ?>
          <?php while($row = $vehicles->fetch_assoc()): 
            $overstay = ($row['duration'] !== null && $row['duration'] > 3); // overstay rule
          ?>
          <tr class="<?= $overstay ? 'overstay' : '' ?>">
            <td><?= htmlspecialchars(decryptVehicleNo($row['vehicle_no'])) ?></td>
            <td><?= maskOwnerName($row['owner_name']) ?></td>
            <td><?= date('M d, Y H:i', strtotime($row['entry_time'])) ?></td>
            <td><?= $row['exit_time'] ? date('M d, Y H:i', strtotime($row['exit_time'])) : '-' ?></td>
            <td><?= $row['duration'] ?? '-' ?></td>
            <td><?= $row['charges'] ? '₹'.$row['charges'] : '-' ?></td>
            <td>
              <?php if ($row['status'] === 'In Lot'): ?>
                <span class="status-badge in-lot">In Lot</span>
              <?php else: ?>
                <span class="status-badge exited">Exited</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($row['status'] === 'In Lot'): ?>
                <a href="?exit=<?= $row['vehicle_id'] ?>" class="btn-exit" onclick="return confirm('Mark as exited?')">Exit</a>
              <?php else: ?> - <?php endif; ?>
            </td>
          </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="8" style="text-align:center; padding:20px;">No Records Found</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <!-- Back to Dashboard Button -->
<div style="text-align:center;">
  <a href="index.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>
</div>
</body>
</html>
