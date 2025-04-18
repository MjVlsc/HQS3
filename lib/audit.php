<?php
// lib/audit.php
function logAction($conn, $actionType, $tableName, $recordId, $userId, $username, $role = null, $actionDetails = null) {
    // First, check if the role column exists in the audit_log table
    $columnExists = false;
    try {
        $checkStmt = $conn->prepare("SHOW COLUMNS FROM audit_log LIKE 'role'");
        $checkStmt->execute();
        $columnExists = ($checkStmt->rowCount() > 0);
    } catch (PDOException $e) {
        // If we can't check, assume column doesn't exist
        $columnExists = false;
    }

    // Prepare the SQL based on whether role column exists
    if ($columnExists) {
        $sql = "
            INSERT INTO audit_log 
            (action_type, table_name, record_id, user_id, username, role, action_details)
            VALUES (:action_type, :table_name, :record_id, :user_id, :username, :role, :action_details)
        ";
        $params = [
            ':action_type' => $actionType,
            ':table_name' => $tableName,
            ':record_id' => $recordId,
            ':user_id' => $userId,
            ':username' => $username,
            ':role' => $role,
            ':action_details' => $actionDetails
        ];
    } else {
        $sql = "
            INSERT INTO audit_log 
            (action_type, table_name, record_id, user_id, username, action_details)
            VALUES (:action_type, :table_name, :record_id, :user_id, :username, :action_details)
        ";
        $params = [
            ':action_type' => $actionType,
            ':table_name' => $tableName,
            ':record_id' => $recordId,
            ':user_id' => $userId,
            ':username' => $username,
            ':action_details' => $actionDetails
        ];
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    
    return $conn->lastInsertId();
}