<?php
session_start();
require_once 'db_config.php';

function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: website.php?page=login");
        exit();
    }
}

// Handle logout first
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: website.php?page=login");
    exit();
}

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to the database: " . $e->getMessage());
}

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = $username;
        header("Location: website.php?page=home");
        exit();
    } else {
        $login_error = "Invalid username or password";
    }
}

if (isset($_POST['register_student'])) {
    check_login();
    
    $id_no = $_POST['id_no'];
    $full_name = $_POST['full_name'];
    $gender = $_POST['gender'];
    $dob = $_POST['dob'];
    $age = $_POST['age'];
    $department = $_POST['department'];
    $address = $_POST['address'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO student (id_no, full_name, gender, dob, age, department, address, email, phone) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id_no, $full_name, $gender, $dob, $age, $department, $address, $email, $phone]);
        $registration_success = "Student registered successfully!";
    } catch (PDOException $e) {
        $registration_error = "Error registering student: " . $e->getMessage();
    }
}

if (isset($_POST['bulk_upload'])) {
    check_login();
    
    if (isset($_FILES['bulk_file']) && $_FILES['bulk_file']['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES['bulk_file']['tmp_name'];
        $handle = fopen($file, "r");
        
        fgetcsv($handle);
        
        $pdo->beginTransaction();
        
        try {
            $stmt = $pdo->prepare("INSERT INTO student (id_no, full_name, gender, dob, age, department, address, email, phone) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $stmt->execute($data);
            }
            
            $pdo->commit();
            $bulk_success = "Bulk upload completed successfully!";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $bulk_error = "Error during bulk upload: " . $e->getMessage();
        }
        
        fclose($handle);
    } else {
        $bulk_error = "Please select a valid CSV file for upload.";
    }
}

$search_results = [];
if (isset($_GET['search'])) {
    check_login();
    
    $id_no = $_GET['id_no'];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM student WHERE id_no = ?");
        $stmt->execute([$id_no]);
        $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $search_error = "Error searching for student: " . $e->getMessage();
    }
}

if (isset($_POST['update_student'])) {
    check_login();
    
    $id_no = $_POST['id_no'];
    $full_name = $_POST['full_name'];
    $gender = $_POST['gender'];
    $dob = $_POST['dob'];
    $age = $_POST['age'];
    $department = $_POST['department'];
    $address = $_POST['address'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    
    try {
        $stmt = $pdo->prepare("UPDATE student SET full_name = ?, gender = ?, dob = ?, age = ?, 
                              department = ?, address = ?, email = ?, phone = ? WHERE id_no = ?");
        $stmt->execute([$full_name, $gender, $dob, $age, $department, $address, $email, $phone, $id_no]);
        $update_success = "Student updated successfully!";
    } catch (PDOException $e) {
        $update_error = "Error updating student: " . $e->getMessage();
    }
}

if (isset($_GET['delete'])) {
    check_login();
    
    $id_no = $_GET['delete'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM student WHERE id_no = ?");
        $stmt->execute([$id_no]);
        $delete_success = "Student deleted successfully!";
    } catch (PDOException $e) {
        $delete_error = "Error deleting student: " . $e->getMessage();
    }
}

if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->query("SELECT * FROM student ORDER BY full_name");
        $all_students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $students_error = "Error fetching students: " . $e->getMessage();
    }
}

if (isset($_GET['page'])) {
    $page = $_GET['page'];
} else {
    $page = isset($_SESSION['user_id']) ? 'home' : 'login';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            width: 80%;
            margin: auto;
            overflow: hidden;
        }
        header {
            background: #35424a;
            color: white;
            padding: 20px 0;
            margin-bottom: 20px;
        }
        header h1 {
            float: left;
        }
        nav {
            float: right;
            margin-top: 10px;
        }
        nav a {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
        }
        nav a:hover {
            background: #e8491d;
        }
        .card {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #35424a;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="password"],
        input[type="date"],
        input[type="number"],
        input[type="email"],
        select,
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button, .btn {
            display: inline-block;
            background: #35424a;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }
        button:hover, .btn:hover {
            background: #e8491d;
        }
        .error {
            color: red;
        }
        .success {
            color: green;
        }
        .logout {
            float: right;
        }
    </style>
</head>
<body>
    <?php if ($page == 'login'): ?>
        <div class="container">
            <div class="card">
                <h2>Login</h2>
                <?php if (isset($login_error)): ?>
                    <p class="error"><?php echo $login_error; ?></p>
                <?php endif; ?>
                <form method="post">
                    <input type="hidden" name="page" value="login">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="submit" name="login">Login</button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <header>
            <div class="container">
                <h1>Student Management System</h1>
                <nav>
                    <a href="?page=home">Home</a>
                    <a href="?page=register">Register Student</a>
                    <a href="?page=bulk">Bulk Upload</a>
                    <a href="?page=search">Search Student</a>
                    <a href="?logout" class="logout">Logout</a>
                </nav>
            </div>
        </header>
        
        <div class="container">
            <?php if ($page == 'home'): ?>
                <div class="card">
                    <h2>All Students</h2>
                    <?php if (isset($students_error)): ?>
                        <p class="error"><?php echo $students_error; ?></p>
                    <?php elseif (empty($all_students)): ?>
                        <p>No students found.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID No</th>
                                    <th>Full Name</th>
                                    <th>Gender</th>
                                    <th>Age</th>
                                    <th>Department</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_students as $student): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['id_no']); ?></td>
                                        <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($student['gender']); ?></td>
                                        <td><?php echo htmlspecialchars($student['age']); ?></td>
                                        <td><?php echo htmlspecialchars($student['department']); ?></td>
                                        <td>
                                            <a href="?page=search&id_no=<?php echo $student['id_no']; ?>" class="btn">View</a>
                                            <a href="?delete=<?php echo $student['id_no']; ?>" class="btn" onclick="return confirm('Are you sure you want to delete this student?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                
            <?php elseif ($page == 'register'): ?>
                <div class="card">
                    <h2>Register New Student</h2>
                    <?php if (isset($registration_success)): ?>
                        <p class="success"><?php echo $registration_success; ?></p>
                    <?php elseif (isset($registration_error)): ?>
                        <p class="error"><?php echo $registration_error; ?></p>
                    <?php endif; ?>
                    <form method="post">
                        <div class="form-group">
                            <label for="id_no">ID Number</label>
                            <input type="text" id="id_no" name="id_no" required>
                        </div>
                        <div class="form-group">
                            <label for="full_name">Full Name</label>
                            <input type="text" id="full_name" name="full_name" required>
                        </div>
                        <div class="form-group">
                            <label for="gender">Gender</label>
                            <select id="gender" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="dob">Date of Birth</label>
                            <input type="date" id="dob" name="dob" required>
                        </div>
                        <div class="form-group">
                            <label for="age">Age</label>
                            <input type="number" id="age" name="age" required>
                        </div>
                        <div class="form-group">
                            <label for="department">Department</label>
                            <input type="text" id="department" name="department" required>
                        </div>
                        <div class="form-group">
                            <label for="address">Address</label>
                            <textarea id="address" name="address" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email">
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="text" id="phone" name="phone">
                        </div>
                        <button type="submit" name="register_student">Register Student</button>
                    </form>
                </div>
                
            <?php elseif ($page == 'bulk'): ?>
                <div class="card">
                    <h2>Bulk Upload Students</h2>
                    <?php if (isset($bulk_success)): ?>
                        <p class="success"><?php echo $bulk_success; ?></p>
                    <?php elseif (isset($bulk_error)): ?>
                        <p class="error"><?php echo $bulk_error; ?></p>
                    <?php endif; ?>
                    <p>Upload a CSV file with student data. The CSV should have the following columns in order:</p>
                    <p>ID No, Full Name, Gender, Date of Birth (YYYY-MM-DD), Age, Department, Address, Email, Phone</p>
                    <form method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="bulk_file">CSV File</label>
                            <input type="file" id="bulk_file" name="bulk_file" accept=".csv" required>
                        </div>
                        <button type="submit" name="bulk_upload">Upload</button>
                    </form>
                </div>
                
            <?php elseif ($page == 'search'): ?>
                <div class="card">
                    <h2>Search Student</h2>
                    <form method="get">
                        <input type="hidden" name="page" value="search">
                        <div class="form-group">
                            <label for="id_no">Search by ID Number</label>
                            <input type="text" id="id_no" name="id_no" value="<?php echo isset($_GET['id_no']) ? htmlspecialchars($_GET['id_no']) : ''; ?>" required>
                        </div>
                        <button type="submit" name="search">Search</button>
                    </form>
                    
                    <?php if (isset($search_error)): ?>
                        <p class="error"><?php echo $search_error; ?></p>
                    <?php elseif (!empty($search_results)): ?>
                        <?php if (isset($update_success)): ?>
                            <p class="success"><?php echo $update_success; ?></p>
                        <?php elseif (isset($update_error)): ?>
                            <p class="error"><?php echo $update_error; ?></p>
                        <?php endif; ?>
                        
                        <h3>Student Details</h3>
                        <form method="post">
                            <input type="hidden" name="id_no" value="<?php echo $search_results[0]['id_no']; ?>">
                            <div class="form-group">
                                <label for="full_name">Full Name</label>
                                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($search_results[0]['full_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <select id="gender" name="gender" required>
                                    <option value="Male" <?php echo $search_results[0]['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo $search_results[0]['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="Other" <?php echo $search_results[0]['gender'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="dob">Date of Birth</label>
                                <input type="date" id="dob" name="dob" value="<?php echo htmlspecialchars($search_results[0]['dob']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="age">Age</label>
                                <input type="number" id="age" name="age" value="<?php echo htmlspecialchars($search_results[0]['age']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="department">Department</label>
                                <input type="text" id="department" name="department" value="<?php echo htmlspecialchars($search_results[0]['department']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="address">Address</label>
                                <textarea id="address" name="address" required><?php echo htmlspecialchars($search_results[0]['address']); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($search_results[0]['email']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone</label>
                                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($search_results[0]['phone']); ?>">
                            </div>
                            <button type="submit" name="update_student">Update</button>
                            <a href="?delete=<?php echo $search_results[0]['id_no']; ?>" class="btn" onclick="return confirm('Are you sure you want to delete this student?')">Delete</a>
                        </form>
                    <?php elseif (isset($_GET['id_no'])): ?>
                        <p>No student found with ID <?php echo htmlspecialchars($_GET['id_no']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</body>
</html>