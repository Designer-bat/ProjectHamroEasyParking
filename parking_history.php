<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'parking_system');
include 'config_secure.php'; // 🔐 include encryption/decryption functions

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

        if (!$conn->query($update)) {
            die("Error updating vehicle record: " . $conn->error);
        }

        $conn->query("UPDATE parking_slots SET status='Available' WHERE slot_id = $slot_id");

    } else {
        die("Error fetching vehicle info: " . $conn->error);
    }
}

// Fetch all vehicle history
$vehicles = $conn->query("SELECT * FROM vehicles ORDER BY entry_time DESC");
if (!$vehicles) {
    die("Error fetching vehicles: " . $conn->error);
}

// Function to mask owner name
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
  --primary-blue: #2c3e50;
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
* { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
body { background-color: #1e3a8a; color: var(--black); line-height: 1.6; min-height: 100vh; padding: 20px; }
.card { background: var(--white); border-radius: 10px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); margin-bottom: 30px; }
.card-header { display: flex; align-items: center; gap: 15px; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid var(--medium-gray); }
.card-header i { font-size: 1.8rem; color: var(--secondary-blue); background: rgba(52, 152, 219, 0.1); width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
.card-title { font-size: 1.5rem; color: var(--primary-blue); }
.card-description { color: var(--dark-gray); margin-top: 5px; font-size: 0.95rem; }
.table-container { overflow-x: auto; border-radius: 8px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05); }
table { width: 100%; border-collapse: collapse; font-size: 0.95rem; }
th, td { padding: 16px 20px; text-align: left; border-bottom: 1px solid var(--medium-gray); }
thead { background: var(--primary-blue); color: var(--white); }
thead th { font-weight: 600; padding: 18px 20px; }
tbody tr { transition: var(--transition); }
tbody tr:hover { background-color: rgba(52, 152, 219, 0.03); }
.status-badge { padding: 6px 14px; border-radius: 20px; font-weight: 500; font-size: 0.85rem; display: inline-block; }
.status-in-lot { background-color: rgba(40, 167, 69, 0.15); color: var(--success); }
.status-exited { background-color: rgba(108, 117, 125, 0.15); color: var(--dark-gray); }
.btn { padding: 8px 16px; border: none; border-radius: 6px; font-size: 0.9rem; font-weight: 500; cursor: pointer; transition: var(--transition); display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
.btn-exit { background: var(--error); color: var(--white); }
.btn-exit:hover { background: #c82333; transform: translateY(-2px); }
.btn-back { background: var(--primary-blue); color: var(--white); padding: 12px 25px; border-radius: 8px; font-weight: 500; display: inline-flex; align-items: center; gap: 10px; margin-top: 20px; transition: var(--transition); text-decoration: none; }
.btn-back:hover { background: var(--accent-blue); transform: translateY(-2px); }
.empty-state { text-align: center; padding: 50px 20px; color: var(--dark-gray); }
.empty-state i { font-size: 3rem; color: var(--medium-gray); margin-bottom: 15px; }
.empty-state h3 { font-size: 1.5rem; margin-bottom: 10px; color: var(--primary-blue); }
@media (max-width: 768px) {
  th, td { padding: 12px 15px; }
  thead th { padding: 15px; }
}
</style>
</head>
<body>

<div class="card">
  <div class="card-header">
    <i class="fas fa-car"></i>
    <div>
      <h2 class="card-title">Vehicle Parking Records</h2>
      <p class="card-description">All vehicles currently in or previously parked in the facility</p>
    </div>
  </div>
  
  <div class="table-container">
    <table>
      <thead>
        <tr>
          <th>Vehicle No</th>
          <th>Owner Name</th>
          <th>Entry Time</th>
          <th>Exit Time</th>
          <th>Duration (hrs)</th>
          <th>Charges (₹)</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($vehicles->num_rows > 0): ?>
          <?php while($row = $vehicles->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars(decryptVehicleNo($row['vehicle_no'])) ?></td>
            <td><?= maskOwnerName($row['owner_name']) ?></td>
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
              <?php if ($row['status'] === 'In Lot'): ?>
                <a href="<?= $_SERVER['PHP_SELF'] ?>?exit=<?= $row['vehicle_id'] ?>" class="btn btn-exit" onclick="return confirm('Mark as exited?')"><i class="fas fa-sign-out-alt"></i> Exit</a>
              <?php else: ?>
                <span style="color: var(--dark-gray)">-</span>
              <?php endif; ?>
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
</div>

<a href="index.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>

</body>
</html>
