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

    .card-title {
        font-weight: 600;
        font-size: 1.5rem;
        color: #D3D9D4;
    }

    .form-label {
        font-weight: 500;
        color: #D3D9D4;
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

    .alert-danger {
        background-color: rgba(248, 113, 113, 0.2);
        color: #F87171;
        border: 1px solid rgba(248, 113, 113, 0.4);
        border-radius: 10px;
    }

    .alert-success {
        background-color: rgba(34, 197, 94, 0.2);
        color: #22c55e;
        border: 1px solid rgba(34, 197, 94, 0.4);
        border-radius: 10px;
    }

    .text-muted {
        color: #748D92 !important;
    }
</style>


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
