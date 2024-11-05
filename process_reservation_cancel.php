<?php
session_start();
if (!isset($_SESSION['userId'])) {
    header("Location: index.php");
    exit();
}

// Include the database connection
require 'connection_customer.php';

try {
    $userID = $_SESSION['userId'];
    $action = 'Cancelled transaction';

    // Insert record into AuditUser table
    $sql = "INSERT INTO audituser (aAction, aUserID) VALUES (:action, :userID)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'action' => $action,
        'userID' => $userID
    ]);

    // Redirect to a confirmation or appropriate page after cancellation
    header("Location: page_book_date.php?message=cancelled");
    exit();
} catch (PDOException $e) {
    // Handle error
    echo "Error: " . $e->getMessage();
}
?>
