<?php
include ("Aiindex.php");
$conn = new mysqli("localhost", "root", "", "employee_log");

if (isset($_POST['start'])) {
    $employee = $_POST['employee'];
    $start_time = date("Y-m-d H:i:s");
    $end_time = date("Y-m-d H:i:s", strtotime("+4 hours"));

    $conn->query("INSERT INTO work_logs (employee_name, start_time, end_time) 
                  VALUES ('$employee', '$start_time', '$end_time')");
}

if (isset($_POST['end'])) {
    $id = $_POST['id'];
    $end_time = date("Y-m-d H:i:s");
    $conn->query("UPDATE work_logs SET status='Ended', end_time='$end_time' WHERE id=$id");
}

$result = $conn->query("SELECT * FROM work_logs ORDER BY id DESC LIMIT 1");
$row = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Work Log</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --secondary: #1e40af;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --light: #f8fafc;
            --dark: #1e293b;
            --gray: #64748b;
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #ffffffff 100%);
            color: var(--dark);
            line-height: 1.6;
            min-height: 100vh;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
        }
        
        header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        h1 {
            color: var(--primary);
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .subtitle {
            color: var(--gray);
            font-size: 1.1rem;
            margin-bottom: 30px;
        }
        
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            margin-bottom: 30px;
            transition: var(--transition);
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15), 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            background: var(--primary);
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 1.4rem;
            font-weight: 600;
        }
        
        .card-body {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }
        
        input[type="text"] {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1rem;
            transition: var(--transition);
        }
        
        input[type="text"]:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }
        
        .btn {
            display: inline-block;
            padding: 14px 28px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
        }
        
        .btn:hover {
            background: var(--secondary);
            transform: translateY(-2px);
        }
        
        .btn-end {
            background: var(--warning);
        }
        
        .btn-end:hover {
            background: #d81159;
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        .log-details {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 25px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            color: var(--gray);
        }
        
        .detail-value {
            font-weight: 500;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-started {
            background: rgba(76, 201, 240, 0.2);
            color: #1890c8;
        }
        
        .status-ended {
            background: rgba(247, 37, 133, 0.2);
            color: #d81159;
        }
        
        .notification {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            display: flex;
            align-items: center;
            animation: pulse 2s infinite;
        }
        
        .notification i {
            color: #ffc107;
            margin-right: 10px;
            font-size: 1.2rem;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(255, 193, 7, 0); }
            100% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0); }
        }
        
        .timer {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--warning);
            text-align: center;
            margin: 15px 0;
        }
        
        footer {
            text-align: center;
            margin-top: 40px;
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .card-body {
                padding: 20px;
            }
            
            h1 {
                font-size: 2rem;
            }
            
            .btn {
                width: 100%;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Employee Work Log System</h1>
            <p class="subtitle">Track and manage employee work hours efficiently</p>
        </header>
        
        <div class="card">
            <div class="card-header">
                <i class="fas fa-user-clock"></i> Log Work Time
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="form-group">
                        <label for="employee">Employee Name</label>
                        <div class="input-with-icon">
                            <i class="input-icon fas fa-user"></i>
                            <input type="text" name="employee" placeholder="Enter your full name" required>
                        </div>
                    </div>
                    
                    <button type="submit" name="start" class="btn">
                        <i class="fas fa-play-circle"></i> Start Work Session
                    </button>
                </form>
                
                <?php if ($row) { ?>
                <div class="log-details">
                    <h3><i class="fas fa-history"></i> Last Work Session</h3>
                    
                    <div class="detail-row">
                        <span class="detail-label">Employee:</span>
                        <span class="detail-value"><?= $row['employee_name'] ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Start Time:</span>
                        <span class="detail-value"><?= date("M j, Y g:i A", strtotime($row['start_time'])) ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">End Time:</span>
                        <span class="detail-value"><?= date("M j, Y g:i A", strtotime($row['end_time'])) ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value">
                            <span class="status-badge <?= $row['status'] == 'Started' ? 'status-started' : 'status-ended' ?>">
                                <?= $row['status'] ?>
                            </span>
                        </span>
                    </div>
                    
                    <?php if ($row['status'] == 'Started') { ?>
                    <form method="post">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <button type="submit" name="end" class="btn btn-end">
                            <i class="fas fa-stop-circle"></i> End Work Session
                        </button>
                    </form>
                    
                    <div class="timer" id="timer"></div>
                    
                    <div class="notification" id="notify">
                        <i class="fas fa-bell"></i>
                        <span id="notify-text">Session in progress</span>
                    </div>

                    <script>
                        const endTime = new Date("<?= $row['end_time'] ?>").getTime();
                        const notifyDiv = document.getElementById("notify");
                        const notifyText = document.getElementById("notify-text");
                        const timerDiv = document.getElementById("timer");
                        
                        // Hide notification initially
                        notifyDiv.style.display = 'none';
                        
                        function updateTimer() {
                            const now = new Date().getTime();
                            const remaining = endTime - now;
                            
                            if (remaining <= 0) {
                                timerDiv.innerHTML = "Work session has ended";
                                notifyDiv.style.display = 'flex';
                                notifyText.innerHTML = "Please end your work session";
                                return;
                            }
                            
                            // Calculate hours, minutes, seconds
                            const hours = Math.floor(remaining / (1000 * 60 * 60));
                            const minutes = Math.floor((remaining % (1000 * 60 * 60)) / (1000 * 60));
                            const seconds = Math.floor((remaining % (1000 * 60)) / 1000);
                            
                            // Display timer
                            timerDiv.innerHTML = `Time remaining: ${hours}h ${minutes}m ${seconds}s`;
                            
                            // Show notification when 15 minutes left
                            if (remaining <= 15 * 60 * 1000) {
                                notifyDiv.style.display = 'flex';
                                notifyText.innerHTML = "Reminder: Only 15 minutes left in your work session!";
                            }
                        }
                        
                        // Initial call
                        updateTimer();
                        
                        // Update every second
                        setInterval(updateTimer, 1000);
                    </script>
                    <?php } ?>
                </div>
                <?php } ?>
            </div>
        </div>
        <a href="index.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </div>
</body>
</html>