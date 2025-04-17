<?php
session_start();
$showAlert = false;
$errorMsg = '';

if (isset($_POST["btnLogin"])) {
    require("lib/conn.php");
    $username = trim($_POST["username"] ?? '');
    $password = trim($_POST["password"] ?? '');

    if (empty($username)) {
        $showAlert = true;
        $errorMsg = "Please enter a valid username!";
    } else {
        $sql = "SELECT * FROM users WHERE username = :username";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(":username", $username);
        $stmt->execute();
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role']; // Set role from database, not from POST
            $_SESSION['dept_id'] = $user['dept_id'] ?? null; // Set department ID if exists

            if ($user['role'] == 'Admin') {
                header("Location: mainpage.php");
                exit();
            } elseif (in_array($user['role'], ['Admitting', 'Information'])) {
                header("Location: queue_display.php");
                exit();
            } elseif ($user['role'] == 'User') {
                switch ($user['dept_id']) {
                    case 1: header("Location: queue_bil.php"); break;
                    case 2: header("Location: queue_phar.php"); break;
                    case 3: header("Location: queue_med.php"); break;
                    case 4: header("Location: queue_ult.php"); break;
                    case 5: header("Location: queue_xr.php"); break;
                    case 6: header("Location: queue_rh.php"); break;
                    case 7: header("Location: queue_dia.php"); break;
                    case 8: header("Location: queue_lab.php"); break;
                    default:
                        echo "<script>alert('Unauthorized department access.'); history.back();</script>";
                        break;
                }
                exit();
            }
        } else {
            $showAlert = true;
            $errorMsg = "Incorrect username or password!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login</title>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: Arial, sans-serif;
      background: linear-gradient(to right, #dff1f9, #c4e0e5);
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      overflow-x: hidden;
    }

    .container {
      max-width: 480px;
      padding: 20px;
      position: relative;
      z-index: 1;
    }

    .logo-container {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 0;
        text-align: center;
    }

    .system-title {
        font-size: 35px;
        font-weight: bold;
        color: #1d3557;
        margin-bottom: 10px;
        text-transform: uppercase;
    }

    .logo-container img {
        height: 650px;
        width: 650px;
    }

    .form-box {
      background-color: rgba(255, 255, 255, 0.85);
      padding: 30px 25px;
      border-radius: 16px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
      margin: 0 auto;
      position: relative;
      z-index: 1;
    }

    .form-box header {
      font-size: 24px;
      font-weight: bold;
      text-align: center;
      margin-bottom: 25px;
      color: #1d3557;
    }

    .field {
      margin-bottom: 20px;
    }

    .field label {
      display: block;
      margin-bottom: 6px;
      font-weight: bold;
      color: #1d3557;
    }

    input[type="text"],
    input[type="password"] {
      width: 100%;
      padding: 12px 14px;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 16px;
    }

    input[type="text"]:focus,
    input[type="password"]:focus {
      outline: none;
      border-color: #457b9d;
      box-shadow: 0 0 5px rgba(69, 123, 157, 0.4);
    }

    .checkbox-field {
      margin-bottom: 20px;
      font-size: 0.95rem;
      display: flex;
      align-items: center;
      color: #1d3557;
    }

    .checkbox-field input {
      margin-right: 10px;
      transform: scale(1.2);
    }

    .checkbox-field label {
      font-weight: normal;
    }

    .btn {
      width: 100%;
      background-color: #1d3557;
      color: white;
      border: none;
      padding: 12px;
      font-weight: bold;
      border-radius: 8px;
      font-size: 16px;
      transition: background-color 0.3s ease;
    }

    .btn:hover {
      background-color: #457b9d;
    }

    .text-center {
      text-align: center;
      margin-top: 15px;
    }

    .text-center a {
      color: #1d3557;
      font-weight: bold;
      text-decoration: none;
    }

    .text-center a:hover {
      text-decoration: underline;
    }

    .login-title {
      text-align: center; 
      margin-bottom: 15px; 
      font-size: 20px; 
      font-weight: bold; 
      color: #1d3557;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="logo-container">
      <div class="system-title">HOSPITAL QUEUEING SYSTEM</div>
      <img src="lib/images/CALLANG LOGO OFFICIAL.png" alt="Logo">
    </div>

    <div class="form-box">
      <div class="login-title">LOGIN</div>
      <form action="index.php" method="POST">
        <div class="field">
          <label for="username">Username</label>
          <input type="text" name="username" id="username" required autocomplete="off">
        </div>

        <div class="field">
          <label for="password">Password</label>
          <input type="password" name="password" id="password" required autocomplete="off">
        </div>

        <div class="checkbox-field">
          <input type="checkbox" onclick="togglePassword()" id="showPass">
          <label for="showPass">Show Password</label>
        </div>

        <div class="field">
          <button type="submit" class="btn" name="btnLogin">Login</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function togglePassword() {
      const pass = document.getElementById("password");
      pass.type = pass.type === "password" ? "text" : "password";
    }

    <?php if ($showAlert): ?>
      Swal.fire({
        icon: 'error',
        title: 'Login Failed',
        text: '<?= $errorMsg ?>',
        confirmButtonColor: '#1d3557',
        background: '#fff',
        customClass: {
          popup: 'rounded-4 shadow'
        }
      });
    <?php endif; ?>
  </script>
</body>
</html>