<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'parking_system');

$error = ""; // Initialize error variable

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();

    if ($admin && $password === $admin['PASSWORD']) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $admin['username'];
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid username or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Hamro Easy Parking - Admin Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    /* Background and overlay */
    body {
      background-image: url('img/andrey-kirov-i7qsJX0Ym44-unsplash.jpg');
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: sans-serif;
      position: relative;
      margin: 0;
    }
    body::before {
      content: "";
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0, 0, 0, 0.4); /* dark overlay */
      z-index: -1;
      backdrop-filter: brightness(0.7);
    }

    /* Apple Glass / frosted glass container */
    .login-container {
      background: rgba(255, 255, 255, 0.15); /* semi-transparent */
      border-radius: 15px;
      box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
      backdrop-filter: blur(10px); /* the blur */
      -webkit-backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.18);
      width: 100%;
      max-width: 420px;
      padding: 2.5rem 2rem;
      color: #fff;
      text-align: center;
    }

    .login-title {
      font-weight: 700;
      font-size: 2rem;
      color: #e0e0e0;
      margin-bottom: 2rem;
      letter-spacing: 0.05em;
      text-transform: uppercase;
    }

    /* Form styles */
    .form-label {
      font-weight: 600;
      color: #f0f0f0;
    }
    .form-control {
      background: rgba(255, 255, 255, 0.25);
      border: none;
      color: white;
      box-shadow: none;
      border-radius: 8px;
      transition: background 0.3s ease;
    }
    .form-control:focus {
      background: rgba(255, 255, 255, 0.4);
      color: #111;
      outline: none;
      box-shadow: 0 0 5px rgba(255, 255, 255, 0.7);
    }

    /* Button style */
    .btn-primary {
      background: #357abd;
      border: none;
      font-weight: 600;
      padding: 12px;
      border-radius: 10px;
      transition: background-color 0.3s ease;
      box-shadow: 0 4px 15px rgba(53, 122, 189, 0.5);
    }
    .btn-primary:hover {
      background: #2a5d9f;
      box-shadow: 0 6px 20px rgba(42, 93, 159, 0.7);
    }

    /* Error alert */
    .alert {
      font-size: 0.9rem;
      background: rgba(255, 0, 0, 0.2);
      color: #ffdddd;
      border: none;
      box-shadow: none;
    }

    /* Footer */
    .footer-text {
      margin-top: 1.5rem;
      font-size: 0.85rem;
      color: #ccc;
      text-shadow: 0 0 2px rgba(0,0,0,0.5);
    }
  </style>
</head>
<body>
  <div class="login-container shadow">
    <h1 class="login-title">Hamro Easy Parking</h1>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger" role="alert">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="mb-4 text-start">
        <label for="username" class="form-label">Username</label>
        <input
          type="text"
          id="username"
          name="username"
          class="form-control form-control-lg"
          required
          autofocus
        />
      </div>

      <div class="mb-4 text-start">
        <label for="password" class="form-label">Password</label>
        <input
          type="password"
          id="password"
          name="password"
          class="form-control form-control-lg"
          required
        />
      </div>

      <button type="submit" class="btn btn-primary w-100 btn-lg">Login</button>
    </form>

    <div class="footer-text">© <?= date('Y') ?> Hamro Easy Parking</div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
