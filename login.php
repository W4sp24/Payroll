<?php
session_start();

// Database connection settings: adjust as needed
$dbUser = 'root';
$dbPass = '';
$dbName = 'project';
$dbHost = 'localhost';
$dbPort = '3306';
$dbCharset = 'utf8mb4';

$dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=$dbCharset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $passwordInput = isset($_POST['password']) ? $_POST['password'] : '';

    if ($username === '' || $passwordInput === '') {
        $error = 'Please provide both username and password.';
    } else {
        try {
            $pdo = new PDO($dsn, $dbUser, $dbPass, $options);

            // Use prepared statements to avoid SQL injection
            $stmt = $pdo->prepare('SELECT id, password FROM users WHERE username = ? LIMIT 1');
            $stmt->execute([$username]);
            $row = $stmt->fetch();

            if ($row) {
                $passwordHash = $row['password'];
                if (password_verify($passwordInput, $passwordHash)) {
                    // after verifying password...
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $row['id'];
                    // If users table has emp_id column:
                    $_SESSION['emp_id'] = $row['emp_id']; // <-- required by leave.php
                    $_SESSION['username'] = $username;
                    header('Location: user.html');
                exit;

                } else {
                    $error = 'Invalid username or password.';
                }
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Login</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
        body{font-family:Arial,Helvetica,sans-serif;padding:2rem}
        form{max-width:320px;margin:0 auto}
        .error{color:#a00;margin-bottom:1rem}
        label{display:block;margin:.5rem 0 0.2rem}
        input[type="text"],input[type="password"]{width:100%;padding:.5rem}
        button{margin-top:1rem;padding:.5rem 1rem}
    </style>
</head>
<body>
    <h1>Login</h1>

    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES); ?></div>
    <?php endif; ?>

    <form method="post" action="">
        <label for="username">Username</label>
        <input id="username" name="username" type="text" value="<?php echo isset($username) ? htmlspecialchars($username, ENT_QUOTES) : ''; ?>" required>

        <label for="password">Password</label>
        <input id="password" name="password" type="password" required>

        <button type="submit">Sign in</button>
    </form>
</body>
</html>