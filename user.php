<?php
session_start();
if (empty($_SESSION['user_id'])) {
  header('Location: index.html');
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
</head>

<body>
  <h1 id="company">This is the Payroll Inc. control panel.</h1>
  <div>
      <button type="timeout" value="timeout">Time Out</button>
      <button type="leave" onclick="document.location='leave.php'">File a Leave</button>
  </div>
  <button type="logout" onclick="document.location='logout.php'">Logout</button>
</body>

</html>
