<?php
require('lib/conn.php');

// Get all departments in associative array
$departments = $conn->query("SELECT * FROM departments")->fetchAll(PDO::FETCH_ASSOC);
$deptMap = [];
foreach ($departments as $dept) {
    $deptMap[$dept['dept_id']] = $dept['name'];
}

// Handle search parameters
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'created_at_desc';

// Build WHERE clause
$whereClause = '';
$params = [];

if (!empty($searchTerm)) {
    $whereClause .= " AND (q.queue_num LIKE :search 
                    OR s.service_name LIKE :search 
                    OR d.name LIKE :search 
                    OR q.status LIKE :search 
                    OR q.priority LIKE :search)";
    $params[':search'] = '%' . $searchTerm . '%';
}

if (!empty($dateFrom)) {
    $whereClause .= " AND q.created_at >= :date_from";
    $params[':date_from'] = $dateFrom . ' 00:00:00';
}

if (!empty($dateTo)) {
    $whereClause .= " AND q.created_at <= :date_to";
    $params[':date_to'] = $dateTo . ' 23:59:59';
}

// Remove leading AND if WHERE clause exists
if (!empty($whereClause)) {
    $whereClause = 'WHERE ' . substr($whereClause, 5);
}

// Handle sorting
$sortOptions = [
    'created_at_desc' => 'q.created_at DESC',
    'created_at_asc' => 'q.created_at ASC',
    'queue_num_desc' => 'q.queue_num DESC',
    'queue_num_asc' => 'q.queue_num ASC',
    'status_desc' => 'q.status DESC',
    'status_asc' => 'q.status ASC'
];

$orderBy = $sortOptions[$sortBy] ?? 'q.created_at DESC';

// Get all queues along with service names
$allQueuesSql = "SELECT q.*, s.service_name, d.name as department_name 
                 FROM queues q
                 LEFT JOIN services s ON q.service_name = s.service_name
                 LEFT JOIN departments d ON q.department_id = d.dept_id
                 $whereClause
                 ORDER BY $orderBy";

$stmt = $conn->prepare($allQueuesSql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$allQueues = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Hospital Queue Log</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    :root {
      --primary-color: #1d3557;
      --secondary-color: #457b9d;
      --light-color: #f1faee;
      --accent-color: #e63946;
    }
    
    body {
      background-color: #f8f9fa;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      padding: 20px;
    }
    
    .header-container {
      background-color: white;
      border-radius: 10px;
      padding: 20px;
      margin-bottom: 20px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .header-text {
      flex: 1;
      min-width: 300px;
    }
    
    .search-container {
      flex: 2;
      min-width: 300px;
    }
    
    .main-container {
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    h1 {
      color: var(--primary-color);
      font-weight: 600;
      margin-bottom: 10px;
    }
    
    .subtitle {
      color: #6c757d;
      font-size: 1.1rem;
      margin-bottom: 0;
    }
    
    .table-container {
      overflow-x: auto;
      border-radius: 8px;
      border: 1px solid #ececec;
    }
    
    .table {
      width: 100%;
      margin-bottom: 0;
      font-size: 0.95rem;
    }
    
    .table thead th {
      background-color: var(--primary-color);
      color: white;
      font-weight: 500;
      padding: 12px 15px;
      text-transform: uppercase;
      font-size: 0.85rem;
      letter-spacing: 0.5px;
      border: none;
    }
    
    .table tbody td {
      padding: 12px 15px;
      vertical-align: middle;
      border-top: 1px solid #f1f1f1;
    }
    
    .table tbody tr:nth-child(even) {
      background-color: #f9f9f9;
    }
    
    .table tbody tr:hover {
      background-color: var(--light-color);
    }
    
    .status-badge {
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 500;
      display: inline-block;
      min-width: 90px;
      text-align: center;
    }
    
    .status-waiting {
      background-color: #fff3cd;
      color: #856404;
    }
    
    .status-in-progress {
      background-color: #cce5ff;
      color: #004085;
    }
    
    .status-completed {
      background-color: #d4edda;
      color: #155724;
    }
    
    .priority-badge {
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 500;
      background-color: #e2e3e5;
      color: #383d41;
      display: inline-block;
      min-width: 90px;
      text-align: center;
    }
    
    .priority-emergency {
      background-color: #f8d7da;
      color: #721c24;
    }
    
    .priority-pwd, .priority-senior_citizen, .priority-pregnant {
      background-color: #fff3cd;
      color: #856404;
    }
    
    .timestamp {
      white-space: nowrap;
    }
    
    .no-queues {
      text-align: center;
      padding: 40px;
      color: #6c757d;
    }
    
    .search-form {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr auto;
      gap: 10px;
      align-items: end;
    }
    
    .search-input-group {
      display: flex;
    }
    
    .search-input {
      border-top-right-radius: 0;
      border-bottom-right-radius: 0;
      border-right: none;
    }
    
    .search-btn {
      border-top-left-radius: 0;
      border-bottom-left-radius: 0;
      background-color: var(--primary-color);
      color: white;
      border-color: var(--primary-color);
      height: 38px;
    }
    
    .search-btn:hover {
      background-color: var(--secondary-color);
      border-color: var(--secondary-color);
    }
    
    .filter-btn {
      background-color: white;
      color: var(--primary-color);
      border-color: var(--primary-color);
      height: 38px;
    }
    
    .filter-btn:hover {
      background-color: var(--light-color);
    }
    
    .date-input {
      height: 38px;
    }
    
    .sort-dropdown .dropdown-toggle::after {
      display: none;
    }
    
    .results-count {
      font-size: 0.9rem;
      color: #6c757d;
      margin-top: 10px;
      font-style: italic;
    }
    
    .sort-indicator {
      margin-left: 5px;
    }
    
    @media (max-width: 1200px) {
      .search-form {
        grid-template-columns: 1fr 1fr;
      }
    }
    
    @media (max-width: 768px) {
      body {
        padding: 10px;
      }
      
      .header-container {
        flex-direction: column;
        align-items: stretch;
        gap: 15px;
      }
      
      .search-container {
        justify-content: stretch;
      }
      
      .search-form {
        grid-template-columns: 1fr;
      }
      
      .main-container {
        padding: 15px;
      }
      
      .table thead th, 
      .table tbody td {
        padding: 8px 10px;
        font-size: 0.85rem;
      }
    }
  </style>
</head>
<body>
  <div class="header-container">
    <div class="header-text">
      <h1><i class="fas fa-list-alt me-2"></i>Queue Log</h1>
      <p class="subtitle">Complete history of all patient queues</p>  
    </div>
    
    <div class="search-container">
      <form method="GET" class="search-form">
        <div class="search-input-group">
          <input type="text" name="search" class="form-control search-input" placeholder="Search queues..." value="<?php echo htmlspecialchars($searchTerm); ?>">
          <button type="submit" class="btn search-btn"><i class="fas fa-search"></i></button>
        </div>
        
        <div>
          <label class="form-label small">From Date</label>
          <input type="date" name="date_from" class="form-control date-input" value="<?php echo htmlspecialchars($dateFrom); ?>">
        </div>
        
        <div>
          <label class="form-label small">To Date</label>
          <input type="date" name="date_to" class="form-control date-input" value="<?php echo htmlspecialchars($dateTo); ?>">
        </div>
        
        <div class="dropdown sort-dropdown">
          <button class="btn btn-outline-secondary filter-btn dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-sort me-1"></i>
            <?php 
              $sortLabels = [
                'created_at_desc' => 'Newest First',
                'created_at_asc' => 'Oldest First',
                'queue_num_desc' => 'Queue # (Z-A)',
                'queue_num_asc' => 'Queue # (A-Z)',
                'status_desc' => 'Status (Z-A)',
                'status_asc' => 'Status (A-Z)'
              ];
              echo $sortLabels[$sortBy];
            ?>
          </button>
          <ul class="dropdown-menu" aria-labelledby="sortDropdown">
            <li><h6 class="dropdown-header">Sort By</h6></li>
            <li><a class="dropdown-item <?php echo $sortBy === 'created_at_desc' ? 'active' : ''; ?>" href="?<?php echo buildQueryString(['sort' => 'created_at_desc']); ?>">Newest First</a></li>
            <li><a class="dropdown-item <?php echo $sortBy === 'created_at_asc' ? 'active' : ''; ?>" href="?<?php echo buildQueryString(['sort' => 'created_at_asc']); ?>">Oldest First</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item <?php echo $sortBy === 'queue_num_desc' ? 'active' : ''; ?>" href="?<?php echo buildQueryString(['sort' => 'queue_num_desc']); ?>">Queue # (Z-A)</a></li>
            <li><a class="dropdown-item <?php echo $sortBy === 'queue_num_asc' ? 'active' : ''; ?>" href="?<?php echo buildQueryString(['sort' => 'queue_num_asc']); ?>">Queue # (A-Z)</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item <?php echo $sortBy === 'status_desc' ? 'active' : ''; ?>" href="?<?php echo buildQueryString(['sort' => 'status_desc']); ?>">Status (Z-A)</a></li>
            <li><a class="dropdown-item <?php echo $sortBy === 'status_asc' ? 'active' : ''; ?>" href="?<?php echo buildQueryString(['sort' => 'status_asc']); ?>">Status (A-Z)</a></li>
          </ul>
        </div>
      </form>
    </div>
  </div>
  
  <div class="main-container">
    <?php if (!empty($searchTerm) || !empty($dateFrom) || !empty($dateTo)): ?>
      <div class="results-count">
        Found <?php echo count($allQueues); ?> result(s) 
        <?php if (!empty($searchTerm)): ?>for "<?php echo htmlspecialchars($searchTerm); ?>"<?php endif; ?>
        <?php if (!empty($dateFrom) || !empty($dateTo)): ?>
          between <?php echo !empty($dateFrom) ? htmlspecialchars($dateFrom) : 'the beginning'; ?> 
          and <?php echo !empty($dateTo) ? htmlspecialchars($dateTo) : 'now'; ?>
        <?php endif; ?>
        <?php if (count($allQueues) > 0): ?>
          <a href="?" class="ms-2 text-decoration-none"><i class="fas fa-times"></i> Clear filters</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
    
    <div class="table-container mt-3">
      <?php if (count($allQueues) > 0): ?>
      <table class="table">
        <thead>
          <tr>
            <th>
              <a href="?<?php echo buildQueryString(['sort' => $sortBy === 'queue_num_asc' ? 'queue_num_desc' : 'queue_num_asc']); ?>" class="text-white text-decoration-none">
                Queue #
                <?php if (strpos($sortBy, 'queue_num') === 0): ?>
                  <i class="fas fa-sort-<?php echo strpos($sortBy, '_asc') !== false ? 'up' : 'down'; ?> sort-indicator"></i>
                <?php endif; ?>
              </a>
            </th>
            <th>
              <a href="?<?php echo buildQueryString(['sort' => $sortBy === 'status_asc' ? 'status_desc' : 'status_asc']); ?>" class="text-white text-decoration-none">
                Status
                <?php if (strpos($sortBy, 'status') === 0): ?>
                  <i class="fas fa-sort-<?php echo strpos($sortBy, '_asc') !== false ? 'up' : 'down'; ?> sort-indicator"></i>
                <?php endif; ?>
              </a>
            </th>
            <th>Priority</th>
            <th>Service</th>
            <th>Department</th>
            <th>
              <a href="?<?php echo buildQueryString(['sort' => $sortBy === 'created_at_asc' ? 'created_at_desc' : 'created_at_asc']); ?>" class="text-white text-decoration-none">
                Date & Time
                <?php if (strpos($sortBy, 'created_at') === 0): ?>
                  <i class="fas fa-sort-<?php echo strpos($sortBy, '_asc') !== false ? 'up' : 'down'; ?> sort-indicator"></i>
                <?php endif; ?>
              </a>
            </th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($allQueues as $q): ?>
            <tr>
              <td><strong><?php echo htmlspecialchars($q['queue_num']); ?></strong></td>
              <td>
                <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $q['status'])); ?>">
                  <?php echo ucfirst($q['status']); ?>
                </span>
              </td>
              <td>
                <span class="priority-badge priority-<?php echo strtolower($q['priority']); ?>">
                  <?php echo ucwords(str_replace('_', ' ', $q['priority'])); ?>
                </span>
              </td>
              <td><?php echo htmlspecialchars($q['service_name']); ?></td>
              <td><?php echo htmlspecialchars($q['department_name'] ?? 'Unknown'); ?></td>
              <td class="timestamp"><?php echo date('M j, Y g:i A', strtotime($q['created_at'])); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
        <div class="no-queues">
          <i class="fas fa-inbox fa-3x mb-3" style="color: #dee2e6;"></i>
          <h4>No queues found</h4>
          <p><?php echo (!empty($searchTerm) || !empty($dateFrom) || !empty($dateTo)) ? 'No results match your filters.' : 'There are currently no queue records in the system'; ?></p>
          <?php if (!empty($searchTerm) || !empty($dateFrom) || !empty($dateTo)): ?>
            <a href="?" class="btn btn-sm btn-outline-primary mt-2">Show all queues</a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Helper function to build query string while preserving existing parameters
function buildQueryString($newParams = []) {
    $params = $_GET;
    foreach ($newParams as $key => $value) {
        $params[$key] = $value;
    }
    return http_build_query($params);
}
?>