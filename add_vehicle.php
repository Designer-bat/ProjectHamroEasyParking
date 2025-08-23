<?php
// Enable error display for development
ini_set('display_errors', 1);
error_reporting(E_ALL);

$conn = new mysqli('localhost', 'root', '', 'parking_system');
$message = '';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Include encryption + hashing functions
require_once 'config_secure.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Raw user inputs
    $vehicle_no_raw = trim($_POST['vehicle_no']);
    $owner_name_raw = trim($_POST['owner_name']);
    $vehicle_type = trim($conn->real_escape_string($_POST['vehicle_type']));
    $duration_hours = intval($_POST['duration_hours']); // Parking duration in hours
    $entry_time = date('Y-m-d H:i:s');
    $exit_time = date('Y-m-d H:i:s', strtotime("+$duration_hours hours", strtotime($entry_time)));

    // Validate duration (1 to 24 hours only)
    if ($duration_hours < 1 || $duration_hours > 24) {
        $message = "Parking duration must be between 1 and 24 hours.";
    } else {
        // Encrypt vehicle number (two-way)
        $vehicle_no = encryptVehicleNo($vehicle_no_raw);

        // Hash owner name (one-way)
        $owner_name = encryptOwnerName($owner_name_raw);

        // Greedy Algorithm: Pick the first available slot
        $slotQuery = $conn->query("SELECT slot_id, slot_name FROM parking_slots WHERE status = 'Available' ORDER BY slot_id ASC LIMIT 1");

        if ($slotQuery->num_rows > 0) {
            $slotData = $slotQuery->fetch_assoc();
            $slot_id = $slotData['slot_id'];
            $slot_name = $slotData['slot_name'];

            // Insert encrypted vehicle number, hashed owner name, duration, entry & exit time
            $insert = $conn->prepare("INSERT INTO vehicles (vehicle_no, owner_name, vehicle_type, entry_time, exit_time, slot_id, duration_hours, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'In Lot')");
            $insert->bind_param("sssssis", $vehicle_no, $owner_name, $vehicle_type, $entry_time, $exit_time, $slot_id, $duration_hours);

            if ($insert->execute()) {
                $insert->close();

                // Update slot status to 'Occupied'
                $update = $conn->prepare("UPDATE parking_slots SET status = 'Occupied' WHERE slot_id = ?");
                $update->bind_param("i", $slot_id);
                $update->execute();
                $update->close();

                // Redirect to dashboard with success message
                header("Location: index.php?success=" . urlencode("Vehicle added to slot $slot_name for $duration_hours hour(s). Exit time: $exit_time"));
                exit;
            } else {
                $message = "Error inserting vehicle: " . $insert->error;
            }
        } else {
            $message = "No available slots!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Vehicle Entry</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    :root {
      --sidebar-blue: #1e3a8a;
      --primary-blue: #3b82f6;
      --card-bg: #f1f5f9;
      --text-black: #000000;
      --white: #ffffff;
      --success-green: #16a34a;
      --warning-yellow: #fde68a;
      --danger-red: #f87171;
    }
    body {
      background-color: var(--sidebar-blue);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      color: var(--text-black);
      margin: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      overflow-x: hidden;
    }
    .container { width: 100%; padding: 30px 15px; }
    .form-container {
      max-width: 650px;
      margin: auto;
      background-color: var(--card-bg);
      padding: 40px;
      border-radius: 16px;
      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
      animation: fadeSlideIn 0.8s ease-out;
    }
    @keyframes fadeSlideIn {
      0% { opacity: 0; transform: translateY(30px); }
      100% { opacity: 1; transform: translateY(0); }
    }
    h3 { color: var(--primary-blue); font-weight: 700; }
    label.form-label { color: var(--text-black); font-weight: 500; }
    .form-control, .form-select {
      background-color: var(--white);
      border: 1px solid #cbd5e1;
      color: var(--text-black);
      border-radius: 10px;
      transition: box-shadow 0.3s ease;
    }
    .form-control::placeholder { color: #94a3b8; }
    .form-control:focus, .form-select:focus {
      border-color: var(--primary-blue);
      background-color: #fff;
      box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
    }
    .btn-primary {
      background-color: var(--primary-blue);
      border: none;
      color: var(--white);
      font-weight: 600;
      padding: 10px 20px;
      border-radius: 8px;
      transition: background-color 0.3s ease, transform 0.3s ease;
    }
    .btn-primary:hover {
      background-color: var(--sidebar-blue);
      transform: translateY(-2px);
    }
    .btn-secondary {
      background-color: transparent;
      color: var(--text-black);
      border: 1px solid #cbd5e1;
      border-radius: 8px;
      font-weight: 500;
      transition: background-color 0.3s ease, color 0.3s ease;
    }
    .btn-secondary:hover {
      background-color: var(--primary-blue);
      color: var(--white);
      border-color: var(--primary-blue);
    }
    .alert { border-radius: 12px; font-weight: 500; padding: 14px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); animation: fadeIn 0.5s ease-in-out; }
    .alert-danger { background-color: var(--danger-red); color: var(--white); }
    .alert-warning { background-color: var(--warning-yellow); color: var(--text-black); }
    @keyframes fadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
    </style>
</head>
<body>

<div class="container">
    <div class="form-container">
        <h3 class="mb-4 text-center">Add Vehicle Entry</h3>

        <?php if ($message): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="post" action="">
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

            <div class="mb-3">
                <label class="form-label">Parking Duration (Hours)</label>
                <input type="number" class="form-control" name="duration_hours" min="1" max="24" placeholder="Enter hours (1–24)" required>
            </div>

            <div class="d-flex justify-content-between">
                <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
                <button type="submit" class="btn btn-primary">Add Vehicle</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
