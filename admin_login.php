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
  body {
    background-color: #212A31;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    min-height: 100vh;
    margin: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #D3D9D4;
  }

  .login-container {
    background: rgba(46, 57, 68, 0.7); /* #2E3944 glass style */
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border-radius: 16px;
    border: 1px solid #748D92;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    max-width: 420px;
    padding: 2.5rem 2rem;
    width: 100%;
    color: #D3D9D4;
    text-align: center;
  }

  .login-title {
    font-weight: 700;
    font-size: 2rem;
    color: #D3D9D4;
    margin-bottom: 2rem;
    text-transform: uppercase;
    letter-spacing: 1px;
  }

  .form-label {
    font-weight: 500;
    color: #D3D9D4;
  }

  .form-control {
    background-color: #2E3944;
    border: 1px solid #748D92;
    border-radius: 8px;
    color: #D3D9D4;
  }

  .form-control::placeholder {
    color: #9CA3AF;
  }

  .form-control:focus {
    background-color: #124E66;
    color: #fff;
    border-color: #124E66;
    box-shadow: 0 0 5px rgba(18, 78, 102, 0.5);
  }

  .btn-primary {
    background-color: #124E66;
    border: none;
    font-weight: 600;
    padding: 12px;
    border-radius: 10px;
    transition: background-color 0.3s ease;
    box-shadow: 0 4px 15px rgba(18, 78, 102, 0.5);
  }

  .btn-primary:hover {
    background-color: #0e3b4d;
    box-shadow: 0 6px 20px rgba(14, 59, 77, 0.6);
  }

  .alert {
    font-size: 0.9rem;
    background: rgba(248, 113, 113, 0.2);
    color: #F87171;
    border: 1px solid rgba(248, 113, 113, 0.5);
    border-radius: 10px;
  }

  .footer-text {
    margin-top: 1.5rem;
    font-size: 0.85rem;
    color: #748D92;
    text-shadow: 0 0 1px rgba(0, 0, 0, 0.3);
  }
</style>
  <link rel="icon" href="favicon.ico" type="image/x-icon" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
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
