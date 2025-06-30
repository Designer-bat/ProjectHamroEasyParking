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
            background-color: #f8f9fa;
        }
        .form-container {
            max-width: 650px;
            margin: auto;
            background-color: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-top: 60px;
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
