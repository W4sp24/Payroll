<?php
session_start();

// 1. DATABASE CONNECTION
$user = 'root';
$password = '';
$database = 'project';
$servername = 'localhost';
$port = '3306';
$charset = 'utf8mb4';
$dsn = "mysql:host=$servername;port=$port;dbname=$database;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $password, $options);
} catch (PDOException $e) {
    die('Connection Failed: ' . $e->getMessage());
}

$message = '';
$errorType = '';
$editRow = null;

// --- ACTION HANDLING (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // A. INSERT INCENTIVE
    if ($action === 'add') {
        $emp_id = $_POST['emp_id'];
        $type = $_POST['inc_type'];
        $amount = $_POST['inc_amount'];
        $date = $_POST['inc_date'];

        if (empty($emp_id) || empty($amount) || empty($date)) {
            $message = "Error: All fields are required.";
            $errorType = "error";
        } elseif (!is_numeric($amount) || $amount <= 0) {
            $message = "Error: Amount must be a positive number.";
            $errorType = "error";
        } else {
            try {
        
                $stmt = $pdo->prepare("INSERT INTO incentive (EMP_ID, INC_TYPE, INC_AMOUNT, INC_DATE) VALUES (:eid, :type, :amt, :date)");
                $stmt->execute(['eid' => $emp_id, 'type' => $type, 'amt' => $amount, 'date' => $date]);
                $message = "Incentive added successfully.";
                $errorType = "success";
            } catch (PDOException $e) {
   
                if ($e->errorInfo[1] == 1452) {
                    $message = "Integrity Error: The selected Employee ID does not exist.";
                } else {
                    $message = "Database Error: " . $e->getMessage();
                }
                $errorType = "error";
            }
        }
    }


    elseif ($action === 'update') {
        $inc_id = $_POST['inc_id'];
        $emp_id = $_POST['emp_id'];
        $type = $_POST['inc_type'];
        $amount = $_POST['inc_amount'];
        $date = $_POST['inc_date'];

        if (!is_numeric($amount) || $amount <= 0) {
            $message = "Error: Amount must be a positive number.";
            $errorType = "error";
        } else {
            try {
       
                $stmt = $pdo->prepare("UPDATE incentive SET EMP_ID = :eid, INC_TYPE = :type, INC_AMOUNT = :amt, INC_DATE = :date WHERE INC_ID = :id");
                $stmt->execute(['eid' => $emp_id, 'type' => $type, 'amt' => $amount, 'date' => $date, 'id' => $inc_id]);
                $message = "Incentive updated successfully.";
                $errorType = "success";
      
                header("Location: incentives.php");
                exit;
            } catch (PDOException $e) {
                $message = "Error updating: " . $e->getMessage();
                $errorType = "error";
            }
        }
    }

    // C. DELETE INCENTIVE
    elseif ($action === 'delete') {
        $inc_id = $_POST['inc_id'];
        try {
            // Corrected table name to 'incentive'
            $stmt = $pdo->prepare("DELETE FROM incentive WHERE INC_ID = :id");
            $stmt->execute(['id' => $inc_id]);
            $message = "Incentive deleted.";
            $errorType = "success";
        } catch (PDOException $e) {
            $message = "Error deleting: " . $e->getMessage();
            $errorType = "error";
        }
    }
}


if (isset($_GET['edit'])) {

    $stmt = $pdo->prepare("SELECT * FROM incentive WHERE INC_ID = :id");
    $stmt->execute(['id' => $_GET['edit']]);
    $editRow = $stmt->fetch();
}


$query = "SELECT i.*, e.EMP_NAME 
          FROM incentive i
          LEFT JOIN employee e ON i.EMP_ID = e.EMP_ID
          ORDER BY i.INC_DATE DESC, i.INC_ID DESC";
$incentives = $pdo->query($query)->fetchAll();


$employees = $pdo->query("SELECT EMP_ID, EMP_NAME FROM employee WHERE status = 'ACTIVE'")->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Incentive Management</title>
    <!-- Basic Internal CSS for Layout -->
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f3f4f6; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        
        /* Card Style */
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        
        /* Buttons */
        .btn-back { display: inline-block; padding: 10px 15px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px; margin-bottom: 20px; }
        .btn-submit { padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .btn-edit { padding: 5px 10px; background: #ffc107; color: black; text-decoration: none; border-radius: 4px; font-size: 14px; }
        .btn-delete { padding: 5px 10px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }
        
        /* Form Inputs */
        label { display: block; margin: 10px 0 5px; font-weight: bold; }
        input, select { width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px; box-sizing: border-box; }
        
        /* Table */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; border-bottom: 1px solid #dee2e6; text-align: left; }
        th { background-color: #e9ecef; }
        
        /* Alerts */
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid transparent; }
        .alert-success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
        .alert-error { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
    </style>
</head>
<body>

<div class="container">
    <a href="admin.php" class="btn-back">&larr; Back to Admin</a>

    <?php if ($message): ?>
        <div class="alert <?php echo ($errorType === 'error') ? 'alert-error' : 'alert-success'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- FORM SECTION -->
    <div class="card">
        <h2><?php echo $editRow ? 'Edit Incentive' : 'Add New Incentive'; ?></h2>
        
        <form method="POST" action="incentives.php">
            <input type="hidden" name="action" value="<?php echo $editRow ? 'update' : 'add'; ?>">
            <?php if ($editRow): ?>
                <input type="hidden" name="inc_id" value="<?php echo $editRow['INC_ID']; ?>">
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <!-- Employee Dropdown -->
                <div>
                    <label>Employee</label>
                    <select name="emp_id" required>
                        <option value="">-- Select Employee --</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?php echo $emp['EMP_ID']; ?>" 
                                <?php if ($editRow && $editRow['EMP_ID'] == $emp['EMP_ID']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($emp['EMP_NAME']); ?> (ID: <?php echo $emp['EMP_ID']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Incentive Type (Enum) -->
                <div>
                    <label>Incentive Type</label>
                    <select name="inc_type" required>
                        <option value="HOLI" <?php if ($editRow && $editRow['INC_TYPE'] == 'HOLI') echo 'selected'; ?>>Holiday (HOLI)</option>
                        <option value="PERF" <?php if ($editRow && $editRow['INC_TYPE'] == 'PERF') echo 'selected'; ?>>Performance (PERF)</option>
                        <option value="LOYAL" <?php if ($editRow && $editRow['INC_TYPE'] == 'LOYAL') echo 'selected'; ?>>Loyalty (LOYAL)</option>
                    </select>
                </div>

                <!-- Amount -->
                <div>
                    <label>Amount</label>
                    <input type="number" step="0.01" name="inc_amount" required 
                           value="<?php echo $editRow ? $editRow['INC_AMOUNT'] : ''; ?>">
                </div>

                <!-- Date -->
                <div>
                    <label>Date Given</label>
                    <input type="date" name="inc_date" required 
                           value="<?php echo $editRow ? $editRow['INC_DATE'] : date('Y-m-d'); ?>">
                </div>
            </div>

            <div style="margin-top: 20px; text-align: right;">
                <?php if ($editRow): ?>
                    <a href="incentives.php" style="margin-right: 10px; color: #666; text-decoration: none;">Cancel</a>
                <?php endif; ?>
                <button type="submit" class="btn-submit">
                    <?php echo $editRow ? 'Save Changes' : 'Add Incentive'; ?>
                </button>
            </div>
        </form>
    </div>

    <!-- TABLE SECTION -->
    <div class="card">
        <h3>Incentive History</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Employee Name</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($incentives)): ?>
                    <tr><td colspan="6" style="text-align: center;">No records found.</td></tr>
                <?php else: ?>
                    <?php foreach ($incentives as $row): ?>
                        <tr>
                            <td><?php echo $row['INC_ID']; ?></td>
                            <td><?php echo htmlspecialchars($row['EMP_NAME'] ?: 'Unknown (ID:'.$row['EMP_ID'].')'); ?></td>
                            <td>
                                <span style="font-weight: bold; color: #0056b3;">
                                    <?php echo htmlspecialchars($row['INC_TYPE']); ?>
                                </span>
                            </td>
                            <td>₱<?php echo number_format($row['INC_AMOUNT'], 2); ?></td>
                            <td><?php echo htmlspecialchars($row['INC_DATE']); ?></td>
                            <td>
                                <!-- Edit Button -->
                                <a href="incentives.php?edit=<?php echo $row['INC_ID']; ?>" class="btn-edit">Edit</a>
                                
                                <!-- Delete Button -->
                                <form method="POST" style="display:inline-block; margin-left: 5px;" 
                                      onsubmit="return confirm('Are you sure you want to delete this?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="inc_id" value="<?php echo $row['INC_ID']; ?>">
                                    <button type="submit" class="btn-delete">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>