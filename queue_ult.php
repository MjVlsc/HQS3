<?php 
require('lib/conn.php');

// Get department_id from URL or default to 5
$departmentId = isset($_GET['department_id']) ? intval($_GET['department_id']) : 4;

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

// Get upcoming queues
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
");
$upcomingStmt->execute(['dept_id' => $departmentId]);
$allUpcomingQueues = $upcomingStmt->fetchAll();

// Get postponed queues
$postponedStmt = $conn->prepare("
    SELECT * FROM queues 
    WHERE status = 'postponed' AND department_id = :dept_id
    ORDER BY updated_at DESC
    LIMIT 5
");
$postponedStmt->execute(['dept_id' => $departmentId]);
$postponedQueues = $postponedStmt->fetchAll();

// Get pending queues
$pendingStmt = $conn->prepare("
    SELECT * FROM queues 
    WHERE status = 'pending' AND department_id = :dept_id
    ORDER BY updated_at DESC
    LIMIT 5
");
$pendingStmt->execute(['dept_id' => $departmentId]);
$pendingQueues = $pendingStmt->fetchAll();

// Handle queue actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['next_in_queue'])) {
      $nextQueue = $allUpcomingQueues[0] ?? null;
      if ($nextQueue) {
          // If there's a current queue, mark it as completed
          if ($currentQueue) {
              $conn->prepare("UPDATE queues SET status = 'completed' WHERE qid = :qid")
                   ->execute(['qid' => $currentQueue['qid']]);
          }
          // Make the next queue in-progress
          $conn->prepare("UPDATE queues SET status = 'in-progress' WHERE qid = :qid")
               ->execute(['qid' => $nextQueue['qid']]);
          header("Location: " . $_SERVER['PHP_SELF'] . "?department_id=" . $departmentId);
          exit;
      }
  } elseif (isset($_POST['postpone_queue'])) {
        if ($currentQueue) {
            $conn->prepare("UPDATE queues SET status = 'postponed' WHERE qid = :qid")
                 ->execute(['qid' => $currentQueue['qid']]);

            $nextQueue = $allUpcomingQueues[0] ?? null;
            if ($nextQueue) {
                $conn->prepare("UPDATE queues SET status = 'in-progress' WHERE qid = :qid")
                     ->execute(['qid' => $nextQueue['qid']]);
            }
            header("Location: " . $_SERVER['PHP_SELF'] . "?department_id=" . $departmentId);
            exit;
        }
    } elseif (isset($_POST['pending_queue'])) {
        if ($currentQueue) {
            $conn->prepare("UPDATE queues SET status = 'pending' WHERE qid = :qid")
                 ->execute(['qid' => $currentQueue['qid']]);

            $nextQueue = $allUpcomingQueues[0] ?? null;
            if ($nextQueue) {
                $conn->prepare("UPDATE queues SET status = 'in-progress' WHERE qid = :qid")
                     ->execute(['qid' => $nextQueue['qid']]);
            }
            header("Location: " . $_SERVER['PHP_SELF'] . "?department_id=" . $departmentId);
            exit;
        }
    } elseif (isset($_POST['reactivate'])) {
        $qid = $_POST['qid'];
        $conn->prepare("UPDATE queues SET status = 'waiting', announcement_count = 0, created_at = NOW() WHERE qid = :qid")
             ->execute(['qid' => $qid]);
        header("Location: " . $_SERVER['PHP_SELF'] . "?department_id=" . $departmentId);
        exit;
    } elseif (isset($_POST['complete_pending'])) {
        $qid = $_POST['qid'];
        $conn->prepare("UPDATE queues SET status = 'completed' WHERE qid = :qid")
             ->execute(['qid' => $qid]);
        header("Location: " . $_SERVER['PHP_SELF'] . "?department_id=" . $departmentId);
        exit;
    } elseif (isset($_POST['complete_current'])) {
      $qid = $_POST['qid'];
      $conn->prepare("UPDATE queues SET status = 'completed' WHERE qid = :qid")
           ->execute(['qid' => $qid]);
      header("Location: " . $_SERVER['PHP_SELF'] . "?department_id=" . $departmentId);
      exit;
  }
} 

// Handle announcement counting
if (isset($_GET['announced'])) {
    $qid = $_GET['announced'];
    $conn->prepare("UPDATE queues SET announcement_count = announcement_count + 1 WHERE qid = :qid")
         ->execute(['qid' => $qid]);
    exit;
}

$hasUpcomingQueues = count($allUpcomingQueues) > 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Queue - <?= htmlspecialchars($deptName) ?></title>
  <style>
    :root {
      --primary: #457b9d;
      --primary-dark: #1d3557;
      --secondary: #e63946;
      --warning: #FF9800;
      --danger: #f44336;
      --success: #4CAF50;
      --light-gray: #f5f5f5;
      --gray: #e0e0e0;
      --dark-gray: #757575;
      --white: #ffffff;
    }
    
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: var(--light-gray);
      padding: 20px;
      min-height: 100vh;
      display: grid;
      grid-template-columns: 250px 1fr 250px;
      gap: 20px;
      max-width: 1400px;
      margin: 0 auto;
    }
    
    /* Side panels */
    .side-panel {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }
    
    /* Main content */
    .main-content {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }
    
    /* Cards */
    .card {
      background: var(--white);
      border-radius: 12px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
      padding: 20px;
      transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .card:hover {
      box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
    }
    
    .card-header {
      font-size: 1.1rem;
      font-weight: 600;
      color: var(--primary-dark);
      margin-bottom: 15px;
      padding-bottom: 8px;
      border-bottom: 1px solid var(--gray);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .card-body {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    
    /* Current queue display */
    .current-queue {
      text-align: center;
      padding: 30px 20px;
    }
    
    .queue-number {
      font-size: 5rem;
      font-weight: 700;
      color: var(--secondary);
      margin: 10px 0;
      line-height: 1;
    }
    
    .queue-details {
      color: var(--dark-gray);
      font-size: 1.1rem;
      margin-bottom: 20px;
    }
    
    .queue-actions {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 10px;
      margin-top: 20px;
    }
    
    /* Buttons */
    .btn {
      padding: 10px 16px;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      transition: all 0.2s;
    }
    
    .btn-sm {
      padding: 6px 10px;
      font-size: 0.85rem;
    }
    
    .btn-primary {
      background: var(--primary);
      color: var(--white);
    }
    
    .btn-primary:hover {
      background: var(--primary-dark);
    }
    
    .btn-success {
      background: var(--success);
      color: var(--white);
    }
    
    .btn-success:hover {
      background: #388E3C;
    }
    
    .btn-warning {
      background: var(--warning);
      color: var(--white);
    }
    
    .btn-warning:hover {
      background: #F57C00;
    }
    
    .btn-danger {
      background: var(--danger);
      color: var(--white);
    }
    
    .btn-danger:hover {
      background: #D32F2F;
    }
    
    .btn-secondary {
      background: #6c757d;
      color: var(--white);
    }
    
    .btn-secondary:hover {
      background: #5a6268;
    }
    
    /* Queue items */
    .queue-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px;
      border-radius: 8px;
      background: var(--light-gray);
    }
    
    .queue-item-number {
      font-weight: 600;
      color: var(--primary-dark);
    }
    
    .queue-item-priority {
      font-size: 0.8rem;
      padding: 3px 8px;
      border-radius: 12px;
      background: var(--gray);
      color: var(--dark-gray);
    }
    
    .queue-item-actions {
      display: flex;
      gap: 6px;
    }
    
    /* Announcement count */
    .announcement-count {
      text-align: center;
      font-size: 0.9rem;
      color: var(--dark-gray);
      margin: 10px 0;
    }
    
    /* Empty state */
    .empty-state {
      color: var(--dark-gray);
      font-style: italic;
      text-align: center;
      padding: 20px 0;
    }
    
    /* Responsive adjustments */
    @media (max-width: 1024px) {
      body {
        grid-template-columns: 1fr;
      }
      
      .side-panel {
        grid-row: 2;
        flex-direction: row;
        flex-wrap: wrap;
      }
      
      .side-panel > .card {
        flex: 1 1 300px;
      }
    }
    
    @media (max-width: 768px) {
      .queue-actions {
        flex-direction: column;
        align-items: center;
      }
      
      .btn {
        width: 100%;
      }
    }
  </style>
</head>
<body>
  <!-- Left Side Panel -->
  <div class="side-panel">
    <div class="card">
      <div class="card-header">
        <span>Pending Results</span>
      </div>
      <div class="card-body">
        <?php foreach ($pendingQueues as $q): ?>
          <div class="queue-item">
            <span class="queue-item-number"><?= $q['queue_num'] ?></span>
            <div class="queue-item-actions">
              <button class="btn btn-secondary btn-sm" onclick="callPendingQueue('<?= $q['queue_num'] ?>')" title="Call patient back">
                📢 Call
              </button>
              <form method="post" style="display:inline;">
                <input type="hidden" name="qid" value="<?= $q['qid'] ?>">
                <button type="submit" name="complete_pending" class="btn btn-success btn-sm" title="Mark as completed">
                  ✓ Complete
                </button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (empty($pendingQueues)): ?>
          <div class="empty-state">No pending queues</div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <div class="card current-queue">
      <h1><?= htmlspecialchars($deptName) ?> Queue</h1>
      
      <?php if ($currentQueue): ?>
        <div class="queue-details">
          <div>Currently Serving</div>
          <div class="queue-number"><?= $currentQueue['queue_num'] ?></div>
          <div><?= htmlspecialchars($currentQueue['service_name']) ?></div>
          <div>Priority: <?= ucfirst($currentQueue['priority']) ?></div>
        </div>
        
        <div class="announcement-count">
          Announcements: <?= $currentQueue['announcement_count'] ?? 0 ?>/3
        </div>
        
        <div class="queue-actions">
  <?php if (($currentQueue['announcement_count'] ?? 0) < 3): ?>
    <button class="announce-btn btn btn-primary" onclick="announceCurrentQueue()">
      📢 Announce
    </button>
  <?php else: ?>
    <button class="announce-btn btn btn-primary" disabled style="opacity: 0.6; cursor: not-allowed;">
      📢 Announce (Max 3)
    </button>
  <?php endif; ?>
  
  <?php if (!$hasUpcomingQueues): ?>
    <form method="post">
      <input type="hidden" name="qid" value="<?= $currentQueue['qid'] ?>">
      <button type="submit" name="complete_current" class="btn btn-success">
        ✓ Mark as Completed
      </button>
    </form>
  <?php endif; ?>
  
  <form method="post" style="display:inline;">
    <button type="submit" name="pending_queue" class="btn btn-warning">
      ⏳ Mark as Pending
    </button>
  </form>
  
  <?php if (($currentQueue['announcement_count'] ?? 0) >= 3): ?>
    <form method="post" style="display:inline;">
      <button type="submit" name="postpone_queue" class="btn btn-danger">
        ↻ Postpone Queue
      </button>
    </form>
  <?php endif; ?>
</div>
      <?php else: ?>
        <div class="empty-state">No active queue</div>
      <?php endif; ?>
    </div>

    <div class="card">
      <div class="card-header">
        <span>Upcoming Queues</span>
        <?php if ($hasUpcomingQueues): ?>
          <form method="post" style="display:inline;">
            <button type="submit" name="next_in_queue" class="btn btn-primary btn-sm">
              Next in Queue →
            </button>
          </form>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <?php if (count($allUpcomingQueues) > 0): ?>
          <?php foreach (array_slice($allUpcomingQueues, 0, 5) as $q): ?>
            <div class="queue-item">
              <span class="queue-item-number"><?= $q['queue_num'] ?></span>
              <span class="queue-item-priority"><?= ucfirst($q['priority']) ?></span>
            </div>
          <?php endforeach; ?>
          <?php if (count($allUpcomingQueues) > 5): ?>
            <div style="text-align: center; color: var(--dark-gray); font-size: 0.9rem;">
              +<?= count($allUpcomingQueues) - 5 ?> more in queue
            </div>
          <?php endif; ?>
        <?php else: ?>
          <div class="empty-state">No upcoming queues</div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Right Side Panel -->
  <div class="side-panel">
    <div class="card">
      <div class="card-header">
        <span>Postponed</span>
      </div>
      <div class="card-body">
        <?php foreach ($postponedQueues as $q): ?>
          <div class="queue-item">
            <span class="queue-item-number"><?= $q['queue_num'] ?></span>
            <form method="post" style="display:inline;">
              <input type="hidden" name="qid" value="<?= $q['qid'] ?>">
              <button type="submit" name="reactivate" class="btn btn-secondary btn-sm" 
                      title="Return to end of waiting queue">
                ↻ Reactivate
              </button>
            </form>
          </div>
        <?php endforeach; ?>
        <?php if (empty($postponedQueues)): ?>
          <div class="empty-state">No postponed queues</div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <script>
// Global state for this tab
const tabState = {
  id: Math.random().toString(36).substring(2, 15),
  isSpeaking: false,
  lastAnnounceTime: 0,
  currentAnnouncementId: null
};

// Announcement queue system
const announcementSystem = {
  // Add announcement to queue
  addAnnouncement: function(queueNumber, departmentName, isPendingCall = false) {
    const now = Date.now();
    let message;
    
    if (isPendingCall) {
      message = `Patient with queue number ${queueNumber}, you may now go back to the ${departmentName} department.`;
    } else {
      message = `Patient with queue number ${queueNumber}, please proceed to the ${departmentName} department.`;
    }
    
    // Throttle rapid clicks (minimum 500ms between adds)
    if (now - tabState.lastAnnounceTime < 500) {
      console.log('Too fast - throttling');
      return;
    }
    tabState.lastAnnounceTime = now;

    // Disable the announce button immediately
    const announceBtn = document.querySelector('.announce-btn');
    if (announceBtn) {
      announceBtn.disabled = true;
      announceBtn.style.opacity = '0.7';
      announceBtn.style.cursor = 'not-allowed';
    }

    const announcement = {
      message: message,
      timestamp: now,
      tabId: tabState.id,
      queueNumber: queueNumber,
      departmentId: <?= $departmentId ?>,
      qid: '<?= $currentQueue['qid'] ?? '' ?>',
      isPendingCall: isPendingCall
    };

    // Get or initialize queue
    let queue = [];
    try {
      queue = JSON.parse(localStorage.getItem('announcementQueue') || '[]');
    } catch (e) {
      console.error('Queue parse error:', e);
    }

    queue.push(announcement);
    localStorage.setItem('announcementQueue', JSON.stringify(queue));
    
    // Start processing if not already running
    this.processQueue();
  },

  // Process the announcement queue
  processQueue: function() {
    // Check if another tab is already processing
    const currentLock = localStorage.getItem('announcementLock');
    if (currentLock && currentLock !== tabState.id) {
      setTimeout(() => this.processQueue(), 300);
      return;
    }

    // Get queue safely
    let queue = [];
    try {
      queue = JSON.parse(localStorage.getItem('announcementQueue') || '[]');
    } catch (e) {
      console.error('Queue parse error:', e);
      return;
    }

    if (queue.length === 0) {
      localStorage.removeItem('announcementLock');
      return;
    }

    // Take lock before processing
    localStorage.setItem('announcementLock', tabState.id);
    const nextAnnouncement = queue.shift();
    localStorage.setItem('announcementQueue', JSON.stringify(queue));

    this.speakAnnouncement(nextAnnouncement);
  },

  // Speak an announcement
  speakAnnouncement: function(announcement) {
    // Cancel any existing speech in this tab
    if (tabState.isSpeaking) {
      window.speechSynthesis.cancel();
    }

    tabState.isSpeaking = true;
    tabState.currentAnnouncementId = announcement.timestamp;
    
    const utterance = new SpeechSynthesisUtterance(announcement.message);
    utterance.lang = 'en-US';
    utterance.rate = 0.9;

    // Voice selection
    const voices = window.speechSynthesis.getVoices();
    const selectedVoice = voices.find(v => v.name.includes("Zira")) || 
                         voices.find(v => v.name.toLowerCase().includes("female")) ||
                         voices.find(v => v.lang === "en-US");
    
    if (selectedVoice) {
      utterance.voice = selectedVoice;
    }

    // Handle end of speech
    utterance.onend = () => {
      tabState.isSpeaking = false;
      localStorage.removeItem('announcementLock');
      
      // Update announcement count AFTER speech is complete (only for regular announcements)
      if (!announcement.isPendingCall) {
        fetch(`?announced=${announcement.qid}&department_id=${announcement.departmentId}`)
          .then(() => {
            // Refresh to show updated count after a short delay
            setTimeout(() => location.reload(), 300);
          });
      }
      
      // Re-enable the announce button
      const announceBtn = document.querySelector('.announce-btn');
      if (announceBtn) {
        announceBtn.disabled = false;
        announceBtn.style.opacity = '1';
        announceBtn.style.cursor = 'pointer';
      }
      
      setTimeout(() => this.processQueue(), 300);
    };

    // Handle errors
    utterance.onerror = (event) => {
      console.error('Announcement error:', event);
      tabState.isSpeaking = false;
      localStorage.removeItem('announcementLock');
      
      // Re-enable the announce button even on error
      const announceBtn = document.querySelector('.announce-btn');
      if (announceBtn) {
        announceBtn.disabled = false;
        announceBtn.style.opacity = '1';
        announceBtn.style.cursor = 'pointer';
      }
      
      setTimeout(() => this.processQueue(), 300);
    };

    window.speechSynthesis.speak(utterance);
  },

  // Initialize the system
  init: function() {
    // Clean up if tab closes
    window.addEventListener('beforeunload', () => {
      if (tabState.isSpeaking) {
        localStorage.removeItem('announcementLock');
      }
    });

    // Start processing any existing queue
    setTimeout(() => this.processQueue(), 1000);
  }
};

// Call back a pending queue
function callPendingQueue(queueNumber) {
  announcementSystem.addAnnouncement(
    queueNumber,
    "<?= addslashes($deptName) ?>",
    true // This is a pending call announcement
  );
}

// Button click handler for current queue
function announceCurrentQueue() {
  <?php if (isset($currentQueue) && $currentQueue): ?>
    // Check if button is already disabled
    const announceBtn = document.querySelector('.announce-btn');
    if (announceBtn && announceBtn.disabled) {
      return;
    }
    
    announcementSystem.addAnnouncement(
      "<?= $currentQueue['queue_num'] ?>", 
      "<?= addslashes($deptName) ?>"
    );
  <?php else: ?>
    alert("No current queue to announce!");
  <?php endif; ?>
}

// Initialize when voices are loaded
function initializeAnnouncements() {
  if (window.speechSynthesis.getVoices().length === 0) {
    window.speechSynthesis.onvoiceschanged = initializeAnnouncements;
    return;
  }
  
  announcementSystem.init();
  
  <?php if (isset($currentQueue) && $currentQueue): ?>
    // Auto-announce on page load 
    if (window.location.pathname.includes('queue_ult.php')) {
      const announcedKey = `announced_${<?= $currentQueue['queue_num'] ?>}_<?= $departmentId ?>`;
      if (!localStorage.getItem(announcedKey)) {
        setTimeout(() => {
          announcementSystem.addAnnouncement(
            "<?= $currentQueue['queue_num'] ?>", 
            "<?= addslashes($deptName) ?>"
          );
          localStorage.setItem(announcedKey, 'true');
        }, 1500);
      }
    }
  <?php endif; ?>
}

// Start initialization
window.addEventListener('load', () => {
  initializeAnnouncements();
});

// Auto-refresh every 10 seconds
setTimeout(() => location.reload(), 10000);
</script>
</body>
</html>