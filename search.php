<?php
include 'Aiindex.php';

// Enable errors for development
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Redirect if admin not logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit;
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'parking_system');
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Safe decode function (not currently used but prepared)
function safe_decode($data) {
    $decoded = base64_decode($data, true);
    return $decoded !== false ? $decoded : $data;
}

// Initialize variables
$results = [];
$query_text = "";

// Search processing
if (!empty($_GET['q'])) {
    $query_text = strtolower(trim($_GET['q']));

    $stmt = $conn->prepare("
        SELECT vehicle_no, vehicle_id, owner_name, slot_id, entry_time, exit_time,
               duration_hours, charges, status, vehicle_type
        FROM vehicles
        WHERE LOWER(CONCAT(
            IFNULL(vehicle_no,''),' ',
            IFNULL(vehicle_id,''),' ',
            IFNULL(owner_name,''),' ',
            IFNULL(slot_id,''),' ',
            IFNULL(vehicle_type,''),' ',
            IFNULL(charges,''),' ',
            IFNULL(status,'')
        )) LIKE CONCAT('%', ?, '%')
        ORDER BY entry_time DESC
        LIMIT 50
    ");

    $stmt->bind_param("s", $query_text);
    $stmt->execute();
    $results = $stmt->get_result();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Parking Records</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background: #eef2f7;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .search-card {
            width: 1000px;
            height: 200px;
            margin-top: 50px;
            padding: 30px;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        
        }
        .table-container {
            width: 1000px;
            height: 200px;
            margin-top: 50px;
            margin-top: 30px;
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead th {
            background: #0f172a;
            color: #fff;
            text-align: center;
        }
        tbody td {
            text-align: center;
        }
        .btn-back {
            display: inline-block;
            margin-top: 20px;
            color: #fff;
            background: #3b82f6;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
        }
        .btn-back:hover {
            background: #2563eb;
        }
    </style>
</head>
<body>

<div class="container d-flex flex-column align-items-center">

    <!-- Search Card -->
    <div class="search-card">
        <h3 class="mb-4 text-center">🔍 Search Parking Entries</h3>
        <form method="GET" class="d-flex">
            <input type="text" name="q" value="<?= htmlspecialchars($query_text) ?>"
                   class="form-control me-2" placeholder="Search vehicle names..." required>
            <button type="submit" class="btn btn-primary">Search</button>
        </form>
    </div>

    <!-- Search Results -->
    <?php if ($query_text !== ""): ?>
        <div class="table-container">
            <h4 class="mb-3 text-center">Search Results</h4>
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Slot</th>
                        <th>Vehicle Type</th>
                        <th>Entry Time</th>
                        <th>Exit Time</th>
                        <th>Hours</th>
                        <th>Charges</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($results->num_rows > 0): ?>
                    <?php while ($row = $results->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['vehicle_id']) ?></td>
                            <td><?= htmlspecialchars($row['vehicle_type']) ?></td>
                            <td><?= htmlspecialchars($row['entry_time']) ?></td>
                            <td><?= htmlspecialchars($row['exit_time']) ?></td>
                            <td><?= htmlspecialchars($row['duration_hours']) ?></td>
                            <td><?= htmlspecialchars($row['charges']) ?></td>
                            <td><?= htmlspecialchars($row['status']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center text-danger">No matching entries found</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <a href="index.php" class="btn-back">← Back to Dashboard</a>
</div>

</body>
</html>
