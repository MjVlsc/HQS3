<?php
require("lib/conn.php");

$departments = [];
try {
    $stmt = $conn->prepare("SELECT dept_id, name FROM departments ORDER BY name ASC");
    $stmt->execute();
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching departments: " . $e->getMessage();
}

$roles = [];
try {
    $stmt = $conn->prepare("SHOW COLUMNS FROM users LIKE 'role'");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && preg_match("/^enum\((.*)\)$/", $row['Type'], $matches)) {
        $enumValues = explode(",", $matches[1]);
        foreach ($enumValues as $value) {
            $roles[] = trim($value, "'");
        }
    }
} catch (PDOException $e) {
    echo "Error fetching roles: " . $e->getMessage();
}

$showAlert = false;
$errorMsg = "";
$showSuccess = false;

if (isset($_POST["btnSave"])) {
    $username = $_POST["username"];
    $password = $_POST["password"];
    $role = $_POST["role"];
    $dept_id = $_POST["dept_id"];

    $password = password_hash($password, PASSWORD_BCRYPT);

    if (empty($username)) {
        $showAlert = true;
        $errorMsg = "Please enter a valid username!";
    } else {
        $check = $conn->prepare("SELECT * FROM users WHERE username = :username");
        $check->execute([':username' => $username]);
        if ($check->rowCount() > 0) {
            $showAlert = true;
            $errorMsg = "User already exists!";
        } else {
            $sql = "INSERT INTO users (username, password, role, dept_id, status) 
                    VALUES (:username, :password, :role, :dept_id, :status)";
            $values = array(
                ":username" => $username,
                ":password" => $password,
                ":role" => $role,
                ":dept_id" => $dept_id,
                ":status" => 2
            );

            $result = $conn->prepare($sql);
            $result->execute($values);

            if ($result->rowCount() > 0) {
                $showSuccess = true;
            } else {
                $showAlert = true;
                $errorMsg = "No record has been saved!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - HQS</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #1d3557;
            --secondary-color: #457b9d;
            --accent-color: #4895ef;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #4cc9f0;
            --danger-color: #f72585;
            --border-radius: 12px;
            --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #e0f2fe 0%, #f0fdfa 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: var(--dark-color);
        }

        .container {
            max-width: 600px;
            width: 100%;
            padding: 2rem;
        }

        .form-container {
            background-color: white;
            padding: 2.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            position: relative;
            overflow: hidden;
        }

        .form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 8px;
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
        }

        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .form-header h1 {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 0.5rem;
            font-size: 2rem;
        }

        .form-header p {
            color: #6c757d;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark-color);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            border: 2px solid #e9ecef;
            border-radius: var(--border-radius);
            transition: var(--transition);
            background-color: #f8f9fa;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
            background-color: white;
        }

        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 16px 12px;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
        }

        .btn {
            width: 100%;
            padding: 0.75rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: var(--border-radius);
            border: none;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 0.5rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .form-footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.9rem;
            color: #6c757d;
        }

        .form-footer a {
            color: var(--primary-color);
            font-weight: 600;
            text-decoration: none;
        }

        .form-footer a:hover {
            text-decoration: underline;
        }

        .input-icon {
            position: relative;
        }

        .input-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }

        .input-icon input, .input-icon select {
            padding-left: 40px;
        }

        @media (max-width: 576px) {
            .container {
                padding: 1rem;
            }
            
            .form-container {
                padding: 1.5rem;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-container {
            animation: fadeIn 0.5s ease-out;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <div class="form-header">
                <h1>Create Your Account</h1>
            </div>
            
            <form action="register.php" method="POST">
                <div class="form-group input-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" id="username" class="form-control" placeholder="Enter your username" required>
                </div>

                <div class="form-group input-icon">
                    <i class="fas fa-key"></i>
                    <select name="role" id="role" class="form-control form-select" required>
                        <option value="">Select your role</option>
                        <?php foreach ($roles as $roleOption): ?>
                            <option value="<?= htmlspecialchars($roleOption) ?>">
                                <?= htmlspecialchars(ucfirst($roleOption)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group input-icon">
                    <i class="fas fa-building"></i>
                    <select name="dept_id" id="dept_id" class="form-control form-select" required>
                        <option value="">Select your department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= htmlspecialchars($dept['dept_id']) ?>">
                                <?= htmlspecialchars($dept['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group input-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Create a password" required>
                </div>

                <button type="submit" class="btn btn-primary" name="btnSave">
                    <i class="fas fa-user-plus me-2"></i> Create Account
                </button>
            </form>

        </div>
    </div>

    <script>
        function togglePassword() {
            const password = document.getElementById("password");
            const icon = document.getElementById("toggleIcon");
            
            if (password.type === "password") {
                password.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                password.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }

        <?php if ($showSuccess): ?>
            Swal.fire({
                icon: 'success',
                title: 'Account Created!',
                text: 'Your account has been created successfully.',
                confirmButtonText: 'Continue',
                customClass: {
                    confirmButton: 'btn btn-primary'
                },
                buttonsStyling: false
            }).then(() => {
                window.location.href = 'index.php';
            });
        <?php elseif ($showAlert): ?>
            Swal.fire({
                icon: 'error',
                title: 'Registration Failed',
                text: '<?= $errorMsg; ?>',
                confirmButtonText: 'Try Again',
                customClass: {
                    confirmButton: 'btn btn-primary'
                },
                buttonsStyling: false
            });
        <?php endif; ?>
    </script>
</body>
</html>