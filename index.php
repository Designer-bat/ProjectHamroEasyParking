<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'parking_system');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Dashboard Stats
$totalVehiclesToday = 0;
$currentParked = 0;
$totalIncomeToday = 0;
$parkingEfficiency = 0;

$result1 = $conn->query("SELECT COUNT(*) AS total FROM vehicles WHERE DATE(entry_time) = CURDATE()");
if ($result1) {
    $totalVehiclesToday = $result1->fetch_assoc()['total'];
}

$result2 = $conn->query("SELECT COUNT(*) AS current FROM vehicles WHERE exit_time IS NULL");
if ($result2) {
    $currentParked = $result2->fetch_assoc()['current'];
}

$result3 = $conn->query("SELECT SUM(charges) AS total FROM vehicles WHERE DATE(exit_time) = CURDATE()");
if ($result3) {
    $totalIncomeToday = $result3->fetch_assoc()['total'] ?? 0;
}

$parkingEfficiency = $totalVehiclesToday > 0 ? ($totalIncomeToday / ($totalVehiclesToday * 10)) * 0.5 : 0;

// Parking Slot Stats
$slots = $conn->query("SELECT * FROM parking_slots");
$availableCount = $conn->query("SELECT COUNT(*) AS total FROM parking_slots WHERE status = 'Available'")->fetch_assoc()['total'];
$occupiedCount = $conn->query("SELECT COUNT(*) AS total FROM parking_slots WHERE status = 'Occupied'")->fetch_assoc()['total'];
$totalSlots = $availableCount + $occupiedCount;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Parking Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    body {
        background: linear-gradient(135deg, #e0f2fe 0%, #60a5fa 100%);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: #b08968; /* Light brown text */
        margin: 0;
    }

    .glass {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(24px) saturate(170%) contrast(97%) brightness(115%);
        -webkit-backdrop-filter: blur(24px) saturate(170%) contrast(97%) brightness(115%);
        border-radius: 18px;
        border: 1px solid rgba(255, 255, 255, 0.15);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
    }

    .navbar {
        background: rgba(37, 99, 235, 0.85); /* blue */
        backdrop-filter: blur(18px);
        padding: 0.5rem 1.5rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.13);
    }

    .navbar-brand {
        color: #ffffff;
        font-weight: 700;
        font-size: 1.25rem;
    }

    .btn-group a {
        color: #ffffff;
        font-weight: 600;
        border-radius: 12px;
        padding: 0.5rem 1rem;
        background: rgba(59, 130, 246, 0.13);
        border: 1px solid rgba(255, 255, 255, 0.1);
        text-decoration: none;
        box-shadow: 0 4px 10px rgba(59, 130, 246, 0.1);
    }

    .btn-group a:hover {
        background: rgba(59, 130, 246, 0.3);
        color: #fff;
    }

    .card-box {
        padding: 20px;
        border-radius: 14px;
        text-align: center;
        background: rgba(255, 255, 255, 0.1);
        color:rgb(0, 0, 0);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.09);
    }

    .card-box .display-4 {
        font-size: 2rem;
        font-weight: 700;
    }

    .bg-primary {
        background: linear-gradient(135deg, #2563eb 60%, #60a5fa 100%);
        color: #ffffff;
    }

    .bg-warning {
        background: linear-gradient(135deg, #fcd34d 60%, #fde68a 100%);
        color: #1e293b;
    }

    .bg-success {
        background: linear-gradient(135deg, #10b981 60%, #6ee7b7 100%);
        color: #1e293b;
    }

    .bg-efficiency {
        background: linear-gradient(135deg, #3b82f6 60%, #93c5fd 100%);
        color: #ffffff;
    }

    .glass-table-container {
        margin-top: 20px;
        padding: 20px;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.07);
        backdrop-filter: blur(16px);
        box-shadow: 0 6px 24px rgba(0, 0, 0, 0.13);
        border: 1px solid rgba(255, 255, 255, 0.09);
    }

    table.table {
        color: #b08968;
    }

    thead.table-light th {
        background: rgba(59, 130, 246, 0.13);
        color: #ffffff;
    }

    tbody tr:hover {
        background: rgba(59, 130, 246, 0.07);
    }

    .status-available {
        color: #22d3ee;
        font-weight: 700;
    }

    .status-occupied {
        color: #ef4444;
        font-weight: 700;
    }

    .progress-bar {
        font-weight: 600;
        font-size: 0.9rem;
        line-height: 30px;
        text-align: center;
    }

    @media (max-width: 768px) {
        .navbar {
            flex-direction: column;
            align-items: flex-start;
        }

        .btn-group {
            flex-wrap: wrap;
        }
    }
</style>

</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h1">HAMRO EASY PARKING</span>
        <div class="btn-group">
            <a href="add_vehicle.php">Vehicle Entry</a>
            <a href="parking_parked.php">Vehicle Parked</a>
            <a href="parking_exit.php">Vehicle Exit</a>
            <a href="parking_history.php">Parking Records</a>
            <a class="btn-danger" href="admin_login.php">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h3 class="fw-bold">Dashboard</h3>

    <div class="row g-3 my-3">
        <div class="col-md-3"><div class="card-box bg-primary glass">Total Vehicles Today<div class="display-4"><?= $totalVehiclesToday ?></div></div></div>
        <div class="col-md-3"><div class="card-box bg-warning glass">Currently Parked<div class="display-4"><?= $currentParked ?></div></div></div>
        <div class="col-md-3"><div class="card-box bg-success glass">Income Today<div class="display-4">₹<?= number_format($totalIncomeToday) ?></div></div></div>
        <div class="col-md-3"><div class="card-box bg-efficiency glass">Efficiency<div class="display-4"><?= round($parkingEfficiency, 2) ?>%</div></div></div>
    </div>

    <!-- Slot Usage Bar -->
    <h5 class="mt-4 mb-2">Slot Usage Indicator</h5>
    <div class="glass p-3 mb-4">
        <div class="progress" style="height: 30px; background-color: rgba(255,255,255,0.1); border-radius: 20px; overflow: hidden;">
            <div class="progress-bar bg-success"
                 role="progressbar"
                 style="width: <?= ($availableCount / $totalSlots) * 100 ?>%"
                 aria-valuenow="<?= $availableCount ?>"
                 aria-valuemin="0"
                 aria-valuemax="<?= $totalSlots ?>">
                <?= $availableCount ?> Available
            </div>
            <div class="progress-bar bg-danger"
                 role="progressbar"
                 style="width: <?= ($occupiedCount / $totalSlots) * 100 ?>%"
                 aria-valuenow="<?= $occupiedCount ?>"
                 aria-valuemin="0"
                 aria-valuemax="<?= $totalSlots ?>">
                <?= $occupiedCount ?> Occupied
            </div>
        </div>
    </div>
     <!-- meanu -->
     <div class="d-flex justify-content-end mt-3 gap-2">
            <a href="show_QR.php" class="btn btn-success">Show QR</a>
            <a href="show_receipt.php" class="btn btn-success">Show Receipt</a>
            <a href="add_new_slot.php" class="btn btn-success">Add New Slot</a>
            <a href="add_vehicle.php" class="btn btn-success">Add Vehicle</a>
            <a href="parking_exit.php" class="btn btn-success">Vehicle Exit</a>
            <a href="parking_history.php" class="btn btn-secondary">History</a>
            <a href="parking_history_delete.php" class="btn btn-secondary">Parking History Delete</a>
        </div>
    <!-- Table -->
    <h5 class="mt-4 mb-2">Parking Slot Status</h5>
    <div class="glass-table-container shadow">
        <table class="table table-bordered text-center">
            <thead class="table-light">
                <tr><th>Slot ID</th><th>Slot Name</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php while($row = $slots->fetch_assoc()) { ?>
                <tr>
                    <td><?= $row['slot_id'] ?></td>
                    <td><?= htmlspecialchars($row['slot_name']) ?></td>
                    <td>
                        <?php if ($row['status'] == 'Available') { ?>
                            <span class="status-available">Available</span>
                        <?php } else { ?>
                            <span class="status-occupied">Occupied</span>
                        <?php } ?>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
