<?php 
// 1. Connection Parameters
$user = 'root';
$password = ''; // WARNING: Using root with no password is a security risk!
$database = 'project';
// Use the standard DSN format for PDO. Port is often omitted if standard 3306.
$servername = 'localhost'; 
$port = '3306'; 
$charset = 'utf8mb4';
$dsn = "mysql:host=$servername;port=$port;dbname=$database;charset=$charset";


// 2. PDO Options (These are fine)
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,     // Return results as associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// 3. ESTABLISH THE PDO CONNECTION ($pdo)
try {
    $pdo = new PDO($dsn, $user, $password, $options);
} catch (\PDOException $e) {
    // If connection fails, stop script and display error
    die('Connection Error: ' . $e->getMessage());
}


// --- The rest of your code using $pdo is now correct ---

$tableName = 'employee';

// SQL Query
$sql = "SELECT * FROM $tableName";

// Prepare the statement (Now uses the created $pdo object)
$stmt = $pdo->prepare($sql);

// Execute the statement
$stmt->execute();

// Fetch all rows as an associative array
$results = $stmt->fetchAll();

// Get the column names to use as table headers
// This assumes the table is not empty
if ($results) {
    $columns = array_keys($results[0]);
    
    echo "<h1>Data from the '$tableName' table</h1>";
    echo "<table border='1' cellpadding='10' cellspacing='0'>";
    
    // Create the table header row
    echo "<tr>";
    foreach ($columns as $columnName) {
        // Display column names as bold headers
        echo "<th>" . htmlspecialchars($columnName) . "</th>";
    }
    echo "</tr>";
    
    // Loop through the data (rows)
    foreach ($results as $row) {
        echo "<tr>";
        // Loop through the columns for each row
        foreach ($columns as $columnName) {
            // Display the value for the current cell
            echo "<td>" . htmlspecialchars($row[$columnName]) . "</td>";
        }
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>The table '$tableName' is empty or does not exist.</p>";
}
?>