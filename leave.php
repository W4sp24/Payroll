<?php
session_start();

// 1. Basic Login Check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$is_employee = !empty($_SESSION['emp_id']);
$emp_id = $is_employee ? (int)$_SESSION['emp_id'] : 0;


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


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_employee) {
    
    $action = post('action') ?? '';

    // 1. CREATE
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
                        VALUES (:emp_id, :lve_type, :date_filled, :duration, :reason, 'Pending')";
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
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }


    elseif ($action === 'update') {
        $lve_id = (int) post('lve_id');
        $lve_reason = post('reason') ?: '';
        $lve_type = post('lve_type') ?: 'UNPAID';
        $lve_duration = post('lve_duration') ?: '00:00:00';


        $stmt = $pdo->prepare("SELECT LVE_STATUS FROM leave_request WHERE LVE_ID = :id AND EMP_ID = :emp_id LIMIT 1");
        $stmt->execute(['id' => $lve_id, 'emp_id' => $emp_id]);
        $row = $stmt->fetch();

        if (!$row) {
            $errors[] = "Leave not found or access denied.";
        } elseif ($row['LVE_STATUS'] !== 'Pending') {
            $errors[] = "You can only edit leaves that are still PENDING.";
        } else {
  
            $updateSql = "UPDATE leave_request 
                          SET LVE_REASON = :reason, LVE_TYPE = :type, LVE_DURATION = :duration 
                          WHERE LVE_ID = :id AND EMP_ID = :emp_id";
            $stmt = $pdo->prepare($updateSql);
            $stmt->execute([
                'reason' => $lve_reason,
                'type' => $lve_type,
                'duration' => $lve_duration,
                'id' => $lve_id,
                'emp_id' => $emp_id
            ]);
            $messages[] = "Leave updated successfully.";
            header("Location: leave.php");
            exit;
        }
    }

 
    elseif ($action === 'delete') {
        $lve_id = (int) post('lve_id');

  
        $stmt = $pdo->prepare("SELECT LVE_STATUS FROM leave_request WHERE LVE_ID = :id AND EMP_ID = :emp_id LIMIT 1");
        $stmt->execute(['id' => $lve_id, 'emp_id' => $emp_id]);
        $row = $stmt->fetch();

        if (!$row) {
            $errors[] = "Leave not found or access denied.";
        } elseif ($row['LVE_STATUS'] !== 'Pending') {
            $errors[] = "You cannot delete leaves that have already been processed.";
        } else {

            $stmt = $pdo->prepare("DELETE FROM leave_request WHERE LVE_ID = :id AND EMP_ID = :emp_id");
            $stmt->execute(['id' => $lve_id, 'emp_id' => $emp_id]);
            $messages[] = "Leave deleted successfully.";
            header("Location: leave.php");
            exit;
        }
    }
}


$editing = false;
$edit_row = null;

if (isset($_GET['edit']) && $is_employee) {
    $edit_id = (int) $_GET['edit'];

    $stmt = $pdo->prepare("SELECT * FROM leave_request WHERE LVE_ID = :id AND EMP_ID = :emp_id LIMIT 1");
    $stmt->execute(['id' => $edit_id, 'emp_id' => $emp_id]);
    $edit_row = $stmt->fetch();

    if ($edit_row && $edit_row['LVE_STATUS'] === 'Pending') {
        $editing = true;
    } else {

        $errors[] = "Cannot edit this leave (it might not exist, or is already Approved/Rejected).";
    }
}


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
    

    .error{padding:15px;background:#ffe6e6;border:1px solid #ffbdbd;margin-bottom:12px; border-left: 5px solid #d00; color: #900;}
    .notice{padding:15px;background:#e6ffed;border:1px solid #bdeec7;margin-bottom:12px; border-left: 5px solid #0d0; color: #060;}
    

    form { background: #fafafa; padding: 15px; border: 1px solid #eee; margin-bottom: 30px; }
    label { font-weight: bold; display: block; margin-bottom: 5px; }
    textarea, input, select { width: 100%; padding: 8px; box-sizing: border-box; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px;}
    
   
    .btn{padding:8px 15px;border:0;border-radius:4px;cursor:pointer; font-size: 14px; text-decoration: none; display: inline-block;}
    .btn-primary{background:#2563eb;color:#fff}
    .btn-primary:hover{background:#1d4ed8}
    
    .btn-danger{background:#dc2626;color:#fff; padding: 4px 8px; font-size: 12px;}
    .btn-danger:hover{background:#b91c1c}
    
    .btn-edit{background:#f59e0b; color: white; padding: 4px 8px; font-size: 12px; margin-right: 5px;}
    .btn-edit:hover{background:#d97706}
    
    .btn-cancel{background:#9ca3af; color: white; margin-left: 10px;}

    /* Table */
    table {width: 100%; border-collapse: collapse; margin-top: 10px;}
    th {background: #f3f4f6; text-align: left; padding: 10px; border-bottom: 2px solid #e5e7eb;}
    td {border-bottom: 1px solid #e5e7eb; padding: 10px; vertical-align: top;}
    
    .status-PENDING { color: #d97706; font-weight: bold; }
    .status-APPROVED { color: #059669; font-weight: bold; }
    .status-REJECTED { color: #dc2626; font-weight: bold; }
</style>
</head>
<body>
<div class="container">
    <h1>File a Leave</h1>

    <?php if (!$is_employee): ?>
        <div class="error">
            <strong>Account not linked!</strong><br>
            Please contact admin to link your User Account to an Employee Profile.
        </div>
    <?php else: ?>
        
        <!-- Display Success/Error Messages -->
        <?php foreach($errors as $e): ?>
            <div class="error"><?php echo htmlspecialchars($e); ?></div>
        <?php endforeach; ?>
        <?php foreach($messages as $m): ?>
            <div class="notice"><?php echo htmlspecialchars($m); ?></div>
        <?php endforeach; ?>



        <?php if ($editing && $edit_row): ?>
            <!-- EDIT MODE -->
            <form method="post" action="leave.php">
                <h3 style="margin-top:0">Edit Leave Request #<?php echo $edit_row['LVE_ID']; ?></h3>
                
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="lve_id" value="<?php echo (int)$edit_row['LVE_ID']; ?>">
                
                <label>Leave Type:</label>
                <select name="lve_type">
                    <option value="PAID" <?php echo ($edit_row['LVE_TYPE'] == 'PAID' ? 'selected' : ''); ?>>PAID</option>
                    <option value="UNPAID" <?php echo ($edit_row['LVE_TYPE'] == 'UNPAID' ? 'selected' : ''); ?>>UNPAID</option>
                </select>

                <label>Duration (HH:MM:SS):</label>
                <input name="lve_duration" value="<?php echo htmlspecialchars($edit_row['LVE_DURATION']); ?>" required>

                <label>Reason:</label>
                <textarea name="reason" rows="4" required><?php echo htmlspecialchars($edit_row['LVE_REASON']); ?></textarea>

                <button class="btn btn-primary" type="submit">Save Changes</button>
                <a href="leave.php" class="btn btn-cancel">Cancel</a>
            </form>

        <?php else: ?>
   
            <form method="post" action="leave.php">
                <h3 style="margin-top:0">New Leave Request</h3>
                <input type="hidden" name="action" value="create">
                
                <label>Leave Type:</label>
                <select name="lve_type">
                    <option value="PAID">PAID</option>
                    <option value="UNPAID" selected>UNPAID</option>
                </select>

                <label>Start Date:</label>
                <input type="date" name="lve_start_date" value="<?php echo date('Y-m-d'); ?>" required>

                <label>Duration (Days):</label>
                <input  type = number name="lve_duration">

                <label>Reason:</label>
                <textarea name="reason" rows="4" required></textarea>

                <button class="btn btn-primary" type="submit">Submit Leave</button>
            </form>
        <?php endif; ?>


        <h2>My Submitted Leaves</h2>
        <?php if (empty($leaves)): ?>
            <p>No leaves found.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th width="5%">ID</th>
                        <th width="15%">Date Filed</th>
                        <th width="10%">Type</th>
                        <th width="40%">Reason</th>
                        <th width="10%">Status</th>
                        <th width="20%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($leaves as $r): ?>
                    <tr>
                        <td><?php echo (int)$r['LVE_ID']; ?></td>
                        <td><?php echo htmlspecialchars($r['LVE_DATE_FILLED']); ?></td>
                        <td><?php echo htmlspecialchars($r['LVE_TYPE']); ?></td>
                        <td><?php echo nl2br(htmlspecialchars($r['LVE_REASON'])); ?></td>
                        <td>
                            <span class="status-<?php echo $r['LVE_STATUS']; ?>">
                                <?php echo htmlspecialchars($r['LVE_STATUS']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($r['LVE_STATUS'] === 'Pending'): ?>
                                <a href="leave.php?edit=<?php echo $r['LVE_ID']; ?>" class="btn btn-edit">Edit</a>

                            
                                <form method="post" action="leave.php" style="display:inline" onsubmit="return confirm('Are you sure you want to DELETE this leave?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="lve_id" value="<?php echo $r['LVE_ID']; ?>">
                                    <button type="submit" class="btn btn-danger">Delete</button>
                                </form>
                            <?php else: ?>
                                <small style="color:#999;">Locked</small>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>
    
    <p style="margin-top:20px;"><a href="user.php">&larr; Return to menu</a></p>
</div>
</body>
</html>