<?php
require('lib/conn.php');

// Fetch all departments
$departmentsStmt = $conn->prepare("SELECT * FROM departments WHERE dept_id NOT IN (9, 10, 11, 12)");
$departmentsStmt->execute();
$departments = $departmentsStmt->fetchAll();

$queues = [];
foreach ($departments as $department) {
    // Current queue
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

    // Upcoming queues
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
    $upcomingQueues = $upcomingStmt->fetchAll();

    // Pending queues
    $pendingStmt = $conn->prepare("
        SELECT * FROM queues 
        WHERE status = 'pending' AND department_id = :dept_id
        ORDER BY 
            CASE 
                WHEN priority = 'emergency' THEN 0
                WHEN priority IN ('PWD', 'Senior_Citizen', 'pregnant') THEN 1
                ELSE 2
            END,
            updated_at DESC
    ");
    $pendingStmt->execute(['dept_id' => $department['dept_id']]);
    $pendingQueues = $pendingStmt->fetchAll();

    // Postponed queues
    $postponedStmt = $conn->prepare("
        SELECT * FROM queues 
        WHERE status = 'postponed' AND department_id = :dept_id
        ORDER BY updated_at DESC
    ");
    $postponedStmt->execute(['dept_id' => $department['dept_id']]);
    $postponedQueues = $postponedStmt->fetchAll();

    $queues[] = [
        'department' => $department,
        'currentQueue' => $currentQueue,
        'upcomingQueues' => $upcomingQueues,
        'pendingQueues' => $pendingQueues,
        'postponedQueues' => $postponedQueues
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Now Serving - Hospital Queue</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background-color: #f8f9fa;
    }

    .display-container {
      padding: 20px;
      max-width: 1400px;
      margin: 0 auto;
      text-align: center;
    }

    h1 {
      font-size: 3rem;
      color: #1d3557;
      font-weight: bold;
      margin-bottom: 30px;
    }

    .cards-wrapper {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
      gap: 24px;
      justify-content: center;
    }

    .queue-card {
      background-color: white;
      border-radius: 16px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      padding: 30px 20px;
      text-align: center;
    }

    .department-name {
      font-size: 1.7rem;
      color: #1d3557;
      font-weight: 700;
      margin-bottom: 20px;
    }

    .queue-label {
      font-size: 1.3rem;
      color: #1d3557;
      font-weight: 600;
      margin-bottom: 10px;
    }

    .queue-value {
      font-size: 2.2rem;
      color: #e63946;
      font-weight: bold;
      margin-bottom: 20px;
      word-wrap: break-word;
    }

    .priority {
      font-size: 1.4rem;
      color: #457b9d;
      font-weight: 600;
      margin-bottom: 20px;
    }

    .queue-section {
      margin-top: 20px;
    }

    .queue-section h3 {
      font-size: 1.4rem;
      color: #1d3557;
      margin-bottom: 12px;
    }

    .queue-list {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      justify-content: center;
    }

    .queue-item {
      background-color: #f1faee;
      color: #1d3557;
      font-size: 1.1rem;
      padding: 8px 12px;
      border-radius: 6px;
      font-weight: 600;
    }

    .pending-item {
      background-color: #fff3cd;
      color: #856404;
    }

    .postponed-item {
      background-color: #f8d7da;
      color: #721c24;
    }

    @media (max-width: 768px) {
      h1 {
        font-size: 2.5rem;
      }

      .department-name {
        font-size: 1.5rem;
      }

      .queue-value {
        font-size: 2rem;
      }
    }
  </style>
</head>
<body>
  <div class="display-container">
    <h1>Now Serving</h1>
    <div class="cards-wrapper">
      <?php foreach ($queues as $queueData): ?>
        <div class="queue-card">
          <div class="department-name"><?= strtoupper(htmlspecialchars($queueData['department']['name'])) ?></div>

          <div class="queue-label">Queue Number</div>
          <div class="queue-value">
            <?= $queueData['currentQueue']
              ? htmlspecialchars($queueData['currentQueue']['queue_num'])
              : 'None'; ?>
          </div>

          <div class="queue-label">Priority</div>
          <div class="priority">
            <?= $queueData['currentQueue']
              ? ucfirst($queueData['currentQueue']['priority'])
              : '—'; ?>
          </div>

          <div class="queue-section">
            <h3>Upcoming Queues</h3>
            <div class="queue-list">
              <?php if (count($queueData['upcomingQueues']) === 0): ?>
                <div class="queue-item">None</div>
              <?php else: ?>
                <?php foreach (array_slice($queueData['upcomingQueues'], 0, 3) as $q): ?>
                  <div class="queue-item">
                    <?= htmlspecialchars($q['queue_num']) ?> (<?= ucfirst($q['priority']) ?>)
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>

          <div class="queue-section">
            <h3>Pending Queues</h3>
            <div class="queue-list">
              <?php if (count($queueData['pendingQueues']) === 0): ?>
                <div class="queue-item pending-item">None</div>
              <?php else: ?>
                <?php foreach ($queueData['pendingQueues'] as $q): ?>
                  <div class="queue-item pending-item">
                    <?= htmlspecialchars($q['queue_num']) ?> (<?= ucfirst($q['priority']) ?>)
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>

          <div class="queue-section">
            <h3>Postponed Queues</h3>
            <div class="queue-list">
              <?php if (count($queueData['postponedQueues']) === 0): ?>
                <div class="queue-item postponed-item">None</div>
              <?php else: ?>
                <?php foreach ($queueData['postponedQueues'] as $q): ?>
                  <div class="queue-item postponed-item">
                    <?= htmlspecialchars($q['queue_num']) ?> (<?= ucfirst($q['priority']) ?>)
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <script>
    // Auto-refresh every 10 seconds
    setInterval(function () {
      location.reload();
    }, 5000);
  </script>
</body>
</html>
