<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "parking_system";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $slot_name = trim($_POST['slot_name']);
    
    if (empty($slot_name)) {
        $error = "Please enter a slot name.";
    } else {
        $stmt = $conn->prepare("INSERT INTO parking_slots (slot_name, status) VALUES (?, 'Available')");
        $stmt->bind_param("s", $slot_name);

        if ($stmt->execute()) {
            $success = "New parking slot added successfully!";
        } else {
            $error = "Error: " . $stmt->error;
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
    <title>Add New Parking Slot</title>
    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h3 class="card-title mb-4 text-center">Add New Parking Slot</h3>

                    <!-- Alerts -->
                    <?php if (!empty($error)) : ?>
                        <div class="alert alert-danger" role="alert">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success)) : ?>
                        <div class="alert alert-success" role="alert">
                            <?= htmlspecialchars($success) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Form -->
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="slot_name" class="form-label">Slot Name</label>
                            <input type="text" class="form-control" id="slot_name" name="slot_name" placeholder="Enter slot name (e.g., A1, B3)" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Add Slot</button>
                    </form>
                </div>
            </div>

            <div class="text-center mt-3 text-muted">
                <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS Bundle with Popper (optional for some components) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
