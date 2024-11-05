<?php
session_start();

if (!isset($_SESSION['userId']) || !isset($_SESSION['date']) || !isset($_SESSION['selectedSlots']) || !isset($_SESSION['totalPayment'])) {
    header("Location: index.php");
    exit();
}

// Store the mobile number in session
$_SESSION['mobileNumber'] = $_POST['mobile-number'];

$userID = $_SESSION['userId'];
$date = $_SESSION['date'];
$selectedSlots = explode('|', $_SESSION['selectedSlots']);
$totalPayment = $_SESSION['totalPayment'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GCash Payment Confirmation</title>
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
        <form action="process_reservation.php" method="post">
            <input type="hidden" name="userID" value="<?php echo $_SESSION['userId']; ?>">
            <input type="hidden" name="date" value="<?php echo $date; ?>">
            <input type="hidden" name="selectedSlots" value="<?php echo htmlspecialchars(implode('|', $selectedSlots)); ?>">
            <input type="hidden" name="totalPayment" value="<?php echo $totalPayment; ?>">
            <button type="submit" class="btn btn-primary btn-block">Pay PHP <?php echo htmlspecialchars($totalPayment); ?>.00</button>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
