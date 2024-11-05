<?php
session_start();

if (!isset($_SESSION['userId']) || !isset($_POST['selectedSlots']) || !isset($_POST['date']) || !isset($_POST['totalPayment'])) {
    header("Location: index.php");
    exit();
}

// Include database connection
require_once 'connection_customer.php';

// Fetch user phone number
$userID = $_SESSION['userId'];
try {
    $sql = "SELECT uPhoneNumber FROM User WHERE uUserID = :userID";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':userID', $userID, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch();
    $userPhoneNumber = $result['uPhoneNumber'] ?? '';
} catch (PDOException $e) {
    error_log("Query failed: " . $e->getMessage());
    $userPhoneNumber = '';
}

// Store the reservation details in session
$_SESSION['date'] = $_POST['date'];
$_SESSION['selectedSlots'] = $_POST['selectedSlots'];
$_SESSION['totalPayment'] = $_POST['totalPayment'];

$date = $_POST['date'];
$selectedSlots = explode('|', $_POST['selectedSlots']);
$totalPayment = $_POST['totalPayment'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GCash Payment Page</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .payment-container {
            background-color: white;
            max-width: 400px;
            margin: 100px auto;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            background-color: #1c2a4a;
            padding: 20px;
            color: white;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
            text-align: center;
        }
        .content {
            padding: 20px;
        }
        .content .form-group {
            margin-bottom: 1.5rem;
        }
        .amount-due {
            color: #007bff;
            font-weight: bold;
        }
        .cancel-link {
            display: block;
            text-align: center;
            color: gray;
            margin-top: 20px;
        }
    </style>
</head>
<body>

<div class="payment-container">
    <div class="header">
        <h2>GCash</h2>
    </div>
    <div class="content">
        <div class="mb-4">
            <div><strong>Merchant</strong>: <span id="merchant"> &nbsp;&nbsp;&nbsp;&nbsp; JCAS Sports Alley</span></div>
            <div><strong>Amount Due</strong>: <span class="amount-due">PHP <?php echo htmlspecialchars($totalPayment); ?>.00</span></div>
        </div>
        <form id="gcashForm" action="page_gcash_payment.php" method="post">
            <div class="form-group">
                <label for="mobile-number">Login to pay with GCash</label>
                <input type="text" class="form-control" id="mobile-number" name="mobile-number" placeholder="ex. 09995554433" pattern="^09\d{9}$" required title="Enter mobile number ex. 09*********" value="<?php echo htmlspecialchars($userPhoneNumber); ?>">
            </div>
            <input type="hidden" name="userID" value="<?php echo $_SESSION['userId']; ?>">
            <input type="hidden" name="date" value="<?php echo htmlspecialchars($date); ?>">
            <input type="hidden" name="selectedSlots" value="<?php echo htmlspecialchars(implode('|', $selectedSlots)); ?>">
            <input type="hidden" name="totalPayment" value="<?php echo $totalPayment; ?>">
            <button type="submit" class="btn btn-primary btn-block">Next</button>
            <a href="process_reservation_cancel.php" class="cancel-link">Cancel Transaction</a>
        </form>
    </div>
</div>

</body>
</html>
