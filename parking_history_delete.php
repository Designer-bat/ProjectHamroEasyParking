<?php
// ====================== CONFIGURATION ======================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'parking_system');
define('CHARGE_RATE_PER_HOUR', 10);
define('CURRENCY_SYMBOL', '₹');

define('ENCRYPTION_KEY', 'your-32-char-secret-key-1234567890abcd');
define('ENCRYPTION_METHOD', 'AES-256-CBC');

// ====================== DB CONNECTION ======================
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    die("Database connection error. Please try again later.");
}

// ====================== ENCRYPTION / DECRYPTION FUNCTIONS ======================
function encryptData($data) {
    $key = hash('sha256', ENCRYPTION_KEY);
    $iv = substr(hash('sha256', 'iv_secret'), 0, 16); // fixed IV
    return openssl_encrypt($data, ENCRYPTION_METHOD, $key, 0, $iv);
}

function decryptData($encryptedData) {
    $key = hash('sha256', ENCRYPTION_KEY);
    $iv = substr(hash('sha256', 'iv_secret'), 0, 16);
    return openssl_decrypt($encryptedData, ENCRYPTION_METHOD, $key, 0, $iv);
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
        header("Location: " . $_SERVER['PHP_SELF']);
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

/* Card Header */
.card-header {
    display: flex;
    gap: 14px;
    align-items: center;
    margin-bottom: 25px;
}
.card-header i {
    font-size: 26px;
    color: #2563eb;
    background-color: #e0e7ff;
    padding: 10px;
    border-radius: 10px;
}
.card-header h2 {
    margin: 0;
    color: #1e293b;
}
.card-header p {
    margin: 2px 0 0 0;
    color: #6b7280;
    font-size: 0.95rem;
}

/* Notification list */
.notification-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

/* Notification Card */
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

/* Status badges */
.status-badge {
    padding: 5px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 6px;
}
.status-in-lot { background-color: rgba(34,197,94,0.15); color: #16a34a; }
.status-exited { background-color: rgba(100,116,139,0.15); color: #475569; }

/* Buttons */
.exit-btn, .delete-btn, .receipt-btn {
    background-color: #2563eb;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 6px 12px;
    cursor: pointer;
    font-size: 0.85rem;
    transition: all 0.2s ease;
}
.delete-btn { background-color: #ef4444; }
.exit-btn:hover { background-color: #1d4ed8; }
.delete-btn:hover { background-color: #dc2626; }
.receipt-btn:hover { background-color: #1d4ed8; }

.btn-back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background-color: #2563eb;
    color: #fff;
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 0.95rem;
    text-decoration: none;
    margin-top: 20px;
}

/* Empty state */
.empty-state { text-align: center; padding: 60px 0; color: #6b7280; }
.empty-state i { font-size: 2rem; margin-bottom: 10px; }

/* Responsive */
@media (max-width: 768px) {
    .card { width: 95%; padding: 20px; }
    .notif-right { flex-wrap: wrap; gap: 5px; }
}
</style>
</head>

<body>
<div class="card">
    <div class="card-header">
        <i class="fas fa-car"></i>
        <div>
            <h2>Vehicle Activity</h2>
            <p>Parking notifications & history</p>
        </div>
    </div>

    <div class="notification-list">
        <?php if ($vehicles && $vehicles->num_rows > 0): ?>
            <?php while($row = $vehicles->fetch_assoc()): ?>
                <div class="notification-card">
                    <div class="notif-left">
                        <div class="notif-icon"><i class="fas fa-car"></i></div>
                        <div class="notif-details">
                            <h4><?= htmlspecialchars($row['vehicle_no']) ?></h4>
                            <p><?= htmlspecialchars(decryptData($row['owner_name'])) ?> • <?= date('M d, Y H:i', strtotime($row['entry_time'])) ?></p>
                        </div>

                    </div>
                    
                    <div class="notif-right">
                        <span class="status-badge <?= $row['status'] === 'In Lot' ? 'status-in-lot' : 'status-exited' ?>">
                            <?= $row['status'] ?>
                        </span>

                        <?php if ($row['status'] === 'In Lot'): ?>
                            <form method="POST" style="margin:0;">
                                <input type="hidden" name="exit" value="<?= $row['vehicle_id']; ?>">
                                <button type="submit" class="exit-btn"><i class="fas fa-sign-out-alt"></i></button>
                            </form>
                        <?php endif; ?>

                        <form method="POST" style="margin:0;">
                            <input type="hidden" name="delete_id" value="<?= $row['vehicle_id']; ?>">
                            <button type="submit" class="delete-btn"><i class="fas fa-trash-alt"></i></button>
                        </form>

                        <a href="?receipt=<?= $row['vehicle_id']; ?>" class="receipt-btn"><i class="fas fa-file-invoice"></i></a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-info-circle"></i><br>No Parking Records Found
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
