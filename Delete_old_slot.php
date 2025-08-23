<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "parking_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle slot addition
    if (isset($_POST['add_quantity'])) {
        $quantity = intval($_POST['add_quantity']);
        
        if ($quantity > 0) {
            // Get last slot ID from database
            $result = $conn->query("SELECT slot_name FROM parking_slots ORDER BY slot_id DESC LIMIT 1");
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                preg_match('/\d+/', $row['slot_name'], $matches);
                $lastNumber = isset($matches[0]) ? intval($matches[0]) : 0;
            } else {
                $lastNumber = 0; // If no slots exist yet
            }

            // Insert new slots
            for ($i = 1; $i <= $quantity; $i++) {
                $newNumber = $lastNumber + $i;
                $newSlotName = "S" . $newNumber;
                $conn->query("INSERT INTO parking_slots (slot_name, status) VALUES ('$newSlotName', 'Available')");
            }

            $addMessage = "<div class='alert success'>$quantity new slot(s) added successfully!</div>";
        } else {
            $addMessage = "<div class='alert error'>Please enter a valid quantity for adding slots.</div>";
        }
    }
    
    // Handle slot deletion
    if (isset($_POST['delete_quantity'])) {
        $quantity = intval($_POST['delete_quantity']);
        
        if ($quantity > 0) {
            // Get total slots count
            $result = $conn->query("SELECT COUNT(*) as total FROM parking_slots");
            $row = $result->fetch_assoc();
            $totalSlots = $row['total'];
            
            if ($quantity <= $totalSlots) {
                // Delete the specified number of slots (starting from the highest IDs)
                $conn->query("DELETE FROM parking_slots ORDER BY slot_id DESC LIMIT $quantity");
                
                $deleteMessage = "<div class='alert success'>$quantity slot(s) deleted successfully!</div>";
            } else {
                $deleteMessage = "<div class='alert error'>Cannot delete more slots than available ($totalSlots available).</div>";
            }
        } else {
            $deleteMessage = "<div class='alert error'>Please enter a valid quantity for deleting slots.</div>";
        }
    }
}

// Get current slot information
$result = $conn->query("SELECT COUNT(*) as total, 
                        SUM(CASE WHEN status = 'Available' THEN 1 ELSE 0 END) as available 
                        FROM parking_slots");
$slotData = $result->fetch_assoc();
$totalSlots = $slotData['total'] ?? 0;
$availableSlots = $slotData['available'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parking Slot Management</title>
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
           background-color: #1e3a8a;
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

        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--white);
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        }

        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: var(--secondary-blue);
        }

        .stat-title {
            font-size: 1.1rem;
            color: var(--dark-gray);
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--primary-blue);
        }

        .actions-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }

        .action-card {
            background: var(--white);
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
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

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--primary-blue);
            font-size: 1rem;
        }

        input[type="number"] {
            width: 100%;
            padding: 14px;
            border: 2px solid var(--medium-gray);
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
        }

        input[type="number"]:focus {
            border-color: var(--secondary-blue);
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        .btn-group {
            display: flex;
            gap: 15px;
            margin-top: 10px;
        }

        .btn {
            padding: 14px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-primary {
            background: var(--secondary-blue);
            color: var(--white);
            flex: 1;
        }

        .btn-primary:hover {
            background: var(--accent-blue);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: var(--light-gray);
            color: var(--primary-blue);
            border: 2px solid var(--medium-gray);
        }

        .btn-secondary:hover {
            background: var(--medium-gray);
            transform: translateY(-2px);
        }

        .btn-warning {
            background: var(--warning);
            color: var(--black);
        }

        .btn-warning:hover {
            background: #e00000ff;
            transform: translateY(-2px);
        }

        .alert {    
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert i {
            font-size: 1.3rem;
        }

        .success {
            background: rgba(40, 167, 69, 0.1);
            color: var(--success);
            border: 1px solid rgba(40, 167, 69, 0.2);
        }

        .error {
            background: rgba(220, 53, 69, 0.1);
            color: var(--error);
            border: 1px solid rgba(220, 53, 69, 0.2);
        }

        .warning {
            background: rgba(255, 193, 7, 0.15);
            color: #856404;
            border: 1px solid rgba(255, 193, 7, 0.2);
        }

        footer {
            text-align: center;
            padding: 25px;
            color: var(--dark-gray);
            font-size: 0.9rem;
            margin-top: 30px;
        }

        @media (max-width: 768px) {
            .actions-container {
                grid-template-columns: 1fr;
            }
            
            .dashboard-stats {
                grid-template-columns: 1fr;
            }
            
            header {
                flex-direction: column;
                gap: 20px;
            }
            
            .btn-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>  
    <div class="container">
        <div class="actions-container">
            <div class="action-card">
                <div class="card-header">
                    <i class="fas fa-minus-circle"></i>
                    <div>
                        <h2 class="card-title">Delete Existing Slots</h2>
                        <p class="card-description">Remove parking slots from the system</p>
                    </div>
                </div>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="delete_quantity">Number of slots to delete</label>
                        <input type="number" id="delete_quantity" name="delete_quantity" min="1" required>
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-trash-alt"></i> Delete Slots
                        </button>
                    </div>
                    
                    <?php if (isset($deleteMessage)) echo $deleteMessage; ?>
                    
                    <div class="alert warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Note: This will delete the most recently added slots first
                    </div>
                </form>
            </div>
        </div>
    </div>
        <a href="index.php" class="btn btn-primary">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
</body>
</html>