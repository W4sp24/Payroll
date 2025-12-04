<?php 
// 1. Connection Parameters
$user = 'root';
$password = ''; // WARNING: Using root with no password is a security risk!
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

$tableName = 'payroll_computation_table';
// Generate Payroll Logic
if (isset($_GET['action']) && $_GET['action'] === 'generate') {

    if (!empty($_GET['pay-month']) && !empty($_GET['pay-period'])) {
        
        $rawDate = $_GET['pay-month'];
        
        $dateParts = explode('-', $rawDate);
        $selectedYear = $dateParts[0]; 
        $selectedMonth = $dateParts[1]; 
        $selectedPeriod = $_GET['pay-period'];  

        $sql = 'SELECT 
                employee_id AS "Employee ID", 
                employee_name AS "Employee_Name", 
                ROUND(monthly_salary + total_incentives + total_paid_leaves, 2) AS "Gross Salary" 
                FROM payroll_view 
                WHERE Pay_Period = :period 
                AND Pay_Month = :month 
                AND Pay_Year = :year';

        $stmt = $pdo->prepare($sql);
        

        $stmt->execute([
            'period' => $selectedPeriod,
            'month'  => $selectedMonth,
            'year'   => $selectedYear
        ]); 

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $error = "Please select both a month and a period.";
    }
}
//finalize payroll logic

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process') {
    
    $finalizeYear = $_POST['h_year'];
    $finalizeMonth = $_POST['h_month'];
    $finalizePeriod = $_POST['h_period'];

    $payDate = date('Y-m-d'); 

    try {

        $sqlInsert = "INSERT INTO payroll (EMP_ID, PAY_AMOUNT, PAY_DATE)
                      SELECT 
                          employee_id, 
                          ROUND(monthly_salary + total_incentives + total_paid_leaves, 2),
                          :payDateStr
                      FROM payroll_view
                      WHERE Pay_Year = :year AND Pay_Month = :month AND Pay_Period = :period";
        $stmt = $pdo->prepare($sqlInsert);
        $stmt->execute([
            'payDateStr' => $payDate, 
            'year' => $finalizeYear, 
            'month' => $finalizeMonth, 
            'period' => $finalizePeriod
        ]);
        
        $count = $stmt->rowCount();
        
        if ($count > 0) {
            // Success Message
            echo "<script>
                    alert('SUCCESS: Payroll processed for $count employees!\\nPay Date recorded as: $payDate');
                    window.location.href = 'payroll.php'; 
                  </script>";
        } else {
            // No Data Found Message
            echo "<script>
                    alert('No employees found to pay for this period.');
                    window.history.back();
                  </script>";
        }

    } catch (PDOException $e) {
 
        if ($e->errorInfo[1] == 1062) {
            echo "<script>
                    alert('WARNING: Payroll for Date $payDate has already been processed!\\nYou cannot process the same period twice on the same day.');
                    window.history.back(); 
                  </script>";
        } else {
            echo "<script>alert('Database Error: " . addslashes($e->getMessage()) . "');</script>";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Employee Payroll</title>
    <link rel="stylesheet" type="text/css" href="payroll.css">

</head>
<body>
    <!-- add a wrapper and class to the form so CSS can target it -->
    <div class="form-container">
        <form class="payroll-form" action='payroll.php' method='GET'>
            <input type="hidden" name="action" value="generate">
            <label>Input Month</label>
            <input type='month' name='pay-month' required/>
            <p class="form-title">Select Pay Period</p>
            <label><input type='radio' name='pay-period' value="1" required/> 1st Period</label>
            <label><input type='radio' name='pay-period' value="2"/> 2nd Period</label>
            <button type='submit' class="btn-generate">Generate payroll</button>
        </form>
    </div>
    <?php if (!empty($results)): ?>
        <div class="table-wrapper">
            <table class="employee-payroll-table">
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Employee Name</th>
                        <th>Gross Salary</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['Employee ID']); ?></td>
                            <td><?php echo htmlspecialchars($row['Employee_Name']); ?></td>
                            <td>$<?php echo number_format($row['Gross Salary'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class='btn'>
            <form action='payroll.php' method='POST'>
                <input type="hidden" name="action" value='process'>
                <input type="hidden" name="h_year" value="<?php echo $selectedYear; ?>">
                <input type="hidden" name="h_month" value="<?php echo $selectedMonth; ?>">
                <input type="hidden" name="h_period" value="<?php echo $selectedPeriod; ?>">
                <button>Process</button>
            </form>

        <div>
    <?php elseif (isset($_GET['action'])): ?>
        <div style="text-align:center; color: red;">
            <p>No records found for this period.</p>
        </div>
    <?php endif; ?>


</body>   
</html>
