<?php
// audit_logs.php
session_start();
require('lib/conn.php');

// Check admin privileges
if ($_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

// Get logs
$logs = $conn->query("
    SELECT * FROM audit_log 
    ORDER BY created_at DESC
    LIMIT 100
")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Audit Logs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2980b9;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
            --success-color: #2ecc71;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .header {
            background-color: #1d3557;
            color: white;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .container {
            max-width: 1200px;
            margin-bottom: 3rem;
        }
        
        h2 {
            color: var(--dark-color);
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 3px solid var(--primary-color);
            display: inline-block;
        }
        
        .table {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }
        
        .table th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
        }
        
        .table td {
            vertical-align: middle;
        }
        
        .action-details {
            max-width: 300px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        
        .badge-action {
            background-color: var(--primary-color);
            color: white;
            padding: 0.35em 0.65em;
            border-radius: 50rem;
            font-size: 0.85em;
            font-weight: 600;
        }
        
        .badge-add {
            background-color: var(--success-color);
        }
        
        .badge-update {
            background-color: var(--secondary-color);
        }
        
        .badge-delete {
            background-color: var(--accent-color);
        }
        
        .timestamp {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .user-cell {
            font-weight: 500;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 0 15px;
            }
            
            .table-responsive {
                border-radius: 8px;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="m-0"><i class="fas fa-clipboard-list me-2"></i>Audit Logs</h1>
                <a href="<?php echo ($_SESSION['role'] == 'Admin') ? 'queue_display_admin.php' : 'queue_display.php'; ?>" class="btn btn-outline-light">
                    <i class="fas fa-arrow-left me-2"></i>Back to Queue
                </a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Table</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td class="timestamp">
                            <i class="far fa-clock me-1"></i>
                            <?= date('M j, Y g:i A', strtotime($log['created_at'])) ?>
                        </td>
                        <td class="user-cell">
                            <i class="fas fa-user me-1"></i>
                            <?= htmlspecialchars($log['username']) ?> |  <?= htmlspecialchars($log['role']) ?>
                        </td>
                        <td>
                            <?php 
                            $badgeClass = 'badge-action';
                            if (strpos($log['action_type'], 'ADD') !== false) $badgeClass .= ' badge-add';
                            elseif (strpos($log['action_type'], 'UPDATE') !== false) $badgeClass .= ' badge-update';
                            elseif (strpos($log['action_type'], 'DELETE') !== false) $badgeClass .= ' badge-delete';
                            ?>
                            <span class="<?= $badgeClass ?>">
                                <?= htmlspecialchars($log['action_type']) ?>
                            </span>
                        </td>
                        <td>
                            <?= htmlspecialchars($log['table_name']) ?>
                        </td>
                        <td class="action-details">
                            <?php 
                            $details = json_decode($log['action_details'], true);
                            if ($details) {
                                // Convert array to readable sentence
                                $sentence = '';
                                foreach ($details as $key => $value) {
                                    $sentence .= ucfirst(str_replace('_', ' ', $key)) . ": " . htmlspecialchars($value) . ", ";
                                }
                                echo rtrim($sentence, ', ');
                            } else {
                                echo htmlspecialchars($log['action_details'] ?? 'No details');
                            }
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>