<?php
session_start();

// Check if session variables exist
if (!isset($_SESSION['firstName']) || !isset($_SESSION['lastName']) || !isset($_SESSION['phoneNumber']) || !isset($_SESSION['email']) || !isset($_SESSION['securityQuestion1']) || !isset($_SESSION['securityAnswer1']) || !isset($_SESSION['securityQuestion2']) || !isset($_SESSION['securityAnswer2'])) {
    // If session variables don't exist, redirect to registration page
    header("Location: page_register.php");
    exit;
}
?>

<!doctype html>
<html lang="en" data-bs-theme="auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Registration - Courtlify</title>
    
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
        max-width: 700px;
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
      .form-label{
        margin-top: -10px;
        font-size: 0.9em;
      }
    </style>
</head>
<body class="d-flex align-items-center py-4 bg-body-tertiary">
    <div class="container">
        <main class="form-signin w-100 m-auto">
            <form action="process_signup.php" method="post">
                <img class="mb-4" src="logowide.png" alt="" width="100" height="80">
                <h1 class="h3 mb-3 fw-normal">Confirm Registration Details</h1>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="firstName" name="firstName" placeholder="First Name" readonly value="<?php echo htmlspecialchars($_SESSION['firstName']); ?>">
                            <label for="firstName">First Name</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="lastName" name="lastName" placeholder="Last Name" readonly value="<?php echo htmlspecialchars($_SESSION['lastName']); ?>">
                            <label for="lastName">Last Name</label>
                        </div>
                    </div>
                </div>

                <div class="form-floating">
                    <input type="tel" class="form-control" id="phone" name="phone" placeholder="Phone Number" readonly value="<?php echo $_SESSION['phoneNumber']; ?>">
                    <label for="phone">Phone Number</label>
                </div>

                <div class="form-floating">
                    <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" readonly value="<?php echo htmlspecialchars($_SESSION['email']); ?>">
                    <label for="email">Email address</label>
                </div>

                <div class="form-floating">
                    <label for="securityQuestion1" class="form-label">Security Question 1</label>
                    <select class="form-select" id="securityQuestion1" name="securityQuestion1" disabled>
                        <option selected><?php echo htmlspecialchars($_SESSION['securityQuestion1']); ?></option>
                    </select>
                </div>

                <div class="form-floating">
                    <input type="text" class="form-control" id="securityAnswer1" name="securityAnswer1" placeholder="Answer" readonly value="<?php echo htmlspecialchars($_SESSION['securityAnswer1']); ?>">
                    <label for="securityAnswer1">Answer</label>
                </div>

                <div class="form-floating">
                    <label for="securityQuestion2" class="form-label">Security Question 2</label>
                    <select class="form-select" id="securityQuestion2" name="securityQuestion2" disabled>
                        <option selected><?php echo htmlspecialchars($_SESSION['securityQuestion2']); ?></option>
                    </select>
                </div>

                <div class="form-floating">
                    <input type="text" class="form-control" id="securityAnswer2" name="securityAnswer2" placeholder="Answer" readonly value="<?php echo htmlspecialchars($_SESSION['securityAnswer2']); ?>">
                    <label for="securityAnswer2">Answer</label>
                </div>

                <button class="btn btn-primary w-100 py-2" type="submit">Sign Up</button>
                <div class="mt-3 text-center">
                    <a href="page_register.php?firstName=<?php echo urlencode($_SESSION['firstName']); ?>&lastName=<?php echo urlencode($_SESSION['lastName']); ?>&phoneNumber=<?php echo urlencode($_SESSION['phoneNumber']); ?>&email=<?php echo urlencode($_SESSION['email']); ?>">Back to Sign Up Page</a>
                </div>
            </form>
        </main>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
