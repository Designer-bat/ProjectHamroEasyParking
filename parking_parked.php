<?php
$conn = new mysqli('localhost', 'root', '', 'parking_system');
include 'config_secure.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle vehicle exit
if (isset($_GET['exit']) && is_numeric($_GET['exit'])) {
    $vehicle_id = intval($_GET['exit']);
    $exit_time = date('Y-m-d H:i:s');

    $result = $conn->query("SELECT entry_time, slot_id FROM vehicles WHERE vehicle_id = $vehicle_id");
    if ($result && $row = $result->fetch_assoc()) {
        $duration_sec = strtotime($exit_time) - strtotime($row['entry_time']);
        $duration_hrs = ceil($duration_sec / 3600);
        $charges = $duration_hrs * 10;

        $conn->query("
            UPDATE vehicles 
            SET exit_time='$exit_time', duration=$duration_hrs, charges=$charges, status='Exited'
            WHERE vehicle_id=$vehicle_id
        ");

        $conn->query("
            UPDATE parking_slots 
            SET status='Available' 
            WHERE slot_id={$row['slot_id']}
        ");
    }
}

// Fetch vehicle history
$vehicles = $conn->query("SELECT * FROM vehicles ORDER BY entry_time DESC");

// Mask owner name
function maskOwnerName($hashed) {
    return substr($hashed, 0, 6) . "****" . substr($hashed, -4);
}

include("Aiindex.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Vehicle Records</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
body {
    background: #f1f5f9;
    font-family: 'Poppins', 'Segoe UI', sans-serif;
    margin: 0;
    padding: 40px 20px;
    display: flex;
    justify-content: center;
}

.card {
  
    width: 1400px;        /* 25cm in px */
    height: 600px;       /* auto height for multiple notifications */
    background: #fff;
    border-radius: 14px;
    padding: 30px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
    margin-left: 250px; /* move card to the right */
    margin-right: 0;
}

.card-header {
  display: flex;
  gap: 14px;
  align-items: center;
  margin-bottom: 25px;
  border-bottom: 2px solid #e5e7eb;
  padding-bottom: 10px;
}

.card-header i {
  font-size: 26px;
  color: #2563eb;
}

.card-header h2 {
  margin: 0;
  color: #1e293b;
}

.notification-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.notification-card {
  background: #f9fafb;
  border-radius: 14px;
  padding: 18px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  transition: 0.25s ease;
}

.notification-card:hover {
  background: #ffffff;
  transform: translateY(-2px);
}

.notif-left {
  display: flex;
  gap: 14px;
  align-items: center;
}

.notif-icon {
  width: 45px;
  height: 45px;
  background: #2563eb;
  border-radius: 50%;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
}

.notif-details h4 {
  margin: 0;
  font-size: 15px;
  color: #1e293b;
}

.notif-details p {
  margin: 4px 0 0;
  font-size: 13px;
  color: #6b7280;
}

.notif-right {
  display: flex;
  align-items: center;
  gap: 10px;
}

.status-badge {
  padding: 5px 14px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 500;
}

.in-lot {
  background: rgba(34,197,94,0.15);
  color: #16a34a;
}

.exited {
  background: rgba(100,116,139,0.15);
  color: #475569;
}

.btn-small {
  background: #2563eb;
  color: #fff;
  text-decoration: none;
  padding: 6px 14px;
  border-radius: 20px;
  font-size: 12px;
  transition: 0.2s ease;
}

.btn-small:hover {
  background: #1e40af;
}

/* Responsive */
@media (max-width: 1200px) {
  .card {
    width: 90%;
    margin-left: auto;
    margin-right: auto;
  }
}

@media (max-width: 768px) {
  .notification-card {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }

  .notif-right {
    width: 100%;
    justify-content: space-between;
  }
}
</style>
</head>

<body>

<div class="card">
  <div class="card-header">
    <i class="fas fa-car"></i>
    <div>
      <h2>Vehicle Activity</h2>
      <p style="margin:0;color:#6b7280;font-size:14px;">Parking notifications & history</p>
    </div>
  </div>

  <div class="notification-list">

    <?php if ($vehicles->num_rows > 0): ?>
      <?php while ($row = $vehicles->fetch_assoc()): ?>
        <div class="notification-card">

          <div class="notif-left">
            <div class="notif-icon">
              <i class="fas fa-car"></i>
            </div>
            <div class="notif-details">
              <h4><?= htmlspecialchars(decryptVehicleNo($row['vehicle_no'])) ?></h4>
              <p>
                <?= htmlspecialchars(maskOwnerName($row['owner_name'])) ?> • 
                <?= date('M d, Y H:i', strtotime($row['entry_time'])) ?>
              </p>
            </div>
          </div>

          <div class="notif-right">
            <!-- Status badge -->
            <span class="status-badge <?= $row['status'] === 'In Lot' ? 'in-lot' : 'exited' ?>">
              <?= $row['status'] ?>
            </span>

            <!-- Receipt button -->
            <a href="?receipt=<?= $row['vehicle_id'] ?>" class="btn-small">Receipt</a>

            <!-- Action button: Exit if In Lot -->
            <?php if ($row['status'] === 'In Lot'): ?>
              <a href="?exit=<?= $row['vehicle_id'] ?>" class="btn-small"
                 onclick="return confirm('Confirm vehicle exit?')">Exit</a>
            <?php else: ?>
              <span class="btn-small" style="background:#6c757d;cursor:default;">No Action</span>
            <?php endif; ?>
          </div>

        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p style="text-align:center;color:#6b7280;">No parking records found</p>
    <?php endif; ?>

  </div>
</div>

</body>
</html>
