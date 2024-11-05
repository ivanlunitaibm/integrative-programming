<?php
session_start(); // Start the session to access session variables

// Redirect to login page if session variables are not set
if (!isset($_SESSION['firstName']) || !isset($_SESSION['lastName']) || !isset($_SESSION['phoneNumber']) || !isset($_SESSION['email']) || !isset($_SESSION['password'])) {
    header("Location: page_register.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $_SESSION['securityQuestion1'] = $_POST['securityQuestion1'];
  $_SESSION['securityAnswer1'] = $_POST['securityAnswer1'];
  $_SESSION['securityQuestion2'] = $_POST['securityQuestion2']; // Fix here
  $_SESSION['securityAnswer2'] = $_POST['securityAnswer2']; // Fix here

  header("Location: page_register_signup.php");
  exit;
}
?>



<!doctype html>
<html lang="en" data-bs-theme="auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Questions - Courtlify</title>
    
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
        <main class="form-signin w-100 m-auto">
          <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <img class="mb-4" src="logowide.png" alt="" width="100" height="80">
                <h1 class="h3 mb-3 fw-normal">Security Questions</h1>

                <div class="mb-3">
                    <label for="securityQuestion1" class="form-label">Security Question 1</label>
                    <select class="form-select" id="securityQuestion1" name="securityQuestion1" required>
                        <option value="" selected disabled>Select a question...</option>
                        <option value="What is your mother's maiden name?">What is your mother's maiden name?</option>
                        <option value="What is the name of your first pet?">What is the name of your first pet?</option>
                        <option value="What city were you born in?">What city were you born in?</option>
                        <option value="What is your favorite book?">What is your favorite book?</option>
                        <option value="What is the name of your best childhood friend?">What is the name of your best childhood friend?</option>
                    </select>
                </div>

                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="securityAnswer1" name="securityAnswer1" placeholder="Answer" required>
                    <label for="securityAnswer1">Answer</label>
                </div>

                <div class="mb-3">
                    <label for="securityQuestion2" class="form-label">Security Question 2</label>
                    <select class="form-select" id="securityQuestion2" name="securityQuestion2" required>
                        <option value="" selected disabled>Select a question...</option>
                        <option value="What was the name of your first school?">What was the name of your first school?</option>
                        <option value="What was your childhood nickname?">What was your childhood nickname?</option>
                        <option value="What is the name of your favorite teacher?">What is the name of your favorite teacher?</option>
                        <option value="What was your dream job as a child?">What was your dream job as a child?</option>
                        <option value="What is your favorite movie?">What is your favorite movie?</option>
                    </select>
                </div>

                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="securityAnswer2" name="securityAnswer2" placeholder="Answer" required>
                    <label for="securityAnswer2">Answer</label>
                </div>

                <button class="btn btn-primary w-100 py-2" type="submit">Next</button>
            </form>
        </main>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
