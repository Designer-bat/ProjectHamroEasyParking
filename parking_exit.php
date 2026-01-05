<?php
include "aiindex.php";
require_once "config_secure.php";

$conn = new mysqli("localhost", "root", "", "parking_system");
if ($conn->connect_error) die("DB Error");

/* Exit vehicle */
if (isset($_GET['exit']) && is_numeric($_GET['exit'])) {
    $id = intval($_GET['exit']);
    $exit_time = date("Y-m-d H:i:s");

    $q = $conn->query("SELECT entry_time FROM vehicles WHERE vehicle_id=$id");
    if ($r = $q->fetch_assoc()) {
        $hours = ceil((strtotime($exit_time) - strtotime($r['entry_time'])) / 3600);
        $charge = $hours * 10;

        $conn->query("UPDATE vehicles SET 
            exit_time='$exit_time',
            duration=$hours,
            charges=$charge,
            status='Exited'
            WHERE vehicle_id=$id
        ");
    }
}

/* Date filter */
$dateFilter = $_GET['date'] ?? '';
$where = "status='Exited'";
if ($dateFilter) {
    $where .= " AND DATE(exit_time)='$dateFilter'";
}

/* Data */
$vehicles = $conn->query("SELECT * FROM vehicles WHERE $where ORDER BY exit_time DESC");

/* Income */
$income = 0;
$sum = $conn->query("SELECT SUM(charges) total FROM vehicles WHERE $where");
if ($s = $sum->fetch_assoc()) $income = $s['total'] ?? 0;
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Parking History</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
  body {
    background: #f3f4f6;
    font-family: Arial, sans-serif;
    padding: 20px;
  }

  .container {
    text-align: center;
    max-width: 1200px;
    margin: auto;
  }
/* ===== Card Layout ===== */
.history-card {
  background: #fff;
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 12px 30px rgba(0,0,0,0.08);
  max-width: 1100px;
  margin: auto;
}

.history-header {
  display: flex;
  align-items: center;
  margin-bottom: 20px;
}

.history-header .title {
  display: flex;
  align-items: center;
  gap: 14px;
}

.history-header .icon {
  font-size: 26px;
  background: #e8f0ff;
  padding: 10px;
  border-radius: 10px;
}

.history-header h4 {
  margin: 0;
  font-weight: 700;
  color: #1e3a8a;
}

.history-header p {
  margin: 0;
  font-size: 0.9rem;
  color: #64748b;
}

/* ===== Table ===== */
.history-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 10px;
}

.history-table thead {
  background: #2563eb;
  color: #fff;
}

.history-table th {
  padding: 14px;
  font-weight: 600;
  text-align: left;
  font-size: 0.9rem;
}

.history-table td {
  padding: 14px;
  border-bottom: 1px solid #e5e7eb;
  font-size: 0.9rem;
}

.history-table tbody tr:hover {
  background: #f8fafc;
}

/* ===== Status Badge ===== */
.status {
  padding: 6px 14px;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  display: inline-block;
}

.status.exited {
  background: #e5e7eb;
  color: #374151;
}

/* ===== Action Button ===== */
.delete-btn {
  background: #ef4444;
  border: none;
  color: #fff;
  width: 36px;
  height: 36px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 16px;
  transition: 0.2s ease;
}

.delete-btn:hover {
  background: #dc2626;
  transform: scale(1.05);
}

/* ===== Back Button ===== */
.back-btn {
  display: inline-block;
  margin-top: 20px;
  background: #2563eb;
  color: #fff;
  padding: 10px 18px;
  border-radius: 8px;
  text-decoration: none;
  font-weight: 600;
}

.back-btn:hover {
  background: #1e40af;
}

/* ===== Responsive ===== */
@media (max-width: 768px) {
  .history-table thead {
    display: none;
  }

  .history-table,
  .history-table tbody,
  .history-table tr,
  .history-table td {
    display: block;
    width: 100%;
  }

  .history-table tr {
    background: #fff;
    margin-bottom: 16px;
    border-radius: 12px;
    padding: 14px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.08);
  }

  .history-table td {
    text-align: right;
    padding: 8px 0;
    position: relative;
  }

  .history-table td::before {
    content: attr(data-label);
    position: absolute;
    left: 0;
    font-weight: 600;
    color: #64748b;
  }
}
  </style>

</head>

<body>

<div class="container">
<!-- Summary -->
<div class="history-card">
  <div class="history-header">
    <div class="title">
      <span class="icon">🚗</span>
      <div>
        <h4>Records and History</h4>
        <p>History of parked in the facility</p>
      </div>
    </div>
  </div>

  <div class="table-responsive">
    <table class="history-table">
      <thead>
        <tr>
          <th>Vehicle No</th>
          <th>Owner Name</th>
          <th>Entry Time</th>
          <th>Exit Time</th>
          <th>Duration</th>
          <th>Charges</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $vehicles->fetch_assoc()) { ?>
        <tr>
          <td>Hidden Content</td>
          <td>Hidden Content</td>
          <td><?= date('M d, Y H:i', strtotime($row['entry_time'])) ?></td>
          <td><?= date('M d, Y H:i', strtotime($row['exit_time'])) ?></td>
          <td><?= $row['duration'] ?> hr</td>
          <td>₹<?= $row['charges'] ?></td>
          <td>
            <span class="status exited">Exited</span>
          </td>
        </tr>
        <?php } ?>
      </tbody>
    </table>
  </div>

  <a href="index.php" class="back-btn">← Back to Dashboard</a>
</div>


<script>
document.getElementById("search").addEventListener("keyup",function(){
 let value=this.value.toLowerCase();
 document.querySelectorAll("#vehicleTable tbody tr").forEach(row=>{
   row.style.display=row.innerText.toLowerCase().includes(value)?"":"none";
 });
});
</script>

</body>
</html>
