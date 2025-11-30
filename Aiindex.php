
<?php
session_start();

// CHECK ADMIN LOGIN
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit;
}

// DATABASE CONNECTION
$conn = new mysqli('localhost', 'root', '', 'parking_system');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Parking Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --sidebar-blue: #1e3a8a;
            --primary-blue: #3b82f6;
            --card-bg: #eff6ff;
            --text-color: #333;
            --body-bg: #f5faff;
            --white: #ffffff;
        }
        body.dark-mode {
            --sidebar-blue: #0f1f4a;
            --primary-blue: #60a5fa;
            --card-bg: #111827;
            --text-color: #e5e7eb;
            --body-bg: #0b1220;
            --white: #0f172a;
        }

        body {
            background-color: var(--body-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-color);
            margin: 0;
        }

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
            z-index: 1000;
            color: white;
        }

        .sidebar .logo {
            text-align: center;
            font-size: 1.3rem;
            font-weight: bold;
            color: #ffffff;
            margin-bottom: 20px;
        }

        

        .sidebar .nav-menu { list-style: none; padding: 0; margin: 0 0 20px 0; }
        .sidebar .nav-menu li a,
        .sidebar .logout a {
            color: #f0f0f0;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            padding: 12px 24px;
            transition: background 0.3s;
        }
        .sidebar .nav-menu li a:hover,
        .sidebar .logout a:hover { background-color: #2563eb; }

        .sidebar .section-title {
            margin: 10px 24px;
            font-size: 0.75rem;
            font-weight: bold;
            color: #dbeafe;
            text-transform: uppercase;
        }

        .main-content { margin-left: 240px; padding: 20px; }
        .navbar-brand { color: var(--text-color); font-weight: 700; font-size: 1.25rem; }

        /* Custom scrollbar */
        .nav-menu::-webkit-scrollbar {
            width: 8px;
        }
        .nav-menu::-webkit-scrollbar-track {
            background: #1a252f;
        }
        .nav-menu::-webkit-scrollbar-thumb {
            background: #3498db;
            border-radius: 4px;
        }
        .nav-menu::-webkit-scrollbar-thumb:hover {
            background: #2980b9;
        }

        .card-box {
            background-color: var(--card-bg);
            border-radius: 14px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .card-box:hover { transform: translateY(-4px); }

        .card-box .display-4 {
            font-size: 2rem; font-weight: 700;
        }

        .glass-table-container { margin-top: 20px; }

        .slot-card {
            background: var(--white);
            border-left: 5px solid #60a5fa;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }
        .slot-card:hover { transform: scale(1.01); box-shadow: 0 8px 16px rgba(0,0,0,0.08); }

        .slot-header { display: flex; justify-content: space-between; font-weight: 600; }

        .status-available {
            color: #10b981; background: #d1fae5; padding: 3px 10px; border-radius: 20px; font-size: 0.9rem;
        }
        .status-occupied {
            color: #ef4444; background: #fee2e2; padding: 3px 10px; border-radius: 20px; font-size: 0.9rem;
        }

        .slot-date { font-size: 0.8rem; color: #6b7280; margin-top: 4px; }

        .progress-bar { font-weight: 600; font-size: 0.9rem; text-align: center; }

        .sidebar .icon { width: 20px; text-align: center; }

        .sidebar {
    position: fixed;
    top: 0; 
    left: 0;
    height: 100%;
    width: 240px;
    background-color: var(--sidebar-blue);
    padding: 20px 0;
    display: flex;
    flex-direction: column;
    justify-content: flex-start; /* change from space-between */
    z-index: 1000;
    color: white;
    overflow: hidden; /* hide extra content outside */
}

/* Make the menu scrollable */
.sidebar .nav-menu {
    list-style: none;
    padding: 0; 
    margin: 0 0 20px 0;
    overflow-y: auto;       /* enable vertical scrolling */
    flex-grow: 1;           /* take remaining vertical space */
    max-height: calc(100vh - 120px); /* adjust according to logo + clock + logout height */
}


        /* Theme toggle */
        .theme-toggle {
            position: fixed; right: 16px; top: 16px; z-index: 2000;
            border: none; border-radius: 50%; width: 44px; height: 44px;
            background: var(--primary-blue); color: white; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        /* Heatmap grid */
        .heatmap-grid { display: flex; flex-wrap: wrap; gap: 10px; }
        .heatmap-cell {
            width: 90px; height: 90px; border-radius: 10px; color: white;
            display: flex; align-items: center; justify-content: center; font-weight: 700;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }
        .heatmap-cell small { display:block; font-weight: 600; opacity: 0.85; }

        /* Chart card */
        .chart-card { background: var(--white); border-radius: 14px; padding: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        
        /* Notification System */
        .notification-container {
            position: fixed;
            top: 20px;
            right: 70px;
            z-index: 2000;
        }

        .notification-bell {
            position: relative;
            background: var(--primary-blue);
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .notification-bell .badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .notification-panel {
            position: absolute;
            top: 50px;
            right: 0;
            width: 320px;
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
            max-height: 400px;
            overflow-y: auto;
            display: none;
        }

        .notification-panel.show {
            display: block;
        }

        .notification-header {
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notification-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .notification-item {
            padding: 12px 15px;
            border-bottom: 1px solid #f3f4f6;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-icon {
            color: #3b82f6;
            font-size: 1.1rem;
        }

        .notification-content {
            flex: 1;
        }

        .notification-time {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 4px;
        }

        .notification-clear {
            background: none;
            border: none;
            color: #3b82f6;
            cursor: pointer;
            font-size: 0.8rem;
        }
        
        .toast-container {
            position: fixed;
            top: 20px;
            right: 130px;
            z-index: 2001;
            max-width: 350px;
        }
        
        .toast {
            background: var(--white);
            border-left: 4px solid #3b82f6;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            padding: 12px 15px;
            margin-bottom: 10px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }
        
        .toast.hide {
            animation: slideOut 0.3s ease;
            opacity: 0;
        }
        
        .toast-icon {
            color: #3b82f6;
            font-size: 1.2rem;
        }
        
        .toast-content {
            flex: 1;
        }
        
        .toast-close {
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            font-size: 1rem;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }

        /* Basic center + look */
.clock {
  background: rgba(255,255,255,0.06);
  padding: 16px 20px;   /* smaller padding */
  border-radius: 10px;
  box-shadow: 0 6px 20px rgba(2,6,23,0.5);
  text-align: center;
}

.time {
  font-size: 32px;      /* reduced from 56px */
  letter-spacing: 1px;
  font-weight: 600;
  margin: 0;
}

.date {
  margin-top: 6px;
  font-size: 12px;      /* smaller text */
  color: rgba(230,238,248,0.75);
}

/* small screens */
@media (max-width:220px){
  .time { font-size: 18px; }
  .clock { padding: 6px 4px; }
}
#guide-icon {
    position: fixed;
    bottom: 30px;
    right: 30px;
    background: #007bff;
    color: white;
    padding: 15px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 22px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    z-index: 9999;
}

#guide-box {
    position: fixed;
    bottom: 90px;
    right: 30px;
    width: 300px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    display: none;
    z-index: 9999;
    overflow: hidden;
}

#guide-header {
    background: #007bff;
    color: white;
    padding: 12px;
    text-align: center;
    font-weight: bold;
}

#guide-content {
    padding: 15px;
}

.guide-btn {
    width: 100%;
    margin-bottom: 10px;
    padding: 8px;
    background: #f1f1f1;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    text-align: left;
    transition: 0.2s;
}

.guide-btn:hover {
    background: #e2e2e2;
}

#guide-answer {
    padding: 12px;
    font-size: 14px;
    border-top: 1px solid #ddd;
    background: #fafafa;
    display: none;
}


</style>
</head>
<body>

<!-- Theme Toggle -->
<button class="theme-toggle" id="themeToggle" title="Toggle Dark/Light">🌗</button>

<!-- Notification System -->
<div class="notification-container">
    <div class="notification-bell" id="notificationBell">
        <i class="fas fa-bell"></i>
        <span class="badge" id="notificationCount">0</span>
    </div>
    <div class="notification-panel" id="notificationPanel">
        <div class="notification-header">
            Notifications
            <button class="notification-clear" id="clearNotifications">Clear All</button>
        </div>
        <ul class="notification-list" id="notificationList">
            <!-- Notifications will be added here dynamically -->
        </ul>
    </div>
</div>

<!-- Toast Container for temporary alerts -->
<div class="toast-container" id="toastContainer"></div>

<!-- Sidebar -->
<div class="sidebar">
    <div class="logo">Parking Management System</div>
    <div class="clock" aria-live="polite">
    <div id="time" class="time">00:00:00</div>
    <div id="date" class="date">Loading date...</div>
  </div>

<!-- Guide / Help Chatbot for Parking System -->
<div id="guide-bot">
 <div id="guide-icon">
    <img src="myAi.png" alt="AI Bot" 
         style="width:65px; height:65px; border-radius:50%; object-fit:cover;">
</div>



    <div id="guide-box">
        <div id="guide-header">
            Smart Parking Guide
        </div>

        <div id="guide-content">
            <p>Hello Admin! How can I help you?</p>

            <button class="guide-btn" data-answer="Slots are auto-assigned from the first available free slot in parking_slots table.">
                How are parking slots assigned?
            </button>

            <button class="guide-btn" data-answer="Billing = ₹10 per hour. Duration is calculated from entry_time to exit_time and stored in vehicles table.">
                How does billing work?
            </button>

            <button class="guide-btn" data-answer="When a vehicle exits, its slot becomes AVAILABLE and vehicle status is set to EXITED.">
                How does the Exit system work?
            </button>

            <button class="guide-btn" data-answer="QR code is generated from vehicle number and linked to DB records.">
                How does QR Code work?
            </button>

            <button class="guide-btn" data-answer="Dashboard data is fetched from vehicles & parking_slots tables to show totals and income.">
                Dashboard Information
            </button>
        </div>

        <div id="guide-answer"></div>
    </div>
</div>


  <script>
    function pad(n){ return n < 10 ? '0' + n : n; }

    function updateClock(){
      const now = new Date();
      const h = pad(now.getHours());
      const m = pad(now.getMinutes());
      const s = pad(now.getSeconds());
      document.getElementById('time').textContent = `${h}:${m}:${s}`;

      // e.g. Sunday, Sep 21, 2025
      const options = { weekday: 'long', month: 'short', day: 'numeric', year: 'numeric' };
      document.getElementById('date').textContent = now.toLocaleDateString(undefined, options);
    }

    updateClock();
    setInterval(updateClock, 1000);
  </script>
    <ul class="nav-menu">
        <li><a href="search.php"><span class="icon"><i class="fas fa-search"></i></span> Search</a></li>
        <li><a href="index.php"><span class="icon"><i class="fas fa-tachometer-alt"></i></span> Dashboard</a></li>
        <li><a href="add_new_slot.php"><span class="icon"><i class="fas fa-car"></i></span> Add Parking Slot</a></li>
        <li><a href="add_vehicle.php"><span class="icon"><i class="fas fa-plus-circle"></i></span> Add Vehicle Entry</a></li>
        <li><a href="parking_parked.php"><span class="icon"><i class="fas fa-parking"></i></span> Vehicle Parked</a></li>
        <li><a href="parking_history.php"><span class="icon"><i class="fas fa-file-alt"></i></span> Parking Records</a></li>
        <li><a href="show_receipt.php"><span class="icon"><i class="fas fa-receipt"></i></span> Receipt</a></li>
        <li><a href="parking_exit.php"><span class="icon"><i class="fas fa-sign-out-alt"></i></span> Vehicle Exit</a></li>
        <li><a href="parking_history_delete.php"><span class="icon"><i class="fas fa-trash-alt"></i></span> Delete History</a></li>
        <li><a href="Delete_old_slot.php"><span class="icon"><i class="fas fa-trash-alt"></i></span> Delete Parking Slot</a></li>
        <li><a href="emplye_log.php"><span class="icon"><i class="fas fa-plus-circle"></i></span>Employee log</a></li>

    </ul>
    <a href="logout.php" class="btn btn-primary">Logout</a>
</div>

<script>
    // Theme Toggle Logic
    const themeToggle = document.getElementById('themeToggle');
    themeToggle.addEventListener('click', () => {
        document.body.classList.toggle('dark-mode');
    });

    // Notification System Logic
    const notificationBell = document.getElementById('notificationBell');
    const notificationPanel = document.getElementById('notificationPanel');
    const notificationList = document.getElementById('notificationList');
    const notificationCount = document.getElementById('notificationCount');
    const clearNotificationsBtn = document.getElementById('clearNotifications');

    let notifications = [];

    notificationBell.addEventListener('click', () => {
        notificationPanel.classList.toggle('show');
    });

    clearNotificationsBtn.addEventListener('click', () => {
        notifications = [];
        renderNotifications();
    });

    function addNotification(message) {
        const timestamp = new Date().toLocaleTimeString();
        notifications.unshift({ message, timestamp });
        renderNotifications();
        showToast(message);
    }

    function renderNotifications() {
        notificationList.innerHTML = '';
        notifications.forEach(notif => {
            const li = document.createElement('li');
            li.className = 'notification-item';
            li.innerHTML = `
                <div class="notification-icon"><i class="fas fa-info-circle"></i></div>
                <div class="notification-content">
                    ${notif.message}
                    <div class="notification-time">${notif.timestamp}</div>
                </div>
            `;
            notificationList.appendChild(li);
        });
        notificationCount.textContent = notifications.length;
    }

    // Toast Logic
    const toastContainer = document.getElementById('toastContainer');

    function showToast(message) {
        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.innerHTML = `
            <div class="toast-icon"><i class="fas fa-info-circle"></i></div>
            <div class="toast-content">${message}</div>
            <button class="toast-close">&times;</button>
        `;
        toastContainer.appendChild(toast);

        toast.querySelector('.toast-close').addEventListener('click', () => {
            hideToast(toast);
        });

        setTimeout(() => {
            hideToast(toast);
        }, 5000);
    }

    function hideToast(toast) {
        toast.classList.add('hide');
        toast.addEventListener('animationend', () => {
            toast.remove();
        });
    }

document.getElementById("guide-icon").onclick = function () {
    let box = document.getElementById("guide-box");
    box.style.display = (box.style.display === "block") ? "none" : "block";
};

document.querySelectorAll(".guide-btn").forEach(btn => {
    btn.onclick = function () {
        let answerBox = document.getElementById("guide-answer");
        answerBox.innerHTML = this.dataset.answer;
        answerBox.style.display = "block";
    };
});
</script>

</body>
</html> 
