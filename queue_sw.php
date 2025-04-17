<?php 
require('lib/conn.php');

// Get department_id from URL or default to 5
$departmentId = isset($_GET['department_id']) ? intval($_GET['department_id']) : 14;

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
    body {
      font-family: Arial, sans-serif;
      background: #f9f9f9;
      padding: 20px;
      display: flex;
      justify-content: center;
      gap: 20px;
      flex-wrap: wrap;
    }
    .next-button {
      margin-top: 15px;
      padding: 10px 20px;
      background-color: #457b9d;
      color: #fff;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: bold;
    }

    .next-button:hover {
      background-color: #1d3557;
    }
    .queue-box {
      background: #fff;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      width: 600px;
      max-width: 100%;
    }
    .pending-container, .postponed-container {
      width: 200px;
    }
    
    .pending-box, .postponed-box {
      background: #fff8f8;
      padding: 15px;
      border-radius: 10px;
      box-shadow: 0 0 5px rgba(0,0,0,0.05);
      margin-bottom: 15px;
    }
    
    .pending-title, .postponed-title {
      font-size: 16px;
      margin-bottom: 10px;
      color: #d32f2f;
      font-weight: bold;
    }
    
    .pending-item, .postponed-item {
      background: #ffebee;
      padding: 8px;
      margin-bottom: 8px;
      border-radius: 6px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .pending-number, .postponed-number {
      font-weight: bold;
      color: #d32f2f;
    }
    
    .pending-actions {
      display: flex;
      gap: 5px;
    }
    
    .pending-call-btn, .pending-complete-btn {
      background: none;
      border: none;
      cursor: pointer;
      font-size: 12px;
    }
    
    .pending-call-btn {
      color: #1976d2;
    }
    
    .pending-complete-btn {
      color: #4CAF50;
    }
    
    .reactivate-btn {
      background: none;
      border: none;
      color: #1976d2;
      cursor: pointer;
      font-size: 12px;
    }
    
    .current-number {
      font-size: 60px;
      color: #e63946;
      margin: 10px 0;
    }
    
    .announce-btn {
      background:rgb(173, 166, 65);
      color: white;
      border: none;
      padding: 10px 15px;
      border-radius: 5px;
      cursor: pointer;
      margin: 10px 5px;
    }
    
    .next-btn {
      background: #2196F3;
      color: white;
      border: none;
      padding: 10px 15px;
      border-radius: 5px;
      cursor: pointer;
      margin: 10px 5px;
    }
    
    .postpone-btn {
      background: #f44336;
      color: white;
      border: none;
      padding: 10px 15px;
      border-radius: 5px;
      cursor: pointer;
      margin: 10px 5px;
    }
    
    .pending-btn {
      background: #FF9800;
      color: white;
      border: none;
      padding: 10px 15px;
      border-radius: 5px;
      cursor: pointer;
      margin: 10px 5px;
    }
    
    .announce-count {
      font-size: 14px;
      color: #666;
      margin: 5px 0;
    }
    
    .button-group {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin: 15px 0;
    }
    body {
  font-family: Arial, sans-serif;
  background: #f9f9f9;
  padding: 20px;
  display: flex;
  justify-content: center;
  gap: 20px;
  flex-wrap: wrap;
  text-align: center; /* This will center all text by default */
}

/* Specific centering for elements that might need it */
.queue-box, 
.pending-box, 
.postponed-box,
.current-number,
.current,
.announce-count,
.button-group {
  text-align: center;
}

/* Center the upcoming queue items */
.queue-box > div {
  display: flex;
  flex-direction: column;
  align-items: center;
}

/* Center the pending and postponed items */
.pending-item, .postponed-item {
  justify-content: center;
  gap: 10px;
}

/* Center the action buttons */
.pending-actions {
  justify-content: center;
}
.complete-button {
  margin-top: 15px;
  padding: 10px 20px;
  background-color: #4CAF50;
  color: #fff;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: bold;
}

.complete-button:hover {
  background-color: #388E3C;
}
  </style>
</head>
<body>
  <div class="pending-container">
    <div class="pending-box">
      <div class="pending-title">Pending Results</div>
      <?php foreach ($pendingQueues as $q): ?>
        <div class="pending-item">
          <span class="pending-number"><?= $q['queue_num'] ?></span>
          <div class="pending-actions">
            <button class="pending-call-btn" onclick="callPendingQueue('<?= $q['queue_num'] ?>')" title="Call patient back">
              📢
            </button>
            <form method="post" style="display:inline;">
              <input type="hidden" name="qid" value="<?= $q['qid'] ?>">
              <button type="submit" name="complete_pending" class="pending-complete-btn" title="Mark as completed">
                ✓
              </button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (empty($pendingQueues)): ?>
        <div style="color:#888; font-size:14px;">None</div>
      <?php endif; ?>
    </div>
  </div>

 
  <div class="queue-box">
    <h1><?= htmlspecialchars($deptName) ?> Queue</h1>
    
    <?php if ($currentQueue): ?>
      <div class="current">In-progress</div>
      <div class="current-number"><?= $currentQueue['queue_num'] ?></div>
      <div>
        <?= htmlspecialchars($currentQueue['service_name']) ?> |
        Priority: <?= ucfirst($currentQueue['priority']) ?>
      </div>
      <div class="announce-count">
        Announcements: <?= $currentQueue['announcement_count'] ?? 0 ?>/3
      </div>
      
      <div class="button-group">
        <button class="announce-btn" onclick="announceCurrentQueue()">
          Announce
        </button>
        
        <?php if (!$hasUpcomingQueues): ?>
          <form method="post">
            <input type="hidden" name="qid" value="<?= $currentQueue['qid'] ?>">
            <button type="submit" name="complete_current" class="complete-button">
              Mark as Completed
            </button>
          </form>
        <?php endif; ?>
        
        <form method="post" style="display:inline;">
          <button type="submit" name="pending_queue" class="pending-btn">
            Mark as Pending
          </button>
        </form>
        
        <?php if (($currentQueue['announcement_count'] ?? 0) >= 3): ?>
          <form method="post" id="postponeForm" style="display:inline;">
            <button type="submit" name="postpone_queue" class="postpone-btn">
              Postpone Queue
            </button>
          </form>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div style="color:#888; font-style:italic;">No active queue</div>
    <?php endif; ?>

    <?php if ($hasUpcomingQueues): ?>
      <form method="post" style="margin: 20px 0;">
        <button type="submit" name="next_in_queue" class="next-button">Next in Queue</button>
      </form>
    <?php endif; ?>

    <h3>Upcoming</h3>
    <div>
      <?php if (count($allUpcomingQueues) > 0): ?>
        <?php foreach (array_slice($allUpcomingQueues, 0, 5) as $q): ?>
          <div><?= $q['queue_num'] ?> (<?= ucfirst($q['priority']) ?>)</div>
        <?php endforeach; ?>
      <?php else: ?>
        <div style="color:#888; font-style:italic;">No upcoming queues</div>
      <?php endif; ?>
    </div>
  </div>

 <div class="postponed-container">
    <div class="postponed-box">
      <div class="postponed-title">Postponed</div>
      <?php foreach ($postponedQueues as $q): ?>
        <div class="postponed-item">
          <span class="postponed-number"><?= $q['queue_num'] ?></span>
          <form method="post" style="display:inline;">
            <input type="hidden" name="qid" value="<?= $q['qid'] ?>">
            <button type="submit" name="reactivate" class="reactivate-btn" 
                    title="Return to end of waiting queue">
              ↻
            </button>
          </form>
        </div>
      <?php endforeach; ?>
      <?php if (empty($postponedQueues)): ?>
        <div style="color:#888; font-size:14px;">None</div>
      <?php endif; ?>
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
    // Auto-announce on page load for X-ray department
    if (window.location.pathname.includes('queue_sw.php')) {
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