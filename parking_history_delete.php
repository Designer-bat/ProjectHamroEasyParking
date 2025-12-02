<?php
include("auth_check.php"); // session and auth check
// ====================== CONFIGURATION ======================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'parking_system');
define('CHARGE_RATE_PER_HOUR', 10);
define('CURRENCY_SYMBOL', '₹');

// ====================== DB CONNECTION ======================
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    die("Database connection error. Please try again later.");
}

// ====================== PROCESS EXIT ACTION ======================
if (isset($_POST['exit']) && ctype_digit($_POST['exit'])) {
    $vehicle_id = intval($_POST['exit']);

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT entry_time, slot_id FROM vehicles WHERE vehicle_id = ? AND status = 'In Lot' FOR UPDATE");
        $stmt->bind_param("i", $vehicle_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            $entry_time = $row['entry_time'];
            $slot_id = $row['slot_id'];
            $exit_time = date('Y-m-d H:i:s');

            $duration_seconds = strtotime($exit_time) - strtotime($entry_time);
            $duration_hours = ceil($duration_seconds / 3600);
            $charges = $duration_hours * CHARGE_RATE_PER_HOUR;

            $update_vehicle = $conn->prepare("UPDATE vehicles SET exit_time = ?, duration = ?, charges = ?, status = 'Exited' WHERE vehicle_id = ?");
            $update_vehicle->bind_param("siii", $exit_time, $duration_hours, $charges, $vehicle_id);
            $update_vehicle->execute();

            $update_slot = $conn->prepare("UPDATE parking_slots SET status = 'Available' WHERE slot_id = ?");
            $update_slot->bind_param("i", $slot_id);
            $update_slot->execute();

            $conn->commit();
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Transaction failed: " . $e->getMessage());
        header("Location: parking_history_delete.php");
        exit;
    }
}

// ====================== PROCESS DELETE ACTION ======================
if (isset($_POST['delete_id']) && ctype_digit($_POST['delete_id'])) {
    $delete_id = intval($_POST['delete_id']);
    $stmt = $conn->prepare("DELETE FROM vehicles WHERE vehicle_id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// ====================== FETCH VEHICLE HISTORY ======================
$stmt = $conn->prepare("SELECT * FROM vehicles ORDER BY entry_time DESC");
$stmt->execute();
$vehicles = $stmt->get_result();

include ("Aiindex.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vehicle Parked Records</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* ====================== GLOBAL STYLE ====================== */
body {
  background: var(--body-bg);
  font-family: 'Poppins', 'Segoe UI', sans-serif;
  margin: 0;
  padding: 40px 20px;
  color: #333;
  min-height: 100vh;  
}

/* ====================== CONTAINER ====================== */
.container {
  max-width: 1200px;
  margin: 0 auto;
  background: #ffffff;
  border-radius: 16px;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
  padding: 30px 40px;
  animation: fadeIn 0.6s ease-in-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

/* ====================== HEADINGS ====================== */
h2 {
  font-size: 1.8rem;
  color: #1e3a8a;
  margin-bottom: 10px;
  display: flex;
  align-items: center;
  gap: 10px;
}
h2 i {
  background-color: #e0e7ff;
  color: #1e3a8a;
  padding: 10px;
  border-radius: 10px;
}
p {
  color: #6b7280;
  font-size: 0.95rem;
  margin-bottom: 25px;
}

/* ====================== TABLE ====================== */
table {
  width: 100%;
  border-collapse: collapse;
  border-radius: 10px;
  overflow: hidden;
  background: #ffffff;
}

thead {
  background-color: #2563eb;
  color: #ffffff;
}

th, td {
  padding: 14px 18px;
  text-align: left;
  font-size: 0.9rem;
  border-bottom: 1px solid #e5e7eb;
}

tbody tr:hover {
  background-color: #f9fafb;
  transition: background 0.3s ease;
}

/* ====================== STATUS BADGE ====================== */
.status-badge {
  padding: 6px 14px;
  border-radius: 20px;
  font-weight: 500;
  font-size: 0.85rem;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}
.status-in-lot {
  background-color: rgba(34,197,94,0.15);
  color: #22c55e;
}
.status-exited {
  background-color: rgba(107,114,128,0.15);
  color: #6b7280;
}

/* ====================== BUTTONS ====================== */
.delete-btn {
  background-color: #ef4444;
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 8px 12px;
  cursor: pointer;
  font-size: 0.9rem;
  transition: all 0.2s ease;
}
.delete-btn:hover {
  background-color: #dc2626;
  transform: scale(1.05);
}

.exit-btn {
  background-color: #2563eb;
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 8px 12px;
  cursor: pointer;
  font-size: 0.9rem;
  margin-right: 5px;
  transition: all 0.2s ease;
}
.exit-btn:hover {
  background-color: #1d4ed8;
  transform: scale(1.05);
}

.btn-back {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background-color: #2563eb;
  color: #fff;
  padding: 12px 20px;
  border-radius: 8px;
  font-size: 0.95rem;
  text-decoration: none;
  margin-top: 20px;
  transition: all 0.3s ease;
}
.btn-back:hover {
  background-color: #1957adff;
  transform: translateY(-2px);
}

/* ====================== EMPTY STATE ====================== */
.empty-state {
  text-align: center;
  padding: 60px 0;
  color: #6b7280;
}
.empty-state i {
  font-size: 2.5rem;
  color: #9ca3af;
  margin-bottom: 10px;
}

/* ====================== RESPONSIVE ====================== */
@media (max-width: 768px) {
  body { padding: 20px 10px; }
  th, td { font-size: 0.85rem; padding: 10px; }
  .container { padding: 20px; }
}
</style>
</head>
<body>
<div class="container">
  <h2><i class="fas fa-car"></i> Records and History</h2>
  <p>History of parked in the facility</p>

  <table>
    <thead>
      <tr>
        <th>Vehicle No</th>
        <th>Owner Name</th>
        <th>Entry Time</th>
        <th>Exit Time</th>
        <th>Duration</th>
        <th>Charges</th>
        <th>Status</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($vehicles && $vehicles->num_rows > 0): ?>
        <?php while($row = $vehicles->fetch_assoc()): ?>
          <tr>
            <td>
              <?= (preg_match('/^[A-Za-z0-9+\/=]+$/', $row['vehicle_no']) && strlen($row['vehicle_no']) > 20)
                  ? 'Hidden Content'
                  : htmlspecialchars($row['vehicle_no']); ?>
            </td>
            <td>
              <?= (preg_match('/^[A-Za-z0-9+\/=]+$/', $row['owner_name']) && strlen($row['owner_name']) > 20)
                  ? 'Hidden Content'
                  : htmlspecialchars($row['owner_name']); ?>
            </td>
            <td><?= !empty($row['entry_time']) ? date('M d, Y H:i', strtotime($row['entry_time'])) : '-' ?></td>
            <td><?= !empty($row['exit_time']) ? date('M d, Y H:i', strtotime($row['exit_time'])) : '-' ?></td>
            <td><?= !empty($row['duration']) ? $row['duration'].' hr' : '-' ?></td>
            <td><?= !empty($row['charges']) ? CURRENCY_SYMBOL.$row['charges'] : '-' ?></td>
            <td>
              <?php if ($row['status'] === 'In Lot'): ?>
                <span class="status-badge status-in-lot"><i class="fas fa-car"></i> In Lot</span>
              <?php else: ?>
                <span class="status-badge status-exited"><i class="fas fa-check-circle"></i> Exited</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($row['status'] === 'In Lot'): ?>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="exit" value="<?= $row['vehicle_id']; ?>">
                  <button type="submit" class="exit-btn"><i class="fas fa-sign-out-alt"></i></button>
                </form>
              <?php endif; ?>
              <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this record?');">
                <input type="hidden" name="delete_id" value="<?= $row['vehicle_id']; ?>">
                <button type="submit" class="delete-btn"><i class="fas fa-trash-alt"></i></button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="8" class="empty-state"><i class="fas fa-info-circle"></i><br>No Parking Records Found</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <a href="index.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>
</body>
</html>
