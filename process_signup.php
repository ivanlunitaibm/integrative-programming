<?php
require_once 'connection_customer.php';
session_start();

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data from session variables
    $firstName = $_SESSION['firstName'];
    $lastName = $_SESSION['lastName'];
    $phoneNumber = $_SESSION['phoneNumber'];
    $email = $_SESSION['email'];
    $hashedpass = password_hash($_SESSION['password'], PASSWORD_DEFAULT); // Hash password
    $securityQuestion1 = $_SESSION['securityQuestion1'];
    $securityAnswer1 = password_hash($_SESSION['securityAnswer1'], PASSWORD_DEFAULT); // Hash security answer 1
    $securityQuestion2 = $_SESSION['securityQuestion2'];
    $securityAnswer2 = password_hash($_SESSION['securityAnswer2'], PASSWORD_DEFAULT); // Hash security answer 2

    try {
        // Start a transaction
        $pdo->beginTransaction();

        // Prepare INSERT query for user
        $sql = "INSERT INTO user (uFName, uLName, uPhoneNumber, uEmail, uPass, uSecurityQuestion1, uSecurityAnswer1, uSecurityQuestion2, uSecurityAnswer2, uLevel)
        VALUES (:firstName, :lastName, :phoneNumber, :email, :hashedpass, :securityQuestion1, :securityAnswer1, :securityQuestion2, :securityAnswer2, 2)";
        $stmt = $pdo->prepare($sql);

        // Bind parameters
        $stmt->bindParam(':firstName', $firstName);
        $stmt->bindParam(':lastName', $lastName);
        $stmt->bindParam(':phoneNumber', $phoneNumber);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':hashedpass', $hashedpass);
        $stmt->bindParam(':securityQuestion1', $securityQuestion1);
        $stmt->bindParam(':securityAnswer1', $securityAnswer1);
        $stmt->bindParam(':securityQuestion2', $securityQuestion2);
        $stmt->bindParam(':securityAnswer2', $securityAnswer2);

        // Execute statement
        if ($stmt->execute()) {
            // Get the user ID of the newly created user
            $userID = $pdo->lastInsertId();

            // Prepare INSERT query for audit
            $auditSql = "INSERT INTO AuditUser (aAction, aUserID) VALUES ('Account created', :userID)";
            $auditStmt = $pdo->prepare($auditSql);
            $auditStmt->bindParam(':userID', $userID);

            // Execute the audit statement
            if ($auditStmt->execute()) {
                // Commit the transaction
                $pdo->commit();
                // Registration successful
                echo '<script type="text/javascript">alert("Registration successful!"); window.location.href = "index.php";</script>';
                // Destroy session to clear form data
                session_destroy();
            } else {
                // Rollback the transaction if audit insertion fails
                $pdo->rollBack();
                // Registration failed
                echo '<div class="alert alert-danger" role="alert">
                        Error: ' . htmlspecialchars($auditStmt->errorInfo()[2]) . '
                      </div>';
            }
        } else {
            // Rollback the transaction if user insertion fails
            $pdo->rollBack();
            // Registration failed
            echo '<div class="alert alert-danger" role="alert">
                    Error: ' . htmlspecialchars($stmt->errorInfo()[2]) . '
                  </div>';
        }
    } catch (Exception $e) {
        // Rollback the transaction on any error
        $pdo->rollBack();
        echo "Error: " . $e->getMessage();
    }
} else {
    // If the form is not submitted, redirect to signup page
    header("Location: page_register.php");
    exit;
}
?>
