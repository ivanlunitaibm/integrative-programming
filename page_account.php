<?php
session_start();

// Redirect if session variables are not set
if (!isset($_SESSION['userId'])) {
    header("Location: index.php");
    exit();
}

// Include database connection
require 'connection_customer.php';

$userID = $_SESSION['userId'];
$user = [];
$alertMessage = '';

// Fetch user information
try {
    $sql = "SELECT uFName, uLName, uPhoneNumber, uEmail, uRegDate, uUpdDate FROM User WHERE uUserID = :userID";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['userID' => $userID]);
    $user = $stmt->fetch();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit();
}

// Update user information if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $newFName = $_POST['uFName'];
    $newLName = $_POST['uLName'];
    $newPhoneNumber = $_POST['uPhoneNumber'];

    // Check if the new phone number already exists
    try {
        $sql = "SELECT COUNT(*) FROM User WHERE uPhoneNumber = :uPhoneNumber AND uUserID != :userID";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'uPhoneNumber' => $newPhoneNumber,
            'userID' => $userID
        ]);
        $phoneExists = $stmt->fetchColumn() > 0;

        if ($phoneExists) {
            $alertMessage = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>Error:</strong> Phone number already exists.
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>';
        } else {
            // Update the user information
            $sql = "UPDATE User SET uFName = :uFName, uLName = :uLName, uPhoneNumber = :uPhoneNumber, uUpdDate = NOW() WHERE uUserID = :userID";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'uFName' => $newFName,
                'uLName' => $newLName,
                'uPhoneNumber' => $newPhoneNumber,
                'userID' => $userID
            ]);

            // Insert record into AuditUser table
            $sql = "INSERT INTO AuditUser (aAction, aUserID) VALUES (:action, :userID)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'action' => 'Updated Profile',
                'userID' => $userID
            ]);

            // Refresh user information after update
            $stmt = $pdo->prepare("SELECT uFName, uLName, uPhoneNumber, uEmail, uRegDate, uUpdDate FROM User WHERE uUserID = :userID");
            $stmt->execute(['userID' => $userID]);
            $user = $stmt->fetch();

            $alertMessage = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                                <strong>Success:</strong> Your information has been updated.
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>';
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
        exit();
    }
}

// Function to format date and time to the specified format
function formatDateTime($dateTime) {
    return date('F d, Y h:i A', strtotime($dateTime));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - Courtlify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-image: url('bgimg.jpg'); /* Provide background image that covers the entire screen */
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            height: 100vh;
        }
        .jumbotron-custom {
            max-width: 700px;
            margin: auto;
            padding: 2rem;
            background-color: #f8f9fa;
            border-radius: 10px;

        }
        .form-group p {
            margin-bottom: 0;
        }
        .display-4{
            font-size: 2em;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="jumbotron jumbotron-custom">
            <h1 class="display-4">My Account</h1>
            <p class="lead">Manage your account information.</p>
            <hr class="my-4">
            <?php echo $alertMessage; ?>
            <form method="POST" action="page_account.php">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="uFName" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="uFName" name="uFName" value="<?php echo htmlspecialchars($user['uFName']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="uLName" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="uLName" name="uLName" value="<?php echo htmlspecialchars($user['uLName']); ?>" required>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="uPhoneNumber" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="uPhoneNumber" name="uPhoneNumber" value="<?php echo htmlspecialchars($user['uPhoneNumber']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="uEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="uEmail" name="uEmail" value="<?php echo htmlspecialchars($user['uEmail']); ?>" disabled>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="uRegDate" class="form-label">Account Created Date</label>
                        <p class="form-control-plaintext"><?php echo htmlspecialchars(formatDateTime($user['uRegDate'])); ?></p>
                    </div>
                    <div class="col-md-6">
                        <label for="uUpdDate" class="form-label">Last Updated Date</label>
                        <p class="form-control-plaintext"><?php echo htmlspecialchars(formatDateTime($user['uUpdDate'])); ?></p>
                    </div>
                </div>
                <button type="submit" class="btn btn-success">Update</button>
                <a href="cust_landingpage.php" class="btn btn-secondary">Back</a>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
