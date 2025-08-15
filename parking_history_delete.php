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
if (isset($_GET['exit']) && ctype_digit($_GET['exit'])) {
    $vehicle_id = intval($_GET['exit']);
    
    // Start transaction for atomic operations
    $conn->begin_transaction();
    
    try {
        // Get vehicle details with row locking
        $stmt = $conn->prepare("SELECT entry_time, slot_id FROM vehicles WHERE vehicle_id = ? AND status = 'In Lot' FOR UPDATE");
        $stmt->bind_param("i", $vehicle_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            $entry_time = $row['entry_time'];
            $slot_id = $row['slot_id'];
            $exit_time = date('Y-m-d H:i:s');
            
            // Calculate duration and charges
            $duration_seconds = strtotime($exit_time) - strtotime($entry_time);
            $duration_hours = ceil($duration_seconds / 3600);
            $charges = $duration_hours * 10;

            // Update vehicle record
            $update_vehicle = $conn->prepare("UPDATE vehicles SET exit_time = ?, duration = ?, charges = ?, status = 'Exited' WHERE vehicle_id = ?");
            $update_vehicle->bind_param("siii", $exit_time, $duration_hours, $charges, $vehicle_id);
            $update_vehicle->execute();
            
            // Update parking slot status
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
if (isset($_GET['delete']) && ctype_digit($_GET['delete'])) {
    $vehicle_id = intval($_GET['delete']);
    
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
        :root {
            --primary-blue: #2c3e50;
            --secondary-blue: #3498db;
            --accent-blue: #1a6ca6;
            --light-blue: #ecf0f1;
            --white: #ffffff;
            --black: #212529;
            --light-gray: #f8f9fa;
            --medium-gray: #e9ecef;
            --dark-gray: #6c757d;
            --success: #28a745;
            --error: #dc3545;
            --warning: #ffc107;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, var(--light-blue) 0%, #d6e4f0 100%);
            color: var(--black);
            line-height: 1.6;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        header {
            background: var(--primary-blue);
            color: var(--white);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-content {
            flex: 1;
        }

        h1 {
            font-size: 2.2rem;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        h1 i {
            color: var(--secondary-blue);
        }

        .subtitle {
            font-size: 1rem;
            color: #a0c7e4;
            font-weight: 400;
        }

        .card {
            background: var(--white);
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--medium-gray);
        }

        .card-header i {
            font-size: 1.8rem;
            color: var(--secondary-blue);
            background: rgba(52, 152, 219, 0.1);
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card-title {
            font-size: 1.5rem;
            color: var(--primary-blue);
        }

        .card-description {
            color: var(--dark-gray);
            margin-top: 5px;
            font-size: 0.95rem;
        }

        .table-container {
            overflow-x: auto;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }

        th, td {
            padding: 16px 20px;
            text-align: left;
            border-bottom: 1px solid var(--medium-gray);
        }

        thead {
            background: var(--primary-blue);
            color: var(--white);
        }

        thead th {
            font-weight: 600;
            padding: 18px 20px;
        }

        tbody tr {
            transition: var(--transition);
        }

        tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.03);
        }

        .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.85rem;
            display: inline-block;
        }

        .status-in-lot {
            background-color: rgba(40, 167, 69, 0.15);
            color: var(--success);
        }

        .status-exited {
            background-color: rgba(108, 117, 125, 0.15);
            color: var(--dark-gray);
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-exit {
            background: var(--error);
            color: var(--white);
        }

        .btn-exit:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .btn-delete {
            background: var(--dark-gray);
            color: var(--white);
        }

        .btn-delete:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .btn-back {
            background: var(--primary-blue);
            color: var(--white);
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
            transition: var(--transition);
            text-decoration: none;
        }

        .btn-back:hover {
            background: var(--accent-blue);
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: var(--dark-gray);
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--medium-gray);
            margin-bottom: 15px;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: var(--primary-blue);
        }

        .charges {
            font-weight: 700;
            color: var(--primary-blue);
        }

        @media (max-width: 768px) {
            header {
                flex-direction: column;
                gap: 20px;
            }
            
            .card {
                padding: 20px;
            }
            
            th, td {
                padding: 12px 15px;
            }
            
            thead th {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">    
        <div class="card">
            <div class="card-header">
                <i class="fas fa-car"></i>
                <div>
                    <h2 class="card-title">Parking History</h2>
                    <p class="card-description">All vehicles previously parked in the facility</p>
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
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($vehicles->num_rows > 0): ?>
                            <?php while($row = $vehicles->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['vehicle_no']) ?></strong></td>
                                <td><?= htmlspecialchars($row['owner_name']) ?></td>
                                <td><?= date('M d, Y H:i', strtotime($row['entry_time'])) ?></td>
                                <td><?= $row['exit_time'] ? date('M d, Y H:i', strtotime($row['exit_time'])) : '-' ?></td>
                                <td><?= $row['duration'] ?? '-' ?></td>
                                <td class="charges"><?= $row['charges'] ? '₹' . $row['charges'] : '-' ?></td>
                                <td>
                                    <?php if ($row['status'] === 'In Lot'): ?>
                                        <span class="status-badge status-in-lot">
                                            <i class="fas fa-car"></i> In Lot
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge status-exited">
                                            <i class="fas fa-check-circle"></i> Exited
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['status'] === 'In Lot'): ?>
                                        <a href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>?exit=<?= $row['vehicle_id'] ?>"
                                           class="btn btn-exit"
                                           onclick="return confirm('Mark vehicle <?= htmlspecialchars($row['vehicle_no']) ?> as exited?')">
                                           <i class="fas fa-sign-out-alt"></i> Exit
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>?delete=<?= $row['vehicle_id'] ?>"
                                           class="btn btn-delete"
                                           onclick="return confirm('Permanently delete record for <?= htmlspecialchars($row['vehicle_no']) ?>?')">
                                           <i class="fas fa-trash-alt"></i> Delete
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <i class="fas fa-inbox"></i>
                                        <h3>No Parking Records Found</h3>
                                        <p>There are no vehicles in the parking history yet.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <a href="index.php" class="btn btn-back">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <script>
        // Add fade-in animation to table rows
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach((row, index) => {
                setTimeout(() => {
                    row.style.opacity = '0';
                    row.style.animation = 'fadeIn 0.5s forwards';
                    row.style.animationDelay = (index * 0.1) + 's';
                }, 100);
            });
            
            // Add animation for exit button clicks
            document.querySelectorAll('.btn-exit, .btn-delete').forEach(button => {
                button.addEventListener('click', function(e) {
                    if (!confirm(this.getAttribute('data-confirm'))) {
                        e.preventDefault();
                        return false;
                    }
                    
                    const row = this.closest('tr');
                    row.style.animation = 'fadeOut 0.5s forwards';
                    setTimeout(() => {
                        row.remove();
                    }, 500);
                });
            });
        });
        
        // Add CSS animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }
            
            @keyframes fadeOut {
                from { opacity: 1; transform: translateY(0); }
                to { opacity: 0; transform: translateY(10px); }
            }
            
            tbody tr {
                animation: fadeIn 0.5s forwards;
                opacity: 0;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>