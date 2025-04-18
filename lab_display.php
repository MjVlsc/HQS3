<?php 
require('lib/conn.php');

// Get department_id from URL or default to 8
$departmentId = isset($_GET['department_id']) ? intval($_GET['department_id']) : 8;

// Fetch department name
$deptName = "Unknown Department";
$deptStmt = $conn->prepare("SELECT name FROM departments WHERE dept_id = :dept_id");
$deptStmt->execute(['dept_id' => $departmentId]);
if ($row = $deptStmt->fetch()) {
    $deptName = $row['name'];
}

// Get current queue
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
$currentStmt->execute(['dept_id' => $departmentId]);
$currentQueue = $currentStmt->fetch();

// Get upcoming queues (next 5)
$upcomingStmt = $conn->prepare("
    SELECT * FROM queues 
    WHERE status = 'waiting' AND department_id = :dept_id
    ORDER BY 
        CASE priority
            WHEN 'emergency' THEN 1
            WHEN 'PWD' THEN 2
            WHEN 'Senior_Citizen' THEN 3
            WHEN 'pregnant' THEN 4
            ELSE 5
        END,
        created_at ASC
    LIMIT 5
");
$upcomingStmt->execute(['dept_id' => $departmentId]);
$upcomingQueues = $upcomingStmt->fetchAll();

// Get postponed queues (last 5)
$postponedStmt = $conn->prepare("
    SELECT * FROM queues 
    WHERE status = 'postponed' AND department_id = :dept_id
    ORDER BY updated_at DESC
    LIMIT 5
");
$postponedStmt->execute(['dept_id' => $departmentId]);
$postponedQueues = $postponedStmt->fetchAll();

// Get pending queues (last 5)
$pendingStmt = $conn->prepare("
    SELECT * FROM queues 
    WHERE status = 'pending' AND department_id = :dept_id
    ORDER BY updated_at DESC
    LIMIT 5
");
$pendingStmt->execute(['dept_id' => $departmentId]);
$pendingQueues = $pendingStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Queue Display - <?= htmlspecialchars($deptName) ?></title>
  <style>
    :root {
      --primary: #457b9d;
      --primary-dark: #1d3557;
      --secondary: #e63946;
      --warning: #FF9800;
      --light-gray: #f5f5f5;
      --white: #ffffff;
      --dark-gray: #333;
    }
    
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: var(--primary-dark);
      color: var(--white);
      height: 100vh;
      display: grid;
      grid-template-columns: 1fr 2fr 1fr;
      gap: 20px;
      padding: 20px;
      overflow: hidden;
    }
    
    .panel {
      display: flex;
      flex-direction: column;
      height: 100%;
    }
    
    .left-panel {
      justify-content: flex-start;
    }
    
    .center-panel {
      justify-content: center;
      text-align: center;
    }
    
    .right-panel {
      justify-content: flex-start;
    }
    
    .department-name {
      font-size: 3rem;
      font-weight: bold;
      text-transform: uppercase;
      margin-bottom: 2rem;
      text-align: center;
    }
    
    .section-title {
      font-size: 1.5rem;
      margin-bottom: 1rem;
      padding-bottom: 0.5rem;
      border-bottom: 2px solid var(--white);
    }
    
    .current-queue {
      margin: 2rem 0;
    }
    
    .current-number {
      font-size: 10rem;
      font-weight: bold;
      color: var(--secondary);
      line-height: 1;
      margin: 1rem 0;
    }
    
    .priority-badge {
      font-size: 1.5rem;
      padding: 0.5rem 1rem;
      border-radius: 20px;
      margin-top: 1rem;
      display: inline-block;
    }
    
    .priority-emergency {
      background-color: #ff0000;
      animation: blink 1s infinite;
    }
    
    .priority-pwd {
      background-color: #4CAF50;
    }
    
    .priority-senior {
      background-color: #FF9800;
    }
    
    .priority-pregnant {
      background-color: #9C27B0;
    }
    
    .priority-normal {
      background-color: #2196F3;
    }
    
    @keyframes blink {
      0% { opacity: 1; }
      50% { opacity: 0.5; }
      100% { opacity: 1; }
    }
    
    .current-label {
      font-size: 2rem;
      margin-bottom: 1rem;
    }
    
    .queue-list {
      display: flex;
      flex-direction: column;
      gap: 0.8rem;
    }
    
    .queue-item {
      font-size: 1.8rem;
      padding: 0.8rem;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 5px;
      text-align: center;
    }
    
    .postponed-item {
      color: var(--warning);
    }
    
    .pending-item {
      color: #ffcccc;
    }
    
    .empty-state {
      font-size: 1.2rem;
      opacity: 0.7;
      text-align: center;
      padding: 1rem;
    }
    
    @media (max-width: 1200px) {
      body {
        grid-template-columns: 1fr 1fr;
        grid-template-rows: auto 1fr;
      }
      
      .center-panel {
        grid-column: span 2;
      }
      
      .department-name {
        font-size: 2.5rem;
      }
      
      .current-number {
        font-size: 8rem;
      }
    }
    
    @media (max-width: 768px) {
      body {
        grid-template-columns: 1fr;
        grid-template-rows: auto auto auto;
        gap: 15px;
      }
      
      .center-panel {
        grid-column: span 1;
        order: 1;
      }
      
      .left-panel {
        order: 2;
      }
      
      .right-panel {
        order: 3;
      }
      
      .department-name {
        font-size: 2rem;
      }
      
      .current-number {
        font-size: 6rem;
      }
      
      .queue-item {
        font-size: 1.5rem;
      }
    }
  </style>
</head>
<body>
  <!-- Left Panel - Postponed & Pending -->
  <div class="panel left-panel">
    <div class="section-container">
      <div class="section-title">Postponed</div>
      <div class="queue-list">
        <?php if (count($postponedQueues) > 0): ?>
          <?php foreach ($postponedQueues as $q): ?>
            <div class="queue-item postponed-item"><?= htmlspecialchars($q['queue_num']) ?></div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="empty-state">No postponed queues</div>
        <?php endif; ?>
      </div>
    </div>
    
    <div class="section-container" style="margin-top: 2rem;">
      <div class="section-title">Pending Results</div>
      <div class="queue-list">
        <?php if (count($pendingQueues) > 0): ?>
          <?php foreach ($pendingQueues as $q): ?>
            <div class="queue-item pending-item"><?= htmlspecialchars($q['queue_num']) ?></div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="empty-state">No pending queues</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  
  <!-- Center Panel - Current Queue -->
  <div class="panel center-panel">
    <div class="department-name"><?= htmlspecialchars($deptName) ?></div>
    
    <div class="current-queue">
      <div class="current-label">Now Serving</div>
      <?php if ($currentQueue): ?>
        <div class="current-number"><?= htmlspecialchars($currentQueue['queue_num']) ?></div>
        <?php if ($currentQueue['priority']): ?>
          <div class="priority-badge priority-<?= strtolower(str_replace('_', '-', $currentQueue['priority'])) ?>">
            <?= ucfirst(str_replace('_', ' ', $currentQueue['priority'])) ?>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <div class="current-number empty-state">---</div>
      <?php endif; ?>
    </div>
  </div>
  
  <!-- Right Panel - Upcoming Queues -->
  <div class="panel right-panel">
    <div class="section-title">Upcoming</div>
    <div class="queue-list">
      <?php if (count($upcomingQueues) > 0): ?>
        <?php foreach ($upcomingQueues as $q): ?>
          <div class="queue-item">
            <?= htmlspecialchars($q['queue_num']) ?>
            <?php if ($q['priority'] && $q['priority'] != 'normal'): ?>
              <small>(<?= ucfirst(str_replace('_', ' ', $q['priority'])) ?>)</small>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="empty-state">No upcoming queues</div>
      <?php endif; ?>
    </div>
  </div>

  <script>
    // Auto-refresh every 10 seconds
    setTimeout(() => location.reload(), 10000);
    
    // Add animation for current queue number
    const currentNumber = document.querySelector('.current-number');
    if (currentNumber) {
      currentNumber.style.transition = 'transform 0.3s ease';
      setInterval(() => {
        currentNumber.style.transform = 'scale(1.05)';
        setTimeout(() => {
          currentNumber.style.transform = 'scale(1)';
        }, 300);
      }, 5000);
    }
  </script>
</body>
</html>