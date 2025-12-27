<?php
include ("Aiindex.php");
$conn = new mysqli('localhost', 'root', '', 'parking_system');
include 'config_secure.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/* ===============================
   HANDLE VEHICLE EXIT
================================ */
if (isset($_GET['exit']) && is_numeric($_GET['exit'])) {
    $vehicle_id = intval($_GET['exit']);
    $exit_time = date('Y-m-d H:i:s');

    $result = $conn->query("SELECT entry_time, slot_id FROM vehicles WHERE vehicle_id = $vehicle_id");
    if ($result && $row = $result->fetch_assoc()) {

        $entry_time = $row['entry_time'];
        $slot_id = $row['slot_id'];

        $duration_sec = strtotime($exit_time) - strtotime($entry_time);
        $duration_hrs = ceil($duration_sec / 3600);

        // Parking rules
        $allowed_hours = 2;
        $base_charge = 20;
        $extra_rate = 10;

        if ($duration_hrs <= $allowed_hours) {
            $charges = $base_charge;
        } else {
            $extra_hours = $duration_hrs - $allowed_hours;
            $charges = $base_charge + ($extra_hours * $extra_rate);
        }

        // Update vehicle record
        $conn->query("
            UPDATE vehicles 
            SET exit_time='$exit_time', 
                duration=$duration_hrs, 
                charges=$charges, 
                status='Exited' 
            WHERE vehicle_id=$vehicle_id
        ");

        // Free parking slot
        $conn->query("UPDATE parking_slots SET status='Available' WHERE slot_id=$slot_id");

        // Exit sound
        echo "<script>
            window.onload = () => {
                let audio = new Audio('https://actions.google.com/sounds/v1/alarms/beep_short.ogg');
                audio.play();
            }
        </script>";
    }
}

/* ===============================
   FETCH VEHICLE HISTORY
================================ */
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
<meta charset="UTF-8">
<title>Parking History</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
  --primary: #2563eb;
  --secondary: #3498db;
  --success: #28a745;
  --danger: #dc3545;
  --light: #f8f9fa;
  --dark: #212529;
}

body {
  background: #eef2ff;
  font-family: 'Segoe UI', sans-serif;
  padding: 30px;
}

.card {
  background: white;
  border-radius: 15px;
  padding: 25px;
  box-shadow: 0 6px 16px rgba(0,0,0,0.15);
  max-width: 1100px;
  margin: auto;
}

h2 {
  text-align: center;
  color: var(--primary);
  margin-bottom: 20px;
}

table {
  width: 100%;
  border-collapse: collapse;
  text-align: center;
}

thead {
  background: var(--primary);
  color: white;
}

th, td {
  padding: 12px;
  border-bottom: 1px solid #ddd;
}

tbody tr:nth-child(even) {
  background: #f9f9f9;
}

.status-badge {
  padding: 6px 14px;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 500;
}

.in-lot {
  background: rgba(40,167,69,0.15);
  color: #28a745;
}

.overstay {
  background: rgba(220,53,69,0.15);
  color: #dc3545;
}

.exited {
  background: rgba(108,117,125,0.15);
  color: gray;
}

.btn-exit {
  background: var(--danger);
  color: white;
  padding: 6px 12px;
  border-radius: 6px;
  text-decoration: none;
}

.btn-exit:hover {
  background: #b52a3a;
}

.btn-back {
  margin-top: 20px;
  display: inline-block;
  background: var(--primary);
  color: white;
  padding: 10px 20px;
  border-radius: 8px;
  text-decoration: none;
}
</style>
</head>
<body>

<div class="card">
<h2>Parking Records & History</h2>

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
<?php while ($row = $vehicles->fetch_assoc()): 

    if ($row['status'] === 'In Lot') {
        $parked_hours = ceil((time() - strtotime($row['entry_time'])) / 3600);
        $overstay = $parked_hours > 2;
    } else {
        $overstay = $row['duration'] > 2;
    }
?>
<tr>
<td><?= htmlspecialchars(decryptVehicleNo($row['vehicle_no'])) ?></td>
<td><?= maskOwnerName($row['owner_name']) ?></td>
<td><?= date('M d, Y H:i', strtotime($row['entry_time'])) ?></td>
<td><?= $row['exit_time'] ? date('M d, Y H:i', strtotime($row['exit_time'])) : '-' ?></td>
<td><?= $row['duration'] ?? '-' ?></td>
<td><?= $row['charges'] ? '₹'.$row['charges'] : '-' ?></td>
<td>
<?php if ($row['status'] === 'In Lot'): ?>
  <span class="status-badge <?= $overstay ? 'overstay' : 'in-lot' ?>">
    <?= $overstay ? 'Overstayed' : 'In Lot' ?>
  </span>
<?php else: ?>
  <span class="status-badge exited">Exited</span>
<?php endif; ?>
</td>
<td>
<?php if ($row['status'] === 'In Lot'): ?>
  <a href="?exit=<?= $row['vehicle_id'] ?>" class="btn-exit"
     onclick="return confirm('Confirm vehicle exit?')">Exit</a>
<?php else: ?> - <?php endif; ?>
</td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="8">No records found</td></tr>
<?php endif; ?>

</tbody>
</table>

<div style="text-align:center;">
<a href="index.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>

</div>
</body>
</html>
