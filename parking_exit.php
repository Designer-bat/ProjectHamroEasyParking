<?php
$conn = new mysqli('localhost', 'root', '', 'parking_system');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle vehicle exit if "exit" param is present (just in case)
if (isset($_GET['exit']) && is_numeric($_GET['exit'])) {
    $vehicle_id = intval($_GET['exit']);
    $exit_time = date('Y-m-d H:i:s');

    $result = $conn->query("SELECT entry_time FROM vehicles WHERE vehicle_id = $vehicle_id");
    if ($result && $row = $result->fetch_assoc()) {
        $entry_time = $row['entry_time'];
        $duration_sec = strtotime($exit_time) - strtotime($entry_time);
        $duration_hrs = ceil($duration_sec / 3600);
        $charges = $duration_hrs * 10;

        $update = "UPDATE vehicles 
                   SET exit_time='$exit_time', duration=$duration_hrs, charges=$charges, status='Exited' 
                   WHERE vehicle_id=$vehicle_id";

        if (!$conn->query($update)) {
            die("Error updating vehicle record: " . $conn->error);
        }
    } else {
        die("Error fetching entry time: " . $conn->error);
    }
}

// Fetch exited vehicles
$vehicles = $conn->query("SELECT * FROM vehicles WHERE status='Exited' ORDER BY exit_time DESC");
if (!$vehicles) {
    die("Error fetching vehicles: " . $conn->error);
}

// Fetch total income from exited vehicles
$totalIncome = 0;
$result = $conn->query("SELECT SUM(charges) AS total FROM vehicles WHERE status = 'Exited'");
if ($result && $row = $result->fetch_assoc()) {
    $totalIncome = $row['total'] ?? 0;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Exited Vehicles History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f8;
        }
        .container {
            margin-top: 50px;
        }
        .table th, .table td {
            vertical-align: middle;
        }
    </style>
</head>
<body>
<div class="container">
    <h2 class="mb-4 text-primary text-center">Exited Vehicles History</h2>

    <div class="row mb-4">
        <div class="col-md-6 offset-md-3">
            <div class="card text-white bg-success shadow">
                <div class="card-body text-center">
                    <h5 class="card-title">Total Income from Exited Vehicles</h5>
                    <h2 class="card-text">$<?= number_format($totalIncome, 2) ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-bordered table-hover text-center">
                <thead class="table-success">
                    <tr>
                        <th>Vehicle No</th>
                        <th>Owner Name</th>
                        <th>Entry Time</th>
                        <th>Exit Time</th>
                        <th>Duration (hrs)</th>
                        <th>Charges (₹)</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $vehicles->fetch_assoc()) { ?>
                        <tr>
                            <td><?= htmlspecialchars($row['vehicle_no']) ?></td>
                            <td><?= htmlspecialchars($row['owner_name']) ?></td>
                            <td><?= $row['entry_time'] ?></td>
                            <td><?= $row['exit_time'] ?? '-' ?></td>
                            <td><?= $row['duration'] ?? '-' ?></td>
                            <td><?= $row['charges'] ?? '-' ?></td>
                            <td><span class="badge bg-secondary">Exited</span></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>

            <div class="text-end mt-3">
                <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
