<?php 
//SQL CONNECTION ------------------------------------------------------
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
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

    $lve_id_input = $_POST['lve_id'] ?? $_POST['id'] ?? null; 

    if (!is_numeric($lve_id_input)) {
        echo '<script type="text/javascript">';
        echo 'alert("Invalid Leave ID. Please enter a numeric ID.");';
        echo 'window.history.back();';
        echo '</script>';
        exit;
    }

    if(isset($_GET['action'])) {
        $action = $_GET['action'];

        if($action === 'approve') {
            $stmt = $pdo->prepare("UPDATE leave_request SET LVE_STATUS = 'Approved' WHERE LVE_ID = :id");
            $stmt->execute(['id' => $lve_id_input]);
            
        } elseif($action === 'reject') {
            $stmt = $pdo->prepare("UPDATE leave_request SET LVE_STATUS = 'Rejected' WHERE LVE_ID = :id");
            $stmt->execute(['id' => $lve_id_input]);
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
  <title>Admin Dashboard</title>
</head>

<body>
    <header>
        <nav class="nav-bar">
            <ul class="nav-elements">
                <li class="nav-button"><a href="/Payroll/payroll.php">Employee Payroll Table</a></li>
                <li class="nav-button"><a href="/Payroll/incentives.php">Manage Incentives    </a></li>
                <li class="nav-button"><a href="/Payroll/employee.php">Employee Management</a></li>
            </ul> 
        </nav>
        
        <div class='util-container'>
            <div class='table-action-section'>
                <div class="table-wrapper">
                    <table class="table">
                        <caption>Recent Leave Requests</caption>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Employee</th>
                                <th>Type</th>
                                <th>Date Filed</th>
                                <th>Duration</th>
                                <th>Reason</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                $stmt = $pdo->query("
                                    SELECT
                                        lr.LVE_ID,
                                        e.EMP_NAME,
                                        lr.LVE_TYPE,
                                        lr.LVE_DATE_FILLED,
                                        lr.LVE_DURATION,
                                        lr.LVE_REASON,
                                        lr.LVE_STATUS
                                    FROM
                                        leave_request AS lr
                                    LEFT JOIN
                                        employee AS e ON lr.EMP_ID = e.EMP_ID
                                    ORDER BY
                                        LVE_ID DESC
                                    LIMIT 50
                                ");
                                
                                $results = $stmt->fetchAll();
                                
                                if (!empty($results)) {
                                    foreach ($results as $row) {
                                        echo '<tr>';
                                        echo '<td>' . htmlspecialchars($row['LVE_ID']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['EMP_NAME']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['LVE_TYPE']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['LVE_DATE_FILLED']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['LVE_DURATION']) . '</td>';
                                        
                                        // Specific class for Reason to handle long text
                                        echo '<td class="reason-cell" title="'.htmlspecialchars($row['LVE_REASON']).'">' . htmlspecialchars($row['LVE_REASON']) . '</td>';
                                        
                                        // Status Badge Logic
                                        $statusClass = 'status-' . $row['LVE_STATUS'];
                                        echo '<td><span class="status-badge ' . $statusClass . '">' . htmlspecialchars($row['LVE_STATUS']) . '</span></td>';
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="7" style="text-align:center;">No leave requests found</td></tr>';
                                }
                            } catch (\PDOException $e) {
                                echo '<tr><td colspan="7" style="color:red;">Error: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <div class='actions'>
                    <h3>Process Request</h3>
                    <!-- Unified Input Name to 'lve_id' -->
                    <div style="display:flex; gap: 10px;">
                        <form action='/Payroll/admin.php?action=approve' method='post'>
                            <label for='approve-input'>LVE ID:</label>
                            <input type='text' id='approve-input' name='lve_id' required style="width:60px;" placeholder="ID"/>
                            <button type='submit' style="background-color: #059669; color: white; cursor: pointer;">Approve</button>
                        </form>
                        
                        <form action='/Payroll/admin.php?action=reject' method='post'>
                            <label for='reject-input'>LVE ID:</label>
                            <input type='text' id='reject-input' name='lve_id' required style="width:60px;" placeholder="ID"/>
                            <button type='submit' style="background-color: #dc2626; color: white; cursor: pointer;">Reject</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>
</body>
</html>