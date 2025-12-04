<?php
session_start();

// Ensure the user is logged in
if (empty($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="styles.css" />
  <title>Attendance Checker</title>
  <style>
    body { font-family: sans-serif; padding: 20px; }
    button { padding: 10px 20px; margin: 5px; cursor: pointer; }
    .btn-group { margin: 20px 0; }
  </style>
</head>

<body>
  <h1 id="company">This is the Payroll Inc. control panel.</h1>
  <p>Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>!</p>
  
  <div class="btn-group">
      <button type="button">Time Out</button>
      
      <!-- FIX: This button now explicitly links to leave.php -->
      <!-- We use window.location.href for a reliable redirect -->
      <button type="button" onclick="window.location.href='leave.php'">File a Leave</button>
  </div>
  
  <button type="button" onclick="window.location.href='logout.php'">Logout</button>
</body>

</html>