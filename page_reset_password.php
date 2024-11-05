<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: page_forgotpass.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['password']) && isset($_POST['confirm_password'])) {
    require 'connection_customer.php';

    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $userId = $_SESSION['user_id'];

    if ($password === $confirm_password) {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Begin a transaction
            $pdo->beginTransaction();

            // Update the User table
            $sql = "UPDATE User SET uPass = :password, failed_attempts = 0, lock_until = NULL, uUpdDate = NOW() WHERE uUserID = :userId";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['password' => $hashedPassword, 'userId' => $userId]);

            // Insert into AuditUser table
            $sql = "INSERT INTO AuditUser (aAction, aUserID) VALUES (:action, :userId)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['action' => 'Password Changed', 'userId' => $userId]);

            // Commit the transaction
            $pdo->commit();

            // Clear the session data
            session_unset();
            session_destroy();

            header("Location: index.php");
            exit();
        } catch (Exception $e) {
            // Rollback the transaction if something failed
            $pdo->rollBack();
            echo "Error: " . $e->getMessage();
            exit();
        }
    } else {
        $alertMessage = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Error:</strong> Passwords do not match.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
    }
}
?>

<!doctype html>
<html lang="en" data-bs-theme="auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Courtlify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container {
            max-width: 500px;
            padding: 50px;
            margin-top: 10px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .form-floating {
            margin-bottom: 20px;
        }
        .form-check-label {
            font-weight: 400;
            font-size: 0.85em;
        }
    </style>
</head>
<body class="d-flex align-items-center py-4 bg-body-tertiary">
    <div class="container">
        <main class="form-signin w-100 m-auto">
            <?php if (isset($alertMessage)) echo $alertMessage; ?>
            <form action="page_reset_password.php" method="post">
                <img class="mb-4" src="logowide.png" alt="" width="100" height="80">
                <h1 class="h3 mb-3 fw-normal">Reset Password</h1>
                <div class="form-floating">
                    <input type="password" class="form-control" id="floatingPassword" name="password" placeholder="Password" required pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*\W).{8,}" title="Password must be at least 8 characters long and contain one uppercase letter, one lowercase letter, one digit, and one special character.">
                    <label for="floatingPassword">New Password</label>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" onclick="togglePasswordVisibility('floatingPassword')">
                        <label class="form-check-label" for="showPassword">SHOW PASSWORD</label>
                    </div>
                </div>
                <div class="form-floating">
                    <input type="password" class="form-control" id="floatingConfirmPassword" name="confirm_password" placeholder="Confirm Password" required pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*\W).{8,}" title="Password must be at least 8 characters long and contain one uppercase letter, one lowercase letter, one digit, and one special character.">
                    <label for="floatingConfirmPassword">Confirm Password</label>
                </div>
                <button class="btn btn-primary w-100 py-2" type="submit">Reset Password</button>
                <div class="mt-3 text-center">
                    <a href="index.php">Back to Login</a>
                </div>
            </form>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePasswordVisibility(passwordFieldId) {
            var passwordField = document.getElementById(passwordFieldId);
            if (passwordField.type === "password") {
                passwordField.type = "text";
            } else {
                passwordField.type = "password";
            }
        }
    </script>
</body>
</html>
