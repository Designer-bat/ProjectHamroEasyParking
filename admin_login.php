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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <style>
:root {
  --sidebar-blue: #1e3a8a;
  --primary-blue: #3b82f6;
  --card-bg: #f1f5f9;
  --text-black: #000000;
  --white: #ffffff;
  --success-green: #16a34a;
}

body {
  background-color: var(--sidebar-blue);
  min-height: 100vh;
  margin: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  color: var(--text-black);
  overflow: hidden;
}

/* ----------------- PRELOADER ----------------- */
#preloader {
  position: fixed;
  top: 0; left: 0;
  width: 100%; height: 100%;
  background: #0a1931; /* dark background */
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
  transition: opacity 0.6s ease, visibility 0.6s ease;
}

#preloader.hide {
  opacity: 0;
  visibility: hidden;
}

.loader-content {
  text-align: center;
  color: #fff;
  animation: fadeInOut 2s infinite;
}

.loader-content i {
  font-size: 4rem;
  color: #00aaff;
  display: block;
  margin-bottom: 1rem;
}

.loader-content h1 {
  font-size: 1.8rem;
  margin: 0;
}

.loader-content span {
  color: #00aaff;
  font-size: 1.1rem;
}

@keyframes fadeInOut {
  0% { opacity: 0.5; transform: scale(0.9);}
  50% { opacity: 1; transform: scale(1.05);}
  100% { opacity: 0.5; transform: scale(0.9);}
}
/* --------------------------------------------- */

.login-container {
  background-color: var(--card-bg);
  border-radius: 12px;
  max-width: 420px;
  padding: 2.5rem 2rem;
  width: 100%;
  text-align: center;
  color: var(--text-black);
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
  opacity: 0;
  transform: translateY(40px);
  animation: slideIn 0.8s ease-out forwards;
}

@keyframes slideIn {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.brand-logo {
  font-size: 3.5rem;
  color: var(--primary-blue);
  margin-bottom: 1rem;
}

.login-title {
  font-weight: 700;
  font-size: 2.2rem;
  color: var(--text-black);
  margin-bottom: 2rem;
}

.input-group {
  margin-bottom: 1.5rem;
}

.input-group-text {
  background-color: var(--sidebar-blue);
  color: var(--white);
  border: none;
  min-width: 50px;
  display: flex;
  justify-content: center;
  align-items: center;
  font-size: 1.2rem;
  border-top-left-radius: 8px;
  border-bottom-left-radius: 8px;
}

.form-control {
  background-color: var(--white);
  border: 1px solid #cbd5e1;
  color: var(--text-black);
  height: 52px;
  font-size: 1.05rem;
  padding: 0 15px;
  transition: all 0.3s ease;
  border-top-right-radius: 8px;
  border-bottom-right-radius: 8px;
}

.form-control::placeholder {
  color: #94a3b8;
}

.form-control:focus {
  outline: none;
  border-color: var(--primary-blue);
  box-shadow: 0 0 8px rgba(59, 130, 246, 0.5);
  background-color: #fff;
}

.btn-login {
  background-color: var(--primary-blue);
  border: none;
  font-weight: 600;
  padding: 14px;
  border-radius: 10px;
  font-size: 1.1rem;
  color: var(--white);
  margin-top: 10px;
  transition: transform 0.3s ease, background-color 0.3s ease;
}

.btn-login:hover {
  background-color: var(--sidebar-blue);
  transform: translateY(-3px);
  box-shadow: 0 6px 20px rgba(30, 58, 138, 0.5);
}

.btn-login:active {
  transform: scale(0.98);
}

.alert {
  font-size: 0.95rem;
  background-color: rgba(255, 0, 0, 0.1);
  color: #b91c1c;
  border: 1px solid rgba(255, 0, 0, 0.2);
  border-radius: 8px;
  margin-bottom: 1.8rem;
  padding: 12px;
  animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: scale(0.9);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.footer-text {
  margin-top: 1.8rem;
  font-size: 0.9rem;
  color: #64748b;
}
  .footer-text i {
    color: var(--primary-blue);
  }

  .footer-text a {
    color: var(--primary-blue);
    text-decoration: none;
  }

  .footer-text a:hover {
    text-decoration: underline;
  }
</style>
</head>
<body>
  <!-- PRELOADER -->
  <div id="preloader">
    <div class="loader-content">
      <i class="fas fa-car"></i>
      <h1>SMART PARKING<br><span>Management System</span></h1>
    </div>
  </div>

  <div class="login-container shadow">
    <div class="brand-logo">
      <i class="fas fa-parking"></i>
    </div>
    <h1 class="login-title">Hamro Easy Parking </h1>

    <?php if (!empty($error)): ?>
      <div class="alert" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="" onsubmit="showLoader()">
      <div class="input-group">
        <span class="input-group-text">
          <i class="fas fa-user"></i>
        </span>
        <input
          type="text"
          id="username"
          name="username"
          class="form-control form-control-lg"
          placeholder="Enter your username"
          required
          autofocus
        />
      </div>

      <div class="input-group">
        <span class="input-group-text">
          <i class="fas fa-lock"></i>
        </span>
        <input
          type="password"
          id="password"
          name="password"
          class="form-control form-control-lg"
          placeholder="Enter your password"
          required
        />
      </div>

      <button type="submit" class="btn btn-login w-100 btn-lg">
        <i class="fas fa-sign-in-alt me-2"></i>ACCESS SYSTEM
      </button>
    </form>

    <div class="footer-text">
      <i class="fas fa-shield-alt me-1"></i>  © <?= date('Y') ?> SMART PARKING MANAGEMENT SYSTEM
    </div>
  </div>

  <script>
    // Hide loader after page fully loads
    window.addEventListener("load", function(){
      document.getElementById("preloader").classList.add("hide");
    });

    // Show loader when submitting form (login)
    function showLoader() {
      document.getElementById("preloader").classList.remove("hide");
    }
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
