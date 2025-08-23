<?php
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
  --bg: #ffffff;
  --text: #212529;
}
body.dark {
  --bg: #121212;
  --text: #e0e0e0;
  --primary: #90caf9;
  --secondary: #42a5f5;
}
body {
  background: var(--bg);
  color: var(--text);
  font-family: 'Segoe UI', sans-serif;
  padding: 20px;
  transition: background 0.3s, color 0.3s;
}
.card { background: var(--bg); border-radius: 10px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 12px 15px; border-bottom: 1px solid #ddd; }
thead { background: var(--primary); color: white; }
tbody tr:hover { background: rgba(0,0,0,0.05); }
.status-badge { padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; }
.in-lot { background: rgba(40,167,69,0.15); color: var(--success); }
.exited { background: rgba(108,117,125,0.15); color: gray; }
.overstay { background: rgba(220,53,69,0.2) !important; }
.btn-exit { background: var(--danger); color: white; padding: 6px 12px; border-radius: 5px; text-decoration: none; }
.btn-exit:hover { background: #b52a3a; }
.toggle-dark {
  position: fixed; top: 20px; right: 20px;
  background: var(--secondary); color: white;
  border: none; padding: 10px; border-radius: 50%;
  cursor: pointer; font-size: 1.2rem;
}
.btn-back {
  display: inline-block;
  margin-top: 20px;
  background: var(--primary);
  color: white;
  padding: 10px 20px;
  border-radius: 6px;
  text-decoration: none;
  font-weight: 500;
  transition: 0.3s;
}
.btn-back:hover {
  background: var(--secondary);
}
</style>
</head>
<body>

<button class="toggle-dark" onclick="toggleDarkMode()"><i class="fas fa-moon"></i></button>

<div class="card">
  <h2><i class="fas fa-history"></i> Parking History</h2>
  <div class="table-container">
    <table>
      <thead>
        <tr>
          <th>Vehicle No</th>
          <th>Owner</th>
          <th>Entry</th>
          <th>Exit</th>
          <th>Duration (hrs)</th>
          <th>Charges (₹)</th>
          <th>Status</th>
          <th>Action</th>
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
</div>

<!-- Back to Dashboard Button -->
<a href="index.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>

<script>
// Dark/Light mode toggle
function toggleDarkMode() {
  document.body.classList.toggle("dark");
  localStorage.setItem("darkMode", document.body.classList.contains("dark"));
}
if (localStorage.getItem("darkMode") === "true") {
  document.body.classList.add("dark");
}
</script>

</body>
</html>
