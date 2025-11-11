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
            // Get current total slots
            $result = $conn->query("SELECT COUNT(*) as total FROM parking_slots");
            $row = $result->fetch_assoc();
            $totalSlots = $row['total'];

            // Check limit
            if ($totalSlots + $quantity > 100) {
                $addMessage = "<div class='alert error'><i class='fas fa-times-circle'></i> Cannot add $quantity slots. Maximum limit of 100 slots reached!</div>";
            } else {
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
            // Get total slots count
            $result = $conn->query("SELECT COUNT(*) as total FROM parking_slots");
            $row = $result->fetch_assoc();
            $totalSlots = $row['total'];
            
            if ($quantity <= $totalSlots) {
                // Delete the specified number of slots (starting from the highest IDs)
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
                        SUM(CASE WHEN status = 'Available' THEN 1 ELSE 0 END) as available 
                        FROM parking_slots");
$slotData = $result->fetch_assoc();
$totalSlots = $slotData['total'] ?? 0;
$availableSlots = $slotData['available'] ?? 0;
include ("index.html");
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
    --body-bg: #f8f9fa; /* Added body background variable */
    --anim-ease: cubic-bezier(.2,.9,.2,1);
}
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}
body {
   background-color:var(--body-bg); 
    color: var(--black);
    line-height: 1.6;
    min-height: 100vh;
    padding: 20px;
    -webkit-font-smoothing:antialiased;
    -moz-osx-font-smoothing:grayscale;
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
    animation: fadeInDown 500ms var(--anim-ease);
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
    display:inline-block;
    transform-origin:center;
    animation: icon-breathe 3s infinite;
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
    transition: transform 350ms var(--anim-ease), box-shadow 350ms var(--anim-ease);
    animation: fadeInUp 450ms var(--anim-ease);
}
.stat-card:hover {
    transform: translateY(-8px) scale(1.01);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
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
    display:inline-block;
    transform-origin:center;
    animation: popIn 600ms var(--anim-ease);
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
    transform-origin:center;
    transition: transform 320ms var(--anim-ease), box-shadow 320ms var(--anim-ease);
    animation: fadeInUp 500ms var(--anim-ease);
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
    transform: translateY(-2px);
    transition: transform 400ms var(--anim-ease);
}
.card-header:hover i {
    transform: translateY(-6px) rotate(-6deg);
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
    transition: box-shadow 300ms var(--anim-ease), border-color 300ms var(--anim-ease), transform 200ms var(--anim-ease);
    background: linear-gradient(180deg, #fff 0%, #fbfdff 100%);
}
input[type="number"]:focus {
    border-color: var(--secondary-blue);
    outline: none;
    box-shadow: 0 6px 20px rgba(52, 152, 219, 0.15);
    transform: translateY(-2px);
}

/* Added button styles for consistency */
.btn {
    display: inline-block;
    padding: 12px 28px;
    font-size: 1rem;
    font-weight: 600;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    transition: transform 220ms var(--anim-ease), box-shadow 220ms var(--anim-ease), background 220ms var(--anim-ease);
    background: var(--medium-gray);
    color: var(--primary-blue);
    text-decoration: none;
    will-change: transform;
}
.btn:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 18px rgba(0,0,0,0.06);
}
.btn:active {
    transform: translateY(0) scale(0.99);
}

.btn-primary {
    background: var(--secondary-blue);
    color: var(--white);
    position: relative;
    overflow: hidden;
}
.btn-primary:hover {
    background: var(--accent-blue);
    color: var(--white);
    transform: translateY(-4px) scale(1.01);
}

/* subtle pulsing on primary buttons to draw attention */
.btn-primary.pulse {
    animation: gentlePulse 2.4s infinite;
}

.btn-warning {
    background: var(--warning);
    color: var(--black);
}
.btn-warning:hover {
    background: #e00000ff;
    transform: translateY(-2px);
}

/* Alerts */
.alert {
    padding: 15px;
    border-radius: 8px;
    margin-top: 20px;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    gap: 12px;
    animation: slideFade 420ms var(--anim-ease);
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

/* Small utility to stagger animations for children (set style="--delay: .2s") */
.animated {
    animation-duration: 520ms;
    animation-fill-mode: both;
    animation-timing-function: var(--anim-ease);
    animation-name: fadeInUp;
    animation-delay: var(--delay, 0s);
}

/* Keyframes */
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(10px) scale(.995); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}
@keyframes fadeInDown {
    from { opacity: 0; transform: translateY(-10px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes popIn {
    0% { transform: scale(.92); opacity: 0; }
    60% { transform: scale(1.03); opacity: 1; }
    100% { transform: scale(1); }
}
@keyframes gentlePulse {
    0% { box-shadow: 0 6px 18px rgba(52,152,219,0.12); transform: translateY(-2px) scale(1); }
    50% { box-shadow: 0 18px 36px rgba(52,152,219,0.06); transform: translateY(-3px) scale(1.01); }
    100% { box-shadow: 0 6px 18px rgba(52,152,219,0.12); transform: translateY(-2px) scale(1); }
}
@keyframes slideFade {
    from { opacity: 0; transform: translateY(-8px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes icon-breathe {
    0% { transform: scale(1); }
    50% { transform: scale(1.06); }
    100% { transform: scale(1); }
}

/* Respect reduced motion preferences */
@media (prefers-reduced-motion: reduce) {
    .animated, .stat-card, .action-card, .btn-primary.pulse, header, .alert, .stat-value, h1 i {
        animation: none !important;
        transition: none !important;
    }
    * { scroll-behavior: auto; }
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
                    
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Slots
                        </button>
                    </div>
                    
                    <?php if (isset($addMessage)) echo $addMessage; ?>
                </form>
            </div>
        </div>

        <a href="index.php" class="btn btn-primary">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</body>
</html>
