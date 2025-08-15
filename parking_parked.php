<?php
$conn = new mysqli('localhost', 'root', '', 'parking_system');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- Encryption/Decryption Settings ---
$secret_key = "your-32-char-secret-key-1234567890abcd";  // use a strong secret key
$cipher_method = "AES-256-CBC";
$iv = substr(hash('sha256', $secret_key), 0, 16);

function decryptData($data, $key, $iv, $cipher_method) {
    return $data ? openssl_decrypt($data, $cipher_method, $key, 0, $iv) : null;
}

$vehicles = $conn->query("SELECT * FROM vehicles WHERE status='In Lot' ORDER BY entry_time DESC");
if (!$vehicles) {
    die("Error fetching vehicles: " . $conn->error);
}

function calculateDurationHours($entry_time) {
    $now = time();
    $entry = strtotime($entry_time);
    $duration_sec = $now - $entry;
    $duration_hrs = ceil($duration_sec / 3600);
    return $duration_hrs;
}

function calculatePriceMeter($duration_hrs) {
    return $duration_hrs * 10; // ₹10 per hour
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Currently Parked Vehicles</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
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

    h1 {
      font-size: 2.2rem;
      display: flex;
      align-items: center;
      gap: 15px;
    }

    h1 i {
      color: var(--secondary-blue);
    }

    .card {
      background: var(--white);
      border-radius: 10px;
      padding: 30px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
      margin-bottom: 30px;
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
    <header>
      <h1><i class="fa-solid fa-car"></i> Currently Parked Vehicles</h1>
    </header>

    <div class="card">
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>Vehicle No</th>
              <th>Owner Name</th>
              <th>Entry Time</th>
              <th>Status</th>
              <th>Duration (hrs)</th>
              <th>Price Meter (₹)</th>
            </tr>
          </thead>
          <tbody>
            <?php while($row = $vehicles->fetch_assoc()) { 
                $vehicle_no = decryptData($row['vehicle_no'], $secret_key, $iv, $cipher_method);
                $owner_name = decryptData($row['owner_name'], $secret_key, $iv, $cipher_method);

                $duration = calculateDurationHours($row['entry_time']);
                $price_meter = calculatePriceMeter($duration);
            ?>
            <tr>
              <td><?= htmlspecialchars($vehicle_no) ?></td>
              <td><?= htmlspecialchars($owner_name) ?></td>
              <td><?= date('d M Y, H:i', strtotime($row['entry_time'])) ?></td>
              <td><span class="status-badge status-in-lot"><?= htmlspecialchars($row['status']) ?></span></td>
              <td><?= $duration ?></td>
              <td>₹<?= $price_meter ?></td>
            </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="text-center">
      <a href="index.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
    </div>
  </div>
</body>
</html>
