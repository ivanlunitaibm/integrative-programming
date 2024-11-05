<?php
session_start();

// Include database connection
require 'connection_customer.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email'])) {
    $email = $_POST['email'];

    // Check if the email exists
    try {
        $sql = "SELECT * FROM User WHERE uEmail = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Store email in session to use it in the next step
            $_SESSION['reset_email'] = $email;
            $_SESSION['user_id'] = $user['uUserID'];
            $_SESSION['security_question_1'] = $user['uSecurityQuestion1'];
            $_SESSION['security_question_2'] = $user['uSecurityQuestion2'];

            // Redirect to the security questions page
            header("Location: page_forgotpass_question.php");
            exit();
        } else {
            $alertMessage = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>Error:</strong> Account does not exist.
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>';
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
        exit();
    }
}
?>

<!doctype html>
<html lang="en" data-bs-theme="auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Courtlify</title>
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
    </style>
</head>
<body class="d-flex align-items-center py-4 bg-body-tertiary">
    <div class="container">
        <main class="form-signin w-100 m-auto">
            <?php if (isset($alertMessage)) echo $alertMessage; ?>
            <form action="page_forgotpass.php" method="post">
                <img class="mb-4" src="logowide.png" alt="" width="100" height="80">
                <h1 class="h3 mb-3 fw-normal">Reset Password</h1>
                <div class="form-floating">
                    <input type="email" class="form-control" id="floatingInput" name="email" placeholder="Email Address" required pattern="[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}$" title="Ex. name@gmail.com">
                    <label for="floatingInput">Email address</label>
                </div>
                <button class="btn btn-primary w-100 py-2" type="submit">Next</button>
                <div class="mt-3 text-center">
                    <a href="index.php">Back to login</a>
                </div>
            </form>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
