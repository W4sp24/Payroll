<?php
session_start();


$dbUser = 'root';
$dbPass = '';
$dbName = 'project';
$dsn = "mysql:host=localhost;dbname=$dbName;charset=utf8mb4";
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (PDOException $e) {
    die("Connection error: " . $e->getMessage());
}

$message = '';
$editAccount = null; 


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    
    if ($action === 'create') {
        $emp_id = (int)$_POST['emp_id_selection'];
        $username = trim($_POST['username']);
        $password = $_POST['password'];

     
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE emp_id = ?");
        $checkStmt->execute([$emp_id]);
        
        if ($checkStmt->rowCount() > 0) {
            $message = "Error: This employee already has a login account.";
        } else {
            // Hash Password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            try {
                $stmt = $pdo->prepare("INSERT INTO users (username, password, emp_id) VALUES (:user, :pass, :eid)");
                $stmt->execute([
                    'user' => $username, 
                    'pass' => $hashedPassword, 
                    'eid' => $emp_id
                ]);
                $message = "Account created successfully!";
            } catch (PDOException $e) {
                $message = "Database Error: " . $e->getMessage();
            }
        }
    }

    // B. UPDATE (Change password or username)
    elseif ($action === 'update') {
        $id = (int)$_POST['user_table_id'];
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        try {
            if (!empty($password)) {
           
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET username = :user, password = :pass WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['user' => $username, 'pass' => $hashedPassword, 'id' => $id]);
            } else {
       
                $sql = "UPDATE users SET username = :user WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['user' => $username, 'id' => $id]);
            }
            $message = "Account updated successfully!";
            header("Location: manage_employees.php?msg=updated");
            exit;
        } catch (PDOException $e) {
            $message = "Error updating: " . $e->getMessage();
        }
    }

    // C. DELETE (Revoke Access)
    elseif ($action === 'delete') {
        $id = (int)$_POST['user_table_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $message = "Access revoked (Account deleted).";
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
        }
    }
}


if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute(['id' => $_GET['edit']]);
    $editAccount = $stmt->fetch();
}


$orphanSql = "SELECT e.EMP_ID, e.EMP_NAME 
              FROM employee e 
              LEFT JOIN users u ON e.EMP_ID = u.emp_id 
              WHERE u.id IS NULL 
              ORDER BY e.EMP_NAME ASC";
$employeesWithoutAccounts = $pdo->query($orphanSql)->fetchAll();


$accountsSql = "SELECT u.id as user_id, u.username, e.EMP_NAME, e.status 
                FROM users u 
                JOIN employee e ON u.emp_id = e.EMP_ID 
                ORDER BY u.id DESC";
$accounts = $pdo->query($accountsSql)->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage User Accounts</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #f4f4f4; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #eee; }
        .btn { padding: 5px 10px; text-decoration: none; color: white; border-radius: 4px; border:none; cursor: pointer; }
        .btn-edit { background: #f39c12; }
        .btn-delete { background: #c0392b; }
        .btn-save { background: #27ae60; padding: 10px 20px; font-size: 16px; }
        .form-box { background: #eef; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        input, select { padding: 8px; width: 100%; box-sizing: border-box; margin-bottom: 10px; }
        label { font-weight: bold; display: block; margin-bottom: 5px; }
    </style>
</head>
<body>

<div class="container">
    <h2 style="text-align:center">User Account Provisioning</h2>

    <?php if ($message || isset($_GET['msg'])): ?>
        <p style="background: #d4edda; color: #155724; padding: 10px; text-align: center;">
            <?php echo $message ?: "Action completed successfully."; ?>
        </p>
    <?php endif; ?>

    <!-- FORM -->
    <div class="form-box">
        <h3><?php echo $editAccount ? 'Edit Login Credentials' : 'Create Login for Existing Employee'; ?></h3>
        
        <form method="POST" action="manage_accounts.php">
            <input type="hidden" name="action" value="<?php echo $editAccount ? 'update' : 'create'; ?>">
            
            <?php if ($editAccount): ?>
                <!-- Update Mode: We need the User Table ID -->
                <input type="hidden" name="user_table_id" value="<?php echo $editAccount['id']; ?>">
                <p><strong>Editing Account For:</strong> (Employee ID: <?php echo $editAccount['emp_id']; ?>)</p>
            <?php else: ?>
                <!-- Create Mode: Select Employee -->
                <label>Select Employee</label>
                <select name="emp_id_selection" required>
                    <option value="">-- Choose an Employee --</option>
                    <?php foreach ($employeesWithoutAccounts as $emp): ?>
                        <option value="<?php echo $emp['EMP_ID']; ?>">
                            <?php echo htmlspecialchars($emp['EMP_NAME']); ?> (ID: <?php echo $emp['EMP_ID']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($employeesWithoutAccounts)): ?>
                    <p style="color:red; font-size:0.9em;">* All current employees already have accounts.</p>
                <?php endif; ?>
            <?php endif; ?>

            <label>Username</label>
            <input type="text" name="username" required 
                   value="<?php echo $editAccount ? htmlspecialchars($editAccount['username']) : ''; ?>"
                   placeholder="Enter username">

            <label>Password <?php echo $editAccount ? '(Leave blank to keep current)' : '(Required)'; ?></label>
            <input type="password" name="password" 
                   <?php echo $editAccount ? '' : 'required'; ?> 
                   placeholder="Enter password">

            <button type="submit" class="btn btn-save">
                <?php echo $editAccount ? 'Update Credentials' : 'Create Account'; ?>
            </button>
            
            <?php if ($editAccount): ?>
                <a href="manage_accounts.php" style="margin-left:10px;">Cancel</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- TABLE -->
    <h3>Active User Accounts</h3>
    <table>
        <thead>
            <tr>
                <th>User ID</th>
                <th>Employee Name</th>
                <th>Username</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($accounts as $acc): ?>
                <tr>
                    <td><?php echo $acc['user_id']; ?></td>
                    <td><?php echo htmlspecialchars($acc['EMP_NAME']); ?></td>
                    <td><?php echo htmlspecialchars($acc['username']); ?></td>
                    <td><?php echo htmlspecialchars($acc['status']); ?></td>
                    <td>
                        <a href="manage_accounts.php?edit=<?php echo $acc['user_id']; ?>" class="btn btn-edit">Edit</a>
                        
                        <form method="POST" action="manage_accounts.php" style="display:inline-block;" 
                              onsubmit="return confirm('Revoke access for this user? The employee profile will remain.');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="user_table_id" value="<?php echo $acc['user_id']; ?>">
                            <button type="submit" class="btn btn-delete">Revoke</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>