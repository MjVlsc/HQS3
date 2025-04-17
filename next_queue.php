<?php
require('lib/conn.php');

// Get the current queue ID and department ID from the form
$currentQueueId = isset($_POST['current_qid']) ? intval($_POST['current_qid']) : null;
$departmentId = isset($_POST['department_id']) ? intval($_POST['department_id']) : null;

// Validate input
if (!$currentQueueId || !$departmentId) {
    die("Missing required data (current_qid or department_id).");
}

// Update the current queue to "completed"
$updateSql = "UPDATE queues SET status = 'in-progress' WHERE qid = :qid";
$updateStmt = $conn->prepare($updateSql);
$updateStmt->execute(['qid' => $currentQueueId]);

// Get the next queue to be in progress
$nextQueueSql = "SELECT * FROM queues WHERE status = 'waiting' AND department_id = :dept_id ORDER BY created_at ASC LIMIT 1";
$nextQueueStmt = $conn->prepare($nextQueueSql);
$nextQueueStmt->execute(['dept_id' => $departmentId]);
$nextQueue = $nextQueueStmt->fetch();

// If there is a next queue, update it to 'in-progress'
if ($nextQueue) {
    $updateNextSql = "UPDATE queues SET status = 'in-progress' WHERE qid = :qid";
    $updateNextStmt = $conn->prepare($updateNextSql);
    $updateNextStmt->execute(['qid' => $nextQueue['qid']]);
}

// Redirect back to the main page
header("Location: queue_lab.php?department_id=$departmentId");
exit;
?>
