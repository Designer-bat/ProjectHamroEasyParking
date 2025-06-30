<?php
// Enable error display for development
ini_set('display_errors', 1);
error_reporting(E_ALL);

$conn = new mysqli('localhost', 'root', '', 'parking_system');
$message = '';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $slot_id = intval($_POST['slot_id']);
    $vehicle_no = trim($conn->real_escape_string($_POST['vehicle_no']));
    $owner_name = trim($conn->real_escape_string($_POST['owner_name']));
    $vehicle_type = trim($conn->real_escape_string($_POST['vehicle_type']));
    $entry_time = date('Y-m-d H:i:s');

    // Check if selected slot is available
    $check = $conn->prepare("SELECT status FROM parking_slots WHERE slot_id = ?");
    $check->bind_param("i", $slot_id);
    $check->execute();
    $result = $check->get_result();
    $slot = $result->fetch_assoc();
    $check->close();

    if (!$slot) {
        $message = "Invalid slot selected.";
    } elseif ($slot['status'] === 'Occupied') {
        $message = "Selected slot is already occupied.";
    } else {
        // Insert vehicle data
        $insert = $conn->prepare("INSERT INTO vehicles (vehicle_no, owner_name, vehicle_type, entry_time, slot_id, status) VALUES (?, ?, ?, ?, ?, 'In Lot')");
        $insert->bind_param("ssssi", $vehicle_no, $owner_name, $vehicle_type, $entry_time, $slot_id);

        if ($insert->execute()) {
            $insert->close();

            // Update slot status to occupied
            $update = $conn->prepare("UPDATE parking_slots SET status = 'Occupied' WHERE slot_id = ?");
            $update->bind_param("i", $slot_id);
            $update->execute();
            $update->close();

            header("Location: index.php");
            exit;
        } else {
            $message = "Error inserting vehicle: " . $insert->error;
        }
    }
}

$availableSlots = $conn->query("SELECT slot_id, slot_name FROM parking_slots WHERE status = 'Available'");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Vehicle Entry</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    body {
        background-color: #212A31;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: #D3D9D4;
        margin: 0;
    }

    .form-container {
        max-width: 650px;
        margin: 60px auto;
        background: rgba(46, 57, 68, 0.7); /* #2E3944 with glass effect */
        padding: 40px;
        border-radius: 14px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid #748D92;
        color: #D3D9D4;
    }

    h3 {
        color: #D3D9D4;
        font-weight: 700;
    }

    label.form-label {
        color: #D3D9D4;
        font-weight: 500;
    }

    .form-control,
    .form-select {
        background-color: #2E3944;
        border: 1px solid #748D92;
        color: #D3D9D4;
    }

    .form-control::placeholder {
        color: #9CA3AF; /* light gray for placeholder */
    }

    .form-control:focus,
    .form-select:focus {
        background-color: #124E66;
        border-color: #124E66;
        color: #fff;
        box-shadow: 0 0 0 0.15rem rgba(18, 78, 102, 0.4);
    }

    .btn-primary {
        background-color: #124E66;
        border: none;
        color: #fff;
        font-weight: 600;
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

    .alert-danger,
    .alert-warning {
        border-radius: 10px;
        font-weight: 500;
    }

    .alert-danger {
        background-color: #f87171;
        color: #212A31;
        border: none;
    }

    .alert-warning {
        background-color: #fde68a;
        color: #212A31;
        border: none;
    }
</style>

</head>
<body>

<div class="container">
    <div class="form-container">
        <h3 class="mb-4 text-primary text-center">Add Vehicle Entry</h3>

        <?php if ($message): ?>
            <div class="alert alert-danger"><?= $message ?></div>
        <?php endif; ?>

        <?php if ($availableSlots && $availableSlots->num_rows > 0): ?>
            <form method="post" action="">
                <div class="mb-3">
                    <label for="slot_id" class="form-label">Parking Slot</label>
                    <select class="form-select" name="slot_id" required>
                        <option value="">Select Available Slot</option>
                        <?php while ($row = $availableSlots->fetch_assoc()): ?>
                            <option value="<?= $row['slot_id'] ?>"><?= htmlspecialchars($row['slot_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Vehicle Number</label>
                    <input type="text" class="form-control" name="vehicle_no" placeholder="BA-2-PA-1234" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Vehicle Type</label>
                    <select class="form-select" name="vehicle_type" required>
                        <option value="">Select Vehicle Type</option>
                        <option value="Car">Car</option>
                        <option value="SUV">SUV</option>
                        <option value="Motorbike">Motorbike</option>
                        <option value="EVbike">EVbike</option>
                        <option value="MinTruck">MinTruck</option>
                        <option value="MinBus">MinBus</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Owner Name</label>
                    <input type="text" class="form-control" name="owner_name" placeholder="Owner full name" required>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
                    <button type="submit" class="btn btn-primary">Add Vehicle</button>
                </div>
            </form>
        <?php else: ?>
            <div class="alert alert-warning text-center">All parking slots are currently occupied.</div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
