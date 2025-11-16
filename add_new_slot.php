<?php
include("index.html");
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
            $result = $conn->query("SELECT COUNT(*) as total FROM parking_slots");
            $row = $result->fetch_assoc();
            $totalSlots = $row['total'];

            if ($totalSlots + $quantity > 100) {
                $addMessage = "<div class='alert error'><i class='fas fa-times-circle'></i> Cannot add $quantity slots. Maximum limit of 100 slots reached!</div>";
            } else {
                $result = $conn->query("SELECT slot_name FROM parking_slots ORDER BY slot_id DESC LIMIT 1");
                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    preg_match('/\d+/', $row['slot_name'], $matches);
                    $lastNumber = isset($matches[0]) ? intval($matches[0]) : 0;
                } else {
                    $lastNumber = 0;
                }

                for ($i = 1; $i <= $quantity; $i++) {
                    $newNumber = $lastNumber + $i;
                    $newSlotName = "S" . $newNumber;
                    $conn->query("INSERT INTO parking_slots (slot_name, status) VALUES ('$newSlotName', 'Available')");
                }

                $addMessage = "<div class='alert success'><i class='fas fa-check-circle'></i> $quantity new slot(s) added successfully!</div>";
            }
        } else {
            $addMessage = "<div class='alert error'><i class='fas fa-exclamation-circle'></i> Please enter a valid quantity for adding slots.</div>";
        }
    }

    // Handle slot deletion
    if (isset($_POST['delete_quantity'])) {
        $quantity = intval($_POST['delete_quantity']);

        if ($quantity > 0) {
            $result = $conn->query("SELECT COUNT(*) as total FROM parking_slots");
            $row = $result->fetch_assoc();
            $totalSlots = $row['total'];

            if ($quantity <= $totalSlots) {
                $conn->query("DELETE FROM parking_slots ORDER BY slot_id DESC LIMIT $quantity");
                $deleteMessage = "<div class='alert success'><i class='fas fa-check-circle'></i> $quantity slot(s) deleted successfully!</div>";
            } else {
                $deleteMessage = "<div class='alert error'><i class='fas fa-exclamation-circle'></i> Cannot delete more slots than available ($totalSlots available).</div>";
            }
        } else {
            $deleteMessage = "<div class='alert error'><i class='fas fa-exclamation-circle'></i> Please enter a valid quantity for deleting slots.</div>";
        }
    }
}

// Get current slot information
$result = $conn->query("SELECT COUNT(*) as total, 
SUM(CASE WHEN status = 'Available' THEN 1 ELSE 0 END) as available FROM parking_slots");
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
/* ======================= ROOT COLORS ======================= */
:root {
    --sidebar-blue: #1e3a8a;
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
    --body-bg: #f8f9fa;
    --anim-ease: cubic-bezier(.2,.9,.2,1);
}

/* ======================= GLOBAL STYLES ======================= */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

body {
    background-color: var(--body-bg);
    color: var(--black);
    line-height: 1.6;
    min-height: 100vh;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    display: flex;
}

/* ======================= SIDEBAR ======================= */
.sidebar {
    position: fixed;
    top: 0; left: 0;
    height: 100%;
    width: 240px;
    background-color: var(--sidebar-blue);
    padding: 20px 0;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    color: white;
    z-index: 1000;
}

.sidebar .logo {
    text-align: center;
    font-size: 1.3rem;
    font-weight: bold;
    color: #ffffff;
    margin-bottom: 20px;
}

.sidebar .nav-menu {
    list-style: none;
    padding: 0;
    margin: 0 0 20px 0;
}

.sidebar .nav-menu li a {
    color: #f0f0f0;
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    padding: 12px 24px;
    transition: background 0.3s;
}

.sidebar .nav-menu li a:hover {
    background-color: #2563eb;
}

.sidebar .icon {
    width: 20px;
    text-align: center;
}

/* ======================= MAIN CONTAINER ======================= */
.container {
    margin-left: 240px;
    width: 100%;
    padding: 40px;
}

/* ======================= ACTION CARD ======================= */
.actions-container {
    display: grid;
    grid-template-columns: 1fr;
    gap: 30px;
    margin-bottom: 30px;
}

.action-card {
    background: var(--white);
    border-radius: 10px;
    padding: 30px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    transition: transform 320ms var(--anim-ease), box-shadow 320ms var(--anim-ease);
}

.action-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
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

/* ======================= FORM ======================= */
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
    transition: box-shadow 300ms var(--anim-ease), border-color 300ms var(--anim-ease), transform 200ms var(--anim-ease);
}

input[type="number"]:focus {
    border-color: var(--secondary-blue);
    outline: none;
    box-shadow: 0 6px 20px rgba(52, 152, 219, 0.15);
}

/* ======================= BUTTONS ======================= */
.btn {
    display: inline-block;
    padding: 12px 28px;
    font-size: 1rem;
    font-weight: 600;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    transition: all 0.3s var(--anim-ease);
    background: var(--medium-gray);
    color: var(--primary-blue);
}

.btn-primary {
    background: var(--secondary-blue);
    color: var(--white);
}

.btn-primary:hover {
    background: var(--accent-blue);
    transform: translateY(-4px);
}

.btn-warning {
    background: var(--warning);
    color: var(--black);
}

.btn-warning:hover {
    background: #ffb300;
}

/* ======================= ALERTS ======================= */
.alert {
    padding: 15px;
    border-radius: 8px;
    margin-top: 20px;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    gap: 12px;
}

.alert.success {
    background: rgba(40, 167, 69, 0.1);
    color: var(--success);
    border: 1px solid rgba(40, 167, 69, 0.2);
}

.alert.error {
    background: rgba(220, 53, 69, 0.1);
    color: var(--error);
    border: 1px solid rgba(220, 53, 69, 0.2);
}

.alert.warning {
    background: rgba(255, 193, 7, 0.15);
    color: #856404;
    border: 1px solid rgba(255, 193, 7, 0.2);
}

/* ======================= FOOTER ======================= */
footer {
    text-align: center;
    padding: 25px;
    color: var(--dark-gray);
    font-size: 0.9rem;
}
</style>
</head>
<body>
<!-- MAIN CONTENT -->
<div class="container">
    <div class="actions-container">
        <div class="action-card">
            <div class="card-header">
                <i class="fas fa-plus-circle"></i>
                <div>
                    <h2 class="card-title">Add New Slots</h2>
                    <p class="card-description">Add new parking slots to the system</p>
                </div>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label for="add_quantity">Number of slots to add</label>
                    <input type="number" id="add_quantity" name="add_quantity" min="1" required>
                    <div class="alert warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Note: You cannot exceed the maximum limit of <b>100 slots</b>.
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Slots
                </button>

                <?php if (isset($addMessage)) echo $addMessage; ?>
            </form>
        </div>

        <div style="text-align:center;">
            <a href="index.php" class="btn btn-primary mt-3">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>
</body>
</html>
