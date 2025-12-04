<?php
session_start();

// Basic auth check
if (!isset($_SESSION['emp_id'])) {
    
    header('Location: login.php');
    exit;
}

$emp_id = (int) $_SESSION['emp_id'];

// DB connection (reuse your connection settings) 
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
            // redirect to avoid form resubmit
            header("Location: leave.php");
            exit;
        }
    }

    elseif ($action === 'update') {
        $lve_id = (int) post('lve_id');
        $lve_reason = post('reason') ?: '';
        $lve_type = post('lve_type') ?: 'UNPAID';
        $lve_duration = post('lve_duration') ?: '00:00:00';

        // Only allow updating if this leave belongs to user AND status is PENDING
        $stmt = $pdo->prepare("SELECT LVE_STATUS FROM leave_request WHERE LVE_ID = :id AND EMP_ID = :emp_id LIMIT 1");
        $stmt->execute(['id' => $lve_id, 'emp_id' => $emp_id]);
        $row = $stmt->fetch();

        if (!$row) {
            $errors[] = "Leave not found or you don't have permission.";
        } elseif ($row['LVE_STATUS'] !== 'PENDING') {
            $errors[] = "Only PENDING leaves can be updated.";
        } else {
            $stmt = $pdo->prepare("UPDATE leave_request
                                   SET LVE_REASON = :reason, LVE_TYPE = :type, LVE_DURATION = :duration
                                   WHERE LVE_ID = :id AND EMP_ID = :emp_id");
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

        // Only allow delete if PENDING
        $stmt = $pdo->prepare("SELECT LVE_STATUS FROM leave_request WHERE LVE_ID = :id AND EMP_ID = :emp_id LIMIT 1");
        $stmt->execute(['id' => $lve_id, 'emp_id' => $emp_id]);
        $row = $stmt->fetch();

        if (!$row) {
            $errors[] = "Leave not found or you don't have permission to delete.";
        } elseif ($row['LVE_STATUS'] !== 'PENDING') {
            $errors[] = "Only PENDING leaves can be deleted.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM leave_request WHERE LVE_ID = :id AND EMP_ID = :emp_id");
            $stmt->execute(['id' => $lve_id, 'emp_id' => $emp_id]);
            $messages[] = "Leave deleted.";
            header("Location: leave.php");
            exit;
        }
    }
}

//For edit form prefill (GET ?edit=ID)
$editing = false;
$edit_row = null;
if (isset($_GET['edit'])) {
    $edit_id = (int) $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM leave_request WHERE LVE_ID = :id AND EMP_ID = :emp_id LIMIT 1");
    $stmt->execute(['id' => $edit_id, 'emp_id' => $emp_id]);
    $edit_row = $stmt->fetch();
    if ($edit_row && $edit_row['LVE_STATUS'] === 'PENDING') {
        $editing = true;
    } else {
        $editing = false;
        $edit_row = null;
    }
}

//Fetch user's leaves
$stmt = $pdo->prepare("SELECT lr.*, e.EMP_NAME
                       FROM leave_request lr
                       LEFT JOIN employee e ON lr.EMP_ID = e.EMP_ID
                       WHERE lr.EMP_ID = :emp_id
                       ORDER BY lr.LVE_DATE_FILLED DESC, lr.LVE_ID DESC");
$stmt->execute(['emp_id' => $emp_id]);
$leaves = $stmt->fetchAll();

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>My Leaves</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
/* small inline styles for quick drop-in */
body{font-family: Arial, Helvetica, sans-serif; margin:20px; background:#f6f6f6}
.container{max-width:900px;margin:0 auto;background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.06)}
h1{margin-bottom:10px}
form{margin-bottom:20px}
textarea{width:100%;min-height:100px}
.table{width:100%;border-collapse:collapse;margin-top:10px}
.table th,.table td{padding:8px;border:1px solid #ddd;text-align:left}
.actions small{color:#666}
.notice{padding:8px;background:#e6ffed;border:1px solid #bdeec7;margin-bottom:12px}
.error{padding:8px;background:#ffe6e6;border:1px solid #ffbdbd;margin-bottom:12px}
.btn{padding:6px 10px;border:0;border-radius:6px;cursor:pointer}
.btn-primary{background:#2563eb;color:#fff}
.btn-danger{background:#ef4444;color:#fff}
.link{background:none;border:0;color:#2563eb;cursor:pointer;text-decoration:underline}
</style>
</head>
<body>
<div class="container">
    <h1>File a Leave</h1>

    <?php foreach($errors as $e): ?>
        <div class="error"><?php echo htmlspecialchars($e); ?></div>
    <?php endforeach; ?>

    <?php foreach($messages as $m): ?>
        <div class="notice"><?php echo htmlspecialchars($m); ?></div>
    <?php endforeach; ?>

    <?php if ($editing && $edit_row): ?>
        <!-- Edit form -->
        <h2>Edit Leave #<?php echo (int)$edit_row['LVE_ID']; ?> (status: <?php echo htmlspecialchars($edit_row['LVE_STATUS']); ?>)</h2>
        <form method="post" action="leave.php">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="lve_id" value="<?php echo (int)$edit_row['LVE_ID']; ?>">
            <label>Type:
                <select name="lve_type">
                    <option value="PAID" <?php echo $edit_row['LVE_TYPE']=='PAID'?'selected':''; ?>>PAID</option>
                    <option value="UNPAID" <?php echo $edit_row['LVE_TYPE']=='UNPAID'?'selected':''; ?>>UNPAID</option>
                </select>
            </label>
            <br><br>
            <label>Duration (HH:MM:SS): <input name="lve_duration" value="<?php echo htmlspecialchars($edit_row['LVE_DURATION']); ?>"></label>
            <br><br>
            <label>Reason:</label><br>
            <textarea name="reason"><?php echo htmlspecialchars($edit_row['LVE_REASON']); ?></textarea>
            <br><br>
            <button class="btn btn-primary" type="submit">Save changes</button>
            <a href="leave.php" class="link">Cancel</a>
        </form>

    <?php else: ?>
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
            <textarea name="reason" required></textarea>
            <br><br>
            <label><input type="checkbox" required> I confirm this information is correct.</label>
            <br><br>
            <button class="btn btn-primary" type="submit">Submit Leave</button>
        </form>
    <?php endif; ?>

    <h2>My Submitted Leaves</h2>
    <?php if (empty($leaves)): ?>
        <p>No leaves found.</p>
    <?php else: ?>
        <table class="table" role="table">
            <thead>
                <tr>
                    <th>LVE ID</th>
                    <th>Date Filed</th>
                    <th>Type</th>
                    <th>Duration</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($leaves as $r): ?>
                <tr>
                    <td><?php echo (int)$r['LVE_ID']; ?></td>
                    <td><?php echo htmlspecialchars($r['LVE_DATE_FILLED']); ?></td>
                    <td><?php echo htmlspecialchars($r['LVE_TYPE']); ?></td>
                    <td><?php echo htmlspecialchars($r['LVE_DURATION']); ?></td>
                    <td><?php echo nl2br(htmlspecialchars($r['LVE_REASON'])); ?></td>
                    <td><?php echo htmlspecialchars($r['LVE_STATUS']); ?></td>
                    <td>
                        <?php if ($r['LVE_STATUS'] === 'PENDING'): ?>
                            <a href="leave.php?edit=<?php echo (int)$r['LVE_ID']; ?>">Edit</a>
                            &nbsp;|&nbsp;
                            <form method="post" action="leave.php" style="display:inline" onsubmit="return confirm('Delete this pending leave?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="lve_id" value="<?php echo (int)$r['LVE_ID']; ?>">
                                <button type="submit" class="btn btn-danger">Delete</button>
                            </form>
                        <?php else: ?>
                            <small>Locked</small>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <p><a href="user.php">Return to menu</a></p>
</div>
</body>
</html>
