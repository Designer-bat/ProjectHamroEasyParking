    <?php
    // Database configuration
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'parking_system');

    // Establish database connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Check connection with proper error handling
    if ($conn->connect_error) {
        error_log("Connection failed: " . $conn->connect_error);
        die("Database connection error. Please try again later.");
    }

    // Process exit action
    if (isset($_POST['exit']) && ctype_digit($_POST['exit'])) {
        $vehicle_id = intval($_POST['exit']);
        
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("SELECT entry_time, slot_id FROM vehicles WHERE vehicle_id = ? AND status = 'In Lot' FOR UPDATE");
            $stmt->bind_param("i", $vehicle_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();
                $entry_time = $row['entry_time'];
                $slot_id = $row['slot_id'];
                $exit_time = date('Y-m-d H:i:s');
                
                $duration_seconds = strtotime($exit_time) - strtotime($entry_time);
                $duration_hours = ceil($duration_seconds / 3600);
                $charges = $duration_hours * 10;

                $update_vehicle = $conn->prepare("UPDATE vehicles SET exit_time = ?, duration = ?, charges = ?, status = 'Exited' WHERE vehicle_id = ?");
                $update_vehicle->bind_param("siii", $exit_time, $duration_hours, $charges, $vehicle_id);
                $update_vehicle->execute();
                
                $update_slot = $conn->prepare("UPDATE parking_slots SET status = 'Available' WHERE slot_id = ?");
                $update_slot->bind_param("i", $slot_id);
                $update_slot->execute();
                
                $conn->commit();
            }
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Transaction failed: " . $e->getMessage());
        }
        
        header("Location: " . filter_var($_SERVER['PHP_SELF'], FILTER_SANITIZE_URL));
        exit;
    }

    // Process delete action
    if (isset($_POST['delete']) && ctype_digit($_POST['delete'])) {
        $vehicle_id = intval($_POST['delete']);
        
        $stmt = $conn->prepare("DELETE FROM vehicles WHERE vehicle_id = ? AND status = 'Exited'");
        $stmt->bind_param("i", $vehicle_id);
        $stmt->execute();
        
        header("Location: " . filter_var($_SERVER['PHP_SELF'], FILTER_SANITIZE_URL));
        exit;
    }

    // Fetch parking history
    $stmt = $conn->prepare("SELECT * FROM vehicles ORDER BY entry_time DESC");
    $stmt->execute();
    $vehicles = $stmt->get_result();

    // Handle query errors
    if ($vehicles === false) {
        die("Error fetching parking history. Please try again later.");
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parking History</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
        background-color: #1e3a8a;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        margin: 0;
        padding: 20px;
        color: #212529;
        }

        .container {
        max-width: 1200px;
        margin: 0 auto;
        }

        .card {
        background: #ffffff;
        border-radius: 10px;
        padding: 25px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        }

        .card-header {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 20px;
        border-bottom: 1px solid #e9ecef;
        padding-bottom: 15px;
        }

        .card-header i {
        font-size: 2rem;
        color: #3498db;
        background: rgba(52,152,219,0.1);
        border-radius: 50%;
        width: 55px;
        height: 55px;
        display: flex;
        align-items: center;
        justify-content: center;
        }

        .card-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: #2c3e50;
        }

        .card-description {
        font-size: 0.9rem;
        color: #6c757d;
        }

        .table-container {
        overflow-x: auto;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        table {
        width: 100%;
        border-collapse: collapse;
        }

        thead {
        background-color: #1e293b;
        color: #ffffff;
        }

        th, td {
        padding: 14px 18px;
        text-align: left;
        font-size: 0.9rem;
        border-bottom: 1px solid #e9ecef;
        }

        tbody tr:hover {
        background-color: #f9fafb;
        }

        .status-badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-weight: 500;
        font-size: 0.8rem;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        }

        .status-in-lot {
        background-color: rgba(40, 167, 69, 0.15);
        color: #28a745;
        }

        .status-exited {
        background-color: rgba(108, 117, 125, 0.15);
        color: #6c757d;
        }

        .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background-color: #1e293b;
        color: #fff;
        padding: 10px 18px;
        border-radius: 6px;
        font-size: 0.9rem;
        text-decoration: none;
        margin-top: 15px;
        transition: all 0.3s ease;
        }

        .btn-back:hover {
        background-color: #334155;
        }

        .empty-state {
        text-align: center;
        padding: 50px 0;
        color: #6c757d;
        }

        .empty-state i {
        font-size: 2.5rem;
        color: #adb5bd;
        margin-bottom: 10px;
        }

        @media (max-width: 768px) {
        th, td { padding: 10px; font-size: 0.85rem; }
        .card { padding: 15px; }
        }
    </style>
    </head>
    <body>
    <div class="container">
        <div class="card">
        <div class="card-header">
            <i class="fas fa-car"></i>
            <div>
            <h2 class="card-title">Vehicle Parked Records</h2>
            <p class="card-description">All vehicles currently parked in the facility</p>
            </div>
        </div>

        <div class="table-container">
            <table>
            <thead>
                <tr>
                <th>Vehicle No</th>
                <th>Owner Name</th>
                <th>Entry Time</th>
                <th>Exit Time</th>
                <th>Duration (hrs)</th>
                <th>Charges (₹)</th>
                <th>Status</th>
                </tr>
            </thead>
            <tbody>
    <?php if ($vehicles && $vehicles->num_rows > 0): ?>
    <?php while($row = $vehicles->fetch_assoc()): ?>
        <tr>
        <td>
            <?php 
            echo (preg_match('/^[A-Za-z0-9+\/=]+$/', $row['vehicle_no']) && strlen($row['vehicle_no']) > 20)
                ? 'Hidden Content'
                : htmlspecialchars($row['vehicle_no']);
            ?>
        </td>
        <td>
            <?php 
            echo (preg_match('/^[A-Za-z0-9+\/=]+$/', $row['owner_name']) && strlen($row['owner_name']) > 20)
                ? 'Hidden Content'
                : htmlspecialchars($row['owner_name']);
            ?>
        </td>
        <td><?= !empty($row['entry_time']) ? date('M d, Y H:i', strtotime($row['entry_time'])) : '-' ?></td>
        <td><?= !empty($row['exit_time']) ? date('M d, Y H:i', strtotime($row['exit_time'])) : '-' ?></td>
        <td><?= !empty($row['duration']) ? $row['duration'] : '-' ?></td>
        <td><?= !empty($row['charges']) ? '₹' . $row['charges'] : '-' ?></td>
        <td>
            <?php if ($row['status'] === 'In Lot'): ?>
            <span class="status-badge status-in-lot"><i class="fas fa-car"></i> In Lot</span>
            <?php else: ?>
            <span class="status-badge status-exited"><i class="fas fa-check-circle"></i> Exited</span>
            <?php endif; ?>
        </td>
        </tr>
    <?php endwhile; ?>
    <?php else: ?>
    <tr><td colspan="7" class="empty-state"><i class="fas fa-info-circle"></i><br>No Parking Records Found</td></tr>
    <?php endif; ?>
    </tbody>
            </table>
        </div>

        <a href="dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </div>
    </body>
    </html>
