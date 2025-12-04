<?php
session_start();

// 1. Basic Login Check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 2. Employee Link Check
// If emp_id is missing or NULL, we flag it.
$is_employee = !empty($_SESSION['emp_id']);
$emp_id = $is_employee ? (int)$_SESSION['emp_id'] : 0;

// DB connection
$dbUser = 'root';
$dbPass = '';
$dbName = 'project';
$dbHost = 'localhost';
$dbPort = '3306';
$charset = 'utf8mb4';

$dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (PDOException $e) {
    die('DB Connection failed: ' . $e->getMessage());
}

function post($key) {
    return isset($_POST[$key]) ? trim($_POST[$key]) : null;
}

$errors = [];
$messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Safety Net: Block submission if not an employee
    if (!$is_employee) {
        $errors[] = "You cannot perform actions because your account is not linked to an employee profile.";
    } else {
        $action = post('action') ?? '';

        if ($action === 'create') {
            $lve_type = post('lve_type') ?: 'UNPAID';
            $lve_start_date = post('lve_start_date') ?: date('Y-m-d');
            $lve_duration = post('lve_duration') ?: '00:00:00'; 
            $lve_reason = post('reason') ?: '';

            if ($lve_reason === '') {
                $errors[] = "Please enter a reason for the leave.";
            }

            if (empty($errors)) {
                try {
                    $sql = "INSERT INTO leave_request (EMP_ID, LVE_TYPE, LVE_DATE_FILLED, LVE_DURATION, LVE_REASON, LVE_STATUS)
                            VALUES (:emp_id, :lve_type, :date_filled, :duration, :reason, 'PENDING')";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        'emp_id' => $emp_id,
                        'lve_type' => $lve_type,
                        'date_filled' => $lve_start_date,
                        'duration' => $lve_duration,
                        'reason' => $lve_reason
                    ]);
                    $messages[] = "Leave submitted successfully.";
                    header("Location: leave.php");
                    exit;
                } catch (PDOException $e) {
                    // Check for that specific foreign key error
                    if ($e->getCode() == 23000) {
                        $errors[] = "System Error: Your User ID is linked to Employee ID #$emp_id, but that Employee ID does not exist in the database.";
                    } else {
                        $errors[] = "Database error: " . $e->getMessage();
                    }
                }
            }
        }
        // ... (Update and Delete logic would go here, same as before) ...
    }
}

// Fetch user's leaves (Only if they are an employee)
$leaves = [];
if ($is_employee) {
    try {
        $stmt = $pdo->prepare("SELECT lr.*
                               FROM leave_request lr
                               WHERE lr.EMP_ID = :emp_id
                               ORDER BY lr.LVE_DATE_FILLED DESC, lr.LVE_ID DESC");
        $stmt->execute(['emp_id' => $emp_id]);
        $leaves = $stmt->fetchAll();
    } catch (PDOException $e) {
        $errors[] = "Could not fetch leaves.";
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>My Leaves</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
body{font-family: Arial, Helvetica, sans-serif; margin:20px; background:#f6f6f6}
.container{max-width:900px;margin:0 auto;background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.06)}
.error{padding:15px;background:#ffe6e6;border:1px solid #ffbdbd;margin-bottom:12px; border-left: 5px solid #d00;}
.notice{padding:15px;background:#e6ffed;border:1px solid #bdeec7;margin-bottom:12px; border-left: 5px solid #0d0;}
.btn{padding:8px 15px;border:0;border-radius:4px;cursor:pointer; font-size: 14px;}
.btn-primary{background:#2563eb;color:#fff}
table {width: 100%; border-collapse: collapse; margin-top: 20px;}
th, td {border: 1px solid #ddd; padding: 8px; text-align: left;}
</style>
</head>
<body>
<div class="container">
    <h1>File a Leave</h1>

    <?php if (!$is_employee): ?>
        <div class="error">
            <strong>Account not linked!</strong><br>
            You are logged in as a User, but your account is not linked to an Employee ID.
            <br><br>
            <em>Admin Fix: Run <code>UPDATE users SET emp_id = 1 WHERE username = '<?php echo $_SESSION['username']; ?>';</code></em>
        </div>
    <?php else: ?>
        
        <?php foreach($errors as $e): ?>
            <div class="error"><?php echo htmlspecialchars($e); ?></div>
        <?php endforeach; ?>

        <?php foreach($messages as $m): ?>
            <div class="notice"><?php echo htmlspecialchars($m); ?></div>
        <?php endforeach; ?>

        <!-- New leave form -->
        <form method="post" action="leave.php">
            <input type="hidden" name="action" value="create">
            <label>Type:
                <select name="lve_type">
                    <option value="PAID">PAID</option>
                    <option value="UNPAID" selected>UNPAID</option>
                </select>
            </label>
            <br><br>
            <label>Start date: <input type="date" name="lve_start_date" value="<?php echo date('Y-m-d'); ?>" required></label>
            <br><br>
            <label>Duration (HH:MM:SS): <input name="lve_duration" value="08:00:00"></label>
            <br><br>
            <label>Reason:</label><br>
            <textarea name="reason" rows="4" style="width:100%" required></textarea>
            <br><br>
            <button class="btn btn-primary" type="submit">Submit Leave</button>
        </form>

        <h2>My Submitted Leaves</h2>
        <?php if (empty($leaves)): ?>
            <p>No leaves found.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Reason</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($leaves as $r): ?>
                    <tr>
                        <td><?php echo (int)$r['LVE_ID']; ?></td>
                        <td><?php echo htmlspecialchars($r['LVE_DATE_FILLED']); ?></td>
                        <td><?php echo htmlspecialchars($r['LVE_TYPE']); ?></td>
                        <td><?php echo htmlspecialchars($r['LVE_REASON']); ?></td>
                        <td><?php echo htmlspecialchars($r['LVE_STATUS']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>
    
    <p style="margin-top:20px;"><a href="user.php">Return to menu</a></p>
</div>
</body>
</html>