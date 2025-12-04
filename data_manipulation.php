<?php 
// 1. DATABASE CONNECTION
$user = 'root'; 
$password = ''; 
$database = 'project'; 
$servername = 'localhost'; 
$port = '3306'; 
$charset = 'utf8mb4';
$dsn = "mysql:host=$servername;port=$port;dbname=$database;charset=$charset";
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false];

try { $pdo = new PDO($dsn, $user, $password, $options); } catch (\PDOException $e) { die('Connection Error: ' . $e->getMessage()); }

$action = $_GET['action'] ?? null;
$errorMessage = ""; 
$employeeData = null; 
$deptData = null;
$jobData = null;

// --- FETCH LOGIC: EMPLOYEE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_fetch'])) {
    $search_id = $_POST['fetch_id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM employee WHERE EMP_ID = :id");
        $stmt->execute(['id' => $search_id]);
        $employeeData = $stmt->fetch(); 
        if (!$employeeData) $errorMessage = "<strong>Error:</strong> Employee ID $search_id not found.";
    } catch (PDOException $e) {
        $errorMessage = "Database Error: " . $e->getMessage();
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_fetch_dept'])) {
    $search_dept_id = $_POST['fetch_dept_id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM department WHERE DEPT_ID = :id");
        $stmt->execute(['id' => $search_dept_id]);
        $deptData = $stmt->fetch(); 
        if (!$deptData) $errorMessage = "<strong>Error:</strong> Department ID $search_dept_id not found.";
    } catch (PDOException $e) {
        $errorMessage = "Database Error: " . $e->getMessage();
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_fetch_job'])) {
    $search_jp_id = $_POST['fetch_jp_id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM job_position WHERE JP_ID = :id");
        $stmt->execute(['id' => $search_jp_id]);
        $jobData = $stmt->fetch(); 
        if (!$jobData) $errorMessage = "<strong>Error:</strong> Job Position ID $search_jp_id not found.";
    } catch (PDOException $e) {
        $errorMessage = "Database Error: " . $e->getMessage();
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actionSQL'])) {
    
    $actionSQL = $_POST['actionSQL'];

    // ADD EMPLOYEE
    if ($actionSQL === 'addEmployee') {
        $emp_name = $_POST['emp_name']; $dept_id = $_POST['department']; $jp_id = $_POST['job_position']; 
        $age = $_POST['age']; $gender = $_POST['gender']; $salary = $_POST['base_salary'];  

        $sql = "INSERT INTO employee (EMP_NAME, DEPT_ID, JP_ID, EMP_AGE, EMP_GENDER, EMP_BASE_SALARY, status) 
                VALUES (:name, :dept, :job, :age, :gender, :salary, 'ACTIVE')";
        $stmt = $pdo->prepare($sql);
        
        try {
            $stmt->execute(['name' => $emp_name, 'dept' => $dept_id, 'job' => $jp_id, 'age' => $age, 'gender' => $gender, 'salary' => $salary]);
            header('Location: employee.php?msg=added'); exit;
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1452) $errorMessage = "<strong>Integrity Error:</strong> Department or Job ID does not exist.";
            elseif ($e->errorInfo[1] == 1062) $errorMessage = "<strong>Duplicate Error:</strong> Record already exists.";
            else $errorMessage = "<strong>Database Error:</strong> " . $e->getMessage();
        }
    }

    // FIRE EMPLOYEE
    elseif ($actionSQL === 'fireEmployee') {
        $emp_id = $_POST['emp_id'];
        try {
            $stmt = $pdo->prepare("UPDATE employee SET status = 'INACTIVE' WHERE EMP_ID = :id");
            $stmt->execute(['id' => $emp_id]);
            if ($stmt->rowCount() > 0) { header('Location: employee.php?msg=fired'); exit; } 
            else { $errorMessage = "<strong>Error:</strong> No employee found with ID: " . htmlspecialchars($emp_id); }
        } catch (PDOException $e) { $errorMessage = "Error updating record: " . $e->getMessage(); }
    }

    // UPDATE EMPLOYEE
    elseif ($actionSQL === 'updateEmployee') {
        $emp_id = $_POST['emp_id']; $emp_name = $_POST['emp_name']; $dept_id = $_POST['department']; 
        $jp_id = $_POST['job_position']; $age = $_POST['age']; $gender = $_POST['gender']; $salary = $_POST['base_salary']; 

        $sql = "UPDATE employee SET EMP_NAME = :name, DEPT_ID = :dept, JP_ID = :job, EMP_AGE = :age, EMP_GENDER = :gender, EMP_BASE_SALARY = :salary WHERE EMP_ID = :id";
        $stmt = $pdo->prepare($sql);

        try {
            $stmt->execute(['name' => $emp_name, 'dept' => $dept_id, 'job' => $jp_id, 'age' => $age, 'gender' => $gender, 'salary' => $salary, 'id' => $emp_id]);
            header('Location: employee.php?msg=updated'); exit;
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1452) $errorMessage = "<strong>Integrity Error:</strong> Invalid Department or Job ID.";
            else $errorMessage = "<strong>Database Error:</strong> " . $e->getMessage();
        }
    }

    // ADD DEPARTMENT
    elseif ($actionSQL === 'addDepartment') {
        $dept_name = $_POST['dept_name'];
        try {
            $stmt = $pdo->prepare("INSERT INTO department (DEPT_NAME) VALUES (:name)");
            $stmt->execute(['name' => $dept_name]);
            header('Location: employee.php?msg=dept_added'); exit;
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) $errorMessage = "<strong>Duplicate Error:</strong> Department '$dept_name' already exists.";
            else $errorMessage = "<strong>Database Error:</strong> " . $e->getMessage();
        }
    }

    // --- NEW: UPDATE DEPARTMENT LOGIC ---
    elseif ($actionSQL === 'updateDepartment') {
        $dept_id = $_POST['dept_id'];     
        $dept_name = $_POST['dept_name']; 

        try {
            $stmt = $pdo->prepare("UPDATE department SET DEPT_NAME = :name WHERE DEPT_ID = :id");
            $stmt->execute(['name' => $dept_name, 'id' => $dept_id]);
            

            header('Location: employee.php?msg=dept_updated'); 
            exit;

        } catch (PDOException $e) {

            if ($e->errorInfo[1] == 1062) {
                $errorMessage = "<strong>Duplicate Error:</strong> The department name '$dept_name' is already taken.";
            } else {
                $errorMessage = "<strong>Database Error:</strong> " . $e->getMessage();
            }
        }
    }elseif ($actionSQL === 'addJobPosition') {
        $jp_level = $_POST['jp_level'];
        $jp_name = $_POST['jp_name'];
        $jp_type = $_POST['jp_type'];

        try {
            $stmt = $pdo->prepare("INSERT INTO job_position (JP_LEVEL, JP_NAME, JP_TYPE) VALUES (:level, :name, :type)");
            $stmt->execute(['level' => $jp_level, 'name' => $jp_name, 'type' => $jp_type]);
            header('Location: employee.php?msg=job_added'); exit;
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                $errorMessage = "<strong>Duplicate Error:</strong> Job Position '$jp_name' already exists.";
            } else {
                $errorMessage = "<strong>Database Error:</strong> " . $e->getMessage();
            }
        }
    }elseif ($actionSQL === 'updateJobPosition') {
        $jp_id = $_POST['jp_id'];
        $jp_level = $_POST['jp_level'];
        $jp_name = $_POST['jp_name'];
        $jp_type = $_POST['jp_type'];

        try {
            $stmt = $pdo->prepare("UPDATE job_position SET JP_LEVEL = :level, JP_NAME = :name, JP_TYPE = :type WHERE JP_ID = :id");
            $stmt->execute(['level' => $jp_level, 'name' => $jp_name, 'type' => $jp_type, 'id' => $jp_id]);
            
            header('Location: employee.php?msg=job_updated'); 
            exit;

        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                $errorMessage = "<strong>Duplicate Error:</strong> Job Position '$jp_name' already exists.";
            } else {
                $errorMessage = "<strong>Database Error:</strong> " . $e->getMessage();
            }
        }
    }elseif ($actionSQL === 'deleteJobPosition') {
        $jp_id = $_POST['jp_id'];

        try {
            $stmt = $pdo->prepare("DELETE FROM job_position WHERE JP_ID = :id");
            $stmt->execute(['id' => $jp_id]);

            if ($stmt->rowCount() > 0) {
                header('Location: employee.php?msg=job_deleted'); 
                exit;
            } else {
                $errorMessage = "<strong>Error:</strong> Job Position ID $jp_id not found.";
            }

        } catch (PDOException $e) {
         
            if ($e->errorInfo[1] == 1451) {
                $errorMessage = "<strong>Cannot Delete:</strong> This Job Position is currently assigned to one or more employees. <br>To prevent system errors, you must reassign those employees to a different position before deleting this one.";
            } else {
                $errorMessage = "<strong>Database Error:</strong> " . $e->getMessage();
            }
        }
    }elseif ($actionSQL === 'deleteDepartment') {
        $dept_id = $_POST['dept_id'];

        try {
            $stmt = $pdo->prepare("DELETE FROM department WHERE DEPT_ID = :id");
            $stmt->execute(['id' => $dept_id]);

            if ($stmt->rowCount() > 0) {
                header('Location: employee.php?msg=dept_deleted'); 
                exit;
            } else {
                $errorMessage = "<strong>Error:</strong> Department ID $dept_id not found.";
            }

        } catch (PDOException $e) {
            // ERROR 1451: Foreign Key Constraint Fails
            if ($e->errorInfo[1] == 1451) {
                $errorMessage = "<strong>Cannot Delete:</strong> This Department currently has active employees.<br>You must move these employees to a different department before you can delete this one.";
            } else {
                $errorMessage = "<strong>Database Error:</strong> " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Data Manipulation</title>
        <link rel="stylesheet" href="data_manipulation.css">
        <style>
            .alert-box { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 15px; margin: 20px auto; max-width: 500px; border-radius: 5px; text-align: center; }
        </style>
    </head>
    <body>
        
        <?php if (!empty($errorMessage)): ?>
            <div class="alert-box"><?php echo $errorMessage; ?></div>
        <?php endif; ?>

        <?php if($action === 'addEmployee'): ?>
        <div class='form-container'>
            <h2>Add New Employee</h2>
            <form method="POST" action="data_manipulation.php?action=addEmployee">
                <input type="hidden" name="actionSQL" value="addEmployee">
                <label>Employee Name:</label> <input type="text" name="emp_name" required>
                <label>Department ID:</label> <input type="number" name="department" required>
                <label>Job Position ID:</label> <input type="number" name="job_position" required>
                <label>Age:</label> <input type="number" name="age" required>
                <label>Gender:</label> 
                <select name="gender" required><option value="Male">Male</option><option value="Female">Female</option></select>
                <label>Base Salary:</label> <input type="number" step="0.01" name="base_salary" required>
                <button type="submit">Add Employee</button>
            </form>
        </div>

        <?php elseif($action === 'fireEmployee'): ?>
        <div class='form-container'>
            <h2>Fire Employee</h2>
            <form method="POST" action="data_manipulation.php?action=fireEmployee">
                <input type="hidden" name="actionSQL" value="fireEmployee">
                <label>Employee ID to Fire:</label>
                <input type="number" name="emp_id" required>
                <button type="submit" style="background-color: red;">Fire Employee</button>
            </form>
        </div>

        <?php elseif ($action === 'updateEmployee'): ?>
        <div class='form-container'>
            <h2>Update Employee Details</h2>
            <form method="POST" action="data_manipulation.php?action=updateEmployee" style="border-bottom: 2px dashed #ccc; padding-bottom: 20px; margin-bottom: 20px;">
                <label>Enter Employee ID to Edit:</label>
                <div style="display:flex; gap:10px;">
                    <input type="number" name="fetch_id" required placeholder="e.g. 101" value="<?php echo isset($_POST['fetch_id']) ? $_POST['fetch_id'] : ''; ?>">
                    <button type="submit" name="btn_fetch" value="1">Find</button>
                </div>
            </form>
            <?php if ($employeeData): ?>
                <form action='data_manipulation.php?action=updateEmployee' method='POST'>
                    <input type="hidden" name="actionSQL" value="updateEmployee">
                    <input type="hidden" name="emp_id" value="<?php echo $employeeData['EMP_ID']; ?>">
                    <label>Name:</label> <input type="text" name="emp_name" value="<?php echo htmlspecialchars($employeeData['EMP_NAME']); ?>" required>
                    <label>Department ID:</label> <input type="number" name="department" value="<?php echo htmlspecialchars($employeeData['DEPT_ID']); ?>" required>
                    <label>Job Position ID:</label> <input type="number" name="job_position" value="<?php echo htmlspecialchars($employeeData['JP_ID']); ?>" required>
                    <label>Age:</label> <input type="number" name="age" value="<?php echo htmlspecialchars($employeeData['EMP_AGE']); ?>" required>
                    <label>Gender:</label> <select name="gender" required>
                        <option value="Male" <?php if($employeeData['EMP_GENDER'] == 'Male') echo 'selected'; ?>>Male</option>
                        <option value="Female" <?php if($employeeData['EMP_GENDER'] == 'Female') echo 'selected'; ?>>Female</option>
                    </select>
                    <label>Base Salary:</label> <input type="number" step="0.01" name="base_salary" value="<?php echo htmlspecialchars($employeeData['EMP_BASE_SALARY']); ?>" required>
                    <button type="submit" style="background-color: #ff9800; margin-top:15px;">Save Changes</button>
                </form>
            <?php endif; ?>
        </div>

        <?php elseif($action==='addDepartment'):?>
        <div class='form-container'>
            <h2>Add Department</h2>
            <form method="POST" action="data_manipulation.php?action=addDepartment">
                <input type="hidden" name="actionSQL" value="addDepartment">
                <label>Department Name:</label> <input type="text" name="dept_name" required>
                <button type="submit">Add Department</button>
            </form>
        </div>

        <?php elseif($action ==='updateDepartment'):?>
        <div class='form-container'>
            <h2>Update Department</h2>
            
            <form method="POST" action="data_manipulation.php?action=updateDepartment" style="border-bottom: 2px dashed #ccc; padding-bottom: 20px; margin-bottom: 20px;">
                <label>Enter Department ID to Edit:</label>
                <div style="display:flex; gap:10px;">
                    <input type="number" name="fetch_dept_id" required placeholder="e.g. 1" value="<?php echo isset($_POST['fetch_dept_id']) ? $_POST['fetch_dept_id'] : ''; ?>">
                    <button type="submit" name="btn_fetch_dept" value="1">Find</button>
                </div>
            </form>

            <?php if ($deptData): ?>
                <form action="data_manipulation.php?action=updateDepartment" method="POST">
                    <input type="hidden" name="actionSQL" value="updateDepartment">
                    
                    <input type="hidden" name="dept_id" value="<?php echo $deptData['DEPT_ID']; ?>">

                    <label>Department Name:</label>
                    <input type="text" name="dept_name" value="<?php echo htmlspecialchars($deptData['DEPT_NAME']); ?>" required>

                    <button type="submit" style="background-color: #ff9800; margin-top:15px;">Update Department</button>
                </form>
            <?php endif; ?>
            
        </div>
        <?php elseif ($action === 'addJobPosition'): ?>
        <div class='form-container'>
            <h2>Add Job Position</h2>
            <form method="POST" action="data_manipulation.php?action=addJobPosition">
                <input type="hidden" name="actionSQL" value="addJobPosition">
                <label for="jp_level">Job Level:</label> 
                <select name="jp_level" id="jp_level" required>
                    <option value="" disabled selected>Select Level</option>
                    <option value="JR">Junior (JR)</option>
                    <option value="SR">Senior (SR)</option>
                    <option value="MNGR">Manager (MNGR)</option>
                </select>

                <label>Job Name:</label> 
                <input type="text" name="jp_name" required>

                <label for="jp_type">Job Type:</label> 
                <select name="jp_type" id="jp_type" required>
                    <option value="" disabled selected>Select Type</option>
                    <option value="CONTRACT">Contract</option>
                    <option value="FULL">Full Time</option>
                    <option value="PART">Part Time</option>
                </select>
                <button type="submit">Add Job Position</button>
            </form>
        <?php elseif ($action === 'updateJobPosition'): ?>
        <div class='form-container'>
            <h2>Update Job Position</h2>
            
            <form method="POST" action="data_manipulation.php?action=updateJobPosition" style="border-bottom: 2px dashed #ccc; padding-bottom: 20px; margin-bottom: 20px;">
                <label>Enter Job ID to Edit:</label>
                <div style="display:flex; gap:10px;">
                    <input type="number" name="fetch_jp_id" required placeholder="e.g. 1" value="<?php echo isset($_POST['fetch_jp_id']) ? $_POST['fetch_jp_id'] : ''; ?>">
                    <button type="submit" name="btn_fetch_job" value="1">Find</button>
                </div>
            </form>

            <?php if ($jobData): ?>
                <form action="data_manipulation.php?action=updateJobPosition" method="POST">
                    <input type="hidden" name="actionSQL" value="updateJobPosition">
                    <input type="hidden" name="jp_id" value="<?php echo $jobData['JP_ID']; ?>">

                    <label for="jp_level">Job Level:</label> 
                    <select name="jp_level" id="jp_level" required>
                        <option value="JR" <?php if($jobData['JP_LEVEL'] == 'JR') echo 'selected'; ?>>Junior (JR)</option>
                        <option value="SR" <?php if($jobData['JP_LEVEL'] == 'SR') echo 'selected'; ?>>Senior (SR)</option>
                        <option value="MNGR" <?php if($jobData['JP_LEVEL'] == 'MNGR') echo 'selected'; ?>>Manager (MNGR)</option>
                    </select>

                    <label>Job Name:</label> 
                    <input type="text" name="jp_name" value="<?php echo htmlspecialchars($jobData['JP_NAME']); ?>" required>

                    <label for="jp_type">Job Type:</label> 
                    <select name="jp_type" id="jp_type" required>
                        <option value="CONTRACT" <?php if($jobData['JP_TYPE'] == 'CONTRACT') echo 'selected'; ?>>Contract</option>
                        <option value="FULL" <?php if($jobData['JP_TYPE'] == 'FULL') echo 'selected'; ?>>Full Time</option>
                        <option value="PART" <?php if($jobData['JP_TYPE'] == 'PART') echo 'selected'; ?>>Part Time</option>
                    </select>

                    <button type="submit" style="background-color: #ff9800; margin-top:15px;">Update Job Position</button>
                </form>
            <?php endif; ?>
        </div>
        <?php elseif ($action === 'deleteJobPosition'): ?>
        <div class='form-container'>
            <h2 style="color: #d32f2f;">Delete Job Position</h2>
            
            <div style="background-color: #fff3cd; padding: 10px; border: 1px solid #ffeeba; margin-bottom: 15px; color: #856404; font-size: 0.9em;">
                <strong>Warning:</strong> You cannot delete a position if employees are currently assigned to it.
            </div>

            <form method="POST" action="data_manipulation.php?action=deleteJobPosition">
                <input type="hidden" name="actionSQL" value="deleteJobPosition">
                
                <label>Enter Job ID to Delete:</label>
                <div style="display:flex; gap:10px;">
                    <input type="number" name="jp_id" required placeholder="e.g. 5">
                    <button type="submit" style="background-color: #d32f2f;">Delete Permanently</button>
                </div>
            </form>
        </div>
       <?php elseif ($action === 'deleteDepartment'): ?>
        <div class='form-container'>
            <h2 style="color: #d32f2f;">Delete Department</h2>
            
            <div style="background-color: #fff3cd; padding: 10px; border: 1px solid #ffeeba; margin-bottom: 15px; color: #856404; font-size: 0.9em;">
                <strong>Warning:</strong> Deleting a department is irreversible. Ensure no employees are assigned to it first.
            </div>

            <form method="POST" action="data_manipulation.php?action=deleteDepartment">
                <input type="hidden" name="actionSQL" value="deleteDepartment">
                
                <label>Enter Department ID to Delete:</label>
                <div style="display:flex; gap:10px;">
                    <input type="number" name="dept_id" required placeholder="e.g. 3">
                    <button type="submit" style="background-color: #d32f2f;">Delete Permanently</button>
                </div>
            </form>
        </div>
        <?php endif; ?>

    </body>
</html>