<?php
// DB connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "parking_system";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$vehicle_number = "";
$data = null;
$error = "";

// Form submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $vehicle_number = trim($_POST['vehicle_no']);

    if (empty($vehicle_number)) {
        $error = "Please enter a vehicle number.";
    } else {
        // Replace `vehicles` with your table name
        $stmt = $conn->prepare("SELECT * FROM vehicles WHERE vehicle_no = ? ORDER BY exit_time DESC LIMIT 1");
        $stmt->bind_param("s", $vehicle_number);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $data = $result->fetch_assoc();
        } else {
            $error = "No data found for vehicle number: " . htmlspecialchars($vehicle_number);
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vehicle Parking Receipt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<style>
    body {
        background-color: #212A31;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: #D3D9D4;
        margin: 0;
    }

    .card {
        background: rgba(46, 57, 68, 0.75); /* #2E3944 glass effect */
        border: 1px solid #748D92;
        border-radius: 16px;
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
        color: #D3D9D4;
    }

    h3, h4 {
        color: #D3D9D4;
        font-weight: 600;
    }

    .form-label {
        color: #D3D9D4;
        font-weight: 500;
    }

    .form-control {
        background-color: #2E3944;
        border: 1px solid #748D92;
        color: #D3D9D4;
        border-radius: 8px;
    }

    .form-control::placeholder {
        color: #9CA3AF;
    }

    .form-control:focus {
        background-color: #124E66;
        border-color: #124E66;
        color: #fff;
        box-shadow: 0 0 5px rgba(18, 78, 102, 0.6);
    }

    .btn-primary {
        background-color: #124E66;
        border: none;
        font-weight: 600;
        transition: 0.3s ease;
    }

    .btn-primary:hover {
        background-color: #0e3b4d;
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

    .btn-success {
        background-color: #10b981;
        border: none;
        font-weight: 600;
    }

    .btn-success:hover {
        background-color: #059669;
    }

    .alert-danger {
        background-color: rgba(248, 113, 113, 0.2);
        color: #F87171;
        border: 1px solid rgba(248, 113, 113, 0.4);
        border-radius: 10px;
    }

    table.table {
        color: #D3D9D4;
        border-color: #748D92;
    }

    .table th {
        background-color: #124E66;
        color: #fff;
    }

    .table td {
        background-color: rgba(255, 255, 255, 0.03);
    }
</style>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <div class="card shadow">
                <div class="card-body">
                    <h3 class="text-center mb-4">Search Parking Receipt</h3>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="vehicle_id" class="form-label">Vehicle ID</label>
                            <input type="text" class="form-control" id="vehicle_id" name="vehicle_no"
                                   placeholder="e.g. BA 2 PA 1234" required value="<?= htmlspecialchars($vehicle_number) ?>">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Search</button>
                    </form>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger mt-3"><?= $error ?></div>
                    <?php endif; ?>

                    <?php if ($data): ?>
                        <hr>
                        <h4 class="text-center mb-3">Parking Receipt</h4>
                        <table class="table table-bordered">
                            <tr><th>Vehicle Number</th><td><?= htmlspecialchars($data['vehicle_no']) ?></td></tr>
                            <tr><th>Owner Name</th><td><?= htmlspecialchars($data['owner_name']) ?></td></tr>
                            <tr><th>Vehicle Type</th><td><?= htmlspecialchars($data['vehicle_type']) ?></td></tr>
                            <tr><th>Slot Name</th><td><?= htmlspecialchars($data['slot_id']) ?></td></tr>
                            <tr><th>Entry Time</th><td><?= htmlspecialchars($data['entry_time']) ?></td></tr>
                            <tr><th>Exit Time</th><td><?= htmlspecialchars($data['exit_time']) ?></td></tr>
                            <tr><th>Total Amount</th><td><?= htmlspecialchars($data['charges']) ?> NPR</td></tr>
                        </table>

                        <div class="text-center mt-3">
                            <button class="btn btn-success" onclick="window.print()">🧾 Print Receipt</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="text-center mt-4 text-muted">
              <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>
