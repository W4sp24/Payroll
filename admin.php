<?php 
//SQL CONNECTION ------------------------------------------------------
$user = 'root';
$password = ''; // WARNING: Using root with no password is a security risk!
$database = 'project';
$servername = 'localhost'; 
$port = '3306'; 
$charset = 'utf8mb4';
$dsn = "mysql:host=$servername;port=$port;dbname=$database;charset=$charset";


// 2. PDO Options (These are fine)
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, 
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,     
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// 3. ESTABLISH THE PDO CONNECTION ($pdo)
try {
    $pdo = new PDO($dsn, $user, $password, $options);
} catch (\PDOException $e) {

    die('Connection Error: ' . $e->getMessage());
}

?>


<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="admin.css" />
  <title>Admin</title>
</head>

<body>
    <header>
        <nav class="nav-bar">
            <ul class="nav-elements">
                <li class="nav-button"><a href="/Payroll/payroll.php">Employee Payroll Table</a></li>
                <li class="nav-button"><a href="#">Simlulate Attendance(FOR DEMO ONLY)</a></li>
                <li class="nav-button"><a href="#">Employee Management</a></li>
            </ul> 
        </nav>
        <div class='util-container'>
            <div class='table-action-section'>
                <div class="table-wrapper">
                    <table class="table">
                        <caption>Recent Leave Request   </caption>
                        <thead>
                            <tr>
                                <th>EMP ID</th>
                                <th>EMP NAME</th>
                                <th>STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                $stmt = $pdo->query("
                                    SELECT
                                        lr.EMP_ID,
                                        e.EMP_NAME,
                                        lr.LVE_STATUS
                                    FROM
                                        leave_request AS lr
                                    LEFT JOIN
                                        employee AS e ON lr.EMP_ID = e.EMP_ID
                                    ORDER BY
                                        lr.EMP_ID DESC
                                    LIMIT 50
                                    ");
                                
                                $results = $stmt->fetchAll();
                                
                                if (!empty($results)) {
                                    foreach ($results as $row) {
                                        echo '<tr>';
                                        foreach ($row as $cell) {
                                            echo '<td>' . htmlspecialchars($cell) . '</td>';
                                        }
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="3">No leave requests found</td></tr>';
                                }
                            } catch (\PDOException $e) {
                                echo '<tr><td colspan="3">Error: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <div class='actions'>
                        <form>
                            <label for='emp-id-input'>EMPLOYEE ID:</label>
                            <input type='text' id='emp-id-input' name='emp_id'/>
                            <button type='submit'>Approve Leave</button>
                        </form>
                        <form>
                            <label for='emp-id-input'>EMPLOYEE ID:</label>
                            <input type='text' id='emp-id-input' name='emp_id'/>
                            <button type='submit'>Reject Leave</button>
                        </form>
                </div>
            </div>

            
        </div>
    </header>
    
</body>

</html>