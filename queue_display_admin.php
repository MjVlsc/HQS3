<?php
session_start();
require('lib/conn.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if (!isset($_SESSION['logout_token'])) {
    $_SESSION['logout_token'] = bin2hex(random_bytes(32));
}

$role = $_SESSION['role'];
$username = $_SESSION['username'];

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
  <title>Hospital Queue Status</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap">
  <style>
    * { box-sizing: border-box; }
    body {
      font-family: 'Inter', sans-serif;
      margin: 0;
      background: #f1f5f9;
      display: flex;
      min-height: 100vh;
    }

    .hamburger {
      display: none;
      position: fixed;
      top: 15px;
      left: 15px;
      font-size: 24px;
      background: #1d3557;
      color: white;
      border: none;
      border-radius: 6px;
      padding: 8px 12px;
      z-index: 1000;
      cursor: pointer;
    }

    .sidebar {
      width: 280px;
      background-color: #1d3557;
      color: white;
      padding: 30px 20px;
      display: flex;
      flex-direction: column;
    }

    .sidebar h2 {
      font-size: 1.6rem;
      margin-bottom: 40px;
      text-align: center;
      font-weight: bold;
    }

    .nav-link {
      margin: 12px 0;
      text-decoration: none;
      color: white;
      font-size: 18px;
      font-weight: bold;
      display: flex;
      align-items: center;
      padding: 12px 15px;
      border-radius: 8px;
      transition: background 0.3s ease;
    }

    .nav-link:hover {
      background-color: #457b9d;
    }

    .nav-link i.icon {
      margin-right: 12px;
      font-size: 20px;
      width: 25px;
      text-align: center;
    }

    .main-content {
      flex: 1;
      padding: 40px;
      transition: margin-left 0.3s ease;
    }

    h1 {
      font-weight: 800;
      color: #1d3557;
      text-align: center;
      margin-bottom: 40px;
    }

    .department-box {
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.06);
      margin-bottom: 35px;
      padding: 25px;
      transition: box-shadow 0.3s;
    }

    .department-box:hover {
      box-shadow: 0 4px 16px rgba(0,0,0,0.1);
    }

    .department-header {
      font-size: 1.25em;
      font-weight: 600;
      color: #1d3557;
      margin-bottom: 18px;
      border-bottom: 1px solid #ddd;
      padding-bottom: 8px;
    }

    .queue-info {
      display: flex;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 20px;
    }

    .queue-item {
      flex: 1 1 250px;
      background: #f1faee;
      padding: 18px;
      border-radius: 10px;
      color: #457b9d;
      text-align: center;
    }

    .current-queue {
      font-size: 30px;
      color: #e63946;
      font-weight: bold;
      margin-top: 5px;
    }

    .queue-list span {
      display: inline-block;
      margin: 4px;
      padding: 6px 14px;
      background-color: #f1faee;
      border-radius: 6px;
      color: #457b9d;
      font-size: 0.95rem;
    }

    .btn-toggle {
      background: none;
      border: none;
      color: #1d3557;
      cursor: pointer;
      font-size: 0.85rem;
      margin-top: 10px;
      text-decoration: underline;
      padding: 5px;
    }

    .extra-queues {
      margin-top: 10px;
    }

    .user-dropdown {
      text-align: right;
      margin-bottom: 20px;
      position: relative;
      display: inline-block;
      float: right;
    }

    .user-info {
      background-color: #e63946;
      color: white;
      border-radius: 5px;
      padding: 10px 15px;
      font-weight: 600;
      cursor: pointer;
    }

    .logout-dropdown {
      display: none;
      position: absolute;
      right: 0;
      top: 100%;
      background-color: white;
      border: 1px solid #ccc;
      border-radius: 5px;
      padding: 8px 15px;
      font-weight: bold;
      color: #e63946;
      cursor: pointer;
      z-index: 10;
    }

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
      background-color: white;
      padding: 30px;
      border-radius: 10px;
      width: 90%;
      max-width: 400px;
      text-align: center;
    }

    .modal h3 {
      margin-bottom: 20px;
      color: #1d3557;
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
      font-weight: bold;
      cursor: pointer;
    }

    .modal-btn-confirm {
      background-color: #e63946;
      color: white;
    }

    .modal-btn-cancel {
      background-color: #ccc;
      color: #333;
    }

    @media (max-width: 768px) {
      .hamburger { display: block; }
      .sidebar {
        position: fixed;
        top: 0;
        left: -280px;
        transition: left 0.3s ease;
        height: 100%;
        z-index: 999;
      }
      .sidebar.active { left: 0; }
      .main-content { padding: 20px; margin-left: 0; }
      .main-content.sidebar-open { margin-left: 280px; }
      .queue-info { flex-direction: column; }
    }
  </style>
</head>
<body>

<!-- Hamburger -->
<button class="hamburger" onclick="toggleSidebar()">☰</button>

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

<!-- Main Content -->
<div class="main-content">
  <div class="user-dropdown">
    <div class="user-info">
      Welcome, <?php echo htmlspecialchars($username); ?> (<?php echo htmlspecialchars($role); ?>)
    </div>
    <div class="logout-dropdown" id="logoutBtn">Logout</div>
  </div>

  <h1>Hospital Queue Status</h1>

  <?php foreach ($queues as $queueData): ?>
    <div class="department-box">
      <div class="department-header">
        <?= htmlspecialchars($queueData['department']['name']); ?>
      </div>
      <div class="queue-info">
        <div class="queue-item">
          <div>Current Number</div>
          <div class="current-queue">
            <?= $queueData['currentQueue']
              ? htmlspecialchars($queueData['currentQueue']['queue_num'])
              : 'None'; ?>
          </div>
        </div>

        <div class="queue-item">
          <div style="font-weight: bold;">Priority</div>
          <div><span style="color: black;">
            <?= $queueData['currentQueue']
              ? ucfirst($queueData['currentQueue']['priority'])
              : '—'; ?>
          </span></div>
        </div>

        <div class="queue-item">
          <div>Upcoming</div>
          <div class="queue-list">
            <?php if (count($queueData['upcomingQueues']) <= 0): ?>
              <span>No upcoming queues.</span>
            <?php else: ?>
              <?php foreach ($queueData['upcomingQueues'] as $q): ?>
                <span><?= htmlspecialchars($q['queue_num']); ?> (<?= ucfirst($q['priority']); ?>)</span>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

          <?php if (count($queueData['extraQueues']) > 0): ?>
            <button class="btn-toggle" data-dept="<?= $queueData['department']['dept_id']; ?>">Show More</button>
            <div class="extra-queues queue-list" id="extra-<?= $queueData['department']['dept_id']; ?>" style="display: none;">
              <?php foreach ($queueData['extraQueues'] as $q): ?>
                <span><?= htmlspecialchars($q['queue_num']); ?> (<?= ucfirst($q['priority']); ?>)</span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<!-- Scripts -->
<script>
  setInterval(() => location.reload(), 10000);

  function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const main = document.querySelector('.main-content');
    sidebar.classList.toggle('active');
    main.classList.toggle('sidebar-open');
  }

  document.querySelectorAll('.btn-toggle').forEach(btn => {
    btn.addEventListener('click', function () {
      const deptId = this.dataset.dept;
      const container = document.getElementById('extra-' + deptId);
      const isShown = container.style.display === 'block';
      container.style.display = isShown ? 'none' : 'block';
      this.textContent = isShown ? 'Show More' : 'Show Less';
    });
  });

  document.addEventListener('DOMContentLoaded', function () {
    const logoutModal = document.getElementById('logoutModal');
    const logoutBtn = document.getElementById('logoutBtn');
    const cancelBtn = document.getElementById('cancelLogout');

    logoutBtn.addEventListener('click', () => logoutModal.classList.add('active'));
    cancelBtn.addEventListener('click', () => logoutModal.classList.remove('active'));

    logoutModal.addEventListener('click', e => {
      if (e.target === logoutModal) logoutModal.classList.remove('active');
    });

    document.querySelector('.user-dropdown').addEventListener('mouseenter', () => {
      document.querySelector('.logout-dropdown').style.display = 'block';
    });

    document.querySelector('.user-dropdown').addEventListener('mouseleave', () => {
      document.querySelector('.logout-dropdown').style.display = 'none';
    });

    document.addEventListener('keydown', e => {
      if (logoutModal.classList.contains('active') && e.key === 'Escape') {
        logoutModal.classList.remove('active');
      }
    });
  });
</script>
</body>
</html>
