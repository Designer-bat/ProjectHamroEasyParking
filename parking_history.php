<?php
$conn = new mysqli('localhost', 'root', '', 'parking_system');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['exit']) && is_numeric($_GET['exit'])) {
    $vehicle_id = intval($_GET['exit']);
    $exit_time = date('Y-m-d H:i:s');

    // Get entry time and slot_id
    $result = $conn->query("SELECT entry_time, slot_id FROM vehicles WHERE vehicle_id = $vehicle_id");
    if ($result && $row = $result->fetch_assoc()) {
        $entry_time = $row['entry_time'];
        $slot_id = $row['slot_id'];

        $duration_sec = strtotime($exit_time) - strtotime($entry_time);
        $duration_hrs = ceil($duration_sec / 3600);
        $charges = $duration_hrs * 10; // $10 per hour

        // Update vehicle record
        $update = "UPDATE vehicles 
                   SET exit_time='$exit_time', duration=$duration_hrs, charges=$charges, status='Exited' 
                   WHERE vehicle_id=$vehicle_id";

        if (!$conn->query($update)) {
            die("Error updating vehicle record: " . $conn->error);
        }

        // Mark slot as available again
        $conn->query("UPDATE parking_slots SET status='Available' WHERE slot_id = $slot_id");

    } else {
        die("Error fetching vehicle info: " . $conn->error);
    }
}

// Fetch all vehicle records
$vehicles = $conn->query("SELECT * FROM vehicles ORDER BY entry_time DESC");
if (!$vehicles) {
    die("Error fetching vehicles: " . $conn->error);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Parking History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    body {
        background-color: #212A31;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: #D3D9D4;
        margin: 0;
    }

    .container {
        margin-top: 50px;
    }

    h2 {
        color: #D3D9D4;
        font-weight: 700;
    }

    .card {
        background: rgba(46, 57, 68, 0.7); /* #2E3944 glass effect */
        border-radius: 14px;
        border: 1px solid #748D92;
        backdrop-filter: blur(18px);
        -webkit-backdrop-filter: blur(18px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }

    .table {
        color: #D3D9D4;
    }

    .table thead {
        background-color: #124E66;
        color: #D3D9D4;
    }

    .table th, .table td {
        vertical-align: middle;
    }

    tbody tr:hover {
        background-color: rgba(116, 141, 146, 0.25); /* #748D92 light hover */
    }

    .badge.bg-success {
        background-color: #6ee7b7; /* optional green for In Lot */
        color: #1e293b;
    }

    .badge.bg-secondary {
        background-color: #748D92;
        color: #212A31;
    }

    .btn-outline-danger {
        border-color: #f87171;
        color: #f87171;
    }

    .btn-outline-danger:hover {
        background-color: #f87171;
        color: #fff;
    }

    .btn-secondary {
        background-color: transparent;
        color: #D3D9D4;
        border: 1px solid #748D92;
    }

    .btn-secondary:hover {
        background-color: #124E66;
        color: #fff;
        border-color: #124E66;
    }

    @media (max-width: 575.98px) {
        h2 {
            font-size: 1.5rem;
        }
    }
</style>

</head>
<body>

<div class="container">
    <h2 class="mb-4 text-primary text-center">Parking History</h2>

    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-bordered table-hover text-center align-middle">
                <thead class="table-primary">
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
                    <?php while($row = $vehicles->fetch_assoc()) { ?>
                    <tr>
                        <td><?= htmlspecialchars($row['vehicle_no']) ?></td>
                        <td><?= htmlspecialchars($row['owner_name']) ?></td>
                        <td><?= $row['entry_time'] ?></td>
                        <td><?= $row['exit_time'] ?? '-' ?></td>
                        <td><?= $row['duration'] ?? '-' ?></td>
                        <td><?= $row['charges'] ?? '-' ?></td>
                        <td>
                            <?php if ($row['status'] === 'In Lot') { ?>
                                <span class="badge bg-success">In Lot</span>
                            <?php } else { ?>
                                <span class="badge bg-secondary">Exited</span>
                            <?php } ?>
                        </td>
                        <td>
                            <?php if ($row['status'] === 'In Lot') { ?>
                                <a href="<?= $_SERVER['PHP_SELF'] ?>?exit=<?= $row['vehicle_id'] ?>" 
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Are you sure you want to mark this vehicle as exited?')">
                                   Exit Vehicle
                                </a>
                            <?php } else { ?>
                                <span class="text-muted">-</span>
                            <?php } ?>
                        </td>
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
