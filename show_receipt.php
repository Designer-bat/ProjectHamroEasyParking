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

// Include encryption/decryption + hashing functions
require_once 'config_secure.php';

$vehicle_number = "";
$data = null;
$error = "";

// Form submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $vehicle_number = trim($_POST['vehicle_no']);

    if (empty($vehicle_number)) {
        $error = "Please enter a vehicle number.";
    } else {
        // Encrypt the input to match DB stored value
        $encrypted_vehicle_no = encryptVehicleNo($vehicle_number);

        $stmt = $conn->prepare("SELECT * FROM vehicles WHERE vehicle_no = ? ORDER BY exit_time DESC LIMIT 1");
        $stmt->bind_param("s", $encrypted_vehicle_no);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $data = $result->fetch_assoc();

            // Decrypt vehicle number for display
            $data['vehicle_no'] = decryptVehicleNo($data['vehicle_no']);
            // Owner name remains hashed — cannot decrypt
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
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    :root {
      --sidebar-blue: #1e3a8a;
      --primary-blue: #3b82f6;
      --card-bg: #f1f5f9;
      --text-black: #000000;
      --white: #ffffff;
      --success-green: #10b981;
      --danger-red: #f87171;
      --muted-gray: #94a3b8;
    }
    body { background-color: var(--sidebar-blue); font-family: 'Segoe UI', sans-serif; color: var(--white); margin: 0; padding: 30px 10px; }
    .card { background-color: var(--card-bg); border-radius: 16px; box-shadow: 0 12px 25px rgba(0, 0, 0, 0.25); border: none; animation: fadeSlide 0.9s ease; }
    .card-body { padding: 30px; color: var(--text-black); }
    h3, h4 { color: var(--primary-blue); font-weight: 700; }
    .form-label { color: var(--text-black); font-weight: 500; }
    .form-control { background-color: var(--white); border: 1px solid #cbd5e1; color: var(--text-black); border-radius: 8px; }
    .form-control::placeholder { color: var(--muted-gray); }
    .form-control:focus { border-color: var(--primary-blue); box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25); }
    .btn-primary { background-color: var(--primary-blue); border: none; font-weight: 600; color: var(--white); transition: all 0.3s ease; }
    .btn-primary:hover { background-color: var(--sidebar-blue); }
    .btn-secondary { background-color: var(--sidebar-blue); color: var(--white); border: none; padding: 10px 20px; font-weight: 600; border-radius: 8px; }
    .btn-secondary:hover { background-color: var(--primary-blue); color: var(--white); }
    .btn-success { background-color: var(--success-green); border: none; font-weight: 600; padding: 10px 25px; margin-top: 10px; }
    .btn-success:hover { background-color: #059669; }
    .alert-danger { background-color: rgba(248, 113, 113, 0.2); color: var(--danger-red); border: 1px solid rgba(248, 113, 113, 0.4); border-radius: 10px; }
    table.table { color: var(--text-black); border-color: #cbd5e1; margin-top: 20px; animation: fadeIn 0.5s ease-in; }
    .table th { background-color: var(--primary-blue); color: var(--white); font-weight: 600; }
    .table td { background-color: rgba(0, 0, 0, 0.02); }
    @keyframes fadeSlide { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes fadeIn { from { opacity: 0; transform: scale(0.98); } to { opacity: 1; transform: scale(1); } }
  </style>
</head>
<body>

<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-lg-6 col-md-8">
      <div class="card shadow">
        <div class="card-body">
          <h3 class="text-center mb-4">Search Parking Receipt</h3>

          <form method="POST" action="">
            <div class="mb-3">
              <label for="vehicle_id" class="form-label">Vehicle Number</label>
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
              <tr><th>Owner Name</th><td><?= htmlspecialchars($data['owner_name']) ?> (hashed)</td></tr>
              <tr><th>Vehicle Type</th><td><?= htmlspecialchars($data['vehicle_type']) ?></td></tr>
              <tr><th>Slot ID</th><td><?= htmlspecialchars($data['slot_id']) ?></td></tr>
              <tr><th>Entry Time</th><td><?= htmlspecialchars($data['entry_time']) ?></td></tr>
              <tr><th>Exit Time</th><td><?= htmlspecialchars($data['exit_time']) ?></td></tr>
              <tr><th>Total Amount</th><td><?= htmlspecialchars($data['charges']) ?> NPR</td></tr>
            </table>

            <div class="text-center">
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
