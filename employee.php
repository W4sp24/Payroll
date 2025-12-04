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
                        <caption>Active Employee List</caption>
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
            <div class='table-employees'>
                <div class="table-wrapper">
                    <table class="table">
                        <caption>Department List</caption> <thead>
                            <tr>
                                <th>DEPT ID</th>
                                <th>DEPT NAME</th>
                                </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                $stmt = $pdo->query("SELECT * FROM department");
                                
                                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                if (!empty($results)) {
                                    foreach ($results as $row) {
                                        echo '<tr>';
                             
                                        foreach ($row as $cell) {
                                            echo '<td>' . htmlspecialchars($cell) . '</td>';
                                        }
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="2">No departments found</td></tr>';
                                }
                            } catch (\PDOException $e) {
                                echo '<tr><td colspan="2">Error: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class='table-employees'>
                <div class="table-wrapper">
                    <table class="table">
                        <caption>Job Position List</caption>
                        <thead>
                            <tr>
                                <th>Position ID</th>
                                <th>Job Position</th>
                                <th>Job Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                // 1. YOUR NEW QUERY
                                // Note: I used single quotes for the space ' ' to keep the PHP string clean
                                $stmt = $pdo->query("
                                    SELECT 
                                        JP_ID as 'Position ID', 
                                        CONCAT(JP_LEVEL, ' ', JP_NAME) as 'Job_Position', 
                                        JP_TYPE as 'Job_Type' 
                                    FROM job_position
                                ");
                                
                                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                if (!empty($results)) {
                                    foreach ($results as $row) {
                                        echo '<tr>';
                                        // This generic loop automatically outputs your 3 new columns
                                        foreach ($row as $cell) {
                                            echo '<td>' . htmlspecialchars($cell) . '</td>';
                                        }
                                        echo '</tr>';
                                    }
                                } else {
                                    // 2. UPDATED COLSPAN to 3
                                    echo '<tr><td colspan="3">No job positions found</td></tr>';
                                }
                            } catch (\PDOException $e) {
                                // 3. UPDATED COLSPAN to 3
                                echo '<tr><td colspan="3">Error: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class='data-manipulation'>
            <div class='action-button'>
                <a href='data_manipulation.php?action=addEmployee'><button>Add Employee</button></a>
            </div>
            <div class='action-button'>
                <a href='data_manipulation.php?action=fireEmployee'><button>Fire Employee</button></a>
            </div>
            <div class='action-button'>
                <a href='data_manipulation.php?action=updateEmployee'><button>Update Employee</button></a>
            </div>
            <div class='action-button'>
                <a href='data_manipulation.php?action=addDepartment'><button>Add Department</button></a>
            </div>
            <div class='action-button'>
                <a href='data_manipulation.php?action=updateDepartment'><button>Update Department</button></a>
            </div>
            <div class='action-button'>
                <a href='data_manipulation.php?action=deleteDepartment'><button>Delete Job Department</button></a>
            </div>
            <div class='action-button'>
                <a href='data_manipulation.php?action=addJobPosition'><button>Add Job Position</button></a>
            </div>
            <div class='action-button'>
                <a href='data_manipulation.php?action=updateJobPosition'><button>Update Job Position</button></a>
            </div>
            <div class='action-button'>
                <a href='data_manipulation.php?action=deleteJobPosition'><button>Delete Job Position</button></a>
            </div>
        </div>

    </body>
</html>