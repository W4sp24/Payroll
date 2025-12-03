<?php 
// 1. Connection Parameters
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




$tableName = 'payroll_computation_table';

// SQL Query
$sql = "SELECT employee_id AS ID,employee_name AS EmpName, ROUND(COALESCE(monthly_salary,0)+ COALESCE(total_incentives,0)+ COALESCE(total_paid_leaves,0),2) AS GrossSalary FROM payroll_computation_table;";


$stmt = $pdo->prepare($sql);

// Execute the statement
$stmt->execute();


$results = $stmt->fetchAll();

$current_date = date('F-Y');

?>

<!DOCTYPE html>
<html>
<head>
    <title>Employee Payroll</title>
    <link rel="stylesheet" type="text/css" href="payroll.css">

</head>
<body>
    <h1>Employee Payroll As of <?php echo $current_date; ?> </h1>
    <div class="table-wrapper">
        <table class="employee-payroll-table">
            <thead>
                <tr>
                    <?php
                    if (!empty($results)) {
                        foreach (array_keys($results[0]) as $columnName) {
                            echo "<th>" . htmlspecialchars($columnName) . "</th>";
                        }
                    }
                    ?>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($results as $row) {
                    echo "<tr>";
                    foreach ($row as $cell) {
                        echo "<td>" . htmlspecialchars($cell) . "</td>";
                    }
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>   
</html>
