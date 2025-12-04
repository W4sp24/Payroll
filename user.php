<?php
session_start();
date_default_timezone_set('Asia/Manila'); 

// 1. Ensure the user is logged in
if (empty($_SESSION['user_id']) || empty($_SESSION['emp_id'])) {
    header('Location: login.php');
    exit;
}

// 2. Database Connection
$dbUser = 'root';
$dbPass = '';
$dbName = 'project';
$dsn = "mysql:host=localhost;dbname=$dbName;charset=utf8mb4";
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (PDOException $e) {
    die("Connection error: " . $e->getMessage());
}

$message = "";
$emp_id = $_SESSION['emp_id'];
$currentDate = date('Y-m-d');
// System Generated Time (Server Time)
$currentTime = date('H:i:s');

// 3. Handle Button Clicks
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- TIME IN LOGIC (INSERT) ---
    if (isset($_POST['time_in'])) {
        // Validation: Check if they already timed in today
        $check = $pdo->prepare("SELECT ATT_ID FROM attendance WHERE EMP_ID = ? AND ATT_DATE = ?");
        $check->execute([$emp_id, $currentDate]);
        
        if ($check->rowCount() > 0) {
            $message = "You have already timed in for today.";
        } else {
            // INSERT: We only put the Time In. Time Out is left NULL.
            $stmt = $pdo->prepare("INSERT INTO attendance (EMP_ID, ATT_TIME_IN, ATT_DATE) VALUES (?, ?, ?)");
            if ($stmt->execute([$emp_id, $currentTime, $currentDate])) {
                $message = "Success: Timed In at $currentTime";
            } else {
                $message = "Error recording Time In.";
            }
        }
    }

    // --- TIME OUT LOGIC (UPDATE) ---
    if (isset($_POST['time_out'])) {
        // Validation: Find the specific row for TODAY that is still 'open' (Time Out is NULL)
        $query = "SELECT ATT_ID FROM attendance 
                  WHERE EMP_ID = ? AND ATT_DATE = ? AND ATT_TIME_OUT IS NULL 
                  ORDER BY ATT_ID DESC LIMIT 1";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$emp_id, $currentDate]);
        $row = $stmt->fetch();

        if ($row) {
            $att_id = $row['ATT_ID'];
            
            // UPDATE: Only update Time Out. SQL handles the calculation.
            $update = $pdo->prepare("UPDATE attendance SET ATT_TIME_OUT = ? WHERE ATT_ID = ?");
            if ($update->execute([$currentTime, $att_id])) {
                $message = "Success: Timed Out at $currentTime.";
            } else {
                $message = "Error recording Time Out.";
            }
        } else {
            $message = "Error: You cannot Time Out because you haven't Timed In yet (or you already finished).";
        }
    }
}

// 4. Fetch Current Status for Display
$stmt = $pdo->prepare("SELECT ATT_TIME_IN, ATT_TIME_OUT FROM attendance WHERE EMP_ID = ? AND ATT_DATE = ?");
$stmt->execute([$emp_id, $currentDate]);
$attendance = $stmt->fetch();

$timeInDisplay = $attendance['ATT_TIME_IN'] ?? '--:--:--';
$timeOutDisplay = $attendance['ATT_TIME_OUT'] ?? '--:--:--';

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Attendance Checker</title>
  <style>
    body { font-family: sans-serif; padding: 20px; background: #f4f4f4; text-align: center; }
    .container { background: white; max-width: 500px; margin: 30px auto; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    button { padding: 15px 30px; margin: 10px; cursor: pointer; border: none; border-radius: 5px; font-size: 16px; color: white; }
    .btn-in { background-color: #28a745; }
    .btn-out { background-color: #dc3545; }
    .btn-leave { background-color: #007bff; }
    .btn-logout { background-color: #6c757d; padding: 10px 20px; font-size: 14px; margin-top: 20px; }
    .message { margin-bottom: 20px; padding: 10px; background: #e2e3e5; border-radius: 4px; color: #383d41; }
    .status-box { background: #f8f9fa; border: 1px solid #ddd; padding: 15px; margin: 20px 0; border-radius: 5px; }
  </style>
</head>

<body>
  <div class="container">
      <h1 id="company">Payroll Inc. Dashboard</h1>
      <p>Welcome, <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></strong>!</p>
      
      <?php if ($message): ?>
          <div class="message"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <div class="status-box">
          <p><strong>Today's Status:</strong></p>
          <p>Time In: <?php echo htmlspecialchars($timeInDisplay); ?></p>
          <p>Time Out: <?php echo htmlspecialchars($timeOutDisplay); ?></p>
      </div>

      <!-- Functional Buttons -->
      <form method="POST" action="user.php">
          <div class="btn-group">
              <!-- Disable buttons logically based on state for better UX -->
              <?php if (empty($attendance['ATT_TIME_IN'])): ?>
                  <button type="submit" name="time_in" class="btn-in">Time In</button>
              <?php elseif (empty($attendance['ATT_TIME_OUT'])): ?>
                  <button type="submit" name="time_out" class="btn-out">Time Out</button>
              <?php else: ?>
                  <p style="color: green;">Attendance Completed for Today</p>
              <?php endif; ?>
          </div>
      </form>

      <div class="btn-group">
          <button type="button" class="btn-leave" onclick="window.location.href='leave.php'">File a Leave</button>
      </div>
      
      <button type="button" class="btn-logout" onclick="window.location.href='logout.php'">Logout</button>
  </div>
</body>
</html>