<?php
require_once 'connection_customer.php';

// Start session
session_start();

// Initialize variables
$alertMessage = '';

// Initialize form data variables
$firstName = '';
$lastName = '';
$phoneNumber = '';
$email = '';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $phoneNumber = $_POST['phoneNumber'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Check if phone number exists
    $stmt = $pdo->prepare("SELECT * FROM user WHERE uPhoneNumber = :phoneNumber");
    $stmt->execute(['phoneNumber' => $phoneNumber]);
    $phoneExists = $stmt->fetch();

    // Check if email exists
    $stmt = $pdo->prepare("SELECT * FROM user WHERE uEmail = :email");
    $stmt->execute(['email' => $email]);
    $emailExists = $stmt->fetch();

      // If phone number or email exists, set alert message
      if ($phoneExists || $emailExists) {
          $alertMessage = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                              <strong>Error:</strong> ' . ($phoneExists ? 'Phone number already exists.' : '') . ' ' . ($emailExists ? 'Email already exists.' : '') . '
                              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                          </div>';
    } else {
        // Store form data in session variables
        $_SESSION['firstName'] = $firstName;
        $_SESSION['lastName'] = $lastName;
        $_SESSION['phoneNumber'] = $phoneNumber;
        $_SESSION['email'] = $email;
        $_SESSION['password'] = $password;

        // Redirect to the next page
        header("Location: page_register_security.php");
        exit;
    }
}
?>

<!doctype html>
<html lang="en" data-bs-theme="auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Courtlify</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
      .bd-placeholder-img {
        font-size: 1.125rem;
        text-anchor: middle;
        -webkit-user-select: none;
        -moz-user-select: none;
        user-select: none;
      }

      @media (min-width: 768px) {
        .bd-placeholder-img-lg {
          font-size: 3.5rem;
        }
      }

      .b-example-divider {
        width: 100%;
        height: 3rem;
        background-color: rgba(0, 0, 0, .1);
        border: solid rgba(0, 0, 0, .15);
        border-width: 1px 0;
        box-shadow: inset 0 .5em 1.5em rgba(0, 0, 0, .1), inset 0 .125em .5em rgba(0, 0, 0, .15);
      }

      .b-example-vr {
        flex-shrink: 0;
        width: 1.5rem;
        height: 100vh;
      }

      .bi {
        vertical-align: -.125em;
        fill: currentColor;
      }

      .nav-scroller {
        position: relative;
        z-index: 2;
        height: 2.75rem;
        overflow-y: hidden;
      }

      .nav-scroller .nav {
        display: flex;
        flex-wrap: nowrap;
        padding-bottom: 1rem;
        margin-top: -1px;
        overflow-x: auto;
        text-align: center;
        white-space: nowrap;
        -webkit-overflow-scrolling: touch;
      }

      .btn-bd-primary {
        --bd-violet-bg: #712cf9;
        --bd-violet-rgb: 112.520718, 44.062154, 249.437846;

        --bs-btn-font-weight: 600;
        --bs-btn-color: var(--bs-white);
        --bs-btn-bg: var(--bd-violet-bg);
        --bs-btn-border-color: var(--bd-violet-bg);
        --bs-btn-hover-color: var(--bs-white);
        --bs-btn-hover-bg: #6528e0;
        --bs-btn-hover-border-color: #6528e0;
        --bs-btn-focus-shadow-rgb: var(--bd-violet-rgb);
        --bs-btn-active-color: var(--bs-btn-hover-color);
        --bs-btn-active-bg: #5a23c8;
        --bs-btn-active-border-color: #5a23c8;
      }

      .bd-mode-toggle {
        z-index: 1500;
      }

      .bd-mode-toggle .dropdown-menu .active .bi {
        display: block !important;
      }
      .container{
        max-width: 500px;
        padding: 50px;
        margin-top: 10px;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
      }
      .form-floating{
        margin-bottom: 20px;
      }
      .form-check-label{
        font-weight: 400;
        font-size: 0.85em;
      }
    </style>
</head>
<body class="d-flex align-items-center py-4 bg-body-tertiary">
    <div class="container">
        <?php echo $alertMessage; // Display alert message ?>
        <main class="form-signin w-100 m-auto">
              <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <img class="mb-4" src="logowide.png" alt="" width="100" height="80">
                <h1 class="h3 mb-3 fw-normal">Sign Up</h1>

                <div class="row">
                    <div class="col">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="firstName" name="firstName" placeholder="First Name" required pattern="^[A-Z][a-z]*( [A-Z][a-z]*){0,2}$" title="Ex. Juan Gabriel" value="<?php echo htmlspecialchars($_GET['firstName'] ?? ''); ?>">
                            <label for="firstName">First Name</label>
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="lastName" name="lastName" placeholder="Last Name" required pattern="^[A-Z][a-z]*( [A-Z][a-z]*){0,2}$" title="Ex. Dela Cruz" value="<?php echo htmlspecialchars($_GET['lastName'] ?? ''); ?>">
                            <label for="lastName">Last Name</label>
                        </div>
                    </div>
                </div>

                <div class="form-floating">
                    <input type="tel" class="form-control" id="phoneNumber" name="phoneNumber" placeholder="Phone Number" required pattern="^09\d{9}$" title="Phone number must be an 11-digit number starting with 09" value="<?php echo htmlspecialchars($_GET['phoneNumber'] ?? ''); ?>">
                    <label for="phoneNumber">Phone Number</label>
                </div>

                <div class="form-floating">
                    <input type="email" class="form-control" id="floatingInput" name="email" placeholder="name@example.com" required pattern="[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}$" title="Ex: name@gmail.com" value="<?php echo htmlspecialchars($_GET['email'] ?? ''); ?>">
                    <label for="floatingInput">Email address</label>
                </div>

                <div class="form-floating">
                    <input type="password" class="form-control" id="floatingPassword" name="password" placeholder="Password" required pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*\W).{8,}" title="Password must be at least 8 characters long and contain one uppercase letter, one lowercase letter, one digit, and one special character.">
                    <label for="floatingPassword">Password</label>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" onclick="togglePasswordVisibility('floatingPassword')">
                        <label class="form-check-label" for="showPassword">SHOW PASSWORD</label>
                    </div>
                </div>

                <button class="btn btn-primary w-100 py-2" type="submit">Next</button>
                <div class="mt-3 text-center">
                    <a href="index.php">Back to login</a>
                </div>
            </form>
        </main>
    </div>
    
    <!-- Bootstrap JS -->
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
