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
include ("index.html");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Vehicle Entry</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
:root{
  --body-bg: linear-gradient(135deg, #eef2ff 0%, #f8fafc 50%, #ffffff 100%);
  --sidebar-blue: #1e3a8a;
  --primary-blue: #2563eb;
  --primary-blue-2: #3b82f6;
  --card-bg: #ffffff;
  --muted: #6b7280;
  --text-black: #0f172a;
  --white: #ffffff;
  --danger-red: #ef4444;
  --success-green: #16a34a;
  --glass: rgba(255,255,255,0.6);
  --shadow-1: 0 8px 24px rgba(16,24,40,0.08);
  --shadow-2: 0 20px 50px rgba(2,6,23,0.12);
  --radius: 14px;
  --transition-fast: 180ms;
  --transition: 300ms;
}

/* Page */
body {
  margin: 0;
  min-height: 100vh;
  font-family: "Inter", ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
  color: var(--text-black);
  background: var(--body-bg);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 32px 16px;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

/* Container */
.container {
  width: 100%;
  max-width: 980px;
  padding: 24px;
}

/* Card */
.form-container {
  background: linear-gradient(180deg, rgba(255,255,255,0.9), var(--card-bg));
  backdrop-filter: blur(6px) saturate(120%);
  border-radius: var(--radius);
  padding: 36px;
  margin: 0 auto;
  box-shadow: var(--shadow-2);
  max-width: 680px;
  transform: translateY(12px);
  opacity: 0;
  animation: enterUp 520ms var(--transition-fast) cubic-bezier(.2,.9,.2,1) forwards;
  border: 1px solid rgba(37,99,235,0.06);
}

/* Heading */
h3 {
  color: var(--primary-blue);
  font-weight: 700;
  margin-bottom: 0.75rem;
  letter-spacing: -0.2px;
}

/* Labels and text */
label.form-label {
  font-weight: 600;
  color: #0f172a;
  display: block;
  margin-bottom: 6px;
}

/* Inputs */
.form-control,
.form-select {
  width: 100%;
  padding: 10px 12px;
  border-radius: 10px;
  border: 1px solid rgba(15,23,42,0.08);
  background: linear-gradient(180deg, #fff, #fbfdff);
  transition: box-shadow var(--transition) ease, border-color var(--transition) ease, transform var(--transition) ease;
  outline: none;
  color: var(--text-black);
  box-shadow: var(--shadow-1);
  font-size: 0.95rem;
}

/* Focus state */
.form-control:focus,
.form-select:focus {
  transform: translateY(-2px);
  border-color: rgba(37,99,235,0.95);
  box-shadow: 0 6px 20px rgba(37,99,235,0.12);
}

/* HTML5 valid/invalid styling (complimentary to JS validation) */
.form-control:valid {
  border-color: rgba(16,185,129,0.9);
  box-shadow: 0 6px 20px rgba(16,185,129,0.08);
}
.form-control:invalid:focus {
  border-color: rgba(239,68,68,0.95);
  box-shadow: 0 6px 20px rgba(239,68,68,0.08);
}

/* Helper messages */
.error-msg, .valid-msg {
  font-size: 0.9rem;
  margin-top: 6px;
  transition: opacity var(--transition-fast) ease, transform var(--transition-fast) ease;
  opacity: 0;
  transform: translateY(-4px);
  display: block; /* keep layout stable; JS toggles visibility via display:block/none, but we keep block to allow transitions */
  height: 0;
  overflow: hidden;
  color: var(--muted);
}

/* When JS sets display block, we reveal with class-like CSS by checking height auto isn't possible; use inline style toggling still works.
   To ensure the messages animate when shown, we'll use a simple approach below using attribute selectors for "required" validation or :not(:placeholder-shown)
   which will play nicely for most browsers. */
.form-control:focus + .error-msg,
.form-control:not(:placeholder-shown) + .error-msg,
.form-control:valid + .valid-msg,
.form-control:focus + .valid-msg {
  opacity: 1;
  transform: translateY(0);
  height: auto;
}

/* Explicit coloring */
.error-msg { color: var(--danger-red); }
.valid-msg { color: var(--success-green); }

/* Buttons */
.btn {
  padding: 10px 16px;
  border-radius: 10px;
  border: none;
  font-weight: 600;
  transition: transform var(--transition-fast), box-shadow var(--transition-fast), opacity var(--transition-fast);
}
.btn:active { transform: translateY(1px); }

.btn-primary {
  background: linear-gradient(90deg, var(--primary-blue), var(--primary-blue-2));
  color: var(--white);
  box-shadow: 0 10px 30px rgba(37,99,235,0.18);
}
.btn-primary:hover {
  transform: translateY(-3px) scale(1.01);
  box-shadow: 0 18px 40px rgba(37,99,235,0.22);
}
.btn-secondary {
  background: linear-gradient(90deg, #f3f4f6, #ffffff);
  color: #0f172a;
  border: 1px solid rgba(15,23,42,0.06);
  box-shadow: 0 6px 20px rgba(2,6,23,0.04);
}

/* Layout helpers */
.d-flex { display:flex; }
.justify-content-between { justify-content: space-between; gap:12px; align-items:center; }

/* Responsive tweaks */
@media (max-width: 520px) {
  .form-container { padding: 22px; border-radius: 12px; }
  .d-flex { flex-direction: column-reverse; gap: 10px; }
  .btn { width: 100%; }
}

/* Entrance animation */
@keyframes enterUp {
  from { transform: translateY(18px); opacity: 0; filter: blur(6px); }
  to   { transform: translateY(0); opacity: 1; filter: blur(0); }
}

/* Subtle floating shadow for card (infinite slow) */
@keyframes floatShadow {
  0% { box-shadow: var(--shadow-2); transform: translateY(0); }
  50% { box-shadow: 0 28px 60px rgba(2,6,23,0.08); transform: translateY(-3px); }
  100% { box-shadow: var(--shadow-2); transform: translateY(0); }
}
.form-container { animation: enterUp 520ms cubic-bezier(.2,.9,.2,1) forwards, floatShadow 8s ease-in-out infinite; opacity: 1; }

/* Small shake for invalid fields when attempting submit - will activate by adding 'shake' to the input's parent in JS if desired */
@keyframes shake {
  10%, 90% { transform: translateX(-1px); }
  20%, 80% { transform: translateX(2px); }
  30%, 50%, 70% { transform: translateX(-4px); }
  40%, 60% { transform: translateX(4px); }
}
.form-control.shake { animation: shake 420ms cubic-bezier(.36,.07,.19,.97); border-color: var(--danger-red); }

/* Tiny accessibility improvement */
.form-control:focus-visible {
  outline: 3px solid rgba(37,99,235,0.12);
  outline-offset: 3px;
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
