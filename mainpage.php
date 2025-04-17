<?php
session_start();
require('lib/conn.php');

// Check if user is logged in, otherwise redirect to login
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Generate CSRF token for logout if not exists
if (!isset($_SESSION['logout_token'])) {
    $_SESSION['logout_token'] = bin2hex(random_bytes(32));
}

$role = $_SESSION['role'];
$username = $_SESSION['username'];
$departmentId = $_SESSION['dept_id'] ?? null;

// Fetch all departments
$departmentsStmt = $conn->prepare("SELECT * FROM departments");
$departmentsStmt->execute();
$departments = $departmentsStmt->fetchAll();

$queues = [];
foreach ($departments as $department) {
    $currentStmt = $conn->prepare("
        SELECT * FROM queues 
        WHERE status = 'in-progress' AND department_id = :dept_id
        ORDER BY 
            CASE priority
                WHEN 'emergency' THEN 1
                WHEN 'PWD' THEN 2
                WHEN 'Senior_Citizen' THEN 3
                WHEN 'pregnant' THEN 4
                ELSE 5
            END,
            CAST(SUBSTRING(queue_num, 5) AS UNSIGNED) ASC
        LIMIT 1
    ");
    $currentStmt->execute(['dept_id' => $department['dept_id']]);
    $currentQueue = $currentStmt->fetch();

    $upcomingSql = "
    SELECT * 
    FROM queues 
    WHERE status = 'waiting' 
    AND department_id = :dept_id 
    ORDER BY 
       CASE 
            WHEN priority = 'emergency' THEN 0
            WHEN priority IN ('PWD', 'Senior_Citizen', 'pregnant') THEN 1
            ELSE 2
        END,
        created_at ASC
    ";
    $upcomingStmt = $conn->prepare($upcomingSql);
    $upcomingStmt->execute(['dept_id' => $department['dept_id']]);
    $allUpcomingQueues = $upcomingStmt->fetchAll();

    $queues[] = [
        'department' => $department,
        'currentQueue' => $currentQueue,
        'upcomingQueues' => array_slice($allUpcomingQueues, 0, 3),
        'extraQueues' => array_slice($allUpcomingQueues, 3)
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hospital Queue</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    :root {
      --primary-color: #1d3557;
      --secondary-color: #457b9d;
      --accent-color: #e63946;
      --light-color: #f1faee;
      --background-color: #f1f5f9;
      --text-color: #333;
      --white: #fff;
      --shadow: 0 4px 10px rgba(0,0,0,0.1);
      --transition: all 0.3s ease;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      margin: 0;
      background: var(--background-color);
      display: flex;
      min-height: 100vh;
      color: var(--text-color);
      line-height: 1.6;
    }

    .hamburger {
      display: none;
      position: fixed;
      top: 15px;
      left: 15px;
      font-size: 24px;
      background: var(--primary-color);
      color: var(--white);
      border: none;
      border-radius: 6px;
      padding: 8px 12px;
      z-index: 1000;
      cursor: pointer;
      transition: var(--transition);
    }

    .hamburger:hover {
      background: var(--secondary-color);
    }

    .sidebar {
      width: 280px;
      background-color: var(--primary-color);
      color: var(--white);
      padding: 30px 20px;
      display: flex;
      flex-direction: column;
      transition: transform 0.3s ease;
      z-index: 999;
    }

    .sidebar h2 {
      font-size: 1.6rem;
      margin-bottom: 40px;
      text-align: center;
      font-weight: bold;
      letter-spacing: 1px;
      color: var(--white);
    }

    .nav-link {
      margin: 12px 0;
      text-decoration: none;
      color: var(--white);
      font-size: 18px;
      font-weight: 600;
      display: flex;
      align-items: center;
      padding: 12px 15px;
      border-radius: 8px;
      transition: var(--transition);
      white-space: nowrap;
    }

    .nav-link:hover, .nav-link:focus {
      background-color: var(--secondary-color);
      outline: none;
    }

    .nav-link i.icon {
      margin-right: 12px;
      font-size: 20px;
      width: 25px;
      text-align: center;
    }

    .nav-link span {
      flex: 1;
    }

    .main-content {
      flex: 1;
      padding: 40px;
      transition: margin-left 0.3s ease;
      margin-top: 20px;
    }

    h1 {
      font-family: Arial, sans-serif;
      font-weight: 900;
      color: var(--primary-color);
      text-align: center;
      margin-bottom: 30px;
      margin-top: 0;
      padding-top: 20px;
      font-size: 2.2rem;
    }

    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 25px;
      margin-top: 20px;
    }

    .card {
      background: var(--white);
      padding: 30px;
      border-radius: 12px;
      box-shadow: var(--shadow);
      text-align: center;
      cursor: pointer;
      transition: var(--transition);
      display: flex;
      flex-direction: column;
      align-items: center;
      min-height: 200px;
      justify-content: center;
    }

    .card:hover, .card:focus {
      transform: translateY(-5px);
      background-color: var(--light-color);
      box-shadow: 0 6px 15px rgba(0,0,0,0.15);
    }

    .card h2 {
      margin: 15px 0 0 0;
      font-size: 22px;
      color: var(--secondary-color);
    }

    .card i {
      font-size: 40px;
      color: var(--secondary-color);
      margin-bottom: 15px;
      display: block;
    }

    .fab {
      position: fixed;
      bottom: 20px;
      right: 20px;
      background-color: var(--primary-color);
      color: var(--white);
      font-size: 24px;
      border-radius: 50%;
      width: 60px;
      height: 60px;
      border: none;
      display: flex;
      justify-content: center;
      align-items: center;
      cursor: pointer;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      transition: var(--transition);
      z-index: 100;
    }

    .fab:hover {
      background-color: var(--accent-color);
      transform: scale(1.1);
    }

    .user-info {
      text-align: right;
      margin-bottom: 20px;
      padding: 10px 15px;
      background-color: var(--accent-color);
      color: var(--white);
      border-radius: 5px;
      display: inline-block;
      float: right;
      font-weight: bold;
      box-shadow: var(--shadow);
    }

    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.5);
      z-index: 2000;
      justify-content: center;
      align-items: center;
      opacity: 0;
      transition: opacity 0.3s ease;
    }

    .modal.active {
      display: flex;
      opacity: 1;
    }

    .modal-content {
      background-color: var(--white);
      padding: 30px;
      border-radius: 10px;
      width: 90%;
      max-width: 400px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.3);
      text-align: center;
      transform: translateY(-20px);
      transition: transform 0.3s ease;
    }

    .modal.active .modal-content {
      transform: translateY(0);
    }

    .modal h3 {
      margin-bottom: 20px;
      color: var(--primary-color);
      font-size: 1.5rem;
    }

    .modal-buttons {
      display: flex;
      justify-content: center;
      gap: 15px;
      margin-top: 25px;
    }

    .modal-btn {
      padding: 10px 20px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-weight: bold;
      transition: var(--transition);
    }

    .modal-btn-confirm {
      background-color: var(--accent-color);
      color: var(--white);
    }

    .modal-btn-confirm:hover {
      background-color: #c1121f;
    }

    .modal-btn-cancel {
      background-color: #ccc;
      color: var(--text-color);
    }

    .modal-btn-cancel:hover {
      background-color: #aaa;
    }

    /* Loading animation */
    .loading {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(255,255,255,0.8);
      z-index: 3000;
      justify-content: center;
      align-items: center;
    }

    .loading.active {
      display: flex;
    }

    .spinner {
      width: 50px;
      height: 50px;
      border: 5px solid #f3f3f3;
      border-top: 5px solid var(--primary-color);
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    /* Accessibility improvements */
    a:focus, button:focus {
      outline: 3px solid var(--secondary-color);
      outline-offset: 2px;
    }

    .sr-only {
      position: absolute;
      width: 1px;
      height: 1px;
      padding: 0;
      margin: -1px;
      overflow: hidden;
      clip: rect(0, 0, 0, 0);
      white-space: nowrap;
      border-width: 0;
    }

    /* Responsive styles */
    @media (max-width: 768px) {
      .hamburger {
        display: block;
      }

      .sidebar {
        position: fixed;
        top: 0;
        left: -280px;
        height: 100%;
        width: 280px;
      }

      .sidebar.active {
        left: 0;
      }

      .main-content {
        padding: 20px;
        margin-left: 0;
        margin-top: 60px;
      }

      .user-info {
        float: none;
        display: block;
        text-align: center;
        margin: 0 auto 20px;
        width: 100%;
      }

      h1 {
        font-size: 1.8rem;
        margin-bottom: 20px;
      }
    }

    @media (max-width: 480px) {
      .grid {
        grid-template-columns: 1fr;
      }
      
      .modal-content {
        width: 95%;
        padding: 20px;
      }
    }
  </style>
</head>
<body>

<!-- Loading overlay -->
<div class="loading" id="loading">
  <div class="spinner"></div>
</div>

<!-- Hamburger -->
<button class="hamburger" onclick="toggleSidebar()" aria-label="Toggle menu">☰</button>

<!-- Sidebar -->
<nav class="sidebar" aria-label="Main navigation">
  <h2>HOSPITAL</h2>
  <a href="add_patient_q.php?user_id=<?php echo htmlspecialchars($role); ?>" class="nav-link">
    <i class="fas fa-user-plus icon" aria-hidden="true"></i>
    <span>PATIENT TO QUEUE</span>
  </a>
  <a href="queue_list.php" class="nav-link">
    <i class="fas fa-list-alt icon" aria-hidden="true"></i>
    <span>QUEUE HISTORY</span>
  </a>
  <a href="mainpage.php" class="nav-link">
    <i class="fas fa-stream icon" aria-hidden="true"></i>
    <span>DEPARTMENT QUEUE</span>
  </a>
  <?php if ($role === 'Admin'): ?>
    <a href="register.php" class="nav-link">
      <i class="fas fa-user-cog icon" aria-hidden="true"></i>
      <span>ADD USER</span>
    </a>
  <?php endif; ?>
  <a href="queue_display_user.php" class="nav-link" target="_blank">
    <i class="fas fa-bullhorn icon" aria-hidden="true"></i>
    <span>NOW SERVING</span>
  </a>
  <button class="nav-link" id="logoutBtn" style="margin-top: auto; background: none; border: none; cursor: pointer; text-align: left;">
    <i class="fas fa-sign-out-alt icon" aria-hidden="true"></i>
    <span>LOGOUT</span>
  </button>
</nav>

<!-- Logout Confirmation Modal -->
<div class="modal" id="logoutModal">
  <div class="modal-content">
    <h3>Confirm Logout</h3>
    <p>Are you sure you want to log out?</p>
    <form action="logout.php" method="post" id="logoutForm">
      <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['logout_token']; ?>">
      <div class="modal-buttons">
        <button type="button" class="modal-btn modal-btn-cancel" id="cancelLogout">Cancel</button>
        <button type="submit" class="modal-btn modal-btn-confirm">Logout</button>
      </div>
    </form>
  </div>
</div>

<!-- Main content -->
<main class="main-content">
  <div class="user-info">
    Welcome, <?php echo htmlspecialchars($username); ?> (<?php echo htmlspecialchars($role); ?>)
  </div>
  
  <h1>Select a Department</h1>
  <div class="grid">
    <?php if ($role === 'Admin' || $role === 'Admitting' || $role === 'Information' || $departmentId == 1): ?>
      <a href="queue_bil.php" class="card-link">
        <div class="card" tabindex="0">
          <i class="fas fa-hospital" aria-hidden="true"></i>
          <h2>Billing</h2>
        </div>
      </a>
    <?php endif; ?>
    
    <?php if ($role === 'Admin' || $role === 'Admitting' || $role === 'Information' || $departmentId == 2): ?>
      <a href="queue_phar.php" class="card-link">
        <div class="card" tabindex="0">
          <i class="fas fa-pills" aria-hidden="true"></i>
          <h2>Pharmacy</h2>
        </div>
      </a>
    <?php endif; ?>
    
    <?php if ($role === 'Admin' || $role === 'Admitting' || $role === 'Information' || $departmentId == 3): ?>
      <a href="queue_med.php" class="card-link">
        <div class="card" tabindex="0">
          <i class="fas fa-stethoscope" aria-hidden="true"></i>
          <h2>Medical</h2>
        </div>
      </a>
    <?php endif; ?>
    
    <?php if ($role === 'Admin' || $role === 'Admitting' || $role === 'Information' || $departmentId == 4): ?>
      <a href="queue_ult.php" class="card-link">
        <div class="card" tabindex="0">
          <i class="fas fa-syringe" aria-hidden="true"></i>
          <h2>Ultrasound</h2>
        </div>
      </a>
    <?php endif; ?>
    
    <?php if ($role === 'Admin' || $role === 'Admitting' || $role === 'Information' || $departmentId == 5): ?>
      <a href="queue_xray.php" class="card-link">
        <div class="card" tabindex="0">
          <i class="fas fa-x-ray" aria-hidden="true"></i>
          <h2>X-Ray</h2>
        </div>
      </a>
    <?php endif; ?>
    
    <?php if ($role === 'Admin' || $role === 'Admitting' || $role === 'Information' || $departmentId == 6): ?>
      <a href="queue_rehab.php" class="card-link">
        <div class="card" tabindex="0">
          <i class="fas fa-wheelchair" aria-hidden="true"></i>
          <h2>Rehabilitation</h2>
        </div>
      </a>
    <?php endif; ?>
    
    <?php if ($role === 'Admin' || $role === 'Admitting' || $role === 'Information' || $departmentId == 7): ?>
      <a href="queue_dia.php" class="card-link">
        <div class="card" tabindex="0">
          <i class="fas fa-heartbeat" aria-hidden="true"></i>
          <h2>Dialysis</h2>
        </div>
      </a>
    <?php endif; ?>
    
    <?php if ($role === 'Admin' || $role === 'Admitting' || $role === 'Information' || $departmentId == 8): ?>
      <a href="queue_lab.php" class="card-link">
        <div class="card" tabindex="0">
          <i class="fas fa-flask" aria-hidden="true"></i>
          <h2>Laboratory</h2>
        </div>
      </a>
    <?php endif; ?>
    
    <?php if ($role === 'Admin' || $role === 'Admitting' || $role === 'Information'): ?>
      <a href="queue_er.php" class="card-link">
        <div class="card" tabindex="0">
          <i class="fas fa-ambulance" aria-hidden="true"></i>
          <h2>Emergency Room</h2>
        </div>
      </a>
      <a href="queue_sw.php" class="card-link">
        <div class="card" tabindex="0">
          <i class="fas fa-user-friends" aria-hidden="true"></i>
          <h2>Social Worker</h2>
        </div>
      </a>
    <?php endif; ?>
  </div>
</main>

<script>
  // Toggle sidebar
  function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const main = document.querySelector('.main-content');
    sidebar.classList.toggle('active');
    main.classList.toggle('sidebar-open');
  }

  // Logout confirmation
  document.addEventListener('DOMContentLoaded', function() {
    const logoutBtn = document.getElementById('logoutBtn');
    const logoutModal = document.getElementById('logoutModal');
    const cancelLogout = document.getElementById('cancelLogout');
    const logoutForm = document.getElementById('logoutForm');
    const loading = document.getElementById('loading');

    // Show logout confirmation
    logoutBtn.addEventListener('click', function() {
      logoutModal.classList.add('active');
    });

    // Hide logout confirmation
    cancelLogout.addEventListener('click', function() {
      logoutModal.classList.remove('active');
    });

    // Handle form submission
    logoutForm.addEventListener('submit', function(e) {
      e.preventDefault();
      loading.classList.add('active');
      
      // Submit form after showing loading animation
      setTimeout(() => {
        this.submit();
      }, 500);
    });

    // Close modal when clicking outside
    logoutModal.addEventListener('click', function(e) {
      if (e.target === logoutModal) {
        logoutModal.classList.remove('active');
      }
    });

    // Add keyboard navigation
    document.addEventListener('keydown', function(e) {
      if (logoutModal.classList.contains('active')) {
        if (e.key === 'Escape') {
          logoutModal.classList.remove('active');
        }
      }
    });

    // Preload hover effects for better performance
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
      card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-5px)';
        this.style.backgroundColor = '#f1faee';
        this.style.boxShadow = '0 6px 15px rgba(0,0,0,0.15)';
      });
      
      card.addEventListener('mouseleave', function() {
        this.style.transform = '';
        this.style.backgroundColor = '';
        this.style.boxShadow = '';
      });
    });
  });
</script>

</body>
</html>