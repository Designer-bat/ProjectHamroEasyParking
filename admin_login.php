<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'parking_system');

$error = "";

// If already logged in, go to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();

    if ($admin && $password === $admin['PASSWORD']) { 
        // ✅ Password check (plain text)
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['last_activity'] = time(); // for session timeout
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
:root{
  --bg-1: #0f172a;
  --bg-2: #071036;
  --card-bg: rgba(255,255,255,0.95);
  --accent: #2563eb;
  --accent-2: #7c3aed;
  --muted: #6b7280;
  --success: #16a34a;
  --danger: #ef4444;
  --glass-border: rgba(255,255,255,0.08);
}

/* Page background */
body{
  margin:0;
  min-height:100vh;
  font-family: "Inter", "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
  color: #0b1220;
  background: radial-gradient(1200px 600px at 10% 10%, rgba(37,99,235,0.08), transparent 8%),
              linear-gradient(180deg, var(--bg-1) 0%, var(--bg-2) 100%);
  display:flex;
  align-items:center;
  justify-content:center;
  padding:2rem;
  overflow:hidden;
}

/* PRELOADER */
#preloader{
  position:fixed;
  inset:0;
  display:flex;
  align-items:center;
  justify-content:center;
  background: linear-gradient(180deg, rgba(2,6,23,0.85), rgba(2,6,23,0.9));
  z-index:9999;
  transition: opacity .45s ease, visibility .45s ease;
}
#preloader.hide{
  opacity:0;
  visibility:hidden;
  pointer-events:none;
}
.loader-content{
  text-align:center;
  color:#e6eef8;
  display:flex;
  flex-direction:column;
  gap:.6rem;
  align-items:center;
  transform:translateY(0);
  animation: loaderFloat 1.8s ease-in-out infinite;
}
.loader-content i{
  font-size:3.2rem;
  color: var(--accent);
  filter: drop-shadow(0 6px 18px rgba(37,99,235,0.18));
  animation: spin 1.6s linear infinite;
}
.loader-content h1{
  margin:0;
  font-size:1.05rem;
  letter-spacing: .6px;
  font-weight:600;
}
.loader-content span{ color: var(--accent); font-weight:700; font-size:.95rem; }

@keyframes spin { to { transform: rotate(360deg); } }
@keyframes loaderFloat{
  0% { transform: translateY(0); opacity:.95 }
  50%{ transform: translateY(-6px); opacity:1 }
  100%{ transform: translateY(0); opacity:.95 }
}

/* LOGIN CARD */
.login-container{
  width:100%;
  max-width:460px;
  background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(250,250,250,0.98));
  border-radius:14px;
  padding:2.25rem 2rem;
  box-shadow: 0 20px 50px rgba(3,7,18,0.55), 0 2px 6px rgba(2,6,23,0.12);
  border: 1px solid var(--glass-border);
  text-align:center;
  color: #0b1220;
  transform: translateY(28px) scale(.995);
  opacity:0;
  animation: cardEnter .7s cubic-bezier(.24,.9,.2,1) .12s forwards;
  backdrop-filter: blur(6px);
}

/* card entrance */
@keyframes cardEnter{
  to { transform: translateY(0) scale(1); opacity:1; }
}

/* brand */
.brand-logo{
  width:84px;
  height:84px;
  margin:-64px auto 8px;
  display:flex;
  align-items:center;
  justify-content:center;
  background: linear-gradient(135deg, rgba(37,99,235,0.12), rgba(124,58,237,0.06));
  color:var(--accent);
  border-radius:50%;
  font-size:2.4rem;
  box-shadow: 0 10px 30px rgba(37,99,235,0.08);
  position:relative;
  animation: popIn .6s cubic-bezier(.2,.9,.2,1) .05s both;
}
@keyframes popIn {
  0% { transform: scale(.55) translateY(-8px); opacity:0; }
  60% { transform: scale(1.08); opacity:1; }
  100%{ transform: scale(1); }
}

/* heading */
.login-title{
  font-size:1.45rem;
  font-weight:700;
  margin:0.5rem 0 1.1rem;
  color:#071033;
}

/* inputs */
.input-group{
  display:flex;
  align-items:center;
  gap:0.6rem;
  margin-bottom:1rem;
}
.input-group-text{
  display:flex;
  align-items:center;
  justify-content:center;
  min-width:54px;
  height:52px;
  background: linear-gradient(180deg, var(--accent) 0%, rgba(37,99,235,0.9) 100%);
  color:#fff;
  border: none;
  border-radius:10px;
  box-shadow: 0 6px 18px rgba(37,99,235,0.12);
  font-size:1.05rem;
}
.form-control{
  height:52px;
  border-radius:10px;
  border:1px solid #e6eef8;
  padding:0.5rem 0.9rem;
  font-size:0.98rem;
  color:#071033;
  box-shadow: none;
  transition: box-shadow .28s ease, transform .18s ease, border-color .18s ease, background-color .18s ease;
}
.form-control::placeholder{ color:#94a3b8; }
.form-control:focus{
  outline:none;
  border-color: rgba(37,99,235,0.85);
  box-shadow: 0 8px 28px rgba(37,99,235,0.12);
  transform: translateY(-2px);
  background:#fff;
}

/* primary button with animated sheen */
.btn-login{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  gap:.6rem;
  width:100%;
  padding:13px;
  border-radius:10px;
  border:none;
  color:#fff;
  font-weight:700;
  font-size:1rem;
  cursor:pointer;
  background: linear-gradient(90deg, var(--accent) 0%, var(--accent-2) 100%);
  box-shadow: 0 12px 30px rgba(37,99,235,0.18);
  position:relative;
  overflow:hidden;
  transition: transform .18s ease, box-shadow .18s ease;
}
.btn-login::after{
  content:"";
  position:absolute;
  top:-40%;
  left:-40%;
  width:40%;
  height:200%;
  background: linear-gradient(120deg, rgba(255,255,255,0.25), rgba(255,255,255,0.08), rgba(255,255,255,0.12));
  transform: rotate(25deg);
  transition: transform .9s cubic-bezier(.2,.9,.2,1);
}
.btn-login:hover{
  transform: translateY(-4px);
  box-shadow: 0 18px 42px rgba(37,99,235,0.22);
}
.btn-login:hover::after{
  transform: translateX(420%) rotate(25deg);
}
.btn-login:active{ transform: translateY(-2px) scale(.995); }

/* subtle input icons spacing for small screens */
@media (max-width:520px){
  .login-container{ padding:1.5rem 1rem; max-width:420px; }
  .brand-logo{ width:72px; height:72px; margin-top:-50px; font-size:2rem; }
}

/* alert box */
.alert{
  display:flex;
  align-items:center;
  gap:.6rem;
  justify-content:center;
  background: rgba(255,69,58,0.06);
  color: var(--danger);
  border: 1px solid rgba(239,68,68,0.12);
  padding:.62rem .9rem;
  border-radius:10px;
  margin-bottom:1rem;
  font-size:.95rem;
  animation: alertIn .35s ease both;
}
@keyframes alertIn{
  from{ transform: translateY(-6px) scale(.98); opacity:0; }
  to{ transform: translateY(0) scale(1); opacity:1; }
}

/* footer */
.footer-text{
  margin-top:1.2rem;
  color:var(--muted);
  font-size:.88rem;
  display:flex;
  align-items:center;
  justify-content:center;
  gap:.5rem;
}
.footer-text i{ color:var(--accent); }

/* small flourish animations */
@keyframes floatSlow{
  0%{ transform: translateY(0); }
  50%{ transform: translateY(-6px); }
  100%{ transform: translateY(0); }
}
.brand-logo, .login-title{ animation: floatSlow 6s ease-in-out infinite; }

/* focus-visible accessibility */
.form-control:focus-visible{ outline: 3px solid rgba(37,99,235,0.12); outline-offset: 2px; }

/* reduce motion preference */
@media (prefers-reduced-motion: reduce){
  *{ animation: none !important; transition: none !important; }
  #preloader{ display:none !important; }
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
    <h1 class="login-title">Parking Management System</h1>

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
      <i class="fas fa-shield-alt me-1"></i>  © <?= date('Y') ?> PARKING MANAGEMENT SYSTEM
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
