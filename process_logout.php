<?php
// Start the session
session_start();

// Include the database connection
require 'connection_customer.php';

// Check if user is logged in
if (isset($_SESSION['userId'])) {
    try {
        $userID = $_SESSION['userId'];
        $action = 'Logged out';

        // Insert record into AuditUser table
        $sql = "INSERT INTO audituser (aAction, aUserID) VALUES (:action, :userID)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'action' => $action,
            'userID' => $userID
        ]);
    } catch (PDOException $e) {
        // Handle error (optional)
        echo "Error: " . $e->getMessage();
    }
}

// Unset all session variables
$_SESSION = array();

// If the session was propagated using a cookie, delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session
session_destroy();

// Redirect to index.php
header("Location: index.php");
exit;
?>
