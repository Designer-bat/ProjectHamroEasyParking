<?php
$conn = new mysqli('localhost', 'root', '', 'parking_system');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$vehicles = $conn->query("SELECT * FROM vehicles WHERE status='In Lot' ORDER BY entry_time DESC");
if (!$vehicles) {
    die("Error fetching vehicles: " . $conn->error);
}

function calculateDurationHours($entry_time) {
    $now = time();
    $entry = strtotime($entry_time);
    $duration_sec = $now - $entry;
    $duration_hrs = ceil($duration_sec / 3600);
    return $duration_hrs;
}

function calculatePriceMeter($duration_hrs) {
    return $duration_hrs * 10; // $10 per hour
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Currently Parked Vehicles</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .table thead th {
            background-color:rgba(12, 148, 55, 0.85);
            color: white;
        }
        .header-section {
            padding: 40px 0 20px 0;
            text-align: center;
            color: #0d6efd;
            font-weight: 700;
            font-size: 2.5rem;
        }
        .btn-back {
            margin-top: 20px;
        }
        @media (max-width: 575.98px) {
            .header-section {
                font-size: 1.8rem;
                padding: 30px 0 10px 0;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header-section">
        Currently Parked Vehicles
    </div>
    <div class="table-responsive shadow-sm rounded bg-white p-3">
        <table class="table table-striped table-hover align-middle">
            <thead>
                <tr>
                    <th scope="col">Vehicle No</th>
                    <th scope="col">Owner Name</th>
                    <th scope="col">Entry Time</th>
                    <th scope="col">Status</th>
                    <th scope="col">Duration (hrs)</th>
                    <th scope="col">Price Meter (₹)</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $vehicles->fetch_assoc()) { 
                    $duration = calculateDurationHours($row['entry_time']);
                    $price_meter = calculatePriceMeter($duration);
                ?>
                <tr>
                    <td><?= htmlspecialchars($row['vehicle_no']) ?></td>
                    <td><?= htmlspecialchars($row['owner_name']) ?></td>
                    <td><?= date('d M Y, H:i', strtotime($row['entry_time'])) ?></td>
                    <td>
                        <span class="badge bg-success"><?= htmlspecialchars($row['status']) ?></span>
                    </td>
                    <td><?= $duration ?></td>
                    <td><?= $price_meter ?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <a href="index.php" class="btn btn-outline-primary btn-back">Back to Dashboard</a>
</div>
</body>
</html>
