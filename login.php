

<?php
//SQL CONNECTION ------------------------------------------------------
$host = 'localhost';          
$db   = 'your_database_name';
$user = 'your_db_user';
$pass = 'your_db_password';
$dsn  = "mysql:host=$host;dbname=$db"; 

try {

    $pdo = new PDO($dsn, $user, $pass);
    

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    die("Connection failed: " . $e->getMessage());
}
//SQL CONNECTION ------------------------------------------------------

print("Hello world");





?>