<?php
require('lib/conn.php');

// Get all departments in associative array
$departments = $conn->query("SELECT * FROM departments")->fetchAll(PDO::FETCH_ASSOC);
$deptMap = [];
foreach ($departments as $dept) {
    $deptMap[$dept['dept_id']] = $dept['name'];
}

// Get all queues along with service names
$allQueuesSql = "SELECT q.*, s.service_name FROM queues q
                 LEFT JOIN services s ON q.service_name = s.service_name
                 ORDER BY q.created_at ASC";
$allQueues = $conn->query($allQueuesSql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Hospital Queue</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"> <!-- Font Awesome -->
  <style>
  body {
    background: #f1f1f1;
    padding: 40px;
  }

  .container {
    max-width: 100%;
    background: #fff;
    padding: 30px 40px;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    overflow-x: auto;
    margin-top: 30px;
  }

  h2 {
    font-family: Arial;
    font-size: 45px;
    font-weight: bold;
    color: #1d3557;
    text-align: start;
  }

  h4 {
    font-family: Arial;
    font-size: 35px;
    color: #333;
    margin-bottom: 30px;
  }

  table {
    width: 100%;
    margin-bottom: 30px;
    font-size: 16px;
    border-collapse: collapse;
  }

  .table th,
  .table td {
    padding: 14px 12px;
    vertical-align: middle;
    text-align: center;
    transition: background-color 0.3s ease; /* Smooth transition */
  }

  .table th {
    background-color: #457b9d;
    color: white;
  }

  .table-striped tbody tr:nth-of-type(odd) {
    background-color: #f9f9f9;
  }

  /* Hover effect on table rows */
  .table tbody tr:hover {
    background-color: #e9f5ff;
  }

  /* Highlight table cell on hover */
  .table td:hover {
    background-color: #d1e7fd;
    cursor: pointer;
  }

  .btn-sm {
    padding: 6px 10px;
    font-size: 14px;
  }

  .modal-content {
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }

  /* Hover effect on buttons */
  .btn:hover {
    background-color: #335c74;
    border-color: #335c74;
  }

  /* Tooltip style for buttons */
  .btn[data-bs-toggle="tooltip"] {
    position: relative;
    cursor: pointer;
  }

  /* Modal enhancements */
  .modal-header {
    background-color: #dc3545;
    color: white;
  }

  @media (max-width: 768px) {
    table {
      font-size: 14px;
    }
    .table th, .table td {
      padding: 10px;
    }
  }
</style>

</head>

<body>
<!-- <h2 class="text-center">Hospital Queueing Display</h2> -->

<h4><center>ALL QUEUES</center></h4>

<div class="container">


  <?php if (count($allQueues) > 0): ?>
  <table class="table table-bordered table-striped">
    <thead>
      <tr>
        <th>Queue Number</th>
        <th>Status</th>
        <th>Priority</th>
        <th>Service</th>
        <th>Department</th>
        <th>Date & Time</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($allQueues as $q): ?>
        <tr>
          <td>Q-<?php echo str_pad($q['queue_num'], 3, '0', STR_PAD_LEFT); ?></td>
          <td><?php echo ucfirst($q['status']); ?></td>
          <td><?php echo ucfirst($q['priority']); ?></td>
          <td><?php echo htmlspecialchars($q['service_name']); ?></td>
          <td><?php echo $deptMap[$q['department_id']] ?? 'Unknown'; ?></td>
          <td><?php echo $q['created_at']; ?></td>
          <td>
            <button class="btn btn-sm btn-danger" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete Queue" onclick="confirmDelete(<?php echo $q['qid']; ?>, 'Q-<?php echo str_pad($q['queue_num'], 3, '0', STR_PAD_LEFT); ?>')">
              <i class="fas fa-trash"></i>
            </button>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php else: ?>
    <p class="text-muted">No queues found.</p>
  <?php endif; ?>

</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to delete <strong id="queueIdentifier">this queue</strong>? This action cannot be undone.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Yes, Delete</a>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
  function confirmDelete(qid, queueNumber) {
    document.getElementById('confirmDeleteBtn').href = 'delete_queue.php?qid=' + qid;
    document.getElementById('queueIdentifier').innerText = queueNumber;
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    deleteModal.show();
  }
  
  // Initialize tooltips
  document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl);
    });
  });
</script>

</body>
</html>