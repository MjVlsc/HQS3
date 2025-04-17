<?php
require('lib/conn.php');

// Fetch all departments
$departmentsStmt = $conn->prepare("SELECT * FROM departments  WHERE dept_id  NOT IN (9, 10, 11, 12)");
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
    $upcomingQueues = $upcomingStmt->fetchAll();

    $queues[] = [
        'department' => $department,
        'currentQueue' => $currentQueue,
        'upcomingQueues' => $upcomingQueues
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
      width: 100%;
      max-width: 1400px;
      margin: 0 auto;
      text-align: center;
    }

    h1 {
      font-size: 2.5rem;
      font-weight: bold;
      color: #1d3557;
      margin-bottom: 30px;
    }

    .cards-wrapper {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
      gap: 20px;
      justify-content: center;
    }

    .queue-card {
      background-color: white;
      border-radius: 16px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      padding: 25px 20px;
      text-align: center;
      transition: transform 0.3s;
    }

    .queue-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
    }

    .department-name {
      font-size: 1.5rem;
      color: #1d3557;
      font-weight: 700;
      margin-bottom: 15px;
    }

    .queue-label {
      font-size: 1.2rem;
      color: #1d3557;
      font-weight: 600;
      margin-bottom: 8px;
    }

    .queue-value {
      font-size: 1.8rem;
      color: #e63946;
      font-weight: bold;
      margin-bottom: 15px;
      word-wrap: break-word;
    }

    .priority {
      font-size: 1.2rem;
      color: #457b9d;
      font-weight: 600;
      margin-bottom: 15px;
    }

    .upcoming-section {
      margin-top: 15px;
    }

    .upcoming-section h3 {
      font-size: 1.1rem;
      color: #1d3557;
      margin-bottom: 8px;
    }

    .upcoming-list {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      justify-content: center;
    }

    .upcoming-item {
      background-color: #f1faee;
      color: #457b9d;
      font-size: 0.9rem;
      padding: 5px 10px;
      border-radius: 6px;
      font-weight: 600;
    }

    @media (max-width: 1200px) {
      .cards-wrapper {
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      }
    }

    @media (max-width: 768px) {
      h1 {
        font-size: 2rem;
      }
      .cards-wrapper {
        grid-template-columns: 1fr;
      }
      .department-name {
        font-size: 1.3rem;
      }
      .queue-value {
        font-size: 1.5rem;
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
          <div class="department-name">
            <?= strtoupper(htmlspecialchars($queueData['department']['name'])); ?>
          </div>

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

          <div class="upcoming-section">
            <h3>Upcoming Queues</h3>
            <div class="upcoming-list">
              <?php if (count($queueData['upcomingQueues']) == 0): ?>
                <div class="upcoming-item">None</div>
              <?php else: ?>
                <?php foreach (array_slice($queueData['upcomingQueues'], 0, 3) as $q): ?>
                  <div class="upcoming-item">
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
    }, 10000);
  </script>
</body>
</html>
