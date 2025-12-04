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
?>


<!DOCTYPE html>
<html>
    <head>
        <title>Employee Management</title>
        <link rel="stylesheet" href="employee.css">
    </head>
    <body>
        <div class='container-tables'>
            <div class='table-employees'>
                <div class="table-wrapper">
                    <table class="table">
                        <caption>Employee List</caption>
                        <thead>
                            <tr>
                                <th>EMP ID</th>
                                <th>EMP NAME</th>
                                <th>DEPARTMENT</th>
                                <th>JOB POSITION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                $stmt = $pdo->query("
                                    SELECT 
                                        EMP_ID, 
                                        EMP_NAME, 
                                        d.DEPT_NAME, 
                                        jp.JP_NAME 
                                    FROM 
                                        employee
                                    JOIN 
                                        department d USING(DEPT_ID) 
                                    JOIN 
                                        job_position jp USING(JP_ID)
                                    WHERE 
                                        status = 'ACTIVE'
                                ");
                                
                                // IMPORTANT: Use FETCH_ASSOC so we don't get duplicate columns in the loop
                                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                if (!empty($results)) {
                                    foreach ($results as $row) {
                                        echo '<tr>';
                                        // This loop automatically prints the 4 columns selected in the query
                                        foreach ($row as $cell) {
                                            echo '<td>' . htmlspecialchars($cell) . '</td>';
                                        }
                                        echo '</tr>';
                                    }
                                } else {
                                    // Changed colspan to 4 because we now have 4 headers
                                    echo '<tr><td colspan="4">No employees found</td></tr>';
                                }
                            } catch (\PDOException $e) {
                                echo '<tr><td colspan="4">Error: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </body>
</html>