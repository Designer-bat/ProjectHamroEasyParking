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
    $vehicle_type   = trim($conn->real_escape_string($_POST['vehicle_type']));
    $duration_hours = intval($_POST['duration_hours']); 
    $entry_time     = date('Y-m-d H:i:s');
    $exit_time      = date('Y-m-d H:i:s', strtotime("+$duration_hours hours", strtotime($entry_time)));

    // 🔹 Backend Validation
    if (!preg_match("/^[A-Z]{2}-[0-9]{1,2}-[A-Z]{1,2}-[0-9]{3,4}$/", $vehicle_no_raw)) {
        $message = "Invalid vehicle number format. Example: BA-2-PA-1234";
    } elseif (!preg_match("/^[a-zA-Z\s]{3,50}$/", $owner_name_raw)) {
        $message = "Owner name must be 3–50 characters and only letters.";
    } elseif ($duration_hours < 1 || $duration_hours > 24) {
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
            $slot_id  = $slotData['slot_id'];
            $slot_name = $slotData['slot_name'];

            // Insert vehicle data
            $insert = $conn->prepare("INSERT INTO vehicles (vehicle_no, owner_name, vehicle_type, entry_time, exit_time, slot_id, duration_hours, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'In Lot')");
            $insert->bind_param("sssssis", $vehicle_no, $owner_name, $vehicle_type, $entry_time, $exit_time, $slot_id, $duration_hours);

            if ($insert->execute()) {
                $insert->close();

                // Update slot status to 'Occupied'
                $update = $conn->prepare("UPDATE parking_slots SET status = 'Occupied' WHERE slot_id = ?");
                $update->bind_param("i", $slot_id);
                $update->execute();
                $update->close();

                // Redirect
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
  --danger-red: #f87171;
  --success-green: #16a34a;
}
body {
  background-color: var(--sidebar-blue);
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  margin: 0;
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
}
.container { width: 100%; padding: 30px 15px; }
.form-container {
  max-width: 650px;
  margin: auto;
  background-color: var(--card-bg);
  padding: 40px;
  border-radius: 16px;
  box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
}
h3 { color: var(--primary-blue); font-weight: 700; }
label.form-label { font-weight: 500; }
.form-control:valid { border: 2px solid var(--success-green); }
.form-control:invalid:focus { border: 2px solid var(--danger-red); }
.error-msg {
  color: var(--danger-red);
  font-size: 0.9rem;
  margin-top: 5px;
  display: none;
}
.valid-msg {
  color: var(--success-green);
  font-size: 0.9rem;
  margin-top: 5px;
  display: none;
}
</style>
</head>
<body>

<div class="container">
  <div class="form-container">
    <h3 class="mb-4 text-center">Add Vehicle Entry</h3>

    <?php if ($message): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post" action="" id="vehicleForm" novalidate>
      <div class="mb-3">
        <label class="form-label">Vehicle Number</label>
        <input type="text" class="form-control" name="vehicle_no" placeholder="BA-2-PA-1234" required>
        <div class="error-msg">Format must be like BA-2-PA-1234</div>
        <div class="valid-msg">Looks good!</div>
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
        <div class="error-msg">Please select a vehicle type</div>
        <div class="valid-msg">Looks good!</div>
      </div>

      <div class="mb-3">
        <label class="form-label">Owner Name</label>
        <input type="text" class="form-control" name="owner_name" placeholder="Owner full name" required>
        <div class="error-msg">Only letters (min 3 characters)</div>
        <div class="valid-msg">Looks good!</div>
      </div>

      <div class="mb-3">
        <label class="form-label">Parking Duration (Hours)</label>
        <input type="number" class="form-control" name="duration_hours" min="1" max="24" placeholder="Enter hours (1–24)" required>
        <div class="error-msg">Must be between 1 and 24 hours</div>
        <div class="valid-msg">Looks good!</div>
      </div>

      <div class="d-flex justify-content-between">
        <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
        <button type="submit" class="btn btn-primary">Add Vehicle</button>
      </div>
    </form>
  </div>
</div>

<script>
const form = document.getElementById("vehicleForm");

const validators = {
  vehicle_no: /^[A-Z]{2}-[0-9]{1,2}-[A-Z]{1,2}-[0-9]{3,4}$/,
  owner_name: /^[a-zA-Z\s]{3,50}$/
};

form.querySelectorAll(".form-control, .form-select").forEach(input => {
  input.addEventListener("input", () => validateField(input));
});

function validateField(input) {
  const errorMsg = input.parentElement.querySelector(".error-msg");
  const validMsg = input.parentElement.querySelector(".valid-msg");
  let valid = false;

  if (input.name === "vehicle_no") {
    valid = validators.vehicle_no.test(input.value.trim());
  } else if (input.name === "owner_name") {
    valid = validators.owner_name.test(input.value.trim());
  } else if (input.name === "duration_hours") {
    const val = parseInt(input.value);
    valid = val >= 1 && val <= 24;
  } else if (input.name === "vehicle_type") {
    valid = input.value !== "";
  }

  if (valid) {
    errorMsg.style.display = "none";
    validMsg.style.display = "block";
  } else {
    errorMsg.style.display = "block";
    validMsg.style.display = "none";
  }
  return valid;
}

// Prevent submission if invalid
form.addEventListener("submit", (e) => {
  let allValid = true;
  form.querySelectorAll(".form-control, .form-select").forEach(input => {
    if (!validateField(input)) allValid = false;
  });
  if (!allValid) e.preventDefault();
});
</script>

</body>
</html>
