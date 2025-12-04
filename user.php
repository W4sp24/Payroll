<?php
session_start();
// This check is correct
if (empty($_SESSION['user_id'])) {
  header('Location: login.php'); // Changed index.html to login.php so it goes to the PHP logic
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
  </style>
</head>

<body>
  <h1 id="company">This is the Payroll Inc. control panel.</h1>
  <p>Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>!</p>
  <div>
      <button type="button">Time Out</button>
      <!-- This link works now because leave.php has been fixed -->
      <button type="button" onclick="document.location='leave.php'">File a Leave</button>
  </div>
  <button type="button" onclick="document.location='logout.php'">Logout</button>
</body>

</html>