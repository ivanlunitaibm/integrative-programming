<?php
date_default_timezone_set('Asia/Manila');
// Start the session
session_start();

// Include the database connection file
include_once 'connection_customer.php';

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get email and password from the form
    $email = $_POST['email'];
    $password = $_POST['password'];

    try {
        // Prepare a SQL statement to fetch user details based on email
        $sql = "SELECT * FROM user WHERE uEmail = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if the user exists
        if ($user) {
            // Check if the account is locked
            $cooldown = $user['lock_until'];
            $failedAttempts = $user['failed_attempts'];
            $currentTime = time();
            $cooldownTime = strtotime($cooldown);

            if ($user['failed_attempts'] >= 3 && time() < strtotime($cooldown)) {
                // User is under cooldown
                echo "<script>alert('You have reached the maximum number of login attempts. Please try again later.'); window.location.href = 'index.php';</script>";
                exit();
            } 

            // Verify the password
            if (password_verify($password, $user['uPass'])) {
                // Reset failed attempts and lock_until
                $sql_reset_attempts = "UPDATE user SET failed_attempts = 0, lock_until = NULL WHERE uEmail = :email";
                $stmt_reset_attempts = $pdo->prepare($sql_reset_attempts);
                $stmt_reset_attempts->bindParam(':email', $email);
                $stmt_reset_attempts->execute();

                // Store user details in session variables
                $_SESSION['userId'] = $user['uUserID'];
                $_SESSION['uLevel'] = $user['uLevel'];

                // Insert record into audituser table
                $auditAction = "Logged in";
                $auditUserID = $user['uUserID'];
                $sql_audit = "INSERT INTO audituser (aAction, aUserID) VALUES (:action, :userID)";
                $stmt_audit = $pdo->prepare($sql_audit);
                $stmt_audit->bindParam(':action', $auditAction);
                $stmt_audit->bindParam(':userID', $auditUserID);
                $stmt_audit->execute();

                // Redirect based on uLevel
                if ($_SESSION['uLevel'] == 1) {
                    // Redirect to admin_dashboard.php
                    header("Location: admin_statistics.php");
                    exit;
                } elseif ($_SESSION['uLevel'] == 2) {
                    // Redirect to cust_landingpage.php
                    header("Location: cust_landingpage.php");
                    exit();
                }
            } else {
                // Increment failed attempts and update last_failed_attempt column
                $failedAttempts = $user['failed_attempts'] + 1;
                $sql_update_attempts = "UPDATE user SET failed_attempts = :failedAttempts, last_failed_attempt = CURRENT_TIMESTAMP WHERE uEmail = :email";
                $stmt_update_attempts = $pdo->prepare($sql_update_attempts);
                $stmt_update_attempts->bindParam(':failedAttempts', $failedAttempts);
                $stmt_update_attempts->bindParam(':email', $email);
                $stmt_update_attempts->execute();
                
                if ($user['failed_attempts'] >= 3) {
                    // Set cooldown time (2 minutes after the last_failed_attempt)
                    $sql_get_last_failed_attempt = "SELECT last_failed_attempt FROM user WHERE uEmail = :email";
                    $stmt_get_last_failed_attempt = $pdo->prepare($sql_get_last_failed_attempt);
                    $stmt_get_last_failed_attempt->bindParam(':email', $email);
                    $stmt_get_last_failed_attempt->execute();
                    $last_failed_attempt = $stmt_get_last_failed_attempt->fetchColumn();

                    // Parse the last failed attempt time
                    $last_failed_attempt_time = strtotime($last_failed_attempt);

                    // Calculate the cooldown time as 2 minutes after the last failed attempt
                    $cooldownTime = date('Y-m-d H:i:s', $last_failed_attempt_time + 120);

                    $sql_update_lock = "UPDATE user SET lock_until = :lockTime WHERE uEmail = :email";
                    $stmt_update_lock = $pdo->prepare($sql_update_lock);
                    $stmt_update_lock->bindParam(':lockTime', $cooldownTime);
                    $stmt_update_lock->bindParam(':email', $email);
                    $stmt_update_lock->execute();

                     // Insert record into audituser table for login attempt failure
                     $auditAction = "Log in attempt failed";
                     $sql_audit_failure = "INSERT INTO audituser (aAction, aUserID) VALUES (:action, :userID)";
                     $stmt_audit_failure = $pdo->prepare($sql_audit_failure);
                     $stmt_audit_failure->bindParam(':action', $auditAction);
                     $stmt_audit_failure->bindParam(':userID', $user['uUserID']);
                     $stmt_audit_failure->execute();

                    // Show error message with formatted cooldown time using JavaScript alert
                    $errorMessage = "You have reached the maximum number of login attempts. Please try again later.";
                    echo "<script>alert('$errorMessage'); window.location.href = 'index.php';</script>";
                    exit();
                }

                // Show error message using JavaScript alert
                $errorMessage = "Incorrect password!";
                echo "<script>alert('$errorMessage'); window.location.href = 'index.php';</script>";
                exit();
            }
        } else {
            // If user does not exist, redirect back to login page with an error message
            echo "<script>alert('User does not exist.'); window.location.href = 'index.php';</script>";
            exit();
        }
    } catch (PDOException $e) {
        // Handle database errors
        echo "Error: " . $e->getMessage();
        exit();
    }
} else {
    // If the form is not submitted, redirect back to login page
    header("Location: index.php");
    exit();
}
?>
