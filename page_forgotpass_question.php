<?php
session_start();

if (!isset($_SESSION['reset_email']) || !isset($_SESSION['security_question_1']) || !isset($_SESSION['security_question_2'])) {
    header("Location: page_forgotpass.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['answer1']) && isset($_POST['answer2'])) {
    require 'connection_customer.php';

    $answer1 = $_POST['answer1'];
    $answer2 = $_POST['answer2'];
    $userId = $_SESSION['user_id'];

    try {
        // Fetch the hashed answers from the database
        $sql = "SELECT uSecurityAnswer1, uSecurityAnswer2 FROM User WHERE uUserID = :userId";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['userId' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($answer1, $user['uSecurityAnswer1']) && password_verify($answer2, $user['uSecurityAnswer2'])) {
            // Security answers matched, proceed to reset password
            header("Location: page_reset_password.php");
            exit();
        } else {
            $alertMessage = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>Error:</strong> Security answers do not match.
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
    <title>Security Questions - Courtlify</title>
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
            <form action="page_forgotpass_question.php" method="post">
                <img class="mb-4" src="logowide.png" alt="" width="100" height="80">
                <h1 class="h3 mb-3 fw-normal">Answer Security Questions</h1>
                <div class="form-floating">
                    <input type="password" class="form-control" id="floatingQuestion1" name="answer1" placeholder="Answer 1" required>
                    <label for="floatingQuestion1"><?php echo htmlspecialchars($_SESSION['security_question_1']); ?></label>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" onclick="togglePasswordVisibility('floatingQuestion1')">
                        <label class="form-check-label" for="showPassword1">SHOW ANSWER</label>
                    </div>
                </div>
                <div class="form-floating">
                    <input type="password" class="form-control" id="floatingQuestion2" name="answer2" placeholder="Answer 2" required>
                    <label for="floatingQuestion2"><?php echo htmlspecialchars($_SESSION['security_question_2']); ?></label>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" onclick="togglePasswordVisibility('floatingQuestion2')">
                        <label class="form-check-label" for="showPassword2">SHOW ANSWER</label>
                    </div>
                </div>
                <button class="btn btn-primary w-100 py-2" type="submit">Next</button>
                <div class="mt-3 text-center">
                    <a href="page_forgotpass.php">Back to Forgot Password</a>
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
