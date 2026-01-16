<?php
// ====================== DB CONNECTION ======================
$conn = new mysqli('localhost', 'root', '', 'parking_system');
if ($conn->connect_error) die("Database connection failed");

// ====================== ADD USER ======================
if (isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role = $_POST['role'];

    if ($username && $password) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $hashed, $role);
        $stmt->execute();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// ====================== DELETE USER ======================
if (isset($_GET['delete']) && ctype_digit($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// ====================== FETCH USERS ======================
$result = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
include("Aiindex.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Management</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body {
    font-family: 'Poppins', sans-serif;
    background: #f1f5f9;
    margin: 0;
    padding: 40px;
    display: flex;
    justify-content: center;
}
.container {
   width: 1400px;        /* 25cm in px */
    height: 600px;       /* auto height for multiple notifications */
    background: #fff;
    border-radius: 14px;
    padding: 30px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
    margin-left: 250px; /* move card to the right */
    margin-right: 0;
}
h2 { color: #1e3a8a; margin-bottom: 10px; }
form input, form select {
    padding: 8px 12px;
    margin-right: 10px;
    border-radius: 8px;
    border: 1px solid #ccc;
}
button {
    padding: 8px 12px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    color: #fff;
    transition: all 0.2s ease;
}
.add-btn { background: #2563eb; }
.add-btn:hover { background: #1d4ed8; }
.delete-btn { background: #ef4444; }
.delete-btn:hover { background: #dc2626; }
table { width: 100%; border-collapse: collapse; margin-top: 20px; }
th, td { padding: 12px; border-bottom: 1px solid #e5e7eb; text-align: left; }
th { background: #2563eb; color: #fff; }
td .status-badge { padding: 5px 10px; border-radius: 12px; color: #fff; font-size: 0.85rem; }
.admin { background: #16a34a; }
.staff { background: #f59e0b; }
</style>
</head>
<body>
<div class="container">
<h2><i class="fas fa-users-cog"></i> User Management</h2>

<!-- ADD USER FORM -->
<form method="POST">
    <input type="text" name="username" placeholder="Username" required>
    <input type="password" name="password" placeholder="Password" required>
    <select name="role">
        <option value="Admin">Admin</option>
        <option value="Staff" selected>Staff</option>
    </select>
    <button type="submit" name="add_user" class="add-btn"><i class="fas fa-plus"></i> Add User</button>
</form>

<!-- USER TABLE -->
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Role</th>
            <th>Created At</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['username']) ?></td>
                    <td>
                        <span class="status-badge <?= strtolower($row['role']) ?>"><?= $row['role'] ?></span>
                    </td>
                    <td><?= date('M d, Y H:i', strtotime($row['created_at'])) ?></td>
                    <td>
                        <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Delete this user?');" class="delete-btn"><i class="fas fa-trash-alt"></i></a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="5" style="text-align:center;color:#6b7280;">No users found</td></tr>
        <?php endif; ?>
    </tbody>
</table>
</div>
</body>
</html>
