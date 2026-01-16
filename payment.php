<?php
$conn = new mysqli('localhost', 'root', '', 'parking_system');
if ($conn->connect_error) die("Database connection failed");

// ====================== PROCESS PAYMENT ======================
if (isset($_POST['pay']) && ctype_digit($_POST['pay'])) {
    $vehicle_id = intval($_POST['pay']);
    
    // Update payment status
    $conn->query("UPDATE payments SET payment_status='Paid', payment_date=NOW() WHERE vehicle_id=$vehicle_id");
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
include'aiindex.php';
// ====================== FETCH VEHICLES WITH PAYMENT INFO ======================
$query = "
SELECT v.vehicle_id, v.vehicle_no, v.owner_name, v.duration, v.charges, v.status, p.payment_status, p.payment_id
FROM vehicles v
LEFT JOIN payments p ON v.vehicle_id = p.vehicle_id
WHERE v.status='Exited'
ORDER BY v.exit_time DESC
";
$vehicles = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payments & Receipts</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body {
    font-family: 'Poppins', sans-serif;
    background: #f1f5f9;
    margin: 0;
    padding: 40px;
    display: flex;
    justify-content: center;
}

.container {
    width: 1400px;        /* 25cm in px */
    height: 600px;       /* auto height for multiple notifications */
    background: #fff;
    border-radius: 14px;
    padding: 30px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
    margin-left: 250px; /* move card to the right */
    margin-right: 0;
}

h2 {
    color: #1e3a8a;
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
}

h2 i {
    background: #e0e7ff;
    padding: 10px;
    border-radius: 10px;
    color: #1e3a8a;
}

/* Activity card list */
.activity-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.activity-card {
    background: #f8fafc;
    border-radius: 14px;
    padding: 16px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: 0.25s ease;
}

.activity-card:hover {
    background: #f1f5f9;
}

.activity-left {
    display: flex;
    align-items: center;
    gap: 15px;
}

.car-icon {
    width: 45px;
    height: 45px;
    background: #2563eb;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 18px;
}

.activity-info h4 {
    margin: 0;
    font-size: 1rem;
    color: #1e293b;
}

.activity-info p {
    margin: 4px 0;
    font-size: 0.85rem;
    color: #64748b;
}

.charge {
    font-size: 0.9rem;
    font-weight: 600;
    color: #2563eb;
}

.activity-right {
    display: flex;
    align-items: center;
    gap: 10px;
}

.status-badge {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
}

.paid {
    background: rgba(34,197,94,0.15);
    color: #22c55e;
}

.pending {
    background: rgba(234,179,8,0.15);
    color: #b45309;
}

.icon-btn {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
}

.icon-btn.pay {
    background: #2563eb;
}

.icon-btn.receipt {
    background: #16a34a;
}

.icon-btn:hover {
    opacity: 0.85;
}

.empty-state {
    text-align: center;
    padding: 60px 0;
    color: #6b7280;
}

/* Responsive */
@media (max-width: 768px) {
    .container {
        width: 95%;
        padding: 20px;
    }
    .activity-card {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    .activity-right {
        gap: 8px;
        width: 100%;
        justify-content: flex-start;
    }
}
</style>
</head>
<body>
<div class="container">
<h2><i class="fas fa-receipt"></i> Payments & Receipts</h2>

<?php if($vehicles && $vehicles->num_rows>0): ?>
<div class="activity-list">
<?php $customerCount = 1; ?>
<?php while($row = $vehicles->fetch_assoc()): ?>
<div class="activity-card">
    <div class="activity-left">
        <div class="car-icon">
            <i class="fas fa-car"></i>
        </div>
        <div class="activity-info">
            <h4>Customer <?= $customerCount++ ?></h4>
            <p><?= htmlspecialchars($row['owner_name']) ?> • <?= $row['duration'] ?: '-' ?> hr</p>
            <span class="charge">₹<?= $row['charges'] ?: '-' ?></span>
        </div>
    </div>

    <div class="activity-right">
        <span class="status-badge <?= $row['payment_status']==='Paid'?'paid':'pending' ?>">
            <?= $row['payment_status'] ?? 'Pending\\' ?>
        </span>

        <?php if($row['payment_status']!=='Paid'): ?>
        <form method="POST">
            <input type="hidden" name="pay" value="<?= $row['vehicle_id'] ?>">
            <button class="icon-btn pay">
                <i class="fas fa-money-bill-wave"></i>
            </button>
        </form>
        <?php endif; ?>

        <?php if($row['payment_status']==='Paid'): ?>
        <a href="receipt.php?vehicle_id=<?= $row['vehicle_id'] ?>" class="icon-btn receipt">
            <i class="fas fa-file-invoice"></i>
        </a>
        <?php endif; ?>
    </div>
</div>
<?php endwhile; ?>
</div>
<?php else: ?>
<div class="empty-state"><i class="fas fa-info-circle"></i><br>No payments found</div>
<?php endif; ?>
</div>
</body>
</html>
