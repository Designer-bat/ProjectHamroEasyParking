<?php
$conn = new mysqli("localhost", "root", "", "employee_log");

if (isset($_POST['start'])) {
    $employee = $_POST['employee'];
    $start_time = date("Y-m-d H:i:s");
    $end_time = date("Y-m-d H:i:s", strtotime("+4 hours"));

    $conn->query("INSERT INTO work_logs (employee_name, start_time, end_time) 
                  VALUES ('$employee', '$start_time', '$end_time')");
}

if (isset($_POST['end'])) {
    $id = $_POST['id'];
    $end_time = date("Y-m-d H:i:s");
    $conn->query("UPDATE work_logs SET status='Ended', end_time='$end_time' WHERE id=$id");
}

$result = $conn->query("SELECT * FROM work_logs ORDER BY id DESC LIMIT 1");
$row = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee Work Log</title>
    <style>
        body {font-family: Arial, sans-serif; background: #f4f4f4; text-align: center; padding: 50px;}
        form {margin: 20px;}
        input[type=text] {padding: 10px; width: 250px;}
        button {padding: 10px 20px; margin: 10px; cursor: pointer; border: none; background: #007bff; color: #fff; border-radius: 5px;}
        button:hover {background: #0056b3;}
        .log-box {background: #fff; padding: 20px; border-radius: 10px; display: inline-block; box-shadow: 0 2px 5px rgba(0,0,0,0.2);}
        .notification {color: red; font-weight: bold; margin-top: 15px;}
    </style>
</head>
<body>
    <h1>Employee Work Log System</h1>

    <div class="log-box">
        <form method="post">
            <input type="text" name="employee" placeholder="Enter Employee Name" required>
            <button type="submit" name="start">Start Work</button>
        </form>

        <?php if ($row) { ?>
            <h3>Last Log</h3>
            <p><strong>Employee:</strong> <?= $row['employee_name'] ?></p>
            <p><strong>Start Time:</strong> <?= $row['start_time'] ?></p>
            <p><strong>End Time:</strong> <?= $row['end_time'] ?></p>
            <p><strong>Status:</strong> <?= $row['status'] ?></p>

            <?php if ($row['status'] == 'Started') { ?>
                <form method="post">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                    <button type="submit" name="end">End Work</button>
                </form>

                <div class="notification" id="notify"></div>

                <script>
                    const endTime = new Date("<?= $row['end_time'] ?>").getTime();
                    const notifyDiv = document.getElementById("notify");

                    setInterval(() => {
                        const now = new Date().getTime();
                        const remaining = endTime - now;

                        if (remaining <= 15 * 60 * 1000 && remaining > 0) {
                            notifyDiv.innerHTML = "Reminder: Only 15 minutes left!";
                        }
                        if (remaining <= 0) {
                            notifyDiv.innerHTML = "Work time ended!";
                        }
                    }, 1000);
                </script>
            <?php } ?>
        <?php } ?>
    </div>
</body>
</html>
