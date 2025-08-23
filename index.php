<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'parking_system');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/* =======================
   DASHBOARD STATS (today)
   ======================= */
$totalVehiclesToday = 0;
$currentParked = 0;
$totalIncomeToday = 0;
$parkingEfficiency = 0;

$result1 = $conn->query("SELECT COUNT(*) AS total FROM vehicles WHERE DATE(entry_time) = CURDATE()");
if ($result1) {
    $totalVehiclesToday = (int)$result1->fetch_assoc()['total'];
}

// Currently Parked
$result2 = $conn->query("SELECT COUNT(*) AS current FROM vehicles WHERE status != 'Exited'");
if ($result2) {
    $currentParked = (int)$result2->fetch_assoc()['current'];
}
// Total Income Today
$result3 = $conn->query("SELECT SUM(charges) AS total FROM vehicles WHERE DATE(exit_time) = CURDATE()");
if ($result3) {
    $totalIncomeToday = (int)($result3->fetch_assoc()['total'] ?? 0);
}

/* Keep your original efficiency formula */
$parkingEfficiency = $totalVehiclesToday > 0 ? ($totalIncomeToday / ($totalVehiclesToday * 10)) * 0.5 : 0;
$parkingEfficiency = max(0, round($parkingEfficiency, 2));

/* =======================
   PARKING SLOT STATS
   ======================= */
$slots = $conn->query("SELECT * FROM parking_slots");
$availableCount = (int)$conn->query("SELECT COUNT(*) AS total FROM parking_slots WHERE status = 'Available'")->fetch_assoc()['total'];
$occupiedCount  = (int)$conn->query("SELECT COUNT(*) AS total FROM parking_slots WHERE status = 'Occupied'")->fetch_assoc()['total'];
$totalSlots = $availableCount + $occupiedCount;

/* =======================
   WEEKLY TRENDS (last 7 days)
   ======================= */
$labels = [];            // e.g., ["2025-08-17", ...]
$labelsPretty = [];      // e.g., ["17 Aug", ...]
$incomeSeries = [];      // SUM(charges) per day
$entriesSeries = [];     // COUNT(entries) per day

$today = new DateTime('today');
for ($i = 6; $i >= 0; $i--) {
    $d = (clone $today)->modify("-$i day");
    $key = $d->format('Y-m-d');
    $labels[] = $key;
    $labelsPretty[] = $d->format('d M');
    $incomeSeries[$key] = 0;
    $entriesSeries[$key] = 0;
}
$startDate = $labels[0];
$endDate   = end($labels);

// Income by day (based on exit_time)
$qIncome = $conn->query("
    SELECT DATE(exit_time) AS d, SUM(charges) AS income
    FROM vehicles
    WHERE exit_time IS NOT NULL
      AND DATE(exit_time) BETWEEN '$startDate' AND '$endDate'
    GROUP BY DATE(exit_time)
");
if ($qIncome) {
    while ($r = $qIncome->fetch_assoc()) {
        $day = $r['d'];
        $incomeSeries[$day] = (int)$r['income'];
    }
}

// Entries by day (based on entry_time)
$qEntries = $conn->query("
    SELECT DATE(entry_time) AS d, COUNT(*) AS c
    FROM vehicles
    WHERE DATE(entry_time) BETWEEN '$startDate' AND '$endDate'
    GROUP BY DATE(entry_time)
");
if ($qEntries) {
    while ($r = $qEntries->fetch_assoc()) {
        $day = $r['d'];
        $entriesSeries[$day] = (int)$r['c'];
    }
}

// Pack to ordered arrays for Chart.js
$incomeData  = [];
$entriesData = [];
foreach ($labels as $k) {
    $incomeData[]  = $incomeSeries[$k];
    $entriesData[] = $entriesSeries[$k];
}

/* =======================
   SLOT HEATMAP (usage count)
   NOTE: Assumes vehicles.slot_id references parking_slots.id
   If your columns differ, adjust the JOIN accordingly.
   ======================= */
$heatmapRows = [];
$qHeat = $conn->query("
    SELECT ps.slot_id AS sid, ps.slot_name, ps.status,
           COALESCE(COUNT(v.vehicle_id), 0) AS use_count
    FROM parking_slots ps
    LEFT JOIN vehicles v ON v.slot_id = ps.slot_id
    GROUP BY ps.slot_id, ps.slot_name, ps.status
    ORDER BY ps.slot_name ASC
");

$minUse = PHP_INT_MAX;
$maxUse = 0;
if ($qHeat) {
    while ($row = $qHeat->fetch_assoc()) {
        $row['use_count'] = (int)$row['use_count'];
        $heatmapRows[] = $row;
        if ($row['use_count'] < $minUse) $minUse = $row['use_count'];
        if ($row['use_count'] > $maxUse) $maxUse = $row['use_count'];
    }
}
if ($minUse === PHP_INT_MAX) { $minUse = 0; } // if no rows

// Helper to map use_count to HSL color (green -> yellow -> red)
function usageToColor($use, $minUse, $maxUse) {
    if ($maxUse <= $minUse) {
        $h = 120; // all green if equal
    } else {
        $ratio = ($use - $minUse) / ($maxUse - $minUse); // 0..1
        // Hue: 120 (green) -> 0 (red)
        $h = (int)round(120 - 120 * $ratio);
    }
    return "hsl($h, 85%, 50%)";
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
    </style>
</head>
<body>

<!-- Theme Toggle -->
<button class="theme-toggle" id="themeToggle" title="Toggle Dark/Light">🌗</button>

<!-- Sidebar -->
<div class="sidebar">
    <div class="logo">HAMRO EASY PARKING</div>
    <ul class="nav-menu">
        <li><a href="#"><span class="icon"><i class="fas fa-tachometer-alt"></i></span> Dashboard</a></li>
        <li><a href="add_new_slot.php"><span class="icon"><i class="fas fa-car"></i></span> Add Parking Slot</a></li>
        <li><a href="add_vehicle.php"><span class="icon"><i class="fas fa-plus-circle"></i></span> Add Vehicle Entry</a></li>
        <li><a href="parking_parked.php"><span class="icon"><i class="fas fa-parking"></i></span> Vehicle Parked</a></li>
        <li><a href="parking_history.php"><span class="icon"><i class="fas fa-file-alt"></i></span> Parking Records</a></li>
        <li><a href="show_receipt.php"><span class="icon"><i class="fas fa-receipt"></i></span> Receipt</a></li>
        <li><a href="parking_exit.php"><span class="icon"><i class="fas fa-sign-out-alt"></i></span> Vehicle Exit</a></li>
        <li><a href="parking_history_delete.php"><span class="icon"><i class="fas fa-trash-alt"></i></span> Delete History</a></li>
        <li><a href="Delete_old_slot.php"><span class="icon"><i class="fas fa-trash-alt"></i></span> Delete Parking Slot</a></li>
    </ul>
    <div class="logout"><a href="admin_login.php"><span class="icon"><i class="fas fa-door-open"></i></span> Log Out</a></div>
</div>

<!-- Main Content -->
<main class="main-content">
    <div class="container mt-4">
        <!-- Stats (Animated) -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card-box">
                    Total Vehicles Today
                    <div class="display-4" id="stat-total-vehicles">0</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-box">
                    Currently Parked
                    <div class="display-4" id="stat-current-parked">0</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-box">
                    Income Today
                    <div class="display-4" id="stat-income">0</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-box">
                    Efficiency
                    <div class="display-4" id="stat-efficiency">0</div>
                </div>
            </div>
        </div>

        <!-- Slot Usage Indicator -->
        <h5 class="mt-4 mb-3">Slot Usage Indicator</h5>
        <div class="progress mb-4" style="height: 30px; border-radius: 20px; overflow: hidden;">
            <div class="progress-bar bg-success"
                 role="progressbar"
                 style="width: <?= $totalSlots > 0 ? ($availableCount / $totalSlots) * 100 : 0 ?>%"
                 aria-valuenow="<?= $availableCount ?>"
                 aria-valuemin="0"
                 aria-valuemax="<?= $totalSlots ?>">
                 <?= $availableCount ?> Available
            </div>
            <div class="progress-bar bg-danger"
                 role="progressbar"
                 style="width: <?= $totalSlots > 0 ? ($occupiedCount / $totalSlots) * 100 : 0 ?>%"
                 aria-valuenow="<?= $occupiedCount ?>"
                 aria-valuemin="0"
                 aria-valuemax="<?= $totalSlots ?>">
                 <?= $occupiedCount ?> Occupied
            </div>
        </div>

        <!-- Charts -->
        <div class="row g-3">
            <div class="col-lg-7">
                <div class="chart-card">
                    <h5 class="mb-3">Weekly Parking Revenue</h5>
                    <canvas id="revenueChart" height="140"></canvas>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="chart-card">
                    <h5 class="mb-3">Weekly Vehicle Entries</h5>
                    <canvas id="entriesChart" height="140"></canvas>
                </div>
            </div>
        </div>

        <!-- Parking Slot Status -->
        <h5 class="mt-4 mb-3">Parking Slot Status</h5>
        <div class="glass-table-container">
            <?php if ($slots): while($row = $slots->fetch_assoc()) { ?>
            <div class="slot-card">
                <div class="slot-header">
                    <div><strong><?= htmlspecialchars($row['slot_name']) ?></strong></div>
                    <div>
                        <?php if ($row['status'] == 'Available') { ?>
                            <span class="status-available">Available</span>
                        <?php } else { ?>
                            <span class="status-occupied">Occupied</span>
                        <?php } ?>
                    </div>
                </div>
                <div class="slot-date">Modified: <?= date('d M Y, h:i A', time()) ?></div>
            </div>
            <?php } endif; ?>
        </div>

        <!-- Slot Heatmap -->
        <h5 class="mt-4 mb-3">Slot Heatmap (Usage Frequency)</h5>
        <div class="heatmap-grid mb-5">
            <?php foreach ($heatmapRows as $hrow): 
                $bg = usageToColor($hrow['use_count'], $minUse, $maxUse);
            ?>
                <div class="heatmap-cell" style="background: <?= $bg ?>;">
                    <div style="text-align:center;">
                        <?= htmlspecialchars($hrow['slot_name']) ?>
                        <small><?= (int)$hrow['use_count'] ?> uses</small>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($heatmapRows)): ?>
                <div class="text-muted">No slot usage data yet.</div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
/* =======================
   Theme Toggle (LocalStorage)
   ======================= */
(function(){
    const body = document.body;
    const btn  = document.getElementById('themeToggle');
    const saved = localStorage.getItem('theme');
    if (saved === 'dark') body.classList.add('dark-mode');
    btn.addEventListener('click', () => {
        body.classList.toggle('dark-mode');
        localStorage.setItem('theme', body.classList.contains('dark-mode') ? 'dark' : 'light');
    });
})();

/* =======================
   Animated Counters
   ======================= */
function animateValue(el, end, opts={}) {
    const {duration=1000, prefix='', suffix='', format='int'} = opts;
    const start = 0;
    const startTime = performance.now();
    function step(now){
        const p = Math.min(1, (now - startTime) / duration);
        let val = start + (end - start) * p;
        if (format === 'currency') el.textContent = prefix + new Intl.NumberFormat().format(Math.round(val));
        else if (format === 'percent') el.textContent = (val).toFixed(2) + suffix;
        else el.textContent = prefix + Math.round(val) + suffix;
        if (p < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
}

animateValue(document.getElementById('stat-total-vehicles'), <?= (int)$totalVehiclesToday ?>, {duration: 1100});
animateValue(document.getElementById('stat-current-parked'), <?= (int)$currentParked ?>, {duration: 1100});
animateValue(document.getElementById('stat-income'), <?= (int)$totalIncomeToday ?>, {duration: 1200, prefix: '₹ ', format:'currency'});
animateValue(document.getElementById('stat-efficiency'), <?= (float)$parkingEfficiency ?>, {duration: 1300, suffix: '%', format:'percent'});

/* =======================
   Charts: Revenue & Entries
   ======================= */
const labels = <?= json_encode($labelsPretty) ?>;
const incomeData = <?= json_encode(array_values($incomeData)) ?>;
const entriesData = <?= json_encode(array_values($entriesData)) ?>;

new Chart(document.getElementById('revenueChart').getContext('2d'), {
    type: 'line',
    data: {
        labels,
        datasets: [{
            label: 'Revenue (₹)',
            data: incomeData,
            fill: true,
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59,130,246,0.2)',
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: true } },
        scales: { y: { beginAtZero: true } }
    }
});

new Chart(document.getElementById('entriesChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels,
        datasets: [{
            label: 'Vehicle Entries',
            data: entriesData,
            backgroundColor: 'rgba(16,185,129,0.4)',
            borderColor: '#10b981',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: true } },
        scales: { y: { beginAtZero: true } }
    }
});

/* =======================
   Voice Alert (only when available slots increase)
   ======================= */
(function(){
    const availNow = <?= (int)$availableCount ?>;
    const key = 'lastAvailableCount';
    const prevStr = localStorage.getItem(key);
    const hadPrev = prevStr !== null;
    const prev = hadPrev ? parseInt(prevStr, 10) : 0;

    if (hadPrev && availNow > prev && 'speechSynthesis' in window) {
        const diff = availNow - prev;
        const msg = diff === 1 ? 'One slot is now available.' : `${diff} slots are now available.`;
        const u = new SpeechSynthesisUtterance(msg);
        u.lang = 'en-US';
        window.speechSynthesis.speak(u);
    }
    localStorage.setItem(key, String(availNow));
})();
</script>
</body>
</html>
