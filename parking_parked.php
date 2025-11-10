<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'parking_system');
include 'config_secure.php'; // 🔐 include encryption/decryption functions

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle vehicle exit (we can remove this block entirely if you don't want exit functionality anymore)
if (isset($_GET['exit']) && is_numeric($_GET['exit'])) {
    $vehicle_id = intval($_GET['exit']);
    $exit_time = date('Y-m-d H:i:s');

    $result = $conn->query("SELECT entry_time, slot_id FROM vehicles WHERE vehicle_id = $vehicle_id");
    if ($result && $row = $result->fetch_assoc()) {
        $entry_time = $row['entry_time'];
        $slot_id = $row['slot_id'];

        $duration_sec = strtotime($exit_time) - strtotime($entry_time);
        $duration_hrs = ceil($duration_sec / 3600);
        $charges = $duration_hrs * 10;

        $update = "UPDATE vehicles 
                   SET exit_time='$exit_time', duration=$duration_hrs, charges=$charges, status='Exited' 
                   WHERE vehicle_id=$vehicle_id";

        if (!$conn->query($update)) {
            die("Error updating vehicle record: " . $conn->error);
        }

        $conn->query("UPDATE parking_slots SET status='Available' WHERE slot_id = $slot_id");
    } else {
        die("Error fetching vehicle info: " . $conn->error);
    }
}

// Fetch all vehicle history
$vehicles = $conn->query("SELECT * FROM vehicles ORDER BY entry_time DESC");
if (!$vehicles) {
    die("Error fetching vehicles: " . $conn->error);
}

// Function to mask hashed owner name for display
function maskOwnerName($hashed) {
    return substr($hashed, 0, 6) . "****" . substr($hashed, -4);
}
include ("index.html");
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Vehicle Parked Records</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
    body {
      background-color: var(--body-bg);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      color: #111827;
      margin: 0;
      padding: 0;
      display: flex;
      justify-content: center;
      align-items: flex-start;
      min-height: 100vh;
    }

    .card {
      background: #ffffff;
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
      width: 90%;
      max-width: 1200px;
      margin-top: 40px;
      padding: 30px;
    }

    .card-header {
      display: flex;
      align-items: center;
      gap: 15px;
      margin-bottom: 25px;
      border-bottom: 2px solid #e5e7eb;
      padding-bottom: 10px;
    }

    .card-header i {
      font-size: 1.8rem;
      color: #2563eb;
    }

    .card-header h2 {
      font-size: 1.5rem;
      color: #0f172a;
      margin: 0;
    }

    .card-header p {
      font-size: 0.9rem;
      color: #6b7280;
      margin: 2px 0 0 0;
    }

    /* Table */
    .table-container {
      border-radius: 12px;
      overflow: hidden;
      background: #fff;
      margin-top: 15px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      text-align: center;
      font-size: 15px;
    }

    thead {
      background: #0f172a;
      color: #fff;
    }

    thead tr {
      height: 55px;
    }

    thead th {
      font-weight: 600;
    }

    tbody tr {
      height: 50px;
      border-bottom: 1px solid #e5e7eb;
      transition: background 0.2s ease;
    }

    tbody tr:hover {
      background-color: #f9fafb;
    }

    td {
      padding: 10px 5px;
      color: #1e293b;
    }

    .status-badge {
      padding: 5px 12px;
      border-radius: 20px;
      font-weight: 500;
      font-size: 0.85rem;
    }

    .status-in-lot {
      background: rgba(40,167,69,0.1);
      color: #28a745;
    }

    .status-exited {
      background: rgba(108,117,125,0.1);
      color: #6c757d;
    }

    .empty-state {
      text-align: center;
      padding: 60px 0;
      color: #6b7280;
    }

    .empty-state i {
      font-size: 40px;
      color: #9ca3af;
      margin-bottom: 12px;
    }

    .empty-state p {
      font-size: 16px;
      font-weight: 600;
      color: #1e293b;
    }

    .btn-back {
      background: #0f172a;
      color: #fff;
      text-decoration: none;
      padding: 12px 25px;
      border-radius: 8px;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      margin-top: 25px;
      font-weight: 500;
      transition: 0.3s ease;
    }

    .btn-back:hover {
      background: #1e40af;
      transform: translateY(-2px);
    }

    @media (max-width: 768px) {
      .card {
        width: 95%;
        padding: 20px;
      }

      table {
        font-size: 13px;
      }

      thead tr {
        height: 45px;
      }

      td, th {
        padding: 8px 4px;
      }
    }
  </style>
</head>
<body>

  <div class="card">
    <div class="card-header">
      <i class="fas fa-car"></i>
      <div>
        <h2>Vehicle Parked Records</h2>
        <p>All vehicles currently parked in the facility</p>
      </div>
    </div>

    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th style="width: 14%;">Vehicle No</th>
            <th style="width: 16%;">Owner Name</th>
            <th style="width: 17%;">Entry Time</th>
            <th style="width: 17%;">Exit Time</th>
            <th style="width: 10%;">Duration</th>
            <th style="width: 12%;">Charges</th>
            <th style="width: 14%;">Status</th>
            <th style="width: 10%;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($vehicles->num_rows > 0): ?>
            <?php while($row = $vehicles->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars(decryptVehicleNo($row['vehicle_no'])) ?></td>
              <td><?= htmlspecialchars(maskOwnerName($row['owner_name'])) ?></td>
              <td><?= date('M d, Y H:i', strtotime($row['entry_time'])) ?></td>
              <td><?= $row['exit_time'] ? date('M d, Y H:i', strtotime($row['exit_time'])) : '-' ?></td>
              <td><?= $row['duration'] ?? '-' ?></td>
              <td><?= $row['charges'] ? '₹' . $row['charges'] : '-' ?></td>
              <td>
                <?php if ($row['status'] === 'In Lot'): ?>
                  <span class="status-badge status-in-lot">In Lot</span>
                <?php else: ?>
                  <span class="status-badge status-exited">Exited</span>
                <?php endif; ?>
              </td>
              <td>
                <a href="?exit=<?= $row['vehicle_id'] ?>" style="color: #2563eb; font-weight: 500; text-decoration: none;">Exit</a>
              </td>
            </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="8">
                <div class="empty-state">
                  <i class="fas fa-info-circle"></i>
                  <p>No Parking Records Found</p>
                </div>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
</body>
</html>
