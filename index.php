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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f5faff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            margin: 0;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: 240px;
            background-color: #1e3a8a;
            padding: 20px 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            z-index: 1000;
            color: white;
        }

        .sidebar .logo {
            text-align: center;
            font-size: 1.3rem;
            font-weight: bold;
            color: #ffffff;
            margin-bottom: 20px;
        }

        .sidebar .logo span {
            display: block;
            font-size: 0.75rem;
            color: #cbd5e1;
        }

        .sidebar .nav-menu {
            list-style: none;
            padding: 0;
            margin: 0 0 20px 0;
        }

        .sidebar .nav-menu li a,
        .sidebar .logout a {
            color: #f0f0f0;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            padding: 12px 24px;
            transition: background 0.3s;
        }

        .sidebar .nav-menu li a:hover,
        .sidebar .logout a:hover {
            background-color: #2563eb;
        }

        .sidebar .section-title {
            margin: 10px 24px;
            font-size: 0.75rem;
            font-weight: bold;
            color: #dbeafe;
            text-transform: uppercase;
        }

        .main-content {
            margin-left: 240px;
            padding: 20px;
        }
        .navbar-brand {
            color: black;
            font-weight: 700;
            font-size: 1.25rem;
        }

        .card-box {
            background-color: #eff6ff;
            border-radius: 14px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .card-box:hover {
            transform: translateY(-4px);
        }

        .card-box .display-4 {
            font-size: 2rem;
            font-weight: 700;
        }

        .glass-table-container {
            margin-top: 20px;
        }

        .slot-card {
            background: white;
            border-left: 5px solid #60a5fa;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }

        .slot-card:hover {
            transform: scale(1.01);
            box-shadow: 0 8px 16px rgba(0,0,0,0.08);
        }

        .slot-header {
            display: flex;
            justify-content: space-between;
            font-weight: 600;
        }

        .status-available {
            color: #10b981;
            background: #d1fae5;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.9rem;
        }

        .status-occupied {
            color: #ef4444;
            background: #fee2e2;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.9rem;
        }

        .slot-date {
            font-size: 0.8rem;
            color: #6b7280;
            margin-top: 4px;
        }

        .progress-bar {
            font-weight: 600;
            font-size: 0.9rem;
            text-align: center;
        }
        
        .sidebar .icon {
            width: 20px;
            text-align: center;
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="logo">HAMRO EASY PARKING</div>
    <ul class="nav-menu">
        <li><a href="#"><span class="icon"><i class="fas fa-tachometer-alt"></i></span> Dashboard</a></li>
                <li><a href="add_new_slot.php"><span class="icon"><i class="fas fa-car"></i></span> Add Parking Slot</a></li>
        <li><a href="add_vehicle.php"><span class="icon"><i class="fas fa-plus-circle"></i></span> Add Vehicle Entry</a></li>
        <li><a href="parking_parked.php"><span class="icon"><i class="fas fa-parking"></i></span> Vehicle Parked</a></li>
        <li><a href="parking_history.php"><span class="icon"><i class="fas fa-file-alt"></i></span> Parking Records</a></li>
        <li><a href="show_receipt.php"><span class="icon"><i class="fas fa-receipt"></i></span> Receipt</a></li>
         <li><a href="parking_exit.php"><span class="icon"><i class="fas fa-sign-out-alt"></i></span> Vehicle Exit</a></li>
        <li><a href="parking_history_delete.php"><span class="icon"><i class="fas fa-trash-alt"></i></span> Delete History</a></li>
        <li><a href="Delete_old_slot.php"><span class="icon"><i class="fas fa-trash-alt"></i></span> Delete Parking Slot</a></li>
    </ul>
    <div class="logout"><a href="admin_login.php"><span class="icon"><i class="fas fa-door-open"></i></span> Log Out</a></div>
</div>

<!-- Main Content -->
<main class="main-content">
    <div class="container mt-4">
        <div class="row g-3 mb-4">
            <div class="col-md-3"><div class="card-box">Total Vehicles Today<div class="display-4"><?= $totalVehiclesToday ?></div></div></div>
            <div class="col-md-3"><div class="card-box">Currently Parked<div class="display-4"><?= $currentParked ?></div></div></div>
            <div class="col-md-3"><div class="card-box">Income Today<div class="display-4">₹<?= number_format($totalIncomeToday) ?></div></div></div>
            <div class="col-md-3"><div class="card-box">Efficiency<div class="display-4"><?= round($parkingEfficiency, 2) ?>%</div></div></div>
        </div>

        <h5 class="mt-4 mb-3">Slot Usage Indicator</h5>
        <div class="progress mb-4" style="height: 30px; border-radius: 20px; overflow: hidden;">
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

        <h5 class="mt-4 mb-3">Parking Slot Status</h5>
        <div class="glass-table-container">
            <?php while($row = $slots->fetch_assoc()) { ?>
            <div class="slot-card">
                <div class="slot-header">
                    <div><strong><?= htmlspecialchars($row['slot_name']) ?></strong></div>
                    <div>
                        <?php if ($row['status'] == 'Available') { ?>
                            <span class="status-available">Available</span>
                        <?php } else { ?>
                            <span class="status-occupied">Occupied</span>
                        <?php } ?>
                    </div>
                </div>
                <div class="slot-date">Modified: <?= date('d M Y, h:i A', time()) ?></div></div>
            <?php } ?>
        </div>
    </div>
</main>

</body>
</html>