<?php 
//SQL CONNECTION ------------------------------------------------------
$user = 'root';
$password = ''; 
$database = 'project';
$servername = 'localhost'; 
$port = '3306'; 
$charset = 'utf8mb4';
$dsn = "mysql:host=$servername;port=$port;dbname=$database;charset=$charset";


$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, 
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,     
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $password, $options);
} catch (\PDOException $e) {

    die('Connection Error: ' . $e->getMessage());
}




// Data manipulation logic

if($_SERVER['REQUEST_METHOD'] === 'POST') {

    $employee_id = $_POST['id'] ?? $_POST['emp_id'] ?? null; // to avoid invalid data type input

    if (!is_numeric($employee_id)) {
        echo '<script type="text/javascript">';
        echo 'alert("Invalid employee ID. Please enter a numeric ID.");';
        echo 'window.history.back();';
        echo '</script>';
        exit;
    }

    if(isset($_GET['action'])) {
        $action = $_GET['action'];
        if($action === 'approve' && isset($_POST['id'])) {
            $emp_id = $_POST['id'];
            $stmt = $pdo->prepare("UPDATE leave_request SET LVE_STATUS = 'APPROVED' WHERE EMP_ID = :emp_id");
            $stmt->execute(['emp_id' => $emp_id]);
        } elseif($action === 'reject' && isset($_POST['emp_id'])) {
            $emp_id = $_POST['emp_id'];
            $stmt = $pdo->prepare("UPDATE leave_request SET LVE_STATUS = 'REJECTED' WHERE EMP_ID = :emp_id");
            $stmt->execute(['emp_id' => $emp_id]);
        }
    }
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
                        <form action ='/Payroll/admin.php?action=approve' method='post'>
                            <label for='emp-id-input'>EMPLOYEE ID:</label>
                            <input type='text' id='emp-id-input' name='id'/>
                            <button type='submit'>Approve Leave</button>
                        </form>
                        <form action ='/Payroll/admin.php?action=reject' method='post'>
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